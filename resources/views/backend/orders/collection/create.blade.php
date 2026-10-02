@extends('backend.master-tailwind')

@section('title', __('Collection'))

@section('content')
    <x-backend.card>
        <form action="{{ route('backend.admin.due.collection', $order->id) }}" method="post" class="accountForm">
            @csrf

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['label' => __('Customer'), 'value' => $order->customer->name],
                    ['label' => __('Order'), 'value' => '# '.$order->id],
                    ['label' => __('Total'), 'value' => $order->total],
                    ['label' => __('Due'), 'value' => $order->due],
                ] as $info)
                    <div>
                        <p class="text-sm font-medium text-qpos-muted">{{ $info['label'] }}</p>
                        <p class="text-qpos-ink">{{ $info['value'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-2">
                <x-backend.input name="amount" type="number" :label="__('Collection Amount')" :value="$order->due"
                    :placeholder="__('Enter amount')" min="1" :max="$order->due" required />
            </div>

            <div class="mt-6">
                <button type="submit"
                    class="qpos-button qpos-button-md qpos-button-primary rounded-lg bg-qpos-brand px-6 py-2 text-sm font-semibold text-white transition hover:opacity-90">
                    {{ __('Submit') }}
                </button>
            </div>
        </form>
    </x-backend.card>
@endsection
