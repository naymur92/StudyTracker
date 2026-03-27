@extends('layouts.app')

@section('title', 'Role Management')

@push('styles')
@endpush

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
            <div class="col-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h5 class="m-0 font-weight-bold text-primary">Roles List</h5>
                        @can('role-create')
                            <x-button.add-new onclick="window.openCreateRoleModal(); return false;">Create New Role</x-button.add-new>
                        @endcan
                    </div>
                    <div class="card-body">
                        {{-- Filter form --}}
                        <form method="GET" action="{{ route('roles.index') }}" class="mb-3">
                            <div class="row g-2 align-items-end">
                                <div class="col">
                                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search role name…" value="{{ request('search') }}">
                                </div>
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="fas fa-search mr-1"></i>Filter
                                    </button>
                                    @if (request()->filled('search'))
                                        <a href="{{ route('roles.index') }}" class="btn btn-sm btn-secondary ml-1">
                                            <i class="fas fa-times mr-1"></i>Clear
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-sm text-center" width="100%">
                                <thead class="text-white bg-primary">
                                    <tr>
                                        <th style="width: 70px;">SL No</th>
                                        <th>Name</th>
                                        <th style="width: 150px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($roles as $item)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td class="font-weight-500">{{ $item->name }}</td>
                                            <td>
                                                <div class="d-flex justify-content-center gap-2">
                                                    @can('role-list')
                                                        <x-icon.eye href="{{ route('roles.show', $item->id) }}" title="View" />
                                                    @endcan
                                                    @can('role-edit')
                                                        <x-icon.pen onclick="window.openEditRoleModal({{ $item->id }}); return false;" data-toggle="tooltip" data-placement="top"
                                                            title="Edit"></x-icon.pen>
                                                    @endcan
                                                    @if ($item->name != 'Super Admin')
                                                        @can('role-delete')
                                                            <x-icon.trash onclick="confirmDelete({{ $item->id }}, '{{ $item->name }}')" title="Delete" />
                                                        @endcan
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="py-4 text-muted">No roles found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <small class="text-muted">
                                Showing {{ $roles->firstItem() }}–{{ $roles->lastItem() }} of {{ $roles->total() }} roles
                            </small>
                            {{ $roles->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Form (hidden) -->
    <form id="deleteForm" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>

    <!-- Add Role Modal (Vue Component) -->
    <role-create-modal :permissions='@json($permissions)' :create-url="'{{ route('roles.store') }}'"></role-create-modal>

    <!-- Edit Role Modal (Vue Component) -->
    <role-edit-modal :permissions='@json($permissions)' :update-url="'{{ route('roles.update', 0) }}'"></role-edit-modal>
@endsection

@push('scripts')
    <script>
        function confirmDelete(roleId, roleName) {
            swalConfirmation(`Are you sure you want to delete the role "${roleName}"?`).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('deleteForm');
                    form.action = `/admin/roles/${roleId}`;
                    form.submit();
                }
            });
        }
    </script>
@endpush
