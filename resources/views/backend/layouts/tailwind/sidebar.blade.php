@php
    $navItems = \App\Support\BackendMenu::items();
    $siteName = readConfig('site_name') ?: 'QPOS';
    $siteLogo = readConfig('site_logo');
@endphp

{{-- Tiroir sur mobile, fixe a partir du breakpoint lg (voir lg:pl-72 du contenu). --}}
<aside id="qpos-sidebar" data-qpos-drawer aria-label="{{ __('Main navigation') }}"
    class="fixed inset-y-0 left-0 z-40 flex w-72 max-w-[calc(100vw-1.5rem)] -translate-x-full flex-col border-r border-qpos-line bg-qpos-sidebar shadow-qpos transition-transform duration-200 lg:translate-x-0 lg:shadow-none print:hidden">

    <div class="flex h-20 shrink-0 items-center justify-between gap-2 border-b border-qpos-line px-5">
        <a href="{{ route('frontend.home') }}" class="qpos-shell-link flex min-w-0 items-center gap-3 rounded-xl">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-qpos-brand-soft text-qpos-brand-ink">
                @if ($siteLogo)
                    <img src="{{ assetImage($siteLogo) }}" alt="{{ __('Logo') }}" class="h-8 w-8 rounded-lg object-contain">
                @else
                    <span class="text-lg font-bold" aria-hidden="true">{{ mb_substr($siteName, 0, 1) }}</span>
                @endif
            </span>
            <span class="truncate text-base font-bold tracking-tight text-qpos-ink">{{ $siteName }}</span>
        </a>
        <button type="button" data-qpos-drawer-toggle aria-controls="qpos-sidebar" aria-expanded="false"
            aria-label="{{ __('Close') }}"
            class="qpos-shell-control flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-qpos-page text-qpos-muted transition hover:bg-qpos-brand-soft hover:text-qpos-brand-ink lg:hidden">
            <i class="fas fa-times" aria-hidden="true"></i>
        </button>
    </div>

    <nav class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-3 py-5">
        <ul class="space-y-1">
            @foreach ($navItems as $navItem)
                <x-backend.tailwind.nav-item :item="$navItem" />
            @endforeach
        </ul>
    </nav>
</aside>
