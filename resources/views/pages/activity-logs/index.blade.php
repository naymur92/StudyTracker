@extends('layouts.app')

@section('title', 'Activity Logs')

@push('styles')
@endpush

@push('scripts')
@endpush

@section('content')
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h5 class="m-0 text-primary">Activity Logs</h5>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <form method="GET" action="{{ route('activity-logs.index') }}" class="mb-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search description..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="log_name" class="form-control form-control-sm">
                                <option value="">All Log Types</option>
                                @foreach ($logNames as $name)
                                    <option value="{{ $name }}" {{ request('log_name') == $name ? 'selected' : '' }}>{{ ucfirst($name) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="event" class="form-control form-control-sm">
                                <option value="">All Events</option>
                                @foreach ($events as $event)
                                    <option value="{{ $event }}" {{ request('event') == $event ? 'selected' : '' }}>{{ strtoupper($event) }}</option>
                                @endforeach
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
                            @if (request()->hasAny(['search', 'log_name', 'event', 'start_date', 'end_date']))
                                <a href="{{ route('activity-logs.index') }}" class="btn btn-sm btn-secondary ml-1">
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
                                <th style="width: 30%">Description</th>
                                <th style="width: 10%">Event</th>
                                <th style="width: 10%">IP Address</th>
                                <th style="width: 15%">Date/Time</th>
                                <th style="width: 15%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                                <tr>
                                    <td>{{ $log->id }}</td>
                                    <td>
                                        @if ($log->causer)
                                            {{ $log->causer->name ?? 'N/A' }}
                                        @else
                                            <span class="text-muted">System</span>
                                        @endif
                                    </td>
                                    <td>{{ $log->description }}</td>
                                    <td>
                                        @if ($log->event)
                                            <span class="badge badge-info">{{ strtoupper($log->event) }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $log->ip_address }}</td>
                                    <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                    <td>
                                        <x-icon.eye :href="route('activity-logs.show', $log)" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">No activity logs found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <small class="text-muted">
                        Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }} entries
                    </small>
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
