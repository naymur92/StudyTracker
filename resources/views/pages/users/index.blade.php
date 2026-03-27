@extends('layouts.app')

@section('title', 'Auth Users')

@push('styles')
    <link href="{{ asset('/') }}assets/vendor/datatables/dataTables.bootstrap4.css" rel="stylesheet">
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
    <script src="{{ asset('/') }}assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="{{ asset('/') }}assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="{{ asset('/') }}assets/js/demo/datatables-demo.js"></script>
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
                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-nowrap align-middle" id="dataTable" width="100%" cellspacing="0">
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
                                    <td class="text-center" data-order="{{ $loop->iteration }}">{{ $loop->iteration }}</td>
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
            </div>
        </div>

    </div>
@endsection
