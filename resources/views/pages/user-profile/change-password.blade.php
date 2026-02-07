@extends('layouts.app')

@section('title', 'User Profile | Password Change')

@section('content')
    <div class="container-fluid">

        <!-- Page Heading -->
        {{-- <h1 class="h3 mb-2 text-gray-800">Change Password</h1> --}}

        <div class="row justify-content-center">
            <div class="col-12 col-xl-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h5 class="m-0 text-primary">Password Change</h5>
                        <div class="ms-auto">
                            <div class="btn-list">
                                <x-button.back href="{{ route('dashboard') }}"></x-button.back>
                            </div>
                        </div>
                    </div>
                    <form action="{{ route('user-profile.update-password') }}" method="POST" onsubmit="swalConfirmationOnSubmit(event, 'Are you sure?');">
                        @csrf
                        @method('put')

                        <div class="card-body">
                            <div class="row">

                                {{-- password --}}
                                <div class="form-group col-12 col-lg-6 mb-3">
                                    <x-form.input type="password" id="_pass" name="password" label="Enter New Password" required placeholder="Password" />
                                    <x-password-strength-indicator inputId="_pass" />
                                </div>

                                {{-- confirm password --}}
                                <div class="form-group col-12 col-lg-6 mb-3">
                                    <x-form.input type="password" id="_pass_conf" name="password_confirmation" label="Enter Password Again" required placeholder="Retype Password" />
                                </div>

                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-end">
                            <x-button.submit>Update Password</x-button.submit>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
@endsection
