<?php

namespace App\Services\StudyTracker;

use App\Models\TopicRevisionTemplate;
use App\Models\StudyTask;
use App\Models\Topic;
use Carbon\Carbon;

class GenerateRevisionTasksService
{
    public function execute(int $userId, Topic $topic): void
    {
        $templates = TopicRevisionTemplate::getForUser($userId)
            ->sortBy('sequence_no')
            ->values();

        // Safety fallback in case template table has no active rows.
        if ($templates->isEmpty()) {
            $templates = collect([
                (object) ['name' => 'Revision 1', 'sequence_no' => 1, 'day_offset' => 1],
                (object) ['name' => 'Revision 2', 'sequence_no' => 2, 'day_offset' => 7],
                (object) ['name' => 'Revision 3', 'sequence_no' => 3, 'day_offset' => 30],
                (object) ['name' => 'Revision 4', 'sequence_no' => 4, 'day_offset' => 90],
            ]);
        }

        foreach ($templates as $template) {
            $scheduledDate = Carbon::parse($topic->first_study_date)
                ->addDays($template->day_offset)
                ->toDateString();

            $blockName = trim((string) ($template->name ?? ''));
            $titlePrefix = $blockName !== '' ? $blockName : "Revision {$template->sequence_no}";

            StudyTask::create([
                'user_id'        => $userId,
                'topic_id'       => $topic->id,
                'task_type'      => 'revision',
                'revision_no'    => $template->sequence_no,
                'title'          => "{$titlePrefix}: {$topic->title}",
                'scheduled_date' => $scheduledDate,
                'status'         => 'pending',
            ]);
        }
    }
}
