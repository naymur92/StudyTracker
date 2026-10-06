<?php

namespace App\Services\StudyTracker\DataDeletion;

use App\Jobs\ProcessDataDeletionRequestJob;
use App\Models\DataDeletionRequest;
use App\Models\User;
use RuntimeException;

/**
 * Admin decisions on a data deletion request. Every transition is a
 * conditional update, so double clicks and concurrent admins cannot apply
 * the same decision twice.
 */
class ReviewDataDeletionRequestService
{
    public function approve(DataDeletionRequest $request, User $admin): void
    {
        $this->transition($request, [DataDeletionRequest::STATUS_PENDING], [
            'status' => DataDeletionRequest::STATUS_APPROVED,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ], 'Only a pending request can be approved.');

        DataDeletionAudit::log('approved', $request->refresh(), $admin, [
            'counts' => $request->request_counts['records'] ?? [],
        ]);

        ProcessDataDeletionRequestJob::dispatch($request->id);
    }

    public function reject(DataDeletionRequest $request, User $admin, string $reason): void
    {
        $this->transition($request, [DataDeletionRequest::STATUS_PENDING, DataDeletionRequest::STATUS_FAILED], [
            'status' => DataDeletionRequest::STATUS_REJECTED,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ], 'Only a pending or failed request can be rejected.');

        DataDeletionAudit::log('rejected', $request->refresh(), $admin, ['reason' => $reason]);
    }

    public function retry(DataDeletionRequest $request, User $admin): void
    {
        $previousError = $request->error_message;

        $this->transition($request, [DataDeletionRequest::STATUS_FAILED], [
            'status' => DataDeletionRequest::STATUS_APPROVED,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ], 'Only a failed request can be retried.');

        DataDeletionAudit::log('retried', $request->refresh(), $admin, ['previous_error' => $previousError]);

        ProcessDataDeletionRequestJob::dispatch($request->id);
    }

    /** @param  list<string>  $from */
    private function transition(DataDeletionRequest $request, array $from, array $changes, string $error): void
    {
        $updated = DataDeletionRequest::whereKey($request->id)
            ->whereIn('status', $from)
            ->update($changes + ['updated_at' => now()]);

        if ($updated !== 1) {
            throw new RuntimeException($error);
        }
    }
}
