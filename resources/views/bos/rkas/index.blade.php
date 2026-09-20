@extends('adminlte::page')

@section('title', 'Rencana RKAS BOSP')

@section('plugins.Sweetalert2', true)

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap" style="padding: 2px 0;">
    <div>
        <h1 class="page-title">
            <i class="fas fa-calculator text-success mr-1"></i> Rencana RKAS BOSP
            <span class="badge badge-primary px-2 py-1 ml-2 font-tabular" style="font-size: 0.82rem;">
                Tahun {{ $selectedYear }}
            </span>
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-house"></i> Dashboard</a></li>
                <li class="breadcrumb-item">BOSP</li>
                <li class="breadcrumb-item active">Rencana RKAS</li>
            </ol>
        </nav>
    </div>
    <div class="header-actions d-flex align-items-center flex-wrap mt-2 mt-md-0" style="gap: 8px;">
        <a href="{{ route('kegiatan-sekolah.index') }}" class="btn btn-outline-secondary btn-sm shadow-sm font-weight-bold" title="Lihat & Kelola Master Kegiatan Sekolah">
            <i class="fas fa-bookmark mr-1 text-indigo"></i> Master Kegiatan Sekolah
        </a>
        <button type="button" class="btn btn-outline-primary btn-sm shadow-sm font-weight-bold" 
                data-toggle="modal" data-target="#modalPagu" 
                data-bs-toggle="modal" data-bs-target="#modalPagu" 
                onclick="window.showModal('#modalPagu')">
            <i class="fas fa-sliders mr-1 text-primary"></i> Atur Pagu Anggaran
        </button>
        <button type="button" class="btn btn-outline-success btn-sm shadow-sm font-weight-bold" 
                data-toggle="modal" data-target="#modalImportExcel" 
                data-bs-toggle="modal" data-bs-target="#modalImportExcel" 
                onclick="window.showModal('#modalImportExcel')">
            <i class="fas fa-file-excel mr-1 text-success"></i> Import Excel
        </button>
        <button type="button" class="btn btn-success btn-sm shadow-sm font-weight-bold" 
                data-toggle="modal" data-target="#modalAddItem" 
                data-bs-toggle="modal" data-bs-target="#modalAddItem" 
                onclick="window.showModal('#modalAddItem')">
            <i class="fas fa-plus mr-1"></i> Tambah Item RKAS
        </button>
    </div>
</div>
@stop

