@extends('backend.master-tailwind')
@section('title',__('notifications.settings'))
@section('content')
<x-backend.card :title="__('notifications.settings')">
<p class="mb-4 text-sm text-qpos-muted">{{ __('notifications.threshold_notice') }}</p>
<form method="post" action="{{ route('backend.admin.notifications.settings.save') }}" class="grid gap-4 sm:grid-cols-2">
    @csrf
    <x-backend.input name="critical_threshold" type="number" min="1" step="1" :label="__('notifications.critical_threshold')" :value="config('system.notification_critical_sale_threshold',config('notifications.critical_sale_threshold'))" />
    <x-backend.input name="low_threshold" type="number" min="1" step="1" :label="__('notifications.low_threshold')" :value="config('system.notification_low_stock_threshold',config('notifications.low_stock_threshold'))" required />
    <fieldset class="sm:col-span-2 grid gap-4 rounded-lg border border-qpos-border p-4">
        <legend class="px-2 font-semibold">{{ __('external_alerts.title') }}</legend>
        <p class="text-sm text-qpos-muted">{{ __('external_alerts.notice') }}</p>
        <input type="hidden" name="external_present" value="1">
        <div><label for="external_locale">{{ __('external_alerts.locale') }}</label>
        <select id="external_locale" name="external_locale" class="qpos-control w-full">
            @foreach(['fr'=>'Français','en'=>'English'] as $code=>$name)<option value="{{ $code }}" @selected(old('external_locale',$settings['locale']??'fr')===$code)>{{ $name }}</option>@endforeach
        </select></div>
        <fieldset class="grid gap-2"><legend>{{ __('external_alerts.kinds') }}</legend>
        @foreach(\App\Services\ExternalAlertService::KINDS as $kind)
        <label class="flex min-h-11 items-center gap-2"><input type="checkbox" name="external_kinds[]" value="{{ $kind }}" @checked(in_array($kind,old('external_kinds',$settings['kinds']??[]),true))>{{ __('notifications.types.'.$kind) }}</label>
        @endforeach</fieldset>
        @foreach(\App\Services\ExternalAlertService::CHANNELS as $channel)
        <div class="grid gap-2 rounded-lg border border-qpos-border p-3">
            <h3 class="font-semibold">{{ __('external_alerts.'.$channel) }}</h3>
            <label class="flex min-h-11 items-center gap-2"><input type="checkbox" name="{{ $channel }}_enabled" value="1" @checked(old($channel.'_enabled',$settings[$channel]['enabled']??false))>{{ __('external_alerts.enabled') }}</label>
            <label for="{{ $channel }}_destinations">{{ __('external_alerts.destinations') }}</label>
            <textarea class="qpos-control w-full" rows="3" id="{{ $channel }}_destinations" name="{{ $channel }}_destinations">{{ old($channel.'_destinations',implode("\n",$settings[$channel]['destinations']??[])) }}</textarea>
            @error($channel.'_destinations')<p role="alert" class="text-qpos-danger">{{ $message }}</p>@enderror
            <p class="text-sm text-qpos-muted">{{ __('external_alerts.'.($external->channelReady($channel,$settings['locale']??'fr')?'ready':'unconfigured')) }}</p>
        </div>
        @endforeach
        <p class="text-sm text-qpos-muted">{{ __('external_alerts.destination_help') }}</p>
        <p class="text-sm text-qpos-muted">{{ __('external_alerts.template_help') }}</p>
        <label class="flex min-h-11 items-start gap-2"><input type="checkbox" name="external_consent" value="1" class="mt-1">{{ __('external_alerts.consent') }}</label>
        @error('external_consent')<p role="alert" class="text-qpos-danger">{{ $message }}</p>@enderror
        @error('external_kinds')<p role="alert" class="text-qpos-danger">{{ $message }}</p>@enderror
    </fieldset>
    <div><x-backend.button>{{ __('Save') }}</x-backend.button></div>
</form>
</x-backend.card>
@if($deliveries)
<x-backend.card :title="__('external_alerts.history')">
<p class="mb-4 text-sm text-qpos-muted">{{ __('external_alerts.accepted_notice') }}</p>
<div class="overflow-x-auto"><table class="w-full text-left text-sm [&_th]:p-3 [&_td]:p-3 [&_td]:border-t [&_td]:border-qpos-border"><thead><tr><th>{{ __('Date') }}</th><th>{{ __('notifications.title') }}</th><th>{{ __('external_alerts.channel') }}</th><th>{{ __('external_alerts.state') }}</th><th>{{ __('external_alerts.attempts') }}</th></tr></thead>
<tbody>@foreach($deliveries as $delivery)<tr><td>{{ $delivery->created_at }}</td><td>{{ __('notifications.types.'.$delivery->kind) }} #{{ $delivery->point_of_sale_id??'global' }}</td><td>{{ __('external_alerts.'.$delivery->channel) }}</td><td>{{ __('external_alerts.states.'.$delivery->state) }}@if($delivery->last_error)<br><small>{{ $delivery->last_error }}</small>@endif</td><td>{{ $delivery->attempts }}</td></tr>@endforeach</tbody></table></div>
{{ $deliveries->links() }}
</x-backend.card>
@endif
@endsection
