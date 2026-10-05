{{--
    Layout Tailwind du back-office (pages migrees).
    ---------------------------------------------------------------------------
    Principes :
      - Tailwind uniquement : ni AdminLTE, ni Bootstrap (CSS et JS). Les pages
        declarent leurs plugins via @push('style') / @push('script').
      - Le theme clair/sombre s'appuie sur public/css/qpos-tokens.css (jetons)
        et sur resources/js/theme.js (bascule).
      - Les notifications flash passent par Sonner (x-backend.flash-toasts).
    backend.master est un alias de compatibilite de ce shell unique.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-palette="{{ \App\Support\SitePalette::current() }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        (() => {
            let savedTheme;
            try {
                savedTheme = localStorage.getItem('qpos-theme');
            } catch (_) {
                // Le theme reste utilisable si le stockage navigateur est bloque.
            }
            const systemPrefersDark = window.matchMedia?.('(prefers-color-scheme: dark)').matches;
            document.documentElement.dataset.theme = ['light', 'dark'].includes(savedTheme)
                ? savedTheme
                : (systemPrefersDark ? 'dark' : 'light');
        })();
    </script>
    <title>
        @yield('title', __('Dashboard')) | {{ readConfig('site_name') }}
    </title>

    <!-- FAVICON ICON -->
    <link rel="shortcut icon" href="{{ assetImage(readconfig('favicon_icon')) }}" type="image/svg+xml">

    <!-- FAVICON ICON APPLE -->
    <link href="{{ assetImage(readconfig('favicon_icon_apple')) }}" rel="apple-touch-icon">
    <link href="{{ assetImage(readconfig('favicon_icon_apple')) }}" rel="apple-touch-icon" sizes="72x72">
    <link href="{{ assetImage(readconfig('favicon_icon_apple')) }}" rel="apple-touch-icon" sizes="114x114">
    <link href="{{ assetImage(readconfig('favicon_icon_apple')) }}" rel="apple-touch-icon" sizes="144x144">

    <!-- Jetons de design (clair/sombre) : source unique partagee avec AdminLTE -->
    <link rel="stylesheet" href="{{ asset('css/qpos-tokens.css') }}">
    <link rel="preload" href="{{ asset('fonts/inter/InterVariable.woff2') }}" as="font" type="font/woff2" crossorigin>

    {{-- Tailwind (compile, sans Preflight) + bascule du theme --}}
    @vite(['resources/css/app.css', 'resources/js/theme.js'])

    @php
        $localeCatalogPath = lang_path(app()->getLocale() . '.json');
        $localeCatalog = is_file($localeCatalogPath)
            ? json_decode(file_get_contents($localeCatalogPath), true)
            : [];
    @endphp
    <script>
        window.qposLocale = @json(app()->getLocale());
        window.qposTranslations = @json($localeCatalog ?? []);
        window.qposIconSprite = @json(asset('icons/lucide/sprite.svg'));
        window.qposIconAliases = @json(config('ui-icons', []));
        window.qposBaseUrl = @json(url('/'));
        window.qposStorageUrl = @json(asset('storage'));
        window.qposFallbackImage = @json(asset('assets/images/no-image.png'));
        window.qposPurchaseIndex = @json(route('backend.admin.purchase.index'));
        window.qposOperationShopId = @json(($selectedPointOfSale ?? null)?->id);
    </script>

    @stack('style')
</head>

<body class="qpos-shell min-h-screen bg-qpos-page font-sans text-qpos-ink antialiased">

    <a href="#qpos-main" class="qpos-skip-link">{{ __('Skip to content') }}</a>

    {{-- Notifications flash (Sonner) --}}
    <x-backend.flash-toasts />

    {{-- Voile du tiroir de navigation (mobile) --}}
    <div data-qpos-drawer-overlay class="fixed inset-0 z-30 hidden bg-black/50 lg:hidden print:hidden" aria-hidden="true"></div>

    {{-- Navigation laterale --}}
    @include('backend.layouts.tailwind.sidebar')

    <div data-qpos-shell-content class="flex min-w-0 min-h-screen flex-col lg:pl-72 print:pl-0">

        {{-- Barre superieure --}}
        @include('backend.layouts.tailwind.topbar')

        <!-- Contenu de la page -->
        <main id="qpos-main" tabindex="-1" class="min-w-0 flex-1 px-4 py-6 sm:px-6 lg:px-8 print:p-0">
            <div class="print:hidden">
                <x-backend.breadcrumbs />
            </div>

            @php
                $returnRoute = preg_replace('/\.(create|edit|show)$/', '.index', request()->route()?->getName() ?? '');
                $hasReturn = $returnRoute !== request()->route()?->getName() && \Illuminate\Support\Facades\Route::has($returnRoute);
            @endphp
            <div class="qpos-page-heading mt-3 flex flex-wrap items-center justify-between gap-3 print:hidden">
                <h1 class="text-2xl font-semibold tracking-tight text-qpos-ink">@yield('title')</h1>

                @if ($hasReturn || trim($__env->yieldContent('page-actions')) !== '')
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($hasReturn) <x-backend.back-button :href="route($returnRoute)" /> @endif
                        @yield('page-actions')
                    </div>
                @endif
            </div>

            <div class="qpos-page-content mt-6">
                @yield('content')
            </div>
        </main>

        @include('backend.layouts.tailwind.footer')

    </div>

    {{-- Comportements du shell (tiroir, menus, plein ecran) --}}
    @vite('resources/js/shell.js')

    @if (request()->routeIs('backend.admin.cart.index', 'backend.admin.purchase.create'))
        @viteReactRefresh
        @vite('resources/js/app.jsx')
    @endif

    @stack('script')
</body>

</html>
