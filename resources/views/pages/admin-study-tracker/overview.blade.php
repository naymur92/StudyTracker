@extends('layouts.app')

@section('title', 'Study Tracker Overview')

@push('styles')
    <style>
        .metric-card {
            border-left: 4px solid;
        }

        .chart-bar-wrap {
            display: flex;
            align-items: flex-end;
            gap: 4px;
            height: 60px;
        }

        .chart-bar {
            width: 100%;
            background: #4e73df;
            border-radius: 2px 2px 0 0;
            min-height: 2px;
        }

        .chart-bar-label {
            font-size: 10px;
            text-align: center;
            color: #6c757d;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">

        {{-- Header --}}
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-brain text-primary mr-2"></i> Study Tracker — Application Overview
            </h1>
            <a href="{{ route('study-tracker.reports') }}" class="btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-chart-bar mr-1"></i> View Reports
            </a>
        </div>

        {{-- Flash --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        @endif

        {{-- KPI Row 1 --}}
        <div class="row">
            <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
                <div class="card metric-card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Users</div>
                        <div class="h4 mb-0 font-weight-bold">{{ number_format($stats['total_users_with_topics']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
                <div class="card metric-card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Topics</div>
                        <div class="h4 mb-0 font-weight-bold">{{ number_format($stats['total_topics']) }}</div>
                        <small class="text-muted">{{ number_format($stats['active_topics']) }} active</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
                <div class="card metric-card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Tasks</div>
                        <div class="h4 mb-0 font-weight-bold">{{ number_format($stats['total_tasks']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
                <div class="card metric-card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Completed</div>
                        <div class="h4 mb-0 font-weight-bold">{{ number_format($stats['completed_tasks']) }}</div>
                        <small class="text-success">{{ $stats['completion_rate'] }}% rate</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
                <div class="card metric-card border-left-danger shadow h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Overdue</div>
                        <div class="h4 mb-0 font-weight-bold">{{ number_format($stats['overdue_tasks']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
                <div class="card metric-card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Practice Logs</div>
                        <div class="h4 mb-0 font-weight-bold">{{ number_format($stats['total_practice_logs']) }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- KPI Row 2 --}}
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-check-double mr-1"></i> Today's Activity
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="h3 font-weight-bold text-info">{{ $stats['tasks_scheduled_today'] }}</div>
                                <div class="text-xs text-muted text-uppercase">Scheduled Today</div>
                            </div>
                            <div class="col-4">
                                <div class="h3 font-weight-bold text-success">{{ $stats['completed_today'] }}</div>
                                <div class="text-xs text-muted text-uppercase">Completed Today</div>
                            </div>
                            <div class="col-4">
                                <div class="h3 font-weight-bold text-warning">{{ $stats['pending_tasks'] }}</div>
                                <div class="text-xs text-muted text-uppercase">Total Pending</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header py-2">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-chart-bar mr-1"></i> Completions — Last 14 Days
                        </h6>
                    </div>
                    <div class="card-body py-2">
                        <div style="position: relative; height: 200px;">
                            <canvas id="chart14Days"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Per-User Summary --}}
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-users mr-1"></i> Top Users by Topic Count
                </h6>
                <a href="{{ route('study-tracker.reports') }}" class="btn btn-sm btn-outline-primary">Full Reports</a>
            </div>
            <div class="card-body p-0">
                @if ($userSummaries->isEmpty())
                    <div class="text-center py-4 text-muted">No study data yet.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0">
                            <thead class="bg-primary text-white text-center">
                                <tr>
                                    <th style="width:5%">#</th>
                                    <th class="text-left">User</th>
                                    <th style="width:10%">Topics</th>
                                    <th style="width:10%">Active</th>
                                    <th style="width:12%">Completed Tasks</th>
                                    <th style="width:12%">Pending Tasks</th>
                                    <th style="width:10%">Overdue</th>
                                    <th style="width:10%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($userSummaries as $i => $u)
                                    <tr>
                                        <td class="text-center">{{ $i + 1 }}</td>
                                        <td>
                                            <div class="font-weight-bold">{{ $u->name }}</div>
                                            <small class="text-muted">{{ $u->email }}</small>
                                        </td>
                                        <td class="text-center">{{ $u->topics_count }}</td>
                                        <td class="text-center">{{ $u->active_topics_count }}</td>
                                        <td class="text-center text-success font-weight-bold">{{ $u->completed_tasks }}</td>
                                        <td class="text-center text-warning">{{ $u->pending_tasks }}</td>
                                        <td class="text-center {{ $u->overdue_tasks > 0 ? 'text-danger font-weight-bold' : 'text-muted' }}">
                                            {{ $u->overdue_tasks }}
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('study-tracker.user-report', $u->id) }}" class="btn btn-xs btn-info">
                                                <i class="fas fa-eye"></i> Report
                                            </a>
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

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const labels = @json($last14Days->keys()->toArray());
                const data = @json($last14Days->values()->toArray());

                const ctx = document.getElementById('chart14Days');
                if (!ctx) return;

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels.map(d => new Date(d).toLocaleDateString('en-US', {
                            month: 'short',
                            day: 'numeric'
                        })),
                        datasets: [{
                            label: 'Tasks Completed',
                            data: data,
                            backgroundColor: '#1cc88a',
                            borderColor: '#17a2b8',
                            borderWidth: 1,
                            borderRadius: 4,
                            hoverBackgroundColor: '#17a2b8'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                labels: {
                                    padding: 15,
                                    usePointStyle: true
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            });
        </script>
    @endpush

@endsection
