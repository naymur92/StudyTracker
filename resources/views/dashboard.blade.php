@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="container-fluid">

        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
        </div>

        <!-- Stat Cards -->
        <div class="row">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Users</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['users']) }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Roles</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['roles']) }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-shield-alt fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Activity Logs</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['activity']) }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Logins Today</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['logins']) }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-sign-in-alt fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ($pendingDataRequests !== null)
            <div class="row">
                <div class="col-xl-3 col-md-6 mb-4">
                    <a href="{{ route('data-requests.index', ['status' => 'pending']) }}" class="text-decoration-none">
                        <div class="card border-left-danger shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Pending Data Requests</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="pending-data-requests-count">{{ number_format($pendingDataRequests['count']) }}</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-shield fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-xl-9 col-md-6 mb-4">
                    <div class="card shadow h-100">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Oldest pending data deletion requests</h6>
                            <a href="{{ route('data-requests.index') }}" class="small">View all</a>
                        </div>
                        <div class="card-body p-0">
                            @if ($pendingDataRequests['oldest']->isEmpty())
                                <p class="text-muted small p-3 mb-0">No requests waiting for review.</p>
                            @else
                                <ul class="list-group list-group-flush">
                                    @foreach ($pendingDataRequests['oldest'] as $dataRequest)
                                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                            <span>
                                                <a href="{{ route('data-requests.show', $dataRequest) }}"><code>{{ $dataRequest->reference() }}</code></a>
                                                <span class="small ml-2">{{ $dataRequest->user->name ?? 'Deleted user' }}</span>
                                            </span>
                                            <small class="text-muted">{{ $dataRequest->created_at->diffForHumans() }}</small>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>
@endsection
