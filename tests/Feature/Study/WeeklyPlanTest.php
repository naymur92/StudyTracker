<?php

namespace Tests\Feature\Study;

use App\Models\StudyBlock;
use App\Models\StudyTask;
use App\Models\StudyWeek;
use App\Models\Topic;
use App\Models\User;
use App\Services\IdHasher;
use App\Services\StudyTracker\StudyPreferences;
use App\Services\StudyTracker\WeeklyPlanService;
use Illuminate\Support\Carbon;

class WeeklyPlanTest extends StudyApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-14 09:00:00'); // Wednesday
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function create(string $gear = 'green', string $weekStart = '2026-10-11', array $extra = [])
    {
        return $this->postJson('/api/study/weekly-plan', array_merge(['week_start' => $weekStart, 'gear' => $gear], $extra));
    }

    private function week(): StudyWeek
    {
        return StudyWeek::where('user_id', $this->user->id)->firstOrFail();
    }

    private function blocks(string $date): array
    {
        return StudyBlock::whereDate('block_date', $date)->orderBy('id')->pluck('slot')->sort()->values()->all();
    }

    public function test_week_without_a_plan(): void
    {
        $this->getJson('/api/study/weekly-plan')
            ->assertOk()
            ->assertJsonPath('data.week_start', '2026-10-11')
            ->assertJsonPath('data.week_end', '2026-10-17')
            ->assertJsonPath('data.plan', null)
            ->assertJsonPath('data.score', null);
    }

    public function test_green_template_with_friday_saturday_off(): void
    {
        $this->create('green', '2026-10-11', ['major_focus' => 'IELTS'])->assertCreated()
            ->assertJsonPath('data.plan.gear', 'green')
            ->assertJsonPath('data.plan.major_focus', 'IELTS');

        $this->assertSame(21, StudyBlock::count());
        foreach (['2026-10-11', '2026-10-12', '2026-10-13', '2026-10-14', '2026-10-15'] as $office) {
            $this->assertSame(['minor', 'morning', 'review'], $this->blocks($office));
        }
        foreach (['2026-10-16', '2026-10-17'] as $off) {
            $this->assertSame(['block_a', 'block_b', 'review'], $this->blocks($off));
        }
        $this->assertSame(1130, (int) StudyBlock::sum('planned_minutes'));
    }

    public function test_yellow_and_red_templates(): void
    {
        $this->create('yellow')->assertCreated();
        $this->assertSame(13, StudyBlock::count());
        $this->assertSame(['block_a', 'review'], $this->blocks('2026-10-16'));
        $this->assertSame(['review'], $this->blocks('2026-10-17'));

        $this->deleteJson('/api/study/weekly-plan/'.IdHasher::encode($this->week()->id))->assertOk();
        $this->create('red')->assertCreated();
        $this->assertSame(7, StudyBlock::count());
        $this->assertSame(140, (int) StudyBlock::sum('planned_minutes'));
    }

    public function test_custom_off_days(): void
    {
        StudyPreferences::update($this->user, ['off_days' => [0, 6]]);
        $this->create('green')->assertCreated();

        $this->assertSame(['block_a', 'block_b', 'review'], $this->blocks('2026-10-11'));
        $this->assertSame(['minor', 'morning', 'review'], $this->blocks('2026-10-16'));
    }

    public function test_plan_next_week_wrong_weekday_and_duplicate(): void
    {
        $this->create('green', '2026-10-18')->assertCreated();
        $this->create('green', '2026-10-14')->assertStatus(422)->assertJsonValidationErrors('week_start', 'errors');
        $this->create('red', '2026-10-18')->assertStatus(422);
        $this->assertSame(1, StudyWeek::count());
    }

    public function test_existing_plan_payload_and_weekly_review(): void
    {
        $this->create('green')->assertCreated();
        $url = '/api/study/weekly-plan/'.IdHasher::encode($this->week()->id);

        $this->patchJson($url, [
            'reflection' => 'Release week ate two mornings.',
            'if_then_plan' => 'If the office runs late, then do 10 minutes of due reviews only.',
            'output_note' => 'Essay #4 marked',
        ])->assertOk()
            ->assertJsonPath('data.plan.if_then_plan', 'If the office runs late, then do 10 minutes of due reviews only.');

        $data = $this->getJson('/api/study/weekly-plan?date=2026-10-14')->assertOk()->json('data');
        $this->assertCount(21, $data['blocks']);
        $this->assertSame('2026-10-11', $data['blocks'][0]['block_date']);
        $this->assertSame(['morning', 'review', 'minor'], array_column(array_slice($data['blocks'], 0, 3), 'slot'));
        $this->assertSame(80, $data['success_threshold_percent']);
    }

    public function test_regenerate_downshifts_only_remaining_planned_blocks(): void
    {
        $this->create('green')->assertCreated();
        $week = $this->week();
        StudyBlock::whereDate('block_date', '2026-10-15')->where('slot', 'morning')->update(['status' => 'done']);

        $this->postJson('/api/study/weekly-plan/'.IdHasher::encode($week->id).'/regenerate', ['gear' => 'yellow'])->assertOk()
            ->assertJsonPath('data.plan.gear', 'yellow');

        $this->assertSame(['minor', 'morning', 'review'], $this->blocks('2026-10-11'), 'past days keep their blocks');
        $this->assertSame(['morning', 'review'], $this->blocks('2026-10-14'));
        $this->assertSame(['morning', 'morning', 'review'], $this->blocks('2026-10-15'), 'done block kept, yellow added');
        $this->assertSame(['block_a', 'review'], $this->blocks('2026-10-16'));
    }

    public function test_score_rules(): void
    {
        $week = StudyWeek::factory()->create(['user_id' => $this->user->id, 'week_start' => '2026-10-11']);
        $make = fn (string $date, string $status) => StudyBlock::factory()->create(['study_week_id' => $week->id, 'user_id' => $this->user->id, 'block_date' => $date, 'status' => $status]);

        foreach (range(1, 7) as $i) {
            $make('2026-10-12', 'done');
        }
        foreach (range(1, 2) as $i) {
            $make('2026-10-13', 'partial');
        }
        $make('2026-10-13', 'missed');
        $make('2026-10-13', 'red');
        $make('2026-10-14', 'planned'); // today, not counted yet
        $make('2026-10-16', 'planned'); // future

        $score = $this->getJson('/api/study/weekly-plan')->json('data.score');
        $this->assertSame(10, $score['counted']);
        $this->assertSame(80, $score['percent']);
        $this->assertTrue($score['on_track']);
        $this->assertSame(12, $score['planned_total']);

        $make('2026-10-13', 'planned'); // yesterday, unmarked → missed
        $score = $this->getJson('/api/study/weekly-plan')->json('data.score');
        $this->assertSame(11, $score['counted']);
        $this->assertSame(2, $score['missed']);
    }

    public function test_stats_and_history(): void
    {
        $topic = Topic::factory()->create(['user_id' => $this->user->id]);
        StudyTask::factory()->for($topic)->revision(1)->completed('2026-10-12 20:00:00')->create(['user_id' => $this->user->id, 'review_seconds' => 1800]);
        StudyTask::factory()->for($topic)->revision(2)->completed('2026-10-13 20:00:00')->create(['user_id' => $this->user->id, 'review_seconds' => 1320]);
        StudyTask::factory()->for(Topic::factory()->create(['user_id' => $this->user->id]))->revision(1)->create(['user_id' => $this->user->id, 'scheduled_date' => '2026-10-10']);

        $this->create('green')->assertCreated();
        $stats = $this->getJson('/api/study/weekly-plan')->json('data.stats');
        $this->assertSame(2, $stats['reviews_completed']);
        $this->assertSame(52, $stats['review_minutes']);
        $this->assertSame(1, $stats['overdue_topics']);
        $this->assertSame(8, $stats['weekly_new_topic_cap']);

        foreach (range(1, 10) as $i) {
            StudyWeek::factory()->create(['user_id' => $this->user->id, 'week_start' => Carbon::parse('2026-10-04')->subWeeks($i - 1)->toDateString()]);
        }
        $history = $this->getJson('/api/study/weekly-plan/history')->assertOk()->json('data');
        $this->assertCount(8, $history);
        $this->assertSame('2026-10-11', $history[0]['week_start']);
    }

    public function test_block_crud_and_validation(): void
    {
        $this->create('green')->assertCreated();
        $week = $this->week();
        $url = '/api/study/weekly-plan/'.IdHasher::encode($week->id).'/blocks';

        $id = $this->postJson($url, ['block_date' => '2026-10-15', 'slot' => 'other', 'lane' => 'work', 'planned_task' => 'Read about isolation levels'])
            ->assertCreated()->json('data.id');
        $this->postJson($url, ['block_date' => '2026-10-20', 'slot' => 'other', 'lane' => 'work'])->assertStatus(422)->assertJsonValidationErrors('block_date', 'errors');
        $this->postJson($url, ['block_date' => '2026-10-15', 'slot' => 'other', 'lane' => 'deep'])->assertStatus(422)->assertJsonValidationErrors('lane', 'errors');

        $morning = StudyBlock::whereDate('block_date', '2026-10-15')->where('slot', 'morning')->first();
        $this->patchJson('/api/study/blocks/'.IdHasher::encode($morning->id), ['planned_task' => 'Write Task 2 essay #4'])
            ->assertOk()->assertJsonPath('data.planned_task', 'Write Task 2 essay #4');
        $this->patchJson('/api/study/blocks/'.IdHasher::encode($morning->id), ['status' => 'skipped'])->assertStatus(422);

        $this->deleteJson('/api/study/blocks/'.$id)->assertOk();
        $this->assertSame(21, StudyBlock::count());
    }

    public function test_red_day_is_excluded_from_score(): void
    {
        $this->create('green')->assertCreated();
        $before = $this->getJson('/api/study/weekly-plan')->json('data.score.counted');
        StudyBlock::whereDate('block_date', '2026-10-12')->get()->each(fn ($b) => $this->patchJson('/api/study/blocks/'.IdHasher::encode($b->id), ['status' => 'red'])->assertOk());

        $this->assertSame($before - 3, $this->getJson('/api/study/weekly-plan')->json('data.score.counted'));
    }

    public function test_access_control(): void
    {
        $other = User::factory()->create();
        $foreignWeek = StudyWeek::factory()->create(['user_id' => $other->id]);
        $foreignBlock = StudyBlock::factory()->create(['study_week_id' => $foreignWeek->id, 'user_id' => $other->id]);

        $this->patchJson('/api/study/blocks/'.IdHasher::encode($foreignBlock->id), ['status' => 'done'])->assertForbidden();
        $this->assertSame('planned', $foreignBlock->fresh()->status);
        $this->patchJson('/api/study/blocks/not-a-hash', ['status' => 'done'])->assertNotFound();

        $this->actingAsDemo();
        $this->create('green')->assertForbidden();
    }

    public function test_service_template_counts_directly(): void
    {
        $service = app(WeeklyPlanService::class);
        $prefs = StudyPreferences::for($this->user);
        $week = StudyWeek::factory()->create(['user_id' => $this->user->id, 'week_start' => '2026-10-11', 'gear' => 'yellow']);
        $service->generateBlocks($week, 'yellow', $prefs);

        $this->assertSame(13, $week->blocks()->count());
        $this->assertSame(1, $week->blocks()->where('slot', 'block_a')->count());
    }
}
