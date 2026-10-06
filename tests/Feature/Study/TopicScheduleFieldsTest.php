<?php

namespace Tests\Feature\Study;

use App\Models\StudyTask;
use App\Models\Topic;
use App\Services\IdHasher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TopicScheduleFieldsTest extends StudyApiTestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_new_topic_starts_at_step_zero(): void
    {
        Carbon::setTestNow('2026-10-07 08:00:00');

        $this->postJson('/api/study/topics', ['title' => 'Kafka partitions', 'first_study_date' => '2026-10-07'])
            ->assertCreated()
            ->assertJsonPath('data.srs_step', 0)
            ->assertJsonPath('data.srs_lapses', 0)
            ->assertJsonPath('data.srs_steps_total', 4)
            ->assertJsonPath('data.last_reviewed_on', null)
            ->assertJsonPath('data.next_review_date', '2026-10-08')
            ->assertJsonPath('data.review_schedule.offsets', [1, 7, 30, 90]);
    }

    public function test_show_returns_next_review_date_and_graduated_topic_has_none(): void
    {
        $topic = Topic::factory()->withSchedule([1, 7, 30, 90])->create(['user_id' => $this->user->id, 'srs_step' => 4]);
        StudyTask::factory()->for($topic)->revision(4)->completed()->create(['user_id' => $this->user->id]);

        $this->getJson('/api/study/topics/'.IdHasher::encode($topic->id))
            ->assertOk()
            ->assertJsonPath('data.topic.srs_step', 4)
            ->assertJsonPath('data.topic.next_review_date', null);
    }

    public function test_topic_index_query_count_does_not_grow_with_topics(): void
    {
        $count = function () {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->getJson('/api/study/topics?per_page=50')->assertOk();
            $n = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $n;
        };

        Topic::factory()->count(2)->create(['user_id' => $this->user->id]);
        $few = $count();

        Topic::factory()->count(8)->create(['user_id' => $this->user->id]);
        $many = $count();

        $this->assertSame($few, $many);
    }
}
