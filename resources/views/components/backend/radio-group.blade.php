{{--
    Groupe de boutons radio (skin Tailwind).

    `options` est un tableau valeur => libelle ; `selected` est la valeur cochee
    (retablie par old() apres un echec de validation). Reprend le motif des
    reglages : deux choix exclusifs sur la meme ligne.
--}}
@props(['name', 'label' => null, 'options' => [], 'selected' => null])

@php
    $current = (string) old($name, $selected);
@endphp

<div>
    @if ($label)
        <span class="block text-sm font-medium text-qpos-ink">{{ $label }}</span>
    @endif

    <div class="mt-1 flex flex-wrap items-center gap-6">
        @foreach ($options as $value => $text)
            <label for="{{ $name }}-{{ $value }}" class="inline-flex items-center gap-2 text-sm text-qpos-ink">
                <input type="radio" name="{{ $name }}" id="{{ $name }}-{{ $value }}"
                    value="{{ $value }}" @checked($current === (string) $value)
                    {{ $attributes->merge([
                        'class' => 'h-4 w-4 border-qpos-line accent-qpos-brand focus:outline-none focus:ring-2 focus:ring-qpos-brand/40',
                    ]) }}>
                {{ $text }}
            </label>
        @endforeach
    </div>

    @if (isset($errors) && $errors->has($name))
        <p class="mt-1 text-xs text-red-600">{{ $errors->first($name) }}</p>
    @endif
</div>
