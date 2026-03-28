<?php

namespace Tests\Unit;

use App\Models\PracticeLog;
use App\Models\StudyTask;
use App\Models\Topic;
use App\Services\StudyTracker\BuildStudyReportService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Tests\TestCase;

class BuildStudyReportServiceTest extends TestCase
{
    public function test_to_csv_handles_eloquent_collections_without_crashing(): void
    {
        $service = new BuildStudyReportService();

        $topic = new Topic(['title' => 'Algorithms']);

        $task = new StudyTask([
            'task_type' => 'revision',
            'revision_no' => 2,
            'title' => 'Graphs Review',
            'status' => 'completed',
            'notes' => 'Covered shortest path problems.',
        ]);
        $task->scheduled_date = Carbon::parse('2026-03-20');
        $task->completed_at = Carbon::parse('2026-03-20 08:15:00');
        $task->setRelation('topic', $topic);

        $practiceLog = new PracticeLog([
            'practice_type' => 'problem_solving',
            'duration_minutes' => 45,
            'outcome' => 'good',
            'details' => 'Solved two graph problems.',
        ]);
        $practiceLog->practiced_on = Carbon::parse('2026-03-21');
        $practiceLog->setRelation('topic', $topic);

        $csv = $service->toCsv([
            'generated_at' => '2026-03-28 10:00:00',
            'months' => ['2026-03'],
            'summary' => [
                '2026-03' => [
                    'tasks_total' => 1,
                    'tasks_completed' => 1,
                    'tasks_pending' => 0,
                    'tasks_skipped' => 0,
                    'tasks_missed' => 0,
                    'practice_sessions' => 1,
                    'practice_minutes' => 45,
                ],
            ],
            'tasks' => new EloquentCollection([$task]),
            'practice_logs' => new EloquentCollection([$practiceLog]),
        ]);

        $this->assertStringContainsString('Graphs Review', $csv);
        $this->assertStringContainsString('2026-03-20', $csv);
        $this->assertStringContainsString('Solved two graph problems.', $csv);
        $this->assertStringContainsString('2026-03-21', $csv);
    }
}
