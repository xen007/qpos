@extends('backend.master-tailwind')
@section('title',$po->reference)
@section('content')
<div class="space-y-6 qpos-touch-workspace">
<x-backend.card :title="$po->reference"><p>{{ $po->supplier->name }} Â· {{ $po->shop->name }} Â· {{ __('replenishment.status_'.$po->status) }}</p>
<p>{{ __('replenishment.expected_date') }}: {{ $po->expected_date??'â€”' }} Â· {{ $po->notes }}</p>
<div class="flex flex-wrap gap-3 mt-4">@if($po->status==='draft')@can('purchase_order_update')<a class="qpos-button qpos-button-md qpos-button-secondary" href="{{ route('backend.admin.purchase-orders.edit',['id'=>$po->id,'operation_point_of_sale_id'=>$po->point_of_sale_id]) }}">{{ __('Edit') }}</a>@endcan
@endif
@can('purchase_order_export')@foreach(['pdf','xlsx'] as $format)<a class="qpos-button qpos-button-md qpos-button-secondary" href="{{ route('backend.admin.purchase-orders.detail-export',['id'=>$po->id,'format'=>$format]) }}">{{ strtoupper($format) }}</a>@endforeach
@endcan
@if($po->purchase_id)@can('purchase_view')<a class="qpos-button qpos-button-md qpos-button-secondary" href="{{ route('backend.admin.purchase.products',$po->purchase_id) }}">{{ __('Purchase') }} #{{ $po->purchase_id }}</a>@endcan
@endif</div>
<div class="overflow-x-auto mt-4"><table class="qpos-table"><thead><tr><th>{{ __('Product') }}</th><th>{{ __('Packaging') }}</th><th>{{ __('Quantity') }}</th><th>{{ __('replenishment.quantity_received') }}</th><th>{{ __('replenishment.remaining') }}</th><th>{{ __('Price') }}</th><th>{{ __('Discount') }}</th></tr></thead><tbody>
@foreach($po->items as $l)<tr><td>{{ $l->snapshot['name'] }}</td><td>{{ $l->snapshot['unit_label'] }} Ã— {{ App\Support\SaleFormat::quantityDisplay($l->snapshot['factor']) }}</td><td>{{ App\Support\SaleFormat::quantityDisplay($l->quantity_ordered) }}</td><td>{{ App\Support\SaleFormat::quantityDisplay($quantities[$l->id]['received']) }}</td><td>{{ App\Support\SaleFormat::quantityDisplay($quantities[$l->id]['remaining']) }}</td><td>{{ $l->unit_price===null?'â€”':App\Support\SaleFormat::moneyDisplay($l->unit_price) }}</td><td>{{ App\Support\SaleFormat::moneyDisplay($l->discount_amount) }}</td></tr>@endforeach</tbody></table></div>
<p>{{ __('Total') }}: {{ $po->total_amount===null?'â€”':App\Support\SaleFormat::moneyDisplay($po->total_amount).' XAF' }}</p>
</x-backend.card>
@php($actions=match($po->status){'draft'=>['approve'=>'purchase_order_approve','delete'=>'purchase_order_delete'],'approved'=>['issue'=>'purchase_order_send','close'=>'purchase_order_cancel','cancel'=>'purchase_order_cancel'],'issued'=>['confirm'=>'purchase_order_send','close'=>'purchase_order_cancel','cancel'=>'purchase_order_cancel'],'confirmed','partial'=>['close'=>'purchase_order_cancel','cancel'=>'purchase_order_cancel'],default=>[]})
@foreach($actions as $action=>$permission)
@can($permission)<x-backend.card :title="__('replenishment.action_'.$action)"><form method="post" action="{{ route('backend.admin.purchase-orders.action',['id'=>$po->id,'action'=>$action]) }}" class="grid gap-3">@csrf<input type="hidden" name="operation_key" value="{{ $key.'-'.$action }}"><input type="hidden" name="version" value="{{ $po->version }}"><input type="hidden" name="operation_point_of_sale_id" value="{{ $po->point_of_sale_id }}"><x-backend.input name="reason" :label="__('Reason')" required maxlength="500"/>@if($action==='approve')
<label class="flex items-center gap-3 min-h-11"><input type="checkbox" name="accept_warnings" value="1">{{ __('replenishment.accept_warnings') }}</label>
@endif
<button class="qpos-button qpos-button-md qpos-button-primary">{{ __('replenishment.action_'.$action) }}</button><p>{{ __('replenishment.no_auto_send') }}</p></form></x-backend.card>@endcan
@endforeach
@include('backend.replenishment.order-receive')
<x-backend.card :title="__('History')"><ul>@foreach($po->events as $e)<li>{{ $e->occurred_at->setTimezone('Africa/Douala')->format('d/m/Y H:i') }} Â· {{ __('replenishment.action_'.$e->action) }} Â· #{{ $e->user_id }} Â· {{ $e->reason }}</li>@endforeach</ul></x-backend.card>
</div>@endsection
