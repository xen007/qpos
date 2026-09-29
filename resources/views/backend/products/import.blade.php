@extends('backend.master-tailwind')

@section('title', __('Product Import'))

@section('content')
    <x-backend.card>
        {{-- Page sans champ image ni select2 : les scripts que poussait l'ancienne
             version n'ont plus lieu d'etre charges. --}}
        <form action="{{ route('backend.admin.products.import') }}" method="post" class="accountForm"
            enctype="multipart/form-data">
            @csrf

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <div>
                    <label for="exampleInputFile" class="block text-sm font-medium text-qpos-ink">
                        {{ __('File input') }}
                        <span class="text-red-600" aria-hidden="true">*</span>
                    </label>

                    <input type="file" name="file" id="exampleInputFile" required
                        class="mt-1 w-full rounded-lg border border-qpos-line bg-qpos-surface px-3 py-2 text-sm text-qpos-ink file:mr-3 file:rounded-md file:border-0 file:bg-qpos-brand file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white focus:border-qpos-brand focus:outline-none">

                    <p class="mt-2 text-xs">
                        <a href="{{ route('backend.admin.products.import', ['download-demo' => true]) }}"
                            class="inline-flex items-center gap-1 font-medium text-qpos-brand transition hover:opacity-80">
                            <i class="fas fa-download" aria-hidden="true"></i>
                            {{ __('Download sample') }}
                        </a>
                    </p>
                </div>
            </div>

            <div class="mt-6">
                <button type="submit"
                    class="w-full rounded-lg bg-qpos-brand px-6 py-2 text-sm font-semibold text-white transition hover:opacity-90 lg:w-auto">
                    {{ __('Import') }}
                </button>
            </div>
        </form>
    </x-backend.card>
@endsection
