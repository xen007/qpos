{{--
    Layout Tailwind du back-office (pages migrees).
    ---------------------------------------------------------------------------
    Principes :
      - Tailwind uniquement : ni AdminLTE, ni Bootstrap (CSS et JS). Les pages
        declarent leurs plugins via @push('style') / @push('script').
      - Le theme clair/sombre s'appuie sur public/css/qpos-tokens.css (jetons)
        et sur resources/js/theme.js (bascule).
      - Les notifications flash passent par Sonner (x-backend.flash-toasts).
    Le layout AdminLTE (backend.master) reste en place pour les pages non
    encore migrees.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>
        (() => {
            const savedTheme = localStorage.getItem('qpos-theme');
            const systemPrefersDark = window.matchMedia?.('(prefers-color-scheme: dark)').matches;
            document.documentElement.dataset.theme = savedTheme || (systemPrefersDark ? 'dark' : 'light');
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
    <!-- Icones -->
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome-free/css/all.min.css') }}">

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
    </script>

    @stack('style')
</head>

<body class="min-h-screen bg-qpos-page text-qpos-ink antialiased">

    {{-- Notifications flash (Sonner) --}}
    <x-backend.flash-toasts />

    {{-- Voile du tiroir de navigation (mobile) --}}
    <div data-qpos-drawer-overlay class="fixed inset-0 z-30 hidden bg-black/50 lg:hidden"></div>

    {{-- Navigation laterale --}}
    @include('backend.layouts.tailwind.sidebar')

    <div class="flex min-h-screen flex-col lg:pl-72">

        {{-- Barre superieure --}}
        @include('backend.layouts.tailwind.topbar')

        <!-- Contenu de la page -->
        <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8 print:p-0">
            <div class="print:hidden">
                <x-backend.breadcrumbs />
            </div>

            <div class="mt-3 flex flex-wrap items-center justify-between gap-3 print:hidden">
                <h1 class="text-2xl font-semibold tracking-tight text-qpos-ink">@yield('title')</h1>

                @hasSection('page-actions')
                    <div class="flex flex-wrap items-center gap-2">@yield('page-actions')</div>
                @endif
            </div>

            <div class="mt-6">
                @yield('content')
            </div>
        </main>

        @include('backend.layouts.tailwind.footer')

    </div>

    {{-- Comportements du shell (tiroir, menus, plein ecran) --}}
    @vite('resources/js/shell.js')

    @stack('script')
</body>

</html>
