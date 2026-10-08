@extends('backend.master-tailwind')
@section('title', __('Payments'))
@section('content')
<x-backend.card :title="__('Payments')"><p>{{ __('Sale') }} #{{ $order->id }}</p>
@foreach($order->paymentAllocations as $a)<p class="py-3">#{{ $a->payment_id }} · {{ __($a->payment->direction) }} · {{ __($a->payment->method) }} · {{ $a->amount }} {{ $a->payment->currency_code }} · <a href="{{ route('backend.admin.payments.receipt',$a->payment_id) }}">{{ __('Receipt') }}</a></p>@endforeach
</x-backend.card>
@endsection
