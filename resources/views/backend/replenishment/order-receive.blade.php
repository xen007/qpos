@can('purchase_order_receive')
@if(in_array($po->status,['approved','issued','confirmed','partial'],true))
<x-backend.card :title="__('replenishment.action_receive')">
<p class="mb-4">{{ __('replenishment.receipt_help') }}</p>
<form method="post" action="{{ route('backend.admin.purchase-orders.receive',$po->id) }}" class="space-y-4">
@csrf
<input type="hidden" name="operation_key" value="{{ old('operation_key',$key.'-receive') }}">
<input type="hidden" name="version" value="{{ $po->version }}">
<input type="hidden" name="operation_point_of_sale_id" value="{{ $po->point_of_sale_id }}">
@foreach($po->items as $index=>$line)
<fieldset class="qpos-card p-4 grid gap-3 md:grid-cols-2">
<legend class="font-semibold">{{ $line->snapshot['name'] }} · {{ $line->snapshot['unit_label'] }}</legend>
<p class="md:col-span-2">{{ __('replenishment.remaining') }}: {{ App\Support\SaleFormat::quantityDisplay($quantities[$line->id]['remaining']) }}</p>
<input type="hidden" name="items[{{ $index }}][purchase_order_item_id]" value="{{ $line->id }}">
<label>{{ __('replenishment.quantity_received') }}
<input class="qpos-control w-full min-h-11" type="number" min="0" step="{{ ($line->snapshot['allows_fractional']??false)?'0.000001':'1' }}" name="items[{{ $index }}][quantity]" value="{{ old('items.'.$index.'.quantity','0') }}" required>
</label>
<label>{{ __('replenishment.batch_number') }}
<input class="qpos-control w-full min-h-11" name="items[{{ $index }}][batch_number]" maxlength="100" value="{{ old('items.'.$index.'.batch_number') }}">
</label>
<label>{{ __('replenishment.expiry') }}
<select class="qpos-control w-full min-h-11" name="items[{{ $index }}][expiry_status]">
@foreach(['unknown'=>__('replenishment.expiry_auto'),'dated'=>__('replenishment.expiry_dated'),'not_applicable'=>__('replenishment.expiry_none')] as $value=>$label)
<option value="{{ $value }}" @selected(old('items.'.$index.'.expiry_status','unknown')===$value)>{{ $label }}</option>
@endforeach
</select>
</label>
<label>{{ __('replenishment.expiry_date') }}
<input class="qpos-control w-full min-h-11" type="date" name="items[{{ $index }}][expires_on]" value="{{ old('items.'.$index.'.expires_on') }}">
</label>
</fieldset>
@endforeach
@can('purchase_order_surplus')
<label class="flex items-center gap-3 min-h-11"><input type="checkbox" name="accept_surplus" value="1" @checked(old('accept_surplus'))>{{ __('replenishment.accept_surplus') }}</label>
@endcan
<x-backend.input name="reason" :label="__('Reason')" maxlength="500" :value="old('reason')"/>
<button class="qpos-button qpos-button-md qpos-button-primary">{{ __('replenishment.action_receive') }}</button>
</form>
</x-backend.card>
@endif
@endcan
