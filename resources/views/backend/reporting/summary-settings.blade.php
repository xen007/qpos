@extends('backend.master-tailwind')
@section('title', __('reporting.summary_settings'))
@section('content')
<x-backend.card :title="__('reporting.summary_settings')">
    <p class="mb-3">{{ __('reporting.'.($mailEnabled ? 'mail_enabled' : 'mail_disabled')) }}</p>
    <p class="mb-3 text-sm">{{ __('reporting.'.($transportReady ? 'smtp_ready' : 'smtp_missing')) }}</p>
    <form method="post" action="{{ route('backend.admin.reporting.summary-settings.update') }}">@csrf
        <input type="hidden" name="approval_hash" value="{{ $mailPreview['hash'] }}">
        <label class="block mb-3">{{ __('reporting.delivery_address') }}
            <textarea name="recipients" rows="4" class="qpos-control block w-full">{{ old('recipients', implode("\n", $recipients)) }}</textarea>
        </label>
        <p class="mb-3 text-sm text-qpos-muted">{{ __('reporting.recipients_notice') }}</p>
        <label class="block mb-3">{{ __('reporting.mail_toggle') }}
            <select name="enable" class="qpos-control"><option value="0" @selected(!old('enable', $mailEnabled))>{{ __('reporting.disable_mail') }}</option><option value="1" @selected(old('enable', $mailEnabled)) @disabled(!$transportReady)>{{ __('reporting.enable_mail') }}</option></select>
        </label>
        <p class="mb-3 text-sm">{{ __('reporting.mail_scope_notice') }}</p>
        <ul class="mb-3">@foreach($mailPreview['pairs'] as $pair)<li>{{ $pair['shop'] }} · {{ $pair['name'] }}</li>@endforeach</ul>
        <label class="block mb-3"><input type="checkbox" name="consent" value="1"> {{ __('reporting.mail_consent') }}</label>
        <button class="qpos-button qpos-button-primary qpos-button-md">{{ __('Save') }}</button>
    </form>
</x-backend.card>
@endsection
