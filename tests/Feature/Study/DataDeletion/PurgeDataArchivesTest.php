<?php

namespace Tests\Feature\Study\DataDeletion;

use App\Models\DataDeletionRequest;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Study\StudyApiTestCase;

class PurgeDataArchivesTest extends StudyApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function completedDaysAgo(int $days, string $status = 'completed'): DataDeletionRequest
    {
        $request = DataDeletionRequest::create([
            'user_id' => $this->user->id,
            'categories' => ['topics'],
            'status' => $status,
            'completed_at' => now()->subDays($days),
        ]);
        $path = "data-archives/{$this->user->id}/{$request->uuid}.json.gz";
        Storage::disk('local')->put($path, gzencode('{}'));
        $request->update(['archive_path' => $path]);

        return $request;
    }

    public function test_expired_archives_are_purged_and_records_kept(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 6)->setTime(0, 10));
        $old = $this->completedDaysAgo(91);
        $restoredOld = $this->completedDaysAgo(95, 'restored');
        $recent = $this->completedDaysAgo(10);

        $this->artisan('data-requests:purge-archives')
            ->expectsOutputToContain('Purged 2 archive(s) older than 90 days.')
            ->assertSuccessful();

        Storage::disk('local')->assertMissing($old->archive_path);
        Storage::disk('local')->assertMissing($restoredOld->archive_path);
        Storage::disk('local')->assertExists($recent->archive_path);
        $this->assertNotNull($old->fresh()->archive_purged_at);
        $this->assertFalse($old->fresh()->hasArchive());
        $this->assertSame('completed', $old->fresh()->status);
        $this->assertNull($recent->fresh()->archive_purged_at);
        $this->assertSame(3, DataDeletionRequest::count());
    }

    public function test_dry_run_deletes_nothing(): void
    {
        $old = $this->completedDaysAgo(120);

        $this->artisan('data-requests:purge-archives', ['--dry-run' => true])
            ->expectsOutputToContain('Would purge '.$old->reference())
            ->assertSuccessful();

        Storage::disk('local')->assertExists($old->archive_path);
        $this->assertNull($old->fresh()->archive_purged_at);
    }

    public function test_missing_file_is_still_marked_purged(): void
    {
        $old = $this->completedDaysAgo(100);
        Storage::disk('local')->delete($old->archive_path);

        $this->artisan('data-requests:purge-archives')->assertSuccessful();

        $this->assertNotNull($old->fresh()->archive_purged_at);
    }

    public function test_retention_follows_config(): void
    {
        config(['study.data_deletion.archive_retention_days' => 7]);
        $request = $this->completedDaysAgo(10);

        $this->artisan('data-requests:purge-archives')->assertSuccessful();

        $this->assertNotNull($request->fresh()->archive_purged_at);
    }
}
