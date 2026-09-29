@php
    $navItems = \App\Support\BackendMenu::items();
@endphp
<div class="sidebar">
    <!-- Sidebar user panel (optional) -->

    <!-- <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
            <img src="{{ auth()->user()->pro_pic }}" class="img-circle elevation-2" style="width: 2.5rem; height: 2.5rem;"
                alt="User Image">
        </div>
        <div class="info">
            <a href="{{ route('backend.admin.profile') }}" class="d-block">
                {{ auth()->user()->name }}
            </a>
        </div>
    </div> -->


    <!-- Sidebar Menu -->
    {{-- Les entrees et leurs permissions viennent de App\Support\BackendMenu :
         une seule source pour les skins AdminLTE et Tailwind. --}}
    <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
            @foreach ($navItems as $navItem)
                <x-backend.adminlte.nav-item :item="$navItem" />
            @endforeach
        </ul>
    </nav>
    <!-- /.sidebar-menu -->
</div>
