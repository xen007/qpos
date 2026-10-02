@extends('backend.master-tailwind')
@section('title', __('Packagings and barcodes'))
@section('content')
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <a class="qpos-button qpos-button-md qpos-button-secondary" href="{{ route('backend.admin.products.edit', $product) }}">{{ __('Back') }}</a>
        <p class="text-sm font-medium text-qpos-ink">{{ $product->name }}</p>
    </div>
    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-qpos-danger p-4 text-qpos-danger" role="alert">
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    @if (\App\Support\PricingSchema::ready() && auth()->user()->can('pricing_view'))
        <a class="qpos-button qpos-button-md qpos-button-secondary mb-4" href="{{ route('backend.admin.pricing.index', ['search' => $product->name]) }}">{{ __('Prices and promotions') }}</a>
    @endif
    <p class="mb-4 text-sm text-qpos-muted">{{ __('The first packaging must use the base unit and factor 1. Quantities use at most six decimal places.') }}</p>
    @if ($product->allows_fractional === null)
        <p class="mb-4 text-sm text-qpos-danger">{{ __('Set the fractional quantity rule on the product first.') }}</p>
    @endif
    @if (!$product->unit_id)
        <p class="mb-4 text-sm text-qpos-danger">{{ __('Select a base unit on the product before creating packagings.') }}</p>
    @endif
    <div class="space-y-5">
        @foreach ($packagings as $packaging)
            <x-backend.card>
                <h2 class="mb-4 font-semibold text-qpos-ink">{{ $packaging->label }} @if ($packaging->is_reference) — {{ __('Reference packaging') }} @endif</h2>
                @include('backend.products.packaging-form', ['packaging' => $packaging])
                <div class="mt-5 space-y-3">
                    <h3 class="font-medium">{{ __('Barcodes') }}</h3>
                    @foreach ($packaging->barcodes as $barcode)
                        <form class="flex flex-wrap items-center gap-3" method="post" action="{{ route('backend.admin.products.units.barcodes.update', [$product, $packaging, $barcode]) }}">
                            @csrf @method('PUT')
                            <span class="font-mono">{{ $barcode->barcode }}</span>
                            <span class="text-sm text-qpos-muted">{{ $barcode->is_active ? __('Active') : __('Inactive') }}</span>
                            <input type="hidden" name="is_active" value="{{ $barcode->is_active ? 0 : 1 }}">
                            <button class="qpos-button qpos-button-sm qpos-button-secondary" type="submit">{{ $barcode->is_active ? __('Deactivate') : __('Activate') }}</button>
                        </form>
                    @endforeach
                    <form class="flex flex-wrap items-end gap-3" method="post" action="{{ route('backend.admin.products.units.barcodes.store', [$product, $packaging]) }}">
                        @csrf
                        <div>
                            <label class="text-sm" for="barcode-{{ $packaging->id }}">{{ __('New barcode') }}</label>
                            <input class="qpos-control mt-1 w-full" id="barcode-{{ $packaging->id }}" name="barcode" type="text" maxlength="128" required>
                        </div>
                        <button class="qpos-button qpos-button-md qpos-button-primary" type="submit">{{ __('Add New') }}</button>
                    </form>
                    @if ($packaging->is_active && $product->allows_fractional !== null)
                        <form class="flex flex-wrap items-end gap-3" method="post" action="{{ route('backend.admin.products.units.convert', [$product, $packaging]) }}">
                            @csrf
                            <div>
                                <label class="text-sm" for="quantity-{{ $packaging->id }}">{{ __('Quantity') }}</label>
                                <input class="qpos-control mt-1 w-full" id="quantity-{{ $packaging->id }}" name="quantity" type="text" inputmode="decimal" maxlength="32" required>
                            </div>
                            <button class="qpos-button qpos-button-md qpos-button-secondary" type="submit">{{ __('Convert to base unit') }}</button>
                        </form>
                    @endif
                </div>
            </x-backend.card>
        @endforeach
        {{ $packagings->links('pagination::tailwind') }}
        <x-backend.card>
            <h2 class="mb-4 font-semibold">{{ __('Add packaging') }}</h2>
            @include('backend.products.packaging-form', ['packaging' => null])
        </x-backend.card>
    </div>
@endsection