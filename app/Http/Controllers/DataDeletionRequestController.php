<?php

namespace App\Http\Controllers;

use App\Models\DataDeletionRequest;
use App\Services\StudyTracker\DataDeletion\DataArchiveService;
use App\Services\StudyTracker\DataDeletion\DataCategoryRegistry;
use App\Services\StudyTracker\DataDeletion\DataDeletionAudit;
use App\Services\StudyTracker\DataDeletion\DataDeletionSummaryService;
use App\Services\StudyTracker\DataDeletion\RestoreDataDeletionService;
use App\Services\StudyTracker\DataDeletion\ReviewDataDeletionRequestService;
use Illuminate\Http\Request;
use RuntimeException;

class DataDeletionRequestController extends Controller
{
    public function __construct(
        private DataCategoryRegistry $registry,
        private ReviewDataDeletionRequestService $review,
    ) {}

    /**
     * List data deletion requests.
     */
    public function index(Request $request)
    {
        $this->authorize('data-request-list');

        $query = DataDeletionRequest::with(['user', 'reviewer'])->latest('id');

        if ($request->filled('status') && in_array($request->status, DataDeletionRequest::STATUSES, true)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = '%'.$request->search.'%';
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', $search)->orWhere('email', 'like', $search));
        }

        $requests = $query->paginate(25)->withQueryString();
        $statusCounts = DataDeletionRequest::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $registry = $this->registry;

        return view('pages.data-deletion-requests.index', compact('requests', 'statusCounts', 'registry'));
    }

    /**
     * Show a request with its live data summary.
     */
    public function show(DataDeletionRequest $dataDeletionRequest, DataDeletionSummaryService $summary)
    {
        $this->authorize('data-request-view');

        $dataDeletionRequest->load(['user', 'reviewer', 'restorer']);

        $live = null;
        $diff = null;
        if ($dataDeletionRequest->isOpen() && $dataDeletionRequest->user) {
            $live = $summary->forCategories($dataDeletionRequest->user, $dataDeletionRequest->categories);
            $diff = $summary->diff($dataDeletionRequest->request_counts['records'] ?? [], $live['counts']);
        }

        return view('pages.data-deletion-requests.show', [
            'deletionRequest' => $dataDeletionRequest,
            'registry' => $this->registry,
            'live' => $live,
            'diff' => $diff,
        ]);
    }

    public function approve(Request $request, DataDeletionRequest $dataDeletionRequest)
    {
        $this->authorize('data-request-approve');

        return $this->decide($dataDeletionRequest, fn () => $this->review->approve($dataDeletionRequest, $request->user()),
            'Request approved. The data is being archived and deleted.');
    }

    public function reject(Request $request, DataDeletionRequest $dataDeletionRequest)
    {
        $this->authorize('data-request-approve');

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        return $this->decide($dataDeletionRequest, fn () => $this->review->reject($dataDeletionRequest, $request->user(), $validated['rejection_reason']),
            'Request rejected. No data was deleted.');
    }

    public function retry(Request $request, DataDeletionRequest $dataDeletionRequest)
    {
        $this->authorize('data-request-approve');

        return $this->decide($dataDeletionRequest, fn () => $this->review->retry($dataDeletionRequest, $request->user()),
            'Retry started.');
    }

    /**
     * Preview what restoring the archive would do.
     */
    public function restorePreview(DataDeletionRequest $dataDeletionRequest, RestoreDataDeletionService $restore)
    {
        $this->authorize('data-request-restore');

        try {
            $report = $restore->plan($dataDeletionRequest);
        } catch (RuntimeException $e) {
            return redirect()->route('data-requests.show', $dataDeletionRequest)->with('error', $e->getMessage());
        }

        return view('pages.data-deletion-requests.restore', [
            'deletionRequest' => $dataDeletionRequest->load('user'),
            'report' => $report,
            'reasons' => RestoreDataDeletionService::REASONS,
        ]);
    }

    public function restore(Request $request, DataDeletionRequest $dataDeletionRequest, RestoreDataDeletionService $restore)
    {
        $this->authorize('data-request-restore');

        try {
            $report = $restore->restore($dataDeletionRequest, $request->user());
        } catch (RuntimeException $e) {
            return redirect()->route('data-requests.show', $dataDeletionRequest)->with('error', $e->getMessage());
        }

        return redirect()->route('data-requests.show', $dataDeletionRequest)->with('success', sprintf(
            'Restored %d rows, skipped %d.', $report['totals']['restored'], $report['totals']['skipped']
        ));
    }

    /**
     * Stream the archive from the private disk.
     */
    public function downloadArchive(Request $request, DataDeletionRequest $dataDeletionRequest, DataArchiveService $archives)
    {
        $this->authorize('data-request-archive-download');

        if (! $dataDeletionRequest->hasArchive() || ! $archives->exists($dataDeletionRequest->archive_path)) {
            abort(404, 'No archive is available for this request.');
        }

        DataDeletionAudit::log('archive_downloaded', $dataDeletionRequest, $request->user());

        return $archives->disk()->download(
            $dataDeletionRequest->archive_path,
            $dataDeletionRequest->reference().'-archive.json.gz',
            ['Content-Type' => 'application/gzip'],
        );
    }

    private function decide(DataDeletionRequest $deletionRequest, callable $action, string $success)
    {
        try {
            $action();
        } catch (RuntimeException $e) {
            return redirect()->route('data-requests.show', $deletionRequest)->with('error', $e->getMessage());
        }

        $deletionRequest->refresh();
        $message = match ($deletionRequest->status) {
            DataDeletionRequest::STATUS_COMPLETED => 'Data archived and deleted.',
            DataDeletionRequest::STATUS_FAILED => 'The deletion failed: '.$deletionRequest->error_message,
            default => $success,
        };

        return redirect()->route('data-requests.show', $deletionRequest)
            ->with($deletionRequest->status === DataDeletionRequest::STATUS_FAILED ? 'error' : 'success', $message);
    }
}
