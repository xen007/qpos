@extends('backend.master-tailwind')

@section('title', __('Update Currency'))

@section('content')
    <x-backend.card>
        <form action="{{ route('backend.admin.currencies.update', $currency->id) }}" method="post" class="accountForm"
            enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <x-backend.input name="name" :label="__('Name')" :value="$currency->name"
                    :placeholder="__('Enter name')" required />

                <x-backend.input name="code" :label="__('Code')" :value="$currency->code"
                    :placeholder="__('Enter short code')" required />

                <x-backend.input name="symbol" :label="__('Symbol')" :value="$currency->symbol"
                    :placeholder="__('Enter symbol')" required />
            </div>

            <div class="mt-6">
                <button type="submit"
                    class="rounded-lg bg-qpos-brand px-6 py-2 text-sm font-semibold text-white transition hover:opacity-90">
                    {{ __('Update') }}
                </button>
            </div>
        </form>
    </x-backend.card>
@endsection
