@extends('backend.master-tailwind')
@section('title', __('Prices and promotions'))
@section('content')
<x-backend.card>
    <p class="mb-4 text-sm text-qpos-muted">{{ __('Prices include tax. Payment and invoice currency rounding will be implemented in Phase 4.') }}</p>
    <form method="get" class="flex flex-wrap gap-3 mb-5">
        <x-backend.input name="search" :label="__('Search products')" :value="$search" maxlength="100" />
        <button type="submit" class="qpos-button qpos-button-md qpos-button-secondary">{{ __('Search') }}</button>
    </form>
    @if (session('pricing_quote'))
        @php($quote = session('pricing_quote'))
        <div role="status" class="mb-5 rounded-xl border border-qpos-line p-4">
            <p>{{ __('Price including tax') }} : {{ $quote['price_ttc'] }}</p>
            <p>{{ __('Discount') }} : {{ $quote['discount_total'] }}</p>
            <p>{{ __('Total') }} : {{ $quote['total_ttc'] }}</p>
            <p>{{ __('Base quantity: :quantity', ['quantity' => $quote['base_quantity']]) }}</p>
        </div>
    @endif
    @forelse ($units as $unit)
        <section class="border-t border-qpos-line py-5">
            <h2 class="font-semibold">{{ $unit->product->name }} / {{ $unit->label }} ({{ $unit->code }})</h2>
            <p class="my-2">{{ __('Price including tax') }} : {{ $unit->sale_price_ttc ?? '—' }}</p>
            @can('pricing_update')
                @can('update', $unit->product)
                <details class="my-3"><summary class="cursor-pointer">{{ __('Packaging prices') }}</summary>
                    <form method="post" action="{{ route('backend.admin.pricing.packaging', $unit) }}" class="mt-3 grid gap-4 sm:grid-cols-2">
                        @csrf @method('PUT')
                        <x-backend.input name="sale_price_ttc" :label="__('Price including tax')" :value="$unit->sale_price_ttc" inputmode="decimal" required />
                        @if (auth()->user()->hasRole('Admin'))
                            <x-backend.input name="reference_purchase_cost" :label="__('Reference purchase cost')" :value="$unit->reference_purchase_cost" inputmode="decimal" />
                        @endif
                        <button type="submit" class="qpos-button qpos-button-md qpos-button-primary">{{ __('Save') }}</button>
                    </form>
                </details>
                @endcan
            @endcan
            @can('pricing_create')
                <details class="my-3"><summary class="cursor-pointer">{{ __('Add price rule') }}</summary>
                    @include('backend.pricing.rule-form', ['entity' => 'rules', 'row' => new \App\Models\PriceRule, 'unit' => $unit])
                </details>
                <details class="my-3"><summary class="cursor-pointer">{{ __('Add promotion') }}</summary>
                    @include('backend.pricing.rule-form', ['entity' => 'promotions', 'row' => new \App\Models\Promotion, 'unit' => $unit])
                </details>
            @endcan
            <form method="post" action="{{ route('backend.admin.pricing.quote') }}" class="mt-3 flex flex-wrap items-end gap-3">
                @csrf <input type="hidden" name="product_unit_id" value="{{ $unit->id }}">
                @if ($selectedPointOfSale)<input type="hidden" name="operation_point_of_sale_id" value="{{ $selectedPointOfSale->id }}">@endif
                <x-backend.input name="quantity" :label="__('Quantity')" inputmode="decimal" value="1" required />
                <x-backend.select name="customer_id" :label="__('Customer')" :options="$customers->pluck('name', 'id')->all()" :placeholder="__('All customers')" />
                <button type="submit" class="qpos-button qpos-button-md qpos-button-secondary">{{ __('Calculate price') }}</button>
            </form>
        </section>
    @empty <p>{{ __('No products found') }}</p>
    @endforelse
    {{ $units->links('pagination::tailwind') }}
</x-backend.card>
@foreach (['rules' => $rules, 'promotions' => $promotions] as $entity => $rows)
<x-backend.card class="mt-6">
    <h2 class="font-semibold mb-4">{{ $entity === 'rules' ? __('Price rules') : __('Promotions') }}</h2>
    @foreach ($rows as $row)
        <section class="border-t border-qpos-line py-4">
            <h3 class="font-medium">{{ $row->name }} — {{ $row->productUnit->product->name }} / {{ $row->productUnit->label }}</h3>
            <p>{{ $row->pointOfSale?->name ?? __('All stores') }} / {{ $row->customer?->name ?? __('All customers') }} — {{ $row->is_active ? __('Active') : __('Inactive') }}</p>
            @can('pricing_update')
                <details class="my-3"><summary class="cursor-pointer">{{ __('Edit') }}</summary>
                    @include('backend.pricing.rule-form', ['unit' => $row->productUnit])
                </details>
            @endcan
            @can('pricing_delete')
                @if ($row->is_active)
                    <form method="post" action="{{ route('backend.admin.pricing.destroy', ['entity' => $entity, 'id' => $row->id]) }}">
                        @csrf @method('DELETE')
                        <button class="qpos-button qpos-button-sm qpos-button-secondary">{{ __('Deactivate') }}</button>
                    </form>
                @endif
            @endcan
        </section>
    @endforeach
    {{ $rows->links('pagination::tailwind') }}
</x-backend.card>
@endforeach
@endsection
