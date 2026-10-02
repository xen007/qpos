@extends('backend.master-tailwind')

@section('title', __('Create Currency'))

@section('content')
    <x-backend.card>
        {{-- Page sans champ image : le script image-field.js que poussait
             l'ancienne version n'a plus lieu d'etre charge. --}}
        <form action="{{ route('backend.admin.currencies.store') }}" method="post" class="accountForm"
            enctype="multipart/form-data">
            @csrf

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <x-backend.input name="name" :label="__('Name')" :placeholder="__('Enter name')" required />

                <x-backend.input name="code" :label="__('Code')" :placeholder="__('Enter short code')" required />

                <x-backend.input name="symbol" :label="__('Symbol')" :placeholder="__('Enter symbol')" required />
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
