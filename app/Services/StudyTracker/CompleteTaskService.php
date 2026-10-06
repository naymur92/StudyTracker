<?php

namespace App\Services\StudyTracker;

use App\Models\StudyTask;
use App\Models\Topic;
use App\Services\StudyTracker\Scheduling\RecallScheduler;
use App\Services\StudyTracker\Scheduling\ScheduleState;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompleteTaskService
{
    public function __construct(
        private RecallScheduler $scheduler,
        private TopicScheduleResolver $scheduleResolver,
        private ReplanRevisionsService $replanner,
    ) {}

    /**
     * Complete a task. A graded revision re-plans the topic's pending reviews;
     * completing the learn task late re-anchors them on the completion date.
     *
     * @return array{task: StudyTask, schedule_outcome: ?array}
     */
    public function execute(StudyTask $task, array $data = []): array
    {
        return DB::transaction(function () use ($task, $data) {
            $topic = Topic::withTrashed()->lockForUpdate()->find($task->topic_id);
            $task = StudyTask::lockForUpdate()->findOrFail($task->id);

            if ($task->status === 'completed') {
                throw ValidationException::withMessages([
                    'task' => 'This task is already completed.',
                ]);
            }

            $isRevision = $task->task_type === 'revision';
            $grade = $isRevision ? ($data['recall_grade'] ?? null) : null;

            $task->update([
                'status'              => 'completed',
                'completed_at'        => now(),
                'locked_at'           => now(),
                'is_date_locked'      => true,
                'notes'               => $data['notes'] ?? $task->notes,
                'difficulty_feedback' => $data['difficulty_feedback'] ?? null,
                'recall_grade'        => $grade,
                'review_seconds'      => $isRevision ? ($data['review_seconds'] ?? null) : null,
            ]);

            $outcome = null;

            if ($topic && $isRevision) {
                $outcome = $this->afterRevision($topic, $task, $grade);
            } elseif ($topic && $task->task_type === 'learn') {
                $outcome = $this->afterLearn($topic, $task);
            }

            return ['task' => $task->fresh(), 'schedule_outcome' => $outcome];
        });
    }

    private function afterRevision(Topic $topic, StudyTask $task, ?string $grade): ?array
    {
        $schedule = $this->scheduleResolver->ensure($topic);
        $state = $this->scheduleResolver->state($topic);
        $today = today();

        if ($grade === null) {
            // Ungraded (legacy clients): count the step, keep every date as it is.
            $topic->srs_step = $this->scheduler->advanceUngraded($schedule, $state)->step;
            $topic->last_reviewed_on = $today;
            $topic->save();

            return null;
        }

        $result = $this->scheduler->grade($schedule, $state, $grade, $today);

        $topic->srs_step = $result->state->step;
        $topic->srs_lapses = $result->state->lapses;
        $topic->last_reviewed_on = $today;
        $topic->save();

        $sequence = $this->scheduler->project($schedule, $result->state, $result->nextDate, $result->nextKind);
        $this->replanner->reconcile($topic, $sequence, $task->id);

        return $result->toArray();
    }

    private function afterLearn(Topic $topic, StudyTask $task): ?array
    {
        $hasCompletedRevision = StudyTask::where('topic_id', $topic->id)
            ->where('task_type', 'revision')
            ->where('status', 'completed')
            ->exists();

        // Learned on the planned day, or reviews already started: dates stay as they are.
        if ($hasCompletedRevision || $topic->first_study_date->isSameDay(today())) {
            return null;
        }

        $schedule = $this->scheduleResolver->ensure($topic);
        $state = new ScheduleState(0, (int) $topic->srs_lapses);
        $first = $this->scheduler->firstReview($schedule, today());

        $topic->srs_step = 0;
        $topic->save();

        $this->replanner->reconcile(
            $topic,
            $this->scheduler->project($schedule, $state, $first, StudyTask::REVIEW_KIND_STEP),
            $task->id,
        );

        return [
            'srs_step'         => 0,
            'next_review_date' => $first->toDateString(),
            'next_review_kind' => StudyTask::REVIEW_KIND_STEP,
            'graduated'        => false,
        ];
    }
}
