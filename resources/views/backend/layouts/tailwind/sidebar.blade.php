@php
    $navItems = \App\Support\BackendMenu::items();
@endphp

{{-- Tiroir sur mobile, fixe a partir du breakpoint lg (voir lg:pl-72 du contenu). --}}
<aside id="qpos-sidebar" data-qpos-drawer aria-label="{{ __('Main navigation') }}"
    class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col border-r border-qpos-line bg-qpos-sidebar transition-transform duration-200 lg:translate-x-0 print:hidden">

    <div class="flex h-16 shrink-0 items-center border-b border-qpos-line px-5">
        <a href="{{ route('frontend.home') }}" class="flex min-w-0 items-center gap-3">
            <img src="{{ assetImage(readConfig('site_logo')) }}" alt="{{ __('Logo') }}" class="h-9 w-9 rounded-full">
            <span class="truncate text-sm font-semibold text-qpos-ink">{{ readConfig('site_name') }}</span>
        </a>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4">
        <ul class="space-y-1">
            @foreach ($navItems as $navItem)
                <x-backend.tailwind.nav-item :item="$navItem" />
            @endforeach
        </ul>
    </nav>
</aside>
