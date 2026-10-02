@extends('backend.master-tailwind')

@section('title', __('Create Customer'))

@section('content')
    <x-backend.card>
        <form action="{{ route('backend.admin.customers.store') }}" method="post" class="accountForm"
            enctype="multipart/form-data">
            @csrf

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <x-backend.input name="name" :label="__('Name')" :placeholder="__('Enter title')" required />

                <x-backend.input name="phone" :label="__('Phone')" :placeholder="__('Enter phone')" required />

                <x-backend.input name="address" :label="__('Address')" :placeholder="__('Enter Address')" />
            </div>

            <div class="mt-6">
                <button type="submit"
                    class="qpos-button qpos-button-md qpos-button-primary rounded-lg bg-qpos-brand px-6 py-2 text-sm font-semibold text-white transition hover:opacity-90">
                    {{ __('Create') }}
                </button>
            </div>
        </form>
    </x-backend.card>
@endsection
