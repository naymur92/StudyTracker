{{-- Archive download and restore --}}
@if ($deletionRequest->hasArchive())
    <hr class="mt-3">
    <p class="small text-muted mb-2">The archive is kept until it is purged; restoring puts the rows back with their original IDs.</p>
    @can('data-request-archive-download')
        <a href="{{ route('data-requests.archive', $deletionRequest) }}" class="btn btn-outline-secondary btn-sm mr-1" id="download-archive-button">
            <i class="fas fa-download mr-1"></i>Download archive
        </a>
    @endcan
    @if ($status === 'completed')
        @can('data-request-restore')
            <a href="{{ route('data-requests.restore.preview', $deletionRequest) }}" class="btn btn-outline-primary btn-sm" id="restore-button">
                <i class="fas fa-undo mr-1"></i>Restore…
            </a>
        @endcan
    @endif
@elseif ($deletionRequest->archive_purged_at)
    <hr class="mt-3">
    <p class="small text-muted mb-0">The archive was purged {{ $deletionRequest->archive_purged_at->format('Y-m-d') }}; it can no longer be downloaded or restored.</p>
@elseif (in_array($status, ['pending', 'approved', 'processing', 'failed', 'rejected'], true))
    <hr class="mt-3">
    <p class="small text-muted mb-0">No archive exists for this request{{ $status === 'rejected' ? ' because it was rejected' : ' yet' }}.</p>
@endif
