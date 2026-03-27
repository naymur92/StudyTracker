<?php

namespace App\Console\Commands;

use App\Models\StudyTask;
use Illuminate\Console\Command;

class MarkMissedTasks extends Command
{
    protected $signature = 'study:mark-missed';

    protected $description = 'Mark overdue pending tasks as missed';

    public function handle(): int
    {
        $count = StudyTask::where('status', 'pending')
            ->where('scheduled_date', '<', today())
            ->update(['status' => 'missed']);

        $this->info("Marked {$count} overdue tasks as missed.");

        return self::SUCCESS;
    }
}
