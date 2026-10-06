<?php

namespace Tests\Feature\Study\DataDeletion;

use App\Jobs\ProcessDataDeletionRequestJob;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\DataDeletionRequest;
use App\Models\PracticeLog;
use App\Models\ReviewLoadSnapshot;
use App\Models\StudyBlock;
use App\Models\StudyBlockSession;
use App\Models\StudyTask;
use App\Models\StudyWeek;
use App\Models\Topic;
use App\Models\User;
use App\Services\IdHasher;
use App\Services\StudyTracker\DataDeletion\DataArchiveService;
use App\Services\StudyTracker\DataDeletion\RestoreDataDeletionService;
use App\Services\StudyTracker\StudyPreferences;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Feature\Study\StudyApiTestCase;

class RestoreTest extends StudyApiTestCase
{
    use SeedsDeletableData;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->admin = User::factory()->create(['type' => 1]);
    }

    private function deleted(array $categories): DataDeletionRequest
    {
        $request = DataDeletionRequest::create([
            'user_id' => $this->user->id,
            'categories' => $categories,
            'status' => DataDeletionRequest::STATUS_APPROVED,
            'reviewed_by' => $this->admin->id,
        ]);
        ProcessDataDeletionRequestJob::dispatch($request->id);
        $request->refresh();
        $this->assertSame(DataDeletionRequest::STATUS_COMPLETED, $request->status, (string) $request->error_message);

        return $request;
    }

    private function service(): RestoreDataDeletionService
    {
        return app(RestoreDataDeletionService::class);
    }

    public function test_clean_restore_brings_back_same_ids_and_old_links(): void
    {
        $category = $this->categoryFor($this->user);
        $topic = $this->topicFor($this->user, ['category_id' => $category->id, 'recall_questions' => [['question' => 'Q?', 'answer' => 'A']]]);
        $this->tasksFor($topic, 3);
        $this->practiceLogFor($topic);
        $mistake = $this->mistakeFor($this->user, ['parent_topic_id' => $topic->id]);
        $week = StudyWeek::factory()->create(['user_id' => $this->user->id]);
        $block = StudyBlock::factory()->create(['study_week_id' => $week->id, 'topic_id' => $topic->id]);
        $taskIds = StudyTask::where('topic_id', $topic->id)->pluck('id')->sort()->values()->all();
        $before = (array) DB::table('topics')->where('id', $topic->id)->first();

        $request = $this->deleted(['topics']);
        $this->assertNull($block->fresh()->topic_id);

        $report = $this->service()->restore($request, $this->admin);

        $this->assertSame(['restored' => 5, 'skipped' => 0], $report['totals']);
        $this->assertSame($before, (array) DB::table('topics')->where('id', $topic->id)->first());
        $this->assertSame($taskIds, StudyTask::where('topic_id', $topic->id)->pluck('id')->sort()->values()->all());
        $this->assertSame(1, PracticeLog::where('topic_id', $topic->id)->count());
        $this->assertSame($topic->id, $mistake->fresh()->parent_topic_id);
        $this->assertSame($topic->id, $block->fresh()->topic_id);
        $this->assertSame(2, $report['references']['restored']);

        $this->getJson('/api/study/topics/'.IdHasher::encode($topic->id))
            ->assertOk()
            ->assertJsonPath('data.topic.id', IdHasher::encode($topic->id));

        $request->refresh();
        $this->assertSame(DataDeletionRequest::STATUS_RESTORED, $request->status);
        $this->assertSame($this->admin->id, $request->restored_by);
        $this->assertNotNull($request->restored_at);
        $this->assertSame(5, $request->restore_report['totals']['restored']);
        $this->assertTrue(ActivityLog::where('event', 'restored')->where('causer_id', $this->admin->id)->exists());
    }

    public function test_slug_clash_restores_with_a_free_slug(): void
    {
        $topic = $this->topicFor($this->user, ['slug' => 'closures']);
        $request = $this->deleted(['topics']);
        $this->topicFor($this->user, ['slug' => 'closures']);

        $report = $this->service()->restore($request, $this->admin);

        $this->assertSame('closures-restored', Topic::find($topic->id)->slug);
        $this->assertSame([['id' => $topic->id, 'from' => 'closures', 'to' => 'closures-restored']], $report['tables']['topics']['renamed']);
    }

    public function test_week_clash_skips_the_week_and_its_blocks(): void
    {
        $kept = StudyWeek::factory()->create(['user_id' => $this->user->id, 'week_start' => '2026-09-06']);
        StudyBlock::factory()->count(3)->create(['study_week_id' => $kept->id]);
        $other = StudyWeek::factory()->create(['user_id' => $this->user->id, 'week_start' => '2026-09-13']);
        StudyBlock::factory()->count(2)->create(['study_week_id' => $other->id]);
        $request = $this->deleted(['weekly_plans']);
        $newPlan = StudyWeek::factory()->create(['user_id' => $this->user->id, 'week_start' => '2026-09-06']);

        $report = $this->service()->restore($request, $this->admin);

        $this->assertSame(['unique_exists' => 1], $report['tables']['study_weeks']['skip_reasons']);
        $this->assertSame(['parent_missing' => 3], $report['tables']['study_blocks']['skip_reasons']);
        $this->assertSame(2, $report['tables']['study_blocks']['restored']);
        $this->assertNull(StudyWeek::find($kept->id));
        $this->assertNotNull(StudyWeek::find($other->id));
        $this->assertSame(0, StudyBlock::where('study_week_id', $newPlan->id)->count());
        $this->assertSame(2, StudyBlock::where('study_week_id', $other->id)->count());
    }

    public function test_restore_brings_timer_runs_back(): void
    {
        $week = StudyWeek::factory()->create(['user_id' => $this->user->id, 'week_start' => '2026-09-06']);
        $block = StudyBlock::factory()->create(['study_week_id' => $week->id, 'status' => 'partial']);
        $this->runFor($block, 40);
        $this->runFor($block, 25);
        $before = $block->fresh()->endedSeconds();

        $request = $this->deleted(['weekly_plans']);
        $this->assertSame(0, StudyBlockSession::count());

        $report = $this->service()->restore($request, $this->admin);

        $this->assertSame(2, $report['tables']['study_block_sessions']['restored']);
        $this->assertSame($before, $block->fresh()->endedSeconds());
        $this->assertSame(65 * 60, $before);
        $this->assertNull($block->fresh()->activeSession);
    }

    public function test_snapshot_clash_is_skipped(): void
    {
        $this->snapshotFor($this->user, '2026-09-01', 3);
        $this->snapshotFor($this->user, '2026-09-02', 4);
        $request = $this->deleted(['review_history']);
        $this->snapshotFor($this->user, '2026-09-02', 9);

        $report = $this->service()->restore($request, $this->admin);

        $this->assertSame(1, $report['tables']['review_load_snapshots']['restored']);
        $this->assertSame(['unique_exists' => 1], $report['tables']['review_load_snapshots']['skip_reasons']);
        $this->assertSame(9, ReviewLoadSnapshot::whereDate('snapshot_date', '2026-09-02')->value('due_topics'));
    }

    public function test_current_preferences_are_kept(): void
    {
        StudyPreferences::update($this->user, ['review_budget_minutes' => 60]);
        $this->templateFor($this->user);
        $request = $this->deleted(['study_settings']);
        StudyPreferences::update($this->user->fresh(), ['review_budget_minutes' => 15]);

        $report = $this->service()->restore($request, $this->admin);

        $this->assertSame('kept_current', $report['preferences']);
        $this->assertSame(15, StudyPreferences::for($this->user->fresh())->all()['review_budget_minutes']);
    }

    public function test_preferences_come_back_when_the_user_has_none(): void
    {
        StudyPreferences::update($this->user, ['review_budget_minutes' => 60]);
        $request = $this->deleted(['study_settings']);

        $report = $this->service()->restore($request, $this->admin);

        $this->assertSame('restored', $report['preferences']);
        $this->assertSame(60, StudyPreferences::for($this->user->fresh())->all()['review_budget_minutes']);
    }

    public function test_category_gone_since_deletion_restores_topic_without_category(): void
    {
        $category = $this->categoryFor($this->user);
        $topic = $this->topicFor($this->user, ['category_id' => $category->id]);
        $request = $this->deleted(['topics']);
        $category->forceDelete();

        $report = $this->service()->restore($request, $this->admin);

        $this->assertNull(Topic::find($topic->id)->category_id);
        $this->assertSame(1, $report['tables']['topics']['nulled']);
    }

    public function test_restoring_categories_puts_topic_links_back(): void
    {
        $category = $this->categoryFor($this->user);
        $topic = $this->topicFor($this->user, ['category_id' => $category->id]);
        $this->scheduleFor($this->user, $category);
        $request = $this->deleted(['categories']);
        $this->assertNull($topic->fresh()->category_id);

        $this->service()->restore($request, $this->admin);

        $this->assertNotNull(Category::find($category->id));
        $this->assertSame($category->id, $topic->fresh()->category_id);
    }

    public function test_second_restore_is_refused(): void
    {
        $this->topicFor($this->user);
        $request = $this->deleted(['topics']);
        $this->service()->restore($request, $this->admin);

        $this->expectException(RuntimeException::class);

        $this->service()->restore($request->fresh(), $this->admin);
    }

    public function test_purged_archive_is_refused(): void
    {
        $this->topicFor($this->user);
        $request = $this->deleted(['topics']);
        $request->update(['archive_purged_at' => now()]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('purged');

        $this->service()->plan($request);
    }

    public function test_preview_writes_nothing(): void
    {
        $this->topicFor($this->user);
        $request = $this->deleted(['topics']);

        $report = $this->service()->plan($request);

        $this->assertSame(1, $report['tables']['topics']['restored']);
        $this->assertSame(0, Topic::count());
        $this->assertSame(DataDeletionRequest::STATUS_COMPLETED, $request->fresh()->status);
    }

    public function test_columns_missing_from_the_current_schema_are_dropped_and_reported(): void
    {
        $topic = $this->topicFor($this->user);
        $request = $this->deleted(['topics']);

        $archive = app(DataArchiveService::class);
        $document = $archive->read($request->archive_path);
        $document['tables']['topics'][0]['legacy_column'] = 'old value';
        Storage::disk('local')->put($request->archive_path, gzencode(json_encode($document)));

        $report = $this->service()->restore($request, $this->admin);

        $this->assertNotNull(Topic::find($topic->id));
        $this->assertSame(['legacy_column'], $report['tables']['topics']['dropped_columns']);
    }
}
