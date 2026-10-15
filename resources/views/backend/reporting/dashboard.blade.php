@extends('backend.master-tailwind')
@section('title', __('Dashboard'))
@section('content')
    @if(($orphanCount??0)>0)<p role="alert" class="my-4 rounded-lg border p-4"><a href="{{ route('backend.admin.cash.supervision') }}">{{ __('Inactive cash sessions require review') }}: {{ $orphanCount }}</a></p>@endif
<div class="qpos-touch-workspace">
    <p class="my-3 text-sm">{{ __('reporting.scope') }}: {{ $shops->whereIn('id',$filter->shops)->pluck('name')->implode(' · ') }}</p>
    <div class="qpos-quick-context"><p class="text-sm text-qpos-muted">{{ $filter->label() }} · {{ __('reporting.native_notice') }}</p><a class="qpos-button qpos-button-md qpos-button-secondary" href="{{ route('backend.admin.reporting.statistics', request()->query()) }}">{{ __('Statistics') }}</a></div>
    <div class="qpos-quick-dashboard-cards my-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-backend.stat-card :label="__('reporting.net_sales')" :value="\App\Support\SaleFormat::moneyDisplay($summary['net_sales']).' XAF'" icon="fas fa-tags" />
        <x-backend.stat-card :label="__('reporting.gross_margin')" :value="\App\Support\SaleFormat::moneyDisplay($summary['gross_margin']).' XAF'" icon="fas fa-chart-bar" :hint="$summary['incomplete_cost'] ? __('reporting.incomplete_margin') : null" />
        <x-backend.stat-card :label="__('Customers served')" :value="$clients" icon="fas fa-user-circle" :hint="__('Registered customers in selected period')" />
        <x-backend.stat-card :label="__('Low stock')" :value="$lowStock" icon="fas fa-box" :hint="__('replenishment.dashboard_low_hint')" />
    </div>
    @if(auth()->user()->can('stock_view')) @include('backend.replenishment.dashboard-alerts') @endif
    <details class="qpos-filter-disclosure" @if($filter->salesOnly()) open @endif><summary>{{ __('reporting.filters') }} · {{ __('phase7.adjust_filters') }}</summary>@include('backend.reporting.filters')</details>
    <div class="grid gap-4 xl:grid-cols-2">
    <x-backend.card :title="__('reporting.daily_sales')"><div class="relative h-64"><canvas data-report-chart="daily" role="img" aria-label="{{ __('reporting.daily_sales') }}"></canvas></div>@include('backend.reporting.chart-values',['values'=>$daily,'title'=>__('reporting.daily_sales')])</x-backend.card>
    <x-backend.card :title="__('reporting.peaks')"><div class="relative h-64"><canvas data-report-chart="hours" role="img" aria-label="{{ __('reporting.peaks') }}"></canvas></div>@include('backend.reporting.chart-values',['values'=>$peaks['rows'],'title'=>__('reporting.peaks')])</x-backend.card>
    </div>
</div>
@endsection
@push('script')
    <script type="application/json" id="qpos-report-chart-data">{!! json_encode(['daily'=>$daily,'hours'=>$peaks['rows'],'label'=>__('reporting.net_sales')], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) !!}</script>
    @vite('resources/js/dashboard.js')
@endpush
