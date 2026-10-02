@extends('backend.master-tailwind')

@section('title', __('Invoice #:id', ['id' => $order->id]))

@section('content')
    @php
        $symbol = currency()->symbol ?? '';
        $money = fn ($value) => $symbol . ' ' . number_format((float) $value, 2, '.', ',');
    @endphp

    <x-backend.card>
        <x-backend.invoice :title="__('Invoice')" :order="$order">
            <x-slot:information>
                {{ __('Sale ID') }} #{{ $order->id }}<br>
                {{ __('Sale Date') }}: {{ date('d/m/Y', strtotime($order->created_at)) }}
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
                    <th class="py-1 text-left font-normal text-qpos-muted">{{ __('Paid') }}:</th>
                    <td class="py-1 text-right tabular-nums">{{ $money($order->paid + $order->change_amount) }}</td>
                </tr>
                @if ($order->change_amount > 0)
                    <tr>
                        <th class="py-1 text-left font-normal text-qpos-muted">{{ __('Change') }}:</th>
                        <td class="py-1 text-right tabular-nums">{{ $money($order->change_amount) }}</td>
                    </tr>
                @endif
                <tr>
                    <th class="py-1 text-left font-normal text-qpos-muted">{{ __('Due') }}:</th>
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
        window.addEventListener("load", window.print());
    </script>
@endpush
