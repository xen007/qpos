@extends('backend.master-tailwind')

@section('title', __('Update Brand'))

@section('content')
    <x-backend.card>
        <form action="{{ route('backend.admin.brands.update', $brand->id) }}" method="post" class="accountForm"
            enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <x-backend.input name="name" :label="__('Name')" :value="$brand->name"
                    :placeholder="__('Enter title')" required />

                <x-backend.image-field name="brand_image" :label="__('Image')"
                    :current-image="$brand->image" />

                <div class="lg:col-span-2">
                    <x-backend.textarea name="description" :label="__('Description')" :value="$brand->description"
                        :placeholder="__('Enter description')" />
                </div>

                <div class="lg:col-span-2">
                    <x-backend.switch name="status" :label="__('Active')" :checked="$brand->status == 1" />
                </div>
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
