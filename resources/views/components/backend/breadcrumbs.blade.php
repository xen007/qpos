{{--
    Fil d'Ariane.
    - Sur le tableau de bord, l'entree courante n'est pas un lien.
    - Ailleurs, une page peut remplacer le libelle courant via @section('breadcrumb').
--}}
<nav aria-label="{{ __('Breadcrumb') }}" class="flex flex-wrap items-center gap-2 text-sm">
    @if (request()->routeIs('backend.admin.dashboard'))
        <span class="inline-flex items-center gap-2 font-medium text-qpos-ink" aria-current="page">
            <i class="fas fa-tachometer-alt" aria-hidden="true"></i>
            {{ __('Dashboard') }}
        </span>
    @else
        <a href="{{ route('backend.admin.dashboard') }}"
            class="inline-flex items-center gap-2 text-qpos-muted transition hover:text-qpos-brand">
            <i class="fas fa-tachometer-alt" aria-hidden="true"></i>
            {{ __('Dashboard') }}
        </a>

        <i class="fas fa-angle-right text-xs text-qpos-muted" aria-hidden="true"></i>

        @hasSection('breadcrumb')
            <span class="font-medium text-qpos-ink" aria-current="page">@yield('breadcrumb')</span>
        @else
            <span class="font-medium text-qpos-ink" aria-current="page">@yield('title')</span>
        @endif
    @endif
</nav>
