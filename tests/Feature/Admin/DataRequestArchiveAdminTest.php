<?php

namespace Tests\Feature\Admin;

use App\Jobs\ProcessDataDeletionRequestJob;
use App\Models\ActivityLog;
use App\Models\DataDeletionRequest;
use App\Models\StudyBlock;
use App\Models\StudyWeek;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Study\DataDeletion\SeedsDeletableData;

class DataRequestArchiveAdminTest extends AdminTestCase
{
    use SeedsDeletableData;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->member = User::factory()->create();
    }

    private function completed(array $categories): DataDeletionRequest
    {
        $request = DataDeletionRequest::create([
            'user_id' => $this->member->id,
            'categories' => $categories,
            'status' => DataDeletionRequest::STATUS_APPROVED,
        ]);
        ProcessDataDeletionRequestJob::dispatch($request->id);

        return tap($request->fresh(), fn ($r) => $this->assertSame('completed', $r->status, (string) $r->error_message));
    }

    public function test_preview_lists_skipped_rows_and_post_restores(): void
    {
        $week = StudyWeek::factory()->create(['user_id' => $this->member->id, 'week_start' => '2026-09-06']);
        StudyBlock::factory()->count(2)->create(['study_week_id' => $week->id]);
        $this->topicFor($this->member, ['title' => 'Dynamic Programming']);
        $request = $this->completed(['weekly_plans', 'topics']);
        StudyWeek::factory()->create(['user_id' => $this->member->id, 'week_start' => '2026-09-06']);
        $this->adminWith(['data-request-view', 'data-request-restore']);

        $this->get(route('data-requests.show', $request))->assertOk()->assertSee('id="restore-button"', false);

        $this->get(route('data-requests.restore.preview', $request))
            ->assertOk()
            ->assertSee('Skipped rows')
            ->assertSee('Current data already uses the same key')
            ->assertSee('The row it belongs to was skipped or no longer exists')
            ->assertSee('2026-09-06');
        $this->assertSame(0, Topic::count());

        $this->post(route('data-requests.restore', $request))
            ->assertRedirect(route('data-requests.show', $request));
        $this->assertFlashed('success', 'Restored 1 rows, skipped 3.');

        $this->assertSame('restored', $request->fresh()->status);
        $this->assertSame(1, Topic::where('title', 'Dynamic Programming')->count());
        $this->get(route('data-requests.show', $request))->assertOk()->assertDontSee('id="restore-button"', false);
    }

    public function test_restore_requires_permission(): void
    {
        $this->topicFor($this->member);
        $request = $this->completed(['topics']);
        $this->adminWith(['data-request-view']);

        $this->get(route('data-requests.show', $request))->assertOk()->assertDontSee('id="restore-button"', false);
        $this->get(route('data-requests.restore.preview', $request))->assertForbidden();
        $this->post(route('data-requests.restore', $request))->assertForbidden();
        $this->assertSame('completed', $request->fresh()->status);
    }

    public function test_restore_action_is_hidden_and_refused_once_purged(): void
    {
        $this->topicFor($this->member);
        $request = $this->completed(['topics']);
        $request->update(['archive_purged_at' => now()]);
        $this->adminWith(['data-request-view', 'data-request-restore']);

        $this->get(route('data-requests.show', $request))
            ->assertOk()
            ->assertDontSee('id="restore-button"', false)
            ->assertSee('Purged');

        $this->post(route('data-requests.restore', $request))->assertRedirect(route('data-requests.show', $request));
        $this->assertFlashed('error', 'The archive for this request has been purged.');
        $this->assertSame(0, Topic::count());
    }

    public function test_download_streams_the_archive_and_is_logged(): void
    {
        $this->topicFor($this->member);
        $request = $this->completed(['topics']);
        $admin = $this->adminWith(['data-request-view', 'data-request-archive-download']);

        $this->get(route('data-requests.show', $request))->assertSee('id="download-archive-button"', false);

        $response = $this->get(route('data-requests.archive', $request))->assertOk();

        $this->assertStringContainsString('application/gzip', $response->headers->get('Content-Type'));
        $this->assertStringContainsString($request->reference().'-archive.json.gz', $response->headers->get('Content-Disposition'));
        $this->assertSame(
            $request->archive_checksum,
            hash('sha256', $response->streamedContent())
        );
        $this->assertTrue(ActivityLog::where('event', 'archive_downloaded')->where('causer_id', $admin->id)->exists());
    }

    public function test_download_requires_permission_and_an_archive(): void
    {
        $this->topicFor($this->member);
        $request = $this->completed(['topics']);
        $pending = DataDeletionRequest::create(['user_id' => User::factory()->create()->id, 'categories' => ['topics']]);

        $this->adminWith(['data-request-view']);
        $this->get(route('data-requests.show', $request))->assertDontSee('id="download-archive-button"', false);
        $this->get(route('data-requests.archive', $request))->assertForbidden();

        $this->adminWith(['data-request-view', 'data-request-archive-download']);
        $this->get(route('data-requests.archive', $pending))->assertNotFound();

        $request->update(['archive_purged_at' => now()]);
        $this->get(route('data-requests.archive', $request))->assertNotFound();
    }
}
