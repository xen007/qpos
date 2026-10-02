{{--
    Fil d'Ariane.
    - Sur le tableau de bord, l'entree courante n'est pas un lien.
    - Ailleurs, une page peut remplacer le libelle courant via @section('breadcrumb').
--}}
<nav aria-label="{{ __('Breadcrumb') }}" class="qpos-breadcrumbs flex flex-wrap items-center gap-2 text-sm">
    @if (request()->routeIs('backend.admin.dashboard'))
        <span class="inline-flex items-center gap-2 font-medium text-qpos-ink" aria-current="page">
            <x-backend.icon name="layout-dashboard" />
            {{ __('Dashboard') }}
        </span>
    @else
        <a href="{{ route('backend.admin.dashboard') }}"
            class="inline-flex items-center gap-2 text-qpos-muted transition hover:text-qpos-brand">
            <x-backend.icon name="layout-dashboard" />
            {{ __('Dashboard') }}
        </a>

        <x-backend.icon name="chevron-right" class="text-qpos-muted" />

        @hasSection('breadcrumb')
            <span class="font-medium text-qpos-ink" aria-current="page">@yield('breadcrumb')</span>
        @else
            <span class="font-medium text-qpos-ink" aria-current="page">@yield('title')</span>
        @endif
    @endif
</nav>
