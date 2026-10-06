@if ($deletionRequest->archive_path || $deletionRequest->archive_purged_at)
    <div class="card shadow mb-4" id="archive-panel">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Archive</h6></div>
        <div class="card-body">
            <dl class="row small mb-0">
                <dt class="col-sm-3">File</dt>
                <dd class="col-sm-9"><code>{{ basename((string) $deletionRequest->archive_path) }}</code> (private disk)</dd>
                <dt class="col-sm-3">Size</dt>
                <dd class="col-sm-9">{{ number_format((int) $deletionRequest->archive_size) }} bytes</dd>
                <dt class="col-sm-3">SHA-256</dt>
                <dd class="col-sm-9"><code class="text-break">{{ $deletionRequest->archive_checksum }}</code></dd>
                <dt class="col-sm-3">Retention</dt>
                <dd class="col-sm-9">
                    @if ($deletionRequest->archive_purged_at)
                        <span class="text-danger">Purged {{ $deletionRequest->archive_purged_at->format('Y-m-d H:i') }}.</span>
                        Download and restore are no longer possible.
                    @elseif ($deletionRequest->completed_at)
                        Kept until {{ $deletionRequest->completed_at->copy()->addDays((int) config('study.data_deletion.archive_retention_days'))->format('Y-m-d') }}.
                    @endif
                </dd>
            </dl>
        </div>
    </div>
@endif
