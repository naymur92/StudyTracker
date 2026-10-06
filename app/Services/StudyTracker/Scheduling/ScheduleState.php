<?php

namespace App\Services\StudyTracker\Scheduling;

/**
 * Where a topic is in its schedule: steps passed and failed recalls.
 */
final class ScheduleState
{
    public function __construct(
        public readonly int $step = 0,
        public readonly int $lapses = 0,
    ) {}
}
