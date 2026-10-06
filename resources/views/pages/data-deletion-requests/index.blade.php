@extends('layouts.app')

@section('title', 'Data Requests')

@section('content')
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h5 class="m-0 text-primary">Data Deletion Requests</h5>
                <small class="text-muted">
                    Pending: <strong>{{ $statusCounts['pending'] ?? 0 }}</strong>
                    · Failed: <strong>{{ $statusCounts['failed'] ?? 0 }}</strong>
                </small>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('data-requests.index') }}" class="mb-3">
                    <div class="row align-items-end">
                        <div class="col-md-4 mb-2">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="User name or email..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3 mb-2">
                            <select name="status" class="form-control form-control-sm">
                                <option value="">All statuses</option>
                                @foreach (\App\Models\DataDeletionRequest::STATUS_LABELS as $value => $label)
                                    <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>
                                        {{ $label }} ({{ $statusCounts[$value] ?? 0 }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto mb-2">
                            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search mr-1"></i>Filter</button>
                            @if (request()->hasAny(['search', 'status']))
                                <a href="{{ route('data-requests.index') }}" class="btn btn-sm btn-secondary ml-1"><i class="fas fa-times mr-1"></i>Clear</a>
                            @endif
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>User</th>
                                <th>Categories</th>
                                <th>Status</th>
                                <th>Requested</th>
                                <th>Reviewer</th>
                                <th style="width: 6%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($requests as $item)
                                <tr>
                                    <td><code>{{ $item->reference() }}</code></td>
                                    <td>
                                        @if ($item->user)
                                            {{ $item->user->name }}<br><small class="text-muted">{{ $item->user->email }}</small>
                                        @else
                                            <span class="text-muted">Deleted user</span>
                                        @endif
                                    </td>
                                    <td>
                                        @foreach ($item->categories as $key)
                                            <span class="badge badge-light border">{{ $registry->has($key) ? $registry->label($key) : $key }}</span>
                                        @endforeach
                                    </td>
                                    <td>@include('pages.data-deletion-requests.partials.status-badge', ['request' => $item])</td>
                                    <td>{{ $item->created_at->format('Y-m-d H:i') }}</td>
                                    <td>{{ $item->reviewer->name ?? '—' }}</td>
                                    <td><x-icon.eye :href="route('data-requests.show', $item)" /></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">No data deletion requests found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <small class="text-muted">
                        Showing {{ $requests->firstItem() ?? 0 }}–{{ $requests->lastItem() ?? 0 }} of {{ $requests->total() }} requests
                    </small>
                    {{ $requests->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
