@extends('layouts.app')

@section('title', 'Login History #' . $loginHistory->id)

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
                    <i class="fas fa-history mr-1"></i>Login History <span class="text-muted font-weight-normal">#{{ $loginHistory->id }}</span>
                    @if ($loginHistory->is_successful)
                        <span class="badge badge-success ml-1">Successful</span>
                    @else
                        <span class="badge badge-danger ml-1">Failed</span>
                    @endif
                </h5>
                <x-button.back href="{{ route('login-history.index') }}">Back</x-button.back>
            </div>
            <div class="card-body">
                <div class="row">
                    {{-- Left --}}
                    <div class="col-md-6">
                        <div class="row">
                            <div class="col-6 mb-4">
                                <div class="info-label"><i class="fas fa-user mr-1"></i>User</div>
                                <div class="info-value">
                                    {{ $loginHistory->user->name ?? '—' }}
                                    @if ($loginHistory->user)
                                        <small class="text-muted d-block">{{ $loginHistory->user->email }}</small>
                                    @endif
                                </div>
                            </div>
                            <div class="col-6 mb-4">
                                <div class="info-label"><i class="fas fa-sign-in-alt mr-1"></i>Login Method</div>
                                <div class="info-value">
                                    <span class="badge badge-info">{{ $loginHistory->method_label }}</span>
                                </div>
                            </div>
                            <div class="col-6 mb-4">
                                <div class="info-label"><i class="fas fa-network-wired mr-1"></i>IP Address</div>
                                <div class="info-value">{{ $loginHistory->ip_address }}</div>
                            </div>
                            <div class="col-6 mb-4">
                                <div class="info-label"><i class="fas fa-map-marker-alt mr-1"></i>Location</div>
                                <div class="info-value">{{ $loginHistory->location ?? 'Unknown' }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Right --}}
                    <div class="col-md-6">
                        <div class="row">
                            <div class="col-6 mb-4">
                                <div class="info-label"><i class="fas fa-tv mr-1"></i>Device</div>
                                <div class="info-value">{{ $loginHistory->device ?? 'Unknown' }}</div>
                            </div>
                            <div class="col-6 mb-4">
                                <div class="info-label"><i class="fas fa-globe mr-1"></i>Browser</div>
                                <div class="info-value">{{ $loginHistory->browser ?? 'Unknown' }}</div>
                            </div>
                            <div class="col-6 mb-4">
                                <div class="info-label"><i class="fas fa-desktop mr-1"></i>Platform</div>
                                <div class="info-value">{{ $loginHistory->platform ?? 'Unknown' }}</div>
                            </div>
                            <div class="col-6 mb-4">
                                <div class="info-label"><i class="fas fa-clock mr-1"></i>Login At</div>
                                <div class="info-value">
                                    {{ $loginHistory->login_at->format('Y-m-d H:i:s') }}
                                    <small class="text-muted d-block">{{ $loginHistory->login_at->diffForHumans() }}</small>
                                </div>
                            </div>
                            <div class="col-6 mb-4">
                                <div class="info-label"><i class="fas fa-sign-out-alt mr-1"></i>Logout At</div>
                                <div class="info-value">
                                    @if ($loginHistory->logout_at)
                                        {{ $loginHistory->logout_at->format('Y-m-d H:i:s') }}
                                    @else
                                        <span class="badge badge-success">Still Active</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-6 mb-4">
                                <div class="info-label"><i class="fas fa-hourglass-half mr-1"></i>Session Duration</div>
                                <div class="info-value">
                                    @if ($loginHistory->logout_at)
                                        {{ $loginHistory->login_at->diffForHumans($loginHistory->logout_at, true) }}
                                    @else
                                        {{ $loginHistory->login_at->diffForHumans() }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($loginHistory->user_agent)
                    <hr>
                    <div>
                        <p class="info-label mb-2"><i class="fas fa-code mr-1"></i>User Agent</p>
                        <div class="card bg-light border-0">
                            <div class="card-body p-3">
                                <small class="text-muted">{{ $loginHistory->user_agent }}</small>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
