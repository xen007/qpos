@extends('backend.master-tailwind')

@section('title', __('Update Unit'))

@section('content')
    <x-backend.card>
        <form action="{{ route('backend.admin.units.update', $unit->id) }}" method="post" class="accountForm"
            enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <x-backend.input name="title" :label="__('Title')" :value="$unit->title"
                    :placeholder="__('Enter title')" required />

                <x-backend.input name="short_name" :label="__('Short Name')" :value="$unit->short_name"
                    :placeholder="__('Enter Short Name')" required />
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
