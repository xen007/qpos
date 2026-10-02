@php
    $editing = $row->exists;
    $scope = $row->point_of_sale_id;
@endphp
<form method="post" action="{{ $editing ? route('backend.admin.pricing.update', ['entity' => $entity, 'id' => $row->id]) : route('backend.admin.pricing.store', ['entity' => $entity]) }}" class="mt-4 grid gap-4 sm:grid-cols-2">
    @csrf @if ($editing) @method('PUT') @endif
    <input type="hidden" name="product_unit_id" value="{{ $unit->id }}">
    <x-backend.input name="name" :label="__('Name')" :value="$row->name" maxlength="255" required />
    <x-backend.select name="scope_point_of_sale_id" :label="__('Store scope')" :options="$shops->pluck('name', 'id')->all()" :selected="$scope" :placeholder="__('All stores')" />
    <x-backend.select name="customer_id" :label="__('Customer')" :options="$customers->pluck('name', 'id')->all()" :selected="$row->customer_id" :placeholder="__('All customers')" />
    <x-backend.input name="minimum_quantity" :label="__('Minimum quantity')" :value="$row->minimum_quantity ?? '0'" inputmode="decimal" required />
    <x-backend.input name="priority" type="number" :label="__('Priority')" :value="$row->priority ?? 0" required />
    <x-backend.input name="starts_at" type="datetime-local" :label="__('Starts at')" :value="$row->starts_at?->format('Y-m-d\\TH:i')" />
    <x-backend.input name="ends_at" type="datetime-local" :label="__('Ends at')" :value="$row->ends_at?->format('Y-m-d\\TH:i')" />
    <x-backend.switch name="is_active" :label="__('Active')" :checked="$editing ? $row->is_active : true" />
    @if ($entity === 'rules')
        <x-backend.input name="price_ttc" :label="__('Price including tax')" :value="$row->price_ttc" inputmode="decimal" required />
    @else
        <x-backend.select name="kind" :label="__('Promotion type')" :options="['percentage' => __('Percentage'), 'fixed' => __('Fixed discount per packaging'), 'quantity' => __('Buy and get free'), 'bundle' => __('Bundle price')]" :selected="$row->kind ?? 'percentage'" required />
        <x-backend.input name="value" :label="__('Discount value')" :value="$row->value ?? '0'" inputmode="decimal" />
        <x-backend.input name="buy_quantity" type="number" min="1" :label="__('Purchased quantity')" :value="$row->buy_quantity" />
        <x-backend.input name="free_quantity" type="number" min="1" :label="__('Free quantity')" :value="$row->free_quantity" />
        <x-backend.input name="bundle_quantity" type="number" min="1" :label="__('Bundle quantity')" :value="$row->bundle_quantity" />
        <x-backend.input name="bundle_price" :label="__('Bundle price')" :value="$row->bundle_price" inputmode="decimal" />
        <p class="text-sm text-qpos-muted sm:col-span-2">{{ __('Quantity offers count complete groups including free items. Bundle offers apply to this packaging only; the remainder keeps its unit price.') }}</p>
    @endif
    <div class="sm:col-span-2"><button type="submit" class="qpos-button qpos-button-md qpos-button-primary">{{ __('Save') }}</button></div>
</form>
