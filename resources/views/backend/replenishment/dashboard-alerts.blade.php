<x-backend.card :title="__('replenishment.dashboard_alerts')">
<p class="mb-3 text-sm">{{ __('replenishment.dashboard_alerts_hint') }}</p>
<p>{{ __('replenishment.out_count') }}: <strong>{{ $replenishment['out'] }}</strong> · {{ __('Low stock') }}: <strong>{{ $replenishment['low'] }}</strong></p>
<details class="my-3"><summary class="min-h-11 cursor-pointer">{{ __('replenishment.dashboard_alerts') }} · {{ __('replenishment.category_count') }}</summary>
<ul class="grid gap-2 sm:grid-cols-2">@foreach($replenishment['categories'] as $category)<li>{{ $category['label'] }}: {{ $category['total'] }}</li>@endforeach</ul></details>
<details><summary class="min-h-11 cursor-pointer">{{ __('Products') }} ({{ $replenishment['rows']->count() }})</summary>
<ul class="space-y-3">
@forelse($replenishment['rows'] as $row)
<li class="flex flex-wrap items-center justify-between gap-3 rounded-lg border p-3">
<div><strong>{{ $row->name }}</strong> · {{ $row->sku }} · {{ __('Shop') }} #{{ $row->shop_id }}
<p>{{ __('replenishment.available') }}: {{ App\Support\SaleFormat::quantityDisplay($row->stock_available??'0') }} · {{ __('replenishment.minimum') }}: {{ App\Support\SaleFormat::quantityDisplay($row->minimum) }}</p></div>
@if(auth()->user()->hasAllPermissions(['purchase_suggestion_manage','purchase_order_view','purchase_order_create','stock_view']))
<a class="qpos-button qpos-button-md qpos-button-secondary" href="{{ route('backend.admin.replenishment.suggestions',['operation_point_of_sale_id'=>$row->shop_id,'search'=>$row->sku]) }}">{{ __('replenishment.suggestions') }}</a>
@elseif(auth()->user()->can('purchase_order_create'))
<a class="qpos-button qpos-button-md qpos-button-secondary" href="{{ route('backend.admin.purchase-orders.create',['operation_point_of_sale_id'=>$row->shop_id]) }}">{{ __('replenishment.manual_order') }}</a>
@endif
</li>
@empty<li>{{ __('No records found') }}</li>@endforelse
</ul>
</details>
</x-backend.card>
