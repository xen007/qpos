@extends('backend.master-tailwind')
@section('title',__('replenishment.suppliers'))
@section('content')
<div class="space-y-6 qpos-touch-workspace"><x-backend.card :title="__('replenishment.suppliers')">
<form method="get" class="flex flex-wrap gap-3"><x-backend.input name="search" :label="__('Search')" :value="request('search')"/><x-backend.select name="supplier_id" :label="__('Supplier')" :options="[''=>__('All')]+$suppliers->pluck('name','id')->all()" :selected="request('supplier_id')"/><button class="qpos-button qpos-button-md qpos-button-secondary">{{ __('Search') }}</button></form>
<div class="flex flex-wrap gap-2 mt-4">@foreach($products as $p)<a class="qpos-button qpos-button-md qpos-button-secondary" href="{{ route('backend.admin.replenishment.suppliers',['product_id'=>$p->id]) }}">{{ $p->name }}</a>@endforeach</div>{{ $products->links() }}
</x-backend.card>
@if($product)
<x-backend.card :title="$product->name">
<form method="post" action="{{ route('backend.admin.replenishment.suppliers.save') }}" class="grid gap-4 md:grid-cols-2">@csrf
<input type="hidden" name="product_id" value="{{ $product->id }}">
<x-backend.select name="supplier_id" :label="__('Supplier')" :options="$suppliers->pluck('name','id')->all()" required/>
<x-backend.select name="product_unit_id" :label="__('Packaging')" :options="$product->productUnits->where('is_active',true)->pluck('label','id')->all()" required/>
<x-backend.input name="supplier_reference" :label="__('replenishment.supplier_reference')" maxlength="100"/>
<x-backend.input name="delay_days" :label="__('replenishment.delay_days')" type="number" min="0" max="365" value="7" required/>
<x-backend.input name="minimum_order" :label="__('replenishment.minimum_order')" type="number" step="0.000001" min="0.000001" value="1" required/>
<x-backend.input name="order_multiple" :label="__('replenishment.order_multiple')" type="number" step="0.000001" min="0.000001" value="1" required/>
<x-backend.input name="estimated_cost" :label="__('replenishment.estimated_cost')" type="number" step="0.000001" min="0"/>
<x-backend.input name="price_on" :label="__('replenishment.price_on')" type="date" :max="now('Africa/Douala')->toDateString()"/>
<x-backend.select name="preferred" :label="__('replenishment.preferred')" :options="['0'=>__('No'),'1'=>__('Yes')]"/>
<x-backend.select name="is_active" :label="__('Active')" :options="['1'=>__('Yes'),'0'=>__('No')]"/>
<x-backend.input name="reason" :label="__('Reason')" required maxlength="500"/>
<button class="qpos-button qpos-button-md qpos-button-primary">{{ __('Save') }}</button>
</form><p class="mt-3">{{ __('replenishment.supplier_help') }}</p>
</x-backend.card>
@endif
<x-backend.card :title="__('replenishment.suppliers')"><div class="overflow-x-auto"><table class="qpos-table"><thead><tr><th>{{ __('Product') }}</th><th>{{ __('Supplier') }}</th><th>{{ __('Packaging') }}</th><th>{{ __('replenishment.delay_days') }}</th><th>{{ __('replenishment.estimated_cost') }}</th><th>{{ __('Active') }}</th></tr></thead><tbody>@foreach($entries as $e)<tr><td>{{ $e->product }}</td><td>{{ $e->supplier }}</td><td>{{ $e->packaging }}</td><td>{{ $e->delay_days }}</td><td>{{ $e->estimated_cost===null?'—':App\Support\SaleFormat::moneyDisplay($e->estimated_cost).' XAF' }} · {{ $e->price_on }}</td><td>{{ $e->is_active?__('Yes'):__('No') }}</td></tr>@endforeach</tbody></table></div>{{ $entries->links() }}</x-backend.card>
</div>@endsection
