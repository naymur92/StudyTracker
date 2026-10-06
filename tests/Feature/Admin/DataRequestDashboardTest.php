<?php

namespace Tests\Feature\Admin;

use App\Models\DataDeletionRequest;
use App\Models\User;

class DataRequestDashboardTest extends AdminTestCase
{
    private function pendingRequests(int $count): void
    {
        foreach (range(1, $count) as $i) {
            DataDeletionRequest::create(['user_id' => User::factory()->create()->id, 'categories' => ['topics']]);
        }
        DataDeletionRequest::create(['user_id' => User::factory()->create()->id, 'categories' => ['topics'], 'status' => 'rejected']);
    }

    public function test_permitted_admin_sees_pending_count_list_and_sidebar_badge(): void
    {
        $this->pendingRequests(6);
        $oldest = DataDeletionRequest::oldest('id')->first();
        $newest = DataDeletionRequest::status('pending')->latest('id')->first();
        $this->adminWith(['data-request-list']);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pending Data Requests')
            ->assertSeeInOrder(['id="pending-data-requests-count"', '6'], false)
            ->assertSee($oldest->reference())
            ->assertDontSee($newest->reference())
            ->assertSee('Data Requests')
            ->assertSeeInOrder(['id="pending-data-requests-badge"', '6'], false);
    }

    public function test_admin_without_permission_sees_neither(): void
    {
        $this->pendingRequests(2);
        $this->adminWith([]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Pending Data Requests')
            ->assertDontSee('pending-data-requests-badge', false)
            ->assertDontSee(route('data-requests.index'), false);
    }
}
