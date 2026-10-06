<?php

namespace App\Services\StudyTracker\Scheduling;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * A topic's review schedule: ascending day offsets plus an optional repeat tail.
 */
final class Schedule
{
    /** @var int[] */
    public readonly array $offsets;

    public readonly ?CarbonImmutable $repeatUntil;

    /**
     * @param  int[]  $offsets  strictly increasing day offsets, e.g. [1, 7, 30, 90]
     */
    public function __construct(
        array $offsets,
        public readonly ?int $repeatEveryDays = null,
        CarbonInterface|string|null $repeatUntil = null,
        public readonly string $source = 'builtin',
        /** @var string[] optional step names (template names), index-aligned with offsets */
        public readonly array $stepNames = [],
    ) {
        $offsets = array_values(array_map('intval', $offsets));

        if ($offsets === []) {
            throw new InvalidArgumentException('A schedule needs at least one offset.');
        }

        $this->offsets = $offsets;
        $this->repeatUntil = $repeatUntil === null ? null : CarbonImmutable::parse($repeatUntil)->startOfDay();
    }

    public function stepCount(): int
    {
        return count($this->offsets);
    }

    /** Offset of step $step (1-indexed). */
    public function offset(int $step): int
    {
        return $this->offsets[$step - 1];
    }

    public function hasRepeat(): bool
    {
        return $this->repeatEveryDays !== null && $this->repeatEveryDays > 0;
    }

    /** The next repeat review after $from, or null when there is none (no rule or past the until date). */
    public function repeatAfter(CarbonInterface $from): ?CarbonImmutable
    {
        if (! $this->hasRepeat()) {
            return null;
        }

        $date = CarbonImmutable::parse($from)->startOfDay()->addDays($this->repeatEveryDays);

        if ($this->repeatUntil !== null && $date->gt($this->repeatUntil)) {
            return null;
        }

        return $date;
    }
}
