<!doctype html><html lang="{{ app()->getLocale() }}"><head><meta charset="utf-8">@include('backend.reporting.pdf-style')</head><body>
<h1>QPOS — {{ __('reporting.'.$report['type']) }}</h1>
<p>{{ $filter->label() }} · {{ __('reporting.scope') }}: {{ implode(', ',$filter->shops) }} · {{ $report['currency'] }}</p>
<p class="muted">{{ $report['type'] === 'history' ? __('reporting.history_notice') : __('reporting.native_notice') }} {{ __('reporting.cost_notice') }}</p>
@if(isset($report['unique_sales']))<p>{{ __('reporting.unique_sales') }}: {{ $report['unique_sales'] }} · {{ __('reporting.flow_notice') }}</p>@endif
@if(isset($report['peaks']))
<p>{{ __('reporting.average_notice',['days'=>$report['peaks']['days']]) }}</p>
@foreach(['peaks','troughs'] as $k)<p>{{ __('reporting.top_'.$k) }}: @foreach($report['peaks'][$k] as $p){{ $p['hour'] }} ({{ \App\Support\SaleFormat::decimal($p['average']) }} XAF) @endforeach</p>@endforeach
<div class="chart">@php($maximum = max(1, ...array_map(fn($v)=>abs((float)$v['average']), $report['peaks']['rows'])))
@foreach($report['peaks']['rows'] as $p)<div>{{ $p['hour'] }} <span class="bar" style="width:{{ abs((float)$p['average'])/$maximum*75 }}%;background:{{ (float)$p['average'] < 0 ? '#b45309' : '#1e5f74' }}"></span> {{ \App\Support\SaleFormat::decimal($p['average']) }}</div>@endforeach
</div>@endif
@if(isset($report['stock_totals']))<p>{{ __('reporting.stock_current') }} · {{ __('reporting.saleable_value') }}: {{ $report['stock_totals']['saleable_value'] }} XAF · {{ __('reporting.unsaleable_value') }}: {{ $report['stock_totals']['unsaleable_value'] }} XAF · {{ __('reporting.unknown_quantity') }}: {{ $report['stock_totals']['unknown_quantity'] }}</p>@endif
<table><thead><tr>@foreach($headings as $h)<th>{{ $h }}</th>@endforeach</tr></thead><tbody>@forelse($data as $r)<tr>@foreach($r as $v)<td>{{ $v }}</td>@endforeach</tr>@empty<tr><td colspan="{{ count($headings) }}">{{ __('reporting.empty') }}</td></tr>@endforelse</tbody></table>
</body></html>
