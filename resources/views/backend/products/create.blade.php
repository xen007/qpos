@extends('backend.master-tailwind')

@section('title', __('Create Product'))

@section('content')
    @if ($catalogueUnitsReady)
        <p class="mb-4 text-sm text-qpos-muted">{{ __('Selecting a base unit creates its reference packaging with factor 1.') }}</p>
    @endif
    <x-backend.card>
        {{-- Les listes deroulantes sont natives (le plugin select2 n'est plus charge)
             et la date d'expiration utilise le controle de date du navigateur, au
             meme format Y-m-d que l'ancien selecteur. --}}
        <form action="{{ route('backend.admin.products.store') }}" method="post" class="accountForm"
            enctype="multipart/form-data">
            @csrf

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <x-backend.input name="name" :label="__('Name')" :placeholder="__('Enter title')" required />

                <x-backend.input name="sku" :label="__('Sku')" :placeholder="__('Enter sku')" />

                <x-backend.select name="brand_id" :label="__('Brand')" :options="$brands->pluck('name', 'id')->all()"
                    :placeholder="__('Select Brand')" />

                <x-backend.select name="category_id" :label="__('Category')"
                    :options="$categories->pluck('name', 'id')->all()" :placeholder="__('Select Category')" />

                <x-backend.input name="price" type="number" step="0.000001" min="0" :label="__('Price')"
                    :placeholder="__('Enter price')" required />

                <x-backend.select name="unit_id" :label="__('Unit')"
                    :options="$units->mapWithKeys(fn ($unit) => [$unit->id => $unit->title . ' (' . $unit->short_name . ')'])->all()"
                    :placeholder="__('Select Unit')" />

                @if ($catalogueUnitsReady)
                    <x-backend.select name="allows_fractional" :label="__('Fractional quantities')"
                        :options="[0 => __('No'), 1 => __('Yes')]" :selected="0" />
                @endif

                <x-backend.select name="discount_type" :label="__('Discount Type')" :options="[
                    'fixed' => __('Fixed'),
                    'percentage' => __('Percentage'),
                ]"
                    :placeholder="__('Select Discount Type')" />

                <x-backend.input name="purchase_price" type="number" step="0.000001" min="0"
                    :label="__('Purchase Price')" :placeholder="__('Enter purchase Price')" />

                <x-backend.input name="discount" type="number" step="0.000001" min="0" :label="__('Discount Amount')"
                    :placeholder="__('Enter discount')" />

                <x-backend.image-field name="product_image" :label="__('Image')" />

                <div class="lg:col-span-2">
                    <x-backend.textarea name="description" :label="__('Description')"
                        :placeholder="__('Enter description')" />
                </div>

                <x-backend.input name="expire_date" type="date" :label="__('Expire date')"
                    :placeholder="__('Enter product expire date')" />

                <div class="lg:col-span-2">
                    <x-backend.switch name="status" :label="__('Active')" :checked="true" />
                </div>
            </div>

            <div class="mt-6">
                <button type="submit"
                    class="qpos-button qpos-button-md qpos-button-primary rounded-lg bg-qpos-brand px-6 py-2 text-sm font-semibold text-white transition hover:opacity-90">
                    {{ __('Create') }}
                </button>
            </div>
        </form>
    </x-backend.card>
@endsection
