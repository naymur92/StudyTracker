@extends('layouts.app')

@section('title', 'Permission Management')


@push('styles')
@endpush

@push('scripts')
@endpush


@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h5 class="m-0 font-weight-bold text-primary">Permissions List</h5>
                        @can('permission-create')
                            <x-button.add-new onclick="window.openPermissionModal(); return false;">Create New Permission</x-button.add-new>
                        @endcan
                    </div>
                    <div class="card-body">
                        {{-- Filter form --}}
                        <form method="GET" action="{{ route('permissions.index') }}" class="mb-3">
                            <div class="row g-2 align-items-end">
                                <div class="col">
                                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search permission name…" value="{{ request('search') }}">
                                </div>
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="fas fa-search mr-1"></i>Filter
                                    </button>
                                    @if (request()->filled('search'))
                                        <a href="{{ route('permissions.index') }}" class="btn btn-sm btn-secondary ml-1">
                                            <i class="fas fa-times mr-1"></i>Clear
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped text-center" width="100%" cellspacing="0">
                                <thead class="text-white bg-primary">
                                    <tr>
                                        <th style="width: 70px;">SL No</th>
                                        <th>Permission Name</th>
                                        <th style="width: 150px;">Action</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($permissions as $item)
                                        <tr>
                                            <td>{{ ($permissions->currentPage() - 1) * $permissions->perPage() + $loop->iteration }}</td>
                                            <td>{{ $item->name }}</td>
                                            <td>
                                                @if ($item->id > 8)
                                                    @can('permission-delete')
                                                        <x-icon.trash onclick="confirmDelete({{ $item->id }}, '{{ $item->name }}')" title="Delete" />
                                                    @endcan
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <small class="text-muted">
                                Showing {{ $permissions->firstItem() }}–{{ $permissions->lastItem() }} of {{ $permissions->total() }} permissions
                            </small>
                            {{ $permissions->links() }}
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

    <!-- Vue Permission Create Modal -->
    <permission-create-modal :create-url="'{{ route('permissions.store') }}'"></permission-create-modal>
@endsection

@push('scripts')
    <script>
        function confirmDelete(permissionId, permissionName) {
            swalConfirmation(`Are you sure you want to delete the permission "${permissionName}"?`).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('deleteForm');
                    form.action = `/admin/permissions/${permissionId}`;
                    form.submit();
                }
            });
        }
    </script>
@endpush
