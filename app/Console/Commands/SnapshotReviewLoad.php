<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\StudyTracker\ReviewLoadService;
use Illuminate\Console\Command;

class SnapshotReviewLoad extends Command
{
    protected $signature = 'study:snapshot-review-load';

    protected $description = 'Record each user\'s start-of-day review load (used for review-debt detection)';

    public function handle(ReviewLoadService $service): int
    {
        $count = 0;

        User::whereHas('studyTasks', function ($q) {
            $q->where('task_type', 'revision')
                ->whereIn('status', ['pending', 'missed'])
                ->whereDate('scheduled_date', '<=', today()->toDateString());
        })->chunkById(200, function ($users) use ($service, &$count) {
            foreach ($users as $user) {
                $service->snapshot($user);
                $count++;
            }
        });

        $this->info("Recorded review-load snapshots for {$count} users.");

        return self::SUCCESS;
    }
}
