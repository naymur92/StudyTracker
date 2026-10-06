<?php

namespace Tests\Feature\Study\DataDeletion;

use App\Models\ActivityLog;
use App\Models\DataDeletionRequest;
use App\Models\User;
use App\Services\IdHasher;
use Tests\Feature\Study\StudyApiTestCase;

class DataDeletionApiTest extends StudyApiTestCase
{
    use SeedsDeletableData;

    private function submit(array $payload)
    {
        return $this->postJson('/api/study/data-deletion/requests', $payload + ['confirmation' => 'DELETE']);
    }

    public function test_summary_reports_counts_per_category(): void
    {
        foreach (range(1, 3) as $i) {
            $this->topicFor($this->user);
        }
        $this->mistakeFor($this->user);

        $response = $this->getJson('/api/study/data-deletion/summary')->assertOk();

        $categories = collect($response->json('data.categories'))->keyBy('key');
        $this->assertCount(8, $categories);
        $this->assertSame(3, $categories['topics']['total']);
        $this->assertSame('Topics', $categories['topics']['records'][0]['label']);
        $this->assertSame(1, $categories['mistakes']['total']);
        $this->assertNotEmpty($categories['categories']['notes']);
    }

    public function test_create_returns_pending_request_with_counts(): void
    {
        $topic = $this->topicFor($this->user);
        $this->practiceLogFor($topic);
        $this->snapshotFor($this->user, '2026-09-01');

        $response = $this->submit(['categories' => ['practice_logs', 'review_history'], 'reason' => 'Starting over'])
            ->assertCreated()
            ->assertJsonPath('flag', true)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.reason', 'Starting over')
            ->assertJsonPath('data.categories.0.key', 'practice_logs')
            ->assertJsonPath('data.categories.1.label', 'Review-load history');

        $counts = collect($response->json('data.request_counts'))->pluck('count', 'key')->all();
        $this->assertSame(['practice_logs' => 1, 'review_load_snapshots' => 1], $counts);

        $request = DataDeletionRequest::firstOrFail();
        $this->assertSame(IdHasher::encode($request->id), $response->json('data.id'));
        $this->assertSame(['practice_logs' => 1], $request->request_counts['categories']['practice_logs']);
        $this->assertStringNotContainsString('archive', $response->getContent());
    }

    public function test_validation_errors(): void
    {
        $this->submit(['categories' => ['topics', 'passwords']])
            ->assertStatus(422)
            ->assertJsonValidationErrors('categories.1');

        $this->submit(['categories' => ['topics', 'topics']])
            ->assertStatus(422)
            ->assertJsonValidationErrors('categories.0');

        $this->submit(['categories' => []])
            ->assertStatus(422)
            ->assertJsonValidationErrors('categories');

        $this->postJson('/api/study/data-deletion/requests', ['categories' => ['topics'], 'confirmation' => 'delete me'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('confirmation');

        $this->submit(['categories' => ['topics'], 'reason' => str_repeat('a', 1001)])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reason');

        $this->assertSame(0, DataDeletionRequest::count());
    }

    public function test_only_one_open_request_at_a_time(): void
    {
        $this->submit(['categories' => ['topics']])->assertCreated();

        $this->submit(['categories' => ['mistakes']])
            ->assertStatus(422)
            ->assertJsonValidationErrors('categories');

        foreach ([DataDeletionRequest::STATUS_APPROVED, DataDeletionRequest::STATUS_PROCESSING, DataDeletionRequest::STATUS_FAILED] as $status) {
            DataDeletionRequest::query()->update(['status' => $status]);
            $this->submit(['categories' => ['mistakes']])->assertStatus(422);
        }

        DataDeletionRequest::query()->update(['status' => DataDeletionRequest::STATUS_REJECTED]);
        $this->submit(['categories' => ['mistakes']])->assertCreated();
        $this->assertSame(2, DataDeletionRequest::count());
    }

    public function test_creating_a_request_is_logged(): void
    {
        $this->submit(['categories' => ['topics']])->assertCreated();

        $log = ActivityLog::where('log_name', 'data_deletion')->where('event', 'created')->first();
        $this->assertNotNull($log);
        $this->assertSame($this->user->id, (int) $log->causer_id);
        $this->assertSame(['topics'], $log->properties['categories']);
    }

    public function test_list_is_newest_first_and_only_own_requests(): void
    {
        $first = DataDeletionRequest::create(['user_id' => $this->user->id, 'categories' => ['topics'], 'status' => 'rejected']);
        $second = DataDeletionRequest::create(['user_id' => $this->user->id, 'categories' => ['mistakes']]);
        DataDeletionRequest::create(['user_id' => User::factory()->create()->id, 'categories' => ['topics']]);

        $ids = collect($this->getJson('/api/study/data-deletion/requests')->assertOk()->json('data'))->pluck('id')->all();

        $this->assertSame([IdHasher::encode($second->id), IdHasher::encode($first->id)], $ids);
    }

    public function test_show_returns_rejection_reason_and_hides_other_users_requests(): void
    {
        $mine = DataDeletionRequest::create([
            'user_id' => $this->user->id,
            'categories' => ['topics'],
            'status' => 'rejected',
            'rejection_reason' => 'Please export your notes first',
            'archive_path' => 'data-archives/1/secret.json.gz',
            'archive_checksum' => str_repeat('a', 64),
        ]);
        $theirs = DataDeletionRequest::create(['user_id' => User::factory()->create()->id, 'categories' => ['topics']]);

        $response = $this->getJson('/api/study/data-deletion/requests/'.IdHasher::encode($mine->id))
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.rejection_reason', 'Please export your notes first');
        $this->assertStringNotContainsString('secret.json.gz', $response->getContent());
        $this->assertStringNotContainsString('archive_path', $response->getContent());
        $this->assertStringNotContainsString(str_repeat('a', 64), $response->getContent());

        $this->getJson('/api/study/data-deletion/requests/'.IdHasher::encode($theirs->id))->assertNotFound();
    }

    public function test_demo_user_can_read_summary_but_not_create(): void
    {
        $this->actingAsDemo();

        $this->getJson('/api/study/data-deletion/summary')->assertOk();
        $this->submit(['categories' => ['topics']])->assertForbidden();

        $this->assertSame(0, DataDeletionRequest::count());
    }
}
