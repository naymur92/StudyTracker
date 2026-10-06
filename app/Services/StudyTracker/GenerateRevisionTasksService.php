<?php

namespace App\Services\StudyTracker;

use App\Models\StudyTask;
use App\Models\Topic;
use App\Services\StudyTracker\Scheduling\RecallScheduler;
use App\Services\StudyTracker\Scheduling\Schedule;
use App\Services\StudyTracker\Scheduling\ScheduleState;

class GenerateRevisionTasksService
{
    public function __construct(
        private RecallScheduler $scheduler,
        private ResolveScheduleService $resolver,
    ) {}

    /**
     * Create the initial revision tasks: first study date + each offset, plus
     * one repeat review when the schedule repeats (within its until date).
     */
    public function execute(int $userId, Topic $topic, ?Schedule $schedule = null): void
    {
        $schedule ??= $this->resolver->forNewTopic($userId, $topic->category_id, $topic->kind ?? Topic::KIND_TOPIC);

        $first = $this->scheduler->firstReview($schedule, $topic->first_study_date);
        $sequence = $this->scheduler->project($schedule, new ScheduleState(0), $first, StudyTask::REVIEW_KIND_STEP);

        foreach ($sequence as $i => $item) {
            $revisionNo = $i + 1;

            StudyTask::create([
                'user_id'        => $userId,
                'topic_id'       => $topic->id,
                'task_type'      => 'revision',
                'revision_no'    => $revisionNo,
                'review_kind'    => $item['kind'],
                'title'          => ReplanRevisionsService::title($item['kind'], $revisionNo, $topic->title, $schedule->stepNames[$i] ?? null),
                'scheduled_date' => $item['date']->toDateString(),
                'status'         => 'pending',
            ]);
        }
    }
}
