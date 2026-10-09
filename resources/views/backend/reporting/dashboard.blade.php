@extends('backend.master-tailwind')
@section('title', __('Dashboard'))
@section('content')
<div class="qpos-touch-workspace">
    @include('backend.reporting.filters')
    @include('backend.reporting.navigation')
    <p class="text-sm text-qpos-muted">{{ __('reporting.native_notice') }}</p>
    <div class="my-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-backend.stat-card :label="__('reporting.net_sales')" :value="\App\Support\SaleFormat::moneyDisplay($summary['net_sales']).' XAF'" icon="fas fa-tags" />
        <x-backend.stat-card :label="__('reporting.gross_margin')" :value="\App\Support\SaleFormat::moneyDisplay($summary['gross_margin']).' XAF'" icon="fas fa-chart-bar" :hint="$summary['incomplete_cost'] ? __('reporting.incomplete_margin') : null" />
        <x-backend.stat-card :label="__('Customers served')" :value="$clients" icon="fas fa-user-circle" :hint="__('Registered customers in selected period')" />
        <x-backend.stat-card :label="__('Low stock')" :value="$lowStock" icon="fas fa-box" :hint="__('Current available stock below 10 base units, excluding shortages')" />
    </div>
    <a class="qpos-button qpos-button-md qpos-button-secondary" href="{{ route('backend.admin.reporting.statistics', request()->query()) }}">{{ __('Statistics') }}</a>
    <x-backend.card :title="__('reporting.daily_sales')"><div class="relative h-80"><canvas data-report-chart="daily" role="img" aria-label="{{ __('reporting.daily_sales') }}"></canvas></div></x-backend.card>
    <x-backend.card :title="__('reporting.peaks')"><div class="relative h-80"><canvas data-report-chart="hours" role="img" aria-label="{{ __('reporting.peaks') }}"></canvas></div></x-backend.card>
</div>
@endsection
@push('script')
    <script type="application/json" id="qpos-report-chart-data">{!! json_encode(['daily'=>$daily,'hours'=>$peaks['rows'],'label'=>__('reporting.net_sales')], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) !!}</script>
    @vite('resources/js/dashboard.js')
@endpush
