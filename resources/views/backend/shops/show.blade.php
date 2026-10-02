@extends('backend.master-tailwind')
@section('title', $shop->name)
@section('content')
<x-backend.card>
    <div class="mb-5 flex gap-3">
        <a class="qpos-button qpos-button-md qpos-button-secondary" href="{{ route('backend.admin.shops.index') }}">{{ __('Back') }}</a>
        @can('update', $shop)<a class="qpos-button qpos-button-md qpos-button-primary" href="{{ route('backend.admin.shops.edit', $shop) }}">{{ __('Edit') }}</a>@endcan
    </div>
    <dl class="grid gap-5 sm:grid-cols-2">
        @foreach ([__('Code') => $shop->code, __('Name') => $shop->name, __('Address') => $shop->address, __('Status') => $shop->is_active ? __('Active') : __('Inactive')] as $label => $value)
            <div><dt class="text-sm text-qpos-muted">{{ $label }}</dt><dd>{{ $value ?? '—' }}</dd></div>
        @endforeach
    </dl>
    <p class="mt-5 text-sm text-qpos-muted">{{ __('Deactivating a store preserves its data and prevents new operations there.') }}</p>
</x-backend.card>
@endsection
