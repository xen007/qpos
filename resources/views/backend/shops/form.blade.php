@extends('backend.master-tailwind')
@section('title', $shop->exists ? __('Edit store') : __('Add store'))
@section('content')
<x-backend.card>
    <a class="qpos-button qpos-button-md qpos-button-secondary mb-5" href="{{ route('backend.admin.shops.index') }}">{{ __('Back') }}</a>
    @php
        $chosenIds = session()->hasOldInput('assignment_payload') ? old('user_ids', []) : $assignedIds;
    @endphp
    <form method="post" action="{{ $shop->exists ? route('backend.admin.shops.update', $shop) : route('backend.admin.shops.store') }}">
        @csrf @if ($shop->exists) @method('PUT') @endif
        <div class="grid gap-5 sm:grid-cols-2">
            <x-backend.input name="code" id="store-code" :label="__('Code')" :value="$shop->code" maxlength="64" :required="$shop->exists" />
            <x-backend.input name="name" id="store-name" :label="__('Name')" :value="$shop->name" maxlength="255" required />
            <x-backend.input name="address" :label="__('Address')" :value="$shop->address" maxlength="255" />
            <x-backend.switch name="is_active" :label="__('Active')" :checked="$shop->is_active" />
        </div>
        @if ($canAssign)
            <fieldset class="mt-6">
                <legend class="mb-3 font-semibold">{{ __('Store assignments') }}</legend>
                <p class="mb-4 text-sm text-qpos-muted">{{ __('Assignments grant store scope; roles still control capabilities. No user is assigned automatically.') }}</p>
                <input type="hidden" name="assignment_payload" value="1">
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($users as $user)
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" @checked(in_array($user->id, $chosenIds))>
                            <span>{{ $user->name }}</span>
                        </label>
                    @endforeach
                </div>
                @error('user_ids')<p role="alert" class="mt-2 text-qpos-danger">{{ $message }}</p>@enderror
                @foreach ($errors->get('user_ids.*') as $messages)
                    @foreach ($messages as $message)<p role="alert" class="mt-2 text-qpos-danger">{{ $message }}</p>@endforeach
                @endforeach
            </fieldset>
        @endif
        <button class="qpos-button qpos-button-md qpos-button-primary mt-6" type="submit">{{ __('Save') }}</button>
    </form>
    @if (!$shop->exists)
        <script>
            (() => {
                const name = document.getElementById('store-name');
                const code = document.getElementById('store-code');
                if (!name || !code) return;
                let manual = code.value.trim() !== '';
                const generate = value => {
                    const words = value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').match(/[a-zA-Z0-9]+/g) || [];
                    const base = words.length > 1 ? words.map(word => word[0]).join('') : (words[0] || '').slice(0, 3);
                    return base.toUpperCase().slice(0, 50);
                };
                code.addEventListener('input', () => { manual = code.value.trim() !== ''; });
                name.addEventListener('input', () => {
                    if (!manual) code.value = generate(name.value);
                });
                if (!manual) code.value = generate(name.value);
            })();
        </script>
    @endif
</x-backend.card>
@endsection
