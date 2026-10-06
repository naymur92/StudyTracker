<?php

namespace App\Console\Commands;

use App\Models\DataDeletionRequest;
use App\Services\StudyTracker\DataDeletion\DataArchiveService;
use App\Services\StudyTracker\DataDeletion\DataDeletionAudit;
use Illuminate\Console\Command;

class PurgeDataArchives extends Command
{
    protected $signature = 'data-requests:purge-archives {--dry-run : List the archives that would be purged without deleting them}';

    protected $description = 'Delete data deletion archives older than the retention period (study.data_deletion.archive_retention_days)';

    public function handle(DataArchiveService $archives): int
    {
        $days = (int) config('study.data_deletion.archive_retention_days');
        $cutoff = now()->subDays($days);
        $dryRun = (bool) $this->option('dry-run');
        $count = 0;

        DataDeletionRequest::whereNotNull('archive_path')
            ->whereNull('archive_purged_at')
            ->where('completed_at', '<', $cutoff)
            ->chunkById(100, function ($requests) use ($archives, $dryRun, &$count) {
                foreach ($requests as $request) {
                    $count++;

                    if ($dryRun) {
                        $this->line("Would purge {$request->reference()} (completed {$request->completed_at->toDateString()})");

                        continue;
                    }

                    $archives->delete($request->archive_path); // a missing file is fine
                    $request->forceFill(['archive_purged_at' => now()])->save();
                    DataDeletionAudit::log('archive_purged', $request, null);
                }
            });

        $this->info($dryRun
            ? "{$count} archive(s) older than {$days} days would be purged."
            : "Purged {$count} archive(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
