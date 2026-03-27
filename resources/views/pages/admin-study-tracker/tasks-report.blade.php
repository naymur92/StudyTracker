@extends('layouts.app')

@section('title', 'Study Tracker — Tasks Report')

@section('content')
    <div class="container-fluid">

        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-tasks text-primary mr-2"></i> Study Tracker — Tasks Report
            </h1>
            <a href="{{ route('study-tracker.overview') }}" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Overview
            </a>
        </div>

        {{-- Advanced Filter --}}
        <div class="card shadow mb-4">
            <div class="card-header py-2">
                <h6 class="m-0 font-weight-bold text-primary">Filters</h6>
            </div>
            <div class="card-body py-2">
                <form method="GET" action="{{ route('study-tracker.tasks-report') }}" class="form-inline flex-wrap gap-2">
                    <div class="form-group">
                        <select name="status" class="form-control form-control-sm" style="width: 120px">
                            <option value="">All Status</option>
                            <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="skipped" {{ $status === 'skipped' ? 'selected' : '' }}>Skipped</option>
                            <option value="missed" {{ $status === 'missed' ? 'selected' : '' }}>Missed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <select name="task_type" class="form-control form-control-sm" style="width: 120px">
                            <option value="">All Types</option>
                            <option value="learn" {{ $taskType === 'learn' ? 'selected' : '' }}>Learn</option>
                            <option value="revision" {{ $taskType === 'revision' ? 'selected' : '' }}>Revision</option>
                            <option value="practice" {{ $taskType === 'practice' ? 'selected' : '' }}>Practice</option>
                            <option value="custom" {{ $taskType === 'custom' ? 'selected' : '' }}>Custom</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <select name="user_id" class="form-control form-control-sm" style="width: 150px">
                            <option value="">All Users</option>
                            @foreach ($users as $u)
                                <option value="{{ $u->id }}" {{ $userId == $u->id ? 'selected' : '' }}>
                                    {{ $u->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <select name="topic_id" class="form-control form-control-sm" style="width: 150px">
                            <option value="">All Topics</option>
                            @foreach ($topics as $t)
                                <option value="{{ $t->id }}" {{ $topicId == $t->id ? 'selected' : '' }}>
                                    {{ Str::limit($t->title, 30) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}" style="width: 140px" placeholder="From date">
                    </div>
                    <div class="form-group">
                        <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}" style="width: 140px" placeholder="To date">
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-search mr-1"></i> Filter
                    </button>
                </form>
            </div>
        </div>

        {{-- Tasks Table --}}
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-table mr-1"></i> All Tasks ({{ $tasks->total() }} total)
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0 text-sm">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th style="width: 4%">#</th>
                                <th style="width: 12%">User</th>
                                <th style="width: 18%">Topic</th>
                                <th style="width: 8%">Type</th>
                                <th style="width: 12%">Scheduled</th>
                                <th style="width: 10%" class="text-center">Status</th>
                                <th style="width: 10%" class="text-center">Completed</th>
                                <th style="width: 8%" class="text-center">Locked</th>
                                <th style="width: 8%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($tasks as $i => $task)
                                @php
                                    $statusColor =
                                        [
                                            'pending' => 'warning',
                                            'completed' => 'success',
                                            'skipped' => 'secondary',
                                            'missed' => 'danger',
                                        ][$task->status] ?? 'secondary';

                                    $typeColor =
                                        [
                                            'learn' => 'success',
                                            'revision' => 'primary',
                                            'practice' => 'info',
                                            'custom' => 'secondary',
                                        ][$task->task_type] ?? 'secondary';

                                    $isOverdue = $task->scheduled_date < today() && !$task->is_completed;
                                @endphp
                                <tr {{ $isOverdue ? 'class="table-danger"' : '' }}>
                                    <td>{{ ($tasks->currentPage() - 1) * $tasks->perPage() + $loop->iteration }}</td>
                                    <td>
                                        <small class="font-weight-bold">{{ $task->user?->name ?? '—' }}</small><br>
                                        <small class="text-muted">{{ $task->user?->email ?? '' }}</small>
                                    </td>
                                    <td>
                                        <small class="font-weight-bold">{{ Str::limit($task->topic?->title, 25) }}</small>
                                    </td>
                                    <td>
                                        @if ($task->task_type === 'learn')
                                            <span class="badge badge-{{ $typeColor }}">Learn</span>
                                        @elseif ($task->task_type === 'revision')
                                            <span class="badge badge-{{ $typeColor }}">Rev {{ $task->revision_no }}</span>
                                        @elseif ($task->task_type === 'practice')
                                            <span class="badge badge-{{ $typeColor }}">Practice</span>
                                        @else
                                            <span class="badge badge-{{ $typeColor }}">Custom</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ $task->scheduled_date?->format('Y-m-d') }}</small>
                                        @if ($isOverdue)
                                            <br><small class="badge badge-danger">OVERDUE</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $statusColor }}">{{ ucfirst($task->status) }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if ($task->completed_at)
                                            <small>{{ $task->completed_at->format('Y-m-d H:i') }}</small>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($task->is_date_locked)
                                            <i class="fas fa-lock text-success" title="Date locked"></i>
                                        @else
                                            <i class="fas fa-unlock text-muted" title="Editable"></i>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('study-tracker.user-report', $task->user_id) }}" class="btn btn-outline-primary" title="User Report">
                                                <i class="fas fa-user"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">No tasks found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="mt-3">
            {{ $tasks->links() }}
        </div>

    </div>
@endsection
