@extends('layouts.app')

@section('title', 'User Report — ' . $user->name)

@section('content')
    <div class="container-fluid">

        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-user-graduate text-primary mr-2"></i>
                Study Report: <strong>{{ $user->name }}</strong>
            </h1>
            <a href="{{ route('study-tracker.overview') }}" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Overview
            </a>
        </div>

        {{-- User KPIs --}}
        <div class="row mb-4">
            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body py-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Topics</div>
                        <div class="h4 mb-0 font-weight-bold">{{ $topics->count() }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body py-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Completed Tasks</div>
                        <div class="h4 mb-0 font-weight-bold">{{ $taskStats['completed'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body py-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending</div>
                        <div class="h4 mb-0 font-weight-bold">{{ $taskStats['pending'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="card border-left-danger shadow h-100 py-2">
                    <div class="card-body py-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Overdue</div>
                        <div class="h4 mb-0 font-weight-bold">{{ $taskStats['overdue'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="card border-left-secondary shadow h-100 py-2">
                    <div class="card-body py-2">
                        <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Skipped</div>
                        <div class="h4 mb-0 font-weight-bold">{{ $taskStats['skipped'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body py-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Completion Rate</div>
                        <div class="h4 mb-0 font-weight-bold">{{ $taskStats['completion_rate'] }}%</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">

            {{-- Topic List --}}
            <div class="col-lg-7 mb-4">
                <div class="card shadow">
                    <div class="card-header py-2">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-book-open mr-1"></i> Topics ({{ $topics->count() }})
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        @if ($topics->isEmpty())
                            <div class="text-center py-4 text-muted">No topics added yet.</div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Title</th>
                                            <th>Category</th>
                                            <th>Difficulty</th>
                                            <th>Status</th>
                                            <th class="text-right">Tasks</th>
                                            <th class="text-right">Done</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($topics as $i => $topic)
                                            @php
                                                $dc = ['easy' => 'success', 'medium' => 'warning', 'hard' => 'danger'];
                                                $sc = ['active' => 'success', 'completed' => 'primary', 'archived' => 'secondary'];
                                                $total = $topic->studyTasks()->count();
                                                $done = $topic->studyTasks()->where('status', 'completed')->count();
                                            @endphp
                                            <tr>
                                                <td>{{ $i + 1 }}</td>
                                                <td class="font-weight-bold">{{ $topic->title }}</td>
                                                <td>
                                                    @if ($topic->category)
                                                        <span class="badge" style="background:{{ $topic->category->color ?? '#6c757d' }}; color:#fff;">
                                                            {{ $topic->category->name }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($topic->difficulty)
                                                        <span class="badge badge-{{ $dc[$topic->difficulty] ?? 'secondary' }}">
                                                            {{ ucfirst($topic->difficulty) }}
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge badge-{{ $sc[$topic->status] ?? 'secondary' }}">
                                                        {{ ucfirst($topic->status) }}
                                                    </span>
                                                </td>
                                                <td class="text-right">{{ $total }}</td>
                                                <td class="text-right text-success font-weight-bold">{{ $done }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Right Column --}}
            <div class="col-lg-5">

                {{-- Upcoming Tasks --}}
                <div class="card shadow mb-4">
                    <div class="card-header py-2">
                        <h6 class="m-0 font-weight-bold text-info">
                            <i class="fas fa-calendar-check mr-1"></i> Upcoming Pending Tasks
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        @if ($upcomingTasks->isEmpty())
                            <div class="text-center py-3 text-muted">No upcoming tasks.</div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Task</th>
                                            <th>Type</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($upcomingTasks as $task)
                                            <tr>
                                                <td class="text-nowrap">
                                                    {{ $task->scheduled_date->format('d M') }}
                                                </td>
                                                <td>{{ $task->topic->title ?? '—' }}</td>
                                                <td>
                                                    @if ($task->task_type === 'learn')
                                                        <span class="badge badge-success">Learn</span>
                                                    @elseif ($task->task_type === 'revision')
                                                        <span class="badge badge-primary">Rev {{ $task->revision_no }}</span>
                                                    @else
                                                        <span class="badge badge-secondary">{{ ucfirst($task->task_type) }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Recent Practice Logs --}}
                <div class="card shadow mb-4">
                    <div class="card-header py-2">
                        <h6 class="m-0 font-weight-bold text-warning">
                            <i class="fas fa-pen-alt mr-1"></i> Recent Practice Logs
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        @if ($recentPracticeLogs->isEmpty())
                            <div class="text-center py-3 text-muted">No practice logs.</div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Topic</th>
                                            <th>Type</th>
                                            <th>Duration</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($recentPracticeLogs as $log)
                                            <tr>
                                                <td class="text-nowrap">{{ $log->practiced_on->format('d M') }}</td>
                                                <td>{{ $log->topic->title ?? '—' }}</td>
                                                <td>
                                                    <small class="badge badge-light border">
                                                        {{ \App\Models\PracticeLog::$practiceTypes[$log->practice_type] ?? $log->practice_type }}
                                                    </small>
                                                </td>
                                                <td class="text-muted">
                                                    {{ $log->duration_minutes ? $log->duration_minutes . 'm' : '—' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>

    </div>
@endsection
