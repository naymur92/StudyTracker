<?php

namespace Tests\Feature\Study\DataDeletion;

use App\Models\DataDeletionRequest;
use App\Services\StudyTracker\DataDeletion\DataArchiveService;
use App\Services\StudyTracker\DataDeletion\DataCollector;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Feature\Study\StudyApiTestCase;

class DataArchiveTest extends StudyApiTestCase
{
    use SeedsDeletableData;

    private DataArchiveService $archives;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->archives = app(DataArchiveService::class);
    }

    private function requestFor(array $categories): DataDeletionRequest
    {
        return DataDeletionRequest::create(['user_id' => $this->user->id, 'categories' => $categories]);
    }

    public function test_round_trip_keeps_raw_values(): void
    {
        $topic = $this->topicFor($this->user, ['recall_questions' => [['question' => 'Q?', 'answer' => 'A']]]);
        $topic->delete(); // soft-deleted rows are archived with deleted_at
        $request = $this->requestFor(['topics']);
        $plan = app(DataCollector::class)->collect($this->user, ['topics']);

        $written = $this->archives->write($request, $plan, $this->user);

        $this->assertSame("data-archives/{$this->user->id}/{$request->uuid}.json.gz", $written['path']);
        Storage::disk('local')->assertExists($written['path']);
        $bytes = Storage::disk('local')->get($written['path']);
        $this->assertSame(hash('sha256', $bytes), $written['checksum']);
        $this->assertSame(strlen($bytes), $written['size']);

        $this->archives->verify($written['path'], $written['checksum'], $plan);

        $document = $this->archives->read($written['path']);
        $this->assertSame('studytracker.data-archive', $document['format']);
        $this->assertSame(1, $document['version']);
        $this->assertSame($request->uuid, $document['request']);
        $this->assertSame($this->user->email, $document['user']['email']);

        $row = $document['tables']['topics'][0];
        $original = $plan->tables()['topics'][$topic->id];
        $this->assertSame($original, $row);
        $this->assertNotNull($row['deleted_at']);
        $this->assertIsString($row['recall_questions']);
        $this->assertEquals([['question' => 'Q?', 'answer' => 'A']], json_decode($row['recall_questions'], true));
    }

    public function test_study_settings_archive_keeps_previous_preferences(): void
    {
        $this->user->forceFill(['study_preferences' => ['review_budget_minutes' => 40]])->save();
        $plan = app(DataCollector::class)->collect($this->user, ['study_settings']);

        $written = $this->archives->write($this->requestFor(['study_settings']), $plan, $this->user);

        $document = $this->archives->read($written['path']);
        $this->assertSame(['review_budget_minutes' => 40], json_decode($document['user_columns']['study_preferences'], true));
    }

    public function test_tampered_byte_fails_verification(): void
    {
        $this->topicFor($this->user);
        $plan = app(DataCollector::class)->collect($this->user, ['topics']);
        $written = $this->archives->write($this->requestFor(['topics']), $plan, $this->user);

        $bytes = Storage::disk('local')->get($written['path']);
        $bytes[20] = $bytes[20] === 'a' ? 'b' : 'a';
        Storage::disk('local')->put($written['path'], $bytes);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('checksum');

        $this->archives->verify($written['path'], $written['checksum'], $plan);
    }

    public function test_row_count_mismatch_fails_verification(): void
    {
        $this->topicFor($this->user);
        $plan = app(DataCollector::class)->collect($this->user, ['topics']);
        $written = $this->archives->write($this->requestFor(['topics']), $plan, $this->user);

        $this->topicFor($this->user);
        $biggerPlan = app(DataCollector::class)->collect($this->user, ['topics']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('row counts');

        $this->archives->verify($written['path'], $written['checksum'], $biggerPlan);
    }
}
