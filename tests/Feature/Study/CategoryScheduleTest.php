<?php

namespace Tests\Feature\Study;

use App\Models\Category;
use App\Models\CategoryReviewSchedule;
use App\Models\StudyTask;
use App\Models\Topic;
use App\Models\User;
use App\Services\IdHasher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CategoryScheduleTest extends StudyApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-11 09:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function url(Category $category): string
    {
        return '/api/study/categories/'.IdHasher::encode($category->id).'/schedule';
    }

    public function test_preset_library(): void
    {
        $presets = collect($this->getJson('/api/study/schedule-presets')->assertOk()->json('data'))->keyBy('key');

        $this->assertSame([1, 7, 30, 90], $presets['standard']['offsets']);
        $this->assertSame([1, 3, 7, 14], $presets['exam_short']['offsets']);
        $this->assertSame(7, $presets['exam_short']['repeat_every_days']);
        $this->assertSame([1, 3, 7, 21, 60], $presets['long_horizon']['offsets']);
        $this->assertSame(90, $presets['long_horizon']['repeat_every_days']);
        $this->assertSame([1, 3, 7], $presets['mistakes']['offsets']);
        $this->assertNull($presets['mistakes']['repeat_every_days']);
    }

    public function test_apply_preset_with_exam_date(): void
    {
        $ielts = Category::factory()->create(['user_id' => $this->user->id, 'name' => 'IELTS']);

        $this->putJson($this->url($ielts), ['preset_key' => 'exam_short', 'repeat_until' => '2026-12-05'])
            ->assertOk()
            ->assertJsonPath('data.offsets', [1, 3, 7, 14])
            ->assertJsonPath('data.repeat_every_days', 7)
            ->assertJsonPath('data.repeat_until', '2026-12-05');
    }

    public function test_system_category_schedule_is_private_to_the_user(): void
    {
        $system = Category::factory()->system()->create();
        $this->putJson($this->url($system), ['preset_key' => 'long_horizon'])->assertOk();

        $other = $this->actingAsUser(User::factory()->create());
        $this->getJson($this->url($system))->assertOk()->assertJsonPath('data.schedule', null)->assertJsonPath('data.fallback', 'system_default');
        $this->assertSame(1, CategoryReviewSchedule::count());
        $this->assertNotSame($other->id, CategoryReviewSchedule::first()->user_id);
    }

    public function test_validation(): void
    {
        $category = Category::factory()->create(['user_id' => $this->user->id]);

        $this->putJson($this->url($category), ['preset_key' => 'custom', 'offsets' => [1, 7, 7, 30]])
            ->assertStatus(422)->assertJsonValidationErrors('offsets', 'errors');
        $this->putJson($this->url($category), ['preset_key' => 'custom', 'offsets' => [1, 7], 'repeat_until' => '2026-12-05'])
            ->assertStatus(422)->assertJsonValidationErrors('repeat_until', 'errors');
        $this->putJson($this->url($category), ['preset_key' => 'custom', 'offsets' => range(1, 11)])
            ->assertStatus(422);
        $this->putJson($this->url($category), ['preset_key' => 'bogus'])->assertStatus(422);
        $this->putJson($this->url($category), ['preset_key' => 'exam_short', 'repeat_until' => '2026-10-01'])->assertStatus(422);
        $this->putJson($this->url($category), ['preset_key' => 'custom', 'offsets' => [2, 9], 'repeat_every_days' => 400])->assertStatus(422);

        $this->assertSame(0, CategoryReviewSchedule::count());
    }

    public function test_read_and_remove(): void
    {
        $category = Category::factory()->create(['user_id' => $this->user->id]);
        $this->putJson($this->url($category), ['preset_key' => 'custom', 'offsets' => [2, 9, 30]])->assertOk();

        $this->getJson($this->url($category))->assertOk()->assertJsonPath('data.schedule.offsets', [2, 9, 30]);
        $this->deleteJson($this->url($category))->assertOk()->assertJsonPath('data.fallback', 'system_default');
        $this->getJson($this->url($category))->assertJsonPath('data.schedule', null);
    }

    public function test_category_list_includes_own_schedule_without_extra_queries(): void
    {
        $maths = Category::factory()->create(['user_id' => $this->user->id, 'name' => 'Maths']);
        Category::factory()->count(3)->create(['user_id' => $this->user->id]);
        $this->putJson($this->url($maths), ['preset_key' => 'long_horizon'])->assertOk();

        DB::enableQueryLog();
        $list = collect($this->getJson('/api/study/categories')->assertOk()->json('data'));
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame('long_horizon', $list->firstWhere('name', 'Maths')['review_schedule']['preset_key']);
        $this->assertNull($list->firstWhere('name', '!=', 'Maths')['review_schedule']);
        $this->assertLessThan(8, $queries);
    }

    public function test_apply_to_existing_topics_updates_snapshot_but_not_dates(): void
    {
        $ielts = Category::factory()->create(['user_id' => $this->user->id]);
        $topic = Topic::factory()->withSchedule([1, 3, 7, 14], 7)->create(['user_id' => $this->user->id, 'category_id' => $ielts->id]);
        $archived = Topic::factory()->withSchedule([1, 3, 7, 14], 7)->create(['user_id' => $this->user->id, 'category_id' => $ielts->id, 'status' => 'archived']);
        $task = StudyTask::factory()->for($topic)->revision(1)->create(['user_id' => $this->user->id, 'scheduled_date' => '2026-10-20']);

        $this->putJson($this->url($ielts), ['preset_key' => 'exam_short', 'repeat_until' => '2026-12-05', 'apply_to_existing_topics' => true])
            ->assertOk()->assertJsonPath('data.applied_to_topics', 1);

        $this->assertSame('2026-12-05', $topic->fresh()->srs_repeat_until->toDateString());
        $this->assertNull($archived->fresh()->srs_repeat_until);
        $this->assertSame('2026-10-20', $task->fresh()->scheduled_date->toDateString());
    }

    public function test_access_control(): void
    {
        $foreign = Category::factory()->create(['user_id' => User::factory()->create()->id]);
        $this->getJson($this->url($foreign))->assertForbidden();
        $this->getJson('/api/study/categories/zzzzzzzz/schedule')->assertNotFound();

        $own = Category::factory()->create(['user_id' => $this->user->id]);
        $this->actingAsDemo();
        $this->putJson($this->url($own), ['preset_key' => 'standard'])->assertForbidden();
    }
}
