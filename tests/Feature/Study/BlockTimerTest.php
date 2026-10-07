<?php

namespace Tests\Feature\Study;

use App\Models\StudyBlock;
use App\Models\StudyBlockSession;
use App\Models\StudyWeek;
use App\Models\Topic;
use App\Models\User;
use App\Services\IdHasher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BlockTimerTest extends StudyApiTestCase
{
    private StudyWeek $week;

    private Topic $topic;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-13 10:00:00'); // Tuesday
        $this->week = StudyWeek::factory()->create(['user_id' => $this->user->id, 'week_start' => '2026-10-11']);
        $this->topic = Topic::factory()->create(['user_id' => $this->user->id, 'title' => 'Binary trees']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function block(array $attrs = []): StudyBlock
    {
        return StudyBlock::factory()->create(array_merge([
            'study_week_id' => $this->week->id,
            'user_id' => $this->user->id,
            'block_date' => '2026-10-13',
            'slot' => 'morning',
            'lane' => 'major',
            'planned_minutes' => 90,
            'topic_id' => $this->topic->id,
        ], $attrs));
    }

    private function url(StudyBlock $block, string $action = ''): string
    {
        return '/api/study/blocks/'.IdHasher::encode($block->id).'/timer'.($action ? "/{$action}" : '');
    }

    private function start(StudyBlock $block, array $data = [])
    {
        return $this->postJson($this->url($block, 'start'), $data);
    }

    private function at(string $time): void
    {
        Carbon::setTestNow($time);
    }

    // ── 2.2 start, topic/minutes, one active timer, lookup, access ──

    public function test_start_todays_block(): void
    {
        $block = $this->block();

        $this->start($block)->assertOk()
            ->assertJsonPath('data.timer.state', 'running')
            ->assertJsonPath('data.timer.block_id', IdHasher::encode($block->id))
            ->assertJsonPath('data.timer.left_seconds', 5400)
            ->assertJsonPath('data.timer.used_seconds', 0)
            ->assertJsonPath('data.timer.phase', 'work')
            ->assertJsonPath('data.timer.block.slot', 'morning')
            ->assertJsonPath('data.block.status', 'planned')
            ->assertJsonPath('data.server_time', now()->toIso8601String());

        $this->assertSame('planned', $block->fresh()->status);
        $this->assertSame(1, StudyBlockSession::where('active_user_id', $this->user->id)->count());
    }

    public function test_only_todays_planned_or_partial_blocks_start(): void
    {
        foreach (['2026-10-14', '2026-10-12'] as $date) {
            $this->start($this->block(['block_date' => $date]))
                ->assertStatus(422)->assertJsonValidationErrors('block_date', 'errors');
        }
        foreach (['done', 'missed', 'red'] as $status) {
            $this->start($this->block(['status' => $status]))
                ->assertStatus(422)->assertJsonValidationErrors('status', 'errors');
        }
        $this->assertSame(0, StudyBlockSession::count());

        $this->start($this->block(['status' => 'partial']))->assertOk();
    }

    public function test_demo_user_cannot_start(): void
    {
        $this->actingAsDemo();
        $demoWeek = StudyWeek::factory()->create(['user_id' => auth()->id(), 'week_start' => '2026-10-11']);
        $block = StudyBlock::factory()->create(['study_week_id' => $demoWeek->id, 'block_date' => '2026-10-13', 'lane' => 'review']);

        $this->start($block)->assertForbidden();
        $this->assertSame(0, StudyBlockSession::count());
    }

    public function test_topic_is_required_except_for_review_blocks(): void
    {
        $major = $this->block(['topic_id' => null]);
        $this->start($major)->assertStatus(422)->assertJsonValidationErrors('topic_id', 'errors');
        $this->assertSame(0, StudyBlockSession::count());

        $this->start($major, ['topic_id' => IdHasher::encode($this->topic->id)])->assertOk()
            ->assertJsonPath('data.timer.block.topic.title', 'Binary trees');
        $this->assertSame($this->topic->id, $major->fresh()->topic_id);
        $this->deleteJson($this->url($major))->assertOk();

        $review = $this->block(['slot' => 'review', 'lane' => 'review', 'topic_id' => null, 'planned_minutes' => 20]);
        $this->start($review)->assertOk()->assertJsonPath('data.timer.block.topic', null);
    }

    public function test_foreign_topic_is_rejected(): void
    {
        $foreign = Topic::factory()->create(['user_id' => User::factory()->create()->id]);
        $block = $this->block(['topic_id' => null]);

        $this->start($block, ['topic_id' => IdHasher::encode($foreign->id)])
            ->assertStatus(422)->assertJsonValidationErrors('topic_id', 'errors');
        $this->assertNull($block->fresh()->topic_id);
    }

    public function test_minutes_are_required(): void
    {
        $block = $this->block(['planned_minutes' => null]);

        $this->start($block)->assertStatus(422)->assertJsonValidationErrors('planned_minutes', 'errors');
        $this->start($block, ['planned_minutes' => 3])->assertStatus(422)->assertJsonValidationErrors('planned_minutes', 'errors');

        $this->start($block, ['planned_minutes' => 45])->assertOk()->assertJsonPath('data.timer.left_seconds', 2700);
        $this->assertSame(45, $block->fresh()->planned_minutes);
    }

    public function test_failed_start_saves_nothing(): void
    {
        // Topic given, minutes missing: the topic must not be saved either.
        $block = $this->block(['topic_id' => null, 'planned_minutes' => null]);

        $this->start($block, ['topic_id' => IdHasher::encode($this->topic->id)])->assertStatus(422);
        $this->assertNull($block->fresh()->topic_id);
    }

    public function test_one_active_timer_per_user(): void
    {
        $morning = $this->block();
        $review = $this->block(['slot' => 'review', 'lane' => 'review', 'planned_minutes' => 20]);

        $this->start($morning)->assertOk();
        $this->start($review)->assertStatus(409)
            ->assertJsonPath('flag', false)
            ->assertJsonPath('data.timer.block_id', IdHasher::encode($morning->id));
        $this->assertStringContainsString('Morning deep block', $this->start($review)->json('msg'));

        $this->assertSame(1, StudyBlockSession::count());
        $this->assertNull($review->fresh()->activeSession);

        // A paused timer is still active.
        $this->postJson($this->url($morning, 'pause'))->assertOk();
        $this->start($review)->assertStatus(409);
    }

    public function test_lookup_without_a_timer(): void
    {
        $this->getJson('/api/study/timer')->assertOk()
            ->assertJsonPath('data.timer', null)
            ->assertJsonPath('data.recent', null)
            ->assertJsonPath('data.server_time', now()->toIso8601String());
    }

    public function test_lookup_with_a_running_timer(): void
    {
        $block = $this->block();
        $this->start($block)->assertOk();
        $this->at('2026-10-13 10:30:00');

        $this->getJson('/api/study/timer')->assertOk()
            ->assertJsonPath('data.timer.state', 'running')
            ->assertJsonPath('data.timer.used_seconds', 1800)
            ->assertJsonPath('data.timer.left_seconds', 3600)
            ->assertJsonPath('data.timer.block.planned_task', $block->planned_task);
    }

    public function test_access_control(): void
    {
        $other = User::factory()->create();
        $foreignWeek = StudyWeek::factory()->create(['user_id' => $other->id, 'week_start' => '2026-10-11']);
        $foreign = StudyBlock::factory()->create(['study_week_id' => $foreignWeek->id, 'block_date' => '2026-10-13', 'lane' => 'review']);

        $this->start($foreign)->assertForbidden();
        $this->postJson('/api/study/blocks/not-a-hash/timer/start')->assertNotFound();
        $this->assertSame(0, StudyBlockSession::count());

        $this->getJson('/api/study/timer')->assertOk()->assertJsonPath('data.timer', null);
    }

    // ── 2.3 pause and resume ──

    public function test_pause_keeps_the_time_left(): void
    {
        $block = $this->block();
        $this->start($block)->assertOk();

        $this->at('2026-10-13 10:20:00');
        $this->postJson($this->url($block, 'pause'))->assertOk()
            ->assertJsonPath('data.timer.state', 'paused')
            ->assertJsonPath('data.timer.left_seconds', 4200);

        $this->at('2026-10-13 10:28:00');
        $this->getJson('/api/study/timer')->assertJsonPath('data.timer.left_seconds', 4200);
        $this->postJson($this->url($block, 'resume'))->assertOk()
            ->assertJsonPath('data.timer.state', 'running')
            ->assertJsonPath('data.timer.left_seconds', 4200);
    }

    public function test_time_with_a_pause(): void
    {
        $block = $this->block();
        $this->start($block)->assertOk();
        $this->at('2026-10-13 10:20:00');
        $this->postJson($this->url($block, 'pause'))->assertOk();
        $this->at('2026-10-13 10:30:00');
        $this->postJson($this->url($block, 'resume'))->assertOk();
        $this->at('2026-10-13 10:45:00');

        $this->getJson('/api/study/timer')
            ->assertJsonPath('data.timer.used_seconds', 35 * 60)
            ->assertJsonPath('data.timer.left_seconds', 55 * 60);
    }

    public function test_resume_only_on_the_blocks_day(): void
    {
        $block = $this->block();
        $this->start($block)->assertOk();
        $this->at('2026-10-13 10:20:00');
        $this->postJson($this->url($block, 'pause'))->assertOk();

        $this->at('2026-10-14 08:00:00');
        $this->postJson($this->url($block, 'resume'))->assertStatus(422)->assertJsonValidationErrors('block_date', 'errors');
        $this->getJson('/api/study/timer')->assertJsonPath('data.timer.state', 'paused');

        // It can still be stopped (as partial) on a later day.
        $this->postJson($this->url($block, 'stop'))->assertOk()->assertJsonPath('data.outcome', 'partial');
        $this->assertSame('partial', $block->fresh()->status);
    }

    public function test_pause_and_resume_need_the_right_state(): void
    {
        $block = $this->block();
        $this->start($block)->assertOk();
        $this->postJson($this->url($block, 'resume'))->assertStatus(422)->assertJsonValidationErrors('timer', 'errors');

        $this->postJson($this->url($block, 'pause'))->assertOk();
        $this->postJson($this->url($block, 'pause'))->assertStatus(422)->assertJsonValidationErrors('timer', 'errors');
    }

    public function test_run_past_midnight_keeps_counting(): void
    {
        $block = $this->block(['planned_minutes' => 90]);
        $this->at('2026-10-13 23:30:00');
        $this->start($block)->assertOk();

        $this->at('2026-10-14 00:15:00');
        $this->getJson('/api/study/timer')->assertJsonPath('data.timer.used_seconds', 45 * 60);
    }

    // ── 2.4 stop and discard ──

    public function test_stop_after_40_minutes_marks_partial(): void
    {
        $block = $this->block();
        $this->start($block)->assertOk();
        $this->at('2026-10-13 10:40:00');

        $this->postJson($this->url($block, 'stop'))->assertOk()
            ->assertJsonPath('data.outcome', 'partial')
            ->assertJsonPath('data.timer', null)
            ->assertJsonPath('data.block.status', 'partial')
            ->assertJsonPath('data.block.actual_minutes', 40)
            ->assertJsonPath('data.recent.end_reason', 'stopped')
            ->assertJsonPath('data.recent.minutes', 40);
    }

    public function test_stop_at_the_last_seconds_completes_the_block(): void
    {
        $block = $this->block();
        $this->start($block)->assertOk();
        $this->at('2026-10-13 11:29:50'); // 10 s left

        $this->postJson($this->url($block, 'stop'))->assertOk()
            ->assertJsonPath('data.outcome', 'done')
            ->assertJsonPath('data.block.status', 'done')
            ->assertJsonPath('data.block.actual_minutes', 90)
            ->assertJsonPath('data.recent.end_reason', 'finished');
    }

    public function test_short_run_is_discarded(): void
    {
        $block = $this->block();
        $this->start($block)->assertOk();
        $this->at('2026-10-13 10:00:20');

        $this->postJson($this->url($block, 'stop'))->assertOk()
            ->assertJsonPath('data.outcome', 'discarded')
            ->assertJsonPath('data.block.status', 'planned')
            ->assertJsonPath('data.block.actual_minutes', 0);
        $this->assertSame(0, StudyBlockSession::count());
        $this->getJson('/api/study/timer')->assertJsonPath('data.timer', null);
    }

    public function test_discard_a_second_run_keeps_the_first(): void
    {
        $block = $this->block();
        $this->start($block)->assertOk();
        $this->at('2026-10-13 10:40:00');
        $this->postJson($this->url($block, 'stop'))->assertOk();

        $this->at('2026-10-13 14:00:00');
        $this->start($block)->assertOk()->assertJsonPath('data.timer.left_seconds', 50 * 60);
        $this->at('2026-10-13 14:15:00');
        $this->deleteJson($this->url($block))->assertOk()
            ->assertJsonPath('data.timer', null)
            ->assertJsonPath('data.block.status', 'partial')
            ->assertJsonPath('data.block.actual_minutes', 40);
    }

    public function test_continue_a_partial_block_until_done(): void
    {
        $block = $this->block();
        $this->start($block)->assertOk();
        $this->at('2026-10-13 10:40:00');
        $this->postJson($this->url($block, 'stop'))->assertOk();

        $this->at('2026-10-13 14:00:00');
        $this->start($block)->assertOk();
        $this->at('2026-10-13 14:50:00');

        $this->getJson('/api/study/weekly-plan')->assertOk();
        $fresh = $block->fresh();
        $this->assertSame('done', $fresh->status);
        $this->assertSame(90 * 60, $fresh->endedSeconds());

        // A done block cannot start again.
        $this->start($block)->assertStatus(422)->assertJsonValidationErrors('status', 'errors');
    }

    public function test_nothing_to_stop_or_discard(): void
    {
        $block = $this->block();
        $this->postJson($this->url($block, 'stop'))->assertStatus(422)->assertJsonValidationErrors('timer', 'errors');
        $this->deleteJson($this->url($block))->assertStatus(422);
        $this->postJson($this->url($block, 'pause'))->assertStatus(422);
    }

    public function test_cannot_stop_another_users_timer(): void
    {
        $other = User::factory()->create();
        $foreignWeek = StudyWeek::factory()->create(['user_id' => $other->id, 'week_start' => '2026-10-11']);
        $foreign = StudyBlock::factory()->create(['study_week_id' => $foreignWeek->id, 'block_date' => '2026-10-13']);
        StudyBlockSession::factory()->running(now())->create(['study_block_id' => $foreign->id, 'user_id' => $other->id]);

        $this->postJson($this->url($foreign, 'stop'))->assertForbidden();
        $this->deleteJson($this->url($foreign))->assertForbidden();
        $this->assertTrue($foreign->fresh()->activeSession->isRunning());
    }

    // ── 2.5 settle ──

    public function test_timer_runs_out_and_marks_done(): void
    {
        $block = $this->block();
        $this->start($block)->assertOk();
        $this->at('2026-10-13 11:30:00');

        $this->getJson('/api/study/timer')->assertOk()
            ->assertJsonPath('data.timer', null)
            ->assertJsonPath('data.recent.end_reason', 'finished')
            ->assertJsonPath('data.recent.minutes', 90)
            ->assertJsonPath('data.recent.block.status', 'done')
            ->assertJsonPath('data.recent.block.actual_minutes', 90);
    }

    public function test_page_closed_during_the_block(): void
    {
        $review = $this->block(['slot' => 'review', 'lane' => 'review', 'planned_minutes' => 20, 'topic_id' => null]);
        $this->at('2026-10-13 09:00:00');
        $this->start($review)->assertOk();
        $this->at('2026-10-13 11:00:00');

        $blocks = collect($this->getJson('/api/study/weekly-plan')->assertOk()->json('data.blocks'));
        $row = $blocks->firstWhere('id', IdHasher::encode($review->id));
        $this->assertSame('done', $row['status']);
        $this->assertSame(20, $row['actual_minutes']);
        $this->assertNull($row['timer']);

        $session = StudyBlockSession::firstOrFail();
        $this->assertSame('2026-10-13 09:20:00', $session->ended_at->toDateTimeString());
        $this->assertSame(1200, $session->used_seconds);
    }

    public function test_history_and_timer_actions_settle_first(): void
    {
        $block = $this->block(['planned_minutes' => 30]);
        $this->start($block)->assertOk();
        $this->at('2026-10-13 10:31:00');

        $this->getJson('/api/study/weekly-plan/history')->assertOk();
        $this->assertSame('done', $block->fresh()->status);

        // A timer action on a run that already ran out finds nothing active.
        $other = $this->block(['planned_minutes' => 30, 'slot' => 'minor']);
        $this->start($other)->assertOk();
        $this->at('2026-10-13 11:05:00');
        $this->postJson($this->url($other, 'stop'))->assertStatus(422);
        $this->assertSame('done', $other->fresh()->status);
    }

    public function test_recent_shows_a_run_that_finished_while_away(): void
    {
        $block = $this->block(['planned_minutes' => 30]);
        $this->start($block)->assertOk();
        $this->at('2026-10-13 11:30:00'); // ran out at 10:30, an hour ago

        $this->getJson('/api/study/timer')
            ->assertJsonPath('data.timer', null)
            ->assertJsonPath('data.recent.end_reason', 'finished')
            ->assertJsonPath('data.recent.block.status', 'done');

        // Older than 12 hours, it is no longer recent.
        $this->at('2026-10-14 00:00:00');
        $this->getJson('/api/study/timer')->assertJsonPath('data.recent', null);
    }

    public function test_paused_timer_never_runs_out(): void
    {
        $block = $this->block(['planned_minutes' => 30]);
        $this->start($block)->assertOk();
        $this->at('2026-10-13 10:10:00');
        $this->postJson($this->url($block, 'pause'))->assertOk();

        $this->at('2026-10-13 18:00:00');
        $this->getJson('/api/study/timer')
            ->assertJsonPath('data.timer.state', 'paused')
            ->assertJsonPath('data.timer.left_seconds', 1200);
        $this->assertSame('planned', $block->fresh()->status);
    }

    // ── 2.6 block payload ──

    public function test_weekly_plan_shows_the_timer_on_the_running_block_only(): void
    {
        $morning = $this->block();
        $this->block(['slot' => 'review', 'lane' => 'review', 'planned_minutes' => 20]);
        $this->block(['block_date' => '2026-10-14']);
        $this->start($morning)->assertOk();

        $blocks = collect($this->getJson('/api/study/weekly-plan')->assertOk()->json('data.blocks'));
        $this->assertSame('running', $blocks->firstWhere('id', IdHasher::encode($morning->id))['timer']['state']);
        $this->assertSame(1, $blocks->whereNotNull('timer')->count());
        $this->assertSame([0, 0, 0], $blocks->pluck('actual_minutes')->all());
    }

    public function test_timer_object_at_52_minutes_of_a_50_10_block(): void
    {
        $block = $this->block(['break_every_minutes' => 50, 'break_minutes' => 10]);
        $this->start($block)->assertOk();
        $this->at('2026-10-13 10:52:00');

        $this->getJson('/api/study/timer')
            ->assertJsonPath('data.timer.state', 'running')
            ->assertJsonPath('data.timer.used_seconds', 3120)
            ->assertJsonPath('data.timer.left_seconds', 2280)
            ->assertJsonPath('data.timer.phase', 'break')
            ->assertJsonPath('data.timer.phase_left_seconds', 480)
            ->assertJsonPath('data.timer.next_break_at_seconds', null)
            ->assertJsonPath('data.timer.break_every_minutes', 50)
            ->assertJsonPath('data.timer.break_minutes', 10)
            ->assertJsonPath('data.timer.session_id', IdHasher::encode(StudyBlockSession::firstOrFail()->id));
    }

    public function test_plan_fetch_does_not_query_runs_per_block(): void
    {
        foreach (range(0, 6) as $d) {
            $b = $this->block(['block_date' => Carbon::parse('2026-10-11')->addDays($d)->toDateString()]);
            StudyBlockSession::factory()->create(['study_block_id' => $b->id, 'user_id' => $this->user->id]);
        }
        $this->start($this->block(['slot' => 'minor']))->assertOk();

        $queries = [];
        DB::listen(function ($q) use (&$queries) {
            if (str_contains($q->sql, 'study_block_sessions')) {
                $queries[] = $q->sql;
            }
        });
        $blocks = $this->getJson('/api/study/weekly-plan')->assertOk()->json('data.blocks');

        $this->assertCount(8, $blocks);
        $this->assertSame([30, 30, 30, 30, 30, 30, 30], collect($blocks)->where('slot', 'morning')->pluck('actual_minutes')->values()->all());
        // Runs are read with one aggregate and one eager load for all blocks;
        // the only single-block read is the settle check of the running block.
        $perBlock = array_filter($queries, fn ($sql) => str_contains($sql, '`study_block_sessions`.`study_block_id` = ?'));
        $this->assertLessThanOrEqual(1, count($perBlock), implode("\n", $queries));
        $this->assertLessThanOrEqual(5, count($queries), implode("\n", $queries));
    }

    // ── Recorded time is evidence ──

    /** Run the block's timer for $minutes and stop it (partial unless the time is up). */
    private function record(StudyBlock $block, int $minutes): void
    {
        $this->start($block)->assertOk();
        $this->at(Carbon::now()->addMinutes($minutes)->toDateTimeString());
        $this->postJson($this->url($block, 'stop'))->assertOk();
    }

    private function blockUrl(StudyBlock $block): string
    {
        return '/api/study/blocks/'.IdHasher::encode($block->id);
    }

    public function test_recorded_block_status_stays_done_or_partial(): void
    {
        $block = $this->block();
        $this->record($block, 40);

        foreach (['planned', 'missed', 'red'] as $status) {
            $this->patchJson($this->blockUrl($block), ['status' => $status])
                ->assertStatus(422)->assertJsonValidationErrors('status', 'errors');
        }
        $this->assertSame('partial', $block->fresh()->status);

        $this->patchJson($this->blockUrl($block), ['status' => 'done'])->assertOk()->assertJsonPath('data.status', 'done');
        $this->patchJson($this->blockUrl($block), ['status' => 'partial'])->assertOk()->assertJsonPath('data.status', 'partial');

        // Other edits are unaffected.
        $this->patchJson($this->blockUrl($block), ['planned_task' => 'Essay #4', 'note' => 'tired', 'slot' => 'deep'])->assertOk();
    }

    public function test_recorded_block_keeps_its_day_and_enough_minutes(): void
    {
        $block = $this->block();
        $this->record($block, 40);

        $this->patchJson($this->blockUrl($block), ['block_date' => '2026-10-12'])
            ->assertStatus(422)->assertJsonValidationErrors('block_date', 'errors');
        $this->patchJson($this->blockUrl($block), ['block_date' => '2026-10-13'])->assertOk();

        $this->patchJson($this->blockUrl($block), ['planned_minutes' => 30])
            ->assertStatus(422)->assertJsonValidationErrors('planned_minutes', 'errors');
        $this->patchJson($this->blockUrl($block), ['planned_minutes' => null])
            ->assertStatus(422)->assertJsonValidationErrors('planned_minutes', 'errors');
        $this->patchJson($this->blockUrl($block), ['planned_minutes' => 40])->assertOk();
        $this->assertSame(40, $block->fresh()->planned_minutes);
    }

    public function test_block_without_recorded_time_is_unrestricted(): void
    {
        $block = $this->block();
        $this->patchJson($this->blockUrl($block), ['status' => 'missed'])->assertOk();
        $this->patchJson($this->blockUrl($block), ['status' => 'planned', 'block_date' => '2026-10-12', 'planned_minutes' => 20])->assertOk();
    }

    public function test_clear_recorded_time(): void
    {
        $block = $this->block();
        $this->record($block, 40);
        $this->at('2026-10-13 14:00:00');
        $this->start($block)->assertOk();
        $this->at('2026-10-13 14:50:00');
        $this->getJson('/api/study/timer')->assertJsonPath('data.recent.block.status', 'done'); // ran out: 90 recorded
        $this->assertSame('done', $block->fresh()->status);

        $this->deleteJson($this->url($block, 'runs'))->assertOk()
            ->assertJsonPath('data.block.status', 'planned')
            ->assertJsonPath('data.block.actual_minutes', 0)
            ->assertJsonPath('data.block.has_recorded_time', false)
            ->assertJsonPath('data.recent', null);
        $this->assertSame(0, StudyBlockSession::count());

        // Now any status is allowed again, and nothing is left to clear.
        $this->patchJson($this->blockUrl($block), ['status' => 'missed'])->assertOk();
        $this->deleteJson($this->url($block, 'runs'))->assertStatus(422)->assertJsonValidationErrors('timer', 'errors');
    }

    public function test_clear_is_refused_while_the_timer_is_active(): void
    {
        $block = $this->block();
        $this->record($block, 40);
        $this->at('2026-10-13 14:00:00');
        $this->start($block)->assertOk();

        $this->deleteJson($this->url($block, 'runs'))->assertStatus(422)->assertJsonValidationErrors('timer', 'errors');
        $this->assertSame(2, StudyBlockSession::count());
        $this->assertSame('partial', $block->fresh()->status);
    }

    public function test_clear_access_control(): void
    {
        $other = User::factory()->create();
        $foreignWeek = StudyWeek::factory()->create(['user_id' => $other->id, 'week_start' => '2026-10-11']);
        $foreign = StudyBlock::factory()->create(['study_week_id' => $foreignWeek->id, 'block_date' => '2026-10-13', 'status' => 'done']);
        StudyBlockSession::factory()->create(['study_block_id' => $foreign->id, 'user_id' => $other->id]);

        $this->deleteJson($this->url($foreign, 'runs'))->assertForbidden();
        $this->assertSame(1, StudyBlockSession::count());

        $this->actingAsDemo();
        $demoWeek = StudyWeek::factory()->create(['user_id' => auth()->id(), 'week_start' => '2026-10-11']);
        $demo = StudyBlock::factory()->create(['study_week_id' => $demoWeek->id, 'block_date' => '2026-10-13']);
        $this->deleteJson($this->url($demo, 'runs'))->assertForbidden();
    }

    public function test_regenerate_keeps_blocks_with_recorded_time(): void
    {
        // A planned block that still has runs (recorded before these rules).
        $block = $this->block(['status' => 'planned']);
        StudyBlockSession::factory()->create(['study_block_id' => $block->id, 'user_id' => $this->user->id]);

        $this->postJson('/api/study/weekly-plan/'.IdHasher::encode($this->week->id).'/regenerate', ['gear' => 'red'])->assertOk();

        $this->assertNotNull($block->fresh());
    }
}
