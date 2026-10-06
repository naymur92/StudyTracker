<?php

namespace App\Services\StudyTracker\DataDeletion;

use App\Models\DataDeletionRequest;
use App\Models\User;
use App\Services\StudyTracker\BlockTimerService;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Carries out an approved request: collect, archive, verify, then delete
 * exactly the archived rows in the same transaction. Any failure rolls back,
 * removes the archive file and marks the request failed.
 */
class ExecuteDataDeletionService
{
    private const CHUNK = 500;

    public function __construct(
        private DataCollector $collector,
        private DataArchiveService $archives,
        private DataCategoryRegistry $registry,
        private BlockTimerService $timers,
    ) {}

    public function run(DataDeletionRequest $request): void
    {
        $claimed = DataDeletionRequest::whereKey($request->id)
            ->where('status', DataDeletionRequest::STATUS_APPROVED)
            ->update([
                'status' => DataDeletionRequest::STATUS_PROCESSING,
                'processing_started_at' => now(),
                'failed_at' => null,
                'error_message' => null,
            ]);

        // Another worker or a duplicate dispatch already took it.
        if ($claimed !== 1) {
            return;
        }

        $request->refresh();
        $archive = null;

        try {
            $user = $request->user ?? throw new RuntimeException('The requesting user no longer exists.');

            // A timer that ran out completes its block first, as on any read.
            if (in_array('weekly_plans', $request->categories, true)) {
                $this->timers->settle($user);
            }

            $plan = DB::transaction(function () use ($request, $user, &$archive) {
                User::whereKey($user->id)->lockForUpdate()->first();

                // An active run is archived as ended, so a restore never brings
                // back a second active timer.
                if (in_array('weekly_plans', $request->categories, true)) {
                    $this->timers->endActiveRun($user);
                }

                $plan = $this->collector->collect($user, $request->categories, lock: true);

                $archive = $this->archives->write($request, $plan, $user);
                $this->archives->verify($archive['path'], $archive['checksum'], $plan);

                $this->clearReferences($plan);
                $this->deleteRows($plan);

                if ($plan->clearsPreferences()) {
                    DB::table('users')->where('id', $user->id)->update(['study_preferences' => null]);
                }

                return $plan;
            });
        } catch (Throwable $e) {
            if ($archive !== null) {
                $this->archives->delete($archive['path']);
            }

            $request->forceFill([
                'status' => DataDeletionRequest::STATUS_FAILED,
                'failed_at' => now(),
                'error_message' => mb_substr($e->getMessage(), 0, 2000),
            ])->save();

            DataDeletionAudit::log('failed', $request, $request->reviewer, ['error' => $request->error_message]);
            report($e);

            return;
        }

        $request->forceFill([
            'status' => DataDeletionRequest::STATUS_COMPLETED,
            'completed_at' => now(),
            'archive_path' => $archive['path'],
            'archive_size' => $archive['size'],
            'archive_checksum' => $archive['checksum'],
            'archive_purged_at' => null,
            'deleted_counts' => $plan->counts(),
        ])->save();

        DataDeletionAudit::log('completed', $request, $request->reviewer, ['counts' => $plan->counts()]);
    }

    /** Null nullable references on rows that stay. */
    private function clearReferences(DeletionPlan $plan): void
    {
        $grouped = [];
        foreach ($plan->references() as $reference) {
            $grouped[$reference['table']][$reference['column']][] = $reference['id'];
        }

        foreach ($grouped as $table => $columns) {
            foreach ($columns as $column => $ids) {
                foreach (array_chunk($ids, self::CHUNK) as $chunk) {
                    DB::table($table)->whereIn('id', $chunk)->update([$column => null]);
                }
            }
        }
    }

    /** Delete exactly the planned ids, children first. */
    private function deleteRows(DeletionPlan $plan): void
    {
        // Self-references inside a table (mistake → topic, task → task) are
        // cut first so the rows can go in any order.
        foreach ($this->registry->edges(required: false) as [$child, $column, $parent]) {
            if ($child === $parent) {
                foreach (array_chunk($plan->ids($child), self::CHUNK) as $chunk) {
                    DB::table($child)->whereIn('id', $chunk)->whereNotNull($column)->update([$column => null]);
                }
            }
        }

        foreach ($this->registry->registeredTables() as $table) {
            $ids = $plan->ids($table);
            $deleted = 0;

            foreach (array_chunk($ids, self::CHUNK) as $chunk) {
                $deleted += DB::table($table)->whereIn('id', $chunk)->delete();
            }

            if ($deleted !== count($ids)) {
                throw new RuntimeException('Expected to delete '.count($ids)." {$table} rows but deleted {$deleted}.");
            }
        }
    }
}