@section('css')
<style>
    body, .content-wrapper, .card, table, .btn, .form-control {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
    }
    h1.page-title {
        font-size: 1.18rem !important;
        font-weight: 700 !important;
        letter-spacing: -0.015em;
        margin-bottom: 0;
    }
    .content-header .breadcrumb {
        font-size: 0.76rem !important;
        margin-top: 2px;
    }
    .font-tabular {
        font-variant-numeric: tabular-nums;
        font-feature-settings: 'tnum';
    }
    .stat-card {
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        padding: 8px 14px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .stat-card .bar-indicator {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 3px;
    }
    .table-rkas th {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        vertical-align: middle;
        background-color: #f8fafc;
        color: #475569;
        border-bottom: 2px solid #e2e8f0;
    }
    .table-rkas td {
        font-size: 0.85rem;
        vertical-align: top;
    }
    .month-pill {
        border-radius: 20px;
        padding: 4px 12px;
        font-size: 0.78rem;
        font-weight: 600;
        white-space: nowrap;
        text-decoration: none !important;
        transition: all 0.15s ease;
    }
    .month-pill.active {
        background-color: #2563eb;
        color: #ffffff !important;
        box-shadow: 0 2px 4px rgba(37,99,235,0.25);
    }
    .month-pill:not(.active) {
        background-color: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
    }
    .month-pill:not(.active):hover {
        background-color: #e2e8f0;
        color: #1e293b;
    }
    .karakter-card {
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        padding: 12px 14px;
        cursor: pointer;
        transition: all 0.15s ease;
        background-color: #ffffff;
    }
    .karakter-card:hover {
        border-color: #94a3b8;
        background-color: #f8fafc;
    }
    .karakter-card.selected {
        border-color: #10b981;
        background-color: #f0fdf4;
    }
    .month-checkbox-label {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 0.8rem;
        font-weight: 500;
        cursor: pointer;
        background-color: #ffffff;
        transition: all 0.15s ease;
        user-select: none;
        margin-bottom: 0;
    }
    .month-checkbox-label:hover {
        border-color: #a855f7;
        background-color: #faf5ff;
    }
    .month-checkbox-label input[type="checkbox"]:checked + span {
        font-weight: 700;
        color: #6b21a8;
    }
    /* Section separator styling */
    .form-section-separator {
        display: flex;
        align-items: center;
        margin: 18px 0 12px 0;
    }
    .form-section-separator .sep-line {
        flex-grow: 1;
        height: 1px;
        background-color: #e2e8f0;
    }
    .form-section-separator .sep-label {
        padding: 0 10px;
        font-size: 0.76rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
    }
    /* Autocomplete Dropdown Styling */
    .autocomplete-wrapper {
        position: relative;
    }
    .autocomplete-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.15);
        z-index: 1060;
        max-height: 260px;
        overflow-y: auto;
        margin-top: 4px;
        display: none;
    }
    .autocomplete-dropdown.show {
        display: block;
    }
    .autocomplete-item {
        padding: 8px 12px;
        border-bottom: 1px solid #f1f5f9;
        cursor: pointer;
        font-size: 0.8rem;
        transition: background-color 0.12s ease;
    }
    .autocomplete-item:hover, .autocomplete-item.active {
        background-color: #eff6ff;
    }
    .autocomplete-item:last-child {
        border-bottom: none;
    }
    .badge-arkas-ref {
        font-size: 0.68rem;
        padding: 2px 6px;
        border-radius: 4px;
        background-color: #dbeafe;
        color: #1e40af;
        font-weight: 600;
    }

    /* Section Bulan & Dynamic Cards */
    .section-bulan-header {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 10px 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 16px;
        margin-bottom: 12px;
        flex-wrap: wrap;
        gap: 8px;
    }
    .section-bulan-title {
        font-size: 0.85rem;
        font-weight: 700;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .total-anggaran-badge {
        background: #ecfdf5;
        border: 1.5px solid #a7f3d0;
        color: #065f46;
        padding: 5px 12px;
        border-radius: 8px;
        font-size: 0.88rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 6px;
        font-variant-numeric: tabular-nums;
    }
    .btn-add-month {
        background: #3b82f6;
        color: #ffffff !important;
        border: none;
        border-radius: 6px;
        padding: 5px 12px;
        font-size: 0.78rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: background-color 0.15s;
    }
    .btn-add-month:hover {
        background: #2563eb;
    }
    .card-bulan {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        margin-bottom: 10px;
        transition: all 0.2s ease-in-out;
        position: relative;
    }
    .card-bulan:hover {
        border-color: #cbd5e1;
        box-shadow: 0 3px 8px rgba(15, 23, 42, 0.06);
    }
    .card-bulan-header {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 4px 6px;
        border-radius: 8px 8px 0 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 4px;
    }
    .card-bulan-header .select-bulan {
        width: 70px;
        font-size: 0.72rem;
        font-weight: 600;
        padding: 1px 2px;
        height: 24px;
        border-radius: 4px;
        border: 1px solid #cbd5e1;
        background-color: #ffffff;
        color: #1e293b;
        cursor: pointer;
    }
    .card-bulan-subtotal {
        font-size: 0.72rem;
        font-weight: 700;
        color: #0284c7;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }
    .btn-remove-card {
        background: transparent;
        border: none;
        color: #ef4444;
        font-size: 0.72rem;
        padding: 1px 3px;
        line-height: 1;
        border-radius: 4px;
        cursor: pointer;
        transition: background 0.15s;
    }
    .btn-remove-card:hover {
        background: #fee2e2;
    }
    .card-bulan-body {
        padding: 6px 8px;
    }

    /* Micro-pills Kegiatan Sekolah & Utilitas yang selaras & proporsional */
    .btn-quick-ks, .btn-quick-ks-edit {
        font-size: 0.68rem !important;
        padding: 1px 7px !important;
        line-height: 1.25 !important;
        height: 21px !important;
        border-radius: 4px !important;
        border: 1px solid #cbd5e1 !important;
        background-color: #ffffff !important;
        color: #475569 !important;
        font-weight: 500 !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 4px !important;
        box-shadow: none !important;
        cursor: pointer !important;
        transition: all 0.12s ease-in-out !important;
        white-space: nowrap !important;
        text-decoration: none !important;
    }
    .btn-quick-ks:hover, .btn-quick-ks-edit:hover {
        background-color: #f1f5f9 !important;
        border-color: #94a3b8 !important;
        color: #0f172a !important;
    }
    .btn-quick-ks.active, .btn-quick-ks-edit.active {
        background-color: #e0e7ff !important;
        border-color: #6366f1 !important;
        color: #3730a3 !important;
        font-weight: 600 !important;
        box-shadow: 0 1px 2px rgba(99, 102, 241, 0.15) !important;
    }
    .btn-quick-ks .ks-dot, .btn-quick-ks-edit .ks-dot {
        width: 5px !important;
        height: 5px !important;
        border-radius: 50% !important;
        display: inline-block !important;
        flex-shrink: 0 !important;
    }

    .btn-utility-pill {
        font-size: 0.68rem !important;
        padding: 1px 8px !important;
        line-height: 1.25 !important;
        height: 21px !important;
        border-radius: 4px !important;
        border: 1px solid #fde047 !important;
        background-color: #ffffff !important;
        color: #854d0e !important;
        font-weight: 500 !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 3px !important;
        box-shadow: none !important;
        cursor: pointer !important;
        transition: all 0.12s ease-in-out !important;
        white-space: nowrap !important;
        text-decoration: none !important;
    }
    .btn-utility-pill:hover {
        background-color: #fefce8 !important;
        border-color: #eab308 !important;
        color: #713f12 !important;
    }
    .btn-utility-pill.active {
        background-color: #fef08a !important;
        border-color: #ca8a04 !important;
        color: #713f12 !important;
        font-weight: 600 !important;
        box-shadow: 0 1px 2px rgba(202, 138, 4, 0.15) !important;
    }
    .btn-utility-pill i {
        font-size: 0.62rem !important;
    }

    /* Form Harga Satuan & Harga Max Kompak & Selaras */
    .input-group-compact {
        height: 28px !important;
    }
    .input-group-compact .form-control {
        height: 28px !important;
        font-size: 0.80rem !important;
        padding: 0.15rem 0.5rem !important;
    }
    .input-group-compact .input-group-prepend .input-group-text,
    .input-group-compact .input-group-append .input-group-text {
        height: 28px !important;
        font-size: 0.74rem !important;
        padding: 0 7px !important;
        line-height: 1 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    .input-group-compact .input-group-prepend .rp-satuan {
        background-color: #f0fdf4 !important;
        color: #15803d !important;
        border-color: #bbf7d0 !important;
    }
    .input-group-compact .input-group-prepend .rp-max {
        background-color: #fffbeb !important;
        color: #b45309 !important;
        border-color: #fef08a !important;
    }
    .input-group-compact .form-control.is-invalid-harga {
        border-color: #f87171 !important;
        background-color: #fef2f2 !important;
        color: #b91c1c !important;
    }
</style>
@stop

@section('content')
<div class="container-fluid px-0">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2 px-3 mb-2 shadow-sm d-flex align-items-center" role="alert" style="font-size: 0.8125rem; border-radius: 6px;">
            <i class="fas fa-check-circle mr-2 text-success flex-shrink-0"></i>
            <div class="flex-grow-1 mr-2">{{ session('success') }}</div>
            <button type="button" class="btn-close close flex-shrink-0" data-dismiss="alert" data-bs-dismiss="alert" aria-label="Close" style="position: static; padding: 0.25rem 0.5rem; font-size: 0.75rem;"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show py-2 px-3 mb-2 shadow-sm d-flex align-items-center" role="alert" style="font-size: 0.8125rem; border-radius: 6px;">
            <i class="fas fa-exclamation-triangle mr-2 text-danger flex-shrink-0"></i>
            <div class="flex-grow-1 mr-2">{{ session('error') }}</div>
            <button type="button" class="btn-close close flex-shrink-0" data-dismiss="alert" data-bs-dismiss="alert" aria-label="Close" style="position: static; padding: 0.25rem 0.5rem; font-size: 0.75rem;"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show py-2 px-3 mb-2 shadow-sm d-flex align-items-start" role="alert" style="font-size: 0.8125rem; border-radius: 6px;">
            <i class="fas fa-exclamation-circle mr-2 text-warning flex-shrink-0 mt-1"></i>
            <div class="flex-grow-1 mr-2">{!! session('warning') !!}</div>
            <button type="button" class="btn-close close flex-shrink-0" data-dismiss="alert" data-bs-dismiss="alert" aria-label="Close" style="position: static; padding: 0.25rem 0.5rem; font-size: 0.75rem;"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <h6 class="font-weight-bold mb-1"><i class="fas fa-times-circle mr-1"></i> Terjadi Kesalahan Pengisian Form:</h6>
            <ul class="mb-0 pl-3">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close close" data-dismiss="alert" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- ======================================================== -->
    <!-- ======================================================== -->
    <!-- 4 CARD STATISTIK PAGU ANGGARAN (KOMPAK)                  -->
    <!-- ======================================================== -->
    <div class="row mb-4">
        <!-- Card 1: Pagu Anggaran -->
        <div class="col-12 col-sm-6 col-lg-3 mb-2 mb-lg-0">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-xs font-weight-bold text-muted text-uppercase tracking-wider">Pagu Anggaran {{ $selectedYear }}</span>
                    <a href="javascript:void(0)" class="text-primary font-weight-bold text-xs" 
                       data-toggle="modal" data-target="#modalPagu"
                       data-bs-toggle="modal" data-bs-target="#modalPagu"
                       onclick="window.showModal('#modalPagu')">
                        <i class="fas fa-edit mr-0.5"></i>Ubah
                    </a>
                </div>
                <div class="d-flex justify-content-between align-items-baseline">
                    <div class="text-bold text-dark font-tabular" style="font-size: 1.18rem; line-height: 1.2;">
                        Rp {{ number_format($stats['pagu'], 0, ',', '.') }}
                    </div>
                    <span class="text-xs text-muted">Target BOSP</span>
                </div>
                <div class="bar-indicator bg-primary"></div>
            </div>
        </div>

        <!-- Card 2: Sudah Dianggarkan -->
        <div class="col-12 col-sm-6 col-lg-3 mb-2 mb-lg-0">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-xs font-weight-bold text-muted text-uppercase tracking-wider">Sudah Dianggarkan</span>
                    <span class="badge badge-success px-1.5 py-0.5 font-weight-bold" style="font-size: 0.72rem;">{{ $stats['persen'] }}%</span>
                </div>
                <div class="d-flex justify-content-between align-items-baseline">
                    <div class="text-bold text-success font-tabular" style="font-size: 1.18rem; line-height: 1.2;">
                        Rp {{ number_format($stats['teranggarkan'], 0, ',', '.') }}
                    </div>
                    <span class="text-xs text-muted">Alokasi Rencana</span>
                </div>
                <div class="bar-indicator bg-success"></div>
            </div>
        </div>

        <!-- Card 3: Sisa / Kekurangan Pagu -->
        <div class="col-12 col-sm-6 col-lg-3 mb-2 mb-lg-0">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    @if($stats['sisa'] >= 0)
                        <span class="text-xs font-weight-bold text-muted text-uppercase tracking-wider">Sisa Pagu Belum Alokasi</span>
                        <span class="badge badge-warning px-1.5 py-0.5 text-dark font-weight-bold" style="font-size: 0.72rem;">
                            Sisa {{ round(100 - $stats['persen'], 1) }}%
                        </span>
                    @else
                        <span class="text-xs font-weight-bold text-danger text-uppercase tracking-wider">Defisit / Over Pagu</span>
                        <span class="badge badge-danger px-1.5 py-0.5 font-weight-bold" style="font-size: 0.72rem;">
                            Over {{ round($stats['persen'] - 100, 1) }}%
                        </span>
                    @endif
                </div>
                <div class="d-flex justify-content-between align-items-baseline">
                    <div class="text-bold {{ $stats['sisa'] >= 0 ? 'text-warning' : 'text-danger' }} font-tabular" style="font-size: 1.18rem; line-height: 1.2;">
                        @if($stats['sisa'] >= 0)
                            Rp {{ number_format($stats['sisa'], 0, ',', '.') }}
                        @else
                            -Rp {{ number_format(abs($stats['sisa']), 0, ',', '.') }}
                        @endif
                    </div>
                    <span class="text-xs text-muted">Status Neraca</span>
                </div>
                <div class="bar-indicator {{ $stats['sisa'] >= 0 ? 'bg-warning' : 'bg-danger' }}"></div>
            </div>
        </div>

        <!-- Card 4: Total Rencana Barang -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-xs font-weight-bold text-muted text-uppercase tracking-wider">Total Item RKAS</span>
                    <span class="font-weight-bold text-xs" style="color: #7c3aed;">{{ $stats['recurring_items'] }} Rutin</span>
                </div>
                <div class="d-flex justify-content-between align-items-baseline">
                    <div class="text-bold text-dark font-tabular" style="font-size: 1.18rem; line-height: 1.2;">
                        {{ $stats['total_items'] }} <small class="text-muted font-weight-normal" style="font-size: 0.78rem;">Pos Belanja</small>
                    </div>
                    <span class="text-xs text-muted">Terdata</span>
                </div>
                <div class="bar-indicator" style="background-color: #8b5cf6;"></div>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- BLOK FILTER (5 KOLOM SEJAJAR DENGAN BESARAN SAMA)        -->
    <!-- ======================================================== -->
    <div class="card shadow-sm border-0 mb-3" style="border-radius: 10px;">
        <div class="card-body p-2.5">
            <form method="GET" action="{{ route('bos.rkas.index') }}">
                <input type="hidden" name="month" value="{{ $selectedMonth }}">
                <div class="row align-items-end g-2">
                    <!-- 1. Tahun Anggaran -->
                    <div class="col-12 col-md mb-2 mb-md-0">
                        <label class="small font-weight-bold text-muted mb-1 d-block text-truncate">Tahun Anggaran</label>
                        <select name="year" class="form-control form-control-sm" onchange="this.form.submit()">
                            @foreach($years as $yr)
                                <option value="{{ $yr }}" {{ $selectedYear == $yr ? 'selected' : '' }}>Tahun {{ $yr }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 2. Program Sekolah -->
                    <div class="col-12 col-md mb-2 mb-md-0">
                        <label class="small font-weight-bold text-muted mb-1 d-block text-truncate">Program Sekolah</label>
                        <select name="kegiatan_sekolah_id" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="">Semua Program</option>
                            @foreach($kegiatanSekolahs as $ks)
                                <option value="{{ $ks->id }}" {{ (string)$selectedKegiatanSekolahId === (string)$ks->id ? 'selected' : '' }}>
                                    {{ $ks->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 3. Filter ARKAS -->
                    <div class="col-12 col-md mb-2 mb-md-0">
                        <label class="small font-weight-bold text-muted mb-1 d-block text-truncate">Filter ARKAS</label>
                        <select name="kegiatan" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="">Semua ARKAS</option>
                            @foreach($kegiatanList as $keg)
                                <option value="{{ $keg }}" {{ $selectedKegiatan === $keg ? 'selected' : '' }}>{{ $keg }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 4. Karakter -->
                    <div class="col-12 col-md mb-2 mb-md-0">
                        <label class="small font-weight-bold text-muted mb-1 d-block text-truncate">Karakter</label>
                        <select name="karakter" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="all" {{ $selectedKarakter === 'all' ? 'selected' : '' }}>Semua Karakter</option>
                            <option value="sekali" {{ $selectedKarakter === 'sekali' ? 'selected' : '' }}>Pengajuan Sekali</option>
                            <option value="berulang" {{ $selectedKarakter === 'berulang' ? 'selected' : '' }}>Pengajuan Berulang</option>
                        </select>
                    </div>

                    <!-- 5. Pencarian Barang / Spek / Pemohon -->
                    <div class="col-12 col-md mb-2 mb-md-0">
                        <label class="small font-weight-bold text-muted mb-1 d-block text-truncate">Pencarian Barang / Pemohon</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" class="form-control" placeholder="Cari barang, pemohon..." value="{{ $search }}">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit" title="Cari">
                                    <i class="fas fa-search"></i>
                                </button>
                                @if($search || $selectedKegiatan || $selectedKegiatanSekolahId || $selectedKarakter !== 'all')
                                    <a href="{{ route('bos.rkas.index', ['year' => $selectedYear, 'month' => $selectedMonth]) }}" class="btn btn-default border" title="Reset Filter">
                                        <i class="fas fa-times text-danger"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- TABEL UTAMA RENCANA RKAS                                 -->
    <!-- ======================================================== -->
    <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
        <div class="card-header bg-white pt-3 pb-2.5 border-bottom">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
                <h6 class="mb-0 font-weight-bold text-dark">
                    <i class="fas fa-list text-primary mr-1"></i> Daftar Barang Rencana RKAS BOSP
                    <span class="text-muted font-weight-normal ml-1" style="font-size: 0.85rem;">
                        (Menampilkan {{ $items->firstItem() ?? 0 }} - {{ $items->lastItem() ?? 0 }} dari {{ $items->total() }} item)
                    </span>
                </h6>
                <div>
                    <a href="{{ route('bos.pemaduan.index', ['year' => $selectedYear, 'view_mode' => 'grouped']) }}" class="btn btn-outline-info btn-xs shadow-sm font-weight-bold py-1 px-2" target="_blank">
                        <i class="fas fa-arrows-split-up-and-left mr-1"></i> Buka Halaman Pemaduan RKAS &rarr;
                    </a>
                </div>
            </div>

            <!-- Tabs Bulan (dipindah ke sini tepat di bawah daftar barang) -->
            <div class="d-flex align-items-center overflow-auto pt-1 pb-1" style="gap: 5px; -webkit-overflow-scrolling: touch;">
                <a href="{{ route('bos.rkas.index', array_merge(request()->except(['month', 'page']), ['month' => 'Semua', 'year' => $selectedYear])) }}" 
                   class="month-pill {{ $selectedMonth === 'Semua' ? 'active' : '' }}">
                    Semua Bulan ({{ $stats['total_items'] }})
                </a>
                @foreach($months as $m)
                    <a href="{{ route('bos.rkas.index', array_merge(request()->except(['month', 'page']), ['month' => $m, 'year' => $selectedYear])) }}" 
                       class="month-pill {{ $selectedMonth === $m ? 'active' : '' }}">
                        {{ $m }} ({{ $stats['month_counts'][$m] ?? 0 }})
                    </a>
                @endforeach
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-rkas mb-0">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 45px;">No</th>
                        <th style="min-width: 250px;">Nama Barang Riil & Spesifikasi</th>
                        <th style="min-width: 170px;">Kegiatan & Pemohon</th>
                        <th class="text-center" style="min-width: 100px;">Bulan</th>
                        <th class="text-center" style="width: 110px;">Volume</th>
                        <th class="text-right" style="width: 130px;">Harga Satuan</th>
                        <th class="text-right" style="width: 140px;">Total Anggaran</th>
                        <th class="text-center" style="width: 130px;">Status Terpadu</th>
                        <th class="text-center" style="width: 100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $index => $item)
                        <tr>
                            <td class="text-center text-muted font-tabular align-middle font-weight-bold">
                                {{ $items->firstItem() + $index }}
                            </td>
                            <td>
                                <div class="font-weight-bold text-dark" style="font-size: 0.9rem;">
                                    {{ $item->nama_barang }}
                                    @if($item->utility_profile_id && $item->utilityProfile)
                                        <span class="badge badge-pill badge-warning text-dark ml-1" style="font-size: 0.68rem;" title="Objek Utilitas Terdaftar: {{ $item->utilityProfile->name }}">
                                            <i class="fas fa-bolt mr-0.5"></i> Utilitas
                                        </span>
                                    @endif
                                </div>
                                @if($item->spesifikasi)
                                    <div class="mt-1 text-muted p-1.5 bg-light rounded small" style="border: 1px dashed #cbd5e1; font-size: 0.8rem; line-height: 1.35;">
                                        <strong>Spek:</strong> {{ $item->spesifikasi }}
                                    </div>
                                @endif
                                @if($item->keterangan)
                                    <div class="text-xs text-info mt-1">
                                        <i class="fas fa-info-circle mr-1"></i>{{ $item->keterangan }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($item->kegiatanSekolah || $item->kegiatan_sekolah)
                                    @php
                                        $ksColor = $item->kegiatanSekolah ? $item->kegiatanSekolah->color : '#4f46e5';
                                        $ksNama = $item->kegiatanSekolah ? $item->kegiatanSekolah->nama : $item->kegiatan_sekolah;
                                    @endphp
                                    <div class="mb-1">
                                        <span class="badge text-white px-2 py-1 font-weight-bold shadow-xs" 
                                              style="background-color: {{ $ksColor }}; font-size: 0.72rem; letter-spacing: 0.2px;">
                                            <i class="fas fa-bookmark mr-1 opacity-75"></i>{{ $ksNama }}
                                        </span>
                                    </div>
                                @endif
                                <div class="font-weight-600 text-dark" style="font-size: 0.82rem;">
                                    {{ $item->kegiatan ?: '-' }}
                                </div>
                                @if($item->kode_rekening)
                                    <div class="text-muted mt-0.5" style="font-size: 0.72rem; font-family: monospace;">
                                        <i class="fas fa-file-invoice-dollar text-warning mr-1"></i>{{ $item->kode_rekening }}
                                    </div>
                                @endif
                                <div class="text-muted mt-1" style="font-size: 0.78rem;">
                                    <i class="fas fa-user-tag text-primary mr-1"></i>
                                    {{ $item->guru ? $item->guru->name : ($item->pemohon ? $item->pemohon->name : 'Bendahara/Sekolah') }}
                                </div>
                            </td>
                            <td class="text-center align-middle">
                                <span class="badge badge-info px-2 py-1 font-weight-bold" style="font-size: 0.78rem;">
                                    {{ $item->bulan_pakai }}
                                </span>
                            </td>
                            <td class="text-center font-tabular font-weight-bold text-dark align-middle">
                                {{ rtrim(rtrim(number_format($item->volume, 2, ',', '.'), '0'), ',') }}
                                <span class="text-muted font-weight-normal small">{{ $item->satuan }}</span>
                            </td>
                            <td class="text-right font-tabular text-dark align-middle" style="font-size: 0.85rem;">
                                Rp {{ number_format($item->harga, 0, ',', '.') }}
                            </td>
                            <td class="text-right font-tabular font-weight-bold text-success align-middle" style="font-size: 0.92rem;">
                                Rp {{ number_format($item->jumlah, 0, ',', '.') }}
                            </td>
                            <td class="text-center align-middle">
                                @if($item->kertasKerjaItems->count() > 0)
                                    <span class="badge badge-success px-2 py-1" style="font-size: 0.72rem;" title="Sudah dipadukan dengan {{ $item->kertasKerjaItems->count() }} item Kertas Kerja ARKAS">
                                        <i class="fas fa-check-circle mr-1"></i> Terpadu ({{ $item->kertasKerjaItems->count() }})
                                    </span>
                                @else
                                    <span class="badge badge-light border text-muted px-2 py-1" style="font-size: 0.72rem;" title="Belum dipadukan dengan kertas kerja ARKAS">
                                        <i class="fas fa-circle-notch mr-1 text-warning"></i> Belum Padu
                                    </span>
                                @endif
                            </td>
                            <td class="text-center align-middle">
                                @php
                                    if ($item->is_recurring && $item->recurring_group_id && $item->recurringSiblings && $item->recurringSiblings->count() > 0) {
                                        $cardsData = $item->recurringSiblings
                                            ->sortBy(function ($s) use ($months) {
                                                $idx = array_search($s->bulan_pakai, $months);
                                                return $idx === false ? 99 : $idx;
                                            })
                                            ->values()
                                            ->map(function ($s) {
                                                return [
                                                    'id' => $s->id,
                                                    'month' => $s->bulan_pakai,
                                                    'volume' => (float)$s->volume,
                                                    'unit' => $s->satuan,
                                                ];
                                            });
                                    } else {
                                        $cardsData = collect([[
                                            'id' => $item->id,
                                            'month' => $item->bulan_pakai,
                                            'volume' => (float)$item->volume,
                                            'unit' => $item->satuan,
                                        ]]);
                                    }
                                @endphp
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-default text-primary btn-sm btn-edit" 
                                            title="Ubah Item"
                                            data-id="{{ $item->id }}"
                                            data-nama="{{ e($item->nama_barang) }}"
                                            data-spek="{{ e($item->spesifikasi) }}"
                                            data-kegiatan="{{ e($item->kegiatan) }}"
                                            data-kegiatan-sekolah-id="{{ $item->kegiatan_sekolah_id }}"
                                            data-kegiatan-sekolah="{{ e($item->kegiatan_sekolah) }}"
                                            data-kode-rekening="{{ e($item->kode_rekening) }}"
                                            data-guru-id="{{ $item->guru_id }}"
                                            data-volume="{{ (float)$item->volume }}"
                                            data-satuan="{{ e($item->satuan) }}"
                                            data-harga="{{ (float)$item->harga }}"
                                            data-bulan="{{ $item->bulan_pakai }}"
                                            data-keterangan="{{ e($item->keterangan) }}"
                                            data-is-recurring="{{ $item->is_recurring ? 1 : 0 }}"
                                            data-recurring-group="{{ $item->recurring_group_id }}"
                                            data-utility-profile-id="{{ $item->utility_profile_id }}"
                                            data-cards="{{ json_encode($cardsData) }}">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <button type="button" class="btn btn-default text-danger btn-sm btn-delete" 
                                            title="Hapus Item"
                                            data-id="{{ $item->id }}"
                                            data-nama="{{ e($item->nama_barang) }}"
                                            data-is-recurring="{{ $item->is_recurring ? 1 : 0 }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <div class="mb-2">
                                    <i class="fas fa-inbox fa-3x text-secondary" style="opacity: 0.4;"></i>
                                </div>
                                <h6 class="font-weight-bold text-dark">Belum ada data Rencana RKAS</h6>
                                <p class="text-xs mb-3">Tidak ada data item yang sesuai dengan filter bulan atau pencarian ini.</p>
                                <button type="button" class="btn btn-success btn-sm font-weight-bold" 
                                        data-toggle="modal" data-target="#modalAddItem" 
                                        data-bs-toggle="modal" data-bs-target="#modalAddItem"
                                        onclick="window.showModal('#modalAddItem')">
                                    <i class="fas fa-plus mr-1"></i> Tambah Item RKAS Sekarang
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
            <div class="card-footer bg-white py-2 border-top d-flex justify-content-between align-items-center">
                <div class="text-xs text-muted">
                    Menampilkan {{ $items->firstItem() }} sampai {{ $items->lastItem() }} dari {{ $items->total() }} entri
                </div>
                <div>
                    {{ $items->links('pagination::bootstrap-4') }}
                </div>
            </div>
        @endif
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL: IMPORT EXCEL RENCANA RKAS                         -->
<!-- ======================================================== -->
<div class="modal fade" id="modalImportExcel" tabindex="-1" role="dialog" aria-labelledby="modalImportExcelLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered" role="document">
        <form method="POST" action="{{ route('bos.rkas.import') }}" enctype="multipart/form-data" class="modal-content border-0 shadow">
            @csrf
            <input type="hidden" name="tahun_anggaran" value="{{ $selectedYear }}">

            <div class="modal-header bg-dark text-white py-2.5 px-3">
                <h5 class="modal-title font-weight-bold" id="modalImportExcelLabel" style="font-size: 1.05rem;">
                    <i class="fas fa-file-excel text-success mr-1"></i> Import Excel Rencana RKAS BOSP
                </h5>
                <button type="button" class="btn-close text-white close" data-dismiss="modal" data-bs-dismiss="modal" onclick="window.hideModal('#modalImportExcel')" aria-label="Close"></button>
            </div>

            <div class="modal-body p-3.5">
                <div class="alert alert-info py-2 px-3 mb-3" style="font-size: 0.8rem; border-radius: 6px;">
                    <div class="font-weight-bold mb-1"><i class="fas fa-lightbulb text-warning mr-1"></i> Petunjuk Import Excel:</div>
                    <ol class="mb-0 pl-3" style="line-height: 1.5;">
                        <li>Unduh template resmi terlebih dahulu agar format kolom sesuai.</li>
                        <li>Urutan kolom: <strong>No, Kegiatan, Bulan, Nama barang, Spesifikasi, Volume, Satuan, Harga, Total, Pemohon, Keterangan</strong>.</li>
                        <li>Kolom <strong>Kegiatan</strong>: Bisa menuliskan Program Sekolah (<em>Pramuka, OSIS, TU, PKL, dsb</em>) dan otomatis dipetakan ke kode kegiatan ARKAS!</li>
                        <li>Kolom <strong>Total</strong> dihitung otomatis oleh formula Excel <code>=Volume x Harga</code>.</li>
                        <li>Nama <strong>Pemohon</strong> otomatis dicocokkan ke daftar guru (contoh: <em>Army</em> &rarr; <em>Army Widhi Cahyono</em>).</li>
                        <li>Barang dan rekening belanja otomatis diselaraskan dengan referensi resmi ARKAS.</li>
                    </ol>
                </div>

                <div class="text-center mb-3">
                    <a href="{{ route('bos.rkas.template', ['year' => $selectedYear]) }}" class="btn btn-outline-success btn-sm font-weight-bold px-3 py-1.5 shadow-sm">
                        <i class="fas fa-download mr-1"></i> Unduh Template Excel RKAS (Tahun {{ $selectedYear }})
                    </a>
                </div>

                <div class="form-group mb-2">
                    <label class="small font-weight-bold text-dark mb-1">
                        Pilih Berkas Excel (.xlsx / .xls / .csv) : <span class="text-danger">*</span>
                    </label>
                    <input type="file" name="file" class="form-control-file border p-1.5 rounded w-100 bg-white" accept=".xlsx,.xls,.csv" required style="font-size: 0.82rem;">
                    <small class="text-muted mt-1 d-block" style="font-size: 0.74rem;">Maksimal ukuran berkas 10 MB. Data akan diimpor ke <strong>Tahun Anggaran {{ $selectedYear }}</strong>.</small>
                </div>
            </div>

            <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" data-bs-dismiss="modal" onclick="window.hideModal('#modalImportExcel')">Batal</button>
                <button type="submit" class="btn btn-success btn-sm font-weight-bold px-3">
                    <i class="fas fa-upload mr-1"></i> Mulai Import Data
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL: TAMBAH ITEM RENCANA RKAS                         -->
<!-- ======================================================== -->
<div class="modal fade" id="modalAddItem" tabindex="-1" role="dialog" aria-labelledby="modalAddItemLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document" style="max-width: 900px;">
        <form method="POST" action="{{ route('bos.rkas.store') }}" id="formAddItem" class="modal-content border-0 shadow">
            @csrf
            <input type="hidden" name="tahun_anggaran" value="{{ $selectedYear }}">
            <input type="hidden" name="months_json" id="addMonthsJson">
            <input type="hidden" name="satuan" id="addHiddenSatuan" value="Unit">
            <input type="hidden" name="volume" id="addHiddenVolume" value="1">

            <div class="modal-header bg-dark text-white py-2.5 px-3">
                <h5 class="modal-title font-weight-bold" id="modalAddItemLabel" style="font-size: 1.05rem;">
                    <i class="fas fa-plus-circle text-success mr-1"></i> Tambah Rencana Barang RKAS BOSP
                </h5>
                <button type="button" class="btn-close text-white close" data-dismiss="modal" data-bs-dismiss="modal" onclick="window.hideModal('#modalAddItem')" aria-label="Close"></button>
            </div>

            <div class="modal-body p-3.5" style="max-height: 80vh; overflow-y: auto;">
                
                <!-- 0. PROGRAM / KEGIATAN SEKOLAH (Pramuka, OSIS, TU, PKL, dsb) -->
                <div class="form-group mb-2.5 p-2 rounded border" style="background-color: #f8fafc; border-color: #e2e8f0 !important;">
                    <div class="d-flex justify-content-between align-items-center mb-1.5">
                        <label class="small font-weight-bold text-dark mb-0">
                            <i class="fas fa-bookmark text-primary mr-1"></i> Program / Kegiatan Sekolah :
                        </label>
                        <span class="text-muted small" style="font-size: 0.72rem;">Klik badge untuk otomatis mengisi kegiatan ARKAS</span>
                    </div>
                    <input type="hidden" name="kegiatan_sekolah_id" id="addKegiatanSekolahId" value="">
                    <input type="hidden" name="kegiatan_sekolah" id="addKegiatanSekolahNama" value="">
                    <div class="d-flex flex-wrap align-items-center" style="gap: 4px;">
                        <button type="button" class="btn-quick-ks active"
                                data-id=""
                                data-nama=""
                                data-kode=""
                                data-arkas=""
                                title="Tanpa Program Sekolah">
                            <span class="ks-dot" style="background-color: #94a3b8;"></span>Tanpa Program
                        </button>
                        @foreach($kegiatanSekolahs as $ks)
                            <button type="button" class="btn-quick-ks"
                                    data-id="{{ $ks->id }}"
                                    data-nama="{{ $ks->nama }}"
                                    data-kode="{{ $ks->kode }}"
                                    data-arkas="{{ $ks->full_arkas_kegiatan }}"
                                    title="{{ $ks->nama }} {{ $ks->kode ? '('.$ks->kode.')' : '' }}">
                                <span class="ks-dot" style="background-color: {{ $ks->color }};"></span>{{ $ks->nama }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- BOX PILIHAN OBJEK UTILITAS (OTOMATIS MUNCUL JIKA KEGIATAN ADALAH UTILITAS) -->
                <div id="addUtilityBox" class="p-2 mb-2 rounded border" style="display: none; background: #fffcf5; border-color: #fef08a !important;">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="d-flex align-items-center" style="gap: 5px;">
                            <span class="badge px-1.5 py-0.5 font-weight-bold" style="font-size: 0.68rem; background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a;">
                                <i class="fas fa-bolt mr-1 text-warning"></i> <span id="addUtilityTitle">Akun Utilitas Terdaftar</span>
                            </span>
                            <span class="text-muted" style="font-size: 0.70rem;">Pilih akun untuk otomatis mengisi uraian & spesifikasi:</span>
                        </div>
                        <button type="button" class="btn btn-link text-muted p-0" style="font-size: 0.70rem; text-decoration: none;" onclick="clearUtilitySelection('add')" title="Batalkan kaitan utilitas">
                            <i class="fas fa-times-circle mr-0.5"></i>Batal Utilitas
                        </button>
                    </div>
                    <div class="row align-items-center">
                        <div class="col-12 col-md-5 mb-1 mb-md-0">
                            <select id="addUtilitySelect" class="form-control form-control-sm" style="font-size: 0.78rem; height: 28px;" onchange="onUtilitySelectChange('add', this.value)">
                                <option value="">-- Pilih Akun Utilitas --</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-7">
                            <div id="addUtilityPills" class="d-flex flex-wrap align-items-center" style="gap: 3px;">
                                <!-- Pills rendered dynamically by JS -->
                            </div>
                        </div>
                    </div>
                    <div id="addUtilityInfo" class="mt-1 p-1 px-2 bg-white rounded border small" style="display: none; font-size: 0.70rem; border-color: #fef08a !important;">
                    </div>
                </div>
                <input type="hidden" name="utility_profile_id" id="addUtilityProfileId">

                <!-- 1. KEGIATAN ARKAS -->
                <div class="form-group mb-2.5 autocomplete-wrapper">
                    <label class="small font-weight-bold text-dark d-flex justify-content-between align-items-center mb-1">
                        <span><i class="fas fa-list-check text-primary mr-1"></i> Kegiatan ARKAS : <span class="text-danger">*</span></span>
                        <span class="text-muted small" style="font-size: 0.72rem;">Mengacu pada referensi ARKAS (> 2 huruf)</span>
                    </label>
                    <input type="text" name="kegiatan" id="addKegiatanInput" class="form-control form-control-sm" 
                           placeholder="Ketik min. 2 huruf (contoh: 02.02.01 atau perpustakaan)..." 
                           autocomplete="off" required>
                    <div id="dropdownAddKegiatan" class="autocomplete-dropdown"></div>
                </div>

                <!-- Rekening Belanja disimpan via Hidden Input dari referensi ARKAS -->
                <input type="hidden" name="kode_rekening" id="addRekeningInput">

                <!-- 2. URAIAN / NAMA BARANG -->
                <div class="form-group mb-2.5 autocomplete-wrapper">
                    <label class="small font-weight-bold text-dark d-flex justify-content-between align-items-center mb-1">
                        <span><i class="fas fa-box text-info mr-1"></i> Uraian / Nama Barang : <span class="text-danger">*</span></span>
                        <div class="d-flex align-items-center" style="gap: 4px;">
                            <span class="badge badge-warning text-dark px-2 py-0.5" id="addBadgeUtility" style="font-size: 0.68rem; display: none;">
                                <i class="fas fa-bolt mr-1"></i>Akun Utilitas
                            </span>
                            <span class="badge badge-primary px-2 py-0.5" id="addBadgeRef" style="font-size: 0.68rem; display: none;">
                                <i class="fas fa-check-circle mr-1"></i>Ref ARKAS
                            </span>
                        </div>
                    </label>
                    <input type="text" name="nama_barang" id="addUraianInput" class="form-control form-control-sm" 
                           placeholder="Ketik min. 2 huruf dari ARKAS atau pilih akun utilitas..." 
                           autocomplete="off" required>
                    <div id="dropdownAddUraian" class="autocomplete-dropdown"></div>
                </div>

                <!-- 3. SPESIFIKASI BARANG / LAYANAN -->
                <div class="form-group mb-2.5">
                    <label class="small font-weight-bold text-dark mb-1">
                        <i class="fas fa-info-circle text-secondary mr-1"></i> Spesifikasi barang / layanan :
                    </label>
                    <input type="text" name="spesifikasi" id="addSpesifikasi" class="form-control form-control-sm" 
                           placeholder="Merek, tipe, ukuran, nomor pelanggan, dll (opsional)...">
                </div>

                <!-- 4. HARGA SATUAN & HARGA MAX (1 BARIS 2 KOLOM) -->
                <div class="row mb-2.5">
                    <div class="col-12 col-md-6 mb-2 mb-md-0">
                        <label class="small font-weight-bold text-dark mb-1 d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-tag text-success mr-1"></i> Harga Satuan : <span class="text-danger">*</span></span>
                            <span id="statusHargaSatuanAdd" style="font-size: 0.70rem; font-weight: normal;"></span>
                        </label>
                        <div class="input-group input-group-sm input-group-compact">
                            <div class="input-group-prepend">
                                <span class="input-group-text font-weight-bold rp-satuan">Rp</span>
                            </div>
                            <input type="text" name="harga" id="addHargaInput" class="form-control form-control-sm font-tabular text-bold text-right thousand-separator-input rupiah-input" 
                                   placeholder="0" required oninput="recalcAddModal()">
                            <div class="input-group-append">
                                <span class="input-group-text bg-light text-muted small" id="labelSatuanAdd">/ Satuan</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="small font-weight-bold text-dark mb-1 d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-shield-halved text-warning mr-1"></i> Harga Max Standar ARKAS :</span>
                            <span id="labelRefRangeInfo" class="text-muted" style="font-size: 0.70rem; font-weight: normal;">Batas standar ARKAS</span>
                        </label>
                        <div class="input-group input-group-sm input-group-compact">
                            <div class="input-group-prepend">
                                <span class="input-group-text font-weight-bold rp-max">Rp</span>
                            </div>
                            <input type="text" id="displayHargaMax" class="form-control form-control-sm font-tabular text-right bg-light text-muted font-weight-bold" 
                                   value="-" readonly placeholder="-">
                            <div class="input-group-append" id="wrapperSatuanMax">
                                <span class="input-group-text bg-light text-muted small" id="labelSatuanMax">/ Satuan</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. DIANGGARKAN UNTUK BULAN & TOTAL ANGGARAN -->
                <div class="section-bulan-header">
                    <div>
                        <div class="section-bulan-title">
                            <i class="fas fa-calendar-alt text-primary"></i>
                            Dianggarkan untuk Bulan
                            <span class="badge badge-primary rounded-pill ml-1" id="badgeBulanCount">1 Bulan</span>
                        </div>
                        <div class="d-flex align-items-center mt-1" style="font-size: 0.74rem; gap: 6px;">
                            <span class="text-muted">Pintasan:</span>
                            <a href="javascript:void(0)" onclick="quickSetMonths('all')" class="font-weight-bold text-primary">12 Bulan</a> &bull;
                            <a href="javascript:void(0)" onclick="quickSetMonths('sem1')" class="font-weight-bold text-primary">Semester 1</a> &bull;
                            <a href="javascript:void(0)" onclick="quickSetMonths('sem2')" class="font-weight-bold text-primary">Semester 2</a>
                        </div>
                    </div>

                    <div class="d-flex align-items-center" style="gap: 10px;">
                        <div class="total-anggaran-badge">
                            <span>Total Anggaran:</span>
                            <span class="font-monospace" id="displayTotalAnggaran">Rp 0</span>
                        </div>
                        <button type="button" class="btn-add-month" onclick="addNewMonthCard()">
                            <i class="fas fa-plus mr-1"></i> Tambah Bulan
                        </button>
                    </div>
                </div>

                <!-- 6. CONTAINER CARD BULAN (GRID 3 KOLOM) -->
                <div class="row" id="monthCardsContainer" style="margin-left: -4px; margin-right: -4px;">
                    <!-- Dynamic Month Cards rendered here -->
                </div>

                <!-- 7. PEMOHON DAN KETERANGAN (OPSIONAL) -->
                <div class="card bg-light border p-2.5 mt-2.5 mb-1" style="border-radius: 8px;">
                    <div class="small font-weight-bold text-dark mb-2">
                        <i class="fas fa-user-edit text-secondary mr-1"></i> Pemohon dan keterangan (opsional):
                    </div>
                    <div class="row">
                        <div class="col-12 col-md-6 mb-2 mb-md-0">
                            <label class="small text-muted font-weight-bold mb-1">Pemohon :</label>
                            <select name="guru_id" id="addGuruId" class="form-control form-control-sm">
                                <option value="">-- Tanpa Spesifik Guru (Sekolah) --</option>
                                @foreach($gurus as $guru)
                                    <option value="{{ $guru->id }}">{{ $guru->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="small text-muted font-weight-bold mb-1">Keterangan :</label>
                            <input type="text" name="keterangan" id="addKeterangan" class="form-control form-control-sm" placeholder="Catatan urgensi belanja...">
                        </div>
                    </div>
                </div>

            </div>

            <div class="modal-footer bg-light py-2.5 d-flex justify-content-between">
                <button type="button" class="btn btn-default btn-sm font-weight-bold px-3" data-dismiss="modal" data-bs-dismiss="modal" onclick="window.hideModal('#modalAddItem')">Batal</button>
                <button type="submit" class="btn btn-success btn-sm font-weight-bold px-4 shadow-sm" id="btnSubmitAddItem">
                    <i class="fas fa-save mr-1"></i> Simpan Rencana RKAS
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL: UBAH ITEM RENCANA RKAS                           -->
<!-- ======================================================== -->
<div class="modal fade" id="modalEditItem" tabindex="-1" role="dialog" aria-labelledby="modalEditItemLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <form method="POST" action="" id="formEditItem" class="modal-content border-0 shadow">
            @csrf
            @method('PUT')
            <input type="hidden" name="months_json" id="editMonthsJson">
            <input type="hidden" name="satuan" id="editHiddenSatuan" value="Unit">
            <input type="hidden" name="volume" id="editHiddenVolume" value="1">
            <input type="hidden" name="bulan_pakai" id="editHiddenBulanPakai" value="Januari">
            <input type="hidden" name="kode_rekening" id="editRekeningInput">
            <input type="hidden" name="kegiatan_sekolah" id="editKegiatanSekolahNama">

            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title font-weight-bold" id="modalEditItemLabel">
                    <i class="fas fa-edit text-primary mr-1"></i> Ubah Rencana Barang RKAS BOSP
                </h5>
                <button type="button" class="btn-close text-white close" data-dismiss="modal" data-bs-dismiss="modal" onclick="window.hideModal('#modalEditItem')" aria-label="Close"></button>
            </div>

            <div class="modal-body p-3.5" style="max-height: 80vh; overflow-y: auto;">
                
                <!-- 0. PROGRAM / KEGIATAN SEKOLAH -->
                <div class="form-group mb-2.5 p-2 rounded border" style="background-color: #f8fafc; border-color: #e2e8f0 !important;">
                    <div class="d-flex justify-content-between align-items-center mb-1.5">
                        <label class="small font-weight-bold text-dark mb-0">
                            <i class="fas fa-bookmark text-primary mr-1"></i> Program / Kegiatan Sekolah :
                        </label>
                        <span class="text-muted small" style="font-size: 0.72rem;">Klik badge untuk otomatis mengisi kegiatan ARKAS</span>
                    </div>
                    <input type="hidden" name="kegiatan_sekolah_id" id="editKegiatanSekolahId" value="">
                    <input type="hidden" name="kegiatan_sekolah" id="editKegiatanSekolahNama" value="">
                    <div class="d-flex flex-wrap align-items-center" style="gap: 4px;">
                        <button type="button" class="btn-quick-ks-edit active"
                                data-id=""
                                data-nama=""
                                data-kode=""
                                data-arkas=""
                                title="Tanpa Program Sekolah">
                            <span class="ks-dot" style="background-color: #94a3b8;"></span>Tanpa Program
                        </button>
                        @foreach($kegiatanSekolahs as $ks)
                            <button type="button" class="btn-quick-ks-edit"
                                    data-id="{{ $ks->id }}"
                                    data-nama="{{ $ks->nama }}"
                                    data-kode="{{ $ks->kode }}"
                                    data-arkas="{{ $ks->full_arkas_kegiatan }}"
                                    title="{{ $ks->nama }} {{ $ks->kode ? '('.$ks->kode.')' : '' }}">
                                <span class="ks-dot" style="background-color: {{ $ks->color }};"></span>{{ $ks->nama }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- BOX PILIHAN OBJEK UTILITAS EDIT (OTOMATIS MUNCUL JIKA KEGIATAN ADALAH UTILITAS) -->
                <div id="editUtilityBox" class="p-2 mb-2 rounded border" style="display: none; background: #fffcf5; border-color: #fef08a !important;">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="d-flex align-items-center" style="gap: 5px;">
                            <span class="badge px-1.5 py-0.5 font-weight-bold" style="font-size: 0.68rem; background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a;">
                                <i class="fas fa-bolt mr-1 text-warning"></i> <span id="editUtilityTitle">Akun Utilitas Terdaftar</span>
                            </span>
                            <span class="text-muted" style="font-size: 0.70rem;">Pilih akun untuk memperbarui uraian & spesifikasi:</span>
                        </div>
                        <button type="button" class="btn btn-link text-muted p-0" style="font-size: 0.70rem; text-decoration: none;" onclick="clearUtilitySelection('edit')" title="Batalkan kaitan utilitas">
                            <i class="fas fa-times-circle mr-0.5"></i>Batal Utilitas
                        </button>
                    </div>
                    <div class="row align-items-center">
                        <div class="col-12 col-md-5 mb-1 mb-md-0">
                            <select id="editUtilitySelect" class="form-control form-control-sm" style="font-size: 0.78rem; height: 28px;" onchange="onUtilitySelectChange('edit', this.value)">
                                <option value="">-- Pilih Akun Utilitas --</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-7">
                            <div id="editUtilityPills" class="d-flex flex-wrap align-items-center" style="gap: 3px;">
                                <!-- Pills rendered dynamically by JS -->
                            </div>
                        </div>
                    </div>
                    <div id="editUtilityInfo" class="mt-1 p-1 px-2 bg-white rounded border small" style="display: none; font-size: 0.70rem; border-color: #fef08a !important;">
                    </div>
                </div>
                <input type="hidden" name="utility_profile_id" id="editUtilityProfileId">

                <!-- 1. KEGIATAN ARKAS -->
                <div class="form-group mb-2.5 autocomplete-wrapper">
                    <label class="small font-weight-bold text-dark d-flex justify-content-between align-items-center mb-1">
                        <span><i class="fas fa-list-check text-primary mr-1"></i> Kegiatan ARKAS : <span class="text-danger">*</span></span>
                        <span class="text-muted small" style="font-size: 0.72rem;">Mengacu pada referensi ARKAS (> 2 huruf)</span>
                    </label>
                    <input type="text" name="kegiatan" id="editKegiatanInput" class="form-control form-control-sm" 
                           placeholder="Ketik min. 2 huruf (contoh: 02.02.01 atau perpustakaan)..." 
                           autocomplete="off" required>
                    <div id="dropdownEditKegiatan" class="autocomplete-dropdown"></div>
                </div>

                <!-- 2. URAIAN / NAMA BARANG -->
                <div class="form-group mb-2.5 autocomplete-wrapper">
                    <label class="small font-weight-bold text-dark d-flex justify-content-between align-items-center mb-1">
                        <span><i class="fas fa-box text-info mr-1"></i> Uraian / Nama Barang : <span class="text-danger">*</span></span>
                        <div class="d-flex align-items-center" style="gap: 4px;">
                            <span class="badge badge-warning text-dark px-2 py-0.5" id="editBadgeUtility" style="font-size: 0.68rem; display: none;">
                                <i class="fas fa-bolt mr-1"></i>Akun Utilitas
                            </span>
                            <span class="badge badge-primary px-2 py-0.5" id="editBadgeRef" style="font-size: 0.68rem; display: none;">
                                <i class="fas fa-check-circle mr-1"></i>Ref ARKAS
                            </span>
                        </div>
                    </label>
                    <input type="text" name="nama_barang" id="editNamaBarang" class="form-control form-control-sm" 
                           placeholder="Ketik min. 2 huruf dari ARKAS atau ketik manual..." 
                           autocomplete="off" required>
                    <div id="dropdownEditUraian" class="autocomplete-dropdown"></div>
                </div>

                <!-- 3. SPESIFIKASI BARANG / LAYANAN -->
                <div class="form-group mb-2.5">
                    <label class="small font-weight-bold text-dark mb-1">
                        <i class="fas fa-info-circle text-secondary mr-1"></i> Spesifikasi barang / layanan :
                    </label>
                    <input type="text" name="spesifikasi" id="editSpesifikasi" class="form-control form-control-sm" 
                           placeholder="Merek, tipe, ukuran, nomor pelanggan, dll (opsional)...">
                </div>

                <!-- 4. HARGA SATUAN & HARGA MAX -->
                <div class="row mb-2.5">
                    <div class="col-12 col-md-6 mb-2 mb-md-0">
                        <label class="small font-weight-bold text-dark mb-1 d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-tag text-success mr-1"></i> Harga Satuan : <span class="text-danger">*</span></span>
                            <span id="statusHargaSatuanEdit" style="font-size: 0.70rem; font-weight: normal;"></span>
                        </label>
                        <div class="input-group input-group-sm input-group-compact">
                            <div class="input-group-prepend">
                                <span class="input-group-text font-weight-bold rp-satuan">Rp</span>
                            </div>
                            <input type="text" name="harga" id="editHargaInput" class="form-control form-control-sm font-tabular text-bold text-right thousand-separator-input rupiah-input" 
                                   placeholder="0" required oninput="recalcEditModal()">
                            <div class="input-group-append">
                                <span class="input-group-text bg-light text-muted small" id="labelSatuanEdit">/ Satuan</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="small font-weight-bold text-dark mb-1 d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-shield-halved text-warning mr-1"></i> Harga Max Standar ARKAS :</span>
                            <span id="labelEditRefRangeInfo" class="text-muted" style="font-size: 0.70rem; font-weight: normal;">Batas standar ARKAS</span>
                        </label>
                        <div class="input-group input-group-sm input-group-compact">
                            <div class="input-group-prepend">
                                <span class="input-group-text font-weight-bold rp-max">Rp</span>
                            </div>
                            <input type="text" id="displayEditHargaMax" class="form-control form-control-sm font-tabular text-right bg-light text-muted font-weight-bold" 
                                   value="-" readonly placeholder="-">
                            <div class="input-group-append">
                                <span class="input-group-text bg-light text-muted small" id="labelEditSatuanMax">/ Satuan</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. DIANGGARKAN UNTUK BULAN & TOTAL ANGGARAN (GRID 3 KOLOM) -->
                <div class="section-bulan-header">
                    <div>
                        <div class="section-bulan-title">
                            <i class="fas fa-calendar-alt text-primary"></i>
                            Alokasi Bulan & Volume
                            <span class="badge badge-primary rounded-pill ml-1" id="badgeEditBulanCount">1 Bulan</span>
                        </div>
                        <div class="d-flex align-items-center mt-1" style="font-size: 0.74rem; gap: 6px;">
                            <span class="text-muted">Pintasan:</span>
                            <a href="javascript:void(0)" onclick="quickSetEditMonths('all')" class="font-weight-bold text-primary">12 Bulan</a> &bull;
                            <a href="javascript:void(0)" onclick="quickSetEditMonths('sem1')" class="font-weight-bold text-primary">Semester 1</a> &bull;
                            <a href="javascript:void(0)" onclick="quickSetEditMonths('sem2')" class="font-weight-bold text-primary">Semester 2</a>
                        </div>
                    </div>

                    <div class="d-flex align-items-center" style="gap: 10px;">
                        <div class="total-anggaran-badge">
                            <span>Total Anggaran:</span>
                            <span class="font-monospace" id="displayEditTotalAnggaran">Rp 0</span>
                        </div>
                        <button type="button" class="btn-add-month" onclick="addNewEditMonthCard()">
                            <i class="fas fa-plus mr-1"></i> Tambah Bulan
                        </button>
                    </div>
                </div>

                <!-- 6. CONTAINER CARD BULAN EDIT (GRID 3 KOLOM) -->
                <div class="row" id="editMonthCardsContainer" style="margin-left: -4px; margin-right: -4px;">
                    <!-- Dynamic Month Cards rendered here -->
                </div>

                <!-- 7. PEMOHON DAN KETERANGAN (OPSIONAL) -->
                <div class="card bg-light border p-2.5 mt-2.5 mb-1" style="border-radius: 8px;">
                    <div class="small font-weight-bold text-dark mb-2">
                        <i class="fas fa-user-edit text-secondary mr-1"></i> Pemohon dan keterangan (opsional):
                    </div>
                    <div class="row">
                        <div class="col-12 col-md-6 mb-2 mb-md-0">
                            <label class="small text-muted font-weight-bold mb-1">Pemohon :</label>
                            <select name="guru_id" id="editGuruId" class="form-control form-control-sm">
                                <option value="">-- Tanpa Spesifik Guru (Sekolah) --</option>
                                @foreach($gurus as $guru)
                                    <option value="{{ $guru->id }}">{{ $guru->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="small text-muted font-weight-bold mb-1">Keterangan :</label>
                            <input type="text" name="keterangan" id="editKeterangan" class="form-control form-control-sm" placeholder="Catatan urgensi belanja...">
                        </div>
                    </div>
                </div>

            </div>

            <div class="modal-footer bg-light py-2.5 d-flex justify-content-between">
                <button type="button" class="btn btn-default btn-sm font-weight-bold px-3" data-dismiss="modal" data-bs-dismiss="modal" onclick="window.hideModal('#modalEditItem')">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm font-weight-bold px-4 shadow-sm" id="btnSubmitEditItem">
                    <i class="fas fa-save mr-1"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL: ATUR PAGU ANGGARAN TAHUNAN                        -->
<!-- ======================================================== -->
<div class="modal fade" id="modalPagu" tabindex="-1" role="dialog" aria-labelledby="modalPaguLabel" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <form method="POST" action="{{ route('bos.rkas.pagu.save') }}" id="formPaguModal" class="modal-content border-0 shadow">
            @csrf
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title font-weight-bold" id="modalPaguLabel">
                    <i class="fas fa-vault text-warning mr-1"></i> Atur Pagu Anggaran BOSP
                </h5>
                <button type="button" class="btn-close text-white close" data-dismiss="modal" data-bs-dismiss="modal" onclick="window.hideModal('#modalPagu')" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <div class="form-group mb-3">
                    <label class="small font-weight-bold text-dark">Tahun Anggaran:</label>
                    <input type="text" name="year" value="{{ $selectedYear }}" readonly class="form-control form-control-sm bg-light font-weight-bold">
                </div>

                <!-- SEPARATOR -->
                <div class="form-section-separator">
                    <span class="sep-label text-primary pl-0"><i class="fas fa-money-check-dollar mr-1"></i> Target Pagu Anggaran</span>
                    <span class="sep-line"></span>
                </div>

                <div class="form-group mb-3">
                    <label class="small font-weight-bold text-dark">Nominal Total Pagu Anggaran (Rp): <span class="text-danger">*</span></label>
                    <div class="input-group input-group-lg">
                        <div class="input-group-prepend">
                            <span class="input-group-text font-weight-bold text-primary">Rp</span>
                        </div>
                        <input type="text" name="amount" id="paguAmountInput" class="form-control form-control-lg font-weight-bold text-primary font-tabular thousand-separator-input rupiah-input" 
                               value="{{ $pagu ? number_format((float)$pagu->amount, 0, ',', '.') : '' }}" 
                               placeholder="Contoh: 450.000.000" required>
                    </div>
                </div>

                <!-- SEPARATOR -->
                <div class="form-section-separator">
                    <span class="sep-label text-muted pl-0"><i class="fas fa-comment-dots mr-1"></i> Dasar Perhitungan</span>
                    <span class="sep-line"></span>
                </div>

                <div class="form-group mb-0">
                    <label class="small font-weight-bold text-dark">Catatan / Dasar Perhitungan:</label>
                    <textarea name="notes" rows="2" class="form-control form-control-sm" placeholder="Contoh: 500 siswa x Rp 900.000 (sesuai SK penetapan)">{{ $pagu ? $pagu->notes : '' }}</textarea>
                </div>
            </div>

            <div class="modal-footer bg-light py-2.5 d-flex justify-content-between">
                <button type="button" class="btn btn-default btn-sm font-weight-bold px-3" data-dismiss="modal" data-bs-dismiss="modal" onclick="window.hideModal('#modalPagu')">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm font-weight-bold px-4 shadow-sm">
                    <i class="fas fa-check mr-1"></i> Simpan Pagu Anggaran
                </button>
            </div>
        </form>
    </div>
</div>

<!-- FORM UNTUK DELETE -->
<form id="formDeleteItem" method="POST" action="" style="display: none;">
    @csrf
    @method('DELETE')
    <input type="hidden" name="delete_all_recurring" id="inputDeleteAllRecurring" value="0">
</form>
@stop

@section('js')
<script>
    // Universal modal helper
    window.showModal = function(selector) {
        const el = typeof selector === 'string' ? document.querySelector(selector) : selector;
        if (!el) return;

        if (window.bootstrap && typeof window.bootstrap.Modal !== 'undefined') {
            try {
                const modal = bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(el) : (bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el));
                modal.show();
                return;
            } catch (e) {}
        }

        if (window.jQuery && typeof $(el).modal === 'function') {
            try {
                $(el).modal('show');
                return;
            } catch (e) {}
        }

        el.classList.add('show');
        el.style.display = 'block';
        el.removeAttribute('aria-hidden');
        el.setAttribute('aria-modal', 'true');
        document.body.classList.add('modal-open');
        if (!document.querySelector('.modal-backdrop')) {
            const bd = document.createElement('div');
            bd.className = 'modal-backdrop fade show custom-modal-backdrop';
            document.body.appendChild(bd);
        }
    };

    window.hideModal = function(selector) {
        const el = typeof selector === 'string' ? document.querySelector(selector) : selector;
        if (!el) return;

        if (window.bootstrap && typeof window.bootstrap.Modal !== 'undefined') {
            try {
                const modal = bootstrap.Modal.getInstance(el);
                if (modal) {
                    modal.hide();
                    return;
                }
            } catch (e) {}
        }

        if (window.jQuery && typeof $(el).modal === 'function') {
            try {
                $(el).modal('hide');
                return;
            } catch (e) {}
        }

        el.classList.remove('show');
        el.style.display = 'none';
        el.setAttribute('aria-hidden', 'true');
        el.removeAttribute('aria-modal');
        document.body.classList.remove('modal-open');
        document.querySelectorAll('.modal-backdrop, .custom-modal-backdrop').forEach(b => b.remove());
    };

    // Profil Utilitas Terdaftar dari Database (PLN, Telkom, Internet)
    const UTILITY_PROFILES = @json($utilityProfilesFormatted ?? []);

    // Helper deteksi kategori kegiatan utilitas
    function detectUtilityCategory(nama, kode) {
        nama = (nama || '').toLowerCase();
        kode = (kode || '').toUpperCase();

        if (kode === 'PLN' || nama.includes('listrik') || nama.includes('pln')) {
            return 'listrik';
        }
        if (kode === 'TLP' || nama.includes('telepon') || nama.includes('telkom') || nama.includes('speedy') || nama.includes('indihome')) {
            return 'telepon';
        }
        if (kode === 'INET' || nama.includes('internet') || nama.includes('wifi') || nama.includes('bandwidth')) {
            return 'internet';
        }
        return null;
    }

    function getProfilesForCategory(cat) {
        if (cat === 'listrik') {
            return UTILITY_PROFILES.filter(p => p.category === 'listrik_pasca' || p.category === 'listrik_pra');
        }
        if (cat === 'telepon') {
            return UTILITY_PROFILES.filter(p => p.category === 'telkom_speedy');
        }
        if (cat === 'internet') {
            return UTILITY_PROFILES.filter(p => p.category === 'internet');
        }
        return [];
    }

    function handleKegiatanSekolahUtility(mode, ksNama, ksKode, targetProfileId = null, overwriteText = true) {
        const cat = detectUtilityCategory(ksNama, ksKode);
        const box = document.getElementById(mode + 'UtilityBox');
        const select = document.getElementById(mode + 'UtilitySelect');
        const pills = document.getElementById(mode + 'UtilityPills');
        const title = document.getElementById(mode + 'UtilityTitle');

        if (!box || !select || !pills) return;

        if (cat) {
            const profiles = getProfilesForCategory(cat);
            if (profiles.length > 0) {
                box.style.display = 'block';

                if (title) {
                    if (cat === 'listrik') title.innerHTML = '<i class="fas fa-bolt text-warning mr-1"></i> Akun Listrik PLN Terdaftar';
                    else if (cat === 'telepon') title.innerHTML = '<i class="fas fa-phone text-info mr-1"></i> Akun Telkom / Speedy Terdaftar';
                    else if (cat === 'internet') title.innerHTML = '<i class="fas fa-wifi text-primary mr-1"></i> Layanan Internet Terdaftar';
                }

                // Build options
                let optHtml = '<option value="">-- Pilih Akun Utilitas --</option>';
                profiles.forEach(p => {
                    optHtml += `<option value="${p.id}" ${targetProfileId == p.id ? 'selected' : ''}>${p.name} (${p.short_label})</option>`;
                });
                select.innerHTML = optHtml;

                // Build pills
                let pillsHtml = '';
                profiles.forEach(p => {
                    const isActive = (targetProfileId == p.id);
                    pillsHtml += `
                        <button type="button" class="btn-utility-pill ${isActive ? 'active' : ''}" 
                                data-mode="${mode}" data-id="${p.id}">
                            <i class="fas fa-check-circle ${isActive ? '' : 'd-none'}"></i>${p.short_label}
                        </button>
                    `;
                });
                pills.innerHTML = pillsHtml;

                // Re-bind pill clicks
                pills.querySelectorAll('.btn-utility-pill').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        applyUtilityProfile(this.dataset.mode, this.dataset.id, true);
                    });
                });

                if (targetProfileId) {
                    applyUtilityProfile(mode, targetProfileId, overwriteText);
                } else if (profiles.length === 1 && mode === 'add') {
                    applyUtilityProfile(mode, profiles[0].id, true);
                }
                return;
            }
        }

        // Bukan kegiatan utilitas
        box.style.display = 'none';
        if (!targetProfileId) {
            clearUtilitySelection(mode);
        }
    }

    function updateActiveKsPills(mode, ksId) {
        const selector = mode === 'add' ? '.btn-quick-ks' : '.btn-quick-ks-edit';
        const targetId = ksId ? String(ksId) : '';
        document.querySelectorAll(selector).forEach(btn => {
            const btnId = btn.dataset.id ? String(btn.dataset.id) : '';
            if (btnId === targetId) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
    }

    function updateSatuanLabels(mode, satuan) {
        const s = satuan ? `/ ${satuan}` : '/ Satuan';
        if (mode === 'add') {
            const l1 = document.getElementById('labelSatuanAdd');
            const l2 = document.getElementById('labelSatuanMax');
            if (l1) l1.textContent = s;
            if (l2) l2.textContent = s;
        } else if (mode === 'edit') {
            const l1 = document.getElementById('labelSatuanEdit');
            const l2 = document.getElementById('labelEditSatuanMax');
            if (l1) l1.textContent = s;
            if (l2) l2.textContent = s;
        }
    }

    function checkHargaMaxValidation(mode) {
        const hargaInput = document.getElementById(mode === 'add' ? 'addHargaInput' : 'editHargaInput');
        const maxInput = document.getElementById(mode === 'add' ? 'displayHargaMax' : 'displayEditHargaMax');
        const statusEl = document.getElementById(mode === 'add' ? 'statusHargaSatuanAdd' : 'statusHargaSatuanEdit');
        if (!hargaInput || !maxInput || !statusEl) return;

        const hargaVal = parseThousands(hargaInput.value);
        const maxText = maxInput.value ? maxInput.value.trim() : '';

        if (maxText === 'Fleksibel' || maxText.toLowerCase().includes('tagihan')) {
            statusEl.innerHTML = '<span class="text-muted" style="font-size: 0.70rem;"><i class="fas fa-bolt text-warning mr-0.5"></i>Tagihan utilitas</span>';
            hargaInput.classList.remove('is-invalid-harga');
            return;
        }

        const maxVal = parseThousands(maxText);
        if (maxVal > 0 && hargaVal > 0) {
            if (hargaVal > maxVal) {
                statusEl.innerHTML = '<span class="text-danger font-weight-bold" style="font-size: 0.70rem;"><i class="fas fa-exclamation-triangle mr-0.5"></i>Melebihi ARKAS!</span>';
                hargaInput.classList.add('is-invalid-harga');
            } else {
                statusEl.innerHTML = '<span class="text-success" style="font-size: 0.70rem;"><i class="fas fa-check-circle mr-0.5"></i>Sesuai standar</span>';
                hargaInput.classList.remove('is-invalid-harga');
            }
        } else {
            statusEl.innerHTML = '';
            hargaInput.classList.remove('is-invalid-harga');
        }
    }

    function applyUtilityProfile(mode, profileId, overwriteText = true) {
        const profile = UTILITY_PROFILES.find(p => p.id == profileId);
        if (!profile) return;

        const select = document.getElementById(mode + 'UtilitySelect');
        const hiddenId = document.getElementById(mode + 'UtilityProfileId');
        const badge = document.getElementById(mode + 'BadgeUtility');
        const info = document.getElementById(mode + 'UtilityInfo');
        const pills = document.getElementById(mode + 'UtilityPills');

        if (select) select.value = profileId;
        if (hiddenId) hiddenId.value = profileId;

        if (overwriteText) {
            if (mode === 'add') {
                const addUraian = document.getElementById('addUraianInput');
                if (addUraian) addUraian.value = profile.name;
                const addSpek = document.getElementById('addSpesifikasi');
                if (addSpek) addSpek.value = profile.formatted_spec;
            } else if (mode === 'edit') {
                const editUraian = document.getElementById('editNamaBarang');
                if (editUraian) editUraian.value = profile.name;
                const editSpek = document.getElementById('editSpesifikasi');
                if (editSpek) editSpek.value = profile.formatted_spec;
            }
        }

        if (badge) badge.style.display = 'inline-block';

        if (info) {
            info.style.display = 'block';
            info.innerHTML = `
                <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap: 4px;">
                    <div>
                        <span class="badge badge-warning text-dark mr-1"><i class="fas fa-check-circle mr-0.5"></i> Terhubung:</span>
                        <strong class="text-dark">${profile.name}</strong>
                        <div class="text-muted mt-0.5" style="font-size: 0.72rem;">${profile.formatted_spec}</div>
                    </div>
                    <span class="badge badge-secondary" style="font-size: 0.7rem;">${profile.provider || 'Utilitas'}</span>
                </div>
            `;
        }

        // Set Satuan kartu bulan menjadi 'Bulan' jika saat ini 'Unit' atau default
        if (mode === 'add') {
            rkasMonthCards.forEach(c => {
                if (!c.unit || c.unit === 'Unit') c.unit = 'Bulan';
            });
            renderRkasMonthCards();
        } else if (mode === 'edit') {
            rkasEditMonthCards.forEach(c => {
                if (!c.unit || c.unit === 'Unit') c.unit = 'Bulan';
            });
            renderEditMonthCards();
        }

        // Set satuan label dan status harga utilitas
        updateSatuanLabels(mode, 'Bulan');
        if (mode === 'add') {
            const maxEl = document.getElementById('displayHargaMax');
            if (maxEl) maxEl.value = 'Fleksibel';
            const infoEl = document.getElementById('labelRefRangeInfo');
            if (infoEl) infoEl.textContent = 'Tagihan pemakaian riil';
        } else {
            const maxEl = document.getElementById('displayEditHargaMax');
            if (maxEl) maxEl.value = 'Fleksibel';
            const infoEl = document.getElementById('labelEditRefRangeInfo');
            if (infoEl) infoEl.textContent = 'Tagihan pemakaian riil';
        }
        checkHargaMaxValidation(mode);

        // Set Guru Pemohon jika profil memiliki default_guru_id
        if (profile.default_guru_id) {
            const guruSelect = document.getElementById(mode === 'add' ? 'addGuruId' : 'editGuruId');
            if (guruSelect && (!guruSelect.value || overwriteText)) {
                guruSelect.value = profile.default_guru_id;
            }
        }

        // Highlight pill aktif
        if (pills) {
            pills.querySelectorAll('.btn-utility-pill').forEach(btn => {
                const isMatch = (btn.dataset.id == profileId);
                if (isMatch) {
                    btn.classList.add('active');
                    const icon = btn.querySelector('i');
                    if (icon) icon.classList.remove('d-none');
                } else {
                    btn.classList.remove('active');
                    const icon = btn.querySelector('i');
                    if (icon) icon.classList.add('d-none');
                }
            });
        }
    }

    function clearUtilitySelection(mode) {
        const select = document.getElementById(mode + 'UtilitySelect');
        if (select) select.value = '';
        const hiddenId = document.getElementById(mode + 'UtilityProfileId');
        if (hiddenId) hiddenId.value = '';
        const badge = document.getElementById(mode + 'BadgeUtility');
        if (badge) badge.style.display = 'none';
        const info = document.getElementById(mode + 'UtilityInfo');
        if (info) {
            info.style.display = 'none';
            info.innerHTML = '';
        }
        const pills = document.getElementById(mode + 'UtilityPills');
        if (pills) {
            pills.querySelectorAll('.btn-utility-pill').forEach(btn => {
                btn.classList.remove('active');
                const icon = btn.querySelector('i');
                if (icon) icon.classList.add('d-none');
            });
        }

        if (mode === 'add') {
            const maxEl = document.getElementById('displayHargaMax');
            if (maxEl) maxEl.value = '-';
            const infoEl = document.getElementById('labelRefRangeInfo');
            if (infoEl) infoEl.textContent = 'Batas standar ARKAS';
            updateSatuanLabels('add', rkasMonthCards[0]?.unit || 'Satuan');
        } else {
            const maxEl = document.getElementById('displayEditHargaMax');
            if (maxEl) maxEl.value = '-';
            const infoEl = document.getElementById('labelEditRefRangeInfo');
            if (infoEl) infoEl.textContent = 'Batas standar ARKAS';
            updateSatuanLabels('edit', rkasEditMonthCards[0]?.unit || 'Satuan');
        }
        checkHargaMaxValidation(mode);
    }

    function onUtilitySelectChange(mode, val) {
        if (val) {
            applyUtilityProfile(mode, val, true);
        } else {
            clearUtilitySelection(mode);
        }
    }

    // Helper format & unformat ribuan
    function formatThousands(val) {
        if (!val && val !== 0) return '';
        let str = val.toString().replace(/[^0-9]/g, '');
        if (!str) return '';
        return parseInt(str, 10).toLocaleString('id-ID');
    }

    function parseThousands(val) {
        if (!val) return 0;
        let str = val.toString().replace(/[^0-9]/g, '');
        return str ? parseFloat(str) : 0;
    }

    function attachThousandSeparator(input) {
        input.addEventListener('input', function() {
            let originalValue = this.value;
            let originalSelection = this.selectionStart;
            let originalLength = originalValue.length;

            let rawVal = originalValue.replace(/[^0-9]/g, '');
            let formatted = rawVal ? parseInt(rawVal, 10).toLocaleString('id-ID') : '';

            this.value = formatted;

            let newLength = formatted.length;
            let newSelection = originalSelection + (newLength - originalLength);
            if (newSelection >= 0 && newSelection <= newLength) {
                this.setSelectionRange(newSelection, newSelection);
            }
        });
    }

    const ALL_MONTHS = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    // State dynamic month cards untuk Tambah RKAS
    let rkasMonthCards = [
        { id: 1, month: 'Januari', volume: 1, unit: 'Unit' }
    ];
    let nextRkasCardId = 2;

    function renderRkasMonthCards() {
        const container = document.getElementById('monthCardsContainer');
        if (!container) return;
        container.innerHTML = '';

        const hargaSatuan = parseThousands(document.getElementById('addHargaInput') ? document.getElementById('addHargaInput').value : 0);
        let grandTotal = 0;

        rkasMonthCards.forEach((card, index) => {
            const subtotal = (card.volume || 0) * hargaSatuan;
            grandTotal += subtotal;

            const col = document.createElement('div');
            col.className = 'col-12 col-sm-6 col-md-4 px-1';
            col.innerHTML = `
                <div class="card-bulan">
                    <div class="card-bulan-header">
                        <div class="d-flex align-items-center" style="gap: 3px; min-width: 0;">
                            <span class="badge badge-secondary rounded-circle flex-shrink-0" style="width: 16px; height: 16px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.6rem;">${index + 1}</span>
                            <select class="select-bulan flex-shrink-0" onchange="updateCardMonth(${card.id}, this.value)">
                                ${ALL_MONTHS.map(m => `<option value="${m}" ${m === card.month ? 'selected' : ''}>${m}</option>`).join('')}
                            </select>
                        </div>
                        <div class="d-flex align-items-center flex-shrink-0" style="gap: 3px;">
                            <span class="card-bulan-subtotal" id="subtotalCard_${card.id}">Rp ${formatThousands(subtotal)}</span>
                            ${rkasMonthCards.length > 1 ? `
                                <button type="button" class="btn-remove-card" title="Hapus bulan ini" onclick="removeMonthCard(${card.id})">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            ` : ''}
                        </div>
                    </div>
                    <div class="card-bulan-body">
                        <div class="row" style="margin-left: -3px; margin-right: -3px;">
                            <div class="col-6 px-1">
                                <label class="text-muted small mb-0.5" style="font-size: 0.7rem;">Volume :</label>
                                <input type="number" step="any" min="0.01" class="form-control form-control-sm text-center font-weight-bold" 
                                       style="height: 25px; padding: 2px 4px; font-size: 0.78rem;"
                                       value="${card.volume}" oninput="updateCardVolume(${card.id}, this.value)">
                            </div>
                            <div class="col-6 px-1">
                                <label class="text-muted small mb-0.5" style="font-size: 0.7rem;">Satuan :</label>
                                <input type="text" class="form-control form-control-sm" 
                                       style="height: 25px; padding: 2px 4px; font-size: 0.78rem;"
                                       value="${card.unit || 'Unit'}" oninput="updateCardUnit(${card.id}, this.value)">
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(col);
        });

        const badgeCount = document.getElementById('badgeBulanCount');
        if (badgeCount) badgeCount.textContent = `${rkasMonthCards.length} Bulan`;

        const displayTotal = document.getElementById('displayTotalAnggaran');
        if (displayTotal) displayTotal.textContent = `Rp ${formatThousands(grandTotal)}`;

        // Sync hidden inputs
        const jsonInput = document.getElementById('addMonthsJson');
        if (jsonInput) jsonInput.value = JSON.stringify(rkasMonthCards);

        const hidSatuan = document.getElementById('addHiddenSatuan');
        if (hidSatuan && rkasMonthCards.length > 0) hidSatuan.value = rkasMonthCards[0].unit || 'Unit';

        const hidVol = document.getElementById('addHiddenVolume');
        if (hidVol && rkasMonthCards.length > 0) {
            const totalVol = rkasMonthCards.reduce((acc, c) => acc + (parseFloat(c.volume) || 0), 0);
            hidVol.value = totalVol;
        }

        if (rkasMonthCards.length > 0) {
            updateSatuanLabels('add', rkasMonthCards[0].unit);
        }
        checkHargaMaxValidation('add');
    }

    function addNewMonthCard() {
        let lastMonth = rkasMonthCards.length > 0 ? rkasMonthCards[rkasMonthCards.length - 1].month : 'Januari';
        let lastIndex = ALL_MONTHS.indexOf(lastMonth);
        let nextMonth = ALL_MONTHS[(lastIndex + 1) % 12];
        let defaultUnit = rkasMonthCards.length > 0 ? rkasMonthCards[0].unit : 'Unit';
        let defaultVol = rkasMonthCards.length > 0 ? rkasMonthCards[rkasMonthCards.length - 1].volume : 1;

        rkasMonthCards.push({
            id: nextRkasCardId++,
            month: nextMonth,
            volume: defaultVol,
            unit: defaultUnit
        });
        renderRkasMonthCards();
    }

    function removeMonthCard(id) {
        if (rkasMonthCards.length <= 1) return;
        rkasMonthCards = rkasMonthCards.filter(c => c.id !== id);
        renderRkasMonthCards();
    }

    function updateCardMonth(id, val) {
        const c = rkasMonthCards.find(item => item.id === id);
        if (c) {
            c.month = val;
            const jsonInput = document.getElementById('addMonthsJson');
            if (jsonInput) jsonInput.value = JSON.stringify(rkasMonthCards);
        }
    }

    function updateCardVolume(id, val) {
        const c = rkasMonthCards.find(item => item.id === id);
        if (c) {
            c.volume = parseFloat(val) || 0;
            recalcAddModal();
        }
    }

    function updateCardUnit(id, val) {
        const c = rkasMonthCards.find(item => item.id === id);
        if (c) {
            c.unit = val;
            const jsonInput = document.getElementById('addMonthsJson');
            if (jsonInput) jsonInput.value = JSON.stringify(rkasMonthCards);
            updateSatuanLabels('add', val);
            checkHargaMaxValidation('add');
        }
    }

    function quickSetMonths(mode) {
        const defaultUnit = rkasMonthCards.length > 0 ? rkasMonthCards[0].unit : 'Unit';
        const defaultVol = rkasMonthCards.length > 0 ? rkasMonthCards[0].volume : 1;
        let selected = [];

        if (mode === 'all') selected = ALL_MONTHS;
        else if (mode === 'sem1') selected = ALL_MONTHS.slice(0, 6);
        else if (mode === 'sem2') selected = ALL_MONTHS.slice(6, 12);

        rkasMonthCards = selected.map((m) => ({
            id: nextRkasCardId++,
            month: m,
            volume: defaultVol,
            unit: defaultUnit
        }));
        renderRkasMonthCards();
    }

    function recalcAddModal() {
        const hargaSatuan = parseThousands(document.getElementById('addHargaInput') ? document.getElementById('addHargaInput').value : 0);
        let grandTotal = 0;

        rkasMonthCards.forEach(card => {
            const subtotal = (card.volume || 0) * hargaSatuan;
            grandTotal += subtotal;
            const subEl = document.getElementById(`subtotalCard_${card.id}`);
            if (subEl) subEl.textContent = `Rp ${formatThousands(subtotal)}`;
        });

        const displayTotal = document.getElementById('displayTotalAnggaran');
        if (displayTotal) displayTotal.textContent = `Rp ${formatThousands(grandTotal)}`;

        const jsonInput = document.getElementById('addMonthsJson');
        if (jsonInput) jsonInput.value = JSON.stringify(rkasMonthCards);

        const hidSatuan = document.getElementById('addHiddenSatuan');
        if (hidSatuan && rkasMonthCards.length > 0) hidSatuan.value = rkasMonthCards[0].unit || 'Unit';

        const hidVol = document.getElementById('addHiddenVolume');
        if (hidVol && rkasMonthCards.length > 0) {
            const totalVol = rkasMonthCards.reduce((acc, c) => acc + (parseFloat(c.volume) || 0), 0);
            hidVol.value = totalVol;
        }

        checkHargaMaxValidation('add');
    }

    // Autocomplete Debounce Helper
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    // 1. Autocomplete Kegiatan
    const inputKegiatan = document.getElementById('addKegiatanInput');
    const dropdownKegiatan = document.getElementById('dropdownAddKegiatan');

    if (inputKegiatan && dropdownKegiatan) {
        inputKegiatan.addEventListener('input', debounce(function() {
            const val = this.value.trim();
            if (val.length < 2) {
                dropdownKegiatan.classList.remove('show');
                return;
            }

            fetch(`{{ route('bos.rkas.search-kegiatan') }}?q=${encodeURIComponent(val)}`)
                .then(res => res.json())
                .then(data => {
                    if (!data || data.length === 0) {
                        dropdownKegiatan.innerHTML = `
                            <div class="p-2 text-muted small d-flex justify-content-between align-items-center" style="cursor: pointer;" onclick="this.closest('.autocomplete-dropdown').classList.remove('show')">
                                <div>
                                    <i class="fas fa-info-circle mr-1 text-secondary"></i>
                                    Tidak ditemukan di referensi ARKAS. Anda dapat tetap menggunakan isian manual.
                                </div>
                                <span class="badge badge-light border text-muted ml-2 flex-shrink-0" style="font-size: 0.68rem; padding: 2px 6px;">Tutup &times;</span>
                            </div>
                        `;
                    } else {
                        dropdownKegiatan.innerHTML = data.map(k => `
                            <div class="autocomplete-item select-kegiatan-item" data-text="${k.kode_kegiatan} - ${k.kegiatan}">
                                <div class="font-weight-bold text-dark">
                                    <span class="badge badge-primary mr-1" style="font-size: 0.72rem;">${k.kode_kegiatan}</span>
                                    <span>${k.kegiatan}</span>
                                </div>
                                ${k.komponen ? `<div class="text-xs text-muted mt-1"><i class="fas fa-layer-group mr-1"></i>${k.komponen}</div>` : ''}
                            </div>
                        `).join('');

                        dropdownKegiatan.querySelectorAll('.select-kegiatan-item').forEach(el => {
                            const selectKegiatan = function(e) {
                                if (e) {
                                    e.preventDefault();
                                    e.stopPropagation();
                                }
                                inputKegiatan.value = el.dataset.text;
                                dropdownKegiatan.classList.remove('show');
                            };
                            el.addEventListener('mousedown', selectKegiatan);
                            el.addEventListener('click', selectKegiatan);
                        });
                    }
                    dropdownKegiatan.classList.add('show');
                })
                .catch(err => console.error('Error fetching kegiatan:', err));
        }, 250));
    }

    // 2. Autocomplete Uraian Barang
    const inputUraian = document.getElementById('addUraianInput');
    const dropdownUraian = document.getElementById('dropdownAddUraian');
    const badgeRefStatus = document.getElementById('addBadgeRef');
    const displayHargaMax = document.getElementById('displayHargaMax');
    const labelSatuanMax = document.getElementById('labelSatuanMax');
    const labelRefRangeInfo = document.getElementById('labelRefRangeInfo');

    if (inputUraian && dropdownUraian) {
        inputUraian.addEventListener('input', debounce(function() {
            const val = this.value.trim();
            if (badgeRefStatus) badgeRefStatus.style.display = 'none';

            if (val.length < 2) {
                dropdownUraian.classList.remove('show');
                return;
            }

            const q = val.toLowerCase();
            const matchedUtilities = UTILITY_PROFILES.filter(p => 
                p.name.toLowerCase().includes(q) || 
                (p.customer_name && p.customer_name.toLowerCase().includes(q)) ||
                (p.idpel && p.idpel.includes(q)) ||
                (p.customer_number && p.customer_number.includes(q)) ||
                (p.category && p.category.toLowerCase().includes(q))
            );

            fetch(`{{ route('bos.rkas.search-barang') }}?q=${encodeURIComponent(val)}`)
                .then(res => res.json())
                .then(data => {
                    const items = Array.isArray(data) ? data : (data.items || []);
                    const total = (data && typeof data.total === 'number') ? data.total : items.length;

                    let utilHtml = '';
                    if (matchedUtilities.length > 0) {
                        utilHtml = `
                            <div class="px-2 py-1 text-dark font-weight-bold border-bottom" style="font-size: 0.7rem; background-color: #fffbeb;">
                                <i class="fas fa-bolt text-warning mr-1"></i> Akun Utilitas Terdaftar (${matchedUtilities.length})
                            </div>
                        ` + matchedUtilities.map(p => `
                            <div class="autocomplete-item select-utility-item bg-white border-bottom" data-id="${p.id}" data-mode="add">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="font-weight-bold text-dark">
                                        <span class="badge badge-warning text-dark mr-1" style="font-size: 0.68rem;"><i class="fas fa-bolt mr-0.5"></i> Utilitas</span>
                                        <span>${p.name}</span>
                                    </div>
                                    <span class="badge badge-secondary" style="font-size: 0.65rem;">${p.provider || 'Utilitas'}</span>
                                </div>
                                <div class="text-xs text-muted mt-0.5">${p.formatted_spec}</div>
                            </div>
                        `).join('');
                    }

                    if ((!items || items.length === 0) && matchedUtilities.length === 0) {
                        dropdownUraian.innerHTML = `
                            <div class="p-2 text-muted small d-flex justify-content-between align-items-center" style="cursor: pointer;" onclick="this.closest('.autocomplete-dropdown').classList.remove('show')">
                                <div>
                                    <i class="fas fa-info-circle mr-1 text-secondary"></i>
                                    Tidak ada di referensi ARKAS atau utilitas. Anda dapat menggunakan isian manual bebas.
                                </div>
                                <span class="badge badge-light border text-muted ml-2 flex-shrink-0" style="font-size: 0.68rem; padding: 2px 6px;">Tutup &times;</span>
                            </div>
                        `;
                    } else {
                        let html = items.map(b => `
                            <div class="autocomplete-item select-barang-item" 
                                 data-nama="${b.nama_barang}" 
                                 data-satuan="${b.satuan || 'Unit'}" 
                                 data-harga="${b.harga_max || 0}" 
                                 data-rekening="${b.kode_rekening || ''}">
                                <div class="font-weight-bold text-dark">${b.nama_barang}</div>
                                <div class="d-flex justify-content-between text-muted mt-0.5" style="font-size: 0.72rem;">
                                    <span class="text-success font-weight-bold"><i class="fas fa-tag mr-1"></i>${b.harga_range || ('Rp ' + formatThousands(b.harga_max))}</span>
                                    <span class="text-truncate" style="max-width: 250px;">${b.kode_rekening || '-'}</span>
                                </div>
                            </div>
                        `).join('');

                        if (total > items.length) {
                            html += `
                                <div class="p-2 bg-light border-top text-center text-muted" style="font-size: 0.72rem;">
                                    <i class="fas fa-info-circle text-primary mr-1"></i> Menampilkan <strong>${items.length}</strong> dari <strong>${total}</strong> barang yang cocok.<br>
                                    <span class="text-secondary">Ketik lebih spesifik (misal: <em>kertas hvs</em> atau <em>kertas f4</em>) untuk mempersempit.</span>
                                </div>
                            `;
                        }

                        dropdownUraian.innerHTML = utilHtml + html;

                        dropdownUraian.querySelectorAll('.select-utility-item').forEach(el => {
                            const selectUtil = function(e) {
                                if (e) {
                                    e.preventDefault();
                                    e.stopPropagation();
                                }
                                const upId = el.dataset.id;
                                const p = UTILITY_PROFILES.find(item => item.id == upId);
                                if (p) {
                                    let matchedKsKode = null;
                                    if (p.category.includes('listrik')) matchedKsKode = 'PLN';
                                    else if (p.category.includes('telkom')) matchedKsKode = 'TLP';
                                    else if (p.category.includes('internet')) matchedKsKode = 'INET';

                                    if (matchedKsKode) {
                                        const ksBtn = document.querySelector(`.btn-quick-ks[data-kode="${matchedKsKode}"]`);
                                        if (ksBtn) {
                                            const hiddenKsId = document.getElementById('addKegiatanSekolahId');
                                            if (hiddenKsId) hiddenKsId.value = ksBtn.dataset.id || '';
                                            const hiddenKsNama = document.getElementById('addKegiatanSekolahNama');
                                            if (hiddenKsNama) hiddenKsNama.value = ksBtn.dataset.nama || '';
                                            const inputKeg = document.getElementById('addKegiatanInput');
                                            if (inputKeg && ksBtn.dataset.arkas) inputKeg.value = ksBtn.dataset.arkas;
                                            updateActiveKsPills('add', ksBtn.dataset.id);
                                        }
                                    }
                                    handleKegiatanSekolahUtility('add', p.category, matchedKsKode, p.id, true);
                                }
                                dropdownUraian.classList.remove('show');
                            };
                            el.addEventListener('mousedown', selectUtil);
                            el.addEventListener('click', selectUtil);
                        });

                        dropdownUraian.querySelectorAll('.select-barang-item').forEach(el => {
                            const selectBarang = function(e) {
                                if (e) {
                                    e.preventDefault();
                                    e.stopPropagation();
                                }
                                inputUraian.value = el.dataset.nama;
                                const satuan = el.dataset.satuan;
                                const harga = parseFloat(el.dataset.harga) || 0;
                                const rekening = el.dataset.rekening;

                                const hargaInput = document.getElementById('addHargaInput');
                                if (hargaInput) hargaInput.value = formatThousands(harga);

                                const hiddenRekening = document.getElementById('addRekeningInput');
                                if (hiddenRekening) hiddenRekening.value = rekening;

                                if (displayHargaMax) displayHargaMax.value = formatThousands(harga);
                                updateSatuanLabels('add', satuan);
                                if (labelRefRangeInfo) labelRefRangeInfo.textContent = 'Batas standar ARKAS';
                                checkHargaMaxValidation('add');

                                if (badgeRefStatus) badgeRefStatus.style.display = 'inline-block';

                                // Update unit across all cards
                                rkasMonthCards.forEach(c => c.unit = satuan);

                                dropdownUraian.classList.remove('show');
                                renderRkasMonthCards();
                            };
                            el.addEventListener('mousedown', selectBarang);
                            el.addEventListener('click', selectBarang);
                        });
                    }
                    dropdownUraian.classList.add('show');
                })
                .catch(err => console.error('Error fetching barang:', err));
        }, 250));
    }

    // 3. Autocomplete Kegiatan Edit
    const inputEditKegiatan = document.getElementById('editKegiatanInput');
    const dropdownEditKegiatan = document.getElementById('dropdownEditKegiatan');
    if (inputEditKegiatan) {
        inputEditKegiatan.addEventListener('input', debounce(function() {
            const query = this.value.trim();
            if (query.length < 2) {
                dropdownEditKegiatan.classList.remove('show');
                return;
            }

            fetch(`{{ route('bos.rkas.search-kegiatan') }}?q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.length === 0) {
                        dropdownEditKegiatan.innerHTML = `
                            <div class="p-2 text-muted small d-flex justify-content-between align-items-center" style="cursor: pointer;" onclick="this.closest('.autocomplete-dropdown').classList.remove('show')">
                                <div>
                                    <i class="fas fa-info-circle mr-1 text-secondary"></i>
                                    Kegiatan tidak ditemukan di ARKAS. Anda dapat tetap menggunakan isian manual.
                                </div>
                                <span class="badge badge-light border text-muted ml-2 flex-shrink-0" style="font-size: 0.68rem; padding: 2px 6px;">Tutup &times;</span>
                            </div>
                        `;
                    } else {
                        let html = '';
                        data.forEach(item => {
                            html += `
                                <div class="autocomplete-item select-edit-kegiatan-item" 
                                     data-kegiatan="${item.kode} ${item.nama}">
                                    <div class="font-weight-bold text-dark" style="font-size: 0.8rem;">
                                        <span class="badge badge-arkas-ref mr-1">${item.kode}</span> ${item.nama}
                                    </div>
                                </div>
                            `;
                        });
                        dropdownEditKegiatan.innerHTML = html;

                        dropdownEditKegiatan.querySelectorAll('.select-edit-kegiatan-item').forEach(el => {
                            const selectEditKegiatan = function(e) {
                                if (e) {
                                    e.preventDefault();
                                    e.stopPropagation();
                                }
                                inputEditKegiatan.value = el.dataset.kegiatan;
                                dropdownEditKegiatan.classList.remove('show');
                            };
                            el.addEventListener('mousedown', selectEditKegiatan);
                            el.addEventListener('click', selectEditKegiatan);
                        });
                    }
                    dropdownEditKegiatan.classList.add('show');
                })
                .catch(err => console.error('Error fetching kegiatan edit:', err));
        }, 250));
    }

    // 4. Autocomplete Uraian Edit
    const inputEditUraian = document.getElementById('editNamaBarang');
    const dropdownEditUraian = document.getElementById('dropdownEditUraian');
    const badgeEditRefStatus = document.getElementById('editBadgeRef');
    const displayEditHargaMax = document.getElementById('displayEditHargaMax');
    const labelEditSatuanMax = document.getElementById('labelEditSatuanMax');

    if (inputEditUraian) {
        inputEditUraian.addEventListener('input', debounce(function() {
            const query = this.value.trim();
            if (query.length < 2) {
                dropdownEditUraian.classList.remove('show');
                if (badgeEditRefStatus) badgeEditRefStatus.style.display = 'none';
                return;
            }

            fetch(`{{ route('bos.rkas.search-barang') }}?q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    const matchedUtilities = searchUtilityProfilesLocally(query);
                    if (data.length === 0 && matchedUtilities.length === 0) {
                        dropdownEditUraian.innerHTML = `
                            <div class="p-2 text-muted small d-flex justify-content-between align-items-center" style="cursor: pointer;" onclick="this.closest('.autocomplete-dropdown').classList.remove('show')">
                                <div>
                                    <i class="fas fa-info-circle mr-1 text-secondary"></i>
                                    Barang tidak ditemukan di referensi ARKAS atau utilitas. Anda dapat menggunakan isian manual bebas.
                                </div>
                                <span class="badge badge-light border text-muted ml-2 flex-shrink-0" style="font-size: 0.68rem; padding: 2px 6px;">Tutup &times;</span>
                            </div>
                        `;
                    } else {
                        let html = '';

                        // Tampilkan hasil akun utilitas terdaftar jika cocok
                        if (matchedUtilities.length > 0) {
                            html += `<div class="p-1 px-2 font-weight-bold text-muted small bg-light" style="font-size: 0.7rem;"><i class="fas fa-bolt text-warning mr-1"></i>Akun Utilitas Terdaftar</div>`;
                            matchedUtilities.forEach(p => {
                                html += `
                                    <div class="autocomplete-item select-utility-item bg-white" 
                                         data-id="${p.id}"
                                         data-category="${p.category}"
                                         data-nama="${p.name}"
                                         data-spec="${p.formatted_spec}">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="font-weight-bold text-dark" style="font-size: 0.8rem;">
                                                <span class="badge badge-warning text-dark mr-1">${p.provider || 'Utilitas'}</span>
                                                ${p.name}
                                            </div>
                                            <span class="badge badge-info" style="font-size: 0.68rem;">Pilih Akun</span>
                                        </div>
                                        <div class="text-muted mt-0.5" style="font-size: 0.72rem;">${p.formatted_spec}</div>
                                    </div>
                                `;
                            });
                        }

                        // Tampilkan hasil katalog barang ARKAS
                        if (data.length > 0) {
                            if (matchedUtilities.length > 0) {
                                html += `<div class="p-1 px-2 font-weight-bold text-muted small bg-light mt-1" style="font-size: 0.7rem;"><i class="fas fa-box text-primary mr-1"></i>Referensi Barang ARKAS</div>`;
                            }
                            data.forEach(b => {
                                html += `
                                    <div class="autocomplete-item select-edit-barang-item" 
                                         data-nama="${b.nama_barang}"
                                         data-satuan="${b.satuan}"
                                         data-harga="${b.harga_max || 0}" 
                                         data-rekening="${b.kode_rekening || ''}">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="font-weight-bold text-dark" style="font-size: 0.8rem;">
                                                <span class="badge badge-arkas-ref mr-1">${b.satuan}</span>
                                                ${b.nama_barang}
                                            </div>
                                            <span class="text-success font-weight-bold"><i class="fas fa-tag mr-1"></i>${b.harga_range || ('Rp ' + formatThousands(b.harga_max))}</span>
                                        </div>
                                        ${b.kode_rekening ? `<div class="text-muted mt-0.5" style="font-size: 0.72rem;"><i class="fas fa-folder mr-1 text-secondary"></i>${b.kode_rekening}</div>` : ''}
                                    </div>
                                `;
                            });
                        }
                        dropdownEditUraian.innerHTML = html;

                        // Event binding untuk item utilitas
                        dropdownEditUraian.querySelectorAll('.select-utility-item').forEach(el => {
                            const selectEditUtil = function(e) {
                                if (e) {
                                    e.preventDefault();
                                    e.stopPropagation();
                                }
                                const p = UTILITY_PROFILES.find(item => item.id == el.dataset.id);
                                if (p) {
                                    let matchedKsKode = '';
                                    if (p.category.includes('listrik')) matchedKsKode = 'PLN';
                                    else if (p.category.includes('telkom')) matchedKsKode = 'TLP';
                                    else if (p.category.includes('internet')) matchedKsKode = 'INET';

                                    if (editKsSelect) {
                                        for (let opt of editKsSelect.options) {
                                            if (opt.dataset.kode === matchedKsKode) {
                                                editKsSelect.value = opt.value;
                                                if (editKsNama) editKsNama.value = opt.dataset.nama || '';
                                                if (opt.dataset.arkas && editKegiatanInput) editKegiatanInput.value = opt.dataset.arkas;
                                                break;
                                            }
                                        }
                                    }
                                    handleKegiatanSekolahUtility('edit', p.category, matchedKsKode, p.id, true);
                                }
                                dropdownEditUraian.classList.remove('show');
                            };
                            el.addEventListener('mousedown', selectEditUtil);
                            el.addEventListener('click', selectEditUtil);
                        });

                        dropdownEditUraian.querySelectorAll('.select-edit-barang-item').forEach(el => {
                            const selectEditBarang = function(e) {
                                if (e) {
                                    e.preventDefault();
                                    e.stopPropagation();
                                }
                                inputEditUraian.value = el.dataset.nama;
                                const satuan = el.dataset.satuan;
                                const harga = parseFloat(el.dataset.harga) || 0;
                                const rekening = el.dataset.rekening;

                                const hargaInput = document.getElementById('editHargaInput');
                                if (hargaInput) hargaInput.value = formatThousands(harga);

                                const hiddenRekening = document.getElementById('editRekeningInput');
                                if (hiddenRekening) hiddenRekening.value = rekening;

                                if (displayEditHargaMax) displayEditHargaMax.value = formatThousands(harga);
                                updateSatuanLabels('edit', satuan);
                                const editRefInfo = document.getElementById('labelEditRefRangeInfo');
                                if (editRefInfo) editRefInfo.textContent = 'Batas standar ARKAS';
                                checkHargaMaxValidation('edit');

                                if (badgeEditRefStatus) badgeEditRefStatus.style.display = 'inline-block';

                                // Update unit across all edit cards
                                rkasEditMonthCards.forEach(c => c.unit = satuan);

                                dropdownEditUraian.classList.remove('show');
                                renderEditMonthCards();
                            };
                            el.addEventListener('mousedown', selectEditBarang);
                            el.addEventListener('click', selectEditBarang);
                        });
                    }
                    dropdownEditUraian.classList.add('show');
                })
                .catch(err => console.error('Error fetching barang edit:', err));
        }, 250));
    }

    // Prevent input blur when clicking inside any autocomplete dropdown
    [dropdownKegiatan, dropdownUraian, dropdownEditKegiatan, dropdownEditUraian].forEach(dd => {
        if (dd) {
            dd.addEventListener('mousedown', function(e) {
                e.preventDefault();
            });
        }
    });

    // Helper to close dropdowns when interacting outside their respective wrapper
    function closeAutocompleteDropdowns(target) {
        const autocompletes = [
            { input: inputKegiatan, dropdown: dropdownKegiatan },
            { input: inputUraian, dropdown: dropdownUraian },
            { input: inputEditKegiatan, dropdown: dropdownEditKegiatan },
            { input: inputEditUraian, dropdown: dropdownEditUraian }
        ];
        autocompletes.forEach(item => {
            if (item.dropdown && item.dropdown.classList.contains('show')) {
                const wrapper = item.input ? item.input.closest('.autocomplete-wrapper') : null;
                if (target && (item.dropdown.contains(target) || (wrapper && wrapper.contains(target)))) {
                    return;
                }
                item.dropdown.classList.remove('show');
            }
        });
    }

    // Close autocomplete on click outside its specific wrapper
    document.addEventListener('click', function(e) {
        closeAutocompleteDropdowns(e.target);
    });

    // Close autocomplete when focus moves to another form control
    document.addEventListener('focusin', function(e) {
        if (e.target && e.target.matches('input, select, textarea, button')) {
            closeAutocompleteDropdowns(e.target);
        }
    });

    // Close autocomplete on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAutocompleteDropdowns(null);
        }
    });

    // Advance to next input and dismiss dropdown on Enter key
    [inputKegiatan, inputUraian, inputEditKegiatan, inputEditUraian].forEach(input => {
        if (!input) return;
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const wrapper = this.closest('.autocomplete-wrapper');
                const dd = wrapper ? wrapper.querySelector('.autocomplete-dropdown') : null;
                if (dd) {
                    dd.classList.remove('show');
                }
                const form = this.closest('form');
                if (form) {
                    const focusables = Array.from(form.querySelectorAll('input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])'));
                    const idx = focusables.indexOf(this);
                    if (idx > -1 && idx + 1 < focusables.length) {
                        focusables[idx + 1].focus();
                    }
                }
            }
        });
    });

    // State dynamic month cards untuk Ubah RKAS
    let rkasEditMonthCards = [];
    let nextRkasEditCardId = 1;

    function renderEditMonthCards() {
        const container = document.getElementById('editMonthCardsContainer');
        if (!container) return;
        container.innerHTML = '';

        const hargaSatuan = parseThousands(document.getElementById('editHargaInput') ? document.getElementById('editHargaInput').value : 0);
        let grandTotal = 0;

        rkasEditMonthCards.forEach((card, index) => {
            const subtotal = (card.volume || 0) * hargaSatuan;
            grandTotal += subtotal;

            const col = document.createElement('div');
            col.className = 'col-12 col-sm-6 col-md-4 px-1';
            col.innerHTML = `
                <div class="card-bulan">
                    <div class="card-bulan-header">
                        <div class="d-flex align-items-center" style="gap: 3px; min-width: 0;">
                            <span class="badge badge-secondary rounded-circle flex-shrink-0" style="width: 16px; height: 16px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.6rem;">${index + 1}</span>
                            <select class="select-bulan flex-shrink-0" onchange="updateEditCardMonth(${card.id}, this.value)">
                                ${ALL_MONTHS.map(m => `<option value="${m}" ${m === card.month ? 'selected' : ''}>${m}</option>`).join('')}
                            </select>
                        </div>
                        <div class="d-flex align-items-center flex-shrink-0" style="gap: 3px;">
                            <span class="card-bulan-subtotal" id="subtotalEditCard_${card.id}">Rp ${formatThousands(subtotal)}</span>
                            ${rkasEditMonthCards.length > 1 ? `
                                <button type="button" class="btn-remove-card" title="Hapus bulan ini" onclick="removeEditMonthCard(${card.id})">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            ` : ''}
                        </div>
                    </div>
                    <div class="card-bulan-body">
                        <div class="row" style="margin-left: -3px; margin-right: -3px;">
                            <div class="col-6 px-1">
                                <label class="text-muted small mb-0.5" style="font-size: 0.7rem;">Volume :</label>
                                <input type="number" step="any" min="0.01" class="form-control form-control-sm text-center font-weight-bold" 
                                       style="height: 25px; padding: 2px 4px; font-size: 0.78rem;"
                                       value="${card.volume}" oninput="updateEditCardVolume(${card.id}, this.value)">
                            </div>
                            <div class="col-6 px-1">
                                <label class="text-muted small mb-0.5" style="font-size: 0.7rem;">Satuan :</label>
                                <input type="text" class="form-control form-control-sm" 
                                       style="height: 25px; padding: 2px 4px; font-size: 0.78rem;"
                                       value="${card.unit || 'Unit'}" oninput="updateEditCardUnit(${card.id}, this.value)">
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(col);
        });

        const badgeCount = document.getElementById('badgeEditBulanCount');
        if (badgeCount) badgeCount.textContent = `${rkasEditMonthCards.length} Bulan`;

        const displayTotal = document.getElementById('displayEditTotalAnggaran');
        if (displayTotal) displayTotal.textContent = `Rp ${formatThousands(grandTotal)}`;

        // Sync hidden inputs
        const jsonInput = document.getElementById('editMonthsJson');
        if (jsonInput) {
            const payload = rkasEditMonthCards.map(c => ({
                id: c.origId || null,
                month: c.month,
                volume: c.volume,
                unit: c.unit
            }));
            jsonInput.value = JSON.stringify(payload);
        }

        const hidSatuan = document.getElementById('editHiddenSatuan');
        if (hidSatuan && rkasEditMonthCards.length > 0) hidSatuan.value = rkasEditMonthCards[0].unit || 'Unit';

        const hidVol = document.getElementById('editHiddenVolume');
        if (hidVol && rkasEditMonthCards.length > 0) {
            const totalVol = rkasEditMonthCards.reduce((acc, c) => acc + (parseFloat(c.volume) || 0), 0);
            hidVol.value = totalVol;
        }

        const hidBulan = document.getElementById('editHiddenBulanPakai');
        if (hidBulan && rkasEditMonthCards.length > 0) {
            hidBulan.value = rkasEditMonthCards[0].month || 'Januari';
        }

        if (rkasEditMonthCards.length > 0) {
            updateSatuanLabels('edit', rkasEditMonthCards[0].unit);
        }
        checkHargaMaxValidation('edit');
    }

    function addNewEditMonthCard() {
        let lastMonth = rkasEditMonthCards.length > 0 ? rkasEditMonthCards[rkasEditMonthCards.length - 1].month : 'Januari';
        let lastIndex = ALL_MONTHS.indexOf(lastMonth);
        let nextMonth = ALL_MONTHS[(lastIndex + 1) % 12];
        let defaultUnit = rkasEditMonthCards.length > 0 ? rkasEditMonthCards[0].unit : 'Unit';
        let defaultVol = rkasEditMonthCards.length > 0 ? rkasEditMonthCards[rkasEditMonthCards.length - 1].volume : 1;

        rkasEditMonthCards.push({
            id: nextRkasEditCardId++,
            origId: null,
            month: nextMonth,
            volume: defaultVol,
            unit: defaultUnit
        });
        renderEditMonthCards();
    }

    function removeEditMonthCard(id) {
        if (rkasEditMonthCards.length <= 1) return;
        rkasEditMonthCards = rkasEditMonthCards.filter(c => c.id !== id);
        renderEditMonthCards();
    }

    function updateEditCardMonth(id, val) {
        const c = rkasEditMonthCards.find(item => item.id === id);
        if (c) {
            c.month = val;
            const jsonInput = document.getElementById('editMonthsJson');
            if (jsonInput) {
                const payload = rkasEditMonthCards.map(c => ({
                    id: c.origId || null,
                    month: c.month,
                    volume: c.volume,
                    unit: c.unit
                }));
                jsonInput.value = JSON.stringify(payload);
            }
            const hidBulan = document.getElementById('editHiddenBulanPakai');
            if (hidBulan && rkasEditMonthCards.length > 0) {
                hidBulan.value = rkasEditMonthCards[0].month;
            }
        }
    }

    function updateEditCardVolume(id, val) {
        const c = rkasEditMonthCards.find(item => item.id === id);
        if (c) {
            c.volume = parseFloat(val) || 0;
            recalcEditModal();
        }
    }

    function updateEditCardUnit(id, val) {
        const c = rkasEditMonthCards.find(item => item.id === id);
        if (c) {
            c.unit = val;
            const jsonInput = document.getElementById('editMonthsJson');
            if (jsonInput) {
                const payload = rkasEditMonthCards.map(c => ({
                    id: c.origId || null,
                    month: c.month,
                    volume: c.volume,
                    unit: c.unit
                }));
                jsonInput.value = JSON.stringify(payload);
            }
            updateSatuanLabels('edit', val);
            checkHargaMaxValidation('edit');
        }
    }

    function quickSetEditMonths(mode) {
        const defaultUnit = rkasEditMonthCards.length > 0 ? rkasEditMonthCards[0].unit : 'Unit';
        const defaultVol = rkasEditMonthCards.length > 0 ? rkasEditMonthCards[0].volume : 1;
        let selected = [];

        if (mode === 'all') selected = ALL_MONTHS;
        else if (mode === 'sem1') selected = ALL_MONTHS.slice(0, 6);
        else if (mode === 'sem2') selected = ALL_MONTHS.slice(6, 12);

        rkasEditMonthCards = selected.map((m) => ({
            id: nextRkasEditCardId++,
            origId: null,
            month: m,
            volume: defaultVol,
            unit: defaultUnit
        }));
        renderEditMonthCards();
    }

    function recalcEditModal() {
        const hargaSatuan = parseThousands(document.getElementById('editHargaInput') ? document.getElementById('editHargaInput').value : 0);
        let grandTotal = 0;

        rkasEditMonthCards.forEach(card => {
            const subtotal = (card.volume || 0) * hargaSatuan;
            grandTotal += subtotal;
            const subEl = document.getElementById(`subtotalEditCard_${card.id}`);
            if (subEl) subEl.textContent = `Rp ${formatThousands(subtotal)}`;
        });

        const displayTotal = document.getElementById('displayEditTotalAnggaran');
        if (displayTotal) displayTotal.textContent = `Rp ${formatThousands(grandTotal)}`;

        const jsonInput = document.getElementById('editMonthsJson');
        if (jsonInput) {
            const payload = rkasEditMonthCards.map(c => ({
                id: c.origId || null,
                month: c.month,
                volume: c.volume,
                unit: c.unit
            }));
            jsonInput.value = JSON.stringify(payload);
        }

        const hidSatuan = document.getElementById('editHiddenSatuan');
        if (hidSatuan && rkasEditMonthCards.length > 0) hidSatuan.value = rkasEditMonthCards[0].unit || 'Unit';

        const hidVol = document.getElementById('editHiddenVolume');
        if (hidVol && rkasEditMonthCards.length > 0) {
            const totalVol = rkasEditMonthCards.reduce((acc, c) => acc + (parseFloat(c.volume) || 0), 0);
            hidVol.value = totalVol;
        }

        checkHargaMaxValidation('edit');
    }

    document.addEventListener('DOMContentLoaded', function() {
        renderRkasMonthCards();

        // Pasang thousand separator pada setiap input nominal
        document.querySelectorAll('.thousand-separator-input').forEach(function(inp) {
            attachThousandSeparator(inp);
        });

        // Unformat thousand separator saat submit form & pastikan months_json terisi
        document.querySelectorAll('#formAddItem, #formEditItem, #formPagu').forEach(function(form) {
            form.addEventListener('submit', function() {
                if (this.id === 'formAddItem') {
                    const jsonInput = document.getElementById('addMonthsJson');
                    if (jsonInput) jsonInput.value = JSON.stringify(rkasMonthCards);
                }
                if (this.id === 'formEditItem') {
                    const jsonInput = document.getElementById('editMonthsJson');
                    if (jsonInput) {
                        const payload = rkasEditMonthCards.map(c => ({
                            id: c.origId || null,
                            month: c.month,
                            volume: c.volume,
                            unit: c.unit
                        }));
                        jsonInput.value = JSON.stringify(payload);
                    }
                }
                this.querySelectorAll('.thousand-separator-input').forEach(function(inp) {
                    inp.value = inp.value.replace(/\./g, '');
                });
            });
        });

        // Auto-mapping Kegiatan Sekolah -> Kegiatan ARKAS pada Modal Tambah (Badges)
        const addKsId = document.getElementById('addKegiatanSekolahId');
        const addKsNama = document.getElementById('addKegiatanSekolahNama');
        const inputKegiatan = document.getElementById('addKegiatanInput');

        // Quick Preset Pills Kegiatan Sekolah Tambah
        document.querySelectorAll('.btn-quick-ks').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const id = this.dataset.id || '';
                const nama = this.dataset.nama || '';
                const kode = this.dataset.kode || '';
                const arkas = this.dataset.arkas || '';
                if (addKsId) addKsId.value = id;
                if (addKsNama) addKsNama.value = nama;

                const addRek = document.getElementById('addRekeningInput');

                if (id && arkas) {
                    if (inputKegiatan) inputKegiatan.value = arkas;
                    handleKegiatanSekolahUtility('add', nama, kode);
                } else {
                    // Tanpa Program ATAU Program yang tidak punya rujukan ARKAS
                    if (inputKegiatan) inputKegiatan.value = '';
                    if (addRek) addRek.value = '';
                    handleKegiatanSekolahUtility('add', '', '');
                }
                updateActiveKsPills('add', id);
            });
        });

        // Quick Preset Pills Kegiatan Sekolah Ubah
        const editKsId = document.getElementById('editKegiatanSekolahId');
        const editKsNama = document.getElementById('editKegiatanSekolahNama');
        const editKegiatanInput = document.getElementById('editKegiatanInput');

        document.querySelectorAll('.btn-quick-ks-edit').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const id = this.dataset.id || '';
                const nama = this.dataset.nama || '';
                const kode = this.dataset.kode || '';
                const arkas = this.dataset.arkas || '';
                if (editKsId) editKsId.value = id;
                if (editKsNama) editKsNama.value = nama;

                const editRek = document.getElementById('editRekeningInput');

                if (id && arkas) {
                    if (editKegiatanInput) editKegiatanInput.value = arkas;
                    handleKegiatanSekolahUtility('edit', nama, kode);
                } else {
                    // Tanpa Program ATAU Program yang tidak punya rujukan ARKAS
                    if (editKegiatanInput) editKegiatanInput.value = '';
                    if (editRek) editRek.value = '';
                    handleKegiatanSekolahUtility('edit', '', '');
                }
                updateActiveKsPills('edit', id);
            });
        });

        // Inisialisasi pill aktif & status harga modal tambah saat dibuka
        document.querySelectorAll('[data-target="#modalAddItem"], [data-bs-target="#modalAddItem"]').forEach(btn => {
            btn.addEventListener('click', function() {
                const hiddenKsId = document.getElementById('addKegiatanSekolahId');
                const hiddenKsNama = document.getElementById('addKegiatanSekolahNama');
                if (hiddenKsId) hiddenKsId.value = '';
                if (hiddenKsNama) hiddenKsNama.value = '';
                updateActiveKsPills('add', '');
                updateSatuanLabels('add', rkasMonthCards[0]?.unit || 'Satuan');
                checkHargaMaxValidation('add');
            });
        });

        // Delegasi klik edit & delete tombol tabel
        document.addEventListener('click', function(e) {
            // Dismiss modal helper
            const dismissBtn = e.target.closest('[data-dismiss="modal"], [data-bs-dismiss="modal"]');
            if (dismissBtn) {
                const modal = dismissBtn.closest('.modal');
                if (modal) window.hideModal(modal);
                return;
            }

            // Open modal helper via attribute
            const modalTrigger = e.target.closest('[data-toggle="modal"], [data-bs-toggle="modal"]');
            if (modalTrigger) {
                const target = modalTrigger.getAttribute('data-target') || modalTrigger.getAttribute('data-bs-target');
                if (target) {
                    window.showModal(target);
                    return;
                }
            }

            // Edit button handler
            const btnEdit = e.target.closest('.btn-edit');
            if (btnEdit) {
                const id = btnEdit.dataset.id;
                const nama = btnEdit.dataset.nama || '';
                const spek = btnEdit.dataset.spek || '';
                const kegiatan = btnEdit.dataset.kegiatan || '';
                const guruId = btnEdit.dataset.guruId || '';
                const satuan = btnEdit.dataset.satuan || 'Unit';
                const harga = btnEdit.dataset.harga || '0';
                const bulan = btnEdit.dataset.bulan || 'Januari';
                const keterangan = btnEdit.dataset.keterangan || '';
                const cardsRaw = btnEdit.dataset.cards;

                const form = document.getElementById('formEditItem');
                let url = '{{ route("bos.rkas.update", ":id") }}';
                form.action = url.replace(':id', id);

                document.getElementById('editNamaBarang').value = nama;
                const editUp = document.getElementById('editUtilityProfileId');
                if (editUp) editUp.value = btnEdit.dataset.utilityProfileId || '';
                document.getElementById('editSpesifikasi').value = spek;
                const editKs = document.getElementById('editKegiatanSekolahId');
                if (editKs) editKs.value = btnEdit.dataset.kegiatanSekolahId || '';
                const editKsNama = document.getElementById('editKegiatanSekolahNama');
                if (editKsNama) editKsNama.value = btnEdit.dataset.kegiatanSekolah || '';
                if (editKegiatanInput) editKegiatanInput.value = kegiatan;
                const editRek = document.getElementById('editRekeningInput');
                if (editRek) editRek.value = btnEdit.dataset.kodeRekening || '';
                document.getElementById('editGuruId').value = guruId;
                document.getElementById('editHargaInput').value = formatThousands(harga);
                document.getElementById('editKeterangan').value = keterangan;

                // Bind utility profile & category pada modal edit
                const upId = btnEdit.dataset.utilityProfileId;
                const ksId = btnEdit.dataset.kegiatanSekolahId;
                const ksNama = btnEdit.dataset.kegiatanSekolah || '';

                let ksKode = '';
                if (ksId) {
                    const matchedBtn = document.querySelector(`.btn-quick-ks-edit[data-id="${ksId}"]`);
                    if (matchedBtn) {
                        ksKode = matchedBtn.dataset.kode || '';
                    }
                }

                if (upId && !ksKode) {
                    const p = UTILITY_PROFILES.find(item => item.id == upId);
                    if (p) {
                        if (p.category.includes('listrik')) ksKode = 'PLN';
                        else if (p.category.includes('telkom')) ksKode = 'TLP';
                        else if (p.category.includes('internet')) ksKode = 'INET';
                    }
                }

                handleKegiatanSekolahUtility('edit', ksNama, ksKode, upId || null, false);
                updateActiveKsPills('edit', ksId || null);

                updateSatuanLabels('edit', satuan);
                const editMax = document.getElementById('displayEditHargaMax');
                const editRefInfo = document.getElementById('labelEditRefRangeInfo');
                if (upId) {
                    if (editMax) editMax.value = 'Fleksibel';
                    if (editRefInfo) editRefInfo.textContent = 'Tagihan pemakaian riil';
                } else {
                    if (editMax) editMax.value = formatThousands(harga);
                    if (editRefInfo) editRefInfo.textContent = 'Batas standar ARKAS';
                }
                checkHargaMaxValidation('edit');

                // Load month cards from data-cards
                let parsedCards = [];
                try {
                    parsedCards = JSON.parse(cardsRaw);
                } catch(err) {
                    parsedCards = [];
                }

                if (Array.isArray(parsedCards) && parsedCards.length > 0) {
                    rkasEditMonthCards = parsedCards.map(c => ({
                        id: nextRkasEditCardId++,
                        origId: c.id || null,
                        month: c.month || 'Januari',
                        volume: parseFloat(c.volume) || 1,
                        unit: c.unit || satuan
                    }));
                } else {
                    rkasEditMonthCards = [{
                        id: nextRkasEditCardId++,
                        origId: id,
                        month: bulan,
                        volume: parseFloat(btnEdit.dataset.volume) || 1,
                        unit: satuan
                    }];
                }

                renderEditMonthCards();
                window.showModal('#modalEditItem');
                return;
            }

            // Delete button handler
            const btnDelete = e.target.closest('.btn-delete');
            if (btnDelete) {
                const id = btnDelete.dataset.id;
                const nama = btnDelete.dataset.nama || 'item ini';
                const isRecurring = btnDelete.dataset.isRecurring;

                const form = document.getElementById('formDeleteItem');
                let url = '{{ route("bos.rkas.destroy", ":id") }}';
                form.action = url.replace(':id', id);

                if (typeof Swal !== 'undefined') {
                    if (isRecurring == '1') {
                        Swal.fire({
                            title: 'Hapus Item Pengajuan Berulang?',
                            html: `Item <strong>${nama}</strong> merupakan bagian dari pengajuan berulang.<br><br>Apakah Anda ingin menghapus <strong>seluruh alokasi bulan</strong> atau <strong>hanya bulan ini saja</strong>?`,
                            icon: 'question',
                            showCancelButton: true,
                            showDenyButton: true,
                            confirmButtonColor: '#d33',
                            denyButtonColor: '#3085d6',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: '<i class="fas fa-trash-alt mr-1"></i> Hapus Semua Bulan',
                            denyButtonText: 'Hanya Bulan Ini Saja',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                document.getElementById('inputDeleteAllRecurring').value = '1';
                                form.submit();
                            } else if (result.isDenied) {
                                document.getElementById('inputDeleteAllRecurring').value = '0';
                                form.submit();
                            }
                        });
                    } else {
                        Swal.fire({
                            title: 'Konfirmasi Hapus',
                            html: `Apakah Anda yakin ingin menghapus item rencana <strong>${nama}</strong>?`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: '<i class="fas fa-trash-alt mr-1"></i> Ya, Hapus!',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                document.getElementById('inputDeleteAllRecurring').value = '0';
                                form.submit();
                            }
                        });
                    }
                } else {
                    if (confirm(`Hapus item rencana '${nama}'?`)) {
                        document.getElementById('inputDeleteAllRecurring').value = '0';
                        form.submit();
                    }
                }
                return;
            }
        });
    });
</script>
@stop
