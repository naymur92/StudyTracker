<?php

namespace App\Services\StudyTracker\Scheduling;

use App\Models\StudyTask;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Pure, deterministic review scheduler (Leitner-style steps over a topic's offsets).
 *
 * State: srs_step = number of schedule steps passed. The next review always
 * tests step srs_step + 1. On review date D:
 *
 *  again → step 0, lapses + 1, relearn check on D + 1
 *  hard  → step unchanged,     relearn check on D + 1
 *  good  → step + 1, next on D + (offset[step'+1] − offset[tested])
 *  easy  → step + 2, next on D + (offset[step'+1] − offset[tested])
 *
 * After the final step, good/easy schedule a repeat review on D + repeat
 * (until the repeat-until date) or graduate the topic.
 */
class RecallScheduler
{
    public function grade(Schedule $schedule, ScheduleState $state, string $grade, CarbonInterface $reviewDate): ScheduleOutcome
    {
        if (! in_array($grade, StudyTask::GRADES, true)) {
            throw new InvalidArgumentException("Unknown recall grade [{$grade}].");
        }

        $date = CarbonImmutable::parse($reviewDate)->startOfDay();
        $n = $schedule->stepCount();
        $step = min($state->step, $n);

        if ($grade === 'again') {
            return new ScheduleOutcome(
                new ScheduleState(0, $state->lapses + 1),
                $date->addDay(),
                StudyTask::REVIEW_KIND_RELEARN,
            );
        }

        if ($grade === 'hard') {
            return new ScheduleOutcome(
                new ScheduleState($step, $state->lapses),
                $date->addDay(),
                StudyTask::REVIEW_KIND_RELEARN,
            );
        }

        $newStep = min($step + ($grade === 'easy' ? 2 : 1), $n);
        $newState = new ScheduleState($newStep, $state->lapses);

        // A normal step remains: space it from the offset just tested.
        if ($step < $n && $newStep < $n) {
            $gap = $schedule->offset($newStep + 1) - $schedule->offset($step + 1);

            return new ScheduleOutcome($newState, $date->addDays($gap), StudyTask::REVIEW_KIND_STEP);
        }

        // Final step passed (or this was a repeat review): repeat tail or graduate.
        $repeat = $schedule->repeatAfter($date);

        return new ScheduleOutcome($newState, $repeat, $repeat ? StudyTask::REVIEW_KIND_REPEAT : null);
    }

    /** Ungraded completion: count the step as passed without moving any dates. */
    public function advanceUngraded(Schedule $schedule, ScheduleState $state): ScheduleState
    {
        return new ScheduleState(min($state->step + 1, $schedule->stepCount()), $state->lapses);
    }

    /**
     * The first review after learning (or re-anchoring) on $learnDate.
     */
    public function firstReview(Schedule $schedule, CarbonInterface $learnDate): CarbonImmutable
    {
        return CarbonImmutable::parse($learnDate)->startOfDay()->addDays($schedule->offset(1));
    }

    /**
     * Projected review sequence starting with a known next review, assuming every
     * later review is graded good: remaining steps, then at most one repeat review.
     *
     * @return array<int, array{date: CarbonImmutable, kind: string}>
     */
    public function project(Schedule $schedule, ScheduleState $state, ?CarbonInterface $firstDate, ?string $firstKind): array
    {
        if ($firstDate === null || $firstKind === null) {
            return [];
        }

        $current = CarbonImmutable::parse($firstDate)->startOfDay();
        $sequence = [['date' => $current, 'kind' => $firstKind]];

        if ($firstKind === StudyTask::REVIEW_KIND_REPEAT) {
            return $sequence;
        }

        $n = $schedule->stepCount();
        $tested = $state->step + 1; // step tested by the first review

        while ($tested < $n) {
            $current = $current->addDays($schedule->offset($tested + 1) - $schedule->offset($tested));
            $sequence[] = ['date' => $current, 'kind' => StudyTask::REVIEW_KIND_STEP];
            $tested++;
        }

        if ($repeat = $schedule->repeatAfter($current)) {
            $sequence[] = ['date' => $repeat, 'kind' => StudyTask::REVIEW_KIND_REPEAT];
        }

        return $sequence;
    }

    /**
     * Next review date for each grade, for previews.
     *
     * @return array{again: ?string, hard: ?string, good: ?string, easy: ?string}
     */
    public function preview(Schedule $schedule, ScheduleState $state, CarbonInterface $reviewDate): array
    {
        $preview = [];

        foreach (StudyTask::GRADES as $grade) {
            $preview[$grade] = $this->grade($schedule, $state, $grade, $reviewDate)->nextDate?->toDateString();
        }

        return $preview;
    }
}
