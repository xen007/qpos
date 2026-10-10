<div class="my-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
    @foreach(['net_sales','cogs','gross_margin','expenses','management_result','cash_difference'] as $metric)
        <x-backend.stat-card :label="__('reporting.'.$metric)" :value="isset($summary[$metric]) ? \App\Support\SaleFormat::moneyDisplay($summary[$metric]).' XAF' : '—'" icon="fas fa-chart-bar" />
    @endforeach
</div>
<p class="mb-4 text-sm text-qpos-muted">{{ __('reporting.sales_count') }}: {{ $summary['sales_count'] }} · {{ __('reporting.returns_count') }}: {{ $summary['returns_count'] }} · {{ __('reporting.open_sessions') }}: {{ $summary['open_sessions'] }}</p>
<p class="mb-4 text-sm text-qpos-muted">@foreach(['new_debt','native_debt'] as $debt){{ __('reporting.'.$debt) }}: {{ isset($summary[$debt]) ? \App\Support\SaleFormat::moneyDisplay($summary[$debt]).' XAF' : '—' }} · @endforeach</p>
@if($summary['incomplete_cost'])<div role="status" class="mb-4 rounded-lg border border-qpos-line bg-qpos-surface p-4">{{ __('reporting.incomplete_margin') }} {{ __('reporting.unknown_quantity') }}: {{ \App\Support\SaleFormat::decimal($summary['unknown_quantity']) }}</div>@endif
@if(!empty($summary['treasury']))
<x-backend.card :title="__('reporting.treasury')">
    <div class="overflow-x-auto"><table class="qpos-table w-full"><thead><tr>@foreach(['method','incoming','outgoing','expenses','net'] as $c)<th class="p-2 text-left">{{ __('reporting.'.$c) }}</th>@endforeach</tr></thead><tbody>
    @foreach($summary['treasury'] as $flow)<tr>@foreach(['method','incoming','outgoing','expenses','net'] as $c)<td class="p-2">{{ $c === 'method' ? __('reporting.'.($flow[$c] === 'cash' ? 'cash_method' : $flow[$c])) : \App\Support\SaleFormat::moneyDisplay($flow[$c]).' XAF' }}</td>@endforeach</tr>@endforeach
    </tbody></table></div><p class="mt-2 text-sm text-qpos-muted">{{ __('reporting.treasury_notice') }}</p>
    <p class="mt-2 text-sm">@foreach(['manual_in','manual_out','opening_floats'] as $m){{ __('reporting.'.$m) }}: {{ \App\Support\SaleFormat::moneyDisplay($summary[$m] ?? 0) }} XAF · @endforeach</p>
</x-backend.card>
@endif
