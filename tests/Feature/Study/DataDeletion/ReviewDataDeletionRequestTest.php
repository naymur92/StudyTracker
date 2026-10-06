<?php

namespace Tests\Feature\Study\DataDeletion;

use App\Models\ActivityLog;
use App\Models\DataDeletionRequest;
use App\Models\Topic;
use App\Models\User;
use App\Services\StudyTracker\DataDeletion\DataArchiveService;
use App\Services\StudyTracker\DataDeletion\DeletionPlan;
use App\Services\StudyTracker\DataDeletion\ReviewDataDeletionRequestService;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Feature\Study\StudyApiTestCase;

class ReviewDataDeletionRequestTest extends StudyApiTestCase
{
    use SeedsDeletableData;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->admin = User::factory()->create(['type' => 1]);
    }

    private function service(): ReviewDataDeletionRequestService
    {
        return app(ReviewDataDeletionRequestService::class);
    }

    private function pending(array $categories = ['topics']): DataDeletionRequest
    {
        return DataDeletionRequest::create(['user_id' => $this->user->id, 'categories' => $categories]);
    }

    public function test_approve_records_reviewer_and_runs_the_deletion(): void
    {
        $this->topicFor($this->user);
        $request = $this->pending();

        $this->service()->approve($request, $this->admin);

        $request->refresh();
        $this->assertSame(DataDeletionRequest::STATUS_COMPLETED, $request->status);
        $this->assertSame($this->admin->id, $request->reviewed_by);
        $this->assertNotNull($request->reviewed_at);
        $this->assertSame(0, Topic::count());
        $this->assertTrue(ActivityLog::where('event', 'approved')->where('causer_id', $this->admin->id)->exists());
    }

    public function test_approving_a_non_pending_request_is_refused(): void
    {
        $request = $this->pending();
        $request->update(['status' => DataDeletionRequest::STATUS_COMPLETED]);

        try {
            $this->service()->approve($request, $this->admin);
            $this->fail('Approval should have been refused.');
        } catch (RuntimeException) {
        }

        $this->assertSame(DataDeletionRequest::STATUS_COMPLETED, $request->fresh()->status);
        $this->assertNull($request->fresh()->reviewed_by);
    }

    public function test_reject_keeps_all_data(): void
    {
        $this->topicFor($this->user);
        $request = $this->pending();

        $this->service()->reject($request, $this->admin, 'Duplicate request');

        $request->refresh();
        $this->assertSame(DataDeletionRequest::STATUS_REJECTED, $request->status);
        $this->assertSame('Duplicate request', $request->rejection_reason);
        $this->assertSame(1, Topic::count());
        $this->assertTrue(ActivityLog::where('event', 'rejected')->exists());
    }

    public function test_completed_request_cannot_be_rejected(): void
    {
        $request = $this->pending();
        $request->update(['status' => DataDeletionRequest::STATUS_COMPLETED]);

        $this->expectException(RuntimeException::class);

        $this->service()->reject($request, $this->admin, 'Too late');
    }

    public function test_retry_after_failure_completes_and_keeps_previous_error_in_the_log(): void
    {
        $this->topicFor($this->user);
        $request = $this->pending();

        $this->app->bind(DataArchiveService::class, fn () => new class extends DataArchiveService
        {
            public function verify(string $path, string $checksum, DeletionPlan $plan): void
            {
                throw new RuntimeException('Disk full');
            }
        });
        $this->service()->approve($request, $this->admin);
        $this->assertSame(DataDeletionRequest::STATUS_FAILED, $request->fresh()->status);

        $this->app->bind(DataArchiveService::class, DataArchiveService::class);
        $this->service()->retry($request->fresh(), $this->admin);

        $request->refresh();
        $this->assertSame(DataDeletionRequest::STATUS_COMPLETED, $request->status);
        $this->assertNull($request->error_message);
        $this->assertSame(0, Topic::count());
        $retried = ActivityLog::where('event', 'retried')->first();
        $this->assertSame('Disk full', $retried->properties['previous_error']);
    }

    public function test_only_failed_requests_can_be_retried(): void
    {
        $this->expectException(RuntimeException::class);

        $this->service()->retry($this->pending(), $this->admin);
    }
}
