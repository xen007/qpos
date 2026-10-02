{{--
    Bouton (skin Tailwind) : lien si `href` est fourni, bouton sinon.
    Memes classes que les boutons ecrits a la main dans les pages deja migrees,
    pour un rendu identique.
--}}
@props(['type' => 'submit', 'variant' => 'primary', 'size' => 'md', 'icon' => null, 'href' => null, 'loading' => false])

@php
    $variants = [
        'primary' => 'qpos-button-primary',
        'secondary' => 'qpos-button-secondary',
        'ghost' => 'qpos-button-ghost',
        'danger' => 'qpos-button-danger',
    ];

    $sizes = [
        'sm' => 'qpos-button-sm',
        'md' => 'qpos-button-md',
        'lg' => 'qpos-button-lg',
    ];

    $classes = 'qpos-button '
        . ($sizes[$size] ?? $sizes['md'])
        . ' '
        . ($variants[$variant] ?? $variants['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)
            <x-backend.icon :name="$icon" />
        @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" @if ($loading) disabled aria-busy="true" @endif {{ $attributes->merge(['class' => $classes]) }}>
        @if ($loading)
            <x-backend.icon name="loader-circle" class="qpos-spin" />
        @elseif ($icon)
            <x-backend.icon :name="$icon" />
        @endif
        {{ $slot }}
    </button>
@endif
