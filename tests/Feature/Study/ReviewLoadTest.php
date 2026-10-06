<?php

namespace Tests\Feature\Study;

use App\Models\ReviewLoadSnapshot;
use App\Models\StudyTask;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Carbon;

class ReviewLoadTest extends StudyApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-14 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function dueTopics(int $count, string $date = '2026-10-14', ?User $user = null): void
    {
        $user ??= $this->user;
        for ($i = 0; $i < $count; $i++) {
            $topic = Topic::factory()->create(['user_id' => $user->id]);
            StudyTask::factory()->for($topic)->revision(1)->create(['user_id' => $user->id, 'scheduled_date' => $date]);
        }
    }

    private function timedReviews(int $count, int $seconds): void
    {
        $topic = Topic::factory()->create(['user_id' => $this->user->id]);
        for ($i = 0; $i < $count; $i++) {
            StudyTask::factory()->for($topic)->revision($i + 1)->completed('2026-10-01 10:00:00')->create([
                'user_id' => $this->user->id, 'scheduled_date' => '2026-10-01', 'recall_grade' => 'good', 'review_seconds' => $seconds,
            ]);
        }
    }

    private function snapshot(string $date, int $minutes): void
    {
        ReviewLoadSnapshot::create(['user_id' => $this->user->id, 'snapshot_date' => $date, 'due_topics' => intdiv($minutes, 3), 'estimated_minutes' => $minutes]);
    }

    private function load(): array
    {
        return $this->getJson('/api/study/review-load')->assertOk()->json('data');
    }

    public function test_load_under_budget(): void
    {
        $this->dueTopics(6);

        $data = $this->load();
        $this->assertSame(6, $data['due_topics']);
        $this->assertSame(18, $data['estimated_minutes']);
        $this->assertFalse($data['over_budget']);
    }

    public function test_load_over_budget_and_overdue_counted(): void
    {
        $this->dueTopics(8);
        $this->dueTopics(2, '2026-10-10');

        $data = $this->load();
        $this->assertSame(10, $data['due_topics']);
        $this->assertSame(2, $data['overdue_topics']);
        $this->assertSame(30, $data['estimated_minutes']);
        $this->assertTrue($data['over_budget']);
    }

    public function test_estimate_from_timing_history(): void
    {
        $this->timedReviews(3, 150);
        $this->assertEquals(3, $this->load()['per_review_minutes'], 'fewer than 5 samples uses the preference');

        $this->timedReviews(7, 150);
        $this->assertEquals(2.5, $this->load()['per_review_minutes']);
    }

    public function test_dashboard_includes_review_load(): void
    {
        $this->dueTopics(2);

        $this->getJson('/api/study/dashboard')
            ->assertOk()
            ->assertJsonPath('data.stats.review_load.due_topics', 2)
            ->assertJsonPath('data.stats.review_load.budget_minutes', 25);
    }

    public function test_three_heavy_days_activate_debt(): void
    {
        $this->snapshot('2026-10-12', 34);
        $this->snapshot('2026-10-13', 38);
        $this->snapshot('2026-10-14', 31);
        $this->dueTopics(9); // live 27 min, still ≥ budget

        $data = $this->load();
        $this->assertTrue($data['review_debt_active']);
        $this->assertSame('2026-10-12', $data['review_debt_since']);
    }

    public function test_debt_clears_under_budget(): void
    {
        $this->snapshot('2026-10-12', 34);
        $this->snapshot('2026-10-13', 38);
        $this->snapshot('2026-10-14', 31);
        $this->dueTopics(7); // live 21 min after reviewing

        $this->assertFalse($this->load()['review_debt_active']);
    }

    public function test_a_gap_breaks_the_run(): void
    {
        $this->snapshot('2026-10-12', 34);
        $this->snapshot('2026-10-13', 20);
        $this->snapshot('2026-10-14', 31);
        $this->dueTopics(11);

        $this->assertFalse($this->load()['review_debt_active']);
    }

    public function test_weekly_new_topic_count_ignores_mistakes(): void
    {
        // Week starts Sunday 2026-10-11 by default.
        Topic::factory()->count(2)->create(['user_id' => $this->user->id]);
        Topic::factory()->mistake()->count(3)->create(['user_id' => $this->user->id]);
        Topic::factory()->create(['user_id' => $this->user->id, 'created_at' => '2026-10-10 10:00:00']);

        $data = $this->load();
        $this->assertSame(2, $data['new_topics_this_week']);
        $this->assertSame(8, $data['weekly_new_topic_cap']);
    }

    public function test_never_miss_twice(): void
    {
        $this->dueTopics(1, '2026-10-13');
        $this->assertTrue($this->load()['never_miss_twice']);

        $topic = Topic::factory()->create(['user_id' => $this->user->id]);
        StudyTask::factory()->for($topic)->revision(1)->completed('2026-10-13 20:00:00')->create(['user_id' => $this->user->id, 'scheduled_date' => '2026-10-13']);
        $this->assertFalse($this->load()['never_miss_twice']);
    }

    public function test_snapshot_command_is_idempotent(): void
    {
        $this->dueTopics(2);
        $other = User::factory()->create();
        $this->dueTopics(1, '2026-10-14', $other);

        $this->artisan('study:snapshot-review-load')->assertSuccessful();
        $this->artisan('study:snapshot-review-load')->assertSuccessful();

        $this->assertSame(2, ReviewLoadSnapshot::count());
        $this->assertSame(6, ReviewLoadSnapshot::where('user_id', $this->user->id)->value('estimated_minutes'));
    }
}
