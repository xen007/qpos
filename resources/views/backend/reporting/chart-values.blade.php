<details class="qpos-chart-values mt-3">
    <summary>{{ __('phase7.chart_values') }}</summary>
    <div class="overflow-x-auto" role="region" aria-label="{{ $title }}" tabindex="0">
        <table class="qpos-report-table"><caption class="sr-only">{{ $title }}</caption><thead><tr><th scope="col">{{ __('phase7.date_or_hour') }}</th><th scope="col">{{ __('reporting.net_sales') }} XAF</th></tr></thead><tbody>
        @forelse($values as $value)<tr><th scope="row">{{ $value['label']??$value['hour'] }}</th><td class="qpos-number">{{ \App\Support\SaleFormat::moneyDisplay($value['value']??$value['average']) }}</td></tr>@empty<tr><td colspan="2">{{ __('reporting.empty') }}</td></tr>@endforelse
        </tbody></table>
    </div>
</details>
