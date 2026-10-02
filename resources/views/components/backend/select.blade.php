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
        @if (isset($errors) && $errors->has($name)) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
        {{ $attributes->merge([
            'class' => 'qpos-control mt-1 w-full',
        ]) }}>
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach ($options as $value => $text)
            <option value="{{ $value }}" @selected((string) $current === (string) $value)>{{ $text }}</option>
        @endforeach
    </select>

    @if (isset($errors) && $errors->has($name))
        <p id="{{ $name }}-error" class="mt-1 text-xs text-qpos-danger" role="alert">{{ $errors->first($name) }}</p>
    @endif
</div>
