<?php

namespace App\Services\StudyTracker\Scheduling;

use Carbon\CarbonImmutable;

/**
 * The result of grading a review: the new state and the next review (if any).
 */
final class ScheduleOutcome
{
    public function __construct(
        public readonly ScheduleState $state,
        public readonly ?CarbonImmutable $nextDate,
        public readonly ?string $nextKind,
    ) {}

    public function graduated(): bool
    {
        return $this->nextDate === null;
    }

    public function toArray(): array
    {
        return [
            'srs_step' => $this->state->step,
            'next_review_date' => $this->nextDate?->toDateString(),
            'next_review_kind' => $this->nextKind,
            'graduated' => $this->graduated(),
        ];
    }
}
