@extends('backend.master-tailwind')

@section('title', __('Collection invoice #:id', ['id' => $transaction->id]))

@section('content')
    @php
        $symbol = currency()->symbol ?? '';
        $money = fn ($value) => $symbol . ' ' . number_format((float) $value, 2, '.', ',');
    @endphp

    <x-backend.card>
        <x-backend.invoice :title="__('Collection Invoice')" :order="$order">
            <x-slot:information>
                {{ __('Invoice ID') }} #{{ $transaction->id }}<br>
                {{ __('Sale ID') }} #{{ $order->id }}<br>
                {{ __('Sale Date') }}: {{ date('d/m/Y', strtotime($order->created_at)) }}<br>
                {{ __('Collection Date') }}: {{ date('d/m/Y', strtotime($transaction->created_at)) }}
            </x-slot:information>

            <x-slot:totals>
                <tr>
                    <th class="py-1 text-left font-normal text-qpos-muted">{{ __('Subtotal') }}:</th>
                    <td class="py-1 text-right tabular-nums">{{ $money($order->sub_total) }}</td>
                </tr>
                <tr>
                    <th class="py-1 text-left font-normal text-qpos-muted">{{ __('Discount') }}:</th>
                    <td class="py-1 text-right tabular-nums">{{ $money($order->discount) }}</td>
                </tr>
                <tr>
                    <th class="py-1 text-left text-qpos-ink">{{ __('Total') }}:</th>
                    <td class="py-1 text-right font-semibold tabular-nums text-qpos-ink">
                        {{ $money($order->total) }}</td>
                </tr>
                <tr>
                    <th class="py-1 text-left font-normal text-qpos-muted">{{ __('Previously Paid') }}:</th>
                    <td class="py-1 text-right tabular-nums">{{ __('Payment-time balance was not retained for this historical receipt.') }}</td>
                </tr>
                <tr>
                    <th class="py-1 text-left font-normal text-qpos-muted">{{ __('Collection Amount') }}:</th>
                    <td class="py-1 text-right tabular-nums">{{ $money($collection_amount) }}</td>
                </tr>
                <tr>
                    <th class="py-1 text-left font-normal text-qpos-muted">{{ __('Current balance') }}:</th>
                    <td class="py-1 text-right tabular-nums">{{ $money($order->due) }}</td>
                </tr>
            </x-slot:totals>
        </x-backend.invoice>

        <div class="mt-6 text-right">
            <button type="button" onclick="window.print()"
                class="qpos-button qpos-button-md qpos-button-primary inline-flex items-center gap-2 rounded-lg bg-qpos-brand px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90 print:hidden">
                <x-backend.icon name="fas fa-print" />
                {{ __('Print') }}
            </button>
        </div>
    </x-backend.card>
@endsection

@push('script')
    <script>
        window.addEventListener("load", () => window.print());
    </script>
@endpush
