@extends('backend.master-tailwind')
@section('title', __('Statistics'))
@section('content')
<div class="qpos-touch-workspace">
    @include('backend.reporting.filters')
    @include('backend.reporting.navigation')
    <p class="text-sm text-qpos-muted">{{ __('reporting.native_notice') }}</p>
    @include('backend.reporting.metrics')
    <div class="my-4 grid gap-4 sm:grid-cols-2">
        <x-backend.stat-card :label="__('reporting.saleable_value')" :value="\App\Support\SaleFormat::moneyDisplay($stock['saleable_value']).' XAF'" icon="fas fa-box" />
        <x-backend.stat-card :label="__('reporting.unknown_quantity')" :value="\App\Support\SaleFormat::decimal($stock['unknown_quantity'])" icon="fas fa-box" />
    </div>
    <p class="text-sm text-qpos-muted">{{ __('reporting.stock_current') }} {{ __('reporting.cost_notice') }}</p>
    <x-backend.card :title="__('reporting.daily_sales')"><div class="relative h-80"><canvas data-report-chart="daily" role="img" aria-label="{{ __('reporting.daily_sales') }}"></canvas></div></x-backend.card>
    @include('backend.reporting.peaks')
</div>
@endsection
@push('script')
    <script type="application/json" id="qpos-report-chart-data">{!! json_encode(['daily'=>$daily,'hours'=>$peaks['rows'],'label'=>__('reporting.net_sales')], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) !!}</script>
    @vite('resources/js/dashboard.js')
@endpush
