{{--
    Liste deroulante (skin Tailwind).

    `options` est un tableau valeur => libelle. `selected` est la valeur courante
    (retablie par old() apres un echec de validation). `placeholder` ajoute une
    premiere option a valeur vide, comme les formulaires d'origine.
--}}
@props(['name', 'label' => null, 'options' => [], 'selected' => null, 'placeholder' => null, 'required' => false])

@php
    $current = old($name, $selected);
@endphp

<div>
    @if ($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-qpos-ink">
            {{ $label }}
            @if ($required)
                <span class="text-red-600" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    <select name="{{ $name }}" id="{{ $name }}"
        @if ($required) required @endif
        {{ $attributes->merge([
            'class' => 'mt-1 w-full rounded-lg border border-qpos-line bg-qpos-surface px-3 py-2 text-sm text-qpos-ink focus:border-qpos-brand focus:outline-none',
        ]) }}>
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach ($options as $value => $text)
            <option value="{{ $value }}" @selected((string) $current === (string) $value)>{{ $text }}</option>
        @endforeach
    </select>

    @if (isset($errors) && $errors->has($name))
        <p class="mt-1 text-xs text-red-600">{{ $errors->first($name) }}</p>
    @endif
</div>
