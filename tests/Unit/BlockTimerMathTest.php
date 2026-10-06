<?php

namespace Tests\Unit;

use App\Services\StudyTracker\Scheduling\BlockTimerMath;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Same vectors as scripts/check-timer-math.mjs (the JS mirror).
 */
class BlockTimerMathTest extends TestCase
{
    /** @return list<array{0: string, 1: int, 2: int}> phases as [phase, start min, end min] */
    private function phases(int $plannedMinutes, ?int $every, ?int $break): array
    {
        return array_map(
            fn (array $p) => [$p['phase'], $p['start'] / 60, $p['end'] / 60],
            BlockTimerMath::phases($plannedMinutes * 60, $every, $break)
        );
    }

    public function test_block_a_with_breaks_has_five_phases(): void
    {
        $this->assertSame([
            ['work', 0, 50], ['break', 50, 60], ['work', 60, 110], ['break', 110, 120], ['work', 120, 150],
        ], $this->phases(150, 50, 10));
    }

    public function test_break_that_would_reach_the_end_is_dropped(): void
    {
        $this->assertSame([
            ['work', 0, 50], ['break', 50, 60], ['work', 60, 120],
        ], $this->phases(120, 50, 10));
    }

    public function test_block_shorter_than_the_work_stretch_has_no_break(): void
    {
        $this->assertSame([['work', 0, 20]], $this->phases(20, 25, 5));
    }

    public function test_no_pattern_is_work_until_the_end(): void
    {
        $this->assertSame([['work', 0, 90]], $this->phases(90, null, null));
        $this->assertSame(['phase' => 'work', 'left' => 5400], BlockTimerMath::phaseAt(0, 5400, null, null));
        $this->assertSame(['phase' => 'work', 'left' => 600], BlockTimerMath::phaseAt(4800, 5400, null, null));
        $this->assertNull(BlockTimerMath::nextBreakAt(0, 5400, null, null));
    }

    public function test_phase_at_52_minutes_of_a_90_minute_block(): void
    {
        $this->assertSame(['phase' => 'break', 'left' => 480], BlockTimerMath::phaseAt(52 * 60, 90 * 60, 50, 10));
        $this->assertSame(['phase' => 'work', 'left' => 3000], BlockTimerMath::phaseAt(0, 90 * 60, 50, 10));
        $this->assertSame(['phase' => 'work', 'left' => 1800], BlockTimerMath::phaseAt(60 * 60, 90 * 60, 50, 10));
        $this->assertSame(['phase' => 'work', 'left' => 0], BlockTimerMath::phaseAt(90 * 60, 90 * 60, 50, 10));
    }

    public function test_next_break(): void
    {
        $this->assertSame(3000, BlockTimerMath::nextBreakAt(0, 9000, 50, 10));
        $this->assertSame(6600, BlockTimerMath::nextBreakAt(3100, 9000, 50, 10), 'during a break, the next one');
        $this->assertNull(BlockTimerMath::nextBreakAt(6600, 9000, 50, 10));
    }

    public function test_paused_time_does_not_count(): void
    {
        // Started 10:00, paused 10:20 (1200 s closed), resumed 10:30, read 10:45.
        $used = BlockTimerMath::runUsedSeconds(1200, CarbonImmutable::parse('2026-10-13 10:30:00'), CarbonImmutable::parse('2026-10-13 10:45:00'));
        $this->assertSame(35 * 60, $used);

        // While paused nothing grows.
        $this->assertSame(1200, BlockTimerMath::runUsedSeconds(1200, null, CarbonImmutable::parse('2026-10-13 10:28:00')));
    }

    public function test_run_out_at(): void
    {
        // 90-minute block, 40 minutes from an earlier run, this run resumed at 14:00 with 5 minutes closed.
        $end = BlockTimerMath::runOutAt(CarbonImmutable::parse('2026-10-13 14:00:00'), 300, 2400, 5400);
        $this->assertSame('2026-10-13 14:45:00', $end->toDateTimeString());
    }
}
