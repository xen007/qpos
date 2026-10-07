@extends('backend.master-tailwind')
@section('title', __('Stock transfers').' #'.$transfer->id)
@section('content')
<x-backend.card>
    <p class="font-semibold">{{ $source->name }} → {{ $destination->name }} · {{ __($transfer->status) }}</p>
    <p class="my-3">{{ $transfer->reason }}</p>
    <div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr><th>{{ __('Product') }}</th><th>{{ __('Requested quantity') }}</th></tr></thead><tbody>
        @foreach($items as $item)<tr><td class="p-2">{{ $item->sku }} · {{ $item->name }}</td><td class="p-2">{{ $item->quantity }}</td></tr>@endforeach
    </tbody></table></div>
    @if($transfer->status==='draft' && $canSource) @can('stock_transfer_dispatch')
        <form method="post" action="{{ route('backend.admin.stock.transfers.dispatch',$transfer->id) }}" class="my-4">@csrf<button class="qpos-button qpos-button-md qpos-button-primary">{{ __('Dispatch') }}</button></form>
        <form method="post" action="{{ route('backend.admin.stock.transfers.cancel',$transfer->id) }}" class="space-y-2">@csrf
            <label>{{ __('Cancellation reason') }}<input required maxlength="5000" name="reason" class="qpos-control w-full"></label>
            <button class="qpos-button qpos-button-md qpos-button-secondary">{{ __('Cancel draft') }}</button>
        </form>
    @endcan @endif
    @if(in_array($transfer->status,['dispatched','partial']))
    <form method="post" action="{{ route('backend.admin.stock.transfers.settle',$transfer->id) }}" class="space-y-4 mt-6">
        @csrf<input type="hidden" name="operation_key" value="{{ $key }}">
        <p class="text-sm text-qpos-muted">{{ __('Enter only physically received or returned quantities. Blank or zero leaves the remainder in transit. Already received stock needs a separate return transfer.') }}</p>
        <label class="block">{{ __('Settlement') }}<select name="kind" class="qpos-control mt-1 w-full">
            @if($canDestination)@can('stock_transfer_receive')<option value="receive">{{ __('Physical receipt') }}</option>@endcan @endif
            @if($canSource)@can('stock_transfer_dispatch')<option value="return">{{ __('Physical return to source') }}</option><option value="loss">{{ __('Documented transit loss') }}</option>@endcan @endif
        </select></label>
        <div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr><th>{{ __('Product / lot') }}</th><th>{{ __('Expiry') }}</th><th>{{ __('Cost') }}</th><th>{{ __('Received') }}</th><th>{{ __('Returned / lost') }}</th><th>{{ __('Remaining') }}</th><th>{{ __('Quantity to settle') }}</th></tr></thead><tbody>
            @foreach($allocations as $allocation)@php($remaining=\Brick\Math\BigDecimal::of($allocation->quantity)->minus($allocation->received_quantity)->minus($allocation->returned_quantity)->minus($allocation->lost_quantity))
            <tr><td class="p-2">{{ $allocation->name }} · {{ $allocation->batch_number ?: '#'.$allocation->product_batch_id }}</td><td class="p-2">{{ __($allocation->expiry_status) }} {{ $allocation->expires_on }}</td>
                <td class="p-2">{{ $allocation->unit_cost }} {{ $allocation->currency_code }}</td><td class="p-2">{{ $allocation->received_quantity }}</td><td class="p-2">{{ $allocation->returned_quantity }} / {{ $allocation->lost_quantity }}</td><td class="p-2">{{ $remaining }}</td>
                <td class="p-2"><input type="hidden" name="lines[{{ $loop->index }}][allocation_id]" value="{{ $allocation->id }}"><input aria-label="{{ __('Quantity to settle') }} {{ $allocation->name }}" name="lines[{{ $loop->index }}][quantity]" class="qpos-control w-32" inputmode="decimal" @disabled($remaining->isZero())></td></tr>
            @endforeach
        </tbody></table></div>
        <label class="block">{{ __('Reason / evidence') }}<textarea name="reason" required maxlength="5000" class="qpos-control mt-1 w-full"></textarea></label>
        <button class="qpos-button qpos-button-md qpos-button-primary">{{ __('Record settlement') }}</button>
    </form>
    @endif
    @if(!in_array($transfer->status,['dispatched','partial']) && $allocations->isNotEmpty())
    <div class="overflow-x-auto mt-6"><table class="w-full text-sm"><thead><tr><th>{{ __('Product / lot') }}</th><th>{{ __('Cost') }}</th><th>{{ __('Received') }}</th><th>{{ __('Returned / lost') }}</th><th>{{ __('Remaining') }}</th></tr></thead><tbody>
        @foreach($allocations as $allocation)<tr><td class="p-2">{{ $allocation->name }} · {{ $allocation->batch_number ?: '#'.$allocation->product_batch_id }}</td><td class="p-2">{{ $allocation->unit_cost }} {{ $allocation->currency_code }}</td>
            <td class="p-2">{{ $allocation->received_quantity }}</td><td class="p-2">{{ $allocation->returned_quantity }} / {{ $allocation->lost_quantity }}</td><td class="p-2">{{ \Brick\Math\BigDecimal::of($allocation->quantity)->minus($allocation->received_quantity)->minus($allocation->returned_quantity)->minus($allocation->lost_quantity) }}</td></tr>@endforeach
    </tbody></table></div>
    @endif
    <h2 class="font-semibold mt-6">{{ __('Settlement history') }}</h2>
    @forelse($receipts as $receipt)<p class="mt-2">#{{ $receipt->id }} · {{ __($receipt->kind) }} · {{ $receipt->occurred_at }} · {{ $receipt->reason }}</p>@empty<p class="mt-2">{{ __('No settlements yet.') }}</p>@endforelse
</x-backend.card>
@endsection
