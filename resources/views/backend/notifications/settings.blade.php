@extends('backend.master-tailwind')
@section('title',__('notifications.settings'))
@section('content')
<x-backend.card :title="__('notifications.settings')">
<p class="mb-4 text-sm text-qpos-muted">{{ __('notifications.threshold_notice') }}</p>
<form method="post" action="{{ route('backend.admin.notifications.settings.save') }}" class="grid gap-4 sm:grid-cols-2">
    @csrf
    <x-backend.input name="critical_threshold" type="number" min="1" step="1" :label="__('notifications.critical_threshold')" :value="config('system.notification_critical_sale_threshold',config('notifications.critical_sale_threshold'))" />
    <x-backend.input name="low_threshold" type="number" min="1" step="1" :label="__('notifications.low_threshold')" :value="config('system.notification_low_stock_threshold',config('notifications.low_stock_threshold'))" required />
    <div><x-backend.button>{{ __('Save') }}</x-backend.button></div>
</form>
</x-backend.card>
@endsection
