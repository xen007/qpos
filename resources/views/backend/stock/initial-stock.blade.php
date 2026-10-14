@extends('backend.master-tailwind')
@section('title',__('Documented startup stock'))
@section('content')
@include('backend.replenishment.quick-product')
<div class="space-y-6 qpos-touch-workspace">
<x-backend.card :title="__('Documented startup stock')">
<p>{{ __('Enter goods already present before using this product in this store. Enter each lot separately with documentary cost and expiry evidence. This does not create a purchase, supplier debt or cash movement.') }}</p>
<p class="mt-2">{{ __('After the first operational movement, use a purchase for new deliveries or an inventory for a physical count. Historical blocked openings use their separate approval screen.') }}</p>
<a class="qpos-button qpos-button-md qpos-button-secondary mt-3" href="{{ route('backend.admin.stock.openings') }}">{{ __('Openings awaiting approval') }}</a>
<form method="get" class="flex flex-wrap gap-3 mt-4"><x-backend.input name="search" :label="__('Search')" :value="request('search')" maxlength="255" /><button class="qpos-button qpos-button-md qpos-button-secondary self-end">{{ __('Search') }}</button></form>
<div class="max-h-80 overflow-y-auto mt-4"><ul>
@foreach($products as $p)<li class="py-2"><a class="qpos-button qpos-button-md qpos-button-secondary" href="{{ route('backend.admin.stock.initial',['product_id'=>$p->id]) }}">{{ $p->sku }} · {{ $p->name }}</a></li>@endforeach
</ul></div>{{ $products->links() }}
</x-backend.card>
@if($selected)
<x-backend.card :title="$selected->name">
@php($known=$classes[$selected->id]??null)
@if($known==='conflict')
<p role="alert">{{ __('This product has conflicting historical expiry classifications. Reconcile its lots before entering new stock.') }}</p>
@else
<form method="post" action="{{ route('backend.admin.stock.initial.store') }}" class="grid gap-4 md:grid-cols-2">@csrf
<input type="hidden" name="operation_key" value="{{ old('operation_key',$key) }}"><input type="hidden" name="product_id" value="{{ $selected->id }}"><input type="hidden" name="operation_point_of_sale_id" value="{{ $shop->id }}"><input type="hidden" name="currency_code" value="XAF">
<x-backend.input name="quantity" :label="__('Base quantity')" type="number" min="{{ $selected->allows_fractional?'0.000001':'1' }}" step="{{ $selected->allows_fractional?'0.000001':'1' }}" required />
<x-backend.input name="unit_cost" :label="__('Documented cost per base unit (XAF)')" type="number" min="0" step="0.000001" required />
<x-backend.input name="batch_number" :label="__('Batch number')" maxlength="255" required />
<x-backend.input name="received_on" :label="__('Stock present on')" type="date" :value="now('Africa/Douala')->toDateString()" :max="now('Africa/Douala')->toDateString()" required />
<x-backend.select name="expiry_status" :label="__('Expiry status')" :options="$known?[$known=>__($known==='dated'?'Dated':'Not applicable')]:['dated'=>__('Dated'),'not_applicable'=>__('Not applicable')]" :selected="$known" required />
<x-backend.input name="expires_on" :label="__('Expiry date')" type="date" />
<p class="text-sm md:col-span-2">{{ __('Known lots use one expiry classification per product; dates can differ by lot. A dated lot requires its actual expiry date. Expired stock remains unavailable for normal sales.') }}</p>
<x-backend.input name="reason" :label="__('Reason')" maxlength="5000" required />
<x-backend.input name="evidence" :label="__('Documentary evidence')" maxlength="5000" required />
<button class="qpos-button qpos-button-md qpos-button-primary md:col-span-2">{{ __('Record startup lot') }}</button>
</form>
@endif
</x-backend.card>
@endif
<x-backend.card :title="__('Startup stock history')"><div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr><th>#</th><th>{{ __('Product') }}</th><th>{{ __('Quantity') }}</th><th>{{ __('Unit cost') }}</th><th>{{ __('Documentary evidence') }}</th></tr></thead><tbody>
@forelse($entries as $e)<tr><td>{{ $e->id }}</td><td>{{ $e->name }}</td><td>{{ App\Support\SaleFormat::quantityDisplay($e->quantity) }}</td><td>{{ App\Support\SaleFormat::moneyDisplay($e->unit_cost) }} {{ $e->currency_code }}</td><td>{{ $e->evidence }}</td></tr>@empty<tr><td colspan="5">{{ __('No documented startup stock in this store.') }}</td></tr>@endforelse
</tbody></table></div>{{ $entries->links() }}</x-backend.card>
</div>
@endsection
