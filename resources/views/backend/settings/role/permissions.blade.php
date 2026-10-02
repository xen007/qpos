@extends('backend.master-tailwind')

@section('title', $role->name . ' ' . __('Role Permission'))

@section('page-actions')
    @can('role_view')
        <a href="{{ route('backend.admin.roles') }}"
            class="qpos-button qpos-button-md qpos-button-primary inline-flex items-center gap-2 rounded-lg bg-qpos-brand px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90">
            <x-backend.icon name="fas fa-ruler-vertical" />
            {{ __('Roles') }}
        </a>
    @endcan
@endsection

@section('content')
    <x-backend.card>
        <form action="{{ route('backend.admin.update.role-permissions', $role->id) }}" method="post">
            @csrf

            {{-- Chaque permission est une case a cocher : cochee si le role la detient.
                 (L'ancienne vue comparait le nom de la permission au booleen trouve,
                 ce qui revenait au meme ; la condition est ici explicite.) --}}
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($permissions as $data)
                    @php
                        $per_found = null;

                        if (isset($role)) {
                            $per_found = $role->hasPermissionTo($data->name);
                        }

                        if (isset($user)) {
                            $per_found = $user->hasDirectPermission($data->name);
                        }
                    @endphp

                    <label for="customSwitch{{ $data->id }}"
                        class="flex cursor-pointer items-center gap-3 rounded-lg border border-qpos-line px-3 py-2 text-sm text-qpos-ink transition hover:bg-qpos-page">
                        <input type="checkbox" id="customSwitch{{ $data->id }}" name="permissions[]"
                            value="{{ $data->name }}" @checked((bool) $per_found)
                            class="h-4 w-4 rounded border-qpos-line accent-qpos-brand focus:outline-none focus:ring-2 focus:ring-qpos-brand/40">
                        <span>{{ snakeToTitle($data->name) }}</span>
                    </label>
                @endforeach
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit"
                    class="qpos-button qpos-button-md qpos-button-primary rounded-lg bg-qpos-brand px-6 py-2 text-sm font-semibold text-white transition hover:opacity-90">
                    {{ __('Submit') }}
                </button>
            </div>
        </form>
    </x-backend.card>
@endsection
