@extends('backend.master-tailwind')
@section('title', $product->name)
@section('content')
<x-backend.card>
    <div class="mb-5 flex flex-wrap gap-3">
        <a class="qpos-button qpos-button-md qpos-button-secondary" href="{{ route('backend.admin.products.index') }}">{{ __('Back') }}</a>
        @can('update', $product)
            <a class="qpos-button qpos-button-md qpos-button-primary" href="{{ route('backend.admin.products.edit', $product) }}">{{ __('Edit') }}</a>
            @if ($catalogueUnitsReady)
                <a class="qpos-button qpos-button-md qpos-button-secondary" href="{{ route('backend.admin.products.units.index', $product) }}">{{ __('Packagings and barcodes') }}</a>
            @endif
        @endcan
    </div>
    <dl class="grid gap-5 sm:grid-cols-2">
        @foreach ([__('Name') => $product->name, __('Sku') => $product->sku, __('Price') => $product->catalogue_price_ttc ?? $product->price, __('Brand') => $product->brand?->name, __('Category') => $product->category?->name, __('Unit') => $product->unit?->title, __('Status') => $product->status ? __('Active') : __('Inactive')] as $label => $value)
            <div><dt class="text-sm text-qpos-muted">{{ $label }}</dt><dd class="font-medium">{{ $value ?? '—' }}</dd></div>
        @endforeach
    </dl>
    <p class="mt-5 whitespace-pre-wrap text-qpos-ink">{{ $product->description }}</p>
    @if ($product->image)
        <img class="mt-5 max-h-64 rounded-xl object-contain" src="{{ asset('storage/'.$product->image) }}" alt="{{ $product->name }}" loading="lazy">
    @endif
</x-backend.card>
@endsection