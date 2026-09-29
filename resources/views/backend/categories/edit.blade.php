@extends('backend.master-tailwind')

@section('title', __('Update Category'))

@section('content')
    <x-backend.card>
        <form action="{{ route('backend.admin.categories.update', $category->id) }}" method="post" class="accountForm"
            enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <x-backend.input name="name" :label="__('Name')" :value="$category->name"
                    :placeholder="__('Enter title')" required />

                <x-backend.image-field name="category_image" :label="__('Image')"
                    :current-image="$category->image" />

                <div class="lg:col-span-2">
                    <x-backend.textarea name="description" :label="__('Description')" :value="$category->description"
                        :placeholder="__('Enter description')" />
                </div>

                <div class="lg:col-span-2">
                    <x-backend.switch name="status" :label="__('Active')" :checked="$category->status == 1" />
                </div>
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
