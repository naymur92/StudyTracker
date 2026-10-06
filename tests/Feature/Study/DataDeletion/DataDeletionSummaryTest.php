<?php

namespace Tests\Feature\Study\DataDeletion;

use App\Models\StudyBlock;
use App\Models\StudyWeek;
use App\Services\StudyTracker\DataDeletion\DataDeletionSummaryService;
use Tests\Feature\Study\StudyApiTestCase;

class DataDeletionSummaryTest extends StudyApiTestCase
{
    use SeedsDeletableData;

    private function service(): DataDeletionSummaryService
    {
        return app(DataDeletionSummaryService::class);
    }

    public function test_for_user_counts_each_category_on_its_own(): void
    {
        foreach (range(1, 3) as $i) {
            $topic = $this->topicFor($this->user);
            $this->tasksFor($topic, 2);
        }
        $this->mistakeFor($this->user);
        foreach (['2026-09-06', '2026-09-13'] as $weekStart) {
            $week = StudyWeek::factory()->create(['user_id' => $this->user->id, 'week_start' => $weekStart]);
            StudyBlock::factory()->count(2)->create(['study_week_id' => $week->id]);
        }

        $summary = collect($this->service()->forUser($this->user))->keyBy('key');

        $this->assertCount(8, $summary);
        $this->assertSame(['topics' => 3, 'study_tasks' => 6], collect($summary['topics']['records'])->pluck('count', 'key')->all());
        $this->assertSame(9, $summary['topics']['total']);
        $this->assertSame(['mistakes' => 1], collect($summary['mistakes']['records'])->pluck('count', 'key')->all());
        $this->assertSame(['study_weeks' => 2, 'study_blocks' => 4], collect($summary['weekly_plans']['records'])->pluck('count', 'key')->all());
        $this->assertSame(0, $summary['review_history']['total']);
        $this->assertNotEmpty($summary['categories']['notes']);
    }

    public function test_for_categories_gives_dates_capped_samples_and_references(): void
    {
        foreach (range(1, 25) as $i) {
            $this->topicFor($this->user, [
                'title' => "Topic {$i}",
                'first_study_date' => now()->setDate(2026, 1, $i)->toDateString(),
            ]);
        }
        $category = $this->categoryFor($this->user, ['name' => 'Algorithms']);
        $this->topicFor($this->user, ['category_id' => $category->id, 'kind' => 'mistake', 'first_study_date' => '2026-03-01']);

        $summary = $this->service()->forCategories($this->user, ['topics', 'categories']);

        $records = collect($summary['records'])->keyBy('key');
        $this->assertSame(25, $records['topics']['count']);
        $this->assertSame('2026-01-01', $records['topics']['earliest']);
        $this->assertSame('2026-01-25', $records['topics']['latest']);
        $this->assertCount(20, $summary['samples']['topics']['titles']);
        $this->assertSame(25, $summary['samples']['topics']['total']);
        $this->assertSame(['Algorithms'], $summary['samples']['categories']['titles']);
        $this->assertSame([[
            'table' => 'topics',
            'column' => 'category_id',
            'label' => 'Topics',
            'count' => 1,
        ]], $summary['references']);
        $this->assertSame(26, $summary['total']);
    }

    public function test_snapshot_and_diff(): void
    {
        $topic = $this->topicFor($this->user);
        $this->practiceLogFor($topic);

        $snapshot = $this->service()->snapshot($this->user, ['topics', 'practice_logs']);

        $this->assertSame(['topics' => 1, 'practice_logs' => 1], $snapshot['categories']['topics']);
        $this->assertSame(['practice_logs' => 1], $snapshot['categories']['practice_logs']);
        $this->assertEquals(['topics' => 1, 'practice_logs' => 1], $snapshot['records']);

        $this->topicFor($this->user);
        $this->topicFor($this->user);
        $live = $this->service()->forCategories($this->user, ['topics', 'practice_logs'])['counts'];

        $diff = $this->service()->diff($snapshot['records'], $live);

        $this->assertSame(['label' => 'Topics', 'before' => 1, 'now' => 3, 'change' => 2], $diff['topics']);
        $this->assertSame(0, $diff['practice_logs']['change']);
        $this->assertSame(['topics', 'practice_logs'], array_keys($diff));
    }
}
