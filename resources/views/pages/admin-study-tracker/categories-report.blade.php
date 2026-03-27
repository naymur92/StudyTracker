@extends('layouts.app')

@section('title', 'Study Tracker — Categories Report')

@section('content')
    <div class="container-fluid">

        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-folder text-primary mr-2"></i> Study Tracker — Categories Report
            </h1>
            <a href="{{ route('study-tracker.overview') }}" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Overview
            </a>
        </div>

        {{-- Filter --}}
        <div class="card shadow mb-4">
            <div class="card-body py-2">
                <form method="GET" action="{{ route('study-tracker.categories-report') }}" class="form-inline flex-wrap gap-2">
                    <div class="form-group">
                        <select name="type" class="form-control form-control-sm">
                            <option value="all" {{ $type === 'all' ? 'selected' : '' }}>All Categories</option>
                            <option value="system" {{ $type === 'system' ? 'selected' : '' }}>System Categories</option>
                            <option value="user" {{ $type === 'user' ? 'selected' : '' }}>User Categories</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-filter mr-1"></i> Filter
                    </button>
                </form>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Categories</div>
                        <div class="h4 mb-0 font-weight-bold">{{ $categories->total() }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Topics</div>
                        <div class="h4 mb-0 font-weight-bold">{{ $categories->sum('topics_count') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Tasks</div>
                        <div class="h4 mb-0 font-weight-bold">{{ array_sum(array_map(fn($c) => $c->total_tasks, $categories->items())) }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Categories Table --}}
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-table mr-1"></i> Categories List
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th style="width: 5%">#</th>
                                <th style="width: 30%">Category Name</th>
                                <th style="width: 15%">Type</th>
                                <th style="width: 12%" class="text-center">Topics</th>
                                <th style="width: 12%" class="text-center">Active</th>
                                <th style="width: 12%" class="text-center">Total Tasks</th>
                                <th style="width: 12%" class="text-center">Completed</th>
                                <th style="width: 2%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($categories as $i => $cat)
                                <tr>
                                    <td>{{ ($categories->currentPage() - 1) * $categories->perPage() + $loop->iteration }}</td>
                                    <td>
                                        <div class="font-weight-bold">{{ $cat->name }}</div>
                                        @if ($cat->description)
                                            <small class="text-muted">{{ Str::limit($cat->description, 80) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if (is_null($cat->user_id))
                                            <span class="badge badge-primary">System</span>
                                        @else
                                            <span class="badge badge-info">User</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-pill badge-light border">{{ $cat->topics_count }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-success">{{ $cat->active_topics }}</span>
                                    </td>
                                    <td class="text-center font-weight-bold">{{ $cat->total_tasks }}</td>
                                    <td class="text-center text-success font-weight-bold">{{ $cat->completed_tasks }}</td>
                                    <td class="text-center">
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar bg-success"
                                                style="width: {{ $cat->total_tasks > 0 ? round(($cat->completed_tasks / $cat->total_tasks) * 100) : 0 }}%"></div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No categories found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="mt-3">
            {{ $categories->links() }}
        </div>

    </div>
@endsection
