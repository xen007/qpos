{{--
    Skin Tailwind d'une entree de navigation.
    Donnees : App\Support\BackendMenu (source unique, deja filtree par permissions).
    Recursif : un groupe rend ses enfants avec le meme composant.
--}}
@props(['item'])

@php
    $panelId = 'qpos-nav-'.\Illuminate\Support\Str::slug($item['label']);
@endphp

@if ($item['type'] === \App\Support\BackendMenu::TYPE_HEADER)

    <li class="flex items-center gap-2 px-3 pb-2 pt-6 text-xs font-semibold uppercase tracking-wider text-qpos-muted">
        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-qpos-accent" aria-hidden="true"></span>
        {{ $item['label'] }}
    </li>

@elseif ($item['type'] === \App\Support\BackendMenu::TYPE_GROUP)

    <li>
        <button type="button" data-qpos-nav-toggle
            aria-controls="{{ $panelId }}" aria-expanded="{{ $item['active'] ? 'true' : 'false' }}"
            class="qpos-shell-control flex min-h-11 w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-medium transition hover:bg-qpos-brand-soft hover:text-qpos-brand-ink {{ $item['active'] ? 'bg-qpos-brand-soft text-qpos-brand-ink' : 'bg-transparent text-qpos-muted' }}">
            <i class="{{ $item['icon'] }} w-4 text-center" aria-hidden="true"></i>
            <span class="flex-1 truncate">{{ $item['label'] }}</span>
            <i class="fas fa-angle-left text-xs transition-transform {{ $item['active'] ? '-rotate-90' : '' }}"
                data-qpos-nav-chevron aria-hidden="true"></i>
        </button>

        <ul id="{{ $panelId }}" data-qpos-nav-panel
            class="my-2 ml-5 space-y-1 border-l border-qpos-line pl-3 {{ $item['active'] ? '' : 'hidden' }}">
            @foreach ($item['children'] as $child)
                <x-backend.tailwind.nav-item :item="$child" />
            @endforeach
        </ul>
    </li>

@else

    <li>
        <a href="{{ $item['url'] }}"
            class="qpos-shell-link flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ $item['active'] ? 'bg-qpos-brand text-white shadow-sm' : 'text-qpos-muted hover:bg-qpos-brand-soft hover:text-qpos-brand-ink' }}"
            @if ($item['active']) aria-current="page" @endif>
            <i class="{{ $item['icon'] }} w-4 text-center" aria-hidden="true"></i>
            <span class="truncate">{{ $item['label'] }}</span>
        </a>
    </li>

@endif
