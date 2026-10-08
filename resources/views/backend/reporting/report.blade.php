@extends('backend.master-tailwind')
@section('title', __('reporting.'.$report['type']))
@section('content')
    @include('backend.reporting.filters')
    @include('backend.reporting.navigation')
    <div class="my-4 flex flex-wrap gap-2">@foreach(['xlsx','pdf'] as $format)<a class="qpos-button qpos-button-secondary qpos-button-md" href="{{ route('backend.admin.reporting.export', ['type'=>$report['type'],'format'=>$format] + request()->except(['page','type','format'])) }}">{{ __('reporting.export_'.$format) }}</a>@endforeach</div>
    <p class="mb-4 text-sm text-qpos-muted">{{ $report['type'] === 'history' ? __('reporting.history_notice') : __('reporting.native_notice') }} {{ __('reporting.cost_notice') }}</p>
    @if(isset($report['summary']))@include('backend.reporting.metrics', ['summary'=>$report['summary']])@endif
    @if(isset($report['peaks']))@include('backend.reporting.peaks', ['peaks'=>$report['peaks']])@endif
    @if(isset($report['stock_totals']))<p class="mb-3">{{ __('reporting.stock_current') }} · {{ __('reporting.saleable_value') }}: {{ \App\Support\SaleFormat::decimal($report['stock_totals']['saleable_value']) }} XAF · {{ __('reporting.unsaleable_value') }}: {{ \App\Support\SaleFormat::decimal($report['stock_totals']['unsaleable_value']) }} XAF · {{ __('reporting.unknown_quantity') }}: {{ \App\Support\SaleFormat::decimal($report['stock_totals']['unknown_quantity']) }} · {{ __('reporting.foreign_quantity') }}: {{ \App\Support\SaleFormat::decimal($report['stock_totals']['foreign_quantity']) }}</p>@endif
    <x-backend.card :title="__('reporting.'.$report['type'])">
    <div class="overflow-x-auto"><table class="qpos-table w-full text-sm"><thead><tr>@foreach($report['columns'] as $c)<th scope="col" class="p-3 text-left">{{ __('reporting.'.$c) }}</th>@endforeach</tr></thead><tbody>
        @forelse($rows as $r)<tr class="border-t border-qpos-line">@foreach($report['columns'] as $c)<td class="p-3">{{ app(\App\Http\Controllers\Backend\ReportingController::class)->displayValue($c, ((array)$r)[$c] ?? null, $report['type']) }}</td>@endforeach</tr>
        @empty<tr><td colspan="{{ count($report['columns']) }}" class="p-4">{{ __('reporting.empty') }}</td></tr>@endforelse
    </tbody></table></div>
    @if($rows instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)<div class="mt-4">{{ $rows->links('pagination::tailwind') }}</div>@endif
    </x-backend.card>
@endsection
@if(isset($report['peaks']))@push('script')
    <script type="application/json" id="qpos-report-chart-data">{!! json_encode(['daily'=>[],'hours'=>$report['peaks']['rows'],'label'=>__('reporting.average')], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) !!}</script>
    @vite('resources/js/dashboard.js')
@endpush @endif
