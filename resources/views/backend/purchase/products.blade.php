@extends('backend.master-tailwind')
@section('title', __('Purchase'))
@section('content')
@php
    $knownBalance = in_array($purchase->payment_status, ['unpaid','partial','paid','cancelled','not_applicable'], true);
    $receivable = $purchase->point_of_sale_id && in_array($purchase->receipt_status, ['pending','partial'], true);
@endphp
<div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
    <div class="space-y-4 xl:col-span-2">
        <x-backend.card :title="__('Supplier')">
            <a class="text-qpos-brand underline" href="{{ route('backend.admin.suppliers.show', $purchase->supplier_id) }}">{{ $purchase->supplier?->name }}</a>
            <p class="mt-2 text-sm text-qpos-muted">{{ __('Receipt status') }}: {{ __($purchase->receipt_status ?? 'unknown') }} · {{ __('Payment status') }}: {{ __($purchase->payment_status ?? 'unknown') }}</p>
            <p class="text-sm text-qpos-muted">{{ __('Due date') }}: {{ $purchase->due_date ?? '—' }} · {{ __('Shop') }}: {{ $purchase->point_of_sale_id ?? '—' }}</p>
            @if($purchase->cancelled_at)<p class="mt-2">{{ __('Cancellation reason') }}: {{ $purchase->cancellation_reason }}</p>@endif
        </x-backend.card>
        @can('purchase_update')
        @if($purchase->receipt_status === 'pending' && $purchase->receipts->isEmpty() && $purchase->paymentAllocations->isEmpty())
        <x-backend.card :title="__('Amend before receipt')">
            <form method="POST" action="{{ route('backend.admin.purchase.amend',$purchase->id) }}" class="space-y-3">
                @csrf
                <input type="hidden" name="operation_point_of_sale_id" value="{{ $purchase->point_of_sale_id }}">
                <input type="hidden" name="idempotency_key" value="{{ (string)\Illuminate\Support\Str::uuid() }}">
                @foreach($purchase->items as $index=>$item)
                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <strong>{{ $item->product?->name }} · {{ $item->unit_label_snapshot }}</strong>
                    <input type="hidden" name="items[{{ $index }}][purchase_item_id]" value="{{ $item->id }}">
                    <label>{{ __('Ordered quantity') }}<input required type="number" step="any" min="0.000001" name="items[{{ $index }}][quantity]" class="qpos-control" value="{{ $item->entered_quantity }}"></label>
                    <label>{{ __('Actual unit cost') }}<input required type="number" step="any" min="0" name="items[{{ $index }}][unit_cost]" class="qpos-control" value="{{ $item->source_unit_cost }}"></label>
                </div>
                @endforeach
                <label class="block">{{ __('Due date') }}<input type="date" name="due_date" class="qpos-control" value="{{ $purchase->due_date }}"></label>
                <label class="block">{{ __('Reason') }}<input required name="reason" maxlength="255" class="qpos-control"></label>
                <button type="submit" class="qpos-button qpos-button-md qpos-button-primary">{{ __('Record amendment') }}</button>
            </form>
        </x-backend.card>
        @endif
        @endcan
        <x-backend.card :title="__('Purchase items')" :padded="false">
            <div class="overflow-x-auto p-4" tabindex="0" role="region" aria-label="{{ __('Purchase items') }}">
                <table class="qpos-table">
                    <thead><tr><th>{{ __('Product') }}</th><th>{{ __('Packaging') }}</th><th>{{ __('Actual unit cost') }}</th><th>{{ __('Ordered / received / remaining') }}</th><th>{{ __('Sub Total') }}</th></tr></thead>
                    <tbody>
                    @foreach($purchase->items as $item)
                        <tr>
                            <td>{{ $item->product?->name }}</td>
                            <td>{{ $item->unit_label_snapshot ?? $item->product?->unit?->short_name ?? '—' }} @if($item->factor_used)<span class="block text-xs text-qpos-muted">× {{ $item->factor_used }}</span>@endif</td>
                            <td class="tabular-nums">{{ $item->source_unit_cost ?? $item->purchase_price }}</td>
                            <td class="tabular-nums">{{ $item->entered_quantity ?? $item->quantity }} / {{ $purchase->receipt_status === 'unknown' ? __('Unknown receipt') : $item->received_quantity }} / {{ $purchase->receipt_status === 'unknown' ? '—' : $item->outstanding_quantity }}</td>
                            <td class="tabular-nums">{{ $item->source_line_amount ?? (string)\Brick\Math\BigDecimal::of((string)$item->purchase_price)->multipliedBy((string)$item->quantity)->toScale(6, \Brick\Math\RoundingMode::HalfUp) }}</td>
                        </tr>
                        @can('purchase_receive')
                        @if($receivable && \Brick\Math\BigDecimal::of($item->outstanding_quantity)->isPositive())
                        <tr><td colspan="5">
                            <form method="POST" action="{{ route('backend.admin.purchase.receive', $purchase->id) }}" class="grid grid-cols-1 gap-3 rounded-lg border border-qpos-line p-3 md:grid-cols-2">
                                @csrf
                                <input type="hidden" name="operation_point_of_sale_id" value="{{ $purchase->point_of_sale_id }}">
                                <input type="hidden" name="idempotency_key" value="{{ (string)\Illuminate\Support\Str::uuid() }}">
                                <input type="hidden" name="items[0][purchase_item_id]" value="{{ $item->id }}">
                                <label>{{ __('Receive quantity') }}<input required name="items[0][quantity]" type="number" min="0.000001" max="{{ $item->outstanding_quantity }}" step="any" class="qpos-control" value="{{ $item->outstanding_quantity }}"></label>
                                <label>{{ __('Actual unit cost') }}<input readonly required name="items[0][unit_cost]" type="number" min="0" step="any" class="qpos-control" value="{{ $item->source_unit_cost ?? $item->purchase_price }}"></label>
                                <label>{{ __('Expiry status') }}<select name="items[0][expiry_status]" class="qpos-control"><option value="unknown">{{ __('Unknown (blocked)') }}</option><option value="dated">{{ __('Dated') }}</option><option value="not_applicable">{{ __('Not applicable') }}</option></select></label>
                                <label>{{ __('Expiry date') }}<input name="items[0][expires_on]" type="date" class="qpos-control"></label>
                                <button class="qpos-button qpos-button-md qpos-button-primary md:col-span-2" type="submit">{{ __('Record receipt') }}</button>
                            </form>
                        </td></tr>
                        @endif
                        @endcan
                    @endforeach
                    </tbody>
                </table>
            </div>
        </x-backend.card>
    </div>
    <x-backend.card :title="__('Total')">
        <dl class="space-y-2 text-sm">
            @foreach([
                [__('Subtotal:'),$purchase->sub_total],[__('Tax:'),$purchase->tax],
                [__('Discount:'),$purchase->discount_value],[__('Shipping:'),$purchase->shipping],
                [__('Total:'),$purchase->grand_total],[__('Paid:'),$knownBalance ? $purchase->paid_amount : __('Unknown balance')],
                [__('Amount due:'),$knownBalance ? $purchase->due_amount : __('Unknown balance')]
            ] as [$label,$value])
            <div class="flex justify-between gap-4"><dt class="text-qpos-muted">{{ $label }}</dt><dd class="tabular-nums">{{ $value }}</dd></div>
            @endforeach
        </dl>
        <p class="mt-3 text-sm text-qpos-muted">{{ $purchase->currency_code ?? __('Historical currency unknown') }}</p>
    </x-backend.card>
    @if($purchase->receipts->isNotEmpty())
    <div class="space-y-4 xl:col-span-3">
        <x-backend.card :title="__('Receipt history')">
            @foreach($purchase->receipts as $receipt)
            <div class="mb-3 rounded-lg border border-qpos-line p-3">
                <p class="text-sm">#{{ $receipt->id }} · {{ $receipt->received_at?->format('d/m/Y H:i') }} · {{ __($receipt->status) }}</p>
                @foreach($receipt->items as $receivedItem)
                <p class="mt-2 text-sm">{{ __('Batch') }} #{{ $receivedItem->product_batch_id }} · {{ $receivedItem->unit_label_snapshot }} × {{ $receivedItem->entered_quantity }} · {{ __('Base quantity') }}: {{ $receivedItem->base_quantity }} · {{ __('Source amount') }}: {{ $receivedItem->source_line_amount_exact }} {{ $purchase->currency_code }} · {{ __('Expiry status') }}: {{ __($receivedItem->expiry_status) }} {{ $receivedItem->expires_on?->format('d/m/Y') }}</p>
                @endforeach
            </div>
            @endforeach
        </x-backend.card>
    </div>
    @endif
    @if($knownBalance && $purchase->point_of_sale_id)
    <div class="space-y-4 xl:col-span-3">
        <x-backend.card :title="__('Supplier payments')">
            @can('purchase_pay')
            @if(!$purchase->cancelled_at && \Brick\Math\BigDecimal::of($purchase->due_amount ?? '0')->isPositive())
            <form method="POST" action="{{ route('backend.admin.purchase.pay', $purchase->id) }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
                @csrf
                <input type="hidden" name="operation_point_of_sale_id" value="{{ $purchase->point_of_sale_id }}">
                <input type="hidden" name="idempotency_key" value="{{ (string)\Illuminate\Support\Str::uuid() }}">
                <label>{{ __('Amount') }}<input required name="amount" type="number" min="0.000001" max="{{ $purchase->due_amount }}" step="any" class="qpos-control"></label>
                <label>{{ __('Method') }}<select name="method" class="qpos-control">@foreach(app(App\Services\PaymentMethodService::class)->choices($purchase->point_of_sale_id) as $key=>$label)<option value="{{ $key }}">{{ __($label) }}</option>@endforeach</select></label>
                <label>{{ __('Reference') }}<input name="external_reference" maxlength="128" class="qpos-control"></label>
                <button class="qpos-button qpos-button-md qpos-button-primary self-end" type="submit">{{ __('Record payment') }}</button>
            </form>
            @endif
            @endcan
            <div class="mt-4 space-y-3">
            @forelse($purchase->paymentAllocations as $allocation)
                <div class="rounded-lg border border-qpos-line p-3">
                    <p class="text-sm tabular-nums">#{{ $allocation->payment_id }} · {{ $allocation->payment?->occurred_at?->format('d/m/Y H:i') }} · {{ $allocation->payment?->direction === 'incoming' ? '−' : '+' }}{{ $allocation->amount }} {{ $purchase->currency_code }} · {{ __($allocation->payment?->method ?? '') }}</p>
                    @if($allocation->payment?->reason)<p class="text-sm text-qpos-muted">{{ $allocation->payment->reason }}</p>@endif
                    @can('purchase_pay')
                    @if($allocation->payment?->direction === 'outgoing' && !$allocation->payment->reversal && !$purchase->cancelled_at)
                    <form method="POST" action="{{ route('backend.admin.purchase.payments.reverse', [$purchase->id,$allocation->payment_id]) }}" class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-4">
                        @csrf
                        <input type="hidden" name="operation_point_of_sale_id" value="{{ $purchase->point_of_sale_id }}">
                        <label>{{ __('Reversal reason') }}<input required name="reason" maxlength="255" class="qpos-control"></label>
                        <label>{{ __('Method') }}<select name="method" class="qpos-control">@foreach(app(App\Services\PaymentMethodService::class)->choices($purchase->point_of_sale_id) as $key=>$label)<option value="{{ $key }}">{{ __($label) }}</option>@endforeach</select></label>
                        <label>{{ __('Reference') }}<input name="external_reference" maxlength="128" class="qpos-control"></label>
                        <button class="qpos-button qpos-button-md qpos-button-secondary self-end" type="submit">{{ __('Record refund / reversal') }}</button>
                    </form>
                    @endif
                    @endcan
                </div>
            @empty
                <p class="text-sm text-qpos-muted">{{ __('No supplier payment recorded.') }}</p>
            @endforelse
            </div>
        </x-backend.card>
        @can('purchase_cancel')
        @if(!$purchase->cancelled_at && $purchase->receipt_status !== 'unknown')
        <x-backend.card :title="__('Cancel purchase')">
            <form method="POST" action="{{ route('backend.admin.purchase.cancel', $purchase->id) }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <input type="hidden" name="operation_point_of_sale_id" value="{{ $purchase->point_of_sale_id }}">
                <label class="flex-1">{{ __('Cancellation reason') }}<input required name="reason" maxlength="255" class="qpos-control"></label>
                <button class="qpos-button qpos-button-md qpos-button-danger" type="submit">{{ __('Cancel purchase') }}</button>
            </form>
            <p class="mt-2 text-sm text-qpos-muted">{{ __('Cancellation requires untouched lots and reversed payments.') }}</p>
        </x-backend.card>
        @endif
        @endcan
    </div>
    @endif
</div>
@endsection
