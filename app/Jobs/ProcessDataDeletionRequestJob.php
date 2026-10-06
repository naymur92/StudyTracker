<?php

namespace App\Jobs;

use App\Models\DataDeletionRequest;
use App\Services\StudyTracker\DataDeletion\DataDeletionAudit;
use App\Services\StudyTracker\DataDeletion\ExecuteDataDeletionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Runs an approved data deletion. Never retried by the worker: a retry is
 * always a deliberate admin action.
 */
class ProcessDataDeletionRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    public bool $failOnTimeout = true;

    public function __construct(private int $dataDeletionRequestId)
    {
        $this->onQueue('default');
    }

    public function handle(ExecuteDataDeletionService $service): void
    {
        $request = DataDeletionRequest::find($this->dataDeletionRequestId);

        if ($request) {
            $service->run($request);
        }
    }

    /** A timeout or crash outside the service leaves the request failed, not stuck. */
    public function failed(?Throwable $exception): void
    {
        $request = DataDeletionRequest::find($this->dataDeletionRequestId);

        if ($request && $request->status === DataDeletionRequest::STATUS_PROCESSING) {
            $request->forceFill([
                'status' => DataDeletionRequest::STATUS_FAILED,
                'failed_at' => now(),
                'error_message' => mb_substr($exception?->getMessage() ?? 'The deletion job did not finish.', 0, 2000),
            ])->save();

            DataDeletionAudit::log('failed', $request, $request->reviewer, ['error' => $request->error_message]);
        }
    }
}
