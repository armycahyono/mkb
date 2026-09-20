@inject('layoutHelper', 'JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper')

@php
    $dashboard_url = View::getSection('dashboard_url') ?? config('adminlte.dashboard_url', 'home');
    $dashboard_url = $layoutHelper->makeUrl($dashboard_url);

    $logoImg = asset(config('adminlte.logo_img', 'img/logo.png'));
    $logoImgAlt = config('adminlte.logo_img_alt', 'MKB GRIK Logo');
    $logoImgClass = config('adminlte.logo_img_class', 'brand-image shadow');
    $logoText = config('adminlte.logo', '<b>MKB</b> GRIK');
    $brandClass = config('adminlte.classes_brand', '');
    $brandTextClass = config('adminlte.classes_brand_text', 'fw-light');
@endphp

@if($layoutHelper->isLayoutTopnavEnabled())

    {{-- Navbar Brand (topnav layout) --}}
    <a href="{{ $dashboard_url }}" class="navbar-brand d-flex align-items-center {{ $brandClass }}">

        {{-- Brand logo --}}
        <img src="{{ $logoImg }}" alt="{{ $logoImgAlt }}" width="33" height="33"
             class="{{ $logoImgClass }} me-2" style="object-fit: contain;">

        {{-- Brand text --}}
        <span class="{{ $brandTextClass }}">{!! $logoText !!}</span>

    </a>

@else

    {{-- Sidebar Brand --}}
    <div class="sidebar-brand">
        <a href="{{ $dashboard_url }}" class="brand-link d-flex align-items-center px-3 py-2 {{ $brandClass }}">

            {{-- Brand logo --}}
            <img src="{{ $logoImg }}" alt="{{ $logoImgAlt }}" class="{{ $logoImgClass }} me-2" style="max-height: 36px; width: 36px; height: 36px; object-fit: contain; flex-shrink: 0;">

            {{-- Brand text --}}
            <span class="brand-text {{ $brandTextClass }}">{!! $logoText !!}</span>

        </a>
    </div>

@endif

