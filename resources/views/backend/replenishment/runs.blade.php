@extends('backend.master-tailwind')
@section('title',__('replenishment.generation_history'))
@section('content')
<x-backend.card :title="__('replenishment.generation_history').' · '.$shop->name">
<a class="qpos-button qpos-button-md qpos-button-secondary mb-4" href="{{ route('backend.admin.replenishment.suggestions',['operation_point_of_sale_id'=>$shop->id]) }}">{{ __('replenishment.suggestions') }}</a>
@if($run)
<p>#{{ $run->id }} · {{ __('replenishment.run_'.$run->state) }} · {{ $run->slot }}</p>
@if($run->last_error)<p role="alert">{{ __('replenishment.error_'.$run->last_error) }} · {{ __('replenishment.attempts') }} {{ $run->attempts }}</p>@endif
<div class="overflow-x-auto"><table class="qpos-table"><thead><tr><th>{{ __('Product') }}</th><th>{{ __('Status') }}</th><th>{{ __('replenishment.orders') }}</th><th>{{ __('replenishment.quantity_packs') }}</th></tr></thead><tbody>
@foreach($items as $item)
@php($snapshot=json_decode($item->snapshot,true,512,JSON_THROW_ON_ERROR))
<tr><td>{{ $snapshot['name'] }}</td><td>{{ __('replenishment.item_'.$item->state) }}</td><td>
@if($item->purchase_order_id)<a class="qpos-button qpos-button-md qpos-button-secondary" href="{{ route('backend.admin.purchase-orders.show',$item->purchase_order_id) }}">{{ $item->reference }}</a>@else — @endif
</td><td>{{ App\Support\SaleFormat::quantityDisplay($snapshot['quantity_packs']) }}</td></tr>
@endforeach
</tbody></table></div>{{ $items->links() }}
@else
<div class="overflow-x-auto"><table class="qpos-table"><thead><tr><th>#</th><th>{{ __('Date') }}</th><th>{{ __('Status') }}</th><th>{{ __('replenishment.schedule') }}</th></tr></thead><tbody>
@foreach($runs as $row)<tr><td><a class="qpos-button qpos-button-md qpos-button-secondary" href="{{ route('backend.admin.replenishment.run',['id'=>$row->id,'operation_point_of_sale_id'=>$shop->id]) }}">{{ $row->id }}</a></td><td>{{ Carbon\CarbonImmutable::parse($row->created_at,'UTC')->setTimezone('Africa/Douala')->format('d/m/Y H:i') }}</td><td>{{ __('replenishment.run_'.$row->state) }}</td><td>{{ $row->slot }}</td></tr>@endforeach
</tbody></table></div>{{ $runs->links() }}
@endif
</x-backend.card>
@endsection
