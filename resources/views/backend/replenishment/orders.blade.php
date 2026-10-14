@extends('backend.master-tailwind')
@section('title',__('replenishment.orders'))
@section('content')
<div class="space-y-6 qpos-touch-workspace"><x-backend.card :title="__('replenishment.orders')">
<div class="flex flex-wrap gap-3">@can('purchase_order_create')<a class="qpos-button qpos-button-md qpos-button-primary" href="{{ route('backend.admin.purchase-orders.create') }}">{{ __('Add New') }}</a>@endcan
@can('purchase_order_export')@foreach(['pdf','xlsx'] as $format)<a class="qpos-button qpos-button-md qpos-button-secondary" href="{{ route('backend.admin.purchase-orders.export',array_merge(request()->query(),['format'=>$format])) }}">{{ strtoupper($format) }}</a>@endforeach
@endcan</div>
<form method="get" class="grid gap-3 md:grid-cols-3 mt-4">
<x-backend.input name="search" :label="__('Reference')" :value="request('search')"/>
<x-backend.select name="supplier_id" :label="__('Supplier')" :options="[''=>__('All')]+$suppliers->pluck('name','id')->all()" :selected="request('supplier_id')"/>
<x-backend.select name="shop_id" :label="__('Shop')" :options="[''=>__('All')]+$shops->pluck('name','id')->all()" :selected="request('shop_id')"/>
<x-backend.select name="category_id" :label="__('Category')" :options="[''=>__('All')]+App\Models\Category::orderBy('name')->pluck('name','id')->all()" :selected="request('category_id')"/>
<x-backend.select name="status" :label="__('Status')" :options="[''=>__('All')]+collect(['draft','approved','issued','confirmed','partial','received','cancelled','closed','deleted'])->mapWithKeys(fn($s)=>[$s=>__('replenishment.status_'.$s)])->all()" :selected="request('status')"/>
<x-backend.input name="date_from" :label="__('From')" type="date" :value="request('date_from')"/><x-backend.input name="date_to" :label="__('To')" type="date" :value="request('date_to')"/>
<button class="qpos-button qpos-button-md qpos-button-primary">{{ __('Search') }}</button><a class="qpos-button qpos-button-md qpos-button-secondary" href="{{ route('backend.admin.purchase-orders.index') }}">{{ __('administration.reset_filters') }}</a></form>
</x-backend.card><x-backend.card><div class="overflow-x-auto"><table class="qpos-table"><thead><tr><th>{{ __('Reference') }}</th><th>{{ __('Shop') }}</th><th>{{ __('Supplier') }}</th><th>{{ __('Status') }}</th><th>{{ __('Total') }}</th></tr></thead><tbody>
@forelse($orders as $po)<tr><td><a class="qpos-button qpos-button-md qpos-button-secondary" href="{{ route('backend.admin.purchase-orders.show',$po->id) }}">{{ $po->reference }}</a></td><td>{{ $po->shop->name }}</td><td>{{ $po->supplier->name }}</td><td>{{ __('replenishment.status_'.$po->status) }}</td><td>{{ $po->total_amount===null?__('replenishment.price_required'):App\Support\SaleFormat::moneyDisplay($po->total_amount).' XAF' }}</td></tr>@empty<tr><td colspan="5">{{ __('No data available') }}</td></tr>@endforelse
</tbody></table></div>{{ $orders->links() }}</x-backend.card></div>@endsection
