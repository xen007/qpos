@extends('backend.master-tailwind')

@section('title', __('Create User'))

@section('content')
    <x-backend.card>
        <form action="{{ route('backend.admin.user.create') }}" method="post" class="accountForm"
            enctype="multipart/form-data">
            @csrf

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <x-backend.input name="name" :label="__('Full Name')" :placeholder="__('Enter full name')" required />

                <x-backend.input name="email" type="email" :label="__('Login Email')" :placeholder="__('Email')"
                    required />

                <x-backend.select name="role" :label="__('Role & Permissions')" :options="$roles->pluck('name', 'id')->all()"
                    :placeholder="'-- ' . __('Select a role') . ' --'" required />

                <x-backend.input name="password" type="password" :label="__('Login password')"
                    :placeholder="__('Enter your password')" autocomplete="new-password" required />

                <div class="lg:col-span-2">
                    <x-backend.image-field name="profile_image" :label="__('Profile Image')" />
                </div>
            </div>

            <div class="mt-6">
                <button type="submit"
                    class="w-full rounded-lg bg-qpos-brand px-6 py-2 text-sm font-semibold text-white transition hover:opacity-90 lg:w-auto">
                    {{ __('Create') }}
                </button>
            </div>
        </form>
    </x-backend.card>
@endsection
