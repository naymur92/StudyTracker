<?php

namespace Tests\Feature\Study;

use App\Models\Category;
use App\Models\CategoryReviewSchedule;
use App\Models\StudyTask;
use App\Models\Topic;
use App\Models\TopicRevisionTemplate;
use App\Services\StudyTracker\ResolveScheduleService;
use App\Services\StudyTracker\TopicScheduleResolver;

class ScheduleResolutionTest extends StudyApiTestCase
{
    private function userTemplates(array $offsets): void
    {
        foreach ($offsets as $i => $offset) {
            TopicRevisionTemplate::create([
                'user_id' => $this->user->id,
                'name' => 'Revision '.($i + 1),
                'day_offset' => $offset,
                'sequence_no' => $i + 1,
                'is_active' => true,
            ]);
        }
    }

    public function test_category_schedule_wins_over_templates(): void
    {
        $this->userTemplates([2, 10]);
        $category = Category::factory()->create(['user_id' => $this->user->id]);
        CategoryReviewSchedule::create([
            'user_id' => $this->user->id,
            'category_id' => $category->id,
            'preset_key' => 'long_horizon',
            'offsets' => [1, 3, 7, 21, 60],
            'repeat_every_days' => 90,
        ]);

        $schedule = app(ResolveScheduleService::class)->forNewTopic($this->user->id, $category->id);

        $this->assertSame([1, 3, 7, 21, 60], $schedule->offsets);
        $this->assertSame(90, $schedule->repeatEveryDays);
        $this->assertSame('category', $schedule->source);
    }

    public function test_fallback_to_user_templates(): void
    {
        $this->userTemplates([2, 10]);
        $category = Category::factory()->create(['user_id' => $this->user->id]);

        $schedule = app(ResolveScheduleService::class)->forNewTopic($this->user->id, $category->id);

        $this->assertSame([2, 10], $schedule->offsets);
        $this->assertNull($schedule->repeatEveryDays);
        $this->assertSame('user_default', $schedule->source);
    }

    public function test_system_templates_and_mistakes_preset(): void
    {
        $resolver = app(ResolveScheduleService::class);

        $this->assertSame([1, 7, 30, 90], $resolver->forNewTopic($this->user->id, null)->offsets);
        $this->assertSame([1, 3, 7], $resolver->forNewTopic($this->user->id, null, Topic::KIND_MISTAKE)->offsets);
    }

    public function test_legacy_topic_adopts_schedule_and_step(): void
    {
        $topic = Topic::factory()->create(['user_id' => $this->user->id]);
        StudyTask::factory()->for($topic)->revision(1)->completed()->create(['user_id' => $this->user->id]);
        StudyTask::factory()->for($topic)->revision(2)->completed()->create(['user_id' => $this->user->id]);
        StudyTask::factory()->for($topic)->revision(3)->create(['user_id' => $this->user->id]);

        $schedule = app(TopicScheduleResolver::class)->ensure($topic);
        $topic->refresh();

        $this->assertSame([1, 7, 30, 90], $schedule->offsets);
        $this->assertSame([1, 7, 30, 90], $topic->srs_offsets);
        $this->assertSame(2, $topic->srs_step);
        $this->assertSame('system_default', $topic->srs_schedule_source);
    }
}
