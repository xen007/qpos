@extends('backend.master-tailwind')

@section('title', __('Dashboard'))

@section('content')
    @php
        $currencySymbol = currency()?->symbol ?? '';
        $formatMoney = fn ($amount) => trim($currencySymbol . ' ' . number_format((float) $amount, 2, '.', ','));
    @endphp

    @can('dashboard_view')
        {{-- Totaux de la periode --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-backend.stat-card :label="__('Sale Subtotal')" :value="$formatMoney($sub_total)" icon="fas fa-cog" />
            <x-backend.stat-card :label="__('Sale Discount')" :value="$formatMoney($discount)" icon="fas fa-thumbs-up" />
            <x-backend.stat-card :label="__('Sale')" :value="$formatMoney($total)" icon="fas fa-shopping-cart" />
            <x-backend.stat-card :label="__('Sale Due')" :value="$formatMoney($due)" icon="fas fa-users" />
        </div>

        {{-- Compteurs --}}
        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-backend.stat-card :label="__('Customers')" :value="$total_customer" icon="fas fa-users"
                :href="route('backend.admin.customers.index')" />
            <x-backend.stat-card :label="__('Products')" :value="$total_product" icon="fas fa-chart-bar"
                :href="route('backend.admin.products.index')" />
            <x-backend.stat-card :label="__('Sale')" :value="$total_order" icon="fas fa-user-plus"
                :href="route('backend.admin.orders.index')" />
            <x-backend.stat-card :label="__('Sale Item')" :value="$total_sale_item" icon="fas fa-chart-pie"
                :href="route('backend.admin.orders.index')" />
        </div>

        {{-- Graphiques --}}
        <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
            <x-backend.card :title="__('Daily Total Sales')" :subtitle="__('Date range from') . ' ' . $dateFrom . ' ' . __('Date range to') . ' ' . $dateTo">
                <x-slot:actions>
                    <form method="get" action="{{ route('backend.admin.dashboard') }}"
                        class="flex flex-wrap items-end gap-2">
                        <div>
                            <label for="date_from"
                                class="block text-xs font-medium text-qpos-muted">{{ __('Date range from') }}</label>
                            <input type="date" id="date_from" name="date_from" value="{{ $dateFrom }}"
                                class="qpos-control mt-1 rounded-lg border border-qpos-line bg-qpos-surface px-3 py-2 text-sm text-qpos-ink">
                        </div>
                        <div>
                            <label for="date_to"
                                class="block text-xs font-medium text-qpos-muted">{{ __('Date range to') }}</label>
                            <input type="date" id="date_to" name="date_to" value="{{ $dateTo }}"
                                class="qpos-control mt-1 rounded-lg border border-qpos-line bg-qpos-surface px-3 py-2 text-sm text-qpos-ink">
                        </div>
                        <button type="submit"
                            class="qpos-button qpos-button-md qpos-button-primary rounded-lg bg-qpos-brand px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90">
                            {{ __('Apply') }}
                        </button>
                        @if (request()->hasAny(['date_from', 'date_to', 'daterange']))
                            <a href="{{ route('backend.admin.dashboard') }}"
                                class="rounded-lg border border-qpos-line px-4 py-2 text-sm font-medium text-qpos-muted transition hover:bg-qpos-page">
                                {{ __('Reset') }}
                            </a>
                        @endif
                    </form>
                </x-slot:actions>

                <div class="relative h-80">
                    <canvas id="dailySaleLineChart" role="img" aria-label="{{ __('Daily Total Sales') }}"></canvas>
                </div>
            </x-backend.card>

            <x-backend.card :title="__('Monthly Total Sales')" :subtitle="__('for') . ' ' . $currentYear">
                <div class="relative h-80">
                    <canvas id="barChartYear" role="img" aria-label="{{ __('Monthly Total Sales') }}"></canvas>
                </div>
            </x-backend.card>
        </div>
    @endcan
@endsection

@push('script')
    @can('dashboard_view')
        <script type="application/json" id="qpos-dashboard-chart-data">
            {!! json_encode(
                [
                    'dates' => $dates,
                    'dailySales' => $totalAmounts,
                    'months' => $months,
                    'monthlySales' => $totalAmountMonth,
                    'salesLabel' => __('Sales'),
                ],
                JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
            ) !!}
        </script>
        @vite('resources/js/dashboard.js')
    @endcan
@endpush
