@extends('layouts.app')

@section('title', 'Data Request ' . $deletionRequest->reference())

@section('content')
    @php
        $user = $deletionRequest->user;
        $status = $deletionRequest->status;
        $canDecide = auth()->user()->can('data-request-approve');
        $requestRecords = $deletionRequest->request_counts['records'] ?? [];
    @endphp

    <div class="container-fluid">
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                Data request <code>{{ $deletionRequest->reference() }}</code>
                @include('pages.data-deletion-requests.partials.status-badge', ['request' => $deletionRequest])
            </h1>
            <a href="{{ route('data-requests.index') }}" class="btn btn-sm btn-secondary"><i class="fas fa-arrow-left mr-1"></i>All requests</a>
        </div>

        @if ($status === 'rejected' && $deletionRequest->rejection_reason)
            <div class="alert alert-secondary"><strong>Rejected:</strong> {{ $deletionRequest->rejection_reason }}</div>
        @endif
        @if ($deletionRequest->error_message)
            <div class="alert alert-danger"><strong>Last error:</strong> {{ $deletionRequest->error_message }}</div>
        @endif
        @if ($status === 'approved')
            <div class="alert alert-info">Approved and waiting for the queue worker. Nothing is deleted until the job runs.</div>
        @elseif ($status === 'processing')
            <div class="alert alert-info">Archiving and deleting now (started {{ $deletionRequest->processing_started_at?->diffForHumans() }}).</div>
        @endif

        <div class="row">
            {{-- Request details --}}
            <div class="col-lg-7 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Request</h6></div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">User</dt>
                            <dd class="col-sm-8">
                                @if ($user)
                                    {{ $user->name }} &lt;{{ $user->email }}&gt;
                                    <span class="badge badge-light border">{{ $user->type_label }}</span>
                                    @if ((int) $user->type === 3)
                                        <a href="{{ route('study-tracker.user-report', $user) }}" class="ml-1 small">Study report</a>
                                    @endif
                                @else
                                    <span class="text-muted">Deleted user</span>
                                @endif
                            </dd>

                            <dt class="col-sm-4">Categories</dt>
                            <dd class="col-sm-8">
                                <ul class="pl-3 mb-0">
                                    @foreach ($deletionRequest->categories as $key)
                                        <li>
                                            <strong>{{ $registry->has($key) ? $registry->label($key) : $key }}</strong>
                                            @if ($registry->has($key))
                                                <span class="text-muted small">— {{ $registry->definition($key)['description'] }}</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </dd>

                            <dt class="col-sm-4">Reason</dt>
                            <dd class="col-sm-8">{{ $deletionRequest->reason ?: '—' }}</dd>

                            <dt class="col-sm-4">Timeline</dt>
                            <dd class="col-sm-8">
                                <ul class="list-unstyled small mb-0">
                                    <li><i class="fas fa-paper-plane text-gray-400 mr-1"></i>Requested {{ $deletionRequest->created_at->format('Y-m-d H:i') }}</li>
                                    @if ($deletionRequest->reviewed_at)
                                        <li><i class="fas fa-user-check text-gray-400 mr-1"></i>Reviewed {{ $deletionRequest->reviewed_at->format('Y-m-d H:i') }} by {{ $deletionRequest->reviewer->name ?? 'unknown' }}</li>
                                    @endif
                                    @if ($deletionRequest->processing_started_at)
                                        <li><i class="fas fa-cog text-gray-400 mr-1"></i>Processing started {{ $deletionRequest->processing_started_at->format('Y-m-d H:i') }}</li>
                                    @endif
                                    @if ($deletionRequest->failed_at)
                                        <li><i class="fas fa-exclamation-triangle text-danger mr-1"></i>Failed {{ $deletionRequest->failed_at->format('Y-m-d H:i') }}</li>
                                    @endif
                                    @if ($deletionRequest->completed_at)
                                        <li><i class="fas fa-check text-success mr-1"></i>Completed {{ $deletionRequest->completed_at->format('Y-m-d H:i') }}</li>
                                    @endif
                                    @if ($deletionRequest->restored_at)
                                        <li><i class="fas fa-undo text-primary mr-1"></i>Restored {{ $deletionRequest->restored_at->format('Y-m-d H:i') }} by {{ $deletionRequest->restorer->name ?? 'unknown' }}</li>
                                    @endif
                                </ul>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="col-lg-5 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Actions</h6></div>
                    <div class="card-body">
                        @if ($canDecide && $status === 'pending')
                            <p class="small text-muted">Approving archives the data below, verifies the archive, then deletes it.</p>
                            <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#approveModal" id="approve-button">
                                <i class="fas fa-check mr-1"></i>Approve &amp; delete
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm ml-1" data-toggle="modal" data-target="#rejectModal" id="reject-button">
                                <i class="fas fa-times mr-1"></i>Reject
                            </button>
                        @elseif ($canDecide && $status === 'failed')
                            <form method="POST" action="{{ route('data-requests.retry', $deletionRequest) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-warning btn-sm" id="retry-button"><i class="fas fa-redo mr-1"></i>Retry deletion</button>
                            </form>
                            <button type="button" class="btn btn-outline-secondary btn-sm ml-1" data-toggle="modal" data-target="#rejectModal" id="reject-button">
                                <i class="fas fa-times mr-1"></i>Reject
                            </button>
                        @elseif (! $canDecide && in_array($status, ['pending', 'failed'], true))
                            <p class="text-muted small mb-0">You can view this request but not approve or reject it.</p>
                        @else
                            <p class="text-muted small mb-0">No review action is needed.</p>
                        @endif

                        @include('pages.data-deletion-requests.partials.archive-actions')
                    </div>
                </div>
            </div>
        </div>

        {{-- Data summary --}}
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    {{ $live ? 'Data summary (live)' : 'Data summary' }}
                </h6>
            </div>
            <div class="card-body">
                @if ($live)
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered" id="live-summary">
                            <thead class="thead-light">
                                <tr>
                                    <th>Record</th>
                                    <th class="text-right">At request</th>
                                    <th class="text-right">Now</th>
                                    <th class="text-right">Change</th>
                                    <th>Earliest</th>
                                    <th>Latest</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $liveRecords = collect($live['records'])->keyBy('key'); @endphp
                                @forelse ($diff as $key => $row)
                                    <tr class="{{ $row['change'] !== 0 ? 'table-warning' : '' }}">
                                        <td>{{ $row['label'] }}</td>
                                        <td class="text-right">{{ $row['before'] }}</td>
                                        <td class="text-right font-weight-bold">{{ $row['now'] }}</td>
                                        <td class="text-right">
                                            @if ($row['change'] !== 0)
                                                <span class="badge badge-warning" title="Changed since the request">{{ $row['change'] > 0 ? '+' : '' }}{{ $row['change'] }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>{{ $liveRecords[$key]['earliest'] ?? '—' }}</td>
                                        <td>{{ $liveRecords[$key]['latest'] ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted">Nothing to delete for these categories.</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Total rows</th>
                                    <th class="text-right">{{ array_sum($requestRecords) }}</th>
                                    <th class="text-right">{{ $live['total'] }}</th>
                                    <th colspan="3"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    @if ($live['resets_preferences'])
                        <p class="small mb-3"><i class="fas fa-sliders-h mr-1 text-gray-500"></i>The user's study preferences will be reset to the defaults.</p>
                    @endif

                    <div class="row">
                        @foreach ($live['samples'] as $sample)
                            <div class="col-md-4 mb-3">
                                <h6 class="small font-weight-bold text-uppercase text-gray-600">{{ $sample['label'] }} ({{ $sample['total'] }})</h6>
                                <ul class="small pl-3 mb-0">
                                    @foreach ($sample['titles'] as $title)
                                        <li>{{ $title }}</li>
                                    @endforeach
                                    @if ($sample['total'] > count($sample['titles']))
                                        <li class="text-muted list-unstyled">…and {{ $sample['total'] - count($sample['titles']) }} more</li>
                                    @endif
                                </ul>
                            </div>
                        @endforeach
                    </div>

                    @if ($live['references'])
                        <h6 class="small font-weight-bold text-uppercase text-gray-600 mt-2">Kept rows that lose a link</h6>
                        <ul class="small pl-3 mb-0">
                            @foreach ($live['references'] as $reference)
                                <li>{{ $reference['count'] }} × {{ $reference['label'] }} — <code>{{ $reference['column'] }}</code> cleared</li>
                            @endforeach
                        </ul>
                    @endif
                @else
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="small font-weight-bold text-uppercase text-gray-600">At request</h6>
                            @include('pages.data-deletion-requests.partials.count-table', ['counts' => $requestRecords])
                        </div>
                        @if ($deletionRequest->deleted_counts !== null)
                            <div class="col-md-6">
                                <h6 class="small font-weight-bold text-uppercase text-gray-600">Deleted</h6>
                                @include('pages.data-deletion-requests.partials.count-table', ['counts' => $deletionRequest->deleted_counts])
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        @include('pages.data-deletion-requests.partials.archive-panel')
    </div>

    {{-- Approve modal --}}
    @if ($canDecide && $status === 'pending')
        <div class="modal fade" id="approveModal" tabindex="-1" role="dialog" aria-labelledby="approveModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <form method="POST" action="{{ route('data-requests.approve', $deletionRequest) }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="approveModalLabel">Approve and delete?</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p>This archives and then permanently deletes the following data of <strong>{{ $user->name ?? 'this user' }}</strong>:</p>
                        <ul class="small">
                            @foreach ($live['records'] ?? [] as $record)
                                <li>{{ $record['count'] }} × {{ $record['label'] }}</li>
                            @endforeach
                        </ul>
                        <p class="small text-muted mb-0">
                            Categories: {{ collect($deletionRequest->categories)->map(fn ($k) => $registry->has($k) ? $registry->label($k) : $k)->implode(', ') }}.
                            The archive can be restored until it is purged.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger btn-sm">Yes, archive and delete</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Reject modal --}}
    @if ($canDecide && in_array($status, ['pending', 'failed'], true))
        <div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <form method="POST" action="{{ route('data-requests.reject', $deletionRequest) }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="rejectModalLabel">Reject request</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <label for="rejection_reason" class="small">Reason (shown to the user)</label>
                        <textarea name="rejection_reason" id="rejection_reason" rows="3" maxlength="1000" class="form-control" required>{{ old('rejection_reason') }}</textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm">Reject request</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
