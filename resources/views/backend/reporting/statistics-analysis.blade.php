<x-backend.card :title="__('phase7.comparison')">
    <p class="mb-3 text-sm text-qpos-muted">{{ $analysis['previous_filter']->label() }} · {{ __('phase7.comparison_notice') }}</p>
    @if($analysis['period_in_progress'])<p role="status" class="mb-3 text-sm text-qpos-warning">{{ __('phase7.period_in_progress') }}</p>@endif
    @if($analysis['incomplete_cost'])<p role="status" class="mb-3 text-sm">{{ __('reporting.incomplete_margin') }}</p>@endif
    <div class="overflow-x-auto" tabindex="0" role="region" aria-label="{{ __('phase7.comparison') }}"><table class="qpos-report-table"><thead><tr>
    @foreach(['indicator','current_period','comparison_period','change','percent'] as $heading)<th scope="col">{{ __('phase7.'.$heading) }}</th>@endforeach
    </tr></thead><tbody>@foreach($analysis['comparison'] as $row)<tr><th scope="row">{{ __('reporting.'.$row['key']) }}</th>
    @foreach(['current','previous','delta'] as $key)<td class="qpos-number">{{ $row[$key]===null?'—':($row['key']==='sales_count'?$row[$key]:\App\Support\SaleFormat::moneyDisplay($row[$key]).' XAF') }}</td>@endforeach
    <td class="qpos-number">{{ $row['percent']===null?'—':\App\Support\SaleFormat::moneyDisplay($row['percent']).' %' }}</td></tr>@endforeach</tbody></table></div>
</x-backend.card>
<div class="my-4 grid gap-4 lg:grid-cols-2">
@foreach(['top','flop'] as $kind)<x-backend.card :title="__('phase7.'.$kind)">
    <p class="mb-3 text-sm text-qpos-muted">{{ __('phase7.ranking_notice') }}</p>
    <ol class="grid gap-3">@forelse($analysis[$kind] as $row)<li class="flex items-start justify-between gap-3"><span class="min-w-0 break-words">{{ $row['label'] }}</span><strong class="shrink-0 tabular-nums">{{ \App\Support\SaleFormat::moneyDisplay($row['net_sales']) }} XAF</strong></li>@empty<li>{{ __('reporting.empty') }}</li>@endforelse</ol>
</x-backend.card>@endforeach
</div>
