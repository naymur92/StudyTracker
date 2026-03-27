@extends('layouts.app')

@section('title', 'Study Tracker — Topics Report')

@section('content')
    <div class="container-fluid">

        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-book text-primary mr-2"></i> Study Tracker — Topics Report
            </h1>
            <a href="{{ route('study-tracker.overview') }}" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Overview
            </a>
        </div>

        {{-- Filter Form --}}
        <div class="card shadow mb-4">
            <div class="card-body py-2">
                <form method="GET" action="{{ route('study-tracker.topics-report') }}" class="form-inline flex-wrap gap-2">
                    <div class="form-group">
                        <input type="text" name="search" placeholder="Search title/description" class="form-control form-control-sm" value="{{ $search }}"
                            style="width: 200px">
                    </div>
                    <div class="form-group">
                        <select name="status" class="form-control form-control-sm" style="width: 120px">
                            <option value="">All Status</option>
                            <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="archived" {{ $status === 'archived' ? 'selected' : '' }}>Archived</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <select name="difficulty" class="form-control form-control-sm" style="width: 120px">
                            <option value="">All Difficulty</option>
                            <option value="easy" {{ $difficulty === 'easy' ? 'selected' : '' }}>Easy</option>
                            <option value="medium" {{ $difficulty === 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="hard" {{ $difficulty === 'hard' ? 'selected' : '' }}>Hard</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-filter mr-1"></i> Filter
                    </button>
                </form>
            </div>
        </div>

        {{-- Topics Table --}}
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-table mr-1"></i> All Topics ({{ $topics->total() }} total)
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0 text-sm">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th style="width: 5%">#</th>
                                <th style="width: 20%">Topic</th>
                                <th style="width: 15%">User</th>
                                <th style="width: 10%">Category</th>
                                <th style="width: 8%" class="text-center">Difficulty</th>
                                <th style="width: 8%" class="text-center">Total Tasks</th>
                                <th style="width: 8%" class="text-center">Completed</th>
                                <th style="width: 8%" class="text-center">Pending</th>
                                <th style="width: 8%" class="text-center">Logs</th>
                                <th style="width: 8%">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($topics as $i => $topic)
                                @php
                                    $diffColor =
                                        [
                                            'easy' => 'success',
                                            'medium' => 'warning',
                                            'hard' => 'danger',
                                        ][$topic->difficulty] ?? 'secondary';
                                    $statusColor =
                                        [
                                            'active' => 'success',
                                            'completed' => 'info',
                                            'archived' => 'secondary',
                                        ][$topic->status] ?? 'secondary';
                                @endphp
                                <tr>
                                    <td>{{ ($topics->currentPage() - 1) * $topics->perPage() + $loop->iteration }}</td>
                                    <td>
                                        <div class="font-weight-bold">{{ $topic->title }}</div>
                                        <small class="text-muted">{{ Str::limit($topic->description, 60) }}</small>
                                    </td>
                                    <td>
                                        <small>{{ $topic->user?->name ?? '—' }}</small><br>
                                        <small class="text-muted">{{ $topic->user?->email ?? '' }}</small>
                                    </td>
                                    <td>
                                        @if ($topic->category)
                                            <span class="badge badge-pill badge-light border">{{ $topic->category->name }}</span>
                                        @else
                                            <small class="text-muted">—</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $diffColor }}">{{ ucfirst($topic->difficulty) }}</span>
                                    </td>
                                    <td class="text-center font-weight-bold">{{ $topic->study_tasks_count }}</td>
                                    <td class="text-center text-success font-weight-bold">{{ $topic->completed_tasks }}</td>
                                    <td class="text-center text-warning">{{ $topic->pending_tasks }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-info">{{ $topic->practice_logs_count }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $statusColor }}">{{ ucfirst($topic->status) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">No topics found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="mt-3">
            {{ $topics->links() }}
        </div>

    </div>
@endsection
