<?php

namespace App\Services\StudyTracker;

use App\Models\StudyTask;
use App\Models\Topic;
use Carbon\CarbonImmutable;

/**
 * Reconciles a topic's pending/missed revision tasks with a projected review
 * sequence: updates rows in place (stable IDs), inserts missing rows and
 * deletes leftovers. Completed and skipped revisions are never touched.
 */
class ReplanRevisionsService
{
    private const MAX_REVISION_NO = 255;

    /**
     * @param  array<int, array{date: CarbonImmutable, kind: string}>  $sequence
     */
    public function reconcile(Topic $topic, array $sequence, ?int $excludeTaskId = null): void
    {
        $revisions = StudyTask::where('topic_id', $topic->id)
            ->where('task_type', 'revision')
            ->orderBy('scheduled_date')
            ->orderBy('id')
            ->get();

        $isEligible = fn (StudyTask $t) => in_array($t->status, ['pending', 'missed'], true)
            && $t->id !== $excludeTaskId
            && ! $t->is_date_locked;

        $eligible = $revisions->filter($isEligible)->values();
        $base = (int) $revisions->reject($isEligible)->max('revision_no');

        foreach ($sequence as $i => $item) {
            $no = min($base + $i + 1, self::MAX_REVISION_NO);
            $attributes = [
                'scheduled_date' => $item['date']->toDateString(),
                'status' => 'pending',
                'revision_no' => $no,
                'review_kind' => $item['kind'],
                'title' => self::title($item['kind'], $no, $topic->title),
            ];

            if ($task = $eligible->get($i)) {
                $task->update($attributes);
            } else {
                StudyTask::create($attributes + [
                    'user_id' => $topic->user_id,
                    'topic_id' => $topic->id,
                    'task_type' => 'revision',
                ]);
            }
        }

        $leftover = $eligible->slice(count($sequence))->pluck('id');

        if ($leftover->isNotEmpty()) {
            StudyTask::whereIn('id', $leftover)->delete();
        }
    }

    public static function title(string $kind, int $revisionNo, string $topicTitle, ?string $stepName = null): string
    {
        $prefix = match ($kind) {
            StudyTask::REVIEW_KIND_RELEARN => 'Relearn check',
            StudyTask::REVIEW_KIND_REPEAT => 'Maintenance review',
            default => ($stepName !== null && $stepName !== '') ? $stepName : "Revision {$revisionNo}",
        };

        return "{$prefix}: {$topicTitle}";
    }
}
