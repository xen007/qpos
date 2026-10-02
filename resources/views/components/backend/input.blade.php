{{--
    Champ de saisie (skin Tailwind).

    La valeur passe par old() : le composant affiche automatiquement la saisie
    precedente apres un echec de validation, puis la valeur fournie.
--}}
@props(['name', 'label' => null, 'value' => null, 'type' => 'text', 'required' => false, 'placeholder' => null, 'icon' => null])

<div>
    @if ($label)
        <label for="{{ $name }}" class="flex items-center gap-2 text-sm font-medium text-qpos-ink">
            @if ($icon)
                <x-backend.icon :name="$icon" class="text-qpos-brand-ink" />
            @endif
            <span>
                {{ $label }}
                @if ($required)
                    <span class="text-red-600" aria-hidden="true">*</span>
                @endif
            </span>
        </label>
    @endif

    <input type="{{ $type }}" name="{{ $name }}" id="{{ $name }}" value="{{ old($name, $value) }}"
        @if ($required) required @endif
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if (isset($errors) && $errors->has($name)) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
        {{ $attributes->merge([
            'class' => 'qpos-control mt-1 w-full',
        ]) }}>

    @if (isset($errors) && $errors->has($name))
        <p id="{{ $name }}-error" class="mt-1 text-xs text-qpos-danger" role="alert">{{ $errors->first($name) }}</p>
    @endif
</div>
