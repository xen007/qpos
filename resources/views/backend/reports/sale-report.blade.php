@extends('backend.master-tailwind')

@section('title', __('Sales Report'))

@section('page-actions')
    {{-- Filtre de periode natif (le daterangepicker n'est plus charge) : memes
         parametres start_date / end_date que l'ancien bouton. --}}
    <form method="get" action="{{ route('backend.admin.sale.report') }}" class="flex flex-wrap items-end gap-2">
        <div>
            <label for="start_date" class="block text-xs font-medium text-qpos-muted">{{ __('Date range from') }}</label>
            <input type="date" id="start_date" name="start_date" value="{{ $start_date_input }}"
                class="qpos-control mt-1 rounded-lg border border-qpos-line bg-qpos-surface px-3 py-2 text-sm text-qpos-ink">
        </div>

        <div>
            <label for="end_date" class="block text-xs font-medium text-qpos-muted">{{ __('Date range to') }}</label>
            <input type="date" id="end_date" name="end_date" value="{{ $end_date_input }}"
                class="qpos-control mt-1 rounded-lg border border-qpos-line bg-qpos-surface px-3 py-2 text-sm text-qpos-ink">
        </div>

        <button type="submit"
            class="qpos-button qpos-button-md qpos-button-primary rounded-lg bg-qpos-brand px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90">
            {{ __('Apply') }}
        </button>

        @if (request()->hasAny(['start_date', 'end_date']))
            <a href="{{ route('backend.admin.sale.report') }}"
                class="rounded-lg border border-qpos-line px-4 py-2 text-sm font-medium text-qpos-muted transition hover:bg-qpos-page">
                {{ __('Reset') }}
            </a>
        @endif
    </form>
@endsection

@section('content')
    <x-backend.card :title="__('Sales Report')" :subtitle="$start_date . ' - ' . $end_date" :padded="false">
        <div class="overflow-x-auto p-4 sm:p-6" tabindex="0" role="region" aria-label="{{ __('Tableau') }}">
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="border-b border-qpos-line text-left text-xs font-semibold uppercase tracking-wide text-qpos-muted">
                        <th data-orderable="false" class="px-3 py-3">#</th>
                        <th class="px-3 py-3">{{ __('Sale ID') }}</th>
                        <th class="px-3 py-3">{{ __('Customer') }}</th>
                        <th class="px-3 py-3">{{ __('Date') }}</th>
                        <th class="px-3 py-3">{{ __('Item') }}</th>
                        <th class="px-3 py-3 text-right">{{ __('Sub Total') }} {{ currency()->symbol ?? '' }}</th>
                        <th class="px-3 py-3 text-right">{{ __('Discount') }} {{ currency()->symbol ?? '' }}</th>
                        <th class="px-3 py-3 text-right">{{ __('Total') }} {{ currency()->symbol ?? '' }}</th>
                        <th class="px-3 py-3 text-right">{{ __('Paid') }} {{ currency()->symbol ?? '' }}</th>
                        <th class="px-3 py-3 text-right">{{ __('Due') }} {{ currency()->symbol ?? '' }}</th>
                        <th class="px-3 py-3">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $index => $order)
                        <tr class="border-b border-qpos-line/60">
                            <td class="px-3 py-2">{{ $index + 1 }}</td>
                            <td class="px-3 py-2">#{{ $order->id }}</td>
                            <td class="px-3 py-2">{{ $order->customer->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $order->created_at->format('d-m-Y') }}</td>
                            <td class="px-3 py-2">{{ $order->total_item }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">
                                {{ number_format($order->sub_total, 2, '.', ',') }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">
                                {{ number_format($order->discount, 2, '.', ',') }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">
                                {{ number_format($order->total, 2, '.', ',') }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">
                                {{ number_format($order->paid, 2, '.', ',') }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">
                                {{ number_format($order->due, 2, '.', ',') }}</td>
                            <td class="px-3 py-2">
                                {{-- Memes classes que statusBadge() de table-actions.js. --}}
                                <span
                                    class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $order->status ? 'bg-qpos-brand text-white' : 'border border-qpos-line bg-qpos-page text-qpos-muted' }}">
                                    {{ $order->status ? __('Paid') : __('Due') }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        {{-- La table compte onze colonnes (l'ancienne version annoncait 7). --}}
                        <tr>
                            <td colspan="11" class="px-3 py-6 text-center text-qpos-muted">
                                {{ __('No sells found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-backend.card>

    <div class="mt-4 text-right print:hidden">
        <button type="button" onclick="window.print()"
            class="qpos-button qpos-button-md qpos-button-primary inline-flex items-center gap-2 rounded-lg bg-qpos-brand px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90">
            <x-backend.icon name="fas fa-print" />
            {{ __('Print') }}
        </button>
    </div>
@endsection
