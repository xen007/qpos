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
                <i class="{{ $icon }} text-qpos-muted" aria-hidden="true"></i>
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
        {{ $attributes->merge([
            'class' => 'mt-1 w-full rounded-lg border border-qpos-line bg-qpos-surface px-3 py-2 text-sm text-qpos-ink placeholder:text-qpos-muted focus:border-qpos-brand focus:outline-none',
        ]) }}>

    @if (isset($errors) && $errors->has($name))
        <p class="mt-1 text-xs text-red-600">{{ $errors->first($name) }}</p>
    @endif
</div>
