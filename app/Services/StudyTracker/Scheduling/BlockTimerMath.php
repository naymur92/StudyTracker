<?php

namespace App\Services\StudyTracker\Scheduling;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Pure arithmetic for weekly-plan block timers. All times are in seconds of
 * block time used (pauses excluded). Mirrored in
 * resources/js/components/timer/timerMath.js — keep both in step.
 *
 * Break pattern: `every` minutes of work, then `break` minutes of break,
 * repeated. Breaks are inside the block's planned time, and a break that
 * would end at or after the block's end is not taken.
 */
class BlockTimerMath
{
    /** Time used by one run at $now: closed stretches plus the running one. */
    public static function runUsedSeconds(int $usedSeconds, ?CarbonInterface $resumedAt, CarbonInterface $now): int
    {
        if ($resumedAt === null) {
            return $usedSeconds;
        }

        return $usedSeconds + max(0, $now->getTimestamp() - $resumedAt->getTimestamp());
    }

    /**
     * The block's phases in order.
     *
     * @return list<array{phase: string, start: int, end: int}>
     */
    public static function phases(int $plannedSeconds, ?int $everyMinutes, ?int $breakMinutes): array
    {
        if ($plannedSeconds <= 0) {
            return [];
        }

        if (! $everyMinutes || ! $breakMinutes) {
            return [['phase' => 'work', 'start' => 0, 'end' => $plannedSeconds]];
        }

        $every = $everyMinutes * 60;
        $break = $breakMinutes * 60;
        $phases = [];
        $t = 0;

        while ($t < $plannedSeconds) {
            $workEnd = min($t + $every, $plannedSeconds);
            $breakEnd = $workEnd + $break;

            if ($workEnd >= $plannedSeconds || $breakEnd >= $plannedSeconds) {
                // No room for a break that ends before the block does: work to the end.
                $phases[] = ['phase' => 'work', 'start' => $t, 'end' => $plannedSeconds];
                break;
            }

            $phases[] = ['phase' => 'work', 'start' => $t, 'end' => $workEnd];
            $phases[] = ['phase' => 'break', 'start' => $workEnd, 'end' => $breakEnd];
            $t = $breakEnd;
        }

        return $phases;
    }

    /**
     * The phase at $usedSeconds and the seconds left in it. Past the end the
     * phase is `work` with 0 seconds left.
     *
     * @return array{phase: string, left: int}
     */
    public static function phaseAt(int $usedSeconds, int $plannedSeconds, ?int $everyMinutes, ?int $breakMinutes): array
    {
        foreach (self::phases($plannedSeconds, $everyMinutes, $breakMinutes) as $phase) {
            if ($usedSeconds >= $phase['start'] && $usedSeconds < $phase['end']) {
                return ['phase' => $phase['phase'], 'left' => $phase['end'] - $usedSeconds];
            }
        }

        return ['phase' => 'work', 'left' => 0];
    }

    /** Start (in seconds used) of the first break after $usedSeconds, or null. */
    public static function nextBreakAt(int $usedSeconds, int $plannedSeconds, ?int $everyMinutes, ?int $breakMinutes): ?int
    {
        foreach (self::phases($plannedSeconds, $everyMinutes, $breakMinutes) as $phase) {
            if ($phase['phase'] === 'break' && $phase['start'] > $usedSeconds) {
                return $phase['start'];
            }
        }

        return null;
    }

    /**
     * When a running run uses up the block's time: $resumedAt plus the time
     * still left once earlier runs ($priorSeconds) and this run's closed
     * stretches ($runUsedSeconds) are counted.
     */
    public static function runOutAt(CarbonInterface $resumedAt, int $runUsedSeconds, int $priorSeconds, int $plannedSeconds): CarbonImmutable
    {
        return CarbonImmutable::parse($resumedAt)->addSeconds(max(0, $plannedSeconds - $priorSeconds - $runUsedSeconds));
    }
}
