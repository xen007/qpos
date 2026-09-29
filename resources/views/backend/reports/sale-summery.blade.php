@extends('backend.master-tailwind')

@section('title', __('Sales Summary'))

@section('page-actions')
    {{-- Filtre de periode natif (le selecteur daterangepicker n'est plus charge) :
         memes parametres start_date / end_date que l'ancien bouton. --}}
    <form method="get" action="{{ route('backend.admin.sale.summery') }}" class="flex flex-wrap items-end gap-2">
        <div>
            <label for="start_date" class="block text-xs font-medium text-qpos-muted">{{ __('Date range from') }}</label>
            <input type="date" id="start_date" name="start_date" value="{{ $start_date_input }}"
                class="mt-1 rounded-lg border border-qpos-line bg-qpos-surface px-3 py-2 text-sm text-qpos-ink">
        </div>

        <div>
            <label for="end_date" class="block text-xs font-medium text-qpos-muted">{{ __('Date range to') }}</label>
            <input type="date" id="end_date" name="end_date" value="{{ $end_date_input }}"
                class="mt-1 rounded-lg border border-qpos-line bg-qpos-surface px-3 py-2 text-sm text-qpos-ink">
        </div>

        <button type="submit"
            class="rounded-lg bg-qpos-brand px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90">
            {{ __('Apply') }}
        </button>

        @if (request()->hasAny(['start_date', 'end_date']))
            <a href="{{ route('backend.admin.sale.summery') }}"
                class="rounded-lg border border-qpos-line px-4 py-2 text-sm font-medium text-qpos-muted transition hover:bg-qpos-page">
                {{ __('Reset') }}
            </a>
        @endif
    </form>
@endsection

@section('content')
    <x-backend.card :title="__('Sales Summary')" :subtitle="$start_date . ' - ' . $end_date">
        <table class="w-full text-sm">
            <tbody>
                <tr class="border-b border-qpos-line/60">
                    <th class="py-2 text-left font-normal text-qpos-muted">{{ __('Subtotal:') }}</th>
                    <td class="py-2 text-right tabular-nums text-qpos-ink">
                        {{ currency()->symbol ?? '' }} {{ number_format($sub_total, 2) }}</td>
                </tr>
                <tr class="border-b border-qpos-line/60">
                    <th class="py-2 text-left font-normal text-qpos-muted">{{ __('Total Discount:') }}</th>
                    <td class="py-2 text-right tabular-nums text-qpos-ink">
                        {{ currency()->symbol ?? '' }} {{ number_format($discount, 2) }}</td>
                </tr>
                <tr class="border-b border-qpos-line/60">
                    <th class="py-2 text-left font-normal text-qpos-muted">{{ __('Total Sold:') }}</th>
                    <td class="py-2 text-right tabular-nums text-qpos-ink">
                        {{ currency()->symbol ?? '' }} {{ number_format($total, 2) }}</td>
                </tr>
                <tr class="border-b border-qpos-line/60">
                    <th class="py-2 text-left font-normal text-qpos-muted">{{ __('Customer Paid:') }}</th>
                    <td class="py-2 text-right tabular-nums text-qpos-ink">
                        {{ currency()->symbol ?? '' }} {{ number_format($paid, 2) }}</td>
                </tr>
                <tr>
                    <th class="py-2 text-left text-qpos-ink">{{ __('Customer Due:') }}</th>
                    <td class="py-2 text-right font-semibold tabular-nums text-qpos-ink">
                        {{ currency()->symbol ?? '' }} {{ number_format($due, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="mt-6 text-right">
            <button type="button" onclick="window.print()"
                class="inline-flex items-center gap-2 rounded-lg bg-qpos-brand px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90 print:hidden">
                <i class="fas fa-print" aria-hidden="true"></i>
                {{ __('Print') }}
            </button>
        </div>
    </x-backend.card>
@endsection
