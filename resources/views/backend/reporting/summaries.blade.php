@extends('backend.master-tailwind')
@section('title', __('reporting.daily_summary'))
@section('content')
    @include('backend.reporting.filters', ['report'=>['type'=>'summary']])
    <p class="my-4">{{ __('reporting.three_levels') }}</p>
    <div class="my-4 flex flex-wrap gap-3">
        @can('cash_session_manage')
        <form method="post" action="{{ route('backend.admin.reporting.pause') }}" class="flex flex-wrap gap-2">@csrf
            <select name="shop_id" aria-label="{{ __('reporting.shop') }}" class="qpos-control">@foreach($shops as $shop)<option value="{{ $shop->id }}">{{ $shop->name }}</option>@endforeach</select>
            <input type="hidden" name="action" value="{{ $pause ? 'resume' : 'pause' }}">
            <button class="qpos-button qpos-button-secondary qpos-button-md">{{ __('reporting.'.($pause ? 'resume' : 'pause')) }}</button>
            <a class="qpos-button qpos-button-secondary qpos-button-md" href="{{ route('backend.admin.cash.index') }}">{{ __('reporting.manage_session') }}</a>
        </form>@endcan
        @if(auth()->user()->hasRole('Admin') && auth()->user()->can('daily_summary_close'))
        <form method="post" action="{{ route('backend.admin.reporting.close-day') }}" class="flex flex-wrap gap-2">@csrf
            <select name="shop_id" aria-label="{{ __('reporting.shop') }}" class="qpos-control">@foreach($shops as $shop)<option value="{{ $shop->id }}">{{ $shop->name }}</option>@endforeach</select>
            <button class="qpos-button qpos-button-primary qpos-button-md">{{ __('reporting.close_now') }}</button>
        </form>@endif
    </div>
    <p class="my-3 text-sm text-qpos-muted">{{ __('reporting.closure_notice') }}</p>
    <x-backend.card :title="__('reporting.mail_activation')">
        <p>{{ __('reporting.'.($mailEnabled ? 'mail_enabled' : 'mail_disabled')) }}</p>
        @if($mailPreview)
        <a class="qpos-button qpos-button-secondary qpos-button-md mt-3" href="{{ route('backend.admin.reporting.summary-settings') }}">{{ __('reporting.summary_settings') }}</a>
        @endif
    </x-backend.card>
    <p class="my-3 text-sm">{{ __('reporting.scheduler') }}: {{ $scheduler ? \Carbon\CarbonImmutable::parse($scheduler, 'UTC')->setTimezone('Africa/Douala')->format('Y-m-d H:i:s') : __('reporting.not_started') }}</p>
    <p class="my-3 text-sm">@foreach($states as $state){{ __('reporting.delivery_'.$state->state) }}: {{ $state->total }} · @endforeach</p>
    <x-backend.card :title="__('reporting.versions')"><div class="overflow-x-auto"><table class="qpos-table w-full text-sm"><thead><tr>@foreach(['shop_id','business_date','closure_type','version','cutoff_at'] as $c)<th class="p-3 text-left">{{ __('reporting.'.$c) }}</th>@endforeach<th>{{ __('View') }}</th></tr></thead><tbody>
    @forelse($rows as $row)<tr class="border-t border-qpos-line"><td class="p-3">{{ $row->point_of_sale_id }}</td><td class="p-3">{{ $row->business_date }}</td><td class="p-3">{{ __('reporting.'.$row->closure_type) }}</td><td class="p-3">{{ $row->version }}</td><td class="p-3">{{ \Carbon\CarbonImmutable::parse($row->cutoff_at, 'UTC')->setTimezone('Africa/Douala')->format('Y-m-d H:i:s') }}</td><td class="p-3"><a href="{{ route('backend.admin.reporting.summary',$row->id) }}">{{ __('View') }}</a></td></tr>@empty<tr><td class="p-3" colspan="6">{{ __('reporting.empty') }}</td></tr>@endforelse
    </tbody></table></div>{{ $rows->links('pagination::tailwind') }}</x-backend.card>
@endsection
