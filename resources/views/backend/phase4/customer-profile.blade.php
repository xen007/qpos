@extends('backend.master-tailwind')
@section('title', __('Customer'))
@section('content')
<x-backend.card :title="$customer->isWalking() ? __('Walking Customer') : $customer->name">
<p>{{ __('Phone') }}: {{ $customer->phone ?? '—' }}</p><p>{{ __('Address') }}: {{ $customer->address ?? '—' }}</p><p>{{ $customer->is_active?__('Active'):__('Inactive') }}</p>
@if($customer->isWalking())<p>{{ __('Cash only: no debt or credit.') }}</p>@endif
@can('customer_sales')<a class="qpos-button qpos-button-md qpos-button-secondary mt-4" href="{{ route('backend.admin.customers.finance',$customer->id) }}">{{ __('Customer balances') }}</a>@endcan
</x-backend.card>
@endsection
