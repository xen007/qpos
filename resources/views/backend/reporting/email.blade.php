<p>{{ $kind === 'late_activity' ? __('reporting.late_available') : ($summary->closure_type === 'corrected' ? __('reporting.corrected_available') : __('reporting.daily_summary')) }}</p>
<p>{{ $data['shop_label'] }} · {{ $summary->business_date }} · v{{ $summary->version }}</p>
<p>{{ __('reporting.net_sales') }}: {{ $data['summary']['net_sales'] }} XAF · {{ __('reporting.management_result') }}: {{ $data['summary']['management_result'] }} XAF</p>
@if($data['summary']['incomplete_cost'])<p>{{ __('reporting.incomplete_margin') }}</p>@endif
<p>{{ __('reporting.open_sessions') }}: {{ $data['summary']['open_sessions'] }}</p>
<p><a href="{{ route('backend.admin.reporting.summary',$summary->id) }}">{{ __('reporting.view_authenticated') }}</a></p>
