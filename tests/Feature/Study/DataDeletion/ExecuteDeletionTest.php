<?php

namespace Tests\Feature\Study\DataDeletion;

use App\Jobs\ProcessDataDeletionRequestJob;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\CategoryReviewSchedule;
use App\Models\DataDeletionRequest;
use App\Models\EmailedStudyReport;
use App\Models\PracticeLog;
use App\Models\ReviewLoadSnapshot;
use App\Models\StudyBlock;
use App\Models\StudyBlockSession;
use App\Models\StudyTask;
use App\Models\StudyWeek;
use App\Models\Topic;
use App\Models\TopicRevisionTemplate;
use App\Models\User;
use App\Services\StudyTracker\DataDeletion\DataArchiveService;
use App\Services\StudyTracker\DataDeletion\DataCategoryRegistry;
use App\Services\StudyTracker\DataDeletion\DataCollector;
use App\Services\StudyTracker\DataDeletion\DeletionPlan;
use App\Services\StudyTracker\StudyPreferences;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Feature\Study\StudyApiTestCase;

class ExecuteDeletionTest extends StudyApiTestCase
{
    use SeedsDeletableData;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->admin = User::factory()->create(['type' => 1]);
    }

    private function approvedRequest(array $categories, ?User $user = null): DataDeletionRequest
    {
        return DataDeletionRequest::create([
            'user_id' => ($user ?? $this->user)->id,
            'categories' => $categories,
            'status' => DataDeletionRequest::STATUS_APPROVED,
            'reviewed_by' => $this->admin->id,
            'reviewed_at' => now(),
        ]);
    }

    private function process(DataDeletionRequest $request): DataDeletionRequest
    {
        ProcessDataDeletionRequestJob::dispatch($request->id);

        return $request->fresh();
    }

    private function archiveOf(DataDeletionRequest $request): array
    {
        return app(DataArchiveService::class)->read($request->archive_path);
    }

    public function test_topics_deletes_topics_tasks_and_logs_and_clears_references(): void
    {
        $topic = $this->topicFor($this->user);
        $this->tasksFor($topic, 4);
        $this->practiceLogFor($topic);
        $mistake = $this->mistakeFor($this->user, ['parent_topic_id' => $topic->id]);
        $week = StudyWeek::factory()->create(['user_id' => $this->user->id]);
        $block = StudyBlock::factory()->create(['study_week_id' => $week->id, 'topic_id' => $topic->id]);
        $otherUsersTopic = $this->topicFor(User::factory()->create());

        $request = $this->process($this->approvedRequest(['topics']));

        $this->assertSame(DataDeletionRequest::STATUS_COMPLETED, $request->status);
        $this->assertEquals(['topics' => 1, 'study_tasks' => 4, 'practice_logs' => 1], $request->deleted_counts);
        $this->assertNull(Topic::withTrashed()->find($topic->id));
        $this->assertSame(0, StudyTask::where('topic_id', $topic->id)->count());
        $this->assertSame(0, PracticeLog::where('user_id', $this->user->id)->count());

        $this->assertNull($mistake->fresh()->parent_topic_id);
        $this->assertNull($block->fresh()->topic_id);
        $this->assertNotNull($otherUsersTopic->fresh());

        $archive = $this->archiveOf($request);
        $this->assertCount(1, $archive['tables']['topics']);
        $this->assertCount(4, $archive['tables']['study_tasks']);
        $this->assertEqualsCanonicalizing([
            ['table' => 'topics', 'id' => $mistake->id, 'column' => 'parent_topic_id', 'value' => $topic->id],
            ['table' => 'study_blocks', 'id' => $block->id, 'column' => 'topic_id', 'value' => $topic->id],
        ], $archive['references']);
        $this->assertSame(hash('sha256', Storage::disk('local')->get($request->archive_path)), $request->archive_checksum);
        $this->assertNotNull($request->completed_at);
    }

    public function test_mistakes_with_a_parent_topic_in_the_same_request(): void
    {
        $topic = $this->topicFor($this->user);
        $mistake = $this->mistakeFor($this->user, ['parent_topic_id' => $topic->id]);
        $this->tasksFor($mistake, 3);

        $request = $this->process($this->approvedRequest(['topics', 'mistakes']));

        $this->assertSame(DataDeletionRequest::STATUS_COMPLETED, $request->status, (string) $request->error_message);
        $this->assertEquals(['topics' => 1, 'mistakes' => 1, 'study_tasks' => 3], $request->deleted_counts);
        $this->assertSame(0, Topic::withTrashed()->where('user_id', $this->user->id)->count());
        $this->assertCount(2, $this->archiveOf($request)['tables']['topics']);
    }

    public function test_mistakes_only(): void
    {
        $topic = $this->topicFor($this->user);
        $mistake = $this->mistakeFor($this->user, ['parent_topic_id' => $topic->id]);
        $this->tasksFor($mistake, 2);

        $request = $this->process($this->approvedRequest(['mistakes']));

        $this->assertEquals(['mistakes' => 1, 'study_tasks' => 2], $request->deleted_counts);
        $this->assertNotNull($topic->fresh());
        $this->assertNull(Topic::withTrashed()->find($mistake->id));
    }

    public function test_practice_logs(): void
    {
        $topic = $this->topicFor($this->user);
        foreach (range(1, 12) as $i) {
            $this->practiceLogFor($topic);
        }

        $request = $this->process($this->approvedRequest(['practice_logs']));

        $this->assertEquals(['practice_logs' => 12], $request->deleted_counts);
        $this->assertCount(12, $this->archiveOf($request)['tables']['practice_logs']);
        $this->assertSame(0, PracticeLog::count());
        $this->assertNotNull($topic->fresh());
    }

    public function test_weekly_plans(): void
    {
        foreach (['2026-09-06', '2026-09-13'] as $weekStart) {
            $week = StudyWeek::factory()->create(['user_id' => $this->user->id, 'week_start' => $weekStart]);
            StudyBlock::factory()->count($weekStart === '2026-09-06' ? 4 : 5)->create(['study_week_id' => $week->id]);
        }

        $request = $this->process($this->approvedRequest(['weekly_plans']));

        $this->assertEquals(['study_weeks' => 2, 'study_blocks' => 9], $request->deleted_counts);
        $this->assertSame(0, StudyWeek::count());
        $this->assertSame(0, StudyBlock::count());
    }

    public function test_weekly_plans_include_timer_runs(): void
    {
        $blocks = collect();
        foreach (['2026-09-06', '2026-09-13'] as $weekStart) {
            $week = StudyWeek::factory()->create(['user_id' => $this->user->id, 'week_start' => $weekStart]);
            $blocks = $blocks->merge(StudyBlock::factory()->count($weekStart === '2026-09-06' ? 4 : 5)->create(['study_week_id' => $week->id]));
        }
        $this->runFor($blocks[0], 40);
        $this->runFor($blocks[0], 50);
        $this->runFor($blocks[5], 20);

        $request = $this->process($this->approvedRequest(['weekly_plans']));

        $this->assertEquals(['study_weeks' => 2, 'study_blocks' => 9, 'study_block_sessions' => 3], $request->deleted_counts);
        $this->assertSame(0, StudyBlockSession::count());
        $archive = $this->archiveOf($request);
        $this->assertCount(3, $archive['tables']['study_block_sessions']);
        $this->assertSame([2400, 3000, 1200], collect($archive['tables']['study_block_sessions'])->pluck('used_seconds')->map(fn ($v) => (int) $v)->all());
    }

    public function test_active_timer_run_is_archived_as_ended(): void
    {
        $week = StudyWeek::factory()->create(['user_id' => $this->user->id, 'week_start' => now()->startOfWeek()->toDateString()]);
        $block = StudyBlock::factory()->create(['study_week_id' => $week->id, 'block_date' => today()->toDateString(), 'planned_minutes' => 90]);
        StudyBlockSession::factory()->running(now()->subMinutes(25))->create(['study_block_id' => $block->id, 'user_id' => $this->user->id]);

        $request = $this->process($this->approvedRequest(['weekly_plans']));

        $this->assertSame(DataDeletionRequest::STATUS_COMPLETED, $request->status, (string) $request->error_message);
        $run = $this->archiveOf($request)['tables']['study_block_sessions'][0];
        $this->assertNull($run['active_user_id']);
        $this->assertNull($run['resumed_at']);
        $this->assertNotNull($run['ended_at']);
        $this->assertSame('stopped', $run['end_reason']);
        $this->assertSame(1500, (int) $run['used_seconds']);
    }

    public function test_categories_leave_topics_uncategorised_and_spare_system_categories(): void
    {
        $category = $this->categoryFor($this->user);
        $trashed = $this->categoryFor($this->user);
        $trashed->delete();
        $this->scheduleFor($this->user, $category);
        $topic = $this->topicFor($this->user, ['category_id' => $category->id]);
        $system = Category::factory()->system()->create();
        $otherUser = User::factory()->create();
        $othersSchedule = $this->scheduleFor($otherUser, $system);

        $request = $this->process($this->approvedRequest(['categories']));

        $this->assertEquals(['categories' => 2, 'category_review_schedules' => 1], $request->deleted_counts);
        $this->assertSame(0, Category::withTrashed()->where('user_id', $this->user->id)->count());
        $this->assertNull($topic->fresh()->category_id);
        $this->assertNotNull($system->fresh());
        $this->assertNotNull($othersSchedule->fresh());
    }

    public function test_study_settings_reset_preferences_to_defaults(): void
    {
        $this->templateFor($this->user);
        $this->scheduleFor($this->user, Category::factory()->system()->create());
        StudyPreferences::update($this->user, ['review_budget_minutes' => 60]);

        $request = $this->process($this->approvedRequest(['study_settings']));

        $this->assertEquals(['category_review_schedules' => 1, 'topic_revision_templates' => 1, 'study_preferences' => 1], $request->deleted_counts);
        $this->assertSame(0, TopicRevisionTemplate::where('user_id', $this->user->id)->count());
        $this->assertGreaterThan(0, TopicRevisionTemplate::whereNull('user_id')->count());
        $this->assertSame(0, CategoryReviewSchedule::where('user_id', $this->user->id)->count());
        $this->assertNull($this->user->fresh()->study_preferences);
        $this->assertSame(StudyPreferences::defaults(), StudyPreferences::for($this->user->fresh())->all());
        $this->assertSame(['review_budget_minutes' => 60], json_decode($this->archiveOf($request)['user_columns']['study_preferences'], true));
    }

    public function test_report_and_review_history(): void
    {
        $this->reportFor($this->user);
        $this->snapshotFor($this->user, '2026-09-01');
        $this->snapshotFor($this->user, '2026-09-02');

        $request = $this->process($this->approvedRequest(['report_history', 'review_history']));

        $this->assertEquals(['emailed_study_reports' => 1, 'review_load_snapshots' => 2], $request->deleted_counts);
        $this->assertSame(0, EmailedStudyReport::count());
        $this->assertSame(0, ReviewLoadSnapshot::count());
    }

    public function test_completion_is_logged_with_the_reviewer_as_causer(): void
    {
        $this->topicFor($this->user);

        $request = $this->process($this->approvedRequest(['topics']));

        $log = ActivityLog::where('event', 'completed')->where('subject_id', $request->id)->first();
        $this->assertNotNull($log);
        $this->assertSame($this->admin->id, (int) $log->causer_id);
        $this->assertSame(['topics' => 1], $log->properties['counts']);
    }

    public function test_rows_created_after_collection_survive(): void
    {
        $this->topicFor($this->user);
        $late = new \ArrayObject;

        $this->app->bind(DataCollector::class, fn ($app) => new class($app->make(DataCategoryRegistry::class), $this->user, $late) extends DataCollector
        {
            public function __construct(DataCategoryRegistry $registry, private User $owner, private \ArrayObject $late)
            {
                parent::__construct($registry);
            }

            public function collect(User $user, array $categories, bool $lock = false): DeletionPlan
            {
                $plan = parent::collect($user, $categories, $lock);
                $this->late['topic'] = Topic::factory()->create(['user_id' => $this->owner->id]);

                return $plan;
            }
        });

        $request = $this->process($this->approvedRequest(['topics']));

        $this->assertSame(DataDeletionRequest::STATUS_COMPLETED, $request->status, (string) $request->error_message);
        $this->assertEquals(['topics' => 1], $request->deleted_counts);
        $this->assertNotNull($late['topic']->fresh());
    }

    public function test_archive_failure_deletes_nothing_and_leaves_no_file(): void
    {
        $topic = $this->topicFor($this->user);
        $this->tasksFor($topic, 2);

        $this->app->bind(DataArchiveService::class, fn () => new class extends DataArchiveService
        {
            public function verify(string $path, string $checksum, DeletionPlan $plan): void
            {
                throw new RuntimeException('Simulated verification failure');
            }
        });

        $request = $this->process($this->approvedRequest(['topics']));

        $this->assertSame(DataDeletionRequest::STATUS_FAILED, $request->status);
        $this->assertSame('Simulated verification failure', $request->error_message);
        $this->assertNotNull($request->failed_at);
        $this->assertNull($request->archive_path);
        $this->assertNotNull($topic->fresh());
        $this->assertSame(2, StudyTask::where('topic_id', $topic->id)->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertTrue(ActivityLog::where('event', 'failed')->where('subject_id', $request->id)->exists());
    }

    public function test_running_the_job_twice_deletes_once(): void
    {
        $this->topicFor($this->user);
        $request = $this->approvedRequest(['topics']);

        ProcessDataDeletionRequestJob::dispatch($request->id);
        $first = $request->fresh();
        $this->topicFor($this->user);
        ProcessDataDeletionRequestJob::dispatch($request->id);

        $this->assertSame(DataDeletionRequest::STATUS_COMPLETED, $request->fresh()->status);
        $this->assertSame($first->archive_checksum, $request->fresh()->archive_checksum);
        $this->assertSame(1, Topic::where('user_id', $this->user->id)->count());
        $this->assertSame(1, ActivityLog::where('event', 'completed')->count());
    }

    public function test_pending_request_is_not_processed(): void
    {
        $this->topicFor($this->user);
        $request = $this->approvedRequest(['topics']);
        $request->update(['status' => DataDeletionRequest::STATUS_PENDING]);

        ProcessDataDeletionRequestJob::dispatch($request->id);

        $this->assertSame(DataDeletionRequest::STATUS_PENDING, $request->fresh()->status);
        $this->assertSame(1, Topic::count());
    }
}
