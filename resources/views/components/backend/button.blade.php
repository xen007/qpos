{{--
    Bouton (skin Tailwind) : lien si `href` est fourni, bouton sinon.
    Memes classes que les boutons ecrits a la main dans les pages deja migrees,
    pour un rendu identique.
--}}
@props(['type' => 'submit', 'variant' => 'primary', 'size' => 'md', 'icon' => null, 'href' => null])

@php
    $variants = [
        'primary' => 'bg-qpos-brand text-white hover:opacity-90',
        'ghost' => 'border border-qpos-line text-qpos-muted hover:bg-qpos-page',
    ];

    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-6 py-2 text-sm',
    ];

    $classes = 'inline-flex items-center justify-center gap-2 rounded-lg font-semibold transition '
        . ($sizes[$size] ?? $sizes['md'])
        . ' '
        . ($variants[$variant] ?? $variants['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)
            <i class="{{ $icon }}" aria-hidden="true"></i>
        @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)
            <i class="{{ $icon }}" aria-hidden="true"></i>
        @endif
        {{ $slot }}
    </button>
@endif
