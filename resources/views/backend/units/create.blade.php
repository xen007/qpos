@extends('backend.master-tailwind')

@section('title', __('Create Unit'))

@section('content')
    <x-backend.card>
        {{-- Cette page n'a pas de champ image : le script image-field.js que
             poussait l'ancienne version n'a plus lieu d'etre charge. --}}
        <form action="{{ route('backend.admin.units.store') }}" method="post" class="accountForm"
            enctype="multipart/form-data">
            @csrf

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <x-backend.input name="title" :label="__('Title')" :placeholder="__('Enter title')" required />

                <x-backend.input name="short_name" :label="__('Short Name')" :placeholder="__('Enter Short Name')"
                    required />
            </div>

            @if ($catalogueUnitsReady)
                <div class="mt-4"><x-backend.switch name="is_active" :label="__('Active')" :checked="true" /></div>
            @endif
            <div class="mt-6">
                <button type="submit"
                    class="qpos-button qpos-button-md qpos-button-primary rounded-lg bg-qpos-brand px-6 py-2 text-sm font-semibold text-white transition hover:opacity-90">
                    {{ __('Create') }}
                </button>
            </div>
        </form>
    </x-backend.card>
@endsection
