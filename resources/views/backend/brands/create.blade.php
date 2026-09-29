@extends('backend.master-tailwind')

@section('title', __('Create Brand'))

@section('content')
    <x-backend.card>
        <form action="{{ route('backend.admin.brands.store') }}" method="post" class="accountForm"
            enctype="multipart/form-data">
            @csrf

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <x-backend.input name="name" :label="__('Name')" :placeholder="__('Enter title')" required />

                <x-backend.image-field name="brand_image" :label="__('Image')" />

                <div class="lg:col-span-2">
                    <x-backend.textarea name="description" :label="__('Description')"
                        :placeholder="__('Enter description')" />
                </div>

                <div class="lg:col-span-2">
                    <x-backend.switch name="status" :label="__('Active')" :checked="true" />
                </div>
            </div>

            <div class="mt-6">
                <button type="submit"
                    class="rounded-lg bg-qpos-brand px-6 py-2 text-sm font-semibold text-white transition hover:opacity-90">
                    {{ __('Create') }}
                </button>
            </div>
        </form>
    </x-backend.card>
@endsection
