@extends('backend.master-tailwind')

@section('title', __('Update Product'))

@section('content')
    @if ($catalogueUnitsReady)
        @can('update', $product)
            <a class="qpos-button qpos-button-md qpos-button-secondary mb-4 inline-flex" href="{{ route('backend.admin.products.units.index', $product) }}">{{ __('Packagings and barcodes') }}</a>
        @endcan
    @endif
    @php
        // Le selecteur d'origine affichait la date au format Y-m-d : on alimente le
        // controle natif avec le meme format.
        $expireDate = $product->expire_date
            ? \Illuminate\Support\Carbon::parse($product->expire_date)->format('Y-m-d')
            : null;
    @endphp

    <x-backend.card>
        <form action="{{ route('backend.admin.products.update', $product->id) }}" method="post" class="accountForm"
            enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <x-backend.input name="name" :label="__('Name')" :value="$product->name"
                    :placeholder="__('Enter title')" required />

                <x-backend.input name="sku" :label="__('Sku')" :value="$product->sku"
                    :placeholder="__('Enter sku')" />

                <x-backend.select name="brand_id" :label="__('Brand')" :options="$brands->pluck('name', 'id')->all()"
                    :selected="$product->brand_id" :placeholder="__('Select Brand')" />

                <x-backend.select name="category_id" :label="__('Category')"
                    :options="$categories->pluck('name', 'id')->all()" :selected="$product->category_id"
                    :placeholder="__('Select Category')" />

                <x-backend.input name="price" type="number" step="0.000001" min="0" :label="__('Price')"
                    :value="$product->catalogue_price_ttc ?? $product->price" :placeholder="__('Enter price')" required />

                <x-backend.select name="unit_id" :label="__('Unit')"
                    :options="$units->mapWithKeys(fn ($unit) => [$unit->id => $unit->title . ' (' . $unit->short_name . ')'])->all()"
                    :selected="$product->unit_id" :placeholder="__('Select Unit')" />

                @if ($catalogueUnitsReady)
                    <x-backend.select name="allows_fractional" :label="__('Fractional quantities')"
                        :options="[0 => __('No'), 1 => __('Yes')]"
                        :selected="$product->allows_fractional === null ? null : (int) $product->allows_fractional"
                        :placeholder="__('Choose the quantity rule')" />
                @endif

                <x-backend.select name="discount_type" :label="__('Discount Type')" :options="[
                    'fixed' => __('Fixed'),
                    'percentage' => __('Percentage'),
                ]"
                    :selected="$product->relationLoaded('legacyPromotion') ? ($product->legacyPromotion?->kind ?? $product->catalogue_discount_type ?? $product->discount_type) : $product->discount_type" :placeholder="__('Select Discount Type')" />

                <x-backend.input name="purchase_price" type="number" step="0.000001" min="0"
                    :label="__('Purchase Price')" :value="\App\Support\PricingSchema::ready() ? $product->catalogue_reference_cost : $product->purchase_price"
                    :placeholder="__('Enter purchase Price')" />

                <x-backend.input name="discount" type="number" step="0.000001" min="0" :label="__('Discount Amount')"
                    :value="$product->relationLoaded('legacyPromotion') ? ($product->legacyPromotion ? ($product->legacyPromotion->is_active ? $product->legacyPromotion->value : '0') : ($product->catalogue_discount ?? $product->discount)) : $product->discount" :placeholder="__('Enter discount')" />

                <x-backend.image-field name="product_image" :label="__('Image')"
                    :current-image="$product->image" />

                <div class="lg:col-span-2">
                    <x-backend.textarea name="description" :label="__('Description')"
                        :value="$product->description" :placeholder="__('Enter description')" />
                </div>

                <x-backend.input name="expire_date" type="date" :label="__('Expire date')"
                    :value="$expireDate" :placeholder="__('Enter product expire date')" />

                <div class="lg:col-span-2">
                    <x-backend.switch name="status" :label="__('Active')" :checked="$product->status == 1" />
                </div>
            </div>

            <div class="mt-6">
                <button type="submit"
                    class="qpos-button qpos-button-md qpos-button-primary rounded-lg bg-qpos-brand px-6 py-2 text-sm font-semibold text-white transition hover:opacity-90">
                    {{ __('Update') }}
                </button>
            </div>
        </form>
    </x-backend.card>
@endsection
