@extends('layouts.app')

@section('title', 'Login History')

@push('styles')
@endpush

@push('scripts')
@endpush

@section('content')
    <div class="container-fluid">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Logins</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['total']) }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-sign-in-alt fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Successful</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['successful']) }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-left-danger shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Failed</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['failed']) }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Today</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['today']) }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h5 class="m-0 text-primary">Login History</h5>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <form method="GET" action="{{ route('login-history.index') }}" class="mb-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search IP address..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="is_successful" class="form-control form-control-sm">
                                <option value="">All Status</option>
                                <option value="1" {{ request('is_successful') === '1' ? 'selected' : '' }}>Successful</option>
                                <option value="0" {{ request('is_successful') === '0' ? 'selected' : '' }}>Failed</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="login_method" class="form-control form-control-sm">
                                <option value="">All Methods</option>
                                <option value="web" {{ request('login_method') == 'web' ? 'selected' : '' }}>Web</option>
                                <option value="api" {{ request('login_method') == 'api' ? 'selected' : '' }}>API</option>
                                <option value="oauth" {{ request('login_method') == 'oauth' ? 'selected' : '' }}>OAuth Token</option>
                                <option value="oauth_refresh" {{ request('login_method') == 'oauth_refresh' ? 'selected' : '' }}>OAuth Refresh</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fas fa-search mr-1"></i>Filter
                            </button>
                            @if (request()->hasAny(['search', 'is_successful', 'login_method', 'start_date', 'end_date']))
                                <a href="{{ route('login-history.index') }}" class="btn btn-sm btn-secondary ml-1">
                                    <i class="fas fa-times mr-1"></i>Clear
                                </a>
                            @endif
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th style="width: 5%">ID</th>
                                <th style="width: 15%">User</th>
                                <th style="width: 10%">IP Address</th>
                                <th style="width: 10%">Device</th>
                                <th style="width: 10%">Browser</th>
                                <th style="width: 10%">Status</th>
                                <th style="width: 10%">Method</th>
                                <th style="width: 15%">Login At</th>
                                <th style="width: 15%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logins as $login)
                                <tr>
                                    <td>{{ $login->id }}</td>
                                    <td>{{ $login->user->name ?? 'N/A' }}</td>
                                    <td>{{ $login->ip_address }}</td>
                                    <td>{{ $login->device ?? 'Unknown' }}</td>
                                    <td>{{ $login->browser ?? 'Unknown' }}</td>
                                    <td>
                                        @if ($login->is_successful)
                                            <span class="badge badge-success">Success</span>
                                        @else
                                            <span class="badge badge-danger">Failed</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-info">{{ $login->method_label }}</span>
                                    </td>
                                    <td>{{ $login->login_at->format('Y-m-d H:i:s') }}</td>
                                    <td>
                                        <x-icon.eye :href="route('login-history.show', $login)" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center">No login history found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <small class="text-muted">
                        Showing {{ $logins->firstItem() }}–{{ $logins->lastItem() }} of {{ $logins->total() }} entries
                    </small>
                    {{ $logins->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
