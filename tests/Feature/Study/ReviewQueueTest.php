<?php

namespace Tests\Feature\Study;

use App\Models\Category;
use App\Models\StudyTask;
use App\Models\Topic;
use Illuminate\Support\Carbon;

class ReviewQueueTest extends StudyApiTestCase
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

    private function review(string $title, string $date, ?Category $category = null, array $topicAttrs = [], int $revisionNo = 1): StudyTask
    {
        $topic = Topic::factory()->withSchedule([1, 7, 30, 90])->create(array_merge([
            'user_id' => $this->user->id, 'title' => $title, 'category_id' => $category?->id,
        ], $topicAttrs));

        return StudyTask::factory()->for($topic)->revision($revisionNo)->create(['user_id' => $this->user->id, 'scheduled_date' => $date]);
    }

    private function queue(): array
    {
        return $this->getJson('/api/study/review-queue')->assertOk()->assertJsonPath('flag', true)->json('data');
    }

    private function titles(array $queue): array
    {
        return array_map(fn ($i) => $i['topic']['title'], $queue['items']);
    }

    public function test_due_and_overdue_but_not_future_or_archived(): void
    {
        $this->review('Today', '2026-10-14');
        $this->review('Overdue', '2026-10-10');
        $this->review('Tomorrow', '2026-10-15');
        $this->review('Archived', '2026-10-14', null, ['status' => 'archived']);

        $this->assertSame(['Overdue', 'Today'], $this->titles($this->queue()));
    }

    public function test_one_review_per_topic(): void
    {
        $task = $this->review('Backlog', '2026-10-08', null, [], 2);
        StudyTask::factory()->for($task->topic)->revision(3)->create(['user_id' => $this->user->id, 'scheduled_date' => '2026-10-12']);

        $queue = $this->queue();
        $this->assertCount(1, $queue['items']);
        $this->assertSame(2, $queue['items'][0]['task']['revision_no']);
        $this->assertSame(1, $queue['summary']['due_topics']);
    }

    public function test_overdue_oldest_first_then_today(): void
    {
        $this->review('Today', '2026-10-14');
        $this->review('Since 4th', '2026-10-04');
        $this->review('Since 1st', '2026-10-01');

        $this->assertSame(['Since 1st', 'Since 4th', 'Today'], $this->titles($this->queue()));
    }

    public function test_today_is_interleaved_across_categories(): void
    {
        $algorithms = Category::factory()->create(['user_id' => $this->user->id, 'name' => 'Algorithms']);
        $backend = Category::factory()->create(['user_id' => $this->user->id, 'name' => 'Backend']);
        $this->review('A1', '2026-10-14', $algorithms);
        $this->review('A2', '2026-10-14', $algorithms);
        $this->review('B1', '2026-10-14', $backend);
        $this->review('U1', '2026-10-14');

        $this->assertSame(['A1', 'B1', 'U1', 'A2'], $this->titles($this->queue()));
    }

    public function test_summary_and_budget_split(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->review("T{$i}", '2026-10-14');
        }

        $queue = $this->queue();
        $this->assertSame(12, $queue['summary']['due_topics']);
        $this->assertSame(36, $queue['summary']['estimated_minutes']);
        $this->assertTrue($queue['summary']['over_budget']);
        $this->assertSame(8, count(array_filter($queue['items'], fn ($i) => $i['within_budget'])));
        $this->assertTrue($queue['items'][0]['within_budget']);
        $this->assertEquals(24, $queue['items'][7]['cumulative_minutes']);
    }

    public function test_grade_preview_for_mid_schedule_topic(): void
    {
        $this->review('Mid', '2026-10-14', null, ['srs_step' => 1], 2);

        $this->assertSame([
            'again' => '2026-10-15',
            'hard' => '2026-10-15',
            'good' => '2026-11-06',
            'easy' => '2027-01-05',
        ], $this->queue()['items'][0]['grade_preview']);
    }

    public function test_grade_preview_at_graduation(): void
    {
        $this->review('Final', '2026-10-14', null, ['srs_step' => 3], 4);

        $preview = $this->queue()['items'][0]['grade_preview'];
        $this->assertNull($preview['good']);
        $this->assertNull($preview['easy']);
    }

    public function test_item_content_includes_recall_card_and_mistake_details(): void
    {
        $parent = Topic::factory()->create(['user_id' => $this->user->id, 'title' => 'IELTS Reading']);
        $this->review('Mistake', '2026-10-14', null, [
            'kind' => 'mistake', 'parent_topic_id' => $parent->id,
            'recall_questions' => [['question' => 'T/F/NG?', 'answer' => 'Not Given']],
            'mistake_details' => ['my_answer' => 'False', 'correct_answer' => 'Not Given', 'cause' => 'concept', 'source' => 'Mock 3'],
        ]);

        $item = $this->queue()['items'][0];
        $this->assertSame('mistake', $item['topic']['kind']);
        $this->assertSame('False', $item['topic']['mistake_details']['my_answer']);
        $this->assertSame('IELTS Reading', $item['topic']['parent_topic']['title']);
        $this->assertSame('T/F/NG?', $item['topic']['recall_questions'][0]['question']);
        $this->assertFalse($item['task']['is_overdue']);
    }
}
