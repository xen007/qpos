{{--
    Skin AdminLTE d'une entree de navigation.
    Ne contient aucune logique de permission : les donnees viennent de
    App\Support\BackendMenu (source unique) et sont deja filtrees.
    Recursif : un groupe rend ses enfants avec le meme composant.
--}}
@props(['item'])

@if ($item['type'] === \App\Support\BackendMenu::TYPE_HEADER)

    <li class="nav-header">{{ $item['label'] }}</li>

@elseif ($item['type'] === \App\Support\BackendMenu::TYPE_GROUP)

    <li class="nav-item {{ $item['active'] ? 'menu-open' : '' }}">
        <a href="#" class="nav-link {{ $item['active'] ? 'active' : '' }}">
            <i class="{{ $item['icon'] }} nav-icon"></i>
            <p>
                {{ $item['label'] }}
                <i class="fas fa-angle-left right"></i>
            </p>
        </a>
        <ul class="nav nav-treeview">
            @foreach ($item['children'] as $child)
                <x-backend.adminlte.nav-item :item="$child" />
            @endforeach
        </ul>
    </li>

@else

    <li class="nav-item">
        <a href="{{ $item['url'] }}" class="nav-link {{ $item['active'] ? 'active' : '' }}">
            <i class="{{ $item['icon'] }} nav-icon"></i>
            <p>{{ $item['label'] }}</p>
        </a>
    </li>

@endif
