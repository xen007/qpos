@extends('backend.master-tailwind')

@section('title', __('Update User'))

@section('content')
    @php
        // La liste ne permet qu'un role : on preselectionne celui de l'utilisateur.
        $currentRoleId = $user->roles->first()?->id;
    @endphp

    <x-backend.card>
        <form action="{{ route('backend.admin.user.edit', $user->id) }}" method="post" class="accountForm"
            enctype="multipart/form-data">
            @csrf

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <x-backend.input name="name" :label="__('Full Name')" :value="$user->name"
                    :placeholder="__('Enter full name')" required />

                <x-backend.input name="email" type="email" :label="__('Login Email')" :value="$user->email"
                    :placeholder="__('Email')" required />

                {{-- Le mot de passe laisse vide conserve l'actuel (StoreUserRequest/UpdateUserRequest). --}}
                @include('backend.users.roles', ['selectedRoles'=>old('roles',$user->roles->pluck('id')->all())])

                <x-backend.input name="password" type="password" :label="__('Login password')"
                    :placeholder="__('Leave blank to keep current password.')" autocomplete="new-password" />

                <div class="lg:col-span-2">
                    {{-- L'ancienne version affichait un apercu vide meme quand l'utilisateur
                         avait un avatar : le composant montre l'image courante. --}}
                    <x-backend.image-field name="profile_image" :label="__('Profile Image')"
                        :current-image="$user->profile_image" />
                </div>
            </div>

            <div class="mt-6">
                <button type="submit"
                    class="qpos-button qpos-button-md qpos-button-primary w-full rounded-lg bg-qpos-brand px-6 py-2 text-sm font-semibold text-white transition hover:opacity-90 lg:w-auto">
                    {{ __('Update') }}
                </button>
            </div>
        </form>
    </x-backend.card>
@endsection
