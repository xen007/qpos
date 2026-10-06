@extends('backend.master-tailwind')

@section('title', __('Update Supplier'))

@section('content')
    <x-backend.card>
        <form action="{{ route('backend.admin.suppliers.update', $supplier->id) }}" method="post" class="accountForm"
            enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <x-backend.input name="name" :label="__('Name')" :value="$supplier->name"
                    :placeholder="__('Enter title')" required />

                <x-backend.input name="phone" :label="__('Phone')" :value="$supplier->phone"
                    :placeholder="__('Enter phone')" />
                <input type="hidden" name="is_active" value="0">
                <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked($supplier->is_active)> {{ __('Active') }}</label>

                <x-backend.input name="address" :label="__('Address')" :value="$supplier->address"
                    :placeholder="__('Enter Address')" />
            </div>

            <div class="mt-6">
                <button type="submit"
                    class="qpos-button qpos-button-md qpos-button-primary rounded-lg bg-qpos-brand px-6 py-2 text-sm font-semibold text-white transition hover:opacity-90">
                    {{ __('Update') }}
                </button>
            </div>
        </form>
    </x-backend.card>
@endsection
