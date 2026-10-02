{{--
    Interrupteur d'etat (skin Tailwind).

    Reproduit le motif des formulaires existants : un champ cache a 0 precede la
    case a cocher a 1, si bien qu'une case decochee envoie bien 0.
--}}
@props(['name', 'label' => null, 'checked' => false])

<div>
    <input type="hidden" name="{{ $name }}" value="0">

    <label for="{{ $name }}" class="qpos-switch inline-flex items-center gap-3">
        <input type="checkbox" name="{{ $name }}" id="{{ $name }}" value="1"
            @checked((string) old($name, $checked ? '1' : '0') === '1')
            {{ $attributes->merge([
                'class' => 'qpos-switch-input',
            ]) }}>
        @if ($label)
            <span class="text-sm text-qpos-ink">{{ $label }}</span>
        @endif
    </label>

    @if (isset($errors) && $errors->has($name))
        <p class="mt-1 text-xs text-qpos-danger">{{ $errors->first($name) }}</p>
    @endif
</div>
