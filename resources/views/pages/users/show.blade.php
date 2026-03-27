@extends('layouts.app')

@section('title', 'User — ' . $user->name)

@push('styles')
    <style>
        .profile-hero {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            min-height: 80px;
            border-radius: .35rem .35rem 0 0;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .15);
        }

        .profile-avatar-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 700;
            color: #fff;
            border: 4px solid #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .15);
        }

        .info-label {
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #858796;
            font-weight: 600;
            margin-bottom: 3px;
        }

        .info-value {
            font-size: .95rem;
            color: #3a3b45;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-9">

                @php
                    $typeColors = [1 => 'danger', 2 => 'warning', 3 => 'info', 4 => 'secondary'];
                    $typeColor = $typeColors[$user->type] ?? 'secondary';
                    $bgPalette = ['4e73df', 'e74a3b', 'f6c23e', '1cc88a', '36b9cc', '858796'];
                    $bg = $bgPalette[$user->id % count($bgPalette)];
                @endphp

                <div class="card shadow mb-4">
                    <div class="profile-hero"></div>
                    <div class="card-body pt-0">

                        {{-- Hero row --}}
                        <div class="d-flex flex-column flex-md-row align-items-md-end mb-4" style="margin-top:-50px;">
                            <div class="mr-4 mb-3 mb-md-0 flex-shrink-0">
                                @if ($user->profilePicture)
                                    <img class="profile-avatar" src="{{ asset('/') }}{{ $user->profilePicture->path . '/' . $user->profilePicture->name }}" alt="{{ $user->name }}">
                                @else
                                    <div class="profile-avatar-placeholder" style="background:#{{ $bg }}">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                @endif
                            </div>
                            <div class="flex-grow-1 pb-1">
                                <h4 class="font-weight-bold mb-1 text-gray-800">{{ $user->name }}</h4>
                                <p class="mb-0 text-muted small"><i class="fas fa-envelope mr-1"></i>{{ $user->email }}</p>
                            </div>
                            <div class="d-flex align-items-center flex-wrap mt-3 mt-md-0" style="gap:.4rem;">
                                <span class="badge badge-{{ $typeColor }} p-2" style="font-size:.78rem;">
                                    <i class="fas fa-user-tag mr-1"></i>{{ $user->getTypeLabelAttribute() }}
                                </span>
                                <x-badge-is-active :isActive="$user->is_active" />
                            </div>
                        </div>

                        <hr class="mt-0">

                        {{-- Info grid --}}
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="info-label"><i class="fas fa-shield-alt mr-1"></i>Roles</div>
                                <div class="info-value">
                                    @forelse ($user->getRoleNames() as $role)
                                        <span class="badge badge-success mr-1 mb-1">{{ $role }}</span>
                                    @empty
                                        <span class="text-muted small">No roles assigned</span>
                                    @endforelse
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="info-label"><i class="fas fa-key mr-1"></i>Permissions</div>
                                <div class="info-value">
                                    @php $perms = $user->getAllPermissions(); @endphp
                                    @if ($perms->count() > 0)
                                        @foreach ($perms as $p)
                                            <span class="badge badge-primary mr-1 mb-1">{{ $p->name }}</span>
                                        @endforeach
                                    @else
                                        <span class="badge badge-danger">No Permissions</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-3 mb-2">
                                <div class="info-label"><i class="fas fa-user-plus mr-1"></i>Created By</div>
                                <div class="info-value">{{ $user->createdBy->name ?? '—' }}</div>
                            </div>
                            <div class="col-md-3 mb-2">
                                <div class="info-label"><i class="fas fa-user-edit mr-1"></i>Updated By</div>
                                <div class="info-value">{{ $user->updatedBy->name ?? '—' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-between align-items-center flex-wrap" style="gap:.5rem;">
                        @can('user-list')
                            <x-button.back href="{{ route('users.index') }}">Back to Users</x-button.back>
                        @endcan

                        @if (auth()->user()->can('user-edit') && $user->id != Auth::user()->id && $user->id != 1)
                            <div class="d-flex flex-wrap align-items-center" style="gap:.4rem;">
                                <x-button.edit href="{{ route('users.edit', $user->id) }}" />

                                @if ($user->is_active == 1)
                                    <x-button.mark-inactive :action="route('users.change-status', $user->id)" />
                                @else
                                    <x-button.mark-active :action="route('users.change-status', $user->id)" />
                                @endif

                                <x-button.reset-password :href="route('users.change-password', $user->id)" />
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
