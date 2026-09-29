{{--
    Barre superieure du shell Tailwind.
    Aucune dependance Bootstrap/AdminLTE : menus et plein ecran sont geres par
    resources/js/shell.js, la bascule clair/sombre par resources/js/theme.js.
--}}
<header
    class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-qpos-line bg-qpos-surface px-4 sm:px-6 lg:px-8 print:hidden">
    <button type="button" data-qpos-drawer-toggle aria-controls="qpos-sidebar" aria-expanded="false"
        aria-label="{{ __('Open navigation menu') }}"
        class="flex h-10 w-10 items-center justify-center rounded-lg text-qpos-muted transition hover:bg-qpos-page hover:text-qpos-ink lg:hidden">
        <i class="fas fa-bars" aria-hidden="true"></i>
    </button>

    <div class="flex flex-1 items-center justify-end gap-2 sm:gap-3">
        @can('sale_create')
            <a href="{{ route('backend.admin.cart.index') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-qpos-brand px-3 py-2 text-sm font-semibold text-white transition hover:opacity-90">
                <i class="fas fa-cart-plus" aria-hidden="true"></i>
                <span class="hidden sm:inline">{{ __('POS') }}</span>
            </a>
        @endcan

        <x-language-switcher variant="tailwind" />

        <button type="button" data-theme-toggle aria-pressed="false" aria-label="{{ __('Toggle color theme') }}"
            title="{{ __('Toggle color theme') }}"
            class="flex h-10 w-10 items-center justify-center rounded-lg text-qpos-muted transition hover:bg-qpos-page hover:text-qpos-ink">
            <i class="fas fa-moon" data-theme-icon aria-hidden="true"></i>
        </button>

        <button type="button" data-qpos-fullscreen aria-label="{{ __('Toggle fullscreen') }}"
            title="{{ __('Toggle fullscreen') }}"
            class="hidden h-10 w-10 items-center justify-center rounded-lg text-qpos-muted transition hover:bg-qpos-page hover:text-qpos-ink sm:flex">
            <i class="fas fa-expand-arrows-alt" aria-hidden="true"></i>
        </button>

        <x-backend.dropdown>
            <x-slot:trigger>
                <i class="fas fa-user-circle text-lg" aria-hidden="true"></i>
                <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
                <i class="fas fa-angle-down text-xs" aria-hidden="true"></i>
            </x-slot:trigger>

            <a href="{{ route('backend.admin.profile') }}"
                class="flex items-center gap-3 px-4 py-2 text-sm text-qpos-ink transition hover:bg-qpos-page">
                <i class="fas fa-address-card text-qpos-muted" aria-hidden="true"></i>
                {{ __('Profile') }}
            </a>

            <form action="{{ route('logout') }}" method="post">
                @csrf
                <button type="submit"
                    class="flex w-full items-center gap-3 px-4 py-2 text-left text-sm text-qpos-ink transition hover:bg-qpos-page">
                    <i class="fas fa-sign-out-alt text-qpos-muted" aria-hidden="true"></i>
                    {{ __('Logout') }}
                </button>
            </form>
        </x-backend.dropdown>
    </div>
</header>
