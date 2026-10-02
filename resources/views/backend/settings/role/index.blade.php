@extends('backend.master-tailwind')

@section('title', __('Roles'))

@section('page-actions')
    @can('role_create')
        <button type="button" data-qpos-modal-open="#roleModal"
            class="qpos-button qpos-button-md qpos-button-primary inline-flex items-center gap-2 rounded-lg bg-qpos-brand px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90">
            <x-backend.icon name="fas fa-plus-circle" />
            {{ __('Add New') }}
        </button>
    @endcan
@endsection

@section('content')
    @php
        $iconButton = 'flex h-9 w-9 items-center justify-center rounded-lg border border-qpos-line text-qpos-muted transition hover:bg-qpos-page hover:text-qpos-ink';
    @endphp

    <x-backend.card :padded="false">
        <div class="overflow-x-auto p-4 sm:p-6" tabindex="0" role="region" aria-label="{{ __('Tableau') }}">
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="border-b border-qpos-line text-left text-xs font-semibold uppercase tracking-wide text-qpos-muted">
                        <th class="px-3 py-3">{{ __('Name') }}</th>
                        <th class="px-3 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roles as $role)
                        <tr class="border-b border-qpos-line/60 last:border-0">
                            <td class="px-3 py-3 text-qpos-ink">{{ $role->name }}</td>
                            <td class="px-3 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('backend.admin.roles.show', $role->id) }}"
                                        title="{{ __('Permission Setup') }}" aria-label="{{ __('Permission Setup') }}"
                                        class="{{ $iconButton }}">
                                        <x-backend.icon name="fas fa-cog" />
                                    </a>

                                    @if ($role->name !== 'Admin')
                                        <button type="button" data-qpos-modal-open="#editRole-{{ $role->id }}"
                                            title="{{ __('Edit Role') }}" aria-label="{{ __('Edit Role') }}"
                                            class="{{ $iconButton }}">
                                            <x-backend.icon name="fas fa-pencil-alt" />
                                        </button>

                                        <form action="{{ route('backend.admin.roles.delete', $role->id) }}"
                                            method="POST"
                                            onsubmit="return confirm(@js(__('Are you sure you want to delete this item?')))">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="{{ __('Delete Role') }}"
                                                aria-label="{{ __('Delete Role') }}" class="{{ $iconButton }}">
                                                <x-backend.icon name="fas fa-trash-alt" />
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-backend.card>

    @can('role_create')
        <x-backend.modal id="roleModal" :title="__('Add new role')" :action="route('backend.admin.roles.create')"
            :submit-label="__('Submit')">
            <div>
                <label for="role-name" class="block text-sm font-medium text-qpos-ink">{{ __('Name') }}</label>
                <input id="role-name" type="text" name="name" value="{{ old('name') }}" required
                    placeholder="{{ __('Role Name') }}"
                    class="qpos-control mt-1 w-full rounded-lg border border-qpos-line bg-qpos-surface px-3 py-2 text-sm text-qpos-ink placeholder:text-qpos-muted focus:border-qpos-brand focus:outline-none">
            </div>
        </x-backend.modal>
    @endcan

    {{-- Une modale d'edition par role modifiable (le role Admin n'est pas modifiable). --}}
    @foreach ($roles as $role)
        @continue ($role->name === 'Admin')

        <x-backend.modal id="editRole-{{ $role->id }}" :title="__('Edit Role')"
            :action="route('backend.admin.roles.update', $role->id)" method="PUT" :submit-label="__('Save changes')">
            <div>
                <label for="role-name-{{ $role->id }}"
                    class="block text-sm font-medium text-qpos-ink">{{ __('Name') }}</label>
                <input id="role-name-{{ $role->id }}" type="text" name="name"
                    value="{{ old('name', $role->name) }}" required placeholder="{{ __('Role Name') }}"
                    class="mt-1 w-full rounded-lg border border-qpos-line bg-qpos-surface px-3 py-2 text-sm text-qpos-ink placeholder:text-qpos-muted focus:border-qpos-brand focus:outline-none">
            </div>
        </x-backend.modal>
    @endforeach
@endsection
