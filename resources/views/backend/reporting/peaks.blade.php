<p class="my-3 text-sm text-qpos-muted">{{ __('reporting.average_notice', ['days'=>$peaks['days']]) }}</p>
<div class="grid gap-3 sm:grid-cols-2">
    @foreach(['peaks','troughs'] as $k)<x-backend.card :title="__('reporting.top_'.$k)"><ol class="list-inside list-decimal">@foreach($peaks[$k] as $p)<li>{{ $p['hour'] }} — {{ \App\Support\SaleFormat::moneyDisplay($p['average']) }} XAF</li>@endforeach</ol></x-backend.card>@endforeach
</div>
<div class="my-4 rounded-lg border border-qpos-line bg-qpos-surface p-4"><div class="relative h-80"><canvas data-report-chart="hours" role="img" aria-label="{{ __('reporting.peaks') }}"></canvas></div></div>
