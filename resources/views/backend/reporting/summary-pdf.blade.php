<!doctype html><html lang="{{ app()->getLocale() }}"><head><meta charset="utf-8">@include('backend.reporting.pdf-style')</head><body>
<h1>QPOS — {{ __('reporting.daily_summary') }}</h1><p>{{ $data['shop_label'] }} · {{ $row->business_date }} · v{{ $row->version }} · {{ __('reporting.'.$row->closure_type) }}</p>
<p>{{ __('reporting.cutoff_at') }}: {{ \Carbon\CarbonImmutable::parse($row->cutoff_at,'UTC')->setTimezone('Africa/Douala')->format('Y-m-d H:i:s') }} Africa/Douala</p>
<p>{{ __('reporting.immutable') }} {{ __('reporting.closure_notice') }}</p>
@if($data['summary']['incomplete_cost'])<p>{{ __('reporting.incomplete_margin') }}</p>@endif
<table><thead><tr><th>{{ __('reporting.indicator') }}</th><th>XAF</th></tr></thead><tbody>@foreach(['net_sales','cogs','gross_margin','expenses','management_result','cash_difference'] as $m)<tr><td>{{ __('reporting.'.$m) }}</td><td>{{ \App\Support\SaleFormat::moneyDisplay($data['summary'][$m]) }}</td></tr>@endforeach</tbody></table>
<h2>{{ __('reporting.treasury') }}</h2><table><thead><tr>@foreach(['method','incoming','outgoing','expenses','net'] as $m)<th>{{ __('reporting.'.$m) }}</th>@endforeach</tr></thead><tbody>@foreach($data['summary']['treasury'] as $flow)<tr>@foreach(['method','incoming','outgoing','expenses','net'] as $m)<td>{{ $m==='method' ? __('reporting.'.($flow[$m] === 'cash' ? 'cash_method' : $flow[$m])) : \App\Support\SaleFormat::moneyDisplay($flow[$m]) }}</td>@endforeach</tr>@endforeach</tbody></table>
<h2>{{ __('reporting.open_sessions') }}: {{ count($data['open_sessions']) }}</h2><ul>@foreach($data['open_sessions'] as $s)<li>#{{ $s['id'] }} · {{ $s['name'] }}</li>@endforeach</ul>
<p>@foreach(['manual_in','manual_out','opening_floats','new_debt','native_debt'] as $m){{ __('reporting.'.$m) }}: {{ \App\Support\SaleFormat::moneyDisplay($data['summary'][$m] ?? 0) }} XAF · @endforeach</p>
<h2>{{ __('reporting.top_peaks') }}</h2><p>@foreach($data['peaks']['peaks'] as $p){{ $p['hour'] }} : {{ \App\Support\SaleFormat::moneyDisplay($p['average']) }} XAF · @endforeach</p>
</body></html>
