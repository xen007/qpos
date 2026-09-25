@extends('backend.master')

@section('title', __('Create User'))

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('backend.admin.user.create') }}" method="post" class="accountForm"
                enctype="multipart/form-data">
                @csrf
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="fullName" class="form-label">{{ __('Full Name') }}</label>
                            <input type="text" class="form-control" id="fullName" placeholder="{{ __('Enter full name') }}"
                                name="name" value="{{ old('name') }}" required>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="email" class="form-label">{{ __('Login Email') }}</label>
                            <input type="email" class="form-control" id="email" placeholder="{{ __('Email') }}" name="email"
                                value="{{ old('email') }}" required>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="confirmPassword" class="form-label">{{ __('Role & Permissions') }}</label>
                            <select class="custom-select" name="role" required>
                                <option value="">-- {{ __('Select a role') }} --</option>
                                @foreach ($roles as $role)
                                    <option {{ old('role') == $role->id ? 'selected' : '' }} value="{{ $role->id }}">
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="password" class="form-label">{{ __('Login password') }}</label>
                            <input type="password" class="form-control" id="password" placeholder="{{ __('Enter your password') }}"
                                name="password" autocomplete="new-password" required>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label for="thumbnail">{{ __('Profile Image') }}</label>
                            <input type="file" class="form-control" name="profile_image"
                                onchange="previewThumbnail(this)">
                            <img class="img-fluid thumbnail-preview" src="{{ nullImg() }}" alt="preview-image">
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-block bg-gradient-primary">{{ __('Create') }}</button>
            </form>
        </div>
    </div>
@endsection
