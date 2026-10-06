<?php

namespace App\Services\StudyTracker\DataDeletion;

use App\Models\DataDeletionRequest;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Writes, verifies and reads the gzip-compressed JSON archive kept for every
 * approved deletion. Archives live on the private disk only.
 */
class DataArchiveService
{
    public const FORMAT = 'studytracker.data-archive';

    public const VERSION = 1;

    /**
     * @return array{path: string, size: int, checksum: string}
     */
    public function write(DataDeletionRequest $request, DeletionPlan $plan, User $user): array
    {
        $document = [
            'format' => self::FORMAT,
            'version' => self::VERSION,
            'request' => $request->uuid,
            'user' => ['id' => $user->id, 'email' => $user->email],
            'categories' => $plan->categories,
            'created_at' => now()->toIso8601String(),
            'tables' => array_map('array_values', $plan->tables()),
            'references' => $plan->references(),
            'user_columns' => $plan->clearsPreferences()
                ? ['study_preferences' => $plan->preferences()]
                : (object) [],
        ];

        $json = json_encode($document, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $bytes = gzencode($json, 9);

        if ($bytes === false) {
            throw new RuntimeException('Could not compress the data archive.');
        }

        $path = trim(config('study.data_deletion.archive_dir'), '/')."/{$user->id}/{$request->uuid}.json.gz";

        if (! $this->disk()->put($path, $bytes)) {
            throw new RuntimeException("Could not write the data archive to {$path}.");
        }

        return [
            'path' => $path,
            'size' => strlen($bytes),
            'checksum' => hash('sha256', $bytes),
        ];
    }

    /**
     * Reads the stored file back and checks it against the plan. Throws on
     * any mismatch, so nothing is deleted without a verified archive.
     */
    public function verify(string $path, string $checksum, DeletionPlan $plan): void
    {
        $bytes = $this->disk()->get($path);

        if ($bytes === null || ! hash_equals($checksum, hash('sha256', $bytes))) {
            throw new RuntimeException('Data archive checksum does not match.');
        }

        $document = $this->decode($bytes);
        $stored = array_map('count', $document['tables'] ?? []);
        $expected = $plan->tableCounts();
        ksort($stored);
        ksort($expected);

        if ($stored !== $expected) {
            throw new RuntimeException('Data archive row counts do not match the deletion plan.');
        }

        if (count($document['references'] ?? []) !== count($plan->references())) {
            throw new RuntimeException('Data archive references do not match the deletion plan.');
        }
    }

    /** @return array<string, mixed> */
    public function read(string $path): array
    {
        $bytes = $this->disk()->get($path);

        if ($bytes === null) {
            throw new RuntimeException("Data archive {$path} is missing.");
        }

        return $this->decode($bytes);
    }

    public function delete(string $path): void
    {
        $this->disk()->delete($path);
    }

    public function exists(string $path): bool
    {
        return $this->disk()->exists($path);
    }

    public function disk(): Filesystem
    {
        return Storage::disk(config('study.data_deletion.archive_disk'));
    }

    /** @return array<string, mixed> */
    private function decode(string $bytes): array
    {
        $json = @gzdecode($bytes);

        if ($json === false) {
            throw new RuntimeException('Data archive is not valid gzip.');
        }

        $document = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (($document['format'] ?? null) !== self::FORMAT) {
            throw new RuntimeException('File is not a StudyTracker data archive.');
        }

        return $document;
    }
}
