@extends('layouts.app')

@section('title', 'Role — ' . $role->name)

@push('scripts')
    <script>
        window.rolesData = {
            permissions: @json($permissions)
        };
    </script>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-7">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h5 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-shield-alt mr-1"></i>{{ $role->name }}
                        </h5>
                        @can('role-list')
                            <x-button.back href="{{ route('roles.index') }}">Back to Roles</x-button.back>
                        @endcan
                    </div>

                    <div class="card-body">
                        <div class="mb-1">
                            <p class="text-xs font-weight-bold text-uppercase text-muted mb-2">
                                <i class="fas fa-key mr-1"></i>Assigned Permissions
                            </p>
                            @if ($role->permissions->count() > 0)
                                <div class="d-flex flex-wrap" style="gap:.4rem;">
                                    @foreach ($role->permissions as $p)
                                        <span class="badge badge-primary p-2">{{ $p->name }}</span>
                                    @endforeach
                                </div>
                            @else
                                <span class="badge badge-danger p-2">No Permissions Assigned</span>
                            @endif
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-between align-items-center flex-wrap" style="gap:.4rem;">
                        @can('role-edit')
                            <x-button.edit onclick="window.openEditRoleModal({{ $role->id }}); return false;" title="Edit" />
                        @endcan

                        @if ($role->name != 'Super Admin')
                            @can('role-delete')
                                <form action="{{ route('roles.destroy', $role->id) }}" method="post" onsubmit="swalConfirmationOnSubmit(event, 'Delete this role?')">
                                    @csrf @method('delete')
                                    <x-button.delete onclick="this.closest('form').requestSubmit()" title="Delete" />
                                </form>
                            @endcan
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Role Modal (Vue Component) -->
    <role-edit-modal :permissions='@json($permissions)' :update-url="'{{ route('roles.update', 0) }}'"></role-edit-modal>
@endsection
