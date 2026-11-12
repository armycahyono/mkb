@inject('layoutHelper', 'JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper')

{{-- Main header (AdminLTE v4 navbar) --}}

<nav class="app-header navbar {{ config('adminlte.classes_topnav_nav', 'navbar-expand') }} {{ config('adminlte.classes_topnav', 'bg-body') }}">

    <div class="{{ config('adminlte.classes_topnav_container', 'container-fluid') }}">

        {{-- Navbar left links --}}
        <ul class="navbar-nav">
            {{-- Left sidebar toggler link --}}
            @include('adminlte::partials.navbar.menu-item-left-sidebar-toggler')

            {{-- Configured left links --}}
            @each('adminlte::partials.navbar.menu-item', $adminlte->menu('navbar-left'), 'item')

            {{-- Custom left links --}}
            @yield('content_top_nav_left')
        </ul>

        {{-- Navbar right links --}}
        <ul class="navbar-nav ms-auto align-items-center">
            {{-- Fiscal Year Selector --}}
            @if(Auth::check())
                @include('partials.fiscal_year_selector')

                {{-- Compact Mode Toggle (Laptop & High-Density Space Saver) --}}
                <li class="nav-item me-2" style="margin-right: 8px;">
                    <button type="button" id="btnToggleCompactMode" class="btn btn-sm d-flex align-items-center py-1 px-2 border" 
                            style="font-size: 0.8rem; font-weight: 600; border-radius: 6px; background: #f8fafc; color: #475569; transition: all 0.15s ease-in-out;"
                            title="Klik untuk Mengaktifkan / Mematikan Mode Kompak (Hemat ruang untuk laptop)">
                        <i class="fas fa-compress-arrows-alt me-1 mr-1 text-primary" id="compactModeIcon"></i>
                        <span class="d-none d-sm-inline" id="compactModeLabel">Kompak: <strong id="compactModeStatus" class="text-secondary">Off</strong></span>
                        <span class="d-inline d-sm-none" id="compactModeLabelShort">Kompak</span>
                    </button>
                </li>
            @endif

            {{-- Custom right links --}}
            @yield('content_top_nav_right')

            {{-- Configured right links --}}
            @each('adminlte::partials.navbar.menu-item', $adminlte->menu('navbar-right'), 'item')

            {{-- User menu link --}}
            @if(Auth::user())
                @if(config('adminlte.usermenu_enabled'))
                    @include('adminlte::partials.navbar.menu-item-dropdown-user-menu')
                @else
                    @include('adminlte::partials.navbar.menu-item-logout-link')
                @endif
            @endif

            {{-- Right sidebar (offcanvas) toggler link --}}
            @if($layoutHelper->isRightSidebarEnabled())
                @include('adminlte::partials.navbar.menu-item-right-sidebar-toggler')
            @endif
        </ul>

    </div>

</nav>
