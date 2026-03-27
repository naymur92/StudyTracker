@extends('layouts.app')

@section('title', 'Activity Log #' . $activityLog->id)

@push('styles')
    <style>
        .info-label {
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #858796;
            font-weight: 600;
            margin-bottom: 3px;
        }

        .info-value {
            font-size: .9rem;
            color: #3a3b45;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h5 class="m-0 text-primary">
                    <i class="fas fa-clipboard-list mr-1"></i>Activity Log <span class="text-muted font-weight-normal">#{{ $activityLog->id }}</span>
                </h5>
                <x-button.back href="{{ route('activity-logs.index') }}">Back</x-button.back>
            </div>
            <div class="card-body">
                <div class="row">
                    {{-- Left column --}}
                    <div class="col-md-6">
                        <div class="row">
                            <div class="col-6 mb-4">
                                <div class="info-label"><i class="fas fa-tag mr-1"></i>Log Name</div>
                                <div class="info-value">{{ $activityLog->log_name ?? '—' }}</div>
                            </div>
                            <div class="col-6 mb-4">
                                <div class="info-label"><i class="fas fa-bolt mr-1"></i>Event</div>
                                <div class="info-value">
                                    @if ($activityLog->event)
                                        <span class="badge badge-info">{{ strtoupper($activityLog->event) }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-12 mb-4">
                                <div class="info-label"><i class="fas fa-align-left mr-1"></i>Description</div>
                                <div class="info-value">{{ $activityLog->description }}</div>
                            </div>
                            <div class="col-6 mb-4">
                                <div class="info-label"><i class="fas fa-user mr-1"></i>Causer</div>
                                <div class="info-value">
                                    @if ($activityLog->causer)
                                        {{ $activityLog->causer->name }}
                                        <small class="text-muted d-block">{{ class_basename($activityLog->causer_type) }}</small>
                                    @else
                                        <span class="text-muted">System</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-6 mb-4">
                                <div class="info-label"><i class="fas fa-crosshairs mr-1"></i>Subject</div>
                                <div class="info-value">
                                    @if ($activityLog->subject)
                                        {{ class_basename($activityLog->subject_type) }} #{{ $activityLog->subject_id }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Right column --}}
                    <div class="col-md-6">
                        <div class="row">
                            <div class="col-6 mb-4">
                                <div class="info-label"><i class="fas fa-network-wired mr-1"></i>IP Address</div>
                                <div class="info-value">{{ $activityLog->ip_address ?? '—' }}</div>
                            </div>
                            <div class="col-6 mb-4">
                                <div class="info-label"><i class="fas fa-clock mr-1"></i>Date/Time</div>
                                <div class="info-value">
                                    {{ $activityLog->created_at->format('Y-m-d H:i:s') }}
                                    <small class="text-muted d-block">{{ $activityLog->created_at->diffForHumans() }}</small>
                                </div>
                            </div>
                            <div class="col-12 mb-4">
                                <div class="info-label"><i class="fas fa-globe mr-1"></i>User Agent</div>
                                <div class="info-value"><small class="text-muted">{{ $activityLog->user_agent ?? '—' }}</small></div>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($activityLog->properties && $activityLog->properties->count())
                    <hr>
                    <div>
                        <p class="info-label mb-2"><i class="fas fa-code mr-1"></i>Properties</p>
                        <div class="card bg-light border-0">
                            <div class="card-body p-3">
                                <pre class="mb-0" style="font-size:.8rem;">{{ json_encode($activityLog->properties, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
