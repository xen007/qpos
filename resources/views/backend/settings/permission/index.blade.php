@extends('backend.master-tailwind')

@section('title', __('Permissions'))

@section('page-actions')
    @can('role_view')
        <a href="{{ route('backend.admin.roles') }}"
            class="inline-flex items-center gap-2 rounded-lg bg-qpos-brand px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90">
            <i class="fas fa-ruler-vertical" aria-hidden="true"></i>
            {{ __('Roles') }}
        </a>
    @endcan
@endsection

@section('content')
    <x-backend.card :padded="false">
        <div class="overflow-x-auto p-4 sm:p-6">
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="border-b border-qpos-line text-left text-xs font-semibold uppercase tracking-wide text-qpos-muted">
                        <th class="px-3 py-3">{{ __('Name') }}</th>
                        <th class="px-3 py-3">{{ __('Slug') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($permissions as $data)
                        <tr class="border-b border-qpos-line/60 last:border-0">
                            <td class="px-3 py-3 text-qpos-ink">{{ snakeToTitle($data->name) }}</td>
                            <td class="px-3 py-3 font-mono text-xs text-qpos-muted">{{ $data->name }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-backend.card>
@endsection
