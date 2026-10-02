@extends('backend.master-tailwind')
@section('title', __('Stores'))
@section('page-actions')
    @can('create', \App\Models\PointOfSale::class)
        <a class="qpos-button qpos-button-md qpos-button-primary" href="{{ route('backend.admin.shops.create') }}">{{ __('Add store') }}</a>
    @endcan
@endsection
@section('content')
<x-backend.card>
    <form method="get" class="mb-5 flex flex-wrap items-end gap-3">
        <x-backend.input name="search" :label="__('Search')" :value="$search" maxlength="100" />
        <button type="submit" class="qpos-button qpos-button-md qpos-button-secondary">{{ __('Search') }}</button>
    </form>
    <div class="overflow-x-auto" tabindex="0" role="region" aria-label="{{ __('Stores') }}">
        <table class="w-full text-left text-sm">
            <thead><tr><th class="p-3">{{ __('Code') }}</th><th class="p-3">{{ __('Name') }}</th><th class="p-3">{{ __('Status') }}</th><th class="p-3">{{ __('Actions') }}</th></tr></thead>
            <tbody>
                @forelse ($shops as $shop)
                    <tr class="border-t border-qpos-line">
                        <td class="p-3">{{ $shop->code }}</td><td class="p-3">{{ $shop->name }}</td>
                        <td class="p-3">{{ $shop->is_active ? __('Active') : __('Inactive') }}</td>
                        <td class="p-3"><div class="flex flex-wrap gap-2">
                            @can('inspect', $shop)<a class="qpos-button qpos-button-sm qpos-button-secondary" href="{{ route('backend.admin.shops.show', $shop) }}">{{ __('View') }}</a>@endcan
                            @can('update', $shop)<a class="qpos-button qpos-button-sm qpos-button-secondary" href="{{ route('backend.admin.shops.edit', $shop) }}">{{ __('Edit') }}</a>@endcan
                            @can('delete', $shop)
                                @if ($shop->is_active)
                                    <form method="post" action="{{ route('backend.admin.shops.destroy', $shop) }}">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="qpos-button qpos-button-sm qpos-button-secondary">{{ __('Deactivate') }}</button>
                                    </form>
                                @endif
                            @endcan
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="p-5 text-qpos-muted">{{ __('No stores available.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $shops->links('pagination::tailwind') }}</div>
</x-backend.card>
@endsection
