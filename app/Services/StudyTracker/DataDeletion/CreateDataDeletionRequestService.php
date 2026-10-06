<?php

namespace App\Services\StudyTracker\DataDeletion;

use App\Models\DataDeletionRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateDataDeletionRequestService
{
    public function __construct(private DataDeletionSummaryService $summary) {}

    /** @param  list<string>  $categories */
    public function create(User $user, array $categories, ?string $reason): DataDeletionRequest
    {
        $request = DB::transaction(function () use ($user, $categories, $reason) {
            // Serialises concurrent submissions from the same user.
            User::whereKey($user->id)->lockForUpdate()->first();

            if (DataDeletionRequest::where('user_id', $user->id)->open()->exists()) {
                throw ValidationException::withMessages([
                    'categories' => 'You already have a data deletion request in progress.',
                ]);
            }

            return DataDeletionRequest::create([
                'user_id' => $user->id,
                'categories' => array_values($categories),
                'reason' => $reason !== null && trim($reason) !== '' ? trim($reason) : null,
                'status' => DataDeletionRequest::STATUS_PENDING,
                'request_counts' => $this->summary->snapshot($user, array_values($categories)),
            ]);
        });

        DataDeletionAudit::log('created', $request, $user, [
            'counts' => $request->request_counts['records'] ?? [],
        ]);

        return $request;
    }
}
