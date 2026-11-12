@inject('layoutHelper', 'JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper')
@inject('assetHelper', 'JeroenNoten\LaravelAdminLte\Helpers\AssetHelper')

@php
    // Resolve the Laravel asset bundling mode.

    $bundling = config('adminlte.laravel_asset_bundling', false);

    // When the user bundles the assets on its own, the AdminLTE core stylesheet
    // and script are expected to be part of the generated bundle.

    $bundlesAdminlte = in_array($bundling, ['mix', 'vite', 'vite_js_only'], true);

    // Resolve the location of every base asset. A null value means the asset
    // is disabled on the configuration file.

    $fontsCss = $assetHelper->fontsCss();
    $overlayScrollbarsCss = $assetHelper->overlayScrollbarsCss();
    $bootstrapIconsCss = $assetHelper->bootstrapIconsCss();
    $adminlteCss = $bundlesAdminlte ? null : $assetHelper->adminlteCss();
    $colorsCss = $bundlesAdminlte ? null : $assetHelper->colorsCss();

    $overlayScrollbarsJs = $assetHelper->overlayScrollbarsJs();
    $bootstrapJs = $assetHelper->bootstrapJs();
    $adminlteJs = $bundlesAdminlte ? null : $assetHelper->adminlteJs();

    // Bridge the Livewire navigation events to the AdminLTE lifecycle. The
    // template binds Turbo on its own, but it knows nothing about Livewire.

    $spaNavigation = config('adminlte.spa_navigation', true) !== false;

    // Resolve the color mode setup. The 'authored' color mode is the one the
    // page declares by itself, the 'auto' mode declares nothing and lets the
    // client resolve the mode from the OS preference.

    $colorMode = $layoutHelper->getColorMode();
    $authoredColorMode = $colorMode === 'auto' ? null : $colorMode;
    $rememberColorMode = (bool) config('adminlte.color_mode.remember', true);

    // The OverlayScrollbars setup only makes sense when there is a sidebar.

    $setupScrollbars = $overlayScrollbarsJs && ! $layoutHelper->isLayoutTopnavEnabled();

    // Extra options for the OverlayScrollbars instance of the sidebar. They
    // are merged into the 'scrollbars' object of its setup, so they may also
    // override the ones the dedicated options above resolve.

    $scrollbarOptions = config('adminlte.sidebar_scrollbar_options', []);

    $scrollbarExtraOptions = is_array($scrollbarOptions) && ! empty($scrollbarOptions)
        ? "\n".str_repeat(' ', 32).'...'.json_encode(
            $scrollbarOptions,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        ).','
        : '';

    // The viewport width (in pixels) at or below which the OverlayScrollbars
    // instance is not created, so touch scrolling is not disturbed.

    $scrollbarDisableBelow = config('adminlte.sidebar_scrollbar_disable_below', 992);

    $scrollbarDisableBelow = is_numeric($scrollbarDisableBelow)
        ? 0 + $scrollbarDisableBelow
        : 992;

    // The theme (a class name) of the sidebar scrollbars. OverlayScrollbars
    // expects a string here, anything else would be dropped by the plugin
    // together with the rest of its 'scrollbars' options.

    $scrollbarTheme = config('adminlte.sidebar_scrollbar_theme', 'os-theme-light');

    $scrollbarTheme = is_string($scrollbarTheme) && trim($scrollbarTheme) !== ''
        ? trim($scrollbarTheme)
        : 'os-theme-light';

    // The event that hides the sidebar scrollbars again. Only the tokens of
    // the OverlayScrollbars plugin are accepted, so an unsupported one does
    // not end up rejected (and reported on the console) by the plugin.

    $scrollbarAutoHide = config('adminlte.sidebar_scrollbar_auto_hide', 'leave');

    $scrollbarAutoHide = in_array($scrollbarAutoHide, ['never', 'scroll', 'leave', 'move'], true)
        ? $scrollbarAutoHide
        : 'leave';

    // The 'crossorigin' attribute is only required on the assets served from
    // an external origin (usually a CDN).

    $crossOrigin = static function ($url) {
        return preg_match('#^(https?:)?//#', (string) $url) ? ' crossorigin="anonymous"' : '';
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" {!! $layoutHelper->makeHtmlData() !!}>

<head>

    {{-- Base Meta Tags --}}
    <meta charset="utf-8">

    {{-- MKB Compact Mode (Early init to prevent layout shift) --}}
    <script>
        try {
            const savedCompact = localStorage.getItem('mkb_compact_mode');
            const isLaptop = window.innerWidth <= 1366 || window.innerHeight <= 800;
            if (savedCompact === 'true' || (savedCompact === null && isLaptop)) {
                document.documentElement.classList.add('compact-mode');
            } else if (savedCompact === 'false') {
                document.documentElement.classList.add('compact-mode-off');
            }
        } catch(e) {}
    </script>

    @if(config('adminlte.color_mode.no_flash_script', true))
        {{-- Theme Init (prevents a flash of the incorrect color mode on load) --}}
        <script>
            (() => {
                'use strict';
                const root = document.documentElement;

                // Applications with their own theming opt out of AdminLTE's
                // color mode entirely, here as well as in the bundle.
                if (root.getAttribute('data-lte-color-mode') === 'off') {
                    return;
                }

                const STORAGE_KEY = 'lte-theme';
                const authored = @json($authoredColorMode);
                let stored = null;

                @if($rememberColorMode)
                    try {
                        stored = localStorage.getItem(STORAGE_KEY);
                    } catch (e) {
                        // localStorage may be unavailable (private mode, sandboxed iframe).
                    }
                @endif

                // Mirror the precedence in color-mode.ts: the visitor's stored
                // choice wins, then a theme this page declared itself, then the
                // OS preference.
                let resolved = 'light';

                if (stored === 'dark' || stored === 'light') {
                    resolved = stored;
                } else if (authored === 'dark' || authored === 'light') {
                    resolved = authored;
                } else if (globalThis.matchMedia('(prefers-color-scheme: dark)').matches) {
                    resolved = 'dark';
                }

                root.setAttribute('data-bs-theme', resolved);
                root.style.colorScheme = resolved;

                // Flag values computed here, so the bundle does not mistake
                // them for a theme the page declared and stop following the OS
                // preference.
                if (resolved !== authored) {
                    root.setAttribute('data-lte-theme-resolved', '');
                }
            })();
        </script>
    @endif

    {{-- Accessibility Meta Tags --}}
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">

    {{-- PWA Meta Tags & Manifest --}}
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="MKB Mobile">
    <link rel="apple-touch-icon" href="{{ asset('img/logo.png') }}">

    @php($lightThemeColor = config('adminlte.color_mode.theme_color.light'))
    @php($darkThemeColor = config('adminlte.color_mode.theme_color.dark'))

    @if($lightThemeColor)
        <meta name="theme-color" content="{{ $lightThemeColor }}" media="(prefers-color-scheme: light)">
    @else
        <meta name="theme-color" content="#3B82F6">
    @endif

    @if($darkThemeColor)
        <meta name="theme-color" content="{{ $darkThemeColor }}" media="(prefers-color-scheme: dark)">
    @endif
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Custom Meta Tags --}}
    @yield('meta_tags')

    {{-- Title --}}
    <title>
        @yield('title_prefix', config('adminlte.title_prefix', ''))
        @yield('title', config('adminlte.title', 'AdminLTE 4'))
        @yield('title_postfix', config('adminlte.title_postfix', ''))
    </title>

    {{-- Custom stylesheets (pre AdminLTE) --}}
    @yield('adminlte_css_pre')

    {{-- Web Fonts --}}
    @isset($fontsCss)
        <link rel="stylesheet" href="{{ $fontsCss }}"{!! $crossOrigin($fontsCss) !!}>
    @endisset

    {{-- Third Party Plugin (OverlayScrollbars) --}}
    @isset($overlayScrollbarsCss)
        <link rel="stylesheet" href="{{ $overlayScrollbarsCss }}"{!! $crossOrigin($overlayScrollbarsCss) !!}>
    @endisset

    {{-- Third Party Plugin (Bootstrap Icons) --}}
    @isset($bootstrapIconsCss)
        <link rel="stylesheet" href="{{ $bootstrapIconsCss }}"{!! $crossOrigin($bootstrapIconsCss) !!}>
    @endisset

    {{-- Base Stylesheets (depends on the Laravel asset bundling tool) --}}
    @switch($bundling)
        @case('mix')
            <link rel="stylesheet" href="{{ mix(config('adminlte.laravel_css_path', 'css/app.css')) }}">
        @break

        @case('vite')
            @vite([config('adminlte.laravel_css_path', 'resources/css/app.css'), config('adminlte.laravel_js_path', 'resources/js/app.js')])
        @break

        @case('vite_js_only')
            @vite(config('adminlte.laravel_js_path', 'resources/js/app.js'))
        @break

        @default
            {{-- Required Plugin (AdminLTE) --}}
            @isset($adminlteCss)
                <link rel="stylesheet" href="{{ $adminlteCss }}">
            @endisset

            {{-- Optional AdminLTE extended colors --}}
            @isset($colorsCss)
                <link rel="stylesheet" href="{{ $colorsCss }}">

                {{-- The palette stylesheet provides no alert and no button
                     families, so they are generated from its own tokens. --}}
                @include('adminlte::partials.common.extended-colors')
            @endisset
    @endswitch

    {{-- Extra Configured Plugins Stylesheets --}}
    @include('adminlte::plugins', ['type' => 'css'])

    {{-- Livewire Styles --}}
    @if(config('adminlte.livewire'))
        @livewireStyles
    @endif

    {{-- Custom CSS variables --}}
    @include('adminlte::partials.common.css-variables')

    {{-- Custom Stylesheets (post AdminLTE) --}}
    @yield('adminlte_css')

    {{-- Favicon --}}
    @if(config('adminlte.use_ico_only'))
        <link rel="shortcut icon" href="{{ asset('favicons/favicon.ico') }}" />
    @elseif(config('adminlte.use_full_favicon'))
        <link rel="shortcut icon" href="{{ asset('favicons/favicon.ico') }}" />
        <link rel="apple-touch-icon" sizes="57x57" href="{{ asset('favicons/apple-icon-57x57.png') }}">
        <link rel="apple-touch-icon" sizes="60x60" href="{{ asset('favicons/apple-icon-60x60.png') }}">
        <link rel="apple-touch-icon" sizes="72x72" href="{{ asset('favicons/apple-icon-72x72.png') }}">
        <link rel="apple-touch-icon" sizes="76x76" href="{{ asset('favicons/apple-icon-76x76.png') }}">
        <link rel="apple-touch-icon" sizes="114x114" href="{{ asset('favicons/apple-icon-114x114.png') }}">
        <link rel="apple-touch-icon" sizes="120x120" href="{{ asset('favicons/apple-icon-120x120.png') }}">
        <link rel="apple-touch-icon" sizes="144x144" href="{{ asset('favicons/apple-icon-144x144.png') }}">
        <link rel="apple-touch-icon" sizes="152x152" href="{{ asset('favicons/apple-icon-152x152.png') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicons/apple-icon-180x180.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicons/favicon-16x16.png') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicons/favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('favicons/favicon-96x96.png') }}">
        <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('favicons/android-icon-192x192.png') }}">
        <link rel="manifest" crossorigin="use-credentials" href="{{ asset('favicons/manifest.json') }}">
        <meta name="msapplication-TileColor" content="#ffffff">
        <meta name="msapplication-TileImage" content="{{ asset('favicons/ms-icon-144x144.png') }}">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="{{ asset('css/mobile-responsive.css') }}">

    <style>
        /* ========================================================
           MKB GRIK — Clean SaaS Admin Theme
        ======================================================== */

        :root {
            --mkb-bg:          #F6F8FC;
            --mkb-white:       #FFFFFF;
            --mkb-sidebar-w:   280px;
            --lte-sidebar-width: 280px;
            --mkb-border:      #E4E9F0;
            --mkb-text:        #1E293B;
            --mkb-text-muted:  #64748B;
            --mkb-text-light:  #94A3B8;
            --mkb-accent-blue: #3B82F6;
            --mkb-accent-green:#22C55E;
            --mkb-accent-teal: #0EA5E9;
            --mkb-accent-amber:#F59E0B;
            --mkb-accent-rose: #F43F5E;
            --mkb-shadow-sm:   0 1px 3px rgba(15,23,42,.06), 0 1px 2px rgba(15,23,42,.04);
            --mkb-shadow:      0 4px 16px rgba(15,23,42,.07);
            --mkb-radius:      12px;
            --mkb-radius-sm:   8px;
            font-family: 'Inter', 'Poppins', system-ui, -apple-system, sans-serif;
        }

        /* ── Body ─────────────────────────────────────── */
        body.mkb-body {
            background: var(--mkb-bg) !important;
            font-family: 'Inter', 'Poppins', system-ui, -apple-system, sans-serif !important;
            color: var(--mkb-text) !important;
        }

        /* ── Sidebar ──────────────────────────────────── */
        .mkb-sidebar,
        .app-sidebar[data-bs-theme="light"],
        .main-sidebar {
            background: var(--mkb-white) !important;
            border-right: 1px solid var(--mkb-border) !important;
            box-shadow: var(--mkb-shadow-sm) !important;
            width: var(--mkb-sidebar-w) !important;
            transition: width .25s ease, min-width .25s ease, max-width .25s ease, box-shadow .25s ease !important;
        }

        /* Brand / Logo area */
        .sidebar-brand {
            background: var(--mkb-white) !important;
            border-bottom: 1px solid var(--mkb-border) !important;
            padding: 0 !important;
        }
        .sidebar-brand .brand-link {
            padding: 14px 20px !important;
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
            color: var(--mkb-text) !important;
            text-decoration: none !important;
            min-height: 64px;
        }
        .sidebar-brand .brand-link:hover {
            background: transparent !important;
        }
        .sidebar-brand .brand-text {
            font-weight: 700 !important;
            font-size: 1.05rem !important;
            color: var(--mkb-text) !important;
            letter-spacing: -0.01em;
        }
        .sidebar-brand .brand-image {
            border-radius: 8px !important;
            box-shadow: none !important;
            opacity: 1 !important;
        }

        /* Nav headers (section labels) */
        .nav-header {
            font-size: 0.65rem !important;
            font-weight: 700 !important;
            letter-spacing: 0.08em !important;
            text-transform: uppercase !important;
            color: var(--mkb-text-light) !important;
            padding: 18px 20px 6px !important;
        }

        /* Nav links */
        .sidebar-wrapper .nav-link,
        .app-sidebar .nav-link {
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
            margin: 2px 10px !important;
            padding: 9px 12px !important;
            border-radius: var(--mkb-radius-sm) !important;
            font-size: 0.875rem !important;
            font-weight: 500 !important;
            transition: all .18s ease !important;
            white-space: normal !important;
            /* do NOT set color here — let icon keep its own Bootstrap text-* colour */
        }
        /* Label text only — adminlte renders text inside <p> */
        .sidebar-wrapper .nav-link p,
        .app-sidebar .nav-link p {
            color: var(--mkb-text-muted) !important;
            margin: 0 !important;
            line-height: 1.25 !important;
            flex: 1 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            word-break: normal !important;
        }
        .sidebar-wrapper .nav-link .nav-icon,
        .app-sidebar .nav-link .nav-icon {
            width: 20px !important;
            font-size: 1rem !important;
            flex-shrink: 0 !important;
            text-align: center !important;
            /* Let each icon keep its own Bootstrap text-* colour */
        }
        .sidebar-wrapper .nav-link:hover,
        .app-sidebar .nav-link:hover {
            background: #F1F5FF !important;
        }
        .sidebar-wrapper .nav-link:hover p,
        .app-sidebar .nav-link:hover p {
            color: var(--mkb-accent-blue) !important;
        }
        .sidebar-wrapper .nav-link:hover .nav-icon,
        .app-sidebar .nav-link:hover .nav-icon {
            opacity: 0.85;
        }
        .sidebar-wrapper .nav-link.active,
        .app-sidebar .nav-link.active {
            background: #EFF6FF !important;
            font-weight: 600 !important;
        }
        .sidebar-wrapper .nav-link.active p,
        .app-sidebar .nav-link.active p {
            color: var(--mkb-accent-blue) !important;
            font-weight: 600 !important;
        }
        .sidebar-wrapper .nav-link.active .nav-icon,
        .app-sidebar .nav-link.active .nav-icon {
            opacity: 1;
        }
        /* Active indicator bar */
        .sidebar-wrapper .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 60%;
            background: var(--mkb-accent-blue);
            border-radius: 0 4px 4px 0;
        }
        .sidebar-wrapper .nav-item { position: relative; }

        /* Badge / label */
        .sidebar-wrapper .nav-link .right {
            margin-left: auto !important;
            font-size: 0.65rem !important;
            font-weight: 700 !important;
            padding: 2px 7px !important;
            border-radius: 20px !important;
        }

        /* ── Sidebar Mini / Collapsed States ──────────────── */
        /* Collapsed (Not Hovered) - Compact Icon Bar */
        .sidebar-mini.sidebar-collapse .app-sidebar:not(:hover),
        .sidebar-mini.sidebar-collapse.sidebar-without-hover .app-sidebar {
            width: 4.6rem !important;
            min-width: 4.6rem !important;
            max-width: 4.6rem !important;
        }

        .sidebar-mini.sidebar-collapse .app-sidebar:not(:hover) .sidebar-brand .brand-link,
        .sidebar-mini.sidebar-collapse.sidebar-without-hover .sidebar-brand .brand-link {
            justify-content: center !important;
            padding: 14px 0 !important;
        }

        .sidebar-mini.sidebar-collapse .app-sidebar:not(:hover) .nav-link,
        .sidebar-mini.sidebar-collapse.sidebar-without-hover .nav-link {
            justify-content: center !important;
            padding: 9px 0 !important;
            margin: 2px 6px !important;
        }

        .sidebar-mini.sidebar-collapse .app-sidebar:not(:hover) .nav-link .nav-icon,
        .sidebar-mini.sidebar-collapse.sidebar-without-hover .nav-link .nav-icon {
            margin: 0 !important;
        }

        .sidebar-mini.sidebar-collapse .app-sidebar:not(:hover) .nav-link p,
        .sidebar-mini.sidebar-collapse.sidebar-without-hover .nav-link p,
        .sidebar-mini.sidebar-collapse .app-sidebar:not(:hover) .nav-header,
        .sidebar-mini.sidebar-collapse.sidebar-without-hover .nav-header,
        .sidebar-mini.sidebar-collapse .app-sidebar:not(:hover) .brand-text,
        .sidebar-mini.sidebar-collapse.sidebar-without-hover .brand-text,
        .sidebar-mini.sidebar-collapse .app-sidebar:not(:hover) .nav-badge,
        .sidebar-mini.sidebar-collapse.sidebar-without-hover .nav-badge,
        .sidebar-mini.sidebar-collapse .app-sidebar:not(:hover) .nav-arrow,
        .sidebar-mini.sidebar-collapse.sidebar-without-hover .nav-arrow,
        .sidebar-mini.sidebar-collapse .app-sidebar:not(:hover) .nav-treeview,
        .sidebar-mini.sidebar-collapse.sidebar-without-hover .nav-treeview {
            display: none !important;
            opacity: 0 !important;
            visibility: hidden !important;
        }

        /* Hover Expansion on Collapsed/Mini Sidebar - Smooth Floating Overlay with Readable Menu */
        .sidebar-mini.sidebar-collapse:not(.sidebar-without-hover) .app-sidebar:hover {
            width: var(--mkb-sidebar-w) !important;
            min-width: var(--mkb-sidebar-w) !important;
            max-width: var(--mkb-sidebar-w) !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
            z-index: 1045 !important;
        }

        .sidebar-mini.sidebar-collapse:not(.sidebar-without-hover) .app-sidebar:hover .sidebar-brand .brand-link {
            justify-content: flex-start !important;
            padding: 14px 20px !important;
        }

        .sidebar-mini.sidebar-collapse:not(.sidebar-without-hover) .app-sidebar:hover .nav-link {
            justify-content: flex-start !important;
            padding: 9px 12px !important;
            margin: 2px 10px !important;
            width: auto !important;
        }

        .sidebar-mini.sidebar-collapse:not(.sidebar-without-hover) .app-sidebar:hover .nav-link p {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            width: auto !important;
            white-space: normal !important;
            word-break: normal !important;
            line-height: 1.25 !important;
        }

        .sidebar-mini.sidebar-collapse:not(.sidebar-without-hover) .app-sidebar:hover .nav-header {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            white-space: nowrap !important;
        }

        .sidebar-mini.sidebar-collapse:not(.sidebar-without-hover) .app-sidebar:hover .brand-text {
            display: inline-block !important;
            visibility: visible !important;
            opacity: 1 !important;
            max-width: none !important;
            margin-left: 0.5rem !important;
        }

        .sidebar-mini.sidebar-collapse:not(.sidebar-without-hover) .app-sidebar:hover .nav-arrow,
        .sidebar-mini.sidebar-collapse:not(.sidebar-without-hover) .app-sidebar:hover .nav-badge {
            display: inline-block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        .sidebar-mini.sidebar-collapse:not(.sidebar-without-hover) .app-sidebar:hover .nav-item.menu-open > .nav-treeview {
            display: block !important;
        }

        .nav-treeview .nav-link {
            padding-left: 36px !important;
            font-size: 0.825rem !important;
        }

        /* ── Topnav ───────────────────────────────────── */
        .mkb-topnav,
        .app-header {
            background: var(--mkb-white) !important;
            border-bottom: 1px solid var(--mkb-border) !important;
            box-shadow: var(--mkb-shadow-sm) !important;
            min-height: 64px !important;
        }
        .mkb-topnav .navbar-nav .nav-link,
        .app-header .navbar-nav .nav-link {
            color: var(--mkb-text-muted) !important;
            font-size: 0.875rem !important;
        }
        .mkb-topnav .navbar-nav .nav-link:hover,
        .app-header .navbar-nav .nav-link:hover {
            color: var(--mkb-accent-blue) !important;
        }

        /* ── Content wrapper & footer ─────────────────── */
        .content-wrapper {
            background: var(--mkb-bg) !important;
            min-height: calc(100vh - 64px - 48px) !important;
        }
        .mkb-footer,
        .main-footer {
            background: var(--mkb-white) !important;
            border-top: 1px solid var(--mkb-border) !important;
            color: var(--mkb-text-muted) !important;
            font-size: 0.8rem !important;
            padding: 12px 24px !important;
        }

        /* ── Content header ───────────────────────────── */
        .content-header {
            padding: 4px 10px !important;
        }
        .content-header h1 {
            font-size: 1.05rem !important;
            font-weight: 700 !important;
            color: var(--mkb-text) !important;
            letter-spacing: -0.015em !important;
            margin: 0 !important;
            padding: 0 !important;
            line-height: 1.15 !important;
        }
        .content-header .breadcrumb {
            background: transparent !important;
            margin: 0 !important;
            padding: 0 !important;
            font-size: 12px !important;
            line-height: 1.2 !important;
        }

        /* ── Cards ────────────────────────────────────── */
        .card {
            border: 1px solid var(--mkb-border) !important;
            border-radius: var(--mkb-radius) !important;
            box-shadow: var(--mkb-shadow-sm) !important;
            background: var(--mkb-white) !important;
        }
        .card-header {
            background: var(--mkb-white) !important;
            border-bottom: 1px solid var(--mkb-border) !important;
            padding: 16px 20px !important;
            border-radius: var(--mkb-radius) var(--mkb-radius) 0 0 !important;
        }
        .card-title {
            font-weight: 600 !important;
            font-size: 0.95rem !important;
            color: var(--mkb-text) !important;
        }
        .card-body {
            padding: 20px !important;
        }

        /* ── MKB Dashboard stat cards ─────────────────── */
        .mkb-stat-card {
            background: var(--mkb-white);
            border: 1px solid var(--mkb-border);
            border-radius: var(--mkb-radius);
            box-shadow: var(--mkb-shadow-sm);
            padding: 22px 24px;
            display: flex;
            align-items: center;
            gap: 18px;
            transition: box-shadow .2s, transform .2s;
        }
        .mkb-stat-card:hover {
            box-shadow: var(--mkb-shadow) !important;
            transform: translateY(-2px);
        }
        .mkb-stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            flex-shrink: 0;
        }
        .mkb-stat-icon.blue  { background: #EFF6FF; color: var(--mkb-accent-blue); }
        .mkb-stat-icon.green { background: #F0FDF4; color: var(--mkb-accent-green); }
        .mkb-stat-icon.teal  { background: #F0F9FF; color: var(--mkb-accent-teal); }
        .mkb-stat-icon.amber { background: #FFFBEB; color: var(--mkb-accent-amber); }
        .mkb-stat-icon.rose  { background: #FFF1F2; color: var(--mkb-accent-rose); }
        .mkb-stat-icon.muted { background: #F8FAFC; color: var(--mkb-text-light); }

        .mkb-stat-label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--mkb-text-muted);
            margin-bottom: 2px;
        }
        .mkb-stat-value {
            font-size: 1.55rem;
            font-weight: 700;
            color: var(--mkb-text);
            line-height: 1.2;
            letter-spacing: -0.03em;
        }
        .mkb-stat-sub {
            font-size: 0.75rem;
            color: var(--mkb-text-light);
            margin-top: 2px;
        }
        .mkb-stat-badge {
            font-size: 0.7rem;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 20px;
            margin-left: auto;
            align-self: flex-start;
        }
        .mkb-stat-badge.soon {
            background: #F1F5F9;
            color: var(--mkb-text-light);
        }
        .mkb-stat-badge.active {
            background: #F0FDF4;
            color: var(--mkb-accent-green);
        }

        /* ── Program module card ──────────────────────── */
        .mkb-module-card {
            background: var(--mkb-white);
            border: 1px solid var(--mkb-border);
            border-radius: var(--mkb-radius);
            box-shadow: var(--mkb-shadow-sm);
            padding: 24px;
            height: 100%;
            transition: box-shadow .2s, transform .2s;
        }
        .mkb-module-card:hover { box-shadow: var(--mkb-shadow) !important; transform: translateY(-2px); }
        .mkb-module-card.disabled { opacity: .72; pointer-events: none; }

        .mkb-module-icon {
            width: 44px; height: 44px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
            margin-bottom: 14px;
        }
        .mkb-module-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--mkb-text);
            margin-bottom: 4px;
        }
        .mkb-module-desc {
            font-size: 0.8rem;
            color: var(--mkb-text-muted);
            line-height: 1.5;
        }
        .mkb-module-actions { margin-top: 18px; display: flex; gap: 8px; flex-wrap: wrap; }

        .mkb-btn {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 0.8rem; font-weight: 600;
            padding: 7px 14px; border-radius: 8px;
            text-decoration: none; border: none; cursor: pointer;
            transition: all .15s;
        }
        .mkb-btn-primary { background: var(--mkb-accent-blue); color: #fff; }
        .mkb-btn-primary:hover { background: #2563EB; color: #fff; }
        .mkb-btn-outline { background: transparent; color: var(--mkb-text-muted); border: 1px solid var(--mkb-border); }
        .mkb-btn-outline:hover { border-color: var(--mkb-accent-blue); color: var(--mkb-accent-blue); background: #F0F7FF; }
        .mkb-btn-muted { background: #F1F5F9; color: var(--mkb-text-light); cursor: not-allowed; }

        /* ── Quick links ──────────────────────────────── */
        .mkb-quick-link {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px;
            border-radius: var(--mkb-radius-sm);
            text-decoration: none;
            color: var(--mkb-text);
            font-size: 0.86rem; font-weight: 500;
            transition: background .15s;
            border: 1px solid transparent;
        }
        .mkb-quick-link:hover {
            background: #F1F5FF;
            border-color: #DBEAFE;
            color: var(--mkb-accent-blue);
        }
        .mkb-quick-link .ql-icon {
            width: 34px; height: 34px;
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.85rem;
        }

        /* ── Info / welcome panel ─────────────────────── */
        .mkb-welcome {
            background: linear-gradient(135deg, #EFF6FF 0%, #F0FDF4 100%);
            border: 1px solid #DBEAFE;
            border-radius: var(--mkb-radius);
            padding: 24px 28px;
        }
        .mkb-welcome h5 {
            font-size: 1.1rem; font-weight: 700; color: var(--mkb-text);
            margin-bottom: 6px;
        }
        .mkb-welcome p {
            font-size: 0.86rem; color: var(--mkb-text-muted);
            margin: 0; line-height: 1.6;
        }

        /* ── Table inside card ────────────────────────── */
        .mkb-table th {
            font-size: 11px !important;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--mkb-text-light);
            border-bottom: 1px solid var(--mkb-border) !important;
            padding: 6px 10px !important;
            background: #FAFBFD !important;
        }
        .mkb-table td {
            font-size: 12px !important;
            color: var(--mkb-text);
            border-bottom: 1px solid var(--mkb-border) !important;
            padding: 5px 8px !important;
            vertical-align: middle !important;
        }
        .mkb-table tr:last-child td { border-bottom: none !important; }
        .mkb-table tr:hover td { background: #FAFBFF; }

        /* ── Scrollbar ────────────────────────────────── */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94A3B8; }

        table.dataTable thead th,
        table.dataTable thead td,
        .table thead th {
            font-size: 11px !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.04em !important;
            color: var(--mkb-text-light) !important;
            background: #FAFBFD !important;
            vertical-align: middle !important;
            padding: 6px 8px !important;
            border-bottom: 1px solid var(--mkb-border) !important;
            white-space: nowrap !important;
        }
        table.dataTable thead th.dt-orderable-asc,
        table.dataTable thead th.dt-orderable-desc,
        table.dataTable thead th.sorting,
        table.dataTable thead th.sorting_asc,
        table.dataTable thead th.sorting_desc {
            cursor: pointer !important;
        }
        table.dataTable tbody td,
        .table tbody td {
            font-size: 0.84rem !important;
            color: var(--mkb-text) !important;
            padding: 6px 10px !important;
            vertical-align: middle !important;
            border-color: var(--mkb-border) !important;
        }
        table.dataTable tbody tr:hover > td {
            background: #F1F5FF !important;
        }
        /* DataTables footer/tfoot */
        table.dataTable tfoot th,
        .table tfoot th {
            font-size: 0.82rem !important;
            font-weight: 700 !important;
            padding: 7px 10px !important;
            background: #FAFBFD !important;
            border-top: 2px solid var(--mkb-border) !important;
            vertical-align: middle !important;
        }
        /* ── Compact & Modern Table Header Controls (DataTables 1, 2 & Bootstrap) ── */
        .dataTables_wrapper .dataTables_length label,
        .dataTables_wrapper .dataTables_filter label,
        .dt-container .dt-length label,
        .dt-container .dt-search label,
        div.dataTables_wrapper div.dataTables_length label,
        div.dataTables_wrapper div.dataTables_filter label,
        div.dt-container div.dt-length label,
        div.dt-container div.dt-search label {
            font-size: 0.74rem !important;
            color: var(--mkb-text-muted, #64748b) !important;
            font-weight: 500 !important;
            margin-bottom: 0 !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 4px !important;
        }

        .dataTables_wrapper .dataTables_filter input,
        .dt-container .dt-search input,
        div.dataTables_wrapper div.dataTables_filter input,
        div.dt-container div.dt-search input,
        .dataTables_wrapper .dataTables_filter input.form-control,
        div.dt-container div.dt-search input.form-control {
            font-size: 0.74rem !important;
            background-color: #f8fafc !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 6px !important;
            padding: 2px 8px !important;
            color: #1e293b !important;
            height: 26px !important;
            line-height: 1.2 !important;
            box-shadow: none !important;
            transition: all 0.15s ease-in-out !important;
        }
        .dataTables_wrapper .dataTables_filter input:focus,
        .dt-container .dt-search input:focus,
        div.dataTables_wrapper div.dataTables_filter input:focus,
        div.dt-container div.dt-search input:focus {
            background-color: #ffffff !important;
            border-color: #3b82f6 !important;
            outline: none !important;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15) !important;
        }

        .dataTables_wrapper .dataTables_length select,
        .dt-container .dt-length select,
        div.dataTables_wrapper div.dataTables_length select,
        div.dt-container div.dt-length select,
        .dataTables_wrapper .dataTables_length select.form-select,
        div.dt-container div.dt-length select.form-select {
            font-size: 0.74rem !important;
            background-color: #f8fafc !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 6px !important;
            padding: 2px 6px !important;
            color: #1e293b !important;
            height: 26px !important;
            line-height: 1.2 !important;
        }

        /* ── Compact & Modern Table Footer Controls (Global) ── */
        .dataTables_wrapper .dataTables_info,
        .dt-container .dt-info,
        div.dataTables_wrapper div.dataTables_info,
        div.dt-container div.dt-info {
            font-size: 0.74rem !important;
            color: #64748b !important;
            padding-top: 0.35rem !important;
            padding-bottom: 0.25rem !important;
            margin-bottom: 0 !important;
            font-weight: 500 !important;
            line-height: 1.3 !important;
        }

        .dataTables_wrapper .dataTables_paginate,
        .dt-container .dt-paging,
        div.dataTables_wrapper div.dataTables_paginate,
        div.dt-container div.dt-paging {
            margin-top: 0.25rem !important;
            margin-bottom: 0.25rem !important;
        }

        /* ── Modern Separated Rounded Pagination ── */
        .pagination,
        .dataTables_wrapper .pagination,
        .dt-container .dt-paging .pagination,
        div.dataTables_wrapper div.dataTables_paginate ul.pagination,
        div.dt-container div.dt-paging ul.pagination {
            --bs-pagination-font-size: 0.72rem !important;
            --bs-pagination-padding-x: 6px !important;
            --bs-pagination-padding-y: 2px !important;
            margin-bottom: 0 !important;
            gap: 4px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: flex-end !important;
            flex-wrap: wrap !important;
        }

        .pagination .page-item,
        .dataTables_wrapper .pagination .page-item,
        .dt-container .dt-paging ul.pagination li.page-item,
        div.dataTables_wrapper div.dataTables_paginate ul.pagination li.page-item,
        div.dt-container div.dt-paging ul.pagination li.page-item {
            margin: 0 !important;
        }

        .pagination .page-link,
        .pagination .page-item .page-link,
        .pagination .page-item:not(:first-child) .page-link,
        .pagination .page-item:first-child .page-link,
        .pagination .page-item:last-child .page-link,
        .dataTables_wrapper .pagination .page-link,
        .dataTables_wrapper .dataTables_paginate .paginate_button,
        .dt-container .dt-paging .page-link,
        .dt-container .dt-paging .dt-paging-button,
        div.dataTables_wrapper div.dataTables_paginate ul.pagination li.page-item a.page-link,
        div.dt-container div.dt-paging ul.pagination li.page-item a.page-link,
        div.dt-container div.dt-paging ul.pagination li.page-item button.page-link {
            font-size: 0.72rem !important;
            padding: 0 6px !important;
            min-width: 26px !important;
            height: 26px !important;
            line-height: 24px !important;
            border-radius: 6px !important;
            margin-left: 0 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            color: #475569 !important;
            border: 1px solid #e2e8f0 !important;
            background-color: #ffffff !important;
            font-weight: 500 !important;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05) !important;
            transition: all 0.15s ease-in-out !important;
            text-decoration: none !important;
        }

        .pagination .page-link i,
        .pagination .page-link svg,
        .dataTables_wrapper .pagination .page-link i,
        .dt-container .dt-paging .page-link i {
            font-size: 0.65rem !important;
        }

        .pagination .page-link:hover:not(.disabled),
        .pagination .page-item:not(.active):not(.disabled) .page-link:hover,
        .dataTables_wrapper .pagination .page-link:hover:not(.disabled),
        .dt-container .dt-paging .page-link:hover:not(.disabled),
        div.dataTables_wrapper div.dataTables_paginate ul.pagination li.page-item:not(.active):not(.disabled) a.page-link:hover,
        div.dt-container div.dt-paging ul.pagination li.page-item:not(.active):not(.disabled) button.page-link:hover {
            background-color: #f1f5f9 !important;
            border-color: #cbd5e1 !important;
            color: #0f172a !important;
        }

        .pagination .page-item.active .page-link,
        .pagination .page-link.active,
        .dataTables_wrapper .pagination .page-item.active .page-link,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dt-container .dt-paging .page-item.active .page-link,
        .dt-container .dt-paging .dt-paging-button.current,
        div.dataTables_wrapper div.dataTables_paginate ul.pagination li.page-item.active a.page-link,
        div.dt-container div.dt-paging ul.pagination li.page-item.active button.page-link {
            background-color: #3b82f6 !important;
            border-color: #3b82f6 !important;
            color: #ffffff !important;
            font-weight: 600 !important;
            box-shadow: 0 2px 5px rgba(59, 130, 246, 0.3) !important;
        }

        .pagination .page-item.disabled .page-link,
        .pagination .page-link.disabled,
        .dataTables_wrapper .pagination .page-item.disabled .page-link,
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
        .dt-container .dt-paging .page-item.disabled .page-link,
        .dt-container .dt-paging .dt-paging-button.disabled,
        div.dataTables_wrapper div.dataTables_paginate ul.pagination li.page-item.disabled a.page-link,
        div.dt-container div.dt-paging ul.pagination li.page-item.disabled button.page-link {
            color: #94a3b8 !important;
            background-color: #f8fafc !important;
            border-color: #e2e8f0 !important;
            cursor: not-allowed !important;
            opacity: 0.55 !important;
            box-shadow: none !important;
        }

        /* ── Compact Table Action Buttons & Icons ──────── */
        .table td .btn,
        table.dataTable td .btn {
            padding: 2px 6px !important;
            font-size: 0.72rem !important;
            line-height: 1.2 !important;
            border-radius: 4px !important;
            margin: 1px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 3px !important;
        }
        .table td .btn i,
        table.dataTable td .btn i {
            font-size: 0.75rem !important;
            margin: 0 !important;
        }
        .table td .btn-group,
        table.dataTable td .btn-group {
            gap: 2px;
        }
        .table td:has(.btn),
        table.dataTable td:has(.btn) {
            white-space: nowrap !important;
        }

        /* ── Bootstrap 4 to Bootstrap 5 Spacing Compatibility ── */
        .mr-0 { margin-right: 0rem !important; }
        .mr-1 { margin-right: 0.25rem !important; }
        .mr-2 { margin-right: 0.5rem !important; }
        .mr-3 { margin-right: 1rem !important; }
        .mr-4 { margin-right: 1.5rem !important; }
        .mr-5 { margin-right: 3rem !important; }
        .mr-auto { margin-right: auto !important; }

        .ml-0 { margin-left: 0rem !important; }
        .ml-1 { margin-left: 0.25rem !important; }
        .ml-2 { margin-left: 0.5rem !important; }
        .ml-3 { margin-left: 1rem !important; }
        .ml-4 { margin-left: 1.5rem !important; }
        .ml-5 { margin-left: 3rem !important; }
        .ml-auto { margin-left: auto !important; }

        .pr-0 { padding-right: 0rem !important; }
        .pr-1 { padding-right: 0.25rem !important; }
        .pr-2 { padding-right: 0.5rem !important; }
        .pr-3 { padding-right: 1rem !important; }
        .pr-4 { padding-right: 1.5rem !important; }
        .pr-5 { padding-right: 3rem !important; }

        .pl-0 { padding-left: 0rem !important; }
        .pl-1 { padding-left: 0.25rem !important; }
        .pl-2 { padding-left: 0.5rem !important; }
        .pl-3 { padding-left: 1rem !important; }
        .pl-4 { padding-left: 1.5rem !important; }
        .pl-5 { padding-left: 3rem !important; }

        .border-left { border-left: 1px solid #dee2e6 !important; }
        .border-right { border-right: 1px solid #dee2e6 !important; }

        /* Global Header Actions Spacing & Wrap */
        .header-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        /* ── Responsive Enhancements ── */
        .table-responsive {
            -webkit-overflow-scrolling: touch;
        }

        @media (max-width: 767.98px) {
            .content-header .d-flex.justify-content-between,
            .content-header .row {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 0.5rem;
            }
            .header-actions {
                width: 100%;
                justify-content: flex-start;
                margin-top: 0.5rem !important;
            }
            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter {
                float: none !important;
                text-align: left !important;
                margin-bottom: 0.5rem;
            }
            .dataTables_wrapper .dataTables_filter input {
                width: 100% !important;
                max-width: 100% !important;
                margin-left: 0 !important;
                display: block;
                margin-top: 0.25rem;
            }
            .dataTables_wrapper .dataTables_paginate {
                float: none !important;
                text-align: center !important;
                margin-top: 0.5rem;
                display: flex;
                justify-content: center;
                flex-wrap: wrap;
            }
            .dataTables_wrapper .dataTables_info {
                text-align: center !important;
                float: none !important;
            }
            .modal-dialog {
                margin: 0.5rem !important;
                max-width: calc(100% - 1rem) !important;
            }
        }

        /* ── Universal High-Contrast Badges (Zero White Text) ── */
        .badge {
            font-weight: 600 !important;
            letter-spacing: 0.2px;
            padding: 3px 7px !important;
            border-radius: 4px !important;
        }
        .badge.badge-primary, .badge.bg-primary, span.badge-primary {
            background-color: #dbeafe !important;
            color: #1e40af !important;
            border: 1px solid #bfdbfe !important;
        }
        .badge.badge-success, .badge.bg-success, span.badge-success {
            background-color: #dcfce7 !important;
            color: #166534 !important;
            border: 1px solid #bbf7d0 !important;
        }
        .badge.badge-info, .badge.bg-info, span.badge-info {
            background-color: #e0f2fe !important;
            color: #0369a1 !important;
            border: 1px solid #bae6fd !important;
        }
        .badge.badge-warning, .badge.bg-warning, span.badge-warning {
            background-color: #fef3c7 !important;
            color: #92400e !important;
            border: 1px solid #fde68a !important;
        }
        .badge.badge-danger, .badge.bg-danger, span.badge-danger {
            background-color: #fee2e2 !important;
            color: #991b1b !important;
            border: 1px solid #fecaca !important;
        }
        .badge.badge-secondary, .badge.bg-secondary, span.badge-secondary {
            background-color: #f1f5f9 !important;
            color: #334155 !important;
            border: 1px solid #cbd5e1 !important;
        }
        .badge.badge-dark, .badge.bg-dark, span.badge-dark {
            background-color: #e2e8f0 !important;
            color: #0f172a !important;
            border: 1px solid #94a3b8 !important;
        }
        .badge.badge-light, .badge.bg-light, span.badge-light {
            background-color: #f8fafc !important;
            color: #1e293b !important;
            border: 1px solid #e2e8f0 !important;
        }
        .badge.badge-pink, span.badge-pink {
            background-color: #fce7f3 !important;
            color: #9d174d !important;
            border: 1px solid #fbcfe8 !important;
        }

        /* ══════════════════════════════════════════════════════════════════
           DESAIN GLOBAL: JUDUL, BREADCRUMB & STANDARISASI TABEL (MAKS 12PX)
           ══════════════════════════════════════════════════════════════════ */

        /* Content Header (Judul & Breadcrumb): Ramping, 12px, tanpa icon, spasi sempit */
        .content-header {
            padding: 4px 10px !important;
            margin-bottom: 0 !important;
        }
        .content-header h1,
        h1.page-title,
        .page-title {
            font-size: 1.05rem !important;
            line-height: 1.15 !important;
            margin-bottom: 0px !important;
            padding-bottom: 0px !important;
        }
        .content-header nav,
        .content-header nav[aria-label="breadcrumb"] {
            margin-top: 0px !important;
            padding-top: 0px !important;
            line-height: 1.15 !important;
        }
        .content-header .breadcrumb,
        .breadcrumb {
            font-size: 12px !important;
            line-height: 1.2 !important;
            margin-top: 0px !important;
            margin-bottom: 0px !important;
            padding-top: 0px !important;
            padding-bottom: 0px !important;
        }
        .content-header .breadcrumb .breadcrumb-item,
        .breadcrumb .breadcrumb-item,
        .content-header .breadcrumb .breadcrumb-item a,
        .breadcrumb .breadcrumb-item a {
            font-size: 12px !important;
            line-height: 1.2 !important;
        }
        /* Tanpa icon pada breadcrumb di bawah judul */
        .content-header .breadcrumb i,
        .content-header .breadcrumb svg,
        .breadcrumb i,
        .breadcrumb svg {
            display: none !important;
        }

        /* ══════════════════════════════════════════════════════════════════
           HIERARKI TIPOGRAFI TABEL (MAKSIMAL 12PX STANDARISASI GLOBAL)
           ══════════════════════════════════════════════════════════════════ */

        /* 1. Teks Data Utama Sel Tabel: Tepat & Maksimal 12px */
        .table,
        table.dataTable,
        .mkb-table,
        .table tbody td,
        table.dataTable tbody td,
        .table td,
        table.dataTable td,
        .mkb-table td {
            font-size: 12px !important;
            line-height: 1.25 !important;
        }

        /* Seluruh teks direct dan nested dalam tabel tidak boleh lebih dari 12px */
        .table td a,
        table.dataTable td a,
        .table td span:not(.badge):not(.subtext),
        table.dataTable td span:not(.badge):not(.subtext),
        .table td div:not(.small):not(.text-muted),
        table.dataTable td div:not(.small):not(.text-muted),
        .table td strong,
        table.dataTable td strong,
        .table td b,
        table.dataTable td b,
        .table td p,
        table.dataTable td p {
            font-size: 12px !important;
            line-height: 1.25 !important;
        }

        /* 2. Header Kolom Tabel (th): 11px Uppercase Rapi & Tegas */
        .table th,
        table.dataTable th,
        .mkb-table th,
        .table thead th,
        table.dataTable thead th {
            font-size: 11px !important;
            font-weight: 700 !important;
            letter-spacing: 0.25px !important;
            text-transform: uppercase !important;
            line-height: 1.25 !important;
        }

        /* 3. Teks Sekunder di dalam Tabel (Keterangan, NIP, Subtext, Detail): 10.5px */
        .table td small,
        table.dataTable td small,
        .table td .small,
        table.dataTable td .small,
        .table td .text-muted,
        table.dataTable td .text-muted,
        .table td .subtext,
        table.dataTable td .subtext,
        .table td .desc,
        table.dataTable td .desc {
            font-size: 10.5px !important;
            line-height: 1.2 !important;
        }

        /* 4. Kode Akun / No BKU / No SPPD / Nomor Surat: 11px */
        .table td code,
        table.dataTable td code,
        .table td kbd,
        table.dataTable td kbd,
        .table td .font-monospace {
            font-size: 11px !important;
            padding: 1px 4px !important;
        }

        /* 5. Badge Status di dalam Tabel: 10px */
        .table td .badge,
        table.dataTable td .badge,
        .table th .badge,
        table.dataTable th .badge {
            font-size: 10px !important;
            padding: 1.5px 5px !important;
            line-height: 1.15 !important;
            letter-spacing: 0.2px !important;
            font-weight: 600 !important;
        }

        /* 6. Tombol Aksi di dalam Sel Tabel: 10.5px */
        .table td .btn,
        table.dataTable td .btn {
            font-size: 10.5px !important;
            padding: 1px 4px !important;
            line-height: 1.2 !important;
        }

        /* 7. Ikon di dalam Sel & Tombol Tabel: 10.5px - 11px */
        .table td .btn i,
        table.dataTable td .btn i,
        .table td i,
        table.dataTable td i,
        .table td svg,
        table.dataTable td svg {
            font-size: 11px !important;
        }

        /* Ikon emoji / icon kustom di tabel agar tidak melonjak besar */
        .table td .mr-1[style*="font-size"],
        .table td span[style*="font-size"] {
            font-size: 12px !important;
        }

        /* ══════════════════════════════════════════════════════════════════
           MODE RAMPING / KOMPAK (PENGHEMAT RUANG UNTUK LAPTOP & TOGGLE)
           ══════════════════════════════════════════════════════════════════ */

        /* Ketika class .compact-mode aktif (secara manual maupun otomatis pada laptop) */
        html.compact-mode .app-header.navbar,
        body.compact-mode .app-header.navbar {
            min-height: 40px !important;
            padding-top: 2px !important;
            padding-bottom: 2px !important;
        }
        html.compact-mode .app-header.navbar .nav-link,
        body.compact-mode .app-header.navbar .nav-link {
            padding-top: 4px !important;
            padding-bottom: 4px !important;
        }

        html.compact-mode .card,
        body.compact-mode .card {
            margin-bottom: 0.55rem !important;
        }
        html.compact-mode .card-header,
        body.compact-mode .card-header {
            padding: 0.35rem 0.60rem !important;
            min-height: 35px !important;
        }
        html.compact-mode .card-header .card-title,
        body.compact-mode .card-header .card-title,
        html.compact-mode .card-header h3.card-title,
        body.compact-mode .card-header h3.card-title,
        html.compact-mode .card-header h6.card-title,
        body.compact-mode .card-header h6.card-title {
            font-size: 0.84rem !important;
            margin-bottom: 0 !important;
        }
        html.compact-mode .card-body,
        body.compact-mode .card-body {
            padding: 0.45rem 0.60rem !important;
        }

        html.compact-mode .table tbody td,
        html.compact-mode table.dataTable tbody td,
        html.compact-mode .table td,
        html.compact-mode table.dataTable td,
        html.compact-mode .mkb-table td,
        body.compact-mode .table tbody td,
        body.compact-mode table.dataTable tbody td,
        body.compact-mode .table td,
        body.compact-mode table.dataTable td,
        body.compact-mode .mkb-table td {
            padding: 0.22rem 0.40rem !important;
        }
        html.compact-mode .table th,
        html.compact-mode table.dataTable th,
        html.compact-mode .mkb-table th,
        html.compact-mode .table thead th,
        html.compact-mode table.dataTable thead th,
        body.compact-mode .table th,
        body.compact-mode table.dataTable th,
        body.compact-mode .mkb-table th,
        body.compact-mode .table thead th,
        body.compact-mode table.dataTable thead th {
            padding: 0.28rem 0.40rem !important;
        }

        html.compact-mode .btn-sm,
        html.compact-mode .btn-group-sm > .btn,
        body.compact-mode .btn-sm,
        body.compact-mode .btn-group-sm > .btn {
            padding: 0.18rem 0.45rem !important;
            font-size: 0.75rem !important;
            line-height: 1.2 !important;
            min-height: 26px !important;
        }
        html.compact-mode .form-control-sm,
        html.compact-mode .form-select-sm,
        body.compact-mode .form-control-sm,
        body.compact-mode .form-select-sm {
            padding: 0.18rem 0.38rem !important;
            font-size: 0.75rem !important;
            height: auto !important;
            min-height: 26px !important;
        }

        html.compact-mode .stat-card,
        body.compact-mode .stat-card {
            padding: 0.45rem 0.65rem !important;
        }
        html.compact-mode .stat-value,
        body.compact-mode .stat-value {
            font-size: 1.15rem !important;
        }
        html.compact-mode .stat-label,
        body.compact-mode .stat-label {
            font-size: 0.68rem !important;
        }

        html.compact-mode .dataTables_wrapper .dataTables_length,
        html.compact-mode .dataTables_wrapper .dataTables_filter,
        html.compact-mode .dt-container .dt-length,
        html.compact-mode .dt-container .dt-search,
        body.compact-mode .dataTables_wrapper .dataTables_length,
        body.compact-mode .dataTables_wrapper .dataTables_filter,
        body.compact-mode .dt-container .dt-length,
        body.compact-mode .dt-container .dt-search {
            margin-bottom: 0.15rem !important;
        }
        html.compact-mode .dataTables_wrapper .dataTables_info,
        html.compact-mode .dataTables_wrapper .dataTables_paginate,
        html.compact-mode .dt-container .dt-info,
        html.compact-mode .dt-container .dt-paging,
        body.compact-mode .dataTables_wrapper .dataTables_info,
        body.compact-mode .dataTables_wrapper .dataTables_paginate,
        body.compact-mode .dt-container .dt-info,
        body.compact-mode .dt-container .dt-paging {
            margin-top: 0.15rem !important;
            padding-top: 0.1rem !important;
        }

        html.compact-mode .badge,
        body.compact-mode .badge {
            font-size: 0.66rem !important;
            padding: 2px 5px !important;
        }

        /* Mode Otomatis Layar Laptop (media query fallback jika belum dimatikan manual) */
        @media screen and (max-width: 1366px), screen and (max-height: 800px) {
            html:not(.compact-mode-off) .app-header.navbar {
                min-height: 40px !important;
                padding-top: 2px !important;
                padding-bottom: 2px !important;
            }
            html:not(.compact-mode-off) .app-header.navbar .nav-link {
                padding-top: 4px !important;
                padding-bottom: 4px !important;
            }
            html:not(.compact-mode-off) .card {
                margin-bottom: 0.55rem !important;
            }
            html:not(.compact-mode-off) .card-header {
                padding: 0.35rem 0.60rem !important;
                min-height: 35px !important;
            }
            html:not(.compact-mode-off) .card-header .card-title,
            html:not(.compact-mode-off) .card-header h3.card-title,
            html:not(.compact-mode-off) .card-header h6.card-title {
                font-size: 0.84rem !important;
                margin-bottom: 0 !important;
            }
            html:not(.compact-mode-off) .card-body {
                padding: 0.45rem 0.60rem !important;
            }
            html:not(.compact-mode-off) .table tbody td,
            html:not(.compact-mode-off) table.dataTable tbody td,
            html:not(.compact-mode-off) .table td,
            html:not(.compact-mode-off) table.dataTable td,
            html:not(.compact-mode-off) .mkb-table td {
                padding: 0.22rem 0.40rem !important;
            }
            html:not(.compact-mode-off) .table th,
            html:not(.compact-mode-off) table.dataTable th,
            html:not(.compact-mode-off) .mkb-table th,
            html:not(.compact-mode-off) .table thead th,
            html:not(.compact-mode-off) table.dataTable thead th {
                padding: 0.28rem 0.40rem !important;
            }
            html:not(.compact-mode-off) .btn-sm,
            html:not(.compact-mode-off) .btn-group-sm > .btn {
                padding: 0.18rem 0.45rem !important;
                font-size: 0.75rem !important;
                min-height: 26px !important;
            }
            html:not(.compact-mode-off) .form-control-sm,
            html:not(.compact-mode-off) .form-select-sm {
                padding: 0.18rem 0.38rem !important;
                font-size: 0.75rem !important;
                min-height: 26px !important;
            }
            html:not(.compact-mode-off) .stat-card {
                padding: 0.45rem 0.65rem !important;
            }
            html:not(.compact-mode-off) .stat-value {
                font-size: 1.15rem !important;
            }
            html:not(.compact-mode-off) .stat-label {
                font-size: 0.68rem !important;
            }
            html:not(.compact-mode-off) .badge {
                font-size: 0.66rem !important;
                padding: 2px 5px !important;
            }
        }
    </style>
</head>

<body class="@yield('classes_body')" @yield('body_data')>

    {{-- Body Content --}}
    @yield('body')

    {{-- Third Party Plugin (OverlayScrollbars) --}}
    @isset($overlayScrollbarsJs)
        <script src="{{ $overlayScrollbarsJs }}"{!! $crossOrigin($overlayScrollbarsJs) !!}></script>
    @endisset


    {{-- jQuery (required for DataTables, AdminLTE jQuery plugin, and view scripts) --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" crossorigin="anonymous"></script>

    {{-- DataTables with Bootstrap 5 theme --}}
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js" crossorigin="anonymous"></script>
    <script>
        if (window.jQuery && $.fn && $.fn.dataTable) {
            $.fn.dataTable.ext.errMode = 'none';

            // Global handler for DataTables errors so table never gets stuck loading forever
            $(document).on('error.dt', function(e, settings, techNote, message) {
                console.error('DataTables Error:', techNote, message);
                if (settings && settings.nTable) {
                    var $table = $(settings.nTable);
                    $table.closest('.dataTables_wrapper').find('.dataTables_processing').hide();
                    var $tbody = $table.find('tbody');
                    if ($tbody.children().length === 0 || $tbody.find('.dataTables_empty').length > 0) {
                        $tbody.html('<tr><td colspan="100" class="text-center py-4 text-danger">' +
                            '<div class="d-inline-flex flex-column align-items-center" style="gap: 6px;">' +
                            '<div><i class="fas fa-triangle-exclamation fa-2x text-warning mb-1"></i></div>' +
                            '<strong style="font-size: 0.88rem;">Gagal memuat data dari server</strong>' +
                            '<small class="text-muted">Koneksi terputus atau server sedang sibuk. Silakan muat ulang halaman.</small>' +
                            '<button type="button" class="btn btn-sm btn-outline-primary mt-1" onclick="location.reload()"><i class="fas fa-rotate-right mr-1"></i> Muat Ulang Halaman</button>' +
                            '</div>' +
                            '</td></tr>');
                    }
                }
            });
        }
    </script>

    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" crossorigin="anonymous"></script>

    {{-- Required Plugin (Bootstrap 5) --}}
    @isset($bootstrapJs)
        <script src="{{ $bootstrapJs }}"{!! $crossOrigin($bootstrapJs) !!}></script>
    @endisset

    {{-- Base Scripts (depends on the Laravel asset bundling tool) --}}
    @switch($bundling)
        @case('mix')
            <script src="{{ mix(config('adminlte.laravel_js_path', 'js/app.js')) }}"></script>
        @break

        @case('vite')
        @case('vite_js_only')
        @break

        @default
            {{-- Required Plugin (AdminLTE) --}}
            @isset($adminlteJs)
                <script src="{{ $adminlteJs }}"></script>
            @endisset
    @endswitch

    {{-- Lifecycle helpers used by the inline scripts of the package --}}
    @include('adminlte::partials.common.lifecycle')

    @if($setupScrollbars)
        {{-- OverlayScrollbars Configuration (main sidebar) --}}
        <script>
            (() => {
                'use strict';
                const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
                const Default = {
                    scrollbarTheme: @json($scrollbarTheme),
                    scrollbarAutoHide: @json($scrollbarAutoHide),
                    scrollbarClickScroll: @json((bool) config('adminlte.sidebar_scrollbar_click_scroll', true)),
                };

                window._AdminLTE_Ready(function () {
                    const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);

                    // Disable OverlayScrollbars on mobile devices to prevent
                    // touch interference.
                    const isMobile = window.innerWidth <= @json($scrollbarDisableBelow);

                    if (
                        sidebarWrapper &&
                        typeof OverlayScrollbarsGlobal !== 'undefined' &&
                        OverlayScrollbarsGlobal?.OverlayScrollbars !== undefined &&
                        !isMobile
                    ) {
                        window._sidebarOsInstance = OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
                            scrollbars: {
                                theme: Default.scrollbarTheme,
                                autoHide: Default.scrollbarAutoHide,
                                clickScroll: Default.scrollbarClickScroll,{!! $scrollbarExtraOptions !!}
                            },
                        });
                    }

                    if (typeof window._restoreMkbSidebarPosition === 'function') {
                        window._restoreMkbSidebarPosition();
                    }
                });
            })();
        </script>
    @endif

    {{-- MKB Sidebar Auto Remember Position & Scroll Persistence --}}
    <script>
        (function() {
            const STORAGE_SCROLL = 'mkb_sidebar_scroll_pos';
            const STORAGE_HREF = 'mkb_sidebar_active_href';

            function getSidebarViewport() {
                const wrapper = document.querySelector('.sidebar-wrapper');
                if (!wrapper) return null;
                return wrapper.querySelector('.os-viewport') || wrapper;
            }

            function saveSidebarPosition() {
                const vp = getSidebarViewport();
                if (vp) {
                    sessionStorage.setItem(STORAGE_SCROLL, vp.scrollTop);
                }
            }

            function restoreSidebarPosition(isInitial = false) {
                const wrapper = document.querySelector('.sidebar-wrapper');
                if (!wrapper) return;
                const vp = getSidebarViewport();
                if (!vp) return;

                // 1. Ensure any parent treeviews of active links are open
                const activeLink = wrapper.querySelector('.nav-link.active');
                if (activeLink) {
                    let parent = activeLink.closest('.nav-treeview');
                    while (parent) {
                        const parentItem = parent.closest('.nav-item');
                        if (parentItem) {
                            parentItem.classList.add('menu-open');
                            const parentLink = parentItem.querySelector(':scope > .nav-link');
                            if (parentLink) parentLink.classList.add('active');
                            parent.style.display = 'block';
                        }
                        parent = parentItem ? parentItem.parentElement.closest('.nav-treeview') : null;
                    }
                }

                // 2. Determine scroll restoration target
                const savedPos = sessionStorage.getItem(STORAGE_SCROLL);
                const clickedHref = sessionStorage.getItem(STORAGE_HREF);
                let restored = false;

                if (savedPos !== null && !isNaN(parseInt(savedPos, 10)) && parseInt(savedPos, 10) > 0) {
                    const targetTop = parseInt(savedPos, 10);
                    let matchesClicked = false;
                    if (clickedHref) {
                        try {
                            const parsedClicked = new URL(clickedHref, window.location.origin).pathname;
                            if (parsedClicked === window.location.pathname) {
                                matchesClicked = true;
                            }
                        } catch (e) {}
                    }

                    if (matchesClicked || !activeLink) {
                        vp.scrollTop = targetTop;
                        restored = true;
                    }
                }

                // If not restored via savedPos or if no savedPos, scroll active link into view
                if (!restored && activeLink) {
                    activeLink.scrollIntoView({ block: 'center', inline: 'nearest', behavior: 'instant' });
                    saveSidebarPosition();
                }

                // 3. Attach scroll listener once viewport exists
                if (vp && !vp._mkbScrollListening) {
                    vp._mkbScrollListening = true;
                    let scrollTimer;
                    vp.addEventListener('scroll', function() {
                        clearTimeout(scrollTimer);
                        scrollTimer = setTimeout(saveSidebarPosition, 100);
                    }, { passive: true });
                }
            }

            window._restoreMkbSidebarPosition = restoreSidebarPosition;

            // Track sidebar link clicks to remember exact clicked position
            document.addEventListener('click', function(e) {
                const navLink = e.target.closest('.sidebar-wrapper .nav-link');
                if (navLink) {
                    const vp = getSidebarViewport();
                    if (vp) {
                        sessionStorage.setItem(STORAGE_SCROLL, vp.scrollTop);
                    }
                    const href = navLink.getAttribute('href');
                    if (href && href !== '#' && href !== 'javascript:void(0)') {
                        sessionStorage.setItem(STORAGE_HREF, href);
                    }
                }
            }, true);

            window.addEventListener('beforeunload', saveSidebarPosition);

            // Execute restoration at key lifecycle moments
            document.addEventListener('DOMContentLoaded', function() {
                restoreSidebarPosition(true);
                setTimeout(restoreSidebarPosition, 60);
                setTimeout(restoreSidebarPosition, 200);
                setTimeout(restoreSidebarPosition, 350);
            });

            window.addEventListener('load', function() {
                setTimeout(restoreSidebarPosition, 100);
            });
        })();
    </script>

    {{-- Extra Configured Plugins Scripts --}}
    @include('adminlte::plugins', ['type' => 'js'])

    {{-- Global AJAX & DataTables Interceptor (Prevents CORS / Host mismatch & infinite loading) --}}
    <script>
        if (typeof jQuery !== 'undefined') {
            // Rewrite absolute URLs to relative when target is an MKB host to prevent CORS & Mixed Content
            jQuery.ajaxPrefilter(function(options, originalOptions, jqXHR) {
                if (options.url && typeof options.url === 'string') {
                    try {
                        if (options.url.indexOf('http://') === 0 || options.url.indexOf('https://') === 0) {
                            let parsed = new URL(options.url);
                            let knownHosts = ['mkb.test', 'mkb.sudahonline.my.id', 'localhost', '127.0.0.1'];
                            if (knownHosts.includes(parsed.hostname.toLowerCase())) {
                                options.url = parsed.pathname + parsed.search + parsed.hash;
                            }
                        }
                    } catch (e) {}
                }
            });

            // Suppress unhandled DataTables error alerts and gracefully show empty/retry state
            if (typeof jQuery.fn.dataTable !== 'undefined') {
                jQuery.fn.dataTable.ext.errMode = 'none';
                jQuery(document).on('error.dt', function(e, settings, techNote, message) {
                    console.warn('DataTables error caught:', message);
                    if (settings && settings.nTable) {
                        let $tbl = jQuery(settings.nTable);
                        let cols = $tbl.find('thead th').length || 6;
                        $tbl.find('tbody').html(
                            '<tr><td colspan="' + cols + '" class="text-center py-4 text-muted">' +
                            '<i class="fas fa-exclamation-triangle text-warning mr-1"></i> Data tidak dapat dimuat atau sedang kosong. ' +
                            '<button type="button" class="btn btn-xs btn-outline-primary ml-2" onclick="location.reload();">Segarkan</button>' +
                            '</td></tr>'
                        );
                        let tblId = $tbl.attr('id');
                        if (tblId) {
                            jQuery('#' + tblId + '_processing').hide();
                        }
                    }
                });
            }
        }
    </script>

    {{-- Livewire Script --}}
    @if(config('adminlte.livewire'))
        @livewireScripts
    @endif

    {{-- PWA Install Banner for Mobile --}}
    <div id="pwaInstallBanner" class="pwa-install-banner shadow-lg">
        <div class="d-flex align-items-center">
            <img src="{{ asset('img/logo.png') }}" alt="MKB App" style="width: 38px; height: 38px; object-fit: contain; margin-right: 12px;">
            <div>
                <strong class="d-block text-dark" style="font-size: 0.875rem;">Pasang Aplikasi MKB</strong>
                <small class="text-muted">Akses cepat & nyaman dari layar utama HP Anda</small>
            </div>
        </div>
        <div class="d-flex align-items-center" style="gap: 6px;">
            <button type="button" class="btn btn-sm btn-primary font-weight-bold" id="btnPwaInstallAction">
                <i class="fas fa-download mr-1"></i> Pasang
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnPwaDismissAction" title="Tutup">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    {{-- Service Worker & PWA Install Logic --}}
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('{{ asset("sw.js") }}')
                    .then(function(reg) {
                        // SW registered successfully
                    })
                    .catch(function(err) {
                        console.warn('MKB PWA SW registration skipped:', err);
                    });
            });
        }

        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            const isDismissed = localStorage.getItem('mkb_pwa_dismissed');
            if (!isDismissed) {
                const banner = document.getElementById('pwaInstallBanner');
                if (banner) banner.classList.add('show');
            }
        });

        document.getElementById('btnPwaInstallAction')?.addEventListener('click', async () => {
            const banner = document.getElementById('pwaInstallBanner');
            if (banner) banner.classList.remove('show');
            if (deferredPrompt) {
                deferredPrompt.prompt();
                await deferredPrompt.userChoice;
                deferredPrompt = null;
            }
        });

        document.getElementById('btnPwaDismissAction')?.addEventListener('click', () => {
            const banner = document.getElementById('pwaInstallBanner');
            if (banner) banner.classList.remove('show');
            localStorage.setItem('mkb_pwa_dismissed', 'true');
        });
    </script>

    {{-- MKB Compact Mode Toggle Script --}}
    <script>
        (function() {
            const STORAGE_KEY = 'mkb_compact_mode';

            function isLaptopScreen() {
                return window.innerWidth <= 1366 || window.innerHeight <= 800;
            }

            function getStoredCompactState() {
                try {
                    return localStorage.getItem(STORAGE_KEY);
                } catch (e) {
                    return null;
                }
            }

            function isCompactActive() {
                const stored = getStoredCompactState();
                if (stored === 'true') return true;
                if (stored === 'false') return false;
                return isLaptopScreen();
            }

            function updateButtonUI(isActive) {
                const btn = document.getElementById('btnToggleCompactMode');
                const statusEl = document.getElementById('compactModeStatus');
                const iconEl = document.getElementById('compactModeIcon');
                if (!btn) return;

                if (isActive) {
                    btn.style.background = '#e0f2fe';
                    btn.style.borderColor = '#7dd3fc';
                    btn.style.color = '#0369a1';
                    if (statusEl) {
                        statusEl.textContent = 'Aktif';
                        statusEl.className = 'text-primary fw-bold';
                    }
                    if (iconEl) {
                        iconEl.className = 'fas fa-compress-arrows-alt me-1 mr-1 text-primary';
                    }
                    btn.title = 'Mode Kompak Aktif (Klik untuk mematikan mode ramping)';
                } else {
                    btn.style.background = '#f8fafc';
                    btn.style.borderColor = '#e2e8f0';
                    btn.style.color = '#475569';
                    if (statusEl) {
                        statusEl.textContent = 'Off';
                        statusEl.className = 'text-secondary';
                    }
                    if (iconEl) {
                        iconEl.className = 'fas fa-expand-arrows-alt me-1 mr-1 text-secondary';
                    }
                    btn.title = 'Mode Kompak Nonaktif (Klik untuk mengaktifkan mode hemat ruang)';
                }
            }

            function applyCompactMode(isActive) {
                const html = document.documentElement;
                const body = document.body;
                if (isActive) {
                    html.classList.add('compact-mode');
                    html.classList.remove('compact-mode-off');
                    if (body) {
                        body.classList.add('compact-mode');
                        body.classList.remove('compact-mode-off');
                    }
                } else {
                    html.classList.remove('compact-mode');
                    html.classList.add('compact-mode-off');
                    if (body) {
                        body.classList.remove('compact-mode');
                        body.classList.add('compact-mode-off');
                    }
                }
                updateButtonUI(isActive);
            }

            function toggleCompactMode() {
                const currentActive = isCompactActive();
                const newActive = !currentActive;
                try {
                    localStorage.setItem(STORAGE_KEY, newActive ? 'true' : 'false');
                } catch (e) {}
                applyCompactMode(newActive);

                // Auto adjust DataTables column header alignment on compact toggle
                setTimeout(function() {
                    window.dispatchEvent(new Event('resize'));
                    if (window.jQuery && $.fn && $.fn.dataTable) {
                        try {
                            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
                        } catch(err) {}
                    }
                }, 100);
            }

            document.addEventListener('DOMContentLoaded', function() {
                const active = isCompactActive();
                applyCompactMode(active);

                const toggleBtn = document.getElementById('btnToggleCompactMode');
                if (toggleBtn) {
                    toggleBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        toggleCompactMode();
                    });
                }
            });

            window.addEventListener('resize', function() {
                const stored = getStoredCompactState();
                if (stored === null) {
                    applyCompactMode(isLaptopScreen());
                }
            });
        })();
    </script>

    {{-- Custom Scripts --}}
    @yield('adminlte_js')

</body>

</html>
