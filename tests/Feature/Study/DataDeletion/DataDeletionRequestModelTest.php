<?php

namespace Tests\Feature\Study\DataDeletion;

use App\Models\DataDeletionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataDeletionRequestModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_fills_uuid_and_pending_status(): void
    {
        $user = User::factory()->create();

        $request = DataDeletionRequest::create([
            'user_id' => $user->id,
            'categories' => ['topics'],
        ]);

        $this->assertNotEmpty($request->uuid);
        $this->assertSame(DataDeletionRequest::STATUS_PENDING, $request->status);
        $this->assertTrue($request->isOpen());
        $this->assertStringStartsWith('DDR-', $request->reference());
        $this->assertSame(1, $user->dataDeletionRequests()->open()->count());
    }

    public function test_has_archive_is_false_once_purged(): void
    {
        $request = DataDeletionRequest::create([
            'user_id' => User::factory()->create()->id,
            'categories' => ['topics'],
            'status' => DataDeletionRequest::STATUS_COMPLETED,
            'archive_path' => 'data-archives/1/x.json.gz',
        ]);

        $this->assertTrue($request->hasArchive());
        $this->assertFalse($request->isOpen());

        $request->update(['archive_purged_at' => now()]);

        $this->assertFalse($request->fresh()->hasArchive());
    }
}
