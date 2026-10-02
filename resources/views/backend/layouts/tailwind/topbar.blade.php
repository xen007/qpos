{{--
    Barre superieure du shell Tailwind.
    Aucune dependance Bootstrap/AdminLTE : menus et plein ecran sont geres par
    resources/js/shell.js, la bascule clair/sombre par resources/js/theme.js.
--}}
<header
    class="qpos-topbar sticky top-0 z-20 flex h-20 items-center gap-2 border-b border-qpos-line bg-qpos-surface px-3 sm:gap-3 sm:px-6 lg:px-8 print:hidden">
    <button type="button" data-qpos-drawer-toggle aria-controls="qpos-sidebar" aria-expanded="false"
        aria-label="{{ __('Open navigation menu') }}"
        class="qpos-shell-control flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-qpos-page text-qpos-muted transition hover:bg-qpos-brand-soft hover:text-qpos-brand-ink lg:hidden">
        <x-backend.icon name="menu" />
    </button>

    <div class="qpos-topbar-context hidden min-w-0 items-center gap-2 lg:flex"><x-backend.icon name="layout-dashboard" /><span class="truncate">@yield('title')</span></div>

    <div class="flex min-w-0 flex-1 items-center justify-end gap-1 sm:gap-3">
        @can('sale_create')
            <a href="{{ route('backend.admin.cart.index') }}"
                aria-label="{{ __('POS') }}" title="{{ __('POS') }}"
                class="qpos-shell-link inline-flex h-10 shrink-0 items-center gap-2 rounded-xl bg-qpos-brand px-3 text-sm font-semibold text-white transition hover:bg-qpos-brand-hover">
                <x-backend.icon name="shopping-cart" />
                <span class="hidden sm:inline">{{ __('POS') }}</span>
            </a>
        @endcan

        <x-language-switcher variant="tailwind" />

        <button type="button" data-theme-toggle aria-pressed="false" aria-label="{{ __('Toggle color theme') }}"
            title="{{ __('Toggle color theme') }}"
            class="qpos-shell-control flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-qpos-page text-qpos-muted transition hover:bg-qpos-brand-soft hover:text-qpos-brand-ink">
            <span class="qpos-theme-glyph" aria-hidden="true"><x-backend.icon name="moon" class="qpos-theme-moon" /><x-backend.icon name="sun" class="qpos-theme-sun" /></span>
        </button>

        <button type="button" data-qpos-fullscreen aria-label="{{ __('Toggle fullscreen') }}"
            title="{{ __('Toggle fullscreen') }}"
            class="qpos-shell-control hidden h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-qpos-page text-qpos-muted transition hover:bg-qpos-brand-soft hover:text-qpos-brand-ink sm:flex">
            <x-backend.icon name="maximize" />
        </button>

        <x-backend.dropdown>
            <x-slot:trigger aria-label="{{ __('Profile') }}" title="{{ auth()->user()->name }}">
                <span class="qpos-profile-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                <span class="hidden max-w-40 truncate sm:inline">{{ auth()->user()->name }}</span>
                <x-backend.icon name="chevron-down" class="hidden sm:inline" />
            </x-slot:trigger>

            <a href="{{ route('backend.admin.profile') }}"
                class="flex items-center gap-3 px-4 py-2 text-sm text-qpos-ink transition hover:bg-qpos-page">
                <x-backend.icon name="contact-round" class="text-qpos-brand-ink" />
                {{ __('Profile') }}
            </a>

            <form action="{{ route('logout') }}" method="post">
                @csrf
                <button type="submit"
                    class="flex w-full items-center gap-3 px-4 py-2 text-left text-sm text-qpos-ink transition hover:bg-qpos-page">
                    <x-backend.icon name="log-out" class="text-qpos-danger" />
                    {{ __('Logout') }}
                </button>
            </form>
        </x-backend.dropdown>
    </div>
</header>
@if (($pointOfSaleContextReady ?? false))
    <div class="border-b border-qpos-line bg-qpos-surface px-3 py-2 sm:px-6 lg:px-8 print:hidden">
        @include('backend.shops.selector')
    </div>
@endif
