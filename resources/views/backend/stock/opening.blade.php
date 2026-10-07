@extends('backend.master-tailwind')
@section('title', __('Review opening'))
@section('content')
<x-backend.card>
    <p class="font-semibold">{{ $stock->product->sku }} · {{ $stock->product->name }}</p>
    <p class="my-3">{{ __('Blocked quantity') }} : {{ $stock->unallocated_opening_quantity }}</p>
    <p class="mb-4 text-sm text-qpos-muted">{{ __('Approval preserves total stock. An expired lot remains unavailable. Enter only quantities supported by the evidence.') }}</p>
    <form method="post" action="{{ route('backend.admin.stock.openings.approve',$stock->product_id) }}" class="space-y-4">@csrf
        <input type="hidden" name="operation_key" value="{{ old('operation_key',$key) }}">
        @if($selectedPointOfSale)<input type="hidden" name="operation_point_of_sale_id" value="{{ $selectedPointOfSale->id }}">@endif
        <div class="grid gap-4 lg:grid-cols-2">
            <label>{{ __('Quantity to approve (base unit)') }}<input required name="quantity" value="{{ old('quantity') }}" inputmode="decimal" class="qpos-control mt-1 w-full"></label>
            <label>{{ __('Documented lot identifier') }}<input required maxlength="255" name="batch_number" value="{{ old('batch_number') }}" class="qpos-control mt-1 w-full"></label>
            <label>{{ __('Evidenced cost per base unit') }}<input required name="unit_cost" value="{{ old('unit_cost') }}" inputmode="decimal" class="qpos-control mt-1 w-full"></label>
            <label>{{ __('Evidence currency (no conversion)') }}<select name="currency_code" required class="qpos-control mt-1 w-full"><option value="">{{ __('Choose currency') }}</option><option value="XAF" @selected(old('currency_code')==='XAF')>XAF</option><option value="BDT" @selected(old('currency_code')==='BDT')>BDT</option></select></label>
            <label>{{ __('Expiry classification') }}<select required name="expiry_status" class="qpos-control mt-1 w-full"><option value="">{{ __('Choose expiry classification') }}</option><option value="dated" @selected(old('expiry_status')==='dated')>{{ __('Dated') }}</option><option value="not_applicable" @selected(old('expiry_status')==='not_applicable')>{{ __('Not applicable (documented)') }}</option></select></label>
            <label>{{ __('Expiration date (dated lots only)') }}<input type="date" name="expires_on" value="{{ old('expires_on') }}" class="qpos-control mt-1 w-full"></label>
        </div>
        <label class="block">{{ __('Reason') }}<textarea required name="reason" maxlength="5000" class="qpos-control mt-1 w-full">{{ old('reason') }}</textarea></label>
        <label class="block">{{ __('Documentary evidence / reference') }}<textarea required name="evidence" maxlength="5000" class="qpos-control mt-1 w-full">{{ old('evidence') }}</textarea></label>
        <button class="qpos-button qpos-button-md qpos-button-primary">{{ __('Approve evidenced quantity') }}</button>
    </form>
</x-backend.card>
@endsection
