<?php

namespace Tests\Feature\Admin;

use App\Models\DataDeletionRequest;
use App\Models\Topic;
use App\Models\User;
use App\Services\StudyTracker\DataDeletion\DataDeletionSummaryService;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Study\DataDeletion\SeedsDeletableData;

class DataDeletionRequestAdminTest extends AdminTestCase
{
    use SeedsDeletableData;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->member = User::factory()->create(['name' => 'Rahim Learner', 'email' => 'rahim@example.com']);
    }

    private function requestFor(User $user, array $categories = ['topics'], string $status = 'pending'): DataDeletionRequest
    {
        return DataDeletionRequest::create([
            'user_id' => $user->id,
            'categories' => $categories,
            'status' => $status,
            'request_counts' => app(DataDeletionSummaryService::class)->snapshot($user, $categories),
        ]);
    }

    public function test_each_page_and_action_requires_its_permission(): void
    {
        $request = $this->requestFor($this->member);
        $this->adminWith([]);

        $this->get(route('data-requests.index'))->assertForbidden();
        $this->get(route('data-requests.show', $request))->assertForbidden();
        $this->post(route('data-requests.approve', $request))->assertForbidden();
        $this->post(route('data-requests.reject', $request), ['rejection_reason' => 'No'])->assertForbidden();
        $this->post(route('data-requests.retry', $request))->assertForbidden();

        $this->assertSame('pending', $request->fresh()->status);
    }

    public function test_list_filters_by_status_and_searches_users(): void
    {
        $pending = $this->requestFor($this->member);
        $other = User::factory()->create(['name' => 'Karim Other', 'email' => 'karim@example.com']);
        $rejected = $this->requestFor($other, ['mistakes'], 'rejected');
        $this->adminWith(['data-request-list']);

        $this->get(route('data-requests.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee($pending->reference())
            ->assertDontSee($rejected->reference());

        $this->get(route('data-requests.index', ['search' => 'karim@']))
            ->assertOk()
            ->assertSee($rejected->reference())
            ->assertDontSee($pending->reference());
    }

    public function test_show_page_lists_live_counts_and_highlights_changes(): void
    {
        $this->topicFor($this->member, ['title' => 'Graph Theory Basics']);
        $request = $this->requestFor($this->member, ['topics']);
        $this->topicFor($this->member);
        $this->topicFor($this->member);
        $this->adminWith(['data-request-view', 'data-request-approve']);

        $this->get(route('data-requests.show', $request))
            ->assertOk()
            ->assertSee('Rahim Learner')
            ->assertSee('Data summary (live)')
            ->assertSee('Graph Theory Basics')
            ->assertSee('table-warning', false)
            ->assertSee('+2')
            ->assertSee('approveModal', false)
            ->assertSee('3 × Topics');
    }

    public function test_view_only_admin_sees_no_decision_buttons_and_cannot_post(): void
    {
        $this->topicFor($this->member);
        $request = $this->requestFor($this->member);
        $this->adminWith(['data-request-list', 'data-request-view']);

        $this->get(route('data-requests.show', $request))
            ->assertOk()
            ->assertSee('Data summary (live)')
            ->assertDontSee('id="approve-button"', false)
            ->assertDontSee('id="reject-button"', false);

        $this->post(route('data-requests.approve', $request))->assertForbidden();
        $this->assertSame('pending', $request->fresh()->status);
        $this->assertSame(1, Topic::count());
    }

    public function test_approve_runs_the_deletion(): void
    {
        $this->topicFor($this->member);
        $request = $this->requestFor($this->member);
        $admin = $this->adminWith(['data-request-view', 'data-request-approve']);

        $this->post(route('data-requests.approve', $request))
            ->assertRedirect(route('data-requests.show', $request));
        $this->assertFlashed('success', 'Data archived and deleted.');

        $request->refresh();
        $this->assertSame('completed', $request->status);
        $this->assertSame($admin->id, $request->reviewed_by);
        $this->assertSame(0, Topic::count());

        // Closed requests show stored counts and the archive panel.
        $this->get(route('data-requests.show', $request))
            ->assertOk()
            ->assertDontSee('Data summary (live)')
            ->assertSee('Deleted')
            ->assertSee($request->archive_checksum);
    }

    public function test_approving_twice_is_refused_with_an_error(): void
    {
        $request = $this->requestFor($this->member, ['topics'], 'completed');
        $this->adminWith(['data-request-view', 'data-request-approve']);

        $this->post(route('data-requests.approve', $request))
            ->assertRedirect(route('data-requests.show', $request));
        $this->assertFlashed('error', 'Only a pending request can be approved.');

        $this->assertSame('completed', $request->fresh()->status);
    }

    public function test_reject_requires_a_reason(): void
    {
        $this->topicFor($this->member);
        $request = $this->requestFor($this->member);
        $this->adminWith(['data-request-view', 'data-request-approve']);

        $this->from(route('data-requests.show', $request))
            ->post(route('data-requests.reject', $request), ['rejection_reason' => ''])
            ->assertSessionHasErrors('rejection_reason');
        $this->assertSame('pending', $request->fresh()->status);

        $this->post(route('data-requests.reject', $request), ['rejection_reason' => 'Duplicate request']);
        $this->assertFlashed('success', 'Request rejected. No data was deleted.');
        $this->assertSame('rejected', $request->fresh()->status);
        $this->assertSame('Duplicate request', $request->fresh()->rejection_reason);
        $this->assertSame(1, Topic::count());
    }

    public function test_failed_request_shows_retry_and_retry_completes(): void
    {
        $this->topicFor($this->member);
        $request = $this->requestFor($this->member, ['topics'], 'failed');
        $request->update(['error_message' => 'Disk full', 'failed_at' => now()]);
        $this->adminWith(['data-request-view', 'data-request-approve']);

        $this->get(route('data-requests.show', $request))
            ->assertOk()
            ->assertSee('Disk full')
            ->assertSee('id="retry-button"', false);

        $this->post(route('data-requests.retry', $request));
        $this->assertFlashed('success', 'Data archived and deleted.');

        $this->assertSame('completed', $request->fresh()->status);
        $this->assertSame(0, Topic::count());
    }
}
