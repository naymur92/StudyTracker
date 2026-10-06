<?php

namespace App\Services\StudyTracker;

use App\Models\Topic;
use App\Services\StudyTracker\Scheduling\Schedule;
use App\Services\StudyTracker\Scheduling\ScheduleState;

/**
 * Reads a topic's stored schedule and state, lazily adopting the adaptive
 * scheduler for topics created before it existed.
 */
class TopicScheduleResolver
{
    public function __construct(private ResolveScheduleService $resolver) {}

    /**
     * Ensure the topic has a schedule snapshot; persists it for legacy topics.
     */
    public function ensure(Topic $topic): Schedule
    {
        if ($topic->srs_offsets) {
            return $this->fromTopic($topic);
        }

        [$schedule, $step] = $this->resolveLegacy($topic);

        $this->resolver->applySnapshot($topic, $schedule);
        $topic->srs_step = $step;
        $topic->save();

        return $schedule;
    }

    /**
     * The topic's schedule and state without saving anything (for previews).
     *
     * @return array{0: Schedule, 1: ScheduleState}
     */
    public function peek(Topic $topic): array
    {
        if ($topic->srs_offsets) {
            return [$this->fromTopic($topic), $this->state($topic)];
        }

        [$schedule, $step] = $this->resolveLegacy($topic);

        return [$schedule, new ScheduleState($step, (int) $topic->srs_lapses)];
    }

    public function state(Topic $topic): ScheduleState
    {
        return new ScheduleState((int) $topic->srs_step, (int) $topic->srs_lapses);
    }

    private function fromTopic(Topic $topic): Schedule
    {
        return new Schedule(
            $topic->srs_offsets,
            $topic->srs_repeat_every_days,
            $topic->srs_repeat_until,
            $topic->srs_schedule_source ?? 'builtin',
        );
    }

    /** @return array{0: Schedule, 1: int} */
    private function resolveLegacy(Topic $topic): array
    {
        $schedule = $this->resolver->forNewTopic($topic->user_id, $topic->category_id, $topic->kind ?? Topic::KIND_TOPIC);

        $completed = $topic->studyTasks()
            ->where('task_type', 'revision')
            ->where('status', 'completed')
            ->count();

        return [$schedule, min($completed, $schedule->stepCount())];
    }
}
