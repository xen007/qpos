@extends('backend.master-tailwind')
@section('title', __('Statistics'))
@section('content')
<div class="qpos-touch-workspace">
    @include('backend.reporting.filters')
    <div class="flex flex-wrap gap-3 my-4">@foreach(['xlsx','pdf'] as $format)<a class="qpos-button qpos-button-md qpos-button-secondary" href="{{ route('backend.admin.reporting.statistics.export',['format'=>$format]+request()->query()) }}">{{ $format==='xlsx'?__('phase7.export_excel'):__('phase7.export_pdf') }}</a>@endforeach</div>
    <p class="text-sm text-qpos-muted">{{ __('reporting.native_notice') }}</p>
    @include('backend.reporting.metrics')
    @include('backend.reporting.statistics-analysis')
    <div class="my-4 grid gap-4 sm:grid-cols-2">
        <x-backend.stat-card :label="__('reporting.saleable_value')" :value="\App\Support\SaleFormat::moneyDisplay($stock['saleable_value']).' XAF'" icon="fas fa-box" />
        <x-backend.stat-card :label="__('reporting.unknown_quantity')" :value="\App\Support\SaleFormat::decimal($stock['unknown_quantity'])" icon="fas fa-box" />
    </div>
    <p class="text-sm text-qpos-muted">{{ __('reporting.stock_current') }} {{ __('reporting.cost_notice') }}</p>
    <x-backend.card :title="__('reporting.daily_sales')"><div class="relative h-80"><canvas data-report-chart="daily" role="img" aria-label="{{ __('reporting.daily_sales') }}"></canvas></div>@include('backend.reporting.chart-values',['values'=>$analysis['daily'],'title'=>__('reporting.daily_sales')])</x-backend.card>
    @include('backend.reporting.peaks')
    <details class="qpos-filter-disclosure"><summary>{{ __('Reports') }}</summary>@include('backend.reporting.navigation')</details>
</div>
@endsection
@push('script')
    <script type="application/json" id="qpos-report-chart-data">{!! json_encode(['daily'=>$analysis['daily'],'previous_daily'=>$analysis['previous_daily'],'hours'=>$peaks['rows'],'label'=>__('reporting.net_sales'),'previous_label'=>__('phase7.comparison_period')], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) !!}</script>
    @vite('resources/js/dashboard.js')
@endpush
