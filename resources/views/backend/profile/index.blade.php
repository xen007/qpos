@extends('backend.master-tailwind')

@section('title', __('Profile'))

@section('content')
    <x-backend.card>
        <form action="{{ route('backend.admin.profile.update') }}" method="post" class="accountForm"
            enctype="multipart/form-data">
            @csrf

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <div>
                    <label for="fullName" class="block text-sm font-medium text-qpos-ink">{{ __('Full Name') }}</label>
                    <input type="text" id="fullName" name="name" value="{{ $user->name }}"
                        placeholder="{{ __('Enter full name') }}"
                        class="mt-1 w-full rounded-lg border border-qpos-line bg-qpos-surface px-3 py-2 text-sm text-qpos-ink placeholder:text-qpos-muted focus:border-qpos-brand focus:outline-none">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-qpos-ink">{{ __('Email') }}</label>
                    <input type="email" id="email" name="email" value="{{ $user->email }}"
                        placeholder="{{ __('Email') }}"
                        class="mt-1 w-full rounded-lg border border-qpos-line bg-qpos-surface px-3 py-2 text-sm text-qpos-ink placeholder:text-qpos-muted focus:border-qpos-brand focus:outline-none">
                </div>

                <div class="lg:col-span-2">
                    <x-backend.image-field name="profile_image" :label="__('Profile Image')"
                        :current-image="$user->profile_image" />
                </div>
            </div>

            <h2 class="mt-8 text-lg font-semibold text-qpos-ink">{{ __('Password change') }}</h2>

            <div class="mt-4 grid grid-cols-1 gap-5 lg:grid-cols-2">
                <div>
                    <label for="password" class="block text-sm font-medium text-qpos-ink">{{ __('Current password') }}</label>
                    <input type="password" id="password" name="current_password" autocomplete="new-password"
                        placeholder="{{ __('Enter your password') }}"
                        class="mt-1 w-full rounded-lg border border-qpos-line bg-qpos-surface px-3 py-2 text-sm text-qpos-ink placeholder:text-qpos-muted focus:border-qpos-brand focus:outline-none">
                </div>

                <div>
                    <label for="new_password" class="block text-sm font-medium text-qpos-ink">{{ __('New password') }}</label>
                    <input type="password" id="new_password" name="new_password" placeholder="{{ __('New password') }}"
                        class="mt-1 w-full rounded-lg border border-qpos-line bg-qpos-surface px-3 py-2 text-sm text-qpos-ink placeholder:text-qpos-muted focus:border-qpos-brand focus:outline-none">
                </div>

                <div>
                    <label for="confirmPassword" class="block text-sm font-medium text-qpos-ink">{{ __('Confirm password') }}</label>
                    <input type="password" id="confirmPassword" name="new_password_confirmation"
                        placeholder="{{ __('Confirm password') }}"
                        class="mt-1 w-full rounded-lg border border-qpos-line bg-qpos-surface px-3 py-2 text-sm text-qpos-ink placeholder:text-qpos-muted focus:border-qpos-brand focus:outline-none">
                </div>

                <div class="lg:col-span-2">
                    <button type="submit"
                        class="w-full rounded-lg bg-qpos-brand px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90">
                        {{ __('Update') }}
                    </button>
                </div>
            </div>
        </form>
    </x-backend.card>
@endsection
