@extends('backend.master-tailwind')
@section('title',__('notifications.preferences'))
@section('content')
<x-backend.card :title="__('notifications.preferences')">
    <p class="mb-4 text-sm text-qpos-muted">{{ __('notifications.preferences_notice') }}</p>
    @if(\App\Support\RoleAssignment::globalManager($user)&&$user->can('system_monitor'))<p class="mb-4"><a class="qpos-button qpos-button-sm qpos-button-secondary" href="{{ route('backend.admin.notifications.settings') }}">{{ __('notifications.settings') }}</a></p>@endif
    <form method="post" action="{{ route('backend.admin.notifications.preferences.save') }}" class="grid gap-4">
        @csrf
        @forelse($kinds as $kind)<label class="flex items-center gap-3 min-h-11"><input type="checkbox" name="enabled[]" value="{{ $kind }}" @checked($notifications->enabled($user,$kind))>{{ __('notifications.types.'.$kind) }}</label>@empty<p>{{ __('reporting.empty') }}</p>@endforelse
        @foreach($external->availableChannels($user) as $channel)
        <fieldset class="grid gap-2 rounded-lg border border-qpos-border p-3">
            <legend>{{ __('external_alerts.'.$channel) }}</legend>
            <p class="text-sm text-qpos-muted">{{ __('external_alerts.preferences_notice') }}</p>
            @foreach(array_intersect($kinds,\App\Services\ExternalAlertService::KINDS) as $kind)
            <label class="flex min-h-11 items-center gap-3"><input type="checkbox" name="external[{{ $channel }}][]" value="{{ $kind }}" @checked($external->preference($user,$kind,$channel))>{{ __('notifications.types.'.$kind) }}</label>
            @endforeach
        </fieldset>
        @endforeach
        <div><x-backend.button>{{ __('Save') }}</x-backend.button></div>
    </form>
</x-backend.card>
@endsection
