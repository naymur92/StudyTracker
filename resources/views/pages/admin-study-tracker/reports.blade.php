@extends('layouts.app')

@section('title', 'Study Tracker Reports')

@section('content')
    <div class="container-fluid">

        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-chart-bar text-primary mr-2"></i> Study Tracker — Reports
            </h1>
            <a href="{{ route('study-tracker.overview') }}" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Overview
            </a>
        </div>

        {{-- Date Range Filter --}}
        <div class="card shadow mb-4">
            <div class="card-body py-2">
                <form method="GET" action="{{ route('study-tracker.reports') }}" class="d-flex align-items-center gap-2 flex-wrap">
                    <label class="mb-0 mr-2 font-weight-bold">Date Range:</label>
                    <input type="date" name="date_from" class="form-control form-control-sm mr-2" value="{{ $dateFrom }}" style="width:160px">
                    <span class="mr-2">to</span>
                    <input type="date" name="date_to" class="form-control form-control-sm mr-2" value="{{ $dateTo }}" style="width:160px">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-search mr-1"></i> Apply
                    </button>
                </form>
            </div>
        </div>

        <div class="row">

            {{-- Completion Trend --}}
            <div class="col-lg-8 mb-4">
                <div class="card shadow">
                    <div class="card-header py-2">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-chart-line mr-1"></i> Daily Completions ({{ $dateFrom }} → {{ $dateTo }})
                        </h6>
                    </div>
                    <div class="card-body">
                        <div style="position: relative; height: 200px;">
                            <canvas id="chartTrendDays"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Task Type Breakdown --}}
            <div class="col-lg-4 mb-4">
                <div class="card shadow">
                    <div class="card-header py-2">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-tasks mr-1"></i> Completed by Task Type
                        </h6>
                    </div>
                    <div class="card-body">
                        <div style="position: relative; height: 200px;">
                            <canvas id="chartTaskTypes"></canvas>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="row">

            {{-- Top Topics --}}
            <div class="col-lg-7 mb-4">
                <div class="card shadow">
                    <div class="card-header py-2">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-trophy mr-1"></i> Top Topics by Revision Completions
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Topic</th>
                                        <th>User</th>
                                        <th>Difficulty</th>
                                        <th class="text-right">Done</th>
                                        <th class="text-right">Total</th>
                                        <th class="text-right">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($topTopics as $i => $topic)
                                        @php
                                            $pct = $topic->total_count > 0 ? round(($topic->completed_count / $topic->total_count) * 100) : 0;
                                            $dc = ['easy' => 'success', 'medium' => 'warning', 'hard' => 'danger'];
                                        @endphp
                                        <tr>
                                            <td>{{ $i + 1 }}</td>
                                            <td>
                                                <span class="font-weight-bold">{{ $topic->title }}</span>
                                            </td>
                                            <td><small>{{ $topic->user->name ?? '—' }}</small></td>
                                            <td>
                                                @if ($topic->difficulty)
                                                    <span class="badge badge-{{ $dc[$topic->difficulty] ?? 'secondary' }}">
                                                        {{ ucfirst($topic->difficulty) }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-right text-success font-weight-bold">{{ $topic->completed_count }}</td>
                                            <td class="text-right">{{ $topic->total_count }}</td>
                                            <td class="text-right">
                                                <div class="d-flex align-items-center justify-content-end">
                                                    <div class="progress flex-fill mr-1" style="height:6px; min-width:50px;">
                                                        <div class="progress-bar bg-success" style="width:{{ $pct }}%"></div>
                                                    </div>
                                                    <small>{{ $pct }}%</small>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Practice & Active Users --}}
            <div class="col-lg-5">

                {{-- Practice Breakdown --}}
                <div class="card shadow mb-4">
                    <div class="card-header py-2">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-pen-alt mr-1"></i> Practice Log Types
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Type</th>
                                    <th class="text-right">Logs</th>
                                    <th class="text-right">Total Mins</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($practiceTypeBreakdown as $row)
                                    @php
                                        $typeLabels = \App\Models\PracticeLog::$practiceTypes;
                                    @endphp
                                    <tr>
                                        <td>{{ $typeLabels[$row->practice_type] ?? ucfirst($row->practice_type) }}</td>
                                        <td class="text-right">{{ number_format($row->count) }}</td>
                                        <td class="text-right text-muted">
                                            {{ $row->total_minutes ? number_format($row->total_minutes) . ' min' : '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Most Active Users --}}
                <div class="card shadow mb-4">
                    <div class="card-header py-2">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-fire mr-1 text-danger"></i> Most Active Users (Period)
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>User</th>
                                    <th class="text-right">Completions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($activeUsers as $i => $row)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>
                                            {{ $row->user->name ?? 'Unknown' }}
                                            <br><small class="text-muted">{{ $row->user->email ?? '' }}</small>
                                        </td>
                                        <td class="text-right font-weight-bold text-success">
                                            {{ number_format($row->completions) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">No completions in this period.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Trend chart
                const trendLabels = @json($trendDays->keys()->toArray());
                const trendData = @json($trendDays->values()->toArray());

                const ctxTrend = document.getElementById('chartTrendDays');
                if (ctxTrend) {
                    new Chart(ctxTrend, {
                        type: 'line',
                        data: {
                            labels: trendLabels.map(d => new Date(d).toLocaleDateString('en-US', {
                                month: 'short',
                                day: 'numeric'
                            })),
                            datasets: [{
                                label: 'Daily Completions',
                                data: trendData,
                                borderColor: '#1cc88a',
                                backgroundColor: 'rgba(28, 200, 138, 0.1)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.3,
                                pointRadius: 3,
                                pointHoverRadius: 5,
                                pointBackgroundColor: '#1cc88a',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: true,
                                    labels: {
                                        padding: 15
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
                }

                // Task type breakdown donut chart
                @php
                    $labels = [];
                    $data = [];
                    foreach ($taskTypeBreakdown as $row) {
                        if ($row->task_type === 'learn') {
                            $labels[] = 'Learn';
                        } elseif ($row->task_type === 'revision') {
                            $labels[] = 'Revision ' . ($row->revision_no ?? '');
                        } elseif ($row->task_type === 'practice') {
                            $labels[] = 'Practice';
                        } else {
                            $labels[] = 'Custom';
                        }
                        $data[] = $row->count;
                    }
                @endphp

                const ctxTypes = document.getElementById('chartTaskTypes');
                if (ctxTypes) {
                    new Chart(ctxTypes, {
                        type: 'doughnut',
                        data: {
                            labels: @json($labels),
                            datasets: [{
                                data: @json($data),
                                backgroundColor: ['#1cc88a', '#4e73df', '#36b9cc', '#858796', '#f6c23e'],
                                borderColor: '#fff',
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 15,
                                        usePointStyle: true
                                    }
                                }
                            }
                        }
                    });
                }
            });
        </script>
    @endpush

@endsection
