@extends('layouts.app')

@section('title', 'My Profile')

@push('styles')
    <style>
        .profile-hero {
            background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
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
            cursor: pointer;
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
            cursor: pointer;
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

        .avatar-hover-overlay {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: rgba(0, 0, 0, .35);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity .2s;
        }

        .avatar-wrap:hover .avatar-hover-overlay {
            opacity: 1;
        }

        .avatar-wrap {
            position: relative;
            display: inline-block;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-9">

                @php
                    $bgPalette = ['4e73df', 'e74a3b', 'f6c23e', '1cc88a', '36b9cc', '858796'];
                    $bg = $bgPalette[Auth::id() % count($bgPalette)];
                @endphp

                <div class="card shadow mb-4">
                    <div class="profile-hero"></div>
                    <div class="card-body pt-0">

                        {{-- Hero row --}}
                        <div class="d-flex flex-column flex-md-row align-items-md-end mb-4" style="margin-top:-50px;">
                            <div class="mr-4 mb-3 mb-md-0 flex-shrink-0">
                                <div class="avatar-wrap" data-toggle="modal" data-target="#profilePictureChangeModal" title="Change profile picture">
                                    @if (Auth::user()->profilePicture)
                                        <img class="profile-avatar" src="{{ asset('/') }}{{ Auth::user()->profilePicture->path . '/' . Auth::user()->profilePicture->name }}"
                                            alt="{{ $user->name }}">
                                    @else
                                        <div class="profile-avatar-placeholder" style="background:#{{ $bg }}">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div class="avatar-hover-overlay">
                                        <i class="fas fa-camera text-white fa-lg"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-grow-1 pb-1">
                                <h4 class="font-weight-bold mb-1 text-gray-800">{{ $user->name }}</h4>
                                <p class="mb-0 text-muted small"><i class="fas fa-envelope mr-1"></i>{{ $user->email }}</p>
                            </div>
                            <div class="d-flex align-items-center mt-3 mt-md-0" style="gap:.4rem;">
                                <x-badge-is-active :isActive="$user->is_active" />
                            </div>
                        </div>

                        <hr class="mt-0">

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
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-between align-items-center flex-wrap" style="gap:.5rem;">
                        <x-button.back href="{{ route('dashboard') }}">Dashboard</x-button.back>
                        <div class="d-flex flex-wrap" style="gap:.4rem;">
                            <a href="{{ route('user-profile.login-history') }}" class="btn btn-outline-info btn-sm">
                                <i class="fas fa-history mr-1"></i>My Login History
                            </a>
                            <x-button.edit href="{{ route('user-profile.edit') }}">Edit Profile</x-button.edit>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Profile picture change modal --}}
    <div class="modal fade" id="profilePictureChangeModal" tabindex="-1" aria-labelledby="profilePictureChangeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="profilePictureChangeModalLabel">
                        <i class="fas fa-camera mr-1"></i>Change Profile Picture
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="profile_picture_change_form" action="{{ route('user-profile.change-profile-picture') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body text-center">
                        <image-uploader
                            initial-src="{{ Auth::user()->profilePicture ? asset('/') . Auth::user()->profilePicture->path . '/' . Auth::user()->profilePicture->name : asset('/') . 'uploads/users/user.png' }}"
                            name="profile_picture" input-id="_pp" size="15vw"></image-uploader>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i>Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
