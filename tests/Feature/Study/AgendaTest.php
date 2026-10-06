<?php

namespace Tests\Feature\Study;

use App\Models\StudyTask;
use App\Models\Topic;
use App\Services\IdHasher;

class AgendaTest extends StudyApiTestCase
{
    public function test_revision_groups_are_ordered_numerically(): void
    {
        $topic = Topic::factory()->create(['user_id' => $this->user->id]);
        StudyTask::factory()->for($topic)->revision(6)->create(['user_id' => $this->user->id]);
        StudyTask::factory()->for($topic)->revision(2)->create(['user_id' => $this->user->id]);
        StudyTask::factory()->for($topic)->learn()->create(['user_id' => $this->user->id]);
        StudyTask::factory()->for($topic)->revision(1)->create(['user_id' => $this->user->id, 'scheduled_date' => today()->subDays(3)]);

        $groups = array_keys($this->getJson('/api/study/daily-tasks')->assertOk()->json('data.groups'));

        $this->assertSame(['learn', 'revision_2', 'revision_6', 'overdue'], $groups);
    }

    public function test_deleted_topic_leaves_no_due_work(): void
    {
        $topic = Topic::factory()->create(['user_id' => $this->user->id]);
        StudyTask::factory()->for($topic)->revision(1)->create(['user_id' => $this->user->id]);
        StudyTask::factory()->for($topic)->revision(2)->create(['user_id' => $this->user->id, 'scheduled_date' => today()->subDay()]);

        $this->deleteJson('/api/study/topics/'.IdHasher::encode($topic->id))->assertOk();

        $this->assertSame([], $this->getJson('/api/study/daily-tasks')->json('data.groups'));
        $dashboard = $this->getJson('/api/study/dashboard')->assertOk();
        $this->assertSame(0, $dashboard->json('data.stats.today_pending'));
        $this->assertSame(0, $dashboard->json('data.stats.overdue'));
        $calendar = $this->getJson('/api/study/calendar?year='.today()->year.'&month='.today()->month)->assertOk();
        $this->assertEmpty($calendar->json('data.calendar'));
    }
}
