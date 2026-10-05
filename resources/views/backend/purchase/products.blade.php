@extends('backend.master-tailwind')

@section('title', __('Purchase'))

@section('content')
    {{-- Page de detail : les lignes et les totaux sont rendus par le serveur,
         aucun plugin n'est necessaire (l'ancienne table portait un id
         "datatables" sans initialisation). --}}
    <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div class="space-y-4 xl:col-span-2">
            <x-backend.card :title="__('Supplier')">
                <p class="text-sm text-qpos-ink">
                    <span class="font-semibold">{{ __('Name') }}:</span> {{ $purchase->supplier->name }}
                </p>
            </x-backend.card>

            <x-backend.card :title="__('Product')" :padded="false">
                <div class="overflow-x-auto p-4 sm:p-6" tabindex="0" role="region" aria-label="{{ __('Tableau') }}">
                    <table class="w-full border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-qpos-line text-left text-xs font-semibold uppercase tracking-wide text-qpos-muted">
                                <th class="px-3 py-3">{{ __('Line') }}</th>
                                <th class="px-3 py-3">{{ __('Product') }}</th>
                                <th class="px-3 py-3">{{ __('Purchase Price') }} {{ currency()->symbol ?? '' }}</th>
                                <th class="px-3 py-3">{{ __('Quantity') }}</th>
                                <th class="px-3 py-3 text-right">{{ __('Sub Total') }} {{ currency()->symbol ?? '' }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($purchase->items as $key => $item)
                                <tr class="border-b border-qpos-line/60 last:border-0">
                                    <td class="px-3 py-3 text-qpos-muted">{{ $key + 1 }}</td>
                                    <td class="px-3 py-3 text-qpos-ink">{{ $item->product->name }}</td>
                                    <td class="px-3 py-3 tabular-nums text-qpos-ink">
                                        {{ number_format($item->purchase_price, 2) }}</td>
                                    <td class="px-3 py-3 text-qpos-ink">
                                        {{ $item->quantity }} {{ optional($item->product->unit)->short_name }}</td>
                                    <td class="px-3 py-3 text-right tabular-nums text-qpos-ink">
                                        {{ number_format($item->purchase_price * $item->quantity, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-backend.card>

            <p class="whitespace-pre-line text-sm text-qpos-muted">{{ $purchase->note ?? '' }}</p>
        </div>

        <x-backend.card :title="__('Total')">
            <dl class="space-y-2 text-sm">
                @foreach ([
                    ['label' => __('Subtotal:'), 'value' => $purchase->sub_total],
                    ['label' => __('Tax:'), 'value' => $purchase->tax],
                    ['label' => __('Discount:'), 'value' => $purchase->discount_value],
                    ['label' => __('Shipping:'), 'value' => $purchase->shipping],
                    ['label' => __('Total:'), 'value' => $purchase->grand_total],
                ] as $line)
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-qpos-muted">{{ $line['label'] }}</dt>
                        <dd class="tabular-nums text-qpos-ink">
                            {{ number_format($line['value'], 2, '.', ',') }}</dd>
                    </div>
                @endforeach
            </dl>
        </x-backend.card>
    </div>
@endsection
