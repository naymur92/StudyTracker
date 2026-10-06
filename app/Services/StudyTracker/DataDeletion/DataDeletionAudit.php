<?php

namespace App\Services\StudyTracker\DataDeletion;

use App\Models\DataDeletionRequest;
use App\Models\User;
use App\Services\ActivityLogger;

/**
 * Activity-log entries for the data deletion workflow. The causer is always
 * explicit because the deletion job runs without an authenticated user.
 * Properties hold categories and counts, never row contents.
 */
class DataDeletionAudit
{
    public const LOG_NAME = 'data_deletion';

    private const DESCRIPTIONS = [
        'created' => 'Requested data deletion',
        'approved' => 'Approved data deletion request',
        'rejected' => 'Rejected data deletion request',
        'retried' => 'Retried data deletion request',
        'completed' => 'Completed data deletion',
        'failed' => 'Data deletion failed',
        'restored' => 'Restored data from deletion archive',
        'archive_downloaded' => 'Downloaded data deletion archive',
        'archive_purged' => 'Purged data deletion archive',
    ];

    public static function log(string $event, DataDeletionRequest $request, ?User $causer, array $properties = []): void
    {
        ActivityLogger::log(
            description: (self::DESCRIPTIONS[$event] ?? $event).' '.$request->reference(),
            subject: $request,
            causer: $causer,
            properties: ['reference' => $request->reference(), 'categories' => $request->categories] + $properties,
            event: $event,
            logName: self::LOG_NAME,
        );
    }
}
