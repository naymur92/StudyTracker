@extends('layouts.app')

@section('title', 'Auth Users')

@push('styles')
    <style>
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
        }

        .user-avatar-placeholder {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }

        .badge-type {
            font-size: 0.75rem;
        }
    </style>
@endpush

@push('scripts')
@endpush

@section('content')
    <div class="container-fluid">

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h5 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-users mr-1"></i> Auth Users
                </h5>
                <div class="ms-auto">
                    @can('user-create')
                        <x-button.add-new href="{{ route('users.create') }}">Add New User</x-button.add-new>
                    @endcan
                </div>
            </div>
            <div class="card-body">
                {{-- Filter form --}}
                <form method="GET" action="{{ route('users.index') }}" class="mb-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name or email…" value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="type" class="form-control form-control-sm">
                                <option value="">All Types</option>
                                <option value="1" {{ request('type') == '1' ? 'selected' : '' }}>Super Admin</option>
                                <option value="2" {{ request('type') == '2' ? 'selected' : '' }}>Admin</option>
                                <option value="3" {{ request('type') == '3' ? 'selected' : '' }}>User</option>
                                <option value="4" {{ request('type') == '4' ? 'selected' : '' }}>API User</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-control form-control-sm">
                                <option value="">All Status</option>
                                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fas fa-search mr-1"></i>Filter
                            </button>
                            @if (request()->hasAny(['search', 'type', 'status']))
                                <a href="{{ route('users.index') }}" class="btn btn-sm btn-secondary ml-1">
                                    <i class="fas fa-times mr-1"></i>Clear
                                </a>
                            @endif
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-nowrap align-middle" width="100%" cellspacing="0">
                        <thead class="bg-primary text-white">
                            <tr class="text-center">
                                <th style="width:5%">#</th>
                                <th style="width:5%" class="no-sort">Photo</th>
                                <th style="width:22%">Name</th>
                                <th style="width:25%">Email</th>
                                <th style="width:13%">Type</th>
                                <th style="width:10%">Status</th>
                                <th style="width:20%" class="no-sort">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                @php
                                    $typeColors = [1 => 'danger', 2 => 'warning', 3 => 'info', 4 => 'secondary'];
                                    $typeColor = $typeColors[$user->type] ?? 'secondary';
                                    $initials = strtoupper(substr($user->name, 0, 1));
                                    $bgColors = ['danger', 'warning', 'info', 'success', 'primary'];
                                    $bgColor = $bgColors[$user->id % count($bgColors)];
                                @endphp
                                <tr>
                                    <td class="text-center">{{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}</td>
                                    <td class="text-center">
                                        @if ($user->profilePicture)
                                            <img class="user-avatar" src="{{ asset('/') }}{{ $user->profilePicture->path . '/' . $user->profilePicture->name }}"
                                                alt="{{ $user->name }}">
                                        @else
                                            <span class="user-avatar-placeholder bg-{{ $bgColor }} text-white">{{ $initials }}</span>
                                        @endif
                                    </td>
                                    <td class="font-weight-600">{{ $user->name }}</td>
                                    <td class="text-muted">{{ $user->email }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $typeColor }} badge-type">{{ $user->getTypeLabelAttribute() }}</span>
                                    </td>
                                    <td class="text-center">
                                        <x-badge-is-active :isActive="$user->is_active" />
                                    </td>
                                    <td>
                                        <div class="text-center d-flex justify-content-center align-items-center gap-1">
                                            @can('user-list')
                                                <x-icon.eye href="{{ route('users.show', $user->id) }}" title="View Details" />
                                            @endcan

                                            @if (auth()->user()->can('user-edit') && $user->id != 1 && $user->id != Auth::user()->id)
                                                <x-icon.pen href="{{ route('users.edit', $user->id) }}" />

                                                @if ($user->is_active == 1)
                                                    <form action="{{ route('users.change-status', $user->id) }}" method="POST"
                                                        onsubmit="swalConfirmationOnSubmit(event, 'Deactivate this user?');">
                                                        @csrf @method('put')
                                                        <input type="hidden" name="is_active" value="0">
                                                        <x-icon.times type="button" title="Mark Inactive" onclick="this.closest('form').requestSubmit()" />
                                                    </form>
                                                @else
                                                    <form action="{{ route('users.change-status', $user->id) }}" method="POST"
                                                        onsubmit="swalConfirmationOnSubmit(event, 'Activate this user?');">
                                                        @csrf @method('put')
                                                        <input type="hidden" name="is_active" value="1">
                                                        <x-icon.check type="button" title="Mark Active" onclick="this.closest('form').requestSubmit()" />
                                                    </form>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <small class="text-muted">
                        Showing {{ $users->firstItem() }}–{{ $users->lastItem() }} of {{ $users->total() }} users
                    </small>
                    {{ $users->links() }}
                </div>
            </div>
        </div>

    </div>
@endsection
