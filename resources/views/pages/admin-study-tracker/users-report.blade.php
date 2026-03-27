@extends('layouts.app')

@section('title', 'Study Tracker — Users Report')

@section('content')
    <div class="container-fluid">

        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-users text-primary mr-2"></i> Study Tracker — Users Report
            </h1>
            <a href="{{ route('study-tracker.overview') }}" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Overview
            </a>
        </div>

        {{-- Search Form --}}
        <div class="card shadow mb-4">
            <div class="card-body py-2">
                <form method="GET" action="{{ route('study-tracker.users-report') }}" class="form-inline flex-wrap gap-2">
                    <div class="form-group">
                        <input type="text" name="search" placeholder="Search by name or email" class="form-control form-control-sm" value="{{ $search }}"
                            style="width: 250px">
                    </div>
                    <div class="form-group">
                        <select name="sort" class="form-control form-control-sm" style="width: 150px">
                            <option value="topics_count" {{ $sort === 'topics_count' ? 'selected' : '' }}>Topics</option>
                            <option value="active_topics_count" {{ $sort === 'active_topics_count' ? 'selected' : '' }}>Active Topics</option>
                            <option value="name" {{ $sort === 'name' ? 'selected' : '' }}>Name</option>
                            <option value="created_at" {{ $sort === 'created_at' ? 'selected' : '' }}>Joined</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-search mr-1"></i> Apply
                    </button>
                </form>
            </div>
        </div>

        {{-- Users Table --}}
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-table mr-1"></i> All Users ({{ $users->total() }} total)
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0 text-sm">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th style="width: 5%">#</th>
                                <th style="width: 20%">User</th>
                                <th style="width: 10%" class="text-center">Topics</th>
                                <th style="width: 10%" class="text-center">Active</th>
                                <th style="width: 12%" class="text-center">Completed</th>
                                <th style="width: 12%" class="text-center">Pending</th>
                                <th style="width: 10%" class="text-center">Overdue</th>
                                <th style="width: 10%" class="text-center">Logs</th>
                                <th style="width: 11%" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $i => $user)
                                <tr>
                                    <td>{{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}</td>
                                    <td>
                                        <div class="font-weight-bold">{{ $user->name }}</div>
                                        <small class="text-muted">{{ $user->email }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-primary">{{ $user->topics_count }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-success">{{ $user->active_topics_count }}</span>
                                    </td>
                                    <td class="text-center text-success font-weight-bold">{{ $user->completed_tasks }}</td>
                                    <td class="text-center text-warning">{{ $user->pending_tasks }}</td>
                                    <td class="text-center {{ $user->overdue_tasks > 0 ? 'text-danger font-weight-bold' : 'text-muted' }}">
                                        {{ $user->overdue_tasks }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-info">{{ $user->practice_logs }}</span>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('study-tracker.user-report', $user) }}" class="btn btn-xs btn-info" title="View Report">
                                            <i class="fas fa-chart-pie"></i> Report
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">No users found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="mt-3">
            {{ $users->links() }}
        </div>

    </div>
@endsection
