@extends('backend.master-tailwind')
@section('title', __('Collect Due'))
@section('content')
<x-backend.card :title="__('Collect Due')"><p>{{ __('Sale') }} #{{ $order->id }} · {{ $order->customer->name }} · {{ __('Due') }}: {{ $order->due }} {{ $order->currency_code ?? __('Unknown') }}</p>
<form method="POST" action="{{ route('backend.admin.due.collection',$order->id) }}" class="grid gap-4 mt-4">@csrf @include('backend.phase4.context')
<x-backend.input name="amount" :label="__('Amount')" type="number" min="1" step="1" required />
<x-backend.select name="method" :label="__('Payment method')" :options="collect(app(App\Services\PaymentMethodService::class)->choices($order->point_of_sale_id))->map(fn($label)=>__($label))->all()" />
<x-backend.input name="external_reference" :label="__('External reference')" maxlength="128" />
<button class="qpos-button qpos-button-md qpos-button-primary">{{ __('Record payment') }}</button></form></x-backend.card>
@endsection
