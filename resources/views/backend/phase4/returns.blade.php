@extends('backend.master-tailwind')
@section('title', __('Sale returns'))
@section('content')
<x-backend.card :title="__('Sale returns')"><p>{{ __('Sale') }} #{{ $order->id }} · {{ $order->customer->name }} · {{ __('Total') }} {{ $order->total }} {{ $order->currency_code ?? __('Unknown') }}</p>
<form id="return-form" method="POST" action="{{ route('backend.admin.sales.correct',$order->id) }}" class="grid gap-4 mt-4">@csrf @include('backend.phase4.context')
<x-backend.select name="kind" id="return-kind" :label="__('Workflow')" :options="['refund'=>__('Refund'),'credit_note'=>__('Store credit'),'exchange'=>__('Exchange'),'cancel'=>__('Cancel sale')]" />
@foreach($order->products as $i=>$line)<div class="grid gap-2 border-b py-3"><p>{{ $line->product_label_snapshot ?? $line->product?->name }} · {{ __('Sold') }} {{ $line->quantity }} · {{ __('Already returned') }} {{ $line->returned_quantity }}</p><label><input type="checkbox" class="return-select" data-index="{{ $i }}">{{ __('Return this item') }}</label><input type="hidden" class="return-field" name="items[{{ $i }}][order_product_id]" value="{{ $line->id }}" disabled><label>{{ __('Quantity') }}<input class="qpos-control return-field" type="number" step="0.000001" min="0.000001" name="items[{{ $i }}][quantity]" value="{{ $line->quantity }}" disabled></label><label><input class="return-field" type="checkbox" name="items[{{ $i }}][saleable]" value="1" disabled>{{ __('Inspected and saleable') }}</label></div>@endforeach
<label><input type="checkbox" name="saleable" value="1">{{ __('All cancelled items inspected and saleable') }}</label>
<x-backend.select name="method" :label="__('Refund method')" :options="['cash'=>__('Cash'),'card'=>__('External card')]" />
<x-backend.input name="external_reference" :label="__('External reference')" maxlength="128" />
<x-backend.input name="reason" :label="__('Reason')" maxlength="255" required />
<button class="qpos-button qpos-button-md qpos-button-primary">{{ __('Validate correction or prepare exchange') }}</button></form>
@foreach($corrections as $c)<p class="mt-4"><a href="{{ route('backend.admin.corrections.document',$c->id) }}">{{ __('Corrective document') }} #{{ $c->id }} · {{ __($c->kind) }} · {{ $c->amount }} XAF</a></p>@endforeach
</x-backend.card>
@endsection
@push('script')<script>
document.querySelectorAll('.return-select').forEach(el=>el.addEventListener('change',()=>el.closest('div').querySelectorAll('.return-field').forEach(field=>field.disabled=!el.checked)));
document.getElementById('return-form').addEventListener('submit',event=>{
 if(document.getElementById('return-kind').value!=='exchange')return;event.preventDefault();const form=event.target;const d=new FormData(form);const items=[];
 document.querySelectorAll('.return-select:checked').forEach(el=>{const i=el.dataset.index;items.push({order_product_id:Number(d.get('items['+i+'][order_product_id]')),quantity:d.get('items['+i+'][quantity]'),saleable:d.get('items['+i+'][saleable]')==='1'});});
 if(!items.length)return;sessionStorage.setItem('qpos-exchange',JSON.stringify({order_id:{{ $order->id }},customer_id:{{ $order->customer_id }},shop_id:{{ $order->point_of_sale_id ?? 0 }},operation_key:d.get('operation_key'),kind:'exchange',reason:d.get('reason'),method:d.get('method'),external_reference:d.get('external_reference')||null,items}));window.location.href=@json(route('backend.admin.cart.index'));
});
</script>@endpush
