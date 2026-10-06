<?php

namespace Tests\Feature\Study\DataDeletion;

use App\Models\Category;
use App\Models\StudyBlock;
use App\Models\StudyTask;
use App\Models\StudyWeek;
use App\Models\TopicRevisionTemplate;
use App\Models\User;
use App\Services\StudyTracker\DataDeletion\DataCollector;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Feature\Study\StudyApiTestCase;

class DataCollectorTest extends StudyApiTestCase
{
    use SeedsDeletableData;

    private function collect(array $categories, ?User $user = null)
    {
        return app(DataCollector::class)->collect($user ?? $this->user, $categories);
    }

    public function test_topics_collects_tasks_and_logs_and_references_mistakes_and_blocks(): void
    {
        $topic = $this->topicFor($this->user);
        $this->tasksFor($topic, 3);
        $this->practiceLogFor($topic);
        $mistake = $this->mistakeFor($this->user, ['parent_topic_id' => $topic->id]);
        $week = StudyWeek::factory()->create(['user_id' => $this->user->id]);
        $block = StudyBlock::factory()->create(['study_week_id' => $week->id, 'topic_id' => $topic->id]);

        $plan = $this->collect(['topics']);

        $this->assertSame([$topic->id], $plan->ids('topics'));
        $this->assertCount(3, $plan->ids('study_tasks'));
        $this->assertCount(1, $plan->ids('practice_logs'));
        $this->assertSame([], $plan->ids('study_blocks'));
        $this->assertEquals(['topics' => 1, 'study_tasks' => 3, 'practice_logs' => 1], $plan->counts());

        $references = collect($plan->references())->map(fn ($r) => "{$r['table']}#{$r['id']}.{$r['column']}={$r['value']}")->all();
        $this->assertEqualsCanonicalizing([
            "topics#{$mistake->id}.parent_topic_id={$topic->id}",
            "study_blocks#{$block->id}.topic_id={$topic->id}",
        ], $references);
    }

    public function test_mistakes_collects_only_mistake_kind(): void
    {
        $this->topicFor($this->user);
        $mistake = $this->mistakeFor($this->user);
        $this->tasksFor($mistake, 2);

        $plan = $this->collect(['mistakes']);

        $this->assertSame([$mistake->id], $plan->ids('topics'));
        $this->assertEquals(['mistakes' => 1, 'study_tasks' => 2], $plan->counts());
    }

    public function test_categories_include_soft_deleted_rows_schedules_and_reference_topics_and_blocks(): void
    {
        $active = $this->categoryFor($this->user);
        $trashed = $this->categoryFor($this->user);
        $trashed->delete();
        $schedule = $this->scheduleFor($this->user, $active);
        $topic = $this->topicFor($this->user, ['category_id' => $active->id]);
        $week = StudyWeek::factory()->create(['user_id' => $this->user->id]);
        $block = StudyBlock::factory()->create(['study_week_id' => $week->id, 'category_id' => $active->id]);

        $plan = $this->collect(['categories']);

        $this->assertEqualsCanonicalizing([$active->id, $trashed->id], $plan->ids('categories'));
        $this->assertNotNull($plan->tables()['categories'][$trashed->id]['deleted_at']);
        $this->assertSame([$schedule->id], $plan->ids('category_review_schedules'));
        $this->assertSame([], $plan->ids('topics'));

        $references = collect($plan->references())->map(fn ($r) => "{$r['table']}#{$r['id']}.{$r['column']}")->all();
        $this->assertEqualsCanonicalizing([
            "topics#{$topic->id}.category_id",
            "study_blocks#{$block->id}.category_id",
        ], $references);
    }

    public function test_system_rows_and_other_users_rows_are_never_collected(): void
    {
        $system = Category::factory()->system()->create();
        $this->scheduleFor($this->user, $system);
        $other = User::factory()->create();
        $this->topicFor($other);
        $this->categoryFor($other);
        $this->templateFor($other);
        $mine = $this->templateFor($this->user);

        $categoriesPlan = $this->collect(['categories', 'topics']);
        $this->assertSame([], $categoriesPlan->ids('categories'));
        $this->assertSame([], $categoriesPlan->ids('topics'));

        $settingsPlan = $this->collect(['study_settings']);
        $this->assertSame([$mine->id], $settingsPlan->ids('topic_revision_templates'));
        $this->assertGreaterThan(0, TopicRevisionTemplate::whereNull('user_id')->count());
        // The user's own schedule on a system category belongs to their settings.
        $this->assertCount(1, $settingsPlan->ids('category_review_schedules'));
    }

    public function test_topics_and_practice_logs_together_count_each_log_once(): void
    {
        $topic = $this->topicFor($this->user);
        $this->practiceLogFor($topic);
        $this->practiceLogFor($topic);
        $otherTopic = $this->topicFor($this->user, ['kind' => 'mistake']);
        $this->practiceLogFor($otherTopic);

        $plan = $this->collect(['topics', 'practice_logs']);

        $this->assertCount(3, $plan->ids('practice_logs'));
        $this->assertSame(3, $plan->counts()['practice_logs']);
    }

    public function test_task_references_are_recorded_when_logs_stay(): void
    {
        $topic = $this->topicFor($this->user);
        $this->tasksFor($topic);
        $task = StudyTask::where('topic_id', $topic->id)->first();
        $mistake = $this->mistakeFor($this->user);
        $log = $this->practiceLogFor($mistake, ['task_id' => $task->id]);

        $plan = $this->collect(['topics']);

        $this->assertContains(
            ['table' => 'practice_logs', 'id' => $log->id, 'column' => 'task_id', 'value' => $task->id],
            $plan->references()
        );
    }

    public function test_row_of_another_user_reached_through_an_edge_throws(): void
    {
        $topic = $this->topicFor($this->user);
        $other = User::factory()->create();
        // Corrupt data: another user's task on this user's topic.
        DB::table('study_tasks')->insert([
            'user_id' => $other->id,
            'topic_id' => $topic->id,
            'task_type' => 'revision',
            'title' => 'Foreign',
            'scheduled_date' => today()->toDateString(),
            'status' => 'pending',
        ]);

        $this->expectException(RuntimeException::class);

        $this->collect(['topics']);
    }

    public function test_study_settings_reports_preferences_only_when_saved(): void
    {
        $this->assertArrayNotHasKey('study_preferences', $this->collect(['study_settings'])->counts());

        $this->user->forceFill(['study_preferences' => ['daily_review_budget_minutes' => 40]])->save();
        $plan = $this->collect(['study_settings']);

        $this->assertTrue($plan->clearsPreferences());
        $this->assertSame(1, $plan->counts()['study_preferences']);
        $this->assertJson($plan->preferences());
    }
}
