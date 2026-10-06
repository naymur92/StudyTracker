<?php

namespace Tests\Unit;

use App\Services\StudyTracker\Scheduling\RecallScheduler;
use App\Services\StudyTracker\Scheduling\Schedule;
use App\Services\StudyTracker\Scheduling\ScheduleState;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Tests\TestCase;

class RecallSchedulerTest extends TestCase
{
    private RecallScheduler $scheduler;

    private Schedule $standard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scheduler = new RecallScheduler;
        $this->standard = new Schedule([1, 7, 30, 90]);
    }

    private function d(string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date);
    }

    /** @return string[] */
    private function dates(array $sequence): array
    {
        return array_map(fn ($item) => $item['date']->toDateString(), $sequence);
    }

    public function test_good_on_second_step(): void
    {
        $out = $this->scheduler->grade($this->standard, new ScheduleState(1), 'good', $this->d('2026-10-14'));

        $this->assertSame(2, $out->state->step);
        $this->assertSame('2026-11-06', $out->nextDate->toDateString());
        $this->assertSame('step', $out->nextKind);
    }

    public function test_on_time_good_reviews_match_the_fixed_schedule(): void
    {
        $state = new ScheduleState(0);
        $date = $this->scheduler->firstReview($this->standard, $this->d('2026-10-07'));
        $seen = [];

        while ($date !== null) {
            $seen[] = $date->toDateString();
            $out = $this->scheduler->grade($this->standard, $state, 'good', $date);
            $state = $out->state;
            $date = $out->nextDate;
        }

        $this->assertSame(['2026-10-08', '2026-10-14', '2026-11-06', '2027-01-05'], $seen);
        $this->assertSame(4, $state->step);
    }

    public function test_easy_on_first_step_skips_one(): void
    {
        $out = $this->scheduler->grade($this->standard, new ScheduleState(0), 'easy', $this->d('2026-10-08'));

        $this->assertSame(2, $out->state->step);
        $this->assertSame('2026-11-06', $out->nextDate->toDateString());
    }

    public function test_easy_near_the_end_is_capped_and_graduates(): void
    {
        $out = $this->scheduler->grade($this->standard, new ScheduleState(3), 'easy', $this->d('2026-11-06'));

        $this->assertSame(4, $out->state->step);
        $this->assertTrue($out->graduated());
    }

    public function test_hard_then_good_resumes_normal_spacing(): void
    {
        $hard = $this->scheduler->grade($this->standard, new ScheduleState(2), 'hard', $this->d('2026-11-06'));
        $this->assertSame(2, $hard->state->step);
        $this->assertSame('2026-11-07', $hard->nextDate->toDateString());
        $this->assertSame('relearn', $hard->nextKind);

        $good = $this->scheduler->grade($this->standard, $hard->state, 'good', $hard->nextDate);
        $this->assertSame(3, $good->state->step);
        $this->assertSame('2027-01-06', $good->nextDate->toDateString());
    }

    public function test_again_restarts_the_schedule(): void
    {
        $again = $this->scheduler->grade($this->standard, new ScheduleState(2, 0), 'again', $this->d('2026-11-06'));
        $this->assertSame(0, $again->state->step);
        $this->assertSame(1, $again->state->lapses);
        $this->assertSame('2026-11-07', $again->nextDate->toDateString());
        $this->assertSame('relearn', $again->nextKind);

        $good = $this->scheduler->grade($this->standard, $again->state, 'good', $again->nextDate);
        $this->assertSame(1, $good->state->step);
        $this->assertSame('2026-11-13', $good->nextDate->toDateString());
    }

    public function test_repeat_tail_until_exam_date(): void
    {
        $exam = new Schedule([1, 3, 7, 14], 7, '2026-12-05');

        $out = $this->scheduler->grade($exam, new ScheduleState(3), 'good', $this->d('2026-11-20'));
        $this->assertSame(4, $out->state->step);
        $this->assertSame('2026-11-27', $out->nextDate->toDateString());
        $this->assertSame('repeat', $out->nextKind);

        $out = $this->scheduler->grade($exam, $out->state, 'good', $out->nextDate);
        $this->assertSame('2026-12-04', $out->nextDate->toDateString());

        $out = $this->scheduler->grade($exam, $out->state, 'good', $out->nextDate);
        $this->assertNull($out->nextDate, '2026-12-11 is past the repeat-until date');
    }

    public function test_graduation_without_repeat(): void
    {
        $out = $this->scheduler->grade($this->standard, new ScheduleState(3), 'good', $this->d('2027-01-05'));

        $this->assertSame(4, $out->state->step);
        $this->assertTrue($out->graduated());
        $this->assertNull($out->nextKind);
    }

    public function test_unknown_grade_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->scheduler->grade($this->standard, new ScheduleState(0), 'medium', $this->d('2026-10-08'));
    }

    public function test_ungraded_completion_advances_step_with_cap(): void
    {
        $this->assertSame(2, $this->scheduler->advanceUngraded($this->standard, new ScheduleState(1))->step);
        $this->assertSame(4, $this->scheduler->advanceUngraded($this->standard, new ScheduleState(4))->step);
    }

    public function test_projection_after_again_expands_the_plan(): void
    {
        $again = $this->scheduler->grade($this->standard, new ScheduleState(2), 'again', $this->d('2026-11-06'));
        $sequence = $this->scheduler->project($this->standard, $again->state, $again->nextDate, $again->nextKind);

        $this->assertSame(['2026-11-07', '2026-11-13', '2026-12-06', '2027-02-04'], $this->dates($sequence));
        $this->assertSame(['relearn', 'step', 'step', 'step'], array_column($sequence, 'kind'));
    }

    public function test_projection_for_learning_three_days_late(): void
    {
        $first = $this->scheduler->firstReview($this->standard, $this->d('2026-10-10'));
        $sequence = $this->scheduler->project($this->standard, new ScheduleState(0), $first, 'step');

        $this->assertSame(['2026-10-11', '2026-10-17', '2026-11-09', '2027-01-08'], $this->dates($sequence));
    }

    public function test_initial_projection_includes_one_repeat_review(): void
    {
        $exam = new Schedule([1, 3, 7, 14], 7, '2026-12-05');
        $first = $this->scheduler->firstReview($exam, $this->d('2026-10-11'));
        $sequence = $this->scheduler->project($exam, new ScheduleState(0), $first, 'step');

        $this->assertSame(['2026-10-12', '2026-10-14', '2026-10-18', '2026-10-25', '2026-11-01'], $this->dates($sequence));
        $this->assertSame('repeat', end($sequence)['kind']);
    }

    public function test_preview_for_mid_schedule_topic(): void
    {
        $preview = $this->scheduler->preview($this->standard, new ScheduleState(1), $this->d('2026-10-14'));

        $this->assertSame([
            'again' => '2026-10-15',
            'hard' => '2026-10-15',
            'good' => '2026-11-06',
            'easy' => '2027-01-05',
        ], $preview);
    }

    public function test_preview_at_graduation_is_null_for_good_and_easy(): void
    {
        $preview = $this->scheduler->preview($this->standard, new ScheduleState(3), $this->d('2027-01-05'));

        $this->assertNull($preview['good']);
        $this->assertNull($preview['easy']);
        $this->assertSame('2027-01-06', $preview['hard']);
    }
}
