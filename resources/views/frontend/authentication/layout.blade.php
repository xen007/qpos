<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-palette="{{ \App\Support\SitePalette::current() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | {{ readConfig('site_name') ?: 'QPOS' }}</title>
    <script>
        (() => {
            let saved;
            try { saved = localStorage.getItem('qpos-theme'); } catch (_) {}
            document.documentElement.dataset.theme = ['light', 'dark'].includes(saved)
                ? saved : (window.matchMedia?.('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        })();
    </script>
    <link rel="icon" href="{{ assetImage(readConfig('favicon_icon')) }}">
    <link rel="preload" href="{{ asset('fonts/inter/InterVariable.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/qpos-tokens.css') }}">
    @vite(['resources/css/auth.css', 'resources/js/theme.js', 'resources/js/frontend.js'])
</head>
<body class="qpos-public">
    <header class="qpos-public-header">
        <a href="{{ route('frontend.home') }}" class="qpos-public-logo">
            <span class="qpos-public-mark" aria-hidden="true">Q</span>
            <span>{{ readConfig('site_name') ?: 'QPOS' }}</span>
        </a>
        <div class="qpos-public-tools">
            <x-language-switcher variant="tailwind" />
            <button type="button" class="qpos-public-theme" data-theme-toggle aria-pressed="false" aria-label="{{ __('Toggle color theme') }}" title="{{ __('Toggle theme') }}">
                <span class="qpos-theme-glyph" aria-hidden="true"><x-backend.icon name="moon" class="qpos-theme-moon" /><x-backend.icon name="sun" class="qpos-theme-sun" /></span>
            </button>
        </div>
    </header>
    <main class="qpos-public-main">
        <section class="qpos-auth-story" aria-labelledby="qpos-story-title">
            <span class="qpos-auth-eyebrow">{{ __('Your daily workspace') }}</span>
            <h2 id="qpos-story-title">{{ __('Your business, in one place.') }}</h2>
            <p>{{ __('Sales, stock and stores. A clear view of your daily business.') }}</p>
            <div class="qpos-auth-illustration" aria-hidden="true">
                <div class="qpos-illustration-top"><span></span><span></span><span></span></div>
                <div class="qpos-illustration-body">
                    <div class="qpos-illustration-menu"><span></span><span></span><span></span><span></span></div>
                    <div class="qpos-illustration-content"><div class="qpos-illustration-cards"><span></span><span></span><span></span></div><div class="qpos-illustration-chart"><span></span><span></span><span></span><span></span><span></span></div><div class="qpos-illustration-line"></div><div class="qpos-illustration-line"></div></div>
                </div>
            </div>
            <span class="qpos-auth-story-footer"><span aria-hidden="true">●</span> {{ __('A workspace that keeps things simple.') }}</span>
        </section>
        <section class="qpos-auth-panel" aria-labelledby="qpos-page-title">
            <div class="qpos-auth-card">
                @hasSection('back') <div class="qpos-auth-back">@yield('back')</div> @endif
                <h1 id="qpos-page-title">@yield('title')</h1>
                <p class="qpos-auth-description">@yield('description')</p>
                @if (isset($errors) && $errors->any())
                    <div class="qpos-auth-alert qpos-auth-error" role="alert">
                        @foreach ($errors->all() as $error) <p>{{ $error }}</p> @endforeach
                    </div>
                @endif
                @foreach (['success', 'error', 'warning'] as $flash)
                    @if (session()->has($flash)) <div class="qpos-auth-alert qpos-auth-{{ $flash }}" role="status">{{ session($flash) }}</div> @endif
                @endforeach
                @yield('content')
            </div>
        </section>
    </main>
    <footer class="qpos-public-footer">© {{ date('Y') }} {{ readConfig('site_name') ?: 'QPOS' }}</footer>
</body>
</html>
