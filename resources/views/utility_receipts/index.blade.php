@extends('adminlte::page')

@section('title', 'Struk Utilitas - Bukti Pembayaran Listrik, Telepon & Internet')

@section('plugins.Datatables', true)
@section('plugins.Select2', true)
@section('plugins.Sweetalert2', true)

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap" style="padding: 2px 0;">
    <div>
        <h1 class="page-title text-dark">
            <i class="fas fa-receipt text-warning mr-1"></i> Struk Utilitas
            <span class="badge badge-success px-2.5 py-1 font-weight-bold ml-1" style="font-size: 0.72rem; vertical-align: middle;">BOS & Operasional</span>
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-house"></i> Dashboard</a></li>
                <li class="breadcrumb-item">BOSP</li>
                <li class="breadcrumb-item active">Struk Utilitas</li>
            </ol>
        </nav>
    </div>
    <div class="header-actions d-flex align-items-center flex-wrap" style="gap: 8px;">
        <!-- Filter Form Tahun & Bulan -->
        <form action="{{ route('bos.utilitas.index') }}" method="GET" class="d-flex align-items-center" style="gap: 6px;">
            <div class="input-group input-group-sm" style="width: auto;">
                <span class="input-group-text bg-light text-muted py-0 px-2 font-weight-bold" style="font-size: 0.75rem;">
                    <i class="far fa-calendar-alt text-primary mr-1"></i> Tahun:
                </span>
                <select name="year" class="form-control form-control-sm font-weight-bold" onchange="this.form.submit()" style="font-size: 0.75rem; height: 30px;">
                    <option value="all" {{ $isAllYears ? 'selected' : '' }}>Semua Tahun</option>
                    @foreach($availableYears as $yr)
                        <option value="{{ $yr }}" {{ (! $isAllYears && (int)$activeFiscalYear === (int)$yr) ? 'selected' : '' }}>{{ $yr }}</option>
                    @endforeach
                </select>
            </div>
            <div class="input-group input-group-sm" style="width: auto;">
                <span class="input-group-text bg-light text-muted py-0 px-2 font-weight-bold" style="font-size: 0.75rem;">Bulan:</span>
                <select name="month" class="form-control form-control-sm font-weight-bold" onchange="this.form.submit()" style="font-size: 0.75rem; height: 30px;">
                    <option value="all" {{ $isAllMonths ? 'selected' : '' }}>Semua Bulan</option>
                    @foreach($months as $mCode => $mName)
                        <option value="{{ $mCode }}" {{ (! $isAllMonths && $selectedMonth === $mCode) ? 'selected' : '' }}>{{ $mName }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        <button type="button" class="btn btn-sm btn-success shadow-sm font-weight-bold" id="btn-open-ocr" onclick="window.openUploadModal('single')" data-toggle="modal" data-target="#modalScanReceipt">
            <i class="fas fa-bolt mr-1"></i> Upload Struk OCR
        </button>
        <button type="button" class="btn btn-sm btn-primary shadow-sm font-weight-bold" id="btn-open-ai" onclick="window.openUploadModal('batch')" data-toggle="modal" data-target="#modalScanReceipt">
            <i class="fas fa-robot mr-1"></i> Upload Struk AI
        </button>
    </div>
</div>
@stop

@section('css')
<style>
    body, .content-wrapper, .card, .btn, .form-control {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
    }
    h1.page-title {
        font-size: 1.2rem !important;
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
    }

    /* Matrix Cards */
    .matrix-card {
        border-radius: 10px;
        transition: all 0.2s ease;
        border: 1px solid rgba(0,0,0,0.08);
        background: #fff;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .matrix-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    }
    .matrix-card.is-paid {
        background-color: #f0fdf4;
        border-color: #bbf7d0;
    }
    .matrix-card.is-pending {
        background-color: #fffbeb;
        border-color: #fef08a;
    }

    /* Uniform Filter Bar Row (Card 2 Header) */
    .filter-category-btn {
        height: 31px !important;
        font-size: 0.78rem !important;
        font-weight: 600 !important;
        border-radius: 6px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 0 13px !important;
        line-height: 1 !important;
        white-space: nowrap !important;
        transition: all 0.15s ease !important;
    }
    #filter-profile {
        height: 31px !important;
        font-size: 0.78rem !important;
        border-radius: 6px !important;
        padding: 0 10px !important;
        line-height: 31px !important;
        color: #1e293b;
    }
    #btn-reload-table {
        height: 31px !important;
        width: 31px !important;
        padding: 0 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        border-radius: 6px !important;
        font-size: 0.8rem !important;
    }

    /* Drag & drop upload box */
    .dropzone-box {
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        background: #f8fafc;
        text-align: center;
        padding: 24px 16px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .dropzone-box:hover, .dropzone-box.dragover {
        border-color: #0284c7;
        background: #f0f9ff;
    }

    /* Modal Tabs High-Contrast Styling */
    #uploadTab {
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 3px;
    }
    #uploadTab .nav-link {
        color: #475569 !important;
        border-radius: 6px;
        padding: 5px 14px;
        font-size: 0.78rem;
        transition: all 0.15s ease-in-out;
        background: transparent;
        border: 1px solid transparent;
    }
    #uploadTab .nav-link:hover {
        color: #0f172a !important;
        background: rgba(255, 255, 255, 0.7) !important;
    }
    #uploadTab .nav-link.active {
        color: #ffffff !important;
        background-color: #0284c7 !important;
        font-weight: 700 !important;
        box-shadow: 0 1px 3px rgba(2, 132, 199, 0.35);
    }
    #uploadTab .nav-link.active * {
        color: #ffffff !important;
    }

    /* Modal Layout Responsive & Full Height Fill */
    #modalScanReceipt .modal-dialog {
        max-width: 1240px;
        width: 95vw;
        margin: 1.25rem auto;
    }
    #modalScanReceipt .modal-content {
        max-height: calc(100vh - 2.5rem);
        height: auto;
        display: flex;
        flex-direction: column;
        border-radius: 14px;
        overflow: hidden;
        background-color: #fff;
    }
    #modalScanReceipt .tab-content {
        flex: 1 1 auto;
        display: flex;
        flex-direction: column;
        min-height: 0;
        overflow: hidden;
    }
    #modalScanReceipt .tab-pane {
        flex: 1 1 auto;
        min-height: 0;
        overflow: hidden;
    }
    #modalScanReceipt .tab-pane.active {
        display: flex !important;
        flex-direction: column;
    }
    #modalScanReceipt .modal-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    #modalScanReceipt .modal-body > .row {
        flex: 1 1 auto;
        min-height: 0;
        height: 100%;
    }
    .receipt-preview-pane {
        background-color: #0f172a;
        height: 100%;
        min-height: 480px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }
    .receipt-tool-btn {
        padding: 2px 6px !important;
        font-size: 0.68rem !important;
        height: 22px !important;
        line-height: 1 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    .receipt-form-pane {
        height: 100%;
        min-height: 0;
        max-height: calc(100vh - 160px);
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        background-color: #fff;
    }
    .receipt-form-pane::-webkit-scrollbar {
        width: 6px;
    }
    .receipt-form-pane::-webkit-scrollbar-track {
        background: #f8fafc;
    }
    .receipt-form-pane::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }
    .receipt-form-pane::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    .receipt-img-view {
        max-height: 100%;
        max-width: 100%;
        object-fit: contain;
        transition: transform 0.2s ease;
    }

    /* Batch File Chip */
    .batch-file-item {
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 8px 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 6px;
    }

    .pulse-highlight {
        animation: pulseBorder 1.5s 2 ease-in-out;
    }
    @keyframes pulseBorder {
        0% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.6); }
        70% { box-shadow: 0 0 0 10px rgba(99, 102, 241, 0); }
        100% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0); }
    }

    /* Google Lens Interactive Styling */
    #lens-image-wrapper {
        position: relative;
        display: inline-block;
        transition: transform 0.15s ease-out;
        transform-origin: center center;
        max-width: 95%;
        line-height: 0;
    }
    #lens-boxes-layer {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 8;
        line-height: normal;
    }
    .lens-box {
        position: absolute;
        pointer-events: auto;
        cursor: pointer;
        border-radius: 3px;
        background: rgba(56, 189, 248, 0.15);
        border: 1px dashed rgba(56, 189, 248, 0.65);
        transition: all 0.12s ease-in-out;
        box-sizing: border-box;
    }
    .lens-box:hover {
        background: rgba(56, 189, 248, 0.5) !important;
        border: 1.5px solid #0284c7 !important;
        box-shadow: 0 0 10px rgba(56, 189, 248, 0.8), inset 0 0 4px rgba(255, 255, 255, 0.5);
        z-index: 25;
    }
    .lens-box.lens-box-token {
        border-color: rgba(234, 179, 8, 0.75);
        background: rgba(234, 179, 8, 0.2);
    }
    .lens-box.lens-box-token:hover {
        border-color: #eab308 !important;
        background: rgba(234, 179, 8, 0.55) !important;
        box-shadow: 0 0 12px rgba(234, 179, 8, 0.85);
    }
    .lens-box.lens-box-money {
        border-color: rgba(34, 197, 94, 0.75);
        background: rgba(34, 197, 94, 0.2);
    }
    .lens-box.lens-box-money:hover {
        border-color: #22c55e !important;
        background: rgba(34, 197, 94, 0.55) !important;
        box-shadow: 0 0 12px rgba(34, 197, 94, 0.85);
    }
    .lens-popover {
        position: absolute;
        background: #0f172a;
        color: #f8fafc;
        border-radius: 8px;
        padding: 6px 8px;
        font-size: 0.73rem;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.5);
        border: 1px solid #334155;
        z-index: 100;
        pointer-events: auto;
        white-space: nowrap;
        animation: lensFadeIn 0.12s ease-out;
    }
    @keyframes lensFadeIn {
        from { opacity: 0; transform: translateY(4px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .lens-popover button {
        display: block;
        width: 100%;
        text-align: left;
        background: none;
        border: none;
        color: #e2e8f0;
        padding: 4px 8px;
        font-size: 0.72rem;
        border-radius: 4px;
        cursor: pointer;
        transition: background 0.12s;
    }
    .lens-popover button:hover {
        background: #1e293b;
        color: #38bdf8;
    }
    .lens-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #1e293b;
        color: #e2e8f0;
        border: 1px solid #334155;
        border-radius: 12px;
        padding: 2px 8px;
        font-size: 0.68rem;
        cursor: pointer;
        transition: all 0.15s;
    }
    .lens-chip:hover {
        background: #0284c7;
        color: #fff;
        border-color: #38bdf8;
        transform: translateY(-1px);
    }
    .lens-chip-token {
        border-color: #ca8a04;
        color: #fef08a;
    }
    .lens-chip-money {
        border-color: #16a34a;
        color: #86efac;
    }
    .sniper-active {
        cursor: crosshair !important;
    }
    #lens-sniper-box {
        position: absolute;
        border: 2px dashed #38bdf8;
        background: rgba(56, 189, 248, 0.22);
        pointer-events: none;
        z-index: 30;
    }
    .input-field-highlight {
        animation: fieldPulse 1.2s 2 ease-in-out;
    }
    @keyframes fieldPulse {
        0% { background-color: #ecfdf5; border-color: #10b981; }
        50% { background-color: #a7f3d0; border-color: #059669; }
        100% { background-color: inherit; }
    }
</style>
@stop

@section('content')
<div class="container-fluid px-0">

    <!-- Card 1: 9 Recurring Subscriptions Checklist Matrix -->
    <div class="card shadow-sm border-0 mb-3" style="border-radius: 12px;">
        <div class="card-header bg-white py-2.5 border-bottom d-flex justify-content-between align-items-center flex-wrap" style="gap: 8px;">
            <div>
                <h6 class="font-weight-bold text-dark mb-0">
                    <i class="fas fa-tasks text-primary mr-1.5"></i> Status 9 Tagihan Rutin Bulanan
                    <span class="text-muted font-weight-normal ml-1" style="font-size: 0.8rem;">(Periode: <strong id="lbl-matrix-period">{{ $selectedMonthName }} {{ $isAllYears ? '(Semua Tahun)' : $activeFiscalYear }}</strong>)</span>
                </h6>
            </div>
            <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                @if($isAllMonths || $isAllYears)
                    <span class="badge badge-info px-2.5 py-1.5 font-weight-bold font-tabular" style="font-size: 0.78rem;">
                        <i class="fas fa-calculator mr-1"></i> Rata-rata/Bulan: Rp {{ number_format($totalMonthlyAvg, 0, ',', '.') }}
                    </span>
                    <span class="badge badge-primary px-2.5 py-1.5 font-weight-bold font-tabular" style="font-size: 0.78rem; background-color: #6366f1;">
                        <i class="fas fa-bullseye mr-1"></i> Acuan RKA (12 Bln): Rp {{ number_format($totalProjectedAnnual, 0, ',', '.') }}
                    </span>
                    <span class="badge badge-success px-2.5 py-1.5 font-weight-bold font-tabular" style="font-size: 0.78rem;" id="badge-month-total">
                        Total Terbayar: Rp {{ number_format($monthTotalExpenses, 0, ',', '.') }}
                    </span>
                @else
                    <span class="badge badge-info px-2.5 py-1.5 font-weight-bold font-tabular" style="font-size: 0.78rem;" id="badge-compliance-count">
                        Kepatuhan: <strong>{{ $paidCount }}</strong> dari {{ $totalProfilesCount }} Terpenuhi
                    </span>
                    <span class="badge badge-success px-2.5 py-1.5 font-weight-bold font-tabular" style="font-size: 0.78rem;" id="badge-month-total">
                        Total Bulan Ini: Rp {{ number_format($monthTotalExpenses, 0, ',', '.') }}
                    </span>
                @endif
            </div>
        </div>
        <div class="card-body p-3 bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-2" id="matrix-container">
                @foreach($profileMatrix as $item)
                    @php
                        $p = $item['profile'];
                        $isPaid = $item['is_paid'];
                        $latest = $item['latest_receipt'];
                    @endphp
                    <div class="col mb-2 matrix-item-col" data-profile-id="{{ $p->id }}">
                        <div class="matrix-card p-2.5 h-100 {{ $isPaid ? 'is-paid' : 'is-pending' }}">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <div class="d-flex align-items-center" style="gap: 6px;">
                                    <span class="badge {{ $isPaid ? 'badge-success' : 'badge-warning text-dark' }} rounded-circle p-1" style="width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.65rem;">
                                        <i class="fas {{ $isPaid ? 'fa-check' : 'fa-clock' }}"></i>
                                    </span>
                                    <span class="font-weight-bold text-dark" style="font-size: 0.82rem;">
                                        {{ $p->sort_order }}. {{ $p->customer_name }}
                                    </span>
                                </div>
                                <span class="badge {{ $isPaid ? 'badge-success' : 'badge-warning' }} px-1.5 py-0.5" style="font-size: 0.68rem;">
                                    @if($isAllMonths || $isAllYears)
                                        {{ $item['distinct_months_count'] > 0 ? $item['distinct_months_count'].' Bln' : 'Kosong' }}
                                    @else
                                        {{ $isPaid ? 'Lunas' : 'Menunggu' }}
                                    @endif
                                </span>
                            </div>

                            <div class="text-muted font-tabular mt-0.5" style="font-size: 0.73rem;">
                                @if($p->meter_number)
                                    <div>Meter: <strong>{{ $p->meter_number }}</strong></div>
                                @elseif($p->customer_number)
                                    <div>ID/Telp: <strong>{{ $p->customer_number }}</strong></div>
                                @else
                                    <div>{{ $p->payment_method ?: 'Transfer Bank' }}</div>
                                @endif
                            </div>

                            @if($isAllMonths || $isAllYears)
                                <div class="mt-2 pt-1 border-top" style="font-size: 0.73rem;">
                                    <div class="d-flex justify-content-between align-items-center mb-0.5">
                                        <span class="text-muted">Total Bayar:</span>
                                        <span class="font-weight-bold font-tabular text-dark">Rp {{ number_format($item['total_paid'], 0, ',', '.') }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-0.5">
                                        <span class="text-primary font-weight-500"><i class="fas fa-calculator mr-1"></i>Rata2/Bln:</span>
                                        <span class="font-weight-bold font-tabular text-primary">Rp {{ number_format($item['avg_per_month'], 0, ',', '.') }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-indigo font-weight-500" style="color: #6366f1;"><i class="fas fa-bullseye mr-1"></i>Acuan RKA (12bln):</span>
                                        <span class="font-weight-bold font-tabular" style="color: #4f46e5;">Rp {{ number_format($item['projected_annual'], 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            @else
                                <div class="d-flex justify-content-between align-items-end mt-2 pt-1 border-top" style="font-size: 0.75rem;">
                                    @if($isPaid)
                                        <span class="text-success font-weight-bold font-tabular">
                                            Rp {{ number_format($item['total_paid'], 0, ',', '.') }}
                                        </span>
                                        @if($latest && $latest->token_number)
                                            <button type="button" class="btn btn-xs btn-outline-warning text-dark py-0 px-1 font-monospace font-weight-bold btn-copy-token" data-token="{{ $latest->clean_token_number }}" title="Salin Token">
                                                <i class="fas fa-bolt text-warning mr-0.5"></i> Token
                                            </button>
                                        @endif
                                    @else
                                        <span class="text-muted font-italic" style="font-size: 0.72rem;">Belum ada struk</span>
                                        <button type="button" class="btn btn-xs btn-outline-primary py-0 px-1.5 btn-quick-upload-profile" data-profile-id="{{ $p->id }}" data-profile-name="{{ $p->name }}" onclick="window.openUploadModal('single', {{ $p->id }})" data-toggle="modal" data-target="#modalScanReceipt" title="Upload Struk untuk tagihan ini">
                                            <i class="fas fa-plus"></i> Upload
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Live Status Banner for Background Jobs -->
    <div id="banner-bg-processing" class="alert alert-warning py-2.5 px-3 mb-3 d-none align-items-center justify-content-between shadow-sm" style="border-radius: 10px; border-left: 4px solid #f59e0b;">
        <div class="d-flex align-items-center" style="gap: 10px;">
            <i class="fas fa-spinner fa-spin text-warning" style="font-size: 1.3rem;"></i>
            <div>
                <strong class="d-block" style="font-size: 0.85rem;" id="banner-bg-text">AI Sedang Memproses Struk di Latar Belakang...</strong>
                <small class="text-muted" id="banner-bg-subtext">Sistem sedang mengekstrak teks struk. Halaman akan terupdate otomatis.</small>
            </div>
        </div>
        <button type="button" class="btn btn-xs btn-outline-dark" id="btn-manual-poll-refresh">
            <i class="fas fa-sync-alt mr-1"></i> Cek Status Sekarang
        </button>
    </div>

    <!-- Card 2: Main Data Table -->
    <div class="card shadow-sm border-0" style="border-radius: 12px;">
        <div class="card-header bg-white py-2.5 border-bottom d-flex justify-content-between align-items-center flex-wrap" style="gap: 8px;">
            <div class="d-flex align-items-center flex-wrap" style="gap: 6px;">
                <button type="button" class="btn btn-sm btn-primary filter-category-btn active" data-cat="all">Semua Kategori</button>
                <button type="button" class="btn btn-sm btn-outline-secondary filter-category-btn" data-cat="listrik_pra"><i class="fas fa-bolt mr-1 text-primary"></i>Listrik Prabayar</button>
                <button type="button" class="btn btn-sm btn-outline-secondary filter-category-btn" data-cat="listrik_pasca"><i class="fas fa-plug mr-1 text-success"></i>Listrik Pasca</button>
                <button type="button" class="btn btn-sm btn-outline-secondary filter-category-btn" data-cat="telkom_speedy"><i class="fas fa-phone mr-1 text-info"></i>Telkom Speedy</button>
                <button type="button" class="btn btn-sm btn-outline-secondary filter-category-btn" data-cat="internet"><i class="fas fa-wifi mr-1 text-warning"></i>Internet</button>
            </div>
            <div class="d-flex align-items-center" style="gap: 6px;">
                <select id="filter-profile" class="form-control form-control-sm font-weight-500" style="width: auto; max-width: 230px;">
                    <option value="">-- Semua Objek Tagihan --</option>
                    @foreach($profiles as $pr)
                        <option value="{{ $pr->id }}">{{ $pr->sort_order }}. {{ $pr->customer_name }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-reload-table" title="Muat Ulang Data">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
        <div class="card-body p-3">
            <div class="table-responsive">
                <table class="table table-sm table-hover table-striped w-100" id="table-receipts" style="font-size: 0.8rem;">
                    <thead class="bg-light text-muted uppercase" style="font-size: 0.72rem; letter-spacing: 0.03em;">
                        <tr>
                            <th class="text-center" style="width: 35px;">No</th>
                            <th>Tanggal & Resi</th>
                            <th>Objek Tagihan</th>
                            <th>No. Meter / IDPEL</th>
                            <th>Tarif / Daya</th>
                            <th>Rincian Biaya</th>
                            <th>Total Bayar</th>
                            <th>Token & Kertas Kerja</th>
                            <th class="text-center" style="width: 75px;">Status AI</th>
                            <th class="text-center" style="width: 55px;">Struk</th>
                            <th class="text-center" style="width: 90px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal 1: Upload Modal (Batch & Single Scan) -->
<div class="modal fade" id="modalScanReceipt" tabindex="-1" role="dialog" aria-labelledby="modalScanLabel" aria-hidden="true" data-backdrop="static" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
            <div class="modal-header bg-white py-2 px-4 border-bottom d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center" style="gap: 12px;">
                    <h6 class="modal-title font-weight-bold text-dark mb-0">
                        <i class="fas fa-cloud-arrow-up text-primary mr-1"></i> Upload Struk Utilitas
                    </h6>
                    <!-- Nav Tabs -->
                    <ul class="nav nav-pills" id="uploadTab" role="tablist" style="font-size: 0.78rem;">
                        <li class="nav-item">
                            <a class="nav-link active py-1.5 px-3 font-weight-bold" id="tab-single-btn" data-toggle="pill" href="#tab-single" role="tab">
                                <i class="fas fa-bolt mr-1"></i> Upload Struk OCR (Lokal Instan)
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link py-1.5 px-3 font-weight-bold" id="tab-batch-btn" data-toggle="pill" href="#tab-batch" role="tab">
                                <i class="fas fa-robot mr-1"></i> Upload Struk AI (Latar Belakang / Massal)
                            </a>
                        </li>
                    </ul>
                </div>
                <button type="button" class="btn-close text-muted close" data-dismiss="modal" data-bs-dismiss="modal" onclick="window.hideModal('#modalScanReceipt')" aria-label="Close"></button>
            </div>

            <div class="tab-content" id="uploadTabContent">
                <!-- TAB 1: SINGLE OCR SCAN (LOCAL FAST & AUTO-FILL) -->
                <div class="tab-pane fade show active" id="tab-single" role="tabpanel">
                    <div class="modal-body p-0">
                        <div class="row no-gutters">
                            <!-- Sisi Kiri: Upload Box & Image Preview -->
                            <div class="col-md-5 receipt-preview-pane p-3 text-center">
                                <div id="box-upload-zone" class="dropzone-box w-100 my-auto">
                                    <i class="fas fa-bolt text-success mb-2" style="font-size: 2.2rem;"></i>
                                    <div class="font-weight-bold text-dark" style="font-size: 0.88rem;">Pilih / Tarik Foto Struk ke Sini</div>
                                    <div class="text-success font-weight-bold my-1" style="font-size: 0.74rem;">
                                        <i class="fas fa-magic mr-1"></i> Otomatis Deteksi ID Pelanggan, Bulan & Nominal
                                    </div>
                                    <div class="text-muted my-1" style="font-size: 0.72rem;">Mendukung foto kamera HP, JPG, PNG, WEBP, PDF (Maks. 20MB)</div>
                                    <div class="mt-2 d-flex flex-column align-items-center" style="gap: 6px;">
                                        <button type="button" class="btn btn-sm btn-outline-success font-weight-bold" onclick="$('#receipt_file_input').click()">
                                            <i class="fas fa-folder-open mr-1"></i> Pilih Berkas Foto Struk
                                        </button>
                                        <span class="badge bg-white text-secondary border px-2 py-1 shadow-xs" style="font-size: 0.72rem;">
                                            <i class="fas fa-paste text-primary mr-1"></i> Bisa langsung <kbd class="bg-dark text-white px-1">Ctrl + V</kbd> untuk tempel gambar WhatsApp Web
                                        </span>
                                    </div>
                                    <input type="file" id="receipt_file_input" accept="image/*,application/pdf" class="d-none">
                                </div>

                                <!-- Image Preview Container with Zoom Controls & Google Lens Overlay -->
                                <div id="box-image-preview" class="w-100 h-100 d-none flex-column align-items-center justify-content-between position-relative">
                                    <!-- Toolbar -->
                                    <div class="d-flex justify-content-between align-items-center w-100 px-2 py-1 mb-1.5 rounded border border-secondary text-light flex-wrap" style="background: rgba(15, 23, 42, 0.9); font-size: 0.72rem; z-index: 5; gap: 4px;">
                                        <span class="font-weight-bold" style="font-size: 0.7rem;"><i class="fas fa-image text-info mr-1"></i> Pratinjau</span>
                                        <div class="d-flex align-items-center flex-wrap" style="gap: 3px;">
                                            <button type="button" class="btn btn-xs btn-outline-warning receipt-tool-btn d-none font-weight-bold" id="btn-toggle-enhanced" title="Filter Cerdas (Kontras & Ketajaman)">
                                                <i class="fas fa-wand-magic-sparkles"></i>
                                            </button>
                                            <div class="btn-group btn-group-xs">
                                                <button type="button" class="btn btn-xs btn-info receipt-tool-btn active" id="btn-toggle-lens" title="Google Lens (Sorot Teks & Angka)">
                                                    <i class="fas fa-bullseye"></i>
                                                </button>
                                                <button type="button" class="btn btn-xs btn-outline-light receipt-tool-btn" id="btn-toggle-sniper" title="Mode Bidik Area (Tarik kotak pada bagian struk untuk baca khusus)">
                                                    <i class="fas fa-crop-simple"></i>
                                                </button>
                                            </div>
                                            <div class="btn-group btn-group-xs">
                                                <button type="button" class="btn btn-xs btn-outline-light receipt-tool-btn" id="btn-zoom-in" title="Perbesar (Zoom In)"><i class="fas fa-search-plus"></i></button>
                                                <button type="button" class="btn btn-xs btn-outline-light receipt-tool-btn" id="btn-zoom-out" title="Perkecil (Zoom Out)"><i class="fas fa-search-minus"></i></button>
                                                <button type="button" class="btn btn-xs btn-outline-light receipt-tool-btn font-weight-bold" id="btn-zoom-reset" title="Reset Ukuran (100%)" style="font-size: 0.62rem;">1:1</button>
                                            </div>
                                            <div class="btn-group btn-group-xs">
                                                <button type="button" class="btn btn-xs btn-outline-light receipt-tool-btn" id="btn-rotate-img" title="Putar 90° Searah Jarum Jam"><i class="fas fa-rotate-right"></i></button>
                                                <button type="button" class="btn btn-xs btn-outline-light receipt-tool-btn" id="btn-flip-180" title="Balik 180° Langsung"><i class="fas fa-arrows-up-down"></i></button>
                                            </div>
                                            <button type="button" class="btn btn-xs btn-warning receipt-tool-btn d-none font-weight-bold" id="btn-apply-rotation" title="Terapkan rotasi ini ke foto dan scan ulang"><i class="fas fa-arrows-rotate mr-1"></i>Pindai</button>
                                            <button type="button" class="btn btn-xs btn-outline-info receipt-tool-btn" id="btn-view-large" title="Lihat Layar Penuh"><i class="fas fa-expand"></i></button>
                                            <button type="button" class="btn btn-xs btn-outline-danger receipt-tool-btn" id="btn-remove-img" title="Ganti Gambar"><i class="fas fa-times"></i></button>
                                        </div>
                                    </div>

                                    <!-- Auto-Flip Notification Badge -->
                                    <div id="badge-autoflip-active" class="d-none badge bg-info text-white py-1 px-2 mb-1.5 w-100 text-left border border-info shadow-sm" style="font-size: 0.7rem; white-space: normal;">
                                        <i class="fas fa-arrows-rotate mr-1"></i> <strong>Foto Terbalik Terdeteksi:</strong> Posisi foto telah diputar 180° otomatis agar tegak lurus dan terbaca akurat.
                                    </div>

                                    <!-- Filter Cerdas Status Notification -->
                                    <div id="badge-enhanced-active" class="d-none badge bg-warning text-dark py-1 px-2 mb-1.5 w-100 text-left border border-warning shadow-sm" style="font-size: 0.7rem; white-space: normal;">
                                        <i class="fas fa-wand-magic-sparkles mr-1 text-primary"></i> <strong>Filter Cerdas Aktif:</strong> Otomatis tegak, dipotong rapi, dan dipertajam untuk memudahkan pembacaan teks struk.
                                    </div>

                                    <!-- Zoomable & Draggable Image Viewport with Lens Wrapper -->
                                    <div class="w-100 text-center p-1 rounded overflow-auto d-flex align-items-center justify-content-center position-relative flex-grow-1" id="preview-img-container" style="min-height: 250px; max-height: calc(100vh - 360px); background: #070b13; user-select: none; border: 1px solid #1e293b;">
                                        <div id="lens-image-wrapper">
                                            <img id="preview-receipt-img" src="" alt="Foto Struk" class="receipt-img-view rounded border border-secondary shadow d-block" style="max-width: 100%; height: auto;">
                                            <!-- Interactive Google Lens Overlay -->
                                            <div id="lens-boxes-layer"></div>
                                            <!-- Sniper Selection Box -->
                                            <div id="lens-sniper-box" class="d-none"></div>
                                        </div>
                                    </div>

                                    <!-- Bottom Hints -->
                                    <div class="w-100 mt-1">
                                        <div class="d-flex justify-content-between align-items-center text-light px-1" style="font-size: 0.67rem; opacity: 0.9;">
                                            <span><i class="fas fa-bullseye text-info mr-1"></i><strong>Google Lens:</strong> Arahkan kursor & klik teks/angka di gambar untuk mengisi form.</span>
                                            <span><kbd style="font-size: 0.64rem; background: #334155;">Ctrl + V</kbd> Tempel Foto</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Scanning Spinner Overlay -->
                                <div id="scan-loading-spinner" class="d-none position-absolute w-100 h-100 d-flex flex-column align-items-center justify-content-center bg-dark" style="opacity: 0.85; z-index: 10;">
                                    <div class="spinner-border text-success mb-2" role="status"></div>
                                    <div class="text-white font-weight-bold" style="font-size: 0.85rem;">OCR Lokal sedang memindai struk...</div>
                                    <small class="text-light">Mengekstrak tanggal, IDPEL, tarif, biaya, dan token</small>
                                </div>
                            </div>

                            <!-- Sisi Kanan: Form Hasil Ekstraksi -->
                            <div class="col-md-7 receipt-form-pane p-3 p-md-4">
                                <form id="form-save-receipt" style="padding-bottom: 2.5rem;">
                                    @csrf
                                    <input type="hidden" name="image_base64" id="form_image_base64">
                                    <input type="hidden" name="ocr_raw_data" id="form_ocr_raw_data">

                                    <!-- Mode OCR Switcher -->
                                    <div class="d-flex align-items-center justify-content-between mb-2.5 p-2 bg-light rounded border">
                                        <span class="font-weight-bold text-dark" style="font-size: 0.76rem;">
                                            <i class="fas fa-microchip text-primary mr-1"></i> Metode Pemindaian:
                                        </span>
                                        <select id="select-ocr-engine" class="form-control form-control-sm font-weight-bold" style="height: 28px; font-size: 0.75rem; width: auto; max-width: 280px;">
                                            <option value="local" selected>🖥️ 100% Lokal (Offline, Cepat & Bebas Kuota)</option>
                                            <option value="auto">⚡ Otomatis (Lokal Cepat + AI Cadangan)</option>
                                            <option value="ai">☁️ Cloud AI (Google Gemini Vision)</option>
                                        </select>
                                    </div>

                                    <!-- Alert status AI/Local Match -->
                                    <div id="alert-ai-match" class="alert alert-info py-2 px-3 mb-2.5 d-none" style="font-size: 0.78rem;">
                                        <i class="fas fa-check-circle text-success mr-1"></i>
                                        <span id="label-match-engine">Sistem mengenali:</span> <strong id="label-ai-matched">SMK PGRI KRAS</strong>
                                    </div>

                                    <!-- Bagian 1: Informasi Periode & Profil Utilitas -->
                                    <div class="d-flex align-items-center mb-2 pb-1 border-bottom">
                                        <span class="font-weight-bold text-dark text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">1. Profil & Periode Transaksi</span>
                                    </div>
                                    <div class="form-group mb-2">
                                        <label class="font-weight-bold text-dark mb-0.5" style="font-size: 0.78rem;">Akun / Objek Tagihan Utilitas <span class="text-danger">*</span></label>
                                        <select name="utility_profile_id" id="field_utility_profile_id" class="form-control form-control-sm font-weight-bold" required style="font-size: 0.8rem;">
                                            <option value="">-- Otomatis Terpilih Saat Struk Terbaca --</option>
                                            @foreach($profiles as $pr)
                                                <option value="{{ $pr->id }}" data-category="{{ $pr->category }}" data-budgeted="{{ $pr->is_budgeted ? '1' : '0' }}" data-meter="{{ $pr->meter_number }}" data-cust="{{ $pr->customer_number }}" data-name="{{ $pr->customer_name }}" data-tariff="{{ $pr->tariff_power }}" data-merchant="{{ $pr->payment_method }}">
                                                    {{ $pr->sort_order }}. {{ $pr->name }} ({{ $pr->customer_number ?: $pr->meter_number ?: 'Transfer' }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group mb-2" id="group-kertas-kerja-scan">
                                        <div class="d-flex justify-content-between align-items-center mb-0.5">
                                            <label class="font-weight-bold text-dark mb-0" style="font-size: 0.78rem;">Hubungkan ke Kertas Kerja (BOS)</label>
                                            <span class="badge badge-secondary d-none" id="badge-non-budgeted-scan">
                                                <i class="fas fa-shield-alt mr-1"></i>Non-Anggaran BOS (Internal)
                                            </span>
                                        </div>
                                        <div id="wrapper-kertas-kerja-scan">
                                            <select name="bos_kertas_kerja_item_id" id="field_bos_kertas_kerja_item_id" class="form-control form-control-sm" style="font-size: 0.8rem;">
                                                <option value="">-- Tidak Dihubungkan / Non-BOS --</option>
                                                @php
                                                    $groupedKkScan = $kertasKerjaItems->groupBy(function($item) {
                                                        $code = rtrim($item->program_code ?? '', '.');
                                                        return ($code ? $code . ' ' : '') . ($item->kegiatan ?? 'Kegiatan Utilitas');
                                                    });
                                                @endphp
                                                @foreach($groupedKkScan as $groupLabel => $groupItems)
                                                    <optgroup label="{{ $groupLabel }}">
                                                        @foreach($groupItems as $kki)
                                                            @php
                                                                $totalVal = (float) ($kki->total_amount ?: ($kki->price * ($kki->volume ?: 1)));
                                                            @endphp
                                                             <option value="{{ $kki->id }}" 
                                                                data-profile-id="{{ $kki->utility_profile_id }}" 
                                                                data-month="{{ $kki->month }}" 
                                                                data-category="{{ str_contains(strtolower($kki->kegiatan), 'listrik') ? 'listrik' : (str_contains(strtolower($kki->kegiatan), 'internet') ? 'internet' : 'telepon') }}"
                                                                data-price="{{ $kki->price }}"
                                                                data-total="{{ $totalVal }}"
                                                                data-desc="{{ $kki->description }}"
                                                                data-kegiatan="{{ $kki->kegiatan }}">
                                                                [{{ $kki->month }}] {{ $kki->description }} (Rp {{ number_format($totalVal, 0, ',', '.') }})
                                                            </option>
                                                        @endforeach
                                                    </optgroup>
                                                @endforeach
                                            </select>
                                            <small class="text-muted d-block mt-0.5" style="font-size: 0.7rem;">
                                                <i class="fas fa-info-circle text-info mr-1"></i>Hanya menampilkan kegiatan Kertas Kerja: <strong>Pembayaran daya listrik</strong>, <strong>biaya telepon</strong>, dan <strong>jasa internet</strong>.
                                            </small>
                                        </div>
                                        <div class="alert alert-secondary py-1 px-2 mb-0 mt-1 d-none" id="alert-non-budgeted-scan" style="font-size: 0.74rem;">
                                            <i class="fas fa-info-circle text-info mr-1"></i> Objek tagihan ini ditandai sebagai <strong>Non-Anggaran BOS</strong> (pembukuan internal). Tidak dapat dimasukkan ke Kertas Kerja BOS.
                                        </div>
                                    </div>

                                    <div class="row g-2 mb-2.5">
                                        <div class="col-sm-6 form-group mb-0">
                                            <label class="font-weight-500 text-muted mb-0.5" style="font-size: 0.75rem;">Tanggal & Waktu Bayar <span class="text-danger">*</span></label>
                                            <input type="datetime-local" name="transaction_date" id="field_transaction_date" class="form-control form-control-sm font-tabular" required style="font-size: 0.8rem;">
                                        </div>
                                        <div class="col-sm-3 form-group mb-0">
                                            <label class="font-weight-500 text-muted mb-0.5" style="font-size: 0.75rem;">Tahun <span class="text-danger">*</span></label>
                                            <input type="number" name="fiscal_year" id="field_fiscal_year" value="{{ $activeFiscalYear }}" class="form-control form-control-sm font-tabular" required style="font-size: 0.8rem;">
                                        </div>
                                        <div class="col-sm-3 form-group mb-0">
                                            <label class="font-weight-500 text-muted mb-0.5" style="font-size: 0.75rem;">Bulan <span class="text-danger">*</span></label>
                                            <select name="billing_month" id="field_billing_month" class="form-control form-control-sm font-weight-bold" required style="font-size: 0.8rem;">
                                                @foreach($months as $mCode => $mName)
                                                    <option value="{{ $mName }}" {{ ($selectedMonth === $mCode) ? 'selected' : '' }}>{{ $mName }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Bagian 2: Rincian Rekening & Identitas Pelanggan -->
                                    <div class="d-flex align-items-center mb-2 pb-1 border-bottom">
                                        <span class="font-weight-bold text-dark text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;" id="header-section-2">2. Identitas Pelanggan & Meter</span>
                                    </div>
                                    <div class="p-2.5 bg-light rounded border mb-2.5" id="box-customer-info">
                                        <div class="row g-2">
                                            <div class="col-sm-6 form-group mb-1" id="group-meter-number">
                                                <label class="font-weight-500 text-muted mb-0.5" style="font-size: 0.73rem;" id="label-meter-number">Nomor Meter / Serial</label>
                                                <input type="text" name="meter_number" id="field_meter_number" class="form-control form-control-sm font-tabular" placeholder="Nomor Meter">
                                            </div>
                                            <div class="col-sm-6 form-group mb-1" id="group-customer-number">
                                                <label class="font-weight-500 text-muted mb-0.5" style="font-size: 0.73rem;" id="label-customer-number">ID Pelanggan (IDPEL/No Telp)</label>
                                                <input type="text" name="customer_number" id="field_customer_number" class="form-control form-control-sm font-tabular" placeholder="ID Pelanggan">
                                            </div>
                                            <div class="col-sm-6 form-group mb-0" id="group-customer-name">
                                                <label class="font-weight-500 text-muted mb-0.5" style="font-size: 0.73rem;" id="label-customer-name">Nama Pelanggan di Struk</label>
                                                <input type="text" name="customer_name" id="field_customer_name" class="form-control form-control-sm" placeholder="Nama Pelanggan">
                                            </div>
                                            <div class="col-sm-6 form-group mb-0" id="group-tariff-power">
                                                <label class="font-weight-500 text-muted mb-0.5" style="font-size: 0.73rem;" id="label-tariff-power">Tarif / Daya</label>
                                                <input type="text" name="tariff_power" id="field_tariff_power" class="form-control form-control-sm" placeholder="Contoh: R1M/900VA">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Bagian 3: Khusus Token Listrik Prabayar -->
                                    <div class="p-2.5 bg-warning-soft rounded border border-warning mb-2.5 d-none" id="box-token-info">
                                        <div class="d-flex align-items-center mb-1.5">
                                            <span class="font-weight-bold text-dark text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;" id="header-section-token">Token Listrik Prabayar (Stroom)</span>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-sm-8 form-group mb-0" id="group-token-number">
                                                <label class="font-weight-bold text-dark mb-0.5" style="font-size: 0.75rem;"><i class="fas fa-key text-warning mr-1"></i> 20 Digit Nomor Token (Stroom)</label>
                                                <input type="text" name="token_number" id="field_token_number" class="form-control form-control-sm font-weight-bold text-dark font-tabular token-input" placeholder="XXXX-XXXX-XXXX-XXXX-XXXX" style="letter-spacing: 1px;" maxlength="24">
                                            </div>
                                            <div class="col-sm-4 form-group mb-0" id="group-kwh-amount">
                                                <label class="font-weight-500 text-muted mb-0.5" style="font-size: 0.75rem;" id="label-kwh-amount">Jml kWh Diterima</label>
                                                <input type="number" step="0.01" name="kwh_amount" id="field_kwh_amount" class="form-control form-control-sm font-tabular" placeholder="0.00">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Bagian 4: Rincian Biaya & Pembayaran -->
                                    <div class="d-flex align-items-center mb-2 pb-1 border-bottom">
                                        <span class="font-weight-bold text-dark text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;" id="header-section-3">3. Rincian Biaya & Pembayaran</span>
                                    </div>
                                    <div class="row g-2 mb-2">
                                        <div class="col-sm-4 form-group mb-1" id="group-base-amount">
                                            <label class="font-weight-500 text-muted mb-0.5" style="font-size: 0.73rem;" id="label-base-amount">Tagihan Pokok (Rp)</label>
                                            <input type="text" name="base_amount" id="field_base_amount" class="form-control form-control-sm font-tabular calc-amount rupiah-input text-right" placeholder="0">
                                        </div>
                                        <div class="col-sm-4 form-group mb-1" id="group-admin-fee">
                                            <label class="font-weight-500 text-muted mb-0.5" style="font-size: 0.73rem;" id="label-admin-fee">Admin Bank/Pos (Rp)</label>
                                            <input type="text" name="admin_fee" id="field_admin_fee" class="form-control form-control-sm font-tabular calc-amount rupiah-input text-right" placeholder="0">
                                        </div>
                                        <div class="col-sm-4 form-group mb-1" id="group-tax-pbjt">
                                            <label class="font-weight-500 text-muted mb-0.5" style="font-size: 0.73rem;" id="label-tax-pbjt">Pajak / PBJT (Rp)</label>
                                            <input type="text" name="tax_pbjt" id="field_tax_pbjt" class="form-control form-control-sm font-tabular calc-amount rupiah-input text-right" placeholder="0">
                                        </div>
                                        <!-- Verifikasi Aritmatika Real-Time (Tagihan + Admin vs Total Bayar) -->
                                        <div class="col-sm-12 mb-1 d-none" id="box-amount-verification"></div>
                                        <div class="col-sm-12 form-group mb-1" id="group-total-amount">
                                            <label class="font-weight-bold text-dark mb-0.5" style="font-size: 0.8rem;" id="label-total-amount">Total Pembayaran Akhir (Rp) <span class="text-danger">*</span></label>
                                            <input type="text" name="total_amount" id="field_total_amount" class="form-control form-control-sm font-weight-bold text-success font-tabular calc-amount rupiah-input text-right" required placeholder="0" style="font-size: 1rem;">
                                        </div>
                                    </div>

                                    <!-- Bagian 5: Identitas Struk & Lokasi -->
                                    <div class="d-flex align-items-center mb-2 pb-1 border-bottom">
                                        <span class="font-weight-bold text-dark text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;" id="header-section-4">4. Identitas Bukti Transaksi</span>
                                    </div>
                                    <div class="row g-2 mb-2">
                                        <div class="col-sm-4 form-group mb-1" id="group-receipt-number">
                                            <label class="font-weight-500 text-muted mb-0.5" style="font-size: 0.73rem;" id="label-receipt-number">Nomor Struk / Seri</label>
                                            <input type="text" name="receipt_number" id="field_receipt_number" class="form-control form-control-sm font-tabular" placeholder="No Struk">
                                        </div>
                                        <div class="col-sm-4 form-group mb-1" id="group-reference-number">
                                            <label class="font-weight-500 text-muted mb-0.5" style="font-size: 0.73rem;" id="label-reference-number">No Referensi Transaksi</label>
                                            <input type="text" name="reference_number" id="field_reference_number" class="form-control form-control-sm font-tabular" placeholder="No Ref">
                                        </div>
                                        <div class="col-sm-4 form-group mb-1" id="group-payment-merchant">
                                            <label class="font-weight-500 text-muted mb-0.5" style="font-size: 0.73rem;" id="label-payment-merchant">Lokasi / Merchant / Loket</label>
                                            <input type="text" name="payment_merchant" id="field_payment_merchant" class="form-control form-control-sm" placeholder="Contoh: PosPay, BRI, Tokopedia">
                                        </div>
                                    </div>

                                    <div class="form-group mb-0">
                                        <label class="font-weight-500 text-muted mb-0.5" style="font-size: 0.73rem;">Catatan Tambahan</label>
                                        <textarea name="notes" id="field_notes" rows="1" class="form-control form-control-sm" placeholder="Catatan jika ada..."></textarea>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between align-items-center border-top">
                        <div class="text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-shield-alt text-success mr-1"></i> Data diverifikasi otomatis sebelum disimpan ke buku BOS.
                        </div>
                        <div class="d-flex" style="gap: 6px;">
                            <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal" data-bs-dismiss="modal" onclick="window.hideModal('#modalScanReceipt')">Batal</button>
                            <button type="button" class="btn btn-sm btn-success font-weight-bold px-3" id="btn-submit-receipt">
                                <i class="fas fa-save mr-1"></i> Simpan Data Struk
                            </button>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: BATCH UPLOAD (BACKGROUND AI) -->
                <div class="tab-pane fade p-4" id="tab-batch" role="tabpanel">
                    <form id="form-batch-upload" enctype="multipart/form-data">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded border h-100">
                                    <h6 class="font-weight-bold text-dark mb-2" style="font-size: 0.85rem;">
                                        <i class="fas fa-robot text-primary mr-1"></i> Cara Kerja Upload Struk AI
                                    </h6>
                                    <ul class="text-muted pl-3 mb-3" style="font-size: 0.78rem; line-height: 1.5;">
                                        <li>Pilih <strong>1 hingga 9 foto struk</strong> sekaligus (Listrik, Telepon, Internet).</li>
                                        <li>Sistem otomatis mendeteksi <strong>ID Pelanggan, Bulan & Nominal</strong> masing-masing struk di latar belakang.</li>
                                        <li>Jika AI gagal karena server sibuk (503), sistem akan <strong>otomatis mencoba ulang</strong> secara berkala.</li>
                                        <li>Data tabel & matriks tagihan terupdate otomatis begitu proses selesai.</li>
                                    </ul>

                                    <div class="p-2 bg-white rounded border mb-2">
                                        <div class="text-muted font-weight-bold mb-1" style="font-size: 0.72rem;">
                                            <i class="fas fa-info-circle text-info mr-1"></i> Nilai Cadangan (Bila Tanggal Buram):
                                        </div>
                                        <div class="form-group mb-1.5">
                                            <label class="font-weight-500 text-muted mb-0" style="font-size: 0.72rem;">Tahun Anggaran</label>
                                            <input type="number" name="fiscal_year" value="{{ $activeFiscalYear }}" class="form-control form-control-sm font-weight-bold font-tabular" required>
                                        </div>
                                        <div class="form-group mb-0">
                                            <label class="font-weight-500 text-muted mb-0" style="font-size: 0.72rem;">Bulan Tagihan</label>
                                            <select name="billing_month" class="form-control form-control-sm font-weight-bold">
                                                @foreach($months as $mCode => $mName)
                                                    <option value="{{ $mCode }}" {{ $selectedMonth === $mCode ? 'selected' : '' }}>{{ $mName }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div id="batch-dropzone" class="dropzone-box mb-3">
                                    <i class="fas fa-images text-primary mb-2" style="font-size: 2.5rem;"></i>
                                    <h6 class="font-weight-bold text-dark mb-1">Pilih Semua Berkas Foto Struk Sekaligus</h6>
                                    <p class="text-muted mb-2" style="font-size: 0.75rem;">Tarik atau pilih berkas struk (JPG, PNG, WEBP, PDF). Anda bisa memilih 1 sampai 9 foto.</p>
                                    <button type="button" class="btn btn-sm btn-outline-primary font-weight-bold" onclick="$('#batch_file_input').click()">
                                        <i class="fas fa-folder-open mr-1"></i> Pilih Berkas Foto Struk
                                    </button>
                                    <input type="file" id="batch_file_input" name="receipt_files[]" multiple accept="image/*,application/pdf" class="d-none">
                                </div>

                                <!-- List of selected batch files -->
                                <div id="batch-files-container" class="d-none">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="font-weight-bold text-dark" style="font-size: 0.82rem;" id="batch-file-count-label">Berkas Terpilih (0):</span>
                                        <button type="button" class="btn btn-xs btn-link text-danger p-0" id="btn-clear-batch-files">Hapus Semua</button>
                                    </div>
                                    <div id="batch-files-list" class="overflow-auto pr-1" style="max-height: 220px;"></div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end align-items-center pt-2 border-top" style="gap: 8px;">
                            <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal" data-bs-dismiss="modal" onclick="window.hideModal('#modalScanReceipt')">Batal</button>
                            <button type="button" class="btn btn-sm btn-success font-weight-bold px-3" id="btn-submit-batch" disabled>
                                <i class="fas fa-cloud-arrow-up mr-1"></i> Simpan & Mulai Proses AI di Latar Belakang
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal 2: Edit Manual Receipt Modal -->
<div class="modal fade" id="modalEditReceipt" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
            <div class="modal-header bg-white py-2.5 px-4 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="modal-title font-weight-bold text-dark mb-0">
                    <i class="fas fa-edit text-warning mr-1"></i> Edit Data Bukti Pembayaran
                </h6>
                <button type="button" class="btn-close text-muted close" data-dismiss="modal" data-bs-dismiss="modal" onclick="window.hideModal('#modalEditReceipt')" aria-label="Close"></button>
            </div>
            <form id="form-edit-receipt">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_receipt_id">

                <div class="modal-body p-4 bg-light overflow-auto" style="max-height: 580px;">
                    <!-- Bagian 1: Informasi Periode & Profil Utilitas -->
                    <div class="d-flex align-items-center mb-2 pb-1 border-bottom">
                        <span class="font-weight-bold text-dark text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">1. Profil & Periode Transaksi</span>
                    </div>
                    <div class="form-group mb-2">
                        <label class="font-weight-bold text-dark mb-0.5" style="font-size: 0.78rem;">Objek Tagihan <span class="text-danger">*</span></label>
                        <select name="utility_profile_id" id="edit_utility_profile_id" class="form-control form-control-sm font-weight-bold" required>
                            <option value="">-- Pilih Profil Tagihan --</option>
                            @foreach($profiles as $pr)
                                <option value="{{ $pr->id }}" data-category="{{ $pr->category }}" data-budgeted="{{ $pr->is_budgeted ? '1' : '0' }}" data-meter="{{ $pr->meter_number }}" data-cust="{{ $pr->customer_number }}" data-name="{{ $pr->customer_name }}" data-tariff="{{ $pr->tariff_power }}" data-merchant="{{ $pr->payment_method }}">
                                    {{ $pr->sort_order }}. {{ $pr->name }} ({{ $pr->customer_number ?: $pr->meter_number ?: 'Transfer' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mb-2" id="edit-group-kertas-kerja">
                        <div class="d-flex justify-content-between align-items-center mb-0.5">
                            <label class="font-weight-bold text-dark mb-0" style="font-size: 0.78rem;">Hubungkan ke Kertas Kerja (BOS)</label>
                            <span class="badge badge-secondary d-none" id="edit-badge-non-budgeted">
                                <i class="fas fa-shield-alt mr-1"></i>Non-Anggaran BOS (Internal)
                            </span>
                        </div>
                        <div id="edit-wrapper-kertas-kerja">
                            <select name="bos_kertas_kerja_item_id" id="edit_bos_kertas_kerja_item_id" class="form-control form-control-sm" style="font-size: 0.8rem;">
                                <option value="">-- Tidak Dihubungkan / Non-BOS --</option>
                                @php
                                    $groupedKkEdit = $kertasKerjaItems->groupBy(function($item) {
                                        $code = rtrim($item->program_code ?? '', '.');
                                        return ($code ? $code . ' ' : '') . ($item->kegiatan ?? 'Kegiatan Utilitas');
                                    });
                                @endphp
                                @foreach($groupedKkEdit as $groupLabel => $groupItems)
                                    <optgroup label="{{ $groupLabel }}">
                                        @foreach($groupItems as $kki)
                                            @php
                                                $totalVal = (float) ($kki->total_amount ?: ($kki->price * ($kki->volume ?: 1)));
                                            @endphp
                                            <option value="{{ $kki->id }}" 
                                                data-profile-id="{{ $kki->utility_profile_id }}" 
                                                data-month="{{ $kki->month }}" 
                                                data-category="{{ str_contains(strtolower($kki->kegiatan), 'listrik') ? 'listrik' : (str_contains(strtolower($kki->kegiatan), 'internet') ? 'internet' : 'telepon') }}"
                                                data-price="{{ $kki->price }}"
                                                data-total="{{ $totalVal }}"
                                                data-desc="{{ $kki->description }}"
                                                data-kegiatan="{{ $kki->kegiatan }}">
                                                [{{ $kki->month }}] {{ $kki->description }} (Rp {{ number_format($totalVal, 0, ',', '.') }})
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-0.5" style="font-size: 0.7rem;">
                                <i class="fas fa-info-circle text-info mr-1"></i>Hanya menampilkan kegiatan Kertas Kerja: <strong>Pembayaran daya listrik</strong>, <strong>biaya telepon</strong>, dan <strong>jasa internet</strong>.
                            </small>
                        </div>
                        <div class="alert alert-secondary py-1 px-2 mb-0 mt-1 d-none" id="edit-alert-non-budgeted" style="font-size: 0.74rem;">
                            <i class="fas fa-info-circle text-info mr-1"></i> Objek tagihan ini ditandai sebagai <strong>Non-Anggaran BOS</strong> (pembukuan internal). Tidak dapat dimasukkan ke Kertas Kerja BOS.
                        </div>
                    </div>

                    <div class="row g-2 mb-2.5">
                        <div class="col-sm-6 form-group mb-0">
                            <label class="font-weight-500 text-muted mb-0.5" style="font-size: 0.75rem;">Tanggal Bayar <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="transaction_date" id="edit_transaction_date" class="form-control form-control-sm font-tabular" required>
                        </div>
                        <div class="col-sm-3 form-group mb-0">
                            <label class="font-weight-500 text-muted mb-0.5" style="font-size: 0.75rem;">Tahun <span class="text-danger">*</span></label>
                            <input type="number" name="fiscal_year" id="edit_fiscal_year" class="form-control form-control-sm font-tabular font-weight-bold" required>
                        </div>
                        <div class="col-sm-3 form-group mb-0">
                            <label class="font-weight-500 text-muted mb-0.5" style="font-size: 0.75rem;">Bulan <span class="text-danger">*</span></label>
                            <input type="text" name="billing_month" id="edit_billing_month" class="form-control form-control-sm font-weight-bold" required>
                        </div>
                    </div>

                    <!-- Bagian 2: Identitas Pelanggan & Meter -->
                    <div class="d-flex align-items-center mb-2 pb-1 border-bottom">
                        <span class="font-weight-bold text-dark text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;" id="edit-header-section-2">2. Identitas Pelanggan & Meter</span>
                    </div>
                    <div class="p-2.5 bg-white rounded border mb-2.5">
                        <div class="row g-2 mb-1.5">
                            <div class="col-sm-6 form-group mb-0" id="edit-group-meter-number">
                                <label class="font-weight-500 text-muted mb-0.5" style="font-size: 0.73rem;" id="edit-label-meter-number">No. Meter (Prabayar)</label>
                                <input type="text" name="meter_number" id="edit_meter_number" class="form-control form-control-sm font-tabular" placeholder="Nomor Meter">
                            </div>
                            <div class="col-sm-6 form-group mb-0" id="edit-group-customer-number">
                                <label class="font-weight-500 text-muted mb-0.5" style="font-size: 0.73rem;" id="edit-label-customer-number">IDPEL / No. Rekening</label>
                                <input type="text" name="customer_number" id="edit_customer_number" class="form-control form-control-sm font-tabular" placeholder="ID Pelanggan">
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col form-group mb-0" id="edit-group-customer-name">
                                <label class="font-weight-500 text-muted mb-0.5" style="font-size: 0.73rem;" id="edit-label-customer-name">Nama Pelanggan di Struk</label>
                                <input type="text" name="customer_name" id="edit_customer_name" class="form-control form-control-sm font-weight-bold">
                            </div>
                            <div class="col-sm-3 form-group mb-0" id="edit-group-tariff-power">
                                <label class="font-weight-500 text-muted mb-0.5" style="font-size: 0.73rem;" id="edit-label-tariff-power">Tarif / Daya</label>
                                <input type="text" name="tariff_power" id="edit_tariff_power" class="form-control form-control-sm">
                            </div>
                            <div class="col-sm-3 form-group mb-0" id="edit-group-kwh-amount">
                                <label class="font-weight-500 text-muted mb-0.5" style="font-size: 0.73rem;" id="edit-label-kwh-amount">Jml kWh</label>
                                <input type="number" step="0.01" name="kwh_amount" id="edit_kwh_amount" class="form-control form-control-sm font-tabular" placeholder="0.00">
                            </div>
                        </div>
                    </div>

                    <!-- Bagian 3: Khusus Token Prabayar -->
                    <div class="form-group mb-2.5 p-2.5 rounded" id="edit-box-token-info" style="background-color: #fffbeb; border: 1px solid #fde68a;">
                        <div class="d-flex align-items-center mb-1">
                            <span class="font-weight-bold text-dark text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Token Listrik Prabayar (Stroom)</span>
                        </div>
                        <input type="text" name="token_number" id="edit_token_number" class="form-control font-weight-bold font-monospace text-dark token-input" placeholder="XXXX-XXXX-XXXX-XXXX-XXXX" maxlength="24">
                    </div>

                    <!-- Bagian 4: Rincian Biaya & Pembayaran -->
                    <div class="d-flex align-items-center mb-2 pb-1 border-bottom">
                        <span class="font-weight-bold text-dark text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">3. Rincian Biaya & Pembayaran</span>
                    </div>
                    <div class="p-2.5 bg-white rounded border mb-2.5">
                        <div class="row g-2">
                            <div class="col-sm-4 form-group mb-1" id="edit-group-base-amount">
                                <label class="font-weight-500 text-muted mb-0.5" style="font-size: 0.72rem;" id="edit-label-base-amount">Nilai Pokok / Transfer</label>
                                <input type="text" name="base_amount" id="edit_base_amount" class="form-control form-control-sm font-tabular edit-calc-amount rupiah-input text-right" placeholder="0">
                            </div>
                            <div class="col-sm-4 form-group mb-1" id="edit-group-admin-fee">
                                <label class="font-weight-500 text-muted mb-0.5" style="font-size: 0.72rem;" id="edit-label-admin-fee">Admin Fee</label>
                                <input type="text" name="admin_fee" id="edit_admin_fee" class="form-control form-control-sm font-tabular edit-calc-amount rupiah-input text-right" placeholder="0">
                            </div>
                            <div class="col-sm-4 form-group mb-1" id="edit-group-tax-pbjt">
                                <label class="font-weight-500 text-muted mb-0.5" style="font-size: 0.72rem;" id="edit-label-tax-pbjt">Pajak PBJT</label>
                                <input type="text" name="tax_pbjt" id="edit_tax_pbjt" class="form-control form-control-sm font-tabular edit-calc-amount rupiah-input text-right" placeholder="0">
                            </div>
                            <div class="col-12 form-group mb-0">
                                <label class="font-weight-bold text-primary mb-0.5" style="font-size: 0.8rem;" id="edit-label-total-amount">TOTAL RP BAYAR <span class="text-danger">*</span></label>
                                <input type="text" name="total_amount" id="edit_total_amount" class="form-control form-control-sm font-weight-bold font-tabular text-success rupiah-input text-right" required placeholder="0" style="font-size: 1rem;">
                            </div>
                        </div>
                    </div>

                    <!-- Bagian 5: Identitas Bukti Transaksi -->
                    <div class="d-flex align-items-center mb-2 pb-1 border-bottom">
                        <span class="font-weight-bold text-dark text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">4. Identitas Bukti Transaksi</span>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-sm-6 form-group mb-1">
                            <label class="font-weight-500 text-muted mb-0.5" style="font-size: 0.75rem;">No. Resi</label>
                            <input type="text" name="receipt_number" id="edit_receipt_number" class="form-control form-control-sm font-monospace" placeholder="No Resi">
                        </div>
                        <div class="col-sm-6 form-group mb-1">
                            <label class="font-weight-500 text-muted mb-0.5" style="font-size: 0.75rem;">Loket / Merchant / Bank</label>
                            <input type="text" name="payment_merchant" id="edit_payment_merchant" class="form-control form-control-sm" placeholder="Contoh: PosPay, BRI, Tokopedia">
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <label class="font-weight-500 text-muted mb-0.5" style="font-size: 0.75rem;">Catatan</label>
                        <textarea name="notes" id="edit_notes" class="form-control form-control-sm" rows="1" placeholder="Catatan tambahan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-white py-2 px-3">
                    <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal" data-bs-dismiss="modal" onclick="window.hideModal('#modalEditReceipt')">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary font-weight-bold">
                        <i class="fas fa-save mr-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 3: Lightbox View Full Receipt Image -->
<div class="modal fade" id="modalImageViewer" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content bg-transparent border-0 shadow-none">
            <div class="modal-header border-0 py-1 px-2 text-white">
                <h6 class="modal-title font-weight-bold text-white" id="lightbox-title">Foto Struk</h6>
                <button type="button" class="btn-close btn-close-white" data-dismiss="modal" data-bs-dismiss="modal" onclick="window.hideModal('#modalImageViewer')" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 text-center">
                <img id="lightbox-img" src="" alt="Struk Besar" class="img-fluid rounded shadow-lg" style="max-height: 85vh; background: #fff;">
            </div>
        </div>
    </div>
</div>

<!-- Modal 4: Detail Modal -->
<div class="modal fade" id="modalDetailReceipt" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header bg-white py-2.5 px-3.5 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="modal-title font-weight-bold text-dark mb-0">
                    <i class="fas fa-eye text-info mr-1.5"></i> Detail Bukti Pembayaran Utilitas
                </h6>
                <button type="button" class="btn-close text-muted close" data-dismiss="modal" data-bs-dismiss="modal" onclick="window.hideModal('#modalDetailReceipt')" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3.5 bg-light" id="detail-receipt-body"></div>
            <div class="modal-footer bg-white py-2 px-3">
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal" data-bs-dismiss="modal" onclick="window.hideModal('#modalDetailReceipt')">Tutup</button>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js" async></script>
<script>
// Modal Helpers for AdminLTE / Bootstrap
window.showModal = function(selector) {
    if (window.jQuery && typeof $(selector).modal === 'function') {
        $(selector).modal('show');
    } else if (window.bootstrap && typeof window.bootstrap.Modal !== 'undefined') {
        let el = typeof selector === 'string' ? document.querySelector(selector) : selector;
        if (el) bootstrap.Modal.getOrCreateInstance(el).show();
    } else {
        let el = typeof selector === 'string' ? document.querySelector(selector) : selector;
        if (el) {
            el.classList.add('show');
            el.style.display = 'block';
        }
    }
};

window.hideModal = function(selector) {
    if (selector === '#modalScanReceipt' && window.activeOcrRequest) {
        try { window.activeOcrRequest.abort(); } catch(e) {}
        window.activeOcrRequest = null;
        $('#scan-loading-spinner').removeClass('d-flex').addClass('d-none');
    }

    if (window.jQuery && typeof $(selector).modal === 'function') {
        $(selector).modal('hide');
    } else if (window.bootstrap && typeof window.bootstrap.Modal !== 'undefined') {
        let el = typeof selector === 'string' ? document.querySelector(selector) : selector;
        if (el) bootstrap.Modal.getOrCreateInstance(el).hide();
    } else {
        let el = typeof selector === 'string' ? document.querySelector(selector) : selector;
        if (el) {
            el.classList.remove('show');
            el.style.display = 'none';
        }
    }
};

window.activateUploadTab = function(tabName) {
    if (tabName === 'single') {
        $('#tab-single-btn').addClass('active text-success').removeClass('text-muted');
        $('#tab-batch-btn').removeClass('active text-primary').addClass('text-muted');
        $('#tab-single').addClass('show active');
        $('#tab-batch').removeClass('show active');
        if (typeof $.fn.tab === 'function') {
            $('#tab-single-btn').tab('show');
        }
    } else {
        $('#tab-batch-btn').addClass('active text-primary').removeClass('text-muted');
        $('#tab-single-btn').removeClass('active text-success').addClass('text-muted');
        $('#tab-batch').addClass('show active');
        $('#tab-single').removeClass('show active');
        if (typeof $.fn.tab === 'function') {
            $('#tab-batch-btn').tab('show');
        }
    }
};

window.openUploadModal = function(mode, profileId) {
    if (typeof window.resetUploadModal === 'function') {
        window.resetUploadModal();
    }
    if (mode === 'batch') {
        window.activateUploadTab('batch');
    } else {
        $('#select-ocr-engine').val('local');
        window.activateUploadTab('single');
        if (profileId) {
            $('#field_utility_profile_id').val(profileId).trigger('change');
        }
    }
    window.showModal('#modalScanReceipt');
};

// ==========================================
// FORMATTER & SEPARATOR HELPERS
// ==========================================
window.parseMoneyValue = function(val) {
    if (val === null || val === undefined || val === '') return 0;
    if (typeof val === 'number') return Math.round(val);
    let str = String(val).trim();
    if (/^\d+\.\d{1,2}$/.test(str)) {
        return Math.round(parseFloat(str));
    }
    let clean = str.replace(/[^0-9]/g, '');
    return clean ? parseInt(clean, 10) : 0;
};

window.formatMoneyValue = function(val) {
    if (val === null || val === undefined || val === '') return '';
    let num;
    if (typeof val === 'number') {
        num = Math.round(val);
    } else {
        let str = String(val).trim();
        if (/^\d+\.\d{1,2}$/.test(str)) {
            num = Math.round(parseFloat(str));
        } else {
            let clean = str.replace(/[^0-9]/g, '');
            num = clean ? parseInt(clean, 10) : 0;
        }
    }
    return isNaN(num) ? '' : num.toLocaleString('id-ID');
};

window.formatTokenNumber = function(val) {
    if (!val) return '';
    let digits = String(val).replace(/[^0-9]/g, '').slice(0, 20);
    let parts = digits.match(/.{1,4}/g);
    return parts ? parts.join('-') : '';
};

window.redirectToReceiptPeriod = function(monthVal, yearVal, transDate = null) {
    const monthMap = {
        'januari': '01', 'februari': '02', 'maret': '03', 'april': '04',
        'mei': '05', 'juni': '06', 'juli': '07', 'agustus': '08',
        'september': '09', 'oktober': '10', 'november': '11', 'desember': '12',
        'jan': '01', 'feb': '02', 'mar': '03', 'apr': '04', 'may': '05',
        'jun': '06', 'jul': '07', 'aug': '08', 'sep': '09', 'oct': '10',
        'nov': '11', 'dec': '12',
        '1': '01', '2': '02', '3': '03', '4': '04', '5': '05', '6': '06',
        '7': '07', '8': '08', '9': '09', '10': '10', '11': '11', '12': '12',
        '01': '01', '02': '02', '03': '03', '04': '04', '05': '05', '06': '06',
        '07': '07', '08': '08', '09': '09'
    };

    let mCode = '';
    if (monthVal) {
        let cleanM = String(monthVal).trim().toLowerCase();
        mCode = monthMap[cleanM] || '';
    }

    if (!mCode && transDate) {
        let dt = new Date(transDate);
        if (!isNaN(dt.getTime())) {
            let mNum = dt.getMonth() + 1;
            mCode = mNum < 10 ? '0' + mNum : String(mNum);
        }
    }

    if (!mCode) {
        let tDate = $('#field_transaction_date').val();
        if (tDate) {
            let dt = new Date(tDate);
            if (!isNaN(dt.getTime())) {
                let mNum = dt.getMonth() + 1;
                mCode = mNum < 10 ? '0' + mNum : String(mNum);
            }
        }
    }

    let targetUrl = new URL(window.location.origin + window.location.pathname);
    if (yearVal && yearVal !== 'all') {
        targetUrl.searchParams.set('year', yearVal);
    } else {
        let currentYr = (new URL(window.location.href)).searchParams.get('year');
        if (currentYr && currentYr !== 'all') {
            targetUrl.searchParams.set('year', currentYr);
        }
    }

    if (mCode) {
        targetUrl.searchParams.set('month', mCode);
    }

    window.location.href = targetUrl.toString();
};

// Live thousand separator formatter for Rupiah inputs
$(document).on('input', '.rupiah-input', function() {
    let input = this;
    let originalValue = input.value;
    let originalSelection = input.selectionStart;
    let originalLength = originalValue.length;

    let rawVal = originalValue.replace(/[^0-9]/g, '');
    let formatted = rawVal ? parseInt(rawVal, 10).toLocaleString('id-ID') : '';

    input.value = formatted;

    let newLength = formatted.length;
    let newSelection = originalSelection + (newLength - originalLength);
    if (newSelection >= 0 && newSelection <= newLength) {
        input.setSelectionRange(newSelection, newSelection);
    }
});

// Live 4-digit dash separator formatter for Token inputs (XXXX-XXXX-XXXX-XXXX-XXXX)
$(document).on('input', '.token-input, #field_token_number, #edit_token_number', function() {
    let input = this;
    let originalValue = input.value;
    let originalSelection = input.selectionStart;
    let digitsBeforeCursor = originalValue.slice(0, originalSelection).replace(/[^0-9]/g, '').length;

    let raw = originalValue.replace(/[^0-9]/g, '').slice(0, 20);
    let parts = raw.match(/.{1,4}/g) || [];
    let formatted = parts.join('-');
    input.value = formatted;

    let newCursor = 0;
    let digitCount = 0;
    while (newCursor < formatted.length && digitCount < digitsBeforeCursor) {
        if (formatted[newCursor] !== '-') {
            digitCount++;
        }
        newCursor++;
    }
    input.setSelectionRange(newCursor, newCursor);
});

window.findBestKertasKerjaOption = function(selectId, category, month, totalAmount) {
    let select = $(selectId);
    if (!month || select.length === 0) return null;

    let cleanMonth = String(month).trim().toLowerCase();
    const numToMonth = {
        '01': 'januari', '02': 'februari', '03': 'maret', '04': 'april',
        '05': 'mei', '06': 'juni', '07': 'juli', '08': 'agustus',
        '09': 'september', '10': 'oktober', '11': 'november', '12': 'desember',
        '1': 'januari', '2': 'februari', '3': 'maret', '4': 'april',
        '5': 'mei', '6': 'juni', '7': 'juli', '8': 'agustus',
        '9': 'september',
        'jan': 'januari', 'feb': 'februari', 'mar': 'maret', 'apr': 'april',
        'may': 'mei', 'jun': 'juni', 'jul': 'juli', 'aug': 'agustus',
        'sep': 'september', 'oct': 'oktober', 'nov': 'november', 'dec': 'desember'
    };
    if (numToMonth[cleanMonth]) {
        cleanMonth = numToMonth[cleanMonth];
    }

    let targetCat = 'listrik';
    if (category === 'internet') {
        targetCat = 'internet';
    } else if (category === 'telkom_speedy') {
        targetCat = 'telepon';
    } else if (category === 'listrik_pra' || category === 'listrik_pasca') {
        targetCat = 'listrik';
    }

    let parsedAmount = typeof parseMoneyValue === 'function' ? parseMoneyValue(totalAmount || 0) : parseFloat(String(totalAmount || 0).replace(/[^0-9]/g, ''));

    let candidates = [];
    select.find('option').each(function() {
        let optVal = $(this).val();
        if (!optVal) return;

        let optMonth = String($(this).data('month') || '').trim().toLowerCase();
        if (optMonth !== cleanMonth) return;

        let optCat = $(this).data('category') || '';
        let optKegiatan = String($(this).data('kegiatan') || '').toLowerCase();
        let optDesc = String($(this).data('desc') || '').toLowerCase();
        let optTotal = parseFloat($(this).data('total')) || 0;

        let matchScore = 0;
        if (targetCat === 'internet') {
            if (optCat === 'internet' || optKegiatan.includes('internet')) matchScore += 10;
        } else if (targetCat === 'telepon') {
            if (optCat === 'telepon' || optKegiatan.includes('telepon')) matchScore += 10;
            if (optDesc.includes('telepon') || optDesc.includes('speedy')) matchScore += 5;
            if (optCat === 'internet' && optDesc.includes('telepon')) matchScore += 8;
        } else if (targetCat === 'listrik') {
            if (optCat === 'listrik' || optKegiatan.includes('listrik')) matchScore += 10;
        }

        if (matchScore > 0) {
            let diff = Math.abs(optTotal - parsedAmount);
            candidates.push({
                val: optVal,
                score: matchScore,
                diff: diff,
                total: optTotal
            });
        }
    });

    if (candidates.length === 0) return null;

    candidates.sort(function(a, b) {
        if (b.score !== a.score) return b.score - a.score;
        return a.diff - b.diff;
    });

    return candidates[0].val;
};

$(document).ready(function() {
    let currentCategoryFilter = 'all';
    let currentProfileFilter = '';
    let batchSelectedFiles = [];
    let pollingTimer = null;
    window.activeOcrRequest = null;

    // Delegasi Dismiss Modal
    $(document).on('click', '[data-dismiss="modal"], [data-bs-dismiss="modal"], .btn-close, .btn-dismiss-modal', function(e) {
        let modal = $(this).closest('.modal');
        if (modal.length) {
            window.hideModal(modal[0]);
        }
    });

    // Inisialisasi DataTable
    const tableReceipts = $('#table-receipts').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('bos.utilitas.data', [], false) }}",
            data: function(d) {
                d.year = "{{ $activeFiscalYear }}";
                d.month = "{{ $selectedMonth }}";
                d.category = currentCategoryFilter;
                d.profile_id = currentProfileFilter;
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center font-tabular text-muted' },
            { data: 'date_display', name: 'transaction_date' },
            { data: 'profile_display', name: 'profile.name' },
            { data: 'account_display', name: 'customer_number' },
            { data: 'tariff_display', name: 'tariff_power' },
            { data: 'breakdown_display', name: 'base_amount' },
            { data: 'total_display', name: 'total_amount', className: 'text-right' },
            { data: 'token_display', name: 'token_number' },
            { data: 'status_display', name: 'ocr_status', className: 'text-center' },
            { data: 'receipt_image_display', name: 'receipt_file', orderable: false, searchable: false, className: 'text-center' },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[1, 'desc']],
        pageLength: 15,
        language: {
            processing: '<i class="fas fa-spinner fa-spin mr-1"></i> Memuat data...',
            emptyTable: 'Belum ada bukti pembayaran utilitas yang tersimpan untuk periode ini.',
            zeroRecords: 'Tidak ditemukan data yang sesuai kriteria pencarian.',
            info: 'Menampilkan _START_ s/d _END_ dari _TOTAL_ struk',
            infoEmpty: 'Menampilkan 0 data',
            search: 'Cari Struk:',
            paginate: {
                first: '<i class="fas fa-angle-double-left"></i>',
                last: '<i class="fas fa-angle-double-right"></i>',
                next: '<i class="fas fa-angle-right"></i>',
                previous: '<i class="fas fa-angle-left"></i>'
            }
        },
        drawCallback: function() {
            checkBackgroundJobs();
        }
    });

    // Kategori Filter Tab Click
    $('.filter-category-btn').on('click', function() {
        $('.filter-category-btn').removeClass('active btn-primary').addClass('btn-outline-secondary');
        $(this).removeClass('btn-outline-secondary').addClass('active btn-primary');
        currentCategoryFilter = $(this).data('cat');
        tableReceipts.draw();
    });

    // Profile Filter Change
    $('#filter-profile').on('change', function() {
        currentProfileFilter = $(this).val();
        tableReceipts.draw();
    });

    $('#btn-reload-table').on('click', function() {
        tableReceipts.draw();
        pollServerStatus();
    });

    $('#tab-single-btn').on('click', function(e) {
        e.preventDefault();
        window.activateUploadTab('single');
    });

    $('#tab-batch-btn').on('click', function(e) {
        e.preventDefault();
        window.activateUploadTab('batch');
    });

    // Delegated open modal handlers
    $(document).on('click', '#btn-open-ocr', function(e) {
        e.preventDefault();
        window.openUploadModal('single');
    });

    $(document).on('click', '#btn-open-ai', function(e) {
        e.preventDefault();
        window.openUploadModal('batch');
    });

    $(document).on('click', '#btn-open-upload', function(e) {
        e.preventDefault();
        window.openUploadModal('single');
    });

    $(document).on('click', '.btn-quick-upload-profile', function(e) {
        e.preventDefault();
        let pId = $(this).data('profile-id');
        window.openUploadModal('single', pId);
    });

    // ==========================================
    // BATCH UPLOAD LOGIC
    // ==========================================
    const $batchDropzone = $('#batch-dropzone');
    $batchDropzone.on('dragover dragenter', function(e) {
        e.preventDefault(); e.stopPropagation();
        $batchDropzone.addClass('dragover');
    });
    $batchDropzone.on('dragleave dragend drop', function(e) {
        e.preventDefault(); e.stopPropagation();
        $batchDropzone.removeClass('dragover');
    });
    $batchDropzone.on('drop', function(e) {
        let files = e.originalEvent.dataTransfer.files;
        if (files && files.length > 0) {
            addBatchFiles(files);
        }
    });

    $('#batch_file_input').on('change', function() {
        if (this.files && this.files.length > 0) {
            addBatchFiles(this.files);
        }
    });

    function addBatchFiles(fileList) {
        for (let i = 0; i < fileList.length; i++) {
            let f = fileList[i];
            if (f.type.match('image.*') || f.type === 'application/pdf') {
                batchSelectedFiles.push(f);
            }
        }
        renderBatchFilesList();
    }

    function renderBatchFilesList() {
        let listContainer = $('#batch-files-list');
        listContainer.empty();

        if (batchSelectedFiles.length === 0) {
            $('#batch-files-container').addClass('d-none');
            $('#btn-submit-batch').prop('disabled', true);
            return;
        }

        $('#batch-files-container').removeClass('d-none');
        $('#btn-submit-batch').prop('disabled', false);
        $('#batch-file-count-label').text(`Berkas Terpilih (${batchSelectedFiles.length}):`);

        batchSelectedFiles.forEach((file, index) => {
            let sizeKb = Math.round(file.size / 1024);
            let itemHtml = `
                <div class="batch-file-item">
                    <div class="d-flex align-items-center" style="gap: 8px; overflow: hidden;">
                        <i class="fas fa-file-image text-primary" style="font-size: 1.1rem;"></i>
                        <div class="text-truncate">
                            <strong class="d-block text-dark text-truncate" style="font-size: 0.8rem;">${file.name}</strong>
                            <small class="text-muted font-tabular">${sizeKb} KB</small>
                        </div>
                    </div>
                    <button type="button" class="btn btn-xs btn-outline-danger py-0 px-1.5 btn-remove-batch-file" data-index="${index}" title="Hapus berkas ini">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            listContainer.append(itemHtml);
        });
    }

    $(document).on('click', '.btn-remove-batch-file', function() {
        let index = $(this).data('index');
        batchSelectedFiles.splice(index, 1);
        renderBatchFilesList();
    });

    $('#btn-clear-batch-files').on('click', function() {
        batchSelectedFiles = [];
        $('#batch_file_input').val('');
        renderBatchFilesList();
    });

    // Submit Batch Upload
    $('#btn-submit-batch').on('click', function() {
        if (batchSelectedFiles.length === 0) return;

        let btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Mengunggah berkas...');

        let formData = new FormData($('#form-batch-upload')[0]);
        formData.delete('receipt_files[]');
        batchSelectedFiles.forEach(file => {
            formData.append('receipt_files[]', file);
        });

        $.ajax({
            url: "{{ route('bos.utilitas.batch-upload', [], false) }}",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                btn.prop('disabled', false).html('<i class="fas fa-cloud-arrow-up mr-1"></i> Simpan & Mulai Proses AI di Latar Belakang');
                window.hideModal('#modalScanReceipt');
                batchSelectedFiles = [];
                renderBatchFilesList();

                let batchMonth = $('#form-batch-upload select[name="billing_month"]').val();
                let batchYear = $('#form-batch-upload input[name="fiscal_year"]').val();

                Swal.fire({
                    icon: 'success',
                    title: 'Upload Batch Berhasil!',
                    text: res.message || 'Berkas tersimpan dan AI sedang memproses di latar belakang.',
                    confirmButtonText: 'OK'
                }).then(() => {
                    if (batchMonth) {
                        window.redirectToReceiptPeriod(batchMonth, batchYear);
                    } else {
                        tableReceipts.draw(false);
                        startAutoPolling();
                    }
                });
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fas fa-cloud-arrow-up mr-1"></i> Simpan & Mulai Proses AI di Latar Belakang');
                let msg = xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan saat mengunggah batch.';
                Swal.fire('Gagal Upload', msg, 'error');
            }
        });
    });

    // ==========================================
    // AUTO POLLING & REAL-TIME STATUS LOGIC
    // ==========================================
    function checkBackgroundJobs() {
        let hasPendingBadges = $('#table-receipts').find('.badge-warning, .fa-spin').length > 0;
        if (hasPendingBadges) {
            startAutoPolling();
        }
    }

    function startAutoPolling() {
        if (pollingTimer) return;
        $('#banner-bg-processing').removeClass('d-none').addClass('d-flex');

        pollingTimer = setInterval(pollServerStatus, 5000);
        pollServerStatus(); // Run first check immediately
    }

    function stopAutoPolling() {
        if (pollingTimer) {
            clearInterval(pollingTimer);
            pollingTimer = null;
        }
        $('#banner-bg-processing').removeClass('d-flex').addClass('d-none');
    }

    function pollServerStatus() {
        $.ajax({
            url: "{{ route('bos.utilitas.poll-status', [], false) }}",
            type: "GET",
            data: {
                year: "{{ $activeFiscalYear }}",
                month: "{{ $selectedMonth }}"
            },
            success: function(res) {
                if (res.success) {
                    if (res.has_pending) {
                        $('#banner-bg-processing').removeClass('d-none').addClass('d-flex');
                        $('#banner-bg-text').text(`AI Sedang Memproses ${res.pending_count} Struk di Latar Belakang...`);
                    } else {
                        stopAutoPolling();
                    }

                    if (res.matrix) {
                        $('#badge-compliance-count').html(`Kepatuhan: <strong>${res.matrix.paid_count}</strong> dari ${res.matrix.total_profiles} Terpenuhi`);
                        $('#badge-month-total').text(`Total Bulan Ini: ${res.matrix.month_total}`);
                    }
                }
            }
        });
    }

    $('#btn-manual-poll-refresh').on('click', function() {
        pollServerStatus();
        tableReceipts.draw(false);
    });

    // Retry OCR Scan
    $(document).on('click', '.btn-retry-ocr', function() {
        let id = $(this).data('id');
        let icon = $(this).find('i');
        icon.addClass('fa-spin');

        $.ajax({
            url: "{{ route('bos.utilitas.index', [], false) }}/" + id + "/retry-ocr",
            type: "POST",
            data: { _token: '{{ csrf_token() }}' },
            success: function(res) {
                Swal.fire({
                    icon: 'success',
                    title: 'Dijadwalkan Ulang',
                    text: 'Pemindaian struk telah dikirim ke antrean background job.',
                    timer: 1800,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
                tableReceipts.draw(false);
                startAutoPolling();
            },
            error: function(xhr) {
                icon.removeClass('fa-spin');
                let msg = xhr.responseJSON ? xhr.responseJSON.message : 'Gagal menjadwalkan ulang OCR.';
                Swal.fire('Gagal', msg, 'error');
            }
        });
    });

    // ==========================================
    // SINGLE SCAN & DRAG DROP
    // ==========================================
    const $dropzone = $('#box-upload-zone');
    $dropzone.on('dragover dragenter', function(e) {
        e.preventDefault(); e.stopPropagation();
        $dropzone.addClass('dragover');
    });
    $dropzone.on('dragleave dragend drop', function(e) {
        e.preventDefault(); e.stopPropagation();
        $dropzone.removeClass('dragover');
    });
    $dropzone.on('drop', function(e) {
        let files = e.originalEvent.dataTransfer.files;
        if (files && files.length > 0) {
            handleSelectedFile(files[0]);
        }
    });

    $('#receipt_file_input').on('change', function() {
        if (this.files && this.files.length > 0) {
            handleSelectedFile(this.files[0]);
        }
    });

    function adjustFormFieldsByCategory(cat) {
        cat = cat || 'listrik_pra';

        if (cat === 'internet') {
            // 1. Identitas Rekening Transfer
            $('#header-section-2').text('2. Identitas Rekening Transfer');
            $('#group-meter-number').addClass('d-none');
            $('#group-tariff-power').addClass('d-none');
            $('#group-customer-number').removeClass('d-none col-12').addClass('col-sm-6 mb-0');
            $('#group-customer-name').removeClass('d-none col-12').addClass('col-sm-6 mb-0');

            $('#label-customer-number').text('No. Rekening Tujuan / IDPEL');
            $('#field_customer_number').attr('placeholder', 'Contoh: 3243258444 (BCA Menaksopal)');
            $('#label-customer-name').text('Nama Penerima Transfer di Struk');
            $('#field_customer_name').attr('placeholder', 'Contoh: MENAKSOPAL LINK NUSANTAR');

            // 2. Token Listrik Prabayar (Sembunyikan)
            $('#box-token-info').addClass('d-none');

            // 3. Rincian Biaya & Pembayaran (PBJT Sembunyikan, Pokok & Admin 50% masing-masing)
            $('#group-tax-pbjt').addClass('d-none');
            $('#group-base-amount').removeClass('col-sm-4').addClass('col-sm-6');
            $('#group-admin-fee').removeClass('col-sm-4').addClass('col-sm-6');

            $('#label-base-amount').text('Nominal Pokok Transfer (Rp)');
            $('#label-admin-fee').text('Biaya Admin Transfer (Rp)');
            $('#label-total-amount').html('TOTAL RP TRANSFER (DIBAYARKAN) <span class="text-danger">*</span>');

            // 4. Identitas Bukti Transaksi
            $('#label-receipt-number').text('No. Ref / No. Transaksi ATM');
            $('#field_receipt_number').attr('placeholder', 'Contoh: 07205 / ATM ID 91461');
            $('#label-reference-number').text('No. Jurnal / Referensi');
            $('#field_reference_number').attr('placeholder', 'Contoh: 128492');
            $('#label-payment-merchant').text('Bank / Lokasi ATM Pengirim');
            $('#field_payment_merchant').attr('placeholder', 'Contoh: BRI UNIT KRAS (Link)');

        } else if (cat === 'telkom_speedy') {
            // 1. Identitas Pelanggan & Telepon
            $('#header-section-2').text('2. Identitas Pelanggan & Telepon');
            $('#group-meter-number').addClass('d-none');
            $('#group-tariff-power').addClass('d-none');
            $('#group-customer-number').removeClass('d-none col-12').addClass('col-sm-6 mb-0');
            $('#group-customer-name').removeClass('d-none col-12').addClass('col-sm-6 mb-0');

            $('#label-customer-number').text('No. Telepon / IDPEL Telkom');
            $('#field_customer_number').attr('placeholder', 'Contoh: 0354-000479487');
            $('#label-customer-name').text('Nama Pelanggan di Struk');
            $('#field_customer_name').attr('placeholder', 'Contoh: IMAM SJADELI / SMK PGRI KRAS');

            // 2. Token Listrik Prabayar (Sembunyikan)
            $('#box-token-info').addClass('d-none');

            // 3. Rincian Biaya & Pembayaran (PBJT Sembunyikan, Pokok & Admin 50% masing-masing)
            $('#group-tax-pbjt').addClass('d-none');
            $('#group-base-amount').removeClass('col-sm-4').addClass('col-sm-6');
            $('#group-admin-fee').removeClass('col-sm-4').addClass('col-sm-6');

            $('#label-base-amount').text('Tagihan Pokok Telkom (Rp)');
            $('#label-admin-fee').text('Biaya Admin Pos/Bank (Rp)');
            $('#label-total-amount').html('TOTAL RP BAYAR (DIBAYARKAN) <span class="text-danger">*</span>');

            // 4. Identitas Bukti Transaksi
            $('#label-receipt-number').text('Nomor Resi / Struk');
            $('#field_receipt_number').attr('placeholder', 'Contoh: 64172-03/2026/...');
            $('#label-reference-number').text('No Referensi Transaksi');
            $('#field_reference_number').attr('placeholder', 'No Ref');
            $('#label-payment-merchant').text('Loket / Merchant');
            $('#field_payment_merchant').attr('placeholder', 'Contoh: PT POS INDONESIA Kpc. Kras');

        } else if (cat === 'listrik_pasca') {
            // 1. Identitas Pelanggan (Tanpa Meter)
            $('#header-section-2').text('2. Identitas Pelanggan');
            $('#group-meter-number').addClass('d-none');
            $('#group-customer-number').removeClass('d-none col-12').addClass('col-sm-6 mb-1');
            $('#group-tariff-power').removeClass('d-none col-12').addClass('col-sm-6 mb-1');
            $('#group-customer-name').removeClass('d-none col-sm-6').addClass('col-12 mb-0');

            $('#label-customer-number').text('ID Pelanggan (IDPEL)');
            $('#field_customer_number').attr('placeholder', 'Contoh: 514031084530');
            $('#label-tariff-power').text('Tarif / Daya');
            $('#field_tariff_power').attr('placeholder', 'Contoh: S1/16500 VA');
            $('#label-customer-name').text('Nama Pelanggan di Struk');
            $('#field_customer_name').attr('placeholder', 'Contoh: STM / MASJID AN NUR');

            // 2. Token Listrik Prabayar (Sembunyikan)
            $('#box-token-info').addClass('d-none');

            // 3. Rincian Biaya & Pembayaran (Tampilkan PBJT)
            $('#group-tax-pbjt').removeClass('d-none');
            $('#group-base-amount').removeClass('col-sm-6').addClass('col-sm-4');
            $('#group-admin-fee').removeClass('col-sm-6').addClass('col-sm-4');

            $('#label-base-amount').text('Tagihan Pokok Listrik (Rp)');
            $('#label-admin-fee').text('Biaya Admin Pos/Bank (Rp)');
            $('#label-tax-pbjt').text('Pajak PBJT-TL / PPJ (Rp)');
            $('#label-total-amount').html('TOTAL RP BAYAR (DIBAYARKAN) <span class="text-danger">*</span>');

            // 4. Identitas Bukti Transaksi
            $('#label-receipt-number').text('Nomor Resi / Struk');
            $('#field_receipt_number').attr('placeholder', 'Contoh: 64172-03/2026/...');
            $('#label-reference-number').text('No Referensi Transaksi');
            $('#field_reference_number').attr('placeholder', 'No Ref');
            $('#label-payment-merchant').text('Loket / Merchant');
            $('#field_payment_merchant').attr('placeholder', 'Contoh: PT POS INDONESIA Kpc. Kras');

        } else if (cat === 'listrik_pra') {
            // 1. Identitas Pelanggan & Meter (Lengkap)
            $('#header-section-2').text('2. Identitas Pelanggan & Meter');
            $('#group-meter-number').removeClass('d-none col-12').addClass('col-sm-6 mb-1');
            $('#group-customer-number').removeClass('d-none col-12').addClass('col-sm-6 mb-1');
            $('#group-customer-name').removeClass('d-none col-12').addClass('col-sm-6 mb-0');
            $('#group-tariff-power').removeClass('d-none col-12').addClass('col-sm-6 mb-0');

            $('#label-meter-number').text('Nomor Meter / Serial');
            $('#field_meter_number').attr('placeholder', 'Nomor Meter');
            $('#label-customer-number').text('ID Pelanggan (IDPEL)');
            $('#field_customer_number').attr('placeholder', 'Contoh: 514031897945');
            $('#label-customer-name').text('Nama Pelanggan di Struk');
            $('#field_customer_name').attr('placeholder', 'Contoh: SMK PGRI KRAS');
            $('#label-tariff-power').text('Tarif / Daya');
            $('#field_tariff_power').attr('placeholder', 'Contoh: S1/5500 VA');

            // 2. Token Listrik Prabayar (TAMPILKAN)
            $('#box-token-info').removeClass('d-none');

            // 3. Rincian Biaya & Pembayaran (Tampilkan PBJT)
            $('#group-tax-pbjt').removeClass('d-none');
            $('#group-base-amount').removeClass('col-sm-6').addClass('col-sm-4');
            $('#group-admin-fee').removeClass('col-sm-6').addClass('col-sm-4');

            $('#label-base-amount').text('Nilai Token Pokok / Stroom (Rp)');
            $('#label-admin-fee').text('Biaya Admin Pos/Bank (Rp)');
            $('#label-tax-pbjt').text('Pajak PBJT-TL / PPJ (Rp)');
            $('#label-total-amount').html('TOTAL RP BAYAR (DIBAYARKAN) <span class="text-danger">*</span> <small class="text-muted font-weight-normal">(Sesuai Baris "RP BAYAR" di Struk)</small>');

            // 4. Identitas Bukti Transaksi
            $('#label-receipt-number').text('Nomor Resi / Struk');
            $('#field_receipt_number').attr('placeholder', 'Contoh: 64172-03/2026/...');
            $('#label-reference-number').text('No Referensi Transaksi');
            $('#field_reference_number').attr('placeholder', 'No Ref');
            $('#label-payment-merchant').text('Loket / Merchant');
            $('#field_payment_merchant').attr('placeholder', 'Contoh: PT POS INDONESIA Kpc. Kras');

        } else {
            // Default / Lainnya
            $('#header-section-2').text('2. Identitas Pelanggan');
            $('#group-meter-number').addClass('d-none');
            $('#group-tariff-power').addClass('d-none');
            $('#group-customer-number').removeClass('d-none col-12').addClass('col-sm-6 mb-0');
            $('#group-customer-name').removeClass('d-none col-12').addClass('col-sm-6 mb-0');

            $('#label-customer-number').text('ID Pelanggan (IDPEL / No. Rekening)');
            $('#field_customer_number').attr('placeholder', 'ID Pelanggan');
            $('#label-customer-name').text('Nama Pelanggan di Struk');
            $('#field_customer_name').attr('placeholder', 'Nama Pelanggan');

            $('#box-token-info').addClass('d-none');

            $('#group-tax-pbjt').addClass('d-none');
            $('#group-base-amount').removeClass('col-sm-4').addClass('col-sm-6');
            $('#group-admin-fee').removeClass('col-sm-4').addClass('col-sm-6');

            $('#label-base-amount').text('Tagihan Pokok (Rp)');
            $('#label-admin-fee').text('Biaya Admin (Rp)');
            $('#label-total-amount').html('Total Pembayaran Akhir (Rp) <span class="text-danger">*</span>');

            $('#label-receipt-number').text('Nomor Struk / Seri');
            $('#field_receipt_number').attr('placeholder', 'No Struk');
            $('#label-reference-number').text('No Referensi Transaksi');
            $('#label-payment-merchant').text('Lokasi / Merchant / Loket');
        }

        if (typeof updateAmountVerification === 'function') {
            updateAmountVerification();
        }
    }

    let originalReceiptBase64 = '';
    let enhancedReceiptBase64 = '';
    let isEnhancedActive = false;
    let currentReceiptFile = null;
    let isAutoFlipped180 = false;
    let ocrImageWidth = 0;
    let ocrImageHeight = 0;

    // Deteksi Orientasi EXIF dari berkas JPEG (100% Offline via DataView)
    function getJpegOrientation(file) {
        return new Promise((resolve) => {
            if (!file || !file.type || (!file.type.match('image/jpeg') && !file.type.match('image/jpg'))) {
                resolve(1);
                return;
            }
            let reader = new FileReader();
            let slice = file.slice(0, 65536);
            reader.onload = function(e) {
                try {
                    let view = new DataView(e.target.result);
                    if (view.byteLength < 4 || view.getUint16(0, false) !== 0xFFD8) {
                        resolve(1);
                        return;
                    }
                    let length = view.byteLength;
                    let offset = 2;
                    while (offset < length - 2) {
                        let marker = view.getUint16(offset, false);
                        offset += 2;
                        if (marker === 0xFFE1) {
                            if (offset + 6 > length) break;
                            if (view.getUint32(offset += 2, false) !== 0x45786966) {
                                resolve(1);
                                return;
                            }
                            let little = view.getUint16(offset += 6, false) === 0x4949;
                            offset += view.getUint32(offset + 4, little);
                            if (offset + 2 > length) break;
                            let tags = view.getUint16(offset, little);
                            offset += 2;
                            for (let i = 0; i < tags; i++) {
                                if (offset + (i * 12) + 10 > length) break;
                                if (view.getUint16(offset + (i * 12), little) === 0x0112) {
                                    let orientation = view.getUint16(offset + (i * 12) + 8, little);
                                    resolve(orientation);
                                    return;
                                }
                            }
                            break;
                        } else if ((marker & 0xFF00) !== 0xFF00) {
                            break;
                        } else {
                            if (offset + 2 > length) break;
                            let sectionLen = view.getUint16(offset, false);
                            offset += sectionLen;
                        }
                    }
                } catch (err) {
                    // Abaikan galat pembacaan metadata
                }
                resolve(1);
            };
            reader.onerror = () => resolve(1);
            reader.readAsArrayBuffer(slice);
        });
    }

    function rotateImageBase64(base64, degrees, maxDimension = null) {
        return new Promise((resolve) => {
            if (!base64 || (degrees % 360 === 0 && !maxDimension)) {
                resolve(base64);
                return;
            }
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = () => {
                let targetW = img.width;
                let targetH = img.height;

                if (maxDimension && (targetW > maxDimension || targetH > maxDimension)) {
                    if (targetW > targetH) {
                        targetH = Math.round((targetH * maxDimension) / targetW);
                        targetW = maxDimension;
                    } else {
                        targetW = Math.round((targetW * maxDimension) / targetH);
                        targetH = maxDimension;
                    }
                }

                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                const normDeg = ((degrees % 360) + 360) % 360;
                const rads = (normDeg * Math.PI) / 180;
                if (normDeg === 90 || normDeg === 270) {
                    canvas.width = targetH;
                    canvas.height = targetW;
                } else {
                    canvas.width = targetW;
                    canvas.height = targetH;
                }
                ctx.translate(canvas.width / 2, canvas.height / 2);
                ctx.rotate(rads);
                ctx.drawImage(img, -targetW / 2, -targetH / 2, targetW, targetH);
                resolve(canvas.toDataURL('image/jpeg', 0.92));
            };
            img.onerror = () => resolve(base64);
            img.src = base64;
        });
    }

    /**
     * Filter Cerdas (Instant Client-Side Canvas Preprocessor)
     * Menjalankan Grayscale, Dynamic Range / Contrast Stretching, dan Gamma Enhancement
     * secara lokal di browser agar teks thermal/dot-matrix ber-kontras maksimal sebelum dibaca OCR.
     */
    function generateSmartFilterImage(base64, maxDimension = 2048) {
        return new Promise((resolve) => {
            if (!base64) {
                resolve(base64);
                return;
            }
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = () => {
                let targetW = img.width;
                let targetH = img.height;

                if (maxDimension && (targetW > maxDimension || targetH > maxDimension)) {
                    if (targetW > targetH) {
                        targetH = Math.round((targetH * maxDimension) / targetW);
                        targetW = maxDimension;
                    } else {
                        targetW = Math.round((targetW * maxDimension) / targetH);
                        targetH = maxDimension;
                    }
                }
                ocrImageWidth = targetW;
                ocrImageHeight = targetH;

                const canvas = document.createElement('canvas');
                canvas.width = targetW;
                canvas.height = targetH;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, targetW, targetH);

                try {
                    const imgData = ctx.getImageData(0, 0, targetW, targetH);
                    const data = imgData.data;
                    const len = data.length;

                    // 1. Hitung histogram luminance (Grayscale Rec. 601)
                    const hist = new Uint32Array(256);
                    const gray = new Uint8Array(len / 4);
                    let p = 0;
                    for (let i = 0; i < len; i += 4) {
                        let y = (data[i] * 299 + data[i + 1] * 587 + data[i + 2] * 114 + 500) / 1000 | 0;
                        gray[p++] = y;
                        hist[y]++;
                    }

                    // 2. Tentukan titik hitam (persentil 5) dan titik putih (persentil 95)
                    const totalPixels = len / 4;
                    const lowThreshold = totalPixels * 0.05;
                    const highThreshold = totalPixels * 0.95;

                    let count = 0;
                    let blackPoint = 0;
                    for (let i = 0; i < 256; i++) {
                        count += hist[i];
                        if (count >= lowThreshold) {
                            blackPoint = i;
                            break;
                        }
                    }

                    count = 0;
                    let whitePoint = 255;
                    for (let i = 255; i >= 0; i--) {
                        count += hist[i];
                        if (count >= totalPixels - highThreshold) {
                            whitePoint = i;
                            break;
                        }
                    }

                    if (whitePoint <= blackPoint + 10) {
                        blackPoint = 0;
                        whitePoint = 255;
                    }

                    // 3. Contrast stretching & penajaman teks untuk keterbacaan OCR optimal
                    const range = whitePoint - blackPoint;
                    const scale = 255 / (range || 1);

                    p = 0;
                    for (let i = 0; i < len; i += 4) {
                        let val = gray[p++];
                        let stretched = (val - blackPoint) * scale;
                        if (stretched < 0) stretched = 0;
                        if (stretched > 255) stretched = 255;

                        // Deepen dark text while whitening greyish background
                        if (stretched < 128) {
                            stretched = Math.pow(stretched / 128, 1.25) * 128;
                        } else {
                            stretched = 128 + Math.pow((stretched - 128) / 127, 0.85) * 127;
                        }

                        let finalVal = Math.round(stretched);
                        data[i] = finalVal;     // R
                        data[i + 1] = finalVal; // G
                        data[i + 2] = finalVal; // B
                    }

                    ctx.putImageData(imgData, 0, 0);
                    resolve(canvas.toDataURL('image/jpeg', 0.92));
                } catch (err) {
                    console.warn('Canvas smart filter error, falling back:', err);
                    resolve(base64);
                }
            };
            img.onerror = () => resolve(base64);
            img.src = base64;
        });
    }

    function resetUploadModal() {
        if (window.activeOcrRequest) {
            try { window.activeOcrRequest.abort(); } catch(e) {}
            window.activeOcrRequest = null;
        }
        $('#form-save-receipt')[0].reset();
        $('#form_image_base64').val('');
        $('#form_ocr_raw_data').val('');
        $('#alert-ai-match').addClass('d-none');
        $('#preview-receipt-img').attr('src', '');
        originalReceiptBase64 = '';
        enhancedReceiptBase64 = '';
        isEnhancedActive = false;
        currentReceiptFile = null;
        isAutoFlipped180 = false;
        ocrImageWidth = 0;
        ocrImageHeight = 0;
        $('#badge-autoflip-active').addClass('d-none');
        $('#btn-apply-rotation').addClass('d-none');
        $('#badge-enhanced-active').addClass('d-none');
        $('#btn-toggle-enhanced').addClass('d-none').removeClass('btn-warning').addClass('btn-outline-warning');
        $('#box-amount-verification').addClass('d-none').empty();
        $('#box-token-info').addClass('d-none');
        currentImageScale = 1.0;
        currentImageRotation = 0;
        clearLensOverlay();
        isSniperMode = false;
        $('#btn-toggle-sniper').removeClass('btn-primary active text-white').addClass('btn-outline-light');
        $('#preview-img-container').removeClass('sniper-active');
        $('#lens-sniper-box').addClass('d-none');
        updatePreviewImageTransform();
        $('#box-image-preview').removeClass('d-flex').addClass('d-none');
        $('#box-upload-zone').removeClass('d-none');
        $('#scan-loading-spinner').removeClass('d-flex').addClass('d-none');

        batchSelectedFiles = [];
        $('#batch_file_input').val('');
        renderBatchFilesList();

        let now = new Date();
        let localIso = new Date(now.getTime() - (now.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);
        $('#field_transaction_date').val(localIso);
        let defaultYear = "{{ $activeFiscalYear }}";
        if (defaultYear === 'all') defaultYear = "{{ date('Y') }}";
        $('#field_fiscal_year').val(defaultYear);
        $('#field_billing_month').val("{{ $selectedMonthName }}");

        adjustFormFieldsByCategory('listrik_pra');
    }
    window.resetUploadModal = resetUploadModal;

    function handleSelectedFile(file) {
        if (!file.type.match('image.*') && file.type !== 'application/pdf') {
            Swal.fire('Format Tidak Didukung', 'Silakan pilih berkas foto (JPG, PNG, WEBP) atau PDF.', 'warning');
            return;
        }

        currentReceiptFile = file;
        isAutoFlipped180 = false;
        $('#badge-autoflip-active').addClass('d-none');
        $('#btn-apply-rotation').addClass('d-none');

        let reader = new FileReader();
        reader.onload = function(e) {
            let base64Data = e.target.result;
            originalReceiptBase64 = base64Data;
            enhancedReceiptBase64 = '';
            isEnhancedActive = false;
            $('#badge-enhanced-active').addClass('d-none');
            $('#btn-toggle-enhanced').addClass('d-none').removeClass('btn-warning').addClass('btn-outline-warning');

            // Cek apakah metadata EXIF kamera HP mengindikasikan foto miring / terbalik (Orientation 3, 6, 8)
            getJpegOrientation(file).then(exifOrientation => {
                let rotateDeg = 0;
                if (exifOrientation === 3) rotateDeg = 180;
                else if (exifOrientation === 6) rotateDeg = 90;
                else if (exifOrientation === 8) rotateDeg = 270;

                if (rotateDeg > 0) {
                    // Miring/terbalik dari orientasi kamera HP -> langsung balik otomatis seketika ke posisi tegak!
                    rotateImageBase64(base64Data, rotateDeg).then(rotated => {
                        originalReceiptBase64 = rotated;
                        base64Data = rotated;
                        isAutoFlipped180 = true;
                        $('#form_image_base64').val(rotated);
                        $('#preview-receipt-img').attr('src', rotated);
                        $('#box-upload-zone').addClass('d-none');
                        $('#box-image-preview').removeClass('d-none').addClass('d-flex');
                        $('#badge-autoflip-active').removeClass('d-none').html(
                            `<i class="fas fa-check-circle mr-1 text-success"></i> <strong>Orientasi Kamera Disesuaikan:</strong> Foto terindikasi miring/terbalik (EXIF ${rotateDeg}°) dan langsung otomatis diselaraskan ke posisi tegak.`
                        );
                        triggerOcrScan(file, rotated);
                    });
                    return;
                }

                // Normal / Tanpa EXIF: Tampilkan preview dan jalankan pemindaian OCR (auto-flip jika teks terbalik)
                $('#form_image_base64').val(base64Data);
                $('#preview-receipt-img').attr('src', base64Data);
                $('#box-upload-zone').addClass('d-none');
                $('#box-image-preview').removeClass('d-none').addClass('d-flex');

                triggerOcrScan(file, base64Data);
            });
        };
        reader.readAsDataURL(file);
    }

    function triggerOcrScan(file, base64Data) {
        if (window.activeOcrRequest) {
            try { window.activeOcrRequest.abort(); } catch(e) {}
            window.activeOcrRequest = null;
        }

        let engine = $('#select-ocr-engine').val() || 'local';

        $('#scan-loading-spinner').removeClass('d-none').addClass('d-flex');

        // Check if browser Tesseract is available and file is image
        if (window.Tesseract && (!file || file.type.match('image.*')) && engine !== 'ai') {
            // LANGKAH 2: Jalankan Filter Cerdas di awal untuk memaksimalkan kontras kertas & tinta cetak
            $('#scan-loading-spinner .font-weight-bold').text('Menjalankan Filter Cerdas...');
            $('#scan-loading-spinner small').text('Memaksimalkan kontras & ketajaman karakter teks...');

            generateSmartFilterImage(base64Data).then(enhancedBase64 => {
                enhancedReceiptBase64 = enhancedBase64;
                // Aktifkan tombol Filter Cerdas agar pengguna dapat membandingkan tampilan kapan saja
                $('#btn-toggle-enhanced').removeClass('d-none btn-warning').addClass('btn-outline-warning').html('<i class="fas fa-wand-magic-sparkles"></i>');

                // LANGKAH 3: Tesseract & Google Lens membaca HASIL FILTER CERDAS yang ber-kontras tinggi
                $('#scan-loading-spinner .font-weight-bold').text('OCR Lokal & Lens sedang membaca...');
                $('#scan-loading-spinner small').text('Menganalisis teks ber-kontras tinggi...');

                let isHandled = false;
                let ocrTimer = setTimeout(function() {
                    if (!isHandled) {
                        isHandled = true;
                        $('#scan-loading-spinner small').text('Menganalisis data struk...');
                        sendOcrRequest(file, base64Data, '', engine);
                    }
                }, 14000);

                Tesseract.recognize(enhancedBase64, 'ind+eng', {
                    logger: m => {
                        if (m.status === 'recognizing text' && !isHandled) {
                            let pct = Math.round((m.progress || 0) * 100);
                            $('#scan-loading-spinner small').text(`Membaca teks terfilter: ${pct}%`);
                        }
                    },
                    tessedit_pageseg_mode: '6'
                }).then(res => {
                    if (isHandled) return;
                    let rawText = (res && res.data && res.data.text) ? res.data.text : '';
                    const utilityKeywords = /(?:PLN|POS|TELKOM|SPEEDY|TAGIHAN|BAYAR|TOKEN|STROOM|METER|IDPEL|TARIF|KWH|ATM|TRANSFER|KRAS|SMK|STM|MASJID|SMP|SADELI|SJADELI|MENAKSOPAL)/i;
                    const hasKeywords = utilityKeywords.test(rawText);

                    // Auto-detect upside-down photo (180° probe) jika 0 kata kunci utilitas ditemukan
                    if (!hasKeywords && !isAutoFlipped180) {
                        isAutoFlipped180 = true;

                        // 1. Indikasi struk terbalik: LANGSUNG balik tampilan pratinjau di layar seketika!
                        $('#lens-image-wrapper').css({
                            'transition': 'transform 0.45s cubic-bezier(0.4, 0, 0.2, 1)',
                            'transform': `scale(${currentImageScale}) rotate(180deg)`
                        });

                        // 2. Beri indikator visual & pesan kepada pengguna
                        $('#badge-autoflip-active').removeClass('d-none').html(
                            '<i class="fas fa-arrows-rotate fa-spin mr-1 text-warning"></i> <strong>Struk Terindikasi Terbalik:</strong> Posisi foto langsung otomatis dibalik 180° ke posisi tegak...'
                        );
                        $('#scan-loading-spinner .font-weight-bold').text('Struk terbalik terdeteksi!');
                        $('#scan-loading-spinner small').text('Membalik posisi foto 180° & memfilter ulang ke posisi tegak...');

                        Swal.fire({
                            icon: 'info',
                            title: 'Struk Terbalik Terdeteksi!',
                            text: 'Posisi foto langsung dibalik 180° otomatis agar tegak lurus.',
                            timer: 2000,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });

                        // 3. Putar 180° foto asli, lalu jalankan Filter Cerdas pada posisi 180°
                        rotateImageBase64(base64Data, 180).then(rotated180 => {
                            generateSmartFilterImage(rotated180).then(enhanced180 => {
                                Tesseract.recognize(enhanced180, 'ind+eng', {
                                    logger: m => {
                                        if (m.status === 'recognizing text' && !isHandled) {
                                            let pct = Math.round((m.progress || 0) * 100);
                                            $('#scan-loading-spinner small').text(`Membaca posisi tegak terfilter: ${pct}%`);
                                        }
                                    },
                                    tessedit_pageseg_mode: '6'
                                }).then(res180 => {
                                    if (isHandled) return;
                                    let text180 = (res180 && res180.data && res180.data.text) ? res180.data.text : '';
                                    if (utilityKeywords.test(text180)) {
                                        // 180° terkonfirmasi posisi tegak yang benar!
                                        isHandled = true;
                                        clearTimeout(ocrTimer);
                                        originalReceiptBase64 = rotated180;
                                        enhancedReceiptBase64 = enhanced180;
                                        base64Data = rotated180;

                                        // Pasang gambar tegak secara permanen ke src preview dan input simpan (Tetap Simpan Foto Asli Tegak)
                                        $('#preview-receipt-img').attr('src', rotated180);
                                        $('#form_image_base64').val(rotated180);

                                        // Reset transformasi wrapper agar koordinat Google Lens tepat lurus
                                        $('#lens-image-wrapper').css('transition', 'none');
                                        currentImageRotation = 0;
                                        updatePreviewImageTransform();
                                        void $('#lens-image-wrapper')[0].offsetHeight;
                                        $('#lens-image-wrapper').css('transition', 'transform 0.15s ease-out');

                                        $('#badge-autoflip-active').html(
                                            '<i class="fas fa-check-circle mr-1 text-success"></i> <strong>Foto Terbalik Berhasil Dibalik:</strong> Posisi foto telah diputar 180° otomatis ke posisi tegak.'
                                        );

                                        if (res180 && res180.data) {
                                            renderLensOverlay(res180.data);
                                        }
                                        sendOcrRequest(file, rotated180, text180, engine);
                                        return;
                                    }

                                    // Jika setelah dibalik 180° tetap tidak ada kata kunci utilitas:
                                    // Kembalikan posisi pratinjau ke normal (0°)
                                    $('#lens-image-wrapper').css({
                                        'transition': 'transform 0.35s ease-out',
                                        'transform': `scale(${currentImageScale}) rotate(0deg)`
                                    });
                                    $('#badge-autoflip-active').addClass('d-none');

                                    isHandled = true;
                                    clearTimeout(ocrTimer);
                                    if (res && res.data) {
                                        renderLensOverlay(res.data);
                                    }
                                    sendOcrRequest(file, base64Data, rawText, engine);
                                }).catch(() => {
                                    if (isHandled) return;
                                    isHandled = true;
                                    clearTimeout(ocrTimer);
                                    $('#lens-image-wrapper').css({
                                        'transition': 'transform 0.35s ease-out',
                                        'transform': `scale(${currentImageScale}) rotate(0deg)`
                                    });
                                    $('#badge-autoflip-active').addClass('d-none');
                                    sendOcrRequest(file, base64Data, rawText, engine);
                                });
                            });
                        });
                        return;
                    }

                    isHandled = true;
                    clearTimeout(ocrTimer);
                    if (res && res.data) {
                        renderLensOverlay(res.data);
                    }
                    sendOcrRequest(file, base64Data, rawText, engine);
                }).catch(err => {
                    if (isHandled) return;
                    isHandled = true;
                    clearTimeout(ocrTimer);
                    sendOcrRequest(file, base64Data, '', engine);
                });
            });
            return;
        }

        // Direct request to server
        $('#scan-loading-spinner .font-weight-bold').text(engine === 'local' ? 'OCR Lokal sedang memindai struk...' : (engine === 'ai' ? 'AI Cloud sedang memproses...' : 'Memindai struk...'));
        $('#scan-loading-spinner small').text('Mengekstrak tanggal, IDPEL, nominal, dan token');
        sendOcrRequest(file, base64Data, '', engine);
    }

    function sendOcrRequest(file, base64Data, rawText, engine) {
        let formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('receipt_file', file);
        formData.append('image_base64', base64Data);
        if (rawText) formData.append('raw_text', rawText);
        formData.append('engine', engine);

        window.activeOcrRequest = $.ajax({
            url: "{{ route('bos.utilitas.scan-receipt', [], false) }}",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            timeout: 25000,
            success: function(res) {
                window.activeOcrRequest = null;
                $('#scan-loading-spinner').removeClass('d-flex').addClass('d-none');

                if (res.enhanced_image) {
                    enhancedReceiptBase64 = res.enhanced_image;
                    isEnhancedActive = false;
                    // Sesuai prinsip: File yang disimpan harus berkas asli (dan sudah dibalik jika terbalik)
                    $('#form_image_base64').val(originalReceiptBase64);
                    $('#badge-enhanced-active').addClass('d-none');
                    $('#btn-toggle-enhanced').removeClass('d-none btn-warning').addClass('btn-outline-warning').html('<i class="fas fa-wand-magic-sparkles"></i>');
                }

                if (res.success && res.data) {
                    populateExtractedData(res.data, res.engine);
                    renderSmartChips(res.data);
                    let isLocal = (res.engine === 'local_regex_parser');
                    if (res.duplicate) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Peringatan Duplikasi!',
                            html: `Struk ini terdeteksi <strong>sudah pernah disimpan</strong> sebelumnya pada data <strong>ID #${res.duplicate.id}</strong> (${res.duplicate.profile_name}, ${res.duplicate.billing_month} ${res.duplicate.fiscal_year} - Rp ${parseFloat(res.duplicate.total_amount).toLocaleString('id-ID')}).<br><br><span class="text-danger">Sistem akan menolak penyimpanan ganda jika Anda menekan tombol simpan.</span>`,
                            confirmButtonText: 'Saya Mengerti',
                            confirmButtonColor: '#e02424'
                        });
                        $('#alert-ai-match').removeClass('d-none alert-info alert-success').addClass('alert-warning').html(
                            `<i class="fas fa-exclamation-triangle text-warning mr-1"></i> <strong>Duplikasi Terdeteksi:</strong> Struk ini sudah tersimpan pada data ID #${res.duplicate.id} (${res.duplicate.profile_name} - ${res.duplicate.billing_month} ${res.duplicate.fiscal_year}).`
                        );
                    } else {
                        Swal.fire({
                            icon: 'success',
                            title: isLocal ? 'Scan OCR Lokal Berhasil!' : 'Scan AI Berhasil!',
                            text: isLocal ? 'Data berhasil dicocokkan dengan 9 Profil Master Tagihan tanpa AI (100% Offline).' : 'Informasi struk berhasil diekstrak via AI.',
                            timer: 2200,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                    }
                } else {
                    Swal.fire('Info', res.message || 'Teks tidak dapat terbaca otomatis. Silakan lengkapi form manual.', 'info');
                }
            },
            error: function(xhr, status) {
                window.activeOcrRequest = null;
                $('#scan-loading-spinner').removeClass('d-flex').addClass('d-none');
                if (status === 'abort') return;
                Swal.fire('Info', 'Pemindaian otomatis mengalami kendala. Anda tetap dapat memasukkan data secara manual.', 'info');
            }
        });
    }

    function populateExtractedData(d, engine) {
        $('#form_ocr_raw_data').val(JSON.stringify(d.raw_ocr || d));

        if (d.matched_profile_id) {
            $('#field_utility_profile_id').val(d.matched_profile_id).trigger('change');
            let engineBadge = (engine === 'local_regex_parser' || d.engine === 'local_regex_parser')
                ? ' <span class="badge badge-success ml-1" style="font-size: 0.68rem;"><i class="fas fa-bolt mr-0.5"></i>Lokal Non-AI</span>'
                : ' <span class="badge badge-primary ml-1" style="font-size: 0.68rem;"><i class="fas fa-robot mr-0.5"></i>AI Cloud</span>';
            $('#label-match-engine').text('Sistem mengenali:');
            $('#label-ai-matched').html((d.matched_profile_name || 'Profil Terdaftar') + engineBadge);
            $('#alert-ai-match').removeClass('d-none');
        } else if (d.utility_type) {
            adjustFormFieldsByCategory(d.utility_type);
        }

        if (d.transaction_date) {
            let dt = new Date(d.transaction_date);
            if (!isNaN(dt.getTime())) {
                let iso = new Date(dt.getTime() - (dt.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);
                $('#field_transaction_date').val(iso);
            }
        }

        if (d.fiscal_year) $('#field_fiscal_year').val(d.fiscal_year);
        if (d.billing_month) $('#field_billing_month').val(d.billing_month);
        if (d.meter_number) $('#field_meter_number').val(d.meter_number);
        if (d.customer_number) $('#field_customer_number').val(d.customer_number);
        if (d.customer_name) $('#field_customer_name').val(d.customer_name);
        if (d.tariff_power) $('#field_tariff_power').val(d.tariff_power);
        if (d.kwh_amount) $('#field_kwh_amount').val(d.kwh_amount);

        if (d.base_amount) $('#field_base_amount').val(formatMoneyValue(d.base_amount));
        if (d.admin_fee) $('#field_admin_fee').val(formatMoneyValue(d.admin_fee));
        if (d.tax_pbjt) $('#field_tax_pbjt').val(formatMoneyValue(d.tax_pbjt));
        if (d.total_amount) $('#field_total_amount').val(formatMoneyValue(d.total_amount));

        if (d.token_number) {
            $('#field_token_number').val(formatTokenNumber(d.token_number));
            $('#box-token-info').removeClass('d-none');
        }
        if (d.receipt_number) $('#field_receipt_number').val(d.receipt_number);
        if (d.payment_merchant) $('#field_payment_merchant').val(d.payment_merchant);

        updateAmountVerification();
    }

    // ==========================================
    // VERIFIKASI ARITMATIKA REAL-TIME (TAGIHAN + ADMIN vs TOTAL)
    // ==========================================
    function updateAmountVerification() {
        let base = parseMoneyValue($('#field_base_amount').val());
        let admin = parseMoneyValue($('#field_admin_fee').val());
        let pbjt = parseMoneyValue($('#field_tax_pbjt').val());
        let total = parseMoneyValue($('#field_total_amount').val());

        let box = $('#box-amount-verification');
        if (base <= 0 && total <= 0) {
            box.addClass('d-none').empty();
            return;
        }

        let expectedTotal = Math.round(base + admin + pbjt);
        let roundedTotal = Math.round(total);

        if (base > 0 && total > 0) {
            if (expectedTotal === roundedTotal) {
                // Sesuai / Cocok
                box.removeClass('d-none').html(`
                    <div class="d-flex justify-content-between align-items-center flex-wrap py-1 px-2.5 rounded border border-success text-success" style="background: #f0fdf4; font-size: 0.74rem; gap: 4px;">
                        <span><i class="fas fa-check-circle mr-1"></i> <strong>Perhitungan Sesuai:</strong> Tagihan (Rp ${base.toLocaleString('id-ID')}) + Admin (Rp ${admin.toLocaleString('id-ID')}) ${pbjt > 0 ? `+ PBJT (Rp ${pbjt.toLocaleString('id-ID')}) ` : ''}= <strong>Rp ${expectedTotal.toLocaleString('id-ID')}</strong></span>
                        <span class="badge badge-success px-2 py-0.5">Sesuai (Match)</span>
                    </div>
                `);
            } else {
                // Ada Selisih
                let diff = Math.abs(roundedTotal - expectedTotal);
                let suggestedBase = Math.max(0, roundedTotal - admin - pbjt);
                box.removeClass('d-none').html(`
                    <div class="p-2 rounded border border-warning text-dark" style="background: #fffbeb; font-size: 0.74rem;">
                        <div class="d-flex justify-content-between align-items-center flex-wrap mb-1.5">
                            <span><i class="fas fa-triangle-exclamation text-warning mr-1"></i> <strong>Perhatian Selisih:</strong> Tagihan + Admin ${pbjt > 0 ? '+ PBJT ' : ''}= <strong>Rp ${expectedTotal.toLocaleString('id-ID')}</strong>, sedangkan Total Bayar = <strong>Rp ${roundedTotal.toLocaleString('id-ID')}</strong> (Selisih: <span class="text-danger font-weight-bold">Rp ${diff.toLocaleString('id-ID')}</span>).</span>
                        </div>
                        <div class="d-flex align-items-center flex-wrap" style="gap: 6px;">
                            <button type="button" class="btn btn-xs btn-success font-weight-bold" id="btn-sync-to-sum" title="Ubah Total Bayar agar sama dengan Tagihan + Admin"><i class="fas fa-check mr-1"></i>Samakan Total Bayar Jadi Rp ${expectedTotal.toLocaleString('id-ID')}</button>
                            ${suggestedBase > 0 ? `<button type="button" class="btn btn-xs btn-outline-primary" id="btn-sync-to-base" title="Hitung ulang Tagihan Pokok (Total Bayar - Admin)"><i class="fas fa-calculator mr-1"></i>Ubah Tagihan Pokok: Rp ${suggestedBase.toLocaleString('id-ID')}</button>` : ''}
                        </div>
                    </div>
                `);
            }
        } else if (base > 0 && total <= 0) {
            // Total belum terisi
            box.removeClass('d-none').html(`
                <div class="d-flex justify-content-between align-items-center flex-wrap py-1 px-2.5 rounded border border-info text-info" style="background: #f0fdfa; font-size: 0.74rem; gap: 4px;">
                    <span><i class="fas fa-info-circle mr-1"></i> Estimasi Total: Tagihan (Rp ${base.toLocaleString('id-ID')}) + Admin (Rp ${admin.toLocaleString('id-ID')}) = <strong>Rp ${expectedTotal.toLocaleString('id-ID')}</strong></span>
                    <button type="button" class="btn btn-xs btn-info py-0 px-2 font-weight-bold" id="btn-sync-to-sum">Isi Total Bayar</button>
                </div>
            `);
        } else {
            box.addClass('d-none').empty();
        }
    }
    window.updateAmountVerification = updateAmountVerification;

    $('.calc-amount').on('input change', function() {
        let base = parseMoneyValue($('#field_base_amount').val());
        let admin = parseMoneyValue($('#field_admin_fee').val());
        let pbjt = parseMoneyValue($('#field_tax_pbjt').val());
        let currTotal = parseMoneyValue($('#field_total_amount').val());

        if (currTotal <= 0 && $(this).attr('id') !== 'field_total_amount') {
            $('#field_total_amount').val(formatMoneyValue(base + admin + pbjt));
        }
        updateAmountVerification();
    });

    $(document).on('click', '#btn-sync-to-sum', function(e) {
        e.preventDefault();
        let base = parseMoneyValue($('#field_base_amount').val());
        let admin = parseMoneyValue($('#field_admin_fee').val());
        let pbjt = parseMoneyValue($('#field_tax_pbjt').val());
        $('#field_total_amount').val(formatMoneyValue(base + admin + pbjt));
        updateAmountVerification();
    });

    $(document).on('click', '#btn-sync-to-base', function(e) {
        e.preventDefault();
        let admin = parseMoneyValue($('#field_admin_fee').val());
        let pbjt = parseMoneyValue($('#field_tax_pbjt').val());
        let total = parseMoneyValue($('#field_total_amount').val());
        $('#field_base_amount').val(formatMoneyValue(Math.max(0, Math.round(total) - admin - pbjt)));
        updateAmountVerification();
    });

    $('#field_utility_profile_id').on('change', function() {
        let opt = $(this).find('option:selected');
        let meter = opt.data('meter') || '';
        let cust = opt.data('cust') || '';
        let name = opt.data('name') || '';
        let tariff = opt.data('tariff') || '';
        let merchant = opt.data('merchant') || '';
        let cat = opt.data('category') || 'listrik_pra';

        adjustFormFieldsByCategory(cat);

        // Bersihkan data field yang tidak relevan dengan kategori akun yang dipilih
        if (cat === 'internet' || cat === 'telkom_speedy') {
            $('#field_meter_number').val('');
            $('#field_tariff_power').val('');
            $('#field_token_number').val('');
            $('#field_kwh_amount').val('');
            $('#field_tax_pbjt').val('');
        } else if (cat === 'listrik_pasca') {
            $('#field_meter_number').val('');
            $('#field_token_number').val('');
            $('#field_kwh_amount').val('');
            if (tariff) $('#field_tariff_power').val(tariff);
        } else if (cat === 'listrik_pra') {
            if (meter) $('#field_meter_number').val(meter);
            if (tariff) $('#field_tariff_power').val(tariff);
        }

        // Isi default dari profil jika tersedia
        if (cust) $('#field_customer_number').val(cust);
        if (name && !$('#field_customer_name').val()) $('#field_customer_name').val(name);
        if (merchant && !$('#field_payment_merchant').val()) $('#field_payment_merchant').val(merchant);

        // Penanganan Kertas Kerja Anggaran BOS vs Non-Anggaran (seperti SMP PGRI)
        let isBudgeted = opt.data('budgeted');
        if (isBudgeted === 0 || isBudgeted === '0') {
            $('#wrapper-kertas-kerja-scan').addClass('d-none');
            $('#badge-non-budgeted-scan').removeClass('d-none');
            $('#alert-non-budgeted-scan').removeClass('d-none');
            $('#field_bos_kertas_kerja_item_id').val('');
        } else {
            $('#wrapper-kertas-kerja-scan').removeClass('d-none');
            $('#badge-non-budgeted-scan').addClass('d-none');
            $('#alert-non-budgeted-scan').addClass('d-none');

            // Auto-pilih item kertas kerja jika cocok dengan profil, bulan & nominal
            let profileId = $(this).val();
            let billingMonth = $('#field_billing_month').val();
            let cat = opt.data('category') || 'listrik_pra';
            let totalAmount = $('#field_total_amount').val();
            if (profileId && billingMonth) {
                let bestMatch = window.findBestKertasKerjaOption('#field_bos_kertas_kerja_item_id', cat, billingMonth, totalAmount);
                if (bestMatch) {
                    $('#field_bos_kertas_kerja_item_id').val(bestMatch);
                }
            }
        }

        updateAmountVerification();
    });

    $('#field_billing_month').on('change', function() {
        let profileOpt = $('#field_utility_profile_id').find('option:selected');
        let isBudgeted = profileOpt.data('budgeted');
        if (isBudgeted !== 0 && isBudgeted !== '0') {
            let profileId = $('#field_utility_profile_id').val();
            let billingMonth = $(this).val();
            let cat = profileOpt.data('category') || 'listrik_pra';
            let totalAmount = $('#field_total_amount').val();
            if (profileId && billingMonth) {
                let bestMatch = window.findBestKertasKerjaOption('#field_bos_kertas_kerja_item_id', cat, billingMonth, totalAmount);
                if (bestMatch) {
                    $('#field_bos_kertas_kerja_item_id').val(bestMatch);
                }
            }
        }
    });

    // ==========================================
    // IMAGE PREVIEW ZOOM, PAN & ROTATE CONTROLS
    // ==========================================
    let currentImageScale = 1.0;
    let currentImageRotation = 0;

    function updatePreviewImageTransform() {
        $('#lens-image-wrapper').css({
            'transform': `scale(${currentImageScale}) rotate(${currentImageRotation}deg)`,
            'transform-origin': 'center center'
        });
        $('#preview-receipt-img').css({
            'transform': 'none'
        });
        $('#btn-zoom-reset').text(`${Math.round(currentImageScale * 100)}%`);
        if (isSniperMode) {
            $('#preview-img-container').css('cursor', 'crosshair');
        } else if (currentImageScale > 1.0) {
            $('#preview-img-container').css('cursor', 'grab');
        } else {
            $('#preview-img-container').css('cursor', 'default');
        }
    }

    $('#btn-zoom-in').on('click', function(e) {
        e.preventDefault();
        if (currentImageScale < 4.0) {
            currentImageScale = Math.min(4.0, +(currentImageScale + 0.25).toFixed(2));
            updatePreviewImageTransform();
        }
    });

    $('#btn-zoom-out').on('click', function(e) {
        e.preventDefault();
        if (currentImageScale > 0.5) {
            currentImageScale = Math.max(0.5, +(currentImageScale - 0.25).toFixed(2));
            updatePreviewImageTransform();
        }
    });

    $('#btn-zoom-reset').on('click', function(e) {
        e.preventDefault();
        currentImageScale = 1.0;
        updatePreviewImageTransform();
    });

    $('#btn-rotate-img').on('click', function(e) {
        e.preventDefault();
        currentImageRotation = (currentImageRotation + 90) % 360;
        updatePreviewImageTransform();
        if (currentImageRotation !== 0) {
            $('#btn-apply-rotation').removeClass('d-none').html(`<i class="fas fa-arrows-rotate mr-1"></i>Pindai Posisi Ini (${currentImageRotation}°)`);
        } else {
            $('#btn-apply-rotation').addClass('d-none');
        }
    });

    $('#btn-flip-180').on('click', function(e) {
        e.preventDefault();
        let currentBase64 = $('#form_image_base64').val() || originalReceiptBase64;
        if (!currentBase64) return;

        isAutoFlipped180 = true;
        rotateImageBase64(currentBase64, 180).then(rotated => {
            originalReceiptBase64 = rotated;
            $('#preview-receipt-img').attr('src', rotated);
            $('#form_image_base64').val(rotated);
            currentImageRotation = 0;
            updatePreviewImageTransform();
            $('#btn-apply-rotation').addClass('d-none');
            $('#badge-autoflip-active').removeClass('d-none').html(
                '<i class="fas fa-arrows-rotate mr-1"></i> <strong>Posisi Dibalik:</strong> Foto diputar 180° secara manual.'
            );
            triggerOcrScan(currentReceiptFile, rotated);
        });
    });

    $('#btn-apply-rotation').on('click', function(e) {
        e.preventDefault();
        let currentBase64 = $('#form_image_base64').val() || originalReceiptBase64;
        if (!currentBase64 || currentImageRotation === 0) return;

        let deg = currentImageRotation;
        isAutoFlipped180 = true;
        rotateImageBase64(currentBase64, deg).then(rotated => {
            originalReceiptBase64 = rotated;
            $('#preview-receipt-img').attr('src', rotated);
            $('#form_image_base64').val(rotated);
            currentImageRotation = 0;
            updatePreviewImageTransform();
            $('#btn-apply-rotation').addClass('d-none');
            $('#badge-autoflip-active').removeClass('d-none').html(
                `<i class="fas fa-arrows-rotate mr-1"></i> <strong>Rotasi Diterapkan:</strong> Foto diputar ${deg}° secara manual.`
            );
            triggerOcrScan(currentReceiptFile, rotated);
        });
    });

    // View full-screen lightbox
    $('#btn-view-large').on('click', function(e) {
        e.preventDefault();
        let src = $('#preview-receipt-img').attr('src');
        if (src) {
            $('#lightbox-img').attr('src', src);
            $('#lightbox-title').text('Foto Struk - Ukuran Penuh');
            window.showModal('#modalImageViewer');
        }
    });

    // Mouse wheel zoom
    $('#preview-img-container').on('wheel', function(e) {
        e.preventDefault();
        if (e.originalEvent.deltaY < 0) {
            if (currentImageScale < 4.0) {
                currentImageScale = Math.min(4.0, +(currentImageScale + 0.2).toFixed(2));
                updatePreviewImageTransform();
            }
        } else {
            if (currentImageScale > 0.6) {
                currentImageScale = Math.max(0.6, +(currentImageScale - 0.2).toFixed(2));
                updatePreviewImageTransform();
            }
        }
    });

    // Drag to pan when zoomed (disabled when in sniper mode)
    let isDraggingImg = false;
    let imgStartX, imgStartY, imgScrollLeft, imgScrollTop;

    $('#preview-img-container').on('mousedown', function(e) {
        if (isSniperMode || currentImageScale <= 1.0) return;
        isDraggingImg = true;
        $(this).css('cursor', 'grabbing');
        imgStartX = e.pageX - this.offsetLeft;
        imgStartY = e.pageY - this.offsetTop;
        imgScrollLeft = this.scrollLeft;
        imgScrollTop = this.scrollTop;
    });

    $(document).on('mouseup', function() {
        if (isDraggingImg) {
            isDraggingImg = false;
            $('#preview-img-container').css('cursor', isSniperMode ? 'crosshair' : (currentImageScale > 1.0 ? 'grab' : 'default'));
        }
    });

    $('#preview-img-container').on('mousemove', function(e) {
        if (!isDraggingImg) return;
        e.preventDefault();
        const x = e.pageX - this.offsetLeft;
        const y = e.pageY - this.offsetTop;
        const walkX = (x - imgStartX) * 1.5;
        const walkY = (y - imgStartY) * 1.5;
        this.scrollLeft = imgScrollLeft - walkX;
        this.scrollTop = imgScrollTop - walkY;
    });

    // Double click to toggle 1.75x zoom
    $('#preview-receipt-img').on('dblclick', function(e) {
        if (isSniperMode) return;
        e.preventDefault();
        currentImageScale = (currentImageScale === 1.0) ? 1.75 : 1.0;
        updatePreviewImageTransform();
    });

    $('#btn-toggle-enhanced').on('click', function(e) {
        e.preventDefault();
        if (!enhancedReceiptBase64 || !originalReceiptBase64) return;

        if (isEnhancedActive) {
            // Kembali ke foto asli
            isEnhancedActive = false;
            $('#preview-receipt-img').attr('src', originalReceiptBase64);
            $('#badge-enhanced-active').addClass('d-none');
            $(this).removeClass('btn-warning').addClass('btn-outline-warning').html('<i class="fas fa-wand-magic-sparkles"></i>');
        } else {
            // Tampilkan bantuan visual filter cerdas (hanya untuk tampilan layar, bukan arsip file)
            isEnhancedActive = true;
            $('#preview-receipt-img').attr('src', enhancedReceiptBase64);
            $('#badge-enhanced-active').removeClass('d-none');
            $(this).removeClass('btn-outline-warning').addClass('btn-warning').html('<i class="fas fa-image"></i>');
        }
        // Berkas simpan form SELALU file asli (sudah dibalik jika terbalik)
        $('#form_image_base64').val(originalReceiptBase64);
    });

    $('#btn-remove-img').on('click', function() {
        currentImageScale = 1.0;
        currentImageRotation = 0;
        clearLensOverlay();
        isSniperMode = false;
        $('#btn-toggle-sniper').removeClass('btn-primary active text-white').addClass('btn-outline-light');
        $('#preview-img-container').removeClass('sniper-active');
        $('#lens-sniper-box').addClass('d-none');
        updatePreviewImageTransform();
        $('#preview-receipt-img').attr('src', '');
        $('#form_image_base64').val('');
        originalReceiptBase64 = '';
        enhancedReceiptBase64 = '';
        isEnhancedActive = false;
        $('#badge-enhanced-active').addClass('d-none');
        $('#btn-toggle-enhanced').addClass('d-none').removeClass('btn-warning').addClass('btn-outline-warning');
        $('#box-image-preview').removeClass('d-flex').addClass('d-none');
        $('#box-upload-zone').removeClass('d-none');
        $('#receipt_file_input').val('');
    });

    // ==========================================
    // GOOGLE LENS INTERACTIVE TEXT DETECTOR & SNIPER TOOL
    // ==========================================
    let isLensVisible = true;
    let isSniperMode = false;
    let lastActiveFieldId = null;
    let currentOcrTokens = [];

    // Track active form input field
    $('#form-save-receipt input, #form-save-receipt textarea, #form-save-receipt select').on('focus', function() {
        lastActiveFieldId = $(this).attr('id');
    });

    // 1. GLOBAL CLIPBOARD PASTE (CTRL + V)
    $(window).on('paste', function(e) {
        let clipboardData = (e.clipboardData || e.originalEvent.clipboardData);
        if (!clipboardData || !clipboardData.items) return;

        let items = clipboardData.items;
        for (let i = 0; i < items.length; i++) {
            if (items[i].type && items[i].type.indexOf('image') !== -1) {
                let file = items[i].getAsFile();
                if (file) {
                    e.preventDefault();
                    if (!$('#modalScanReceipt').hasClass('show') && !$('#modalScanReceipt').is(':visible')) {
                        window.openUploadModal('single');
                    } else {
                        $('#tab-single-btn').tab('show');
                    }
                    handleSelectedFile(file);
                    Swal.fire({
                        icon: 'success',
                        title: 'Foto Terdeteksi dari Clipboard (Ctrl+V)!',
                        text: 'Memuat gambar struk dan mulai memindai secara instan...',
                        timer: 1800,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                    break;
                }
            }
        }
    });

    // 2. CLEAR LENS OVERLAY
    function clearLensOverlay() {
        $('#lens-boxes-layer').empty();
        $('#lens-chips-container').empty();
        $('#lens-chips-bar').addClass('d-none');
        $('#lens-popover').remove();
        currentOcrTokens = [];
    }
    window.clearLensOverlay = clearLensOverlay;

    // 3. RENDER LENS OVERLAY FROM OCR RESULTS
    function renderLensOverlay(ocrData) {
        clearLensOverlay();
        if (!ocrData) return;

        let img = document.getElementById('preview-receipt-img');
        if (!img) return;

        let natW = img.naturalWidth || img.width;
        let natH = img.naturalHeight || img.height;
        if (!natW || !natH) {
            $(img).one('load', function() {
                renderLensOverlay(ocrData);
            });
            return;
        }

        let detectedLines = ocrData.lines || [];
        let detectedWords = ocrData.words || [];
        let itemsToRender = [];

        // Check lines for 20-digit token numbers and phrases
        detectedLines.forEach(line => {
            let txt = (line.text || '').trim();
            if (!txt) return;

            let digitsOnly = txt.replace(/[^0-9]/g, '');

            // Token 20 digit pattern
            if (digitsOnly.length === 20 || /^[0-9]{4}[ -][0-9]{4}[ -][0-9]{4}[ -][0-9]{4}[ -][0-9]{4}$/.test(txt)) {
                let formatted = digitsOnly.replace(/(\d{4})(?=\d)/g, '$1-');
                itemsToRender.push({
                    type: 'token',
                    text: formatted,
                    displayText: 'Token: ' + formatted,
                    rawText: txt,
                    bbox: line.bbox,
                    cssClass: 'lens-box-token',
                    icon: 'fa-key text-warning',
                    label: 'Nomor Token (Stroom)'
                });
            }
            // ID Pelanggan line (11-13 digits)
            else if (digitsOnly.length >= 11 && digitsOnly.length <= 13 && /idpel|pln|cust|pelanggan/i.test(txt)) {
                itemsToRender.push({
                    type: 'customer_number',
                    text: digitsOnly,
                    displayText: 'IDPEL: ' + digitsOnly,
                    rawText: txt,
                    bbox: line.bbox,
                    cssClass: '',
                    icon: 'fa-id-card text-info',
                    label: 'ID Pelanggan'
                });
            }
        });

        // Check words for amounts, IDs, kWh, and names
        detectedWords.forEach(w => {
            let txt = (w.text || '').trim();
            if (!txt || txt.length < 2) return;
            if (w.confidence && w.confidence < 25) return;

            let digitsOnly = txt.replace(/[^0-9]/g, '');

            // Skip if part of an already detected token line
            let isInsideExisting = itemsToRender.some(it => {
                return (it.type === 'token' && it.bbox &&
                        w.bbox.x0 >= it.bbox.x0 && w.bbox.x1 <= it.bbox.x1 &&
                        w.bbox.y0 >= it.bbox.y0 && w.bbox.y1 <= it.bbox.y1);
            });
            if (isInsideExisting) return;

            // Numbers / Amounts (Rp)
            if (/^(?:rp\.?)?[0-9]{1,3}(?:[.,][0-9]{3})*(?:[.,][0-9]{2})?$/i.test(txt) && digitsOnly.length >= 3) {
                let val = parseInt(digitsOnly, 10);
                if (val >= 100) {
                    itemsToRender.push({
                        type: 'money',
                        text: val,
                        displayText: 'Rp ' + val.toLocaleString('id-ID'),
                        rawText: txt,
                        bbox: w.bbox,
                        cssClass: 'lens-box-money',
                        icon: 'fa-money-bill-wave text-success',
                        label: 'Nominal Biaya'
                    });
                    return;
                }
            }

            // IDPEL / Meter (11-13 digits)
            if (/^[0-9]{11,13}$/.test(digitsOnly)) {
                itemsToRender.push({
                    type: 'customer_number',
                    text: digitsOnly,
                    displayText: digitsOnly,
                    rawText: txt,
                    bbox: w.bbox,
                    cssClass: '',
                    icon: 'fa-id-card text-info',
                    label: 'ID Pelanggan'
                });
                return;
            }

            // kWh number (e.g. 64.2 or 120,5)
            if (/^[0-9]{1,4}[.,][0-9]{1,2}$/.test(txt)) {
                let kwhVal = parseFloat(txt.replace(',', '.'));
                if (kwhVal > 0 && kwhVal < 9999) {
                    itemsToRender.push({
                        type: 'kwh',
                        text: kwhVal,
                        displayText: kwhVal + ' kWh',
                        rawText: txt,
                        bbox: w.bbox,
                        cssClass: '',
                        icon: 'fa-bolt text-warning',
                        label: 'Jml kWh'
                    });
                    return;
                }
            }

            // Alphanumeric words (length 3-30)
            if (/^[A-Za-z0-9/.-]{3,30}$/.test(txt) && !/^(dan|yang|untuk|atau|pada|dengan|ke|di|rp)$/i.test(txt)) {
                itemsToRender.push({
                    type: 'text',
                    text: txt,
                    displayText: txt,
                    rawText: txt,
                    bbox: w.bbox,
                    cssClass: '',
                    icon: 'fa-font text-light',
                    label: 'Teks'
                });
            }
        });

        // Add bounding boxes to layer
        let layer = $('#lens-boxes-layer');
        let baseW = (ocrData && ocrData.imageWidth) || ocrImageWidth || natW;
        let baseH = (ocrData && ocrData.imageHeight) || ocrImageHeight || natH;

        itemsToRender.forEach((item, idx) => {
            if (!item.bbox) return;

            let leftPct = ((item.bbox.x0 / baseW) * 100).toFixed(3);
            let topPct = ((item.bbox.y0 / baseH) * 100).toFixed(3);
            let widthPct = (((item.bbox.x1 - item.bbox.x0) / baseW) * 100).toFixed(3);
            let heightPct = (((item.bbox.y1 - item.bbox.y0) / baseH) * 100).toFixed(3);

            if (parseFloat(widthPct) < 2) widthPct = '2.5';
            if (parseFloat(heightPct) < 1.5) heightPct = '2';

            let boxHtml = $(`
                <div class="lens-box ${item.cssClass}" 
                     style="left: ${leftPct}%; top: ${topPct}%; width: ${widthPct}%; height: ${heightPct}%;" 
                     data-index="${idx}"
                     title="Klik untuk memasukkan: ${item.displayText || item.text}">
                </div>
            `);

            boxHtml.data('lensItem', item);
            layer.append(boxHtml);
        });

        currentOcrTokens = itemsToRender;

        if (!isLensVisible) {
            layer.hide();
        } else {
            layer.show();
        }
    }
    window.renderLensOverlay = renderLensOverlay;

    // 4. CLICK LENS BOX HANDLER
    $(document).on('click', '.lens-box', function(e) {
        if (isSniperMode) return;
        e.stopPropagation();
        e.preventDefault();

        let item = $(this).data('lensItem');
        if (!item) return;

        // If user already focused a specific form field before, fill directly
        if (lastActiveFieldId && $('#' + lastActiveFieldId).length) {
            fillField(lastActiveFieldId, item.text);
            return;
        }

        // Otherwise show quick popover
        showLensPopover($(this), item, e);
    });

    // 5. SHOW LENS POPOVER
    function showLensPopover($box, item, e) {
        $('#lens-popover').remove();

        let text = item.text;
        let preview = (item.displayText || item.text);
        if (preview.length > 25) preview = preview.slice(0, 22) + '...';

        let popHtml = $(`
            <div class="lens-popover shadow-lg" id="lens-popover">
                <div class="font-weight-bold text-info pb-1 mb-1 border-bottom border-secondary d-flex justify-content-between align-items-center" style="font-size: 0.72rem;">
                    <span><i class="fas ${item.icon || 'fa-crosshairs'} mr-1"></i> "${preview}"</span>
                    <i class="fas fa-times text-muted ml-2 cursor-pointer btn-close-popover" style="cursor: pointer;"></i>
                </div>
                <div class="lens-popover-actions d-flex flex-column" style="gap: 2px;"></div>
            </div>
        `);

        let actions = popHtml.find('.lens-popover-actions');

        if (item.type === 'token' || (typeof text === 'string' && text.replace(/[^0-9]/g, '').length >= 16)) {
            actions.append(`<button type="button" class="btn-popover-fill" data-target="field_token_number"><i class="fas fa-key text-warning mr-1"></i> Isi Nomor Token (20 Digit)</button>`);
        }

        if (item.type === 'customer_number' || (typeof text === 'string' && text.replace(/[^0-9]/g, '').length >= 10 && text.replace(/[^0-9]/g, '').length <= 13)) {
            actions.append(`<button type="button" class="btn-popover-fill" data-target="field_customer_number"><i class="fas fa-id-card text-info mr-1"></i> Isi ID Pelanggan (IDPEL)</button>`);
            actions.append(`<button type="button" class="btn-popover-fill" data-target="field_meter_number"><i class="fas fa-tachometer-alt text-secondary mr-1"></i> Isi Nomor Meter</button>`);
        }

        if (item.type === 'money' || !isNaN(parseFloat(text))) {
            actions.append(`<button type="button" class="btn-popover-fill" data-target="field_total_amount"><i class="fas fa-money-bill-wave text-success mr-1"></i> Isi Total Bayar</button>`);
            actions.append(`<button type="button" class="btn-popover-fill" data-target="field_base_amount"><i class="fas fa-receipt text-primary mr-1"></i> Isi Tagihan Pokok</button>`);
            actions.append(`<button type="button" class="btn-popover-fill" data-target="field_admin_fee"><i class="fas fa-coins text-warning mr-1"></i> Isi Biaya Admin</button>`);
        }

        if (item.type === 'kwh') {
            actions.append(`<button type="button" class="btn-popover-fill" data-target="field_kwh_amount"><i class="fas fa-bolt text-warning mr-1"></i> Isi Jml kWh</button>`);
        }

        actions.append(`<button type="button" class="btn-popover-fill" data-target="field_customer_name"><i class="fas fa-user text-info mr-1"></i> Isi Nama Pelanggan</button>`);
        actions.append(`<button type="button" class="btn-popover-fill" data-target="field_receipt_number"><i class="fas fa-file-invoice text-secondary mr-1"></i> Isi Nomor Resi / Seri</button>`);
        actions.append(`<button type="button" class="btn-popover-copy"><i class="fas fa-copy text-light mr-1"></i> Salin Teks</button>`);

        $('#preview-img-container').append(popHtml);

        let boxPos = $box.position();
        let top = boxPos.top + $box.outerHeight() + 6;
        let left = Math.max(10, boxPos.left);
        popHtml.css({ top: top + 'px', left: left + 'px' });

        popHtml.find('.btn-popover-fill').on('click', function(ev) {
            ev.stopPropagation();
            let targetId = $(this).data('target');
            fillField(targetId, item.text);
            $('#lens-popover').remove();
        });

        popHtml.find('.btn-popover-copy').on('click', function(ev) {
            ev.stopPropagation();
            navigator.clipboard.writeText(String(item.text)).then(() => {
                Swal.fire({
                    icon: 'success',
                    title: 'Tersalin!',
                    text: `"${item.text}" berhasil disalin ke clipboard`,
                    timer: 1400,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            });
            $('#lens-popover').remove();
        });

        popHtml.find('.btn-close-popover').on('click', function(ev) {
            ev.stopPropagation();
            $('#lens-popover').remove();
        });
    }

    // 6. SMART ENTITY CHIPS
    function renderSmartChips(d) {
        let container = $('#lens-chips-container');
        container.empty();

        if (!d) {
            $('#lens-chips-bar').addClass('d-none');
            return;
        }

        let chips = [];

        if (d.token_number) {
            chips.push({
                icon: 'fa-key',
                cls: 'lens-chip-token',
                label: 'Token: ' + d.token_number,
                target: 'field_token_number',
                val: d.token_number
            });
        }

        if (d.customer_number) {
            chips.push({
                icon: 'fa-id-card',
                cls: '',
                label: 'IDPEL: ' + d.customer_number,
                target: 'field_customer_number',
                val: d.customer_number
            });
        }

        if (d.meter_number) {
            chips.push({
                icon: 'fa-tachometer-alt',
                cls: '',
                label: 'Meter: ' + d.meter_number,
                target: 'field_meter_number',
                val: d.meter_number
            });
        }

        if (d.total_amount && d.total_amount > 0) {
            chips.push({
                icon: 'fa-money-bill-wave',
                cls: 'lens-chip-money',
                label: 'Total: Rp ' + Math.round(d.total_amount).toLocaleString('id-ID'),
                target: 'field_total_amount',
                val: Math.round(d.total_amount)
            });
        }

        if (d.base_amount && d.base_amount > 0) {
            chips.push({
                icon: 'fa-receipt',
                cls: '',
                label: 'Pokok: Rp ' + Math.round(d.base_amount).toLocaleString('id-ID'),
                target: 'field_base_amount',
                val: Math.round(d.base_amount)
            });
        }

        if (d.admin_fee && d.admin_fee > 0) {
            chips.push({
                icon: 'fa-coins',
                cls: '',
                label: 'Admin: Rp ' + Math.round(d.admin_fee).toLocaleString('id-ID'),
                target: 'field_admin_fee',
                val: Math.round(d.admin_fee)
            });
        }

        if (d.customer_name) {
            chips.push({
                icon: 'fa-user',
                cls: '',
                label: 'Nama: ' + d.customer_name,
                target: 'field_customer_name',
                val: d.customer_name
            });
        }

        if (d.tariff_power) {
            chips.push({
                icon: 'fa-bolt',
                cls: '',
                label: 'Tarif: ' + d.tariff_power,
                target: 'field_tariff_power',
                val: d.tariff_power
            });
        }

        if (d.kwh_amount) {
            chips.push({
                icon: 'fa-tachometer-alt',
                cls: '',
                label: d.kwh_amount + ' kWh',
                target: 'field_kwh_amount',
                val: d.kwh_amount
            });
        }

        if (chips.length > 0) {
            chips.forEach(c => {
                let chipEl = $(`
                    <button type="button" class="lens-chip ${c.cls}" data-target="${c.target}" title="Klik untuk memasukkan ke form">
                        <i class="fas ${c.icon}"></i> ${c.label}
                    </button>
                `);
                chipEl.on('click', function(ev) {
                    ev.preventDefault();
                    fillField(c.target, c.val);
                });
                container.append(chipEl);
            });
            $('#lens-chips-count').text(`${chips.length} nilai terdeteksi`);
            $('#lens-chips-bar').removeClass('d-none');
        } else {
            $('#lens-chips-bar').addClass('d-none');
        }
    }
    window.renderSmartChips = renderSmartChips;

    // 7. FILL FIELD & ANIMATE HIGHLIGHT
    function fillField(targetId, value) {
        let $field = $('#' + targetId);
        if (!$field.length) return;

        if ($field.hasClass('rupiah-input') || ['field_base_amount', 'field_admin_fee', 'field_tax_pbjt', 'field_total_amount', 'edit_base_amount', 'edit_admin_fee', 'edit_tax_pbjt', 'edit_total_amount'].includes(targetId)) {
            value = formatMoneyValue(value);
        } else if ($field.hasClass('token-input') || targetId.includes('token_number')) {
            value = formatTokenNumber(value);
        }

        $field.val(value).trigger('input').trigger('change');
        $field.addClass('input-field-highlight');
        setTimeout(() => $field.removeClass('input-field-highlight'), 2200);

        let container = $field.closest('.col-md-7');
        if (container.length) {
            let offsetTop = $field.offset().top - container.offset().top + container.scrollTop() - 40;
            container.animate({ scrollTop: offsetTop }, 200);
        }

        let labelText = $field.closest('.form-group').find('label').text().replace(/[*]/g, '').trim() || targetId;
        Swal.fire({
            icon: 'success',
            title: 'Berhasil Dimasukkan!',
            html: `Nilai <strong>${value}</strong> dimasukkan ke kolom <strong>${labelText}</strong>`,
            timer: 1600,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    }
    window.fillField = fillField;

    // 8. TOGGLE LENS VISIBILITY BUTTON
    $('#btn-toggle-lens').on('click', function(e) {
        e.preventDefault();
        isLensVisible = !isLensVisible;
        if (isLensVisible) {
            $(this).addClass('btn-info active').removeClass('btn-outline-light');
            $('#lens-boxes-layer').show();
        } else {
            $(this).removeClass('btn-info active').addClass('btn-outline-light');
            $('#lens-boxes-layer').hide();
            $('#lens-popover').remove();
        }
    });

    // 9. SNIPER / REGION CROP OCR TOOL
    $('#btn-toggle-sniper').on('click', function(e) {
        e.preventDefault();
        isSniperMode = !isSniperMode;

        if (isSniperMode) {
            $(this).addClass('btn-primary active text-white').removeClass('btn-outline-light');
            $('#preview-img-container').addClass('sniper-active');
            $('#lens-boxes-layer').css('pointer-events', 'none');
            Swal.fire({
                icon: 'info',
                title: 'Mode Bidik Aktif',
                text: 'Klik dan tarik mouse pada gambar nota untuk memilih area tulisan yang ingin dibaca.',
                timer: 2200,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        } else {
            $(this).removeClass('btn-primary active text-white').addClass('btn-outline-light');
            $('#preview-img-container').removeClass('sniper-active');
            $('#lens-boxes-layer').css('pointer-events', 'auto');
            $('#lens-sniper-box').addClass('d-none');
        }
        updatePreviewImageTransform();
    });

    let isSniperDragging = false;
    let snipStartX = 0, snipStartY = 0;

    $('#preview-img-container').on('mousedown', function(e) {
        if (!isSniperMode) return;
        let img = document.getElementById('preview-receipt-img');
        if (!img || !img.src) return;

        let rect = img.getBoundingClientRect();
        if (e.clientX < rect.left || e.clientX > rect.right || e.clientY < rect.top || e.clientY > rect.bottom) {
            return;
        }

        isSniperDragging = true;
        snipStartX = e.clientX - rect.left;
        snipStartY = e.clientY - rect.top;

        let pctX = (snipStartX / rect.width) * 100;
        let pctY = (snipStartY / rect.height) * 100;

        $('#lens-sniper-box').removeClass('d-none').css({
            left: pctX + '%',
            top: pctY + '%',
            width: '0%',
            height: '0%'
        });
    });

    $('#preview-img-container').on('mousemove', function(e) {
        if (!isSniperMode || !isSniperDragging) return;
        let img = document.getElementById('preview-receipt-img');
        if (!img) return;

        let rect = img.getBoundingClientRect();
        let currX = Math.max(0, Math.min(rect.width, e.clientX - rect.left));
        let currY = Math.max(0, Math.min(rect.height, e.clientY - rect.top));

        let left = Math.min(snipStartX, currX);
        let top = Math.min(snipStartY, currY);
        let w = Math.abs(currX - snipStartX);
        let h = Math.abs(currY - snipStartY);

        $('#lens-sniper-box').css({
            left: (left / rect.width * 100) + '%',
            top: (top / rect.height * 100) + '%',
            width: (w / rect.width * 100) + '%',
            height: (h / rect.height * 100) + '%'
        });
    });

    $(document).on('mouseup', function(e) {
        if (!isSniperMode || !isSniperDragging) return;
        isSniperDragging = false;

        let img = document.getElementById('preview-receipt-img');
        if (!img) return;

        let rect = img.getBoundingClientRect();
        let endX = Math.max(0, Math.min(rect.width, e.clientX - rect.left));
        let endY = Math.max(0, Math.min(rect.height, e.clientY - rect.top));

        let rawW = Math.abs(endX - snipStartX);
        let rawH = Math.abs(endY - snipStartY);

        if (rawW < 12 || rawH < 10) {
            $('#lens-sniper-box').addClass('d-none');
            return;
        }

        let scaleX = img.naturalWidth / rect.width;
        let scaleY = img.naturalHeight / rect.height;

        let cropX = Math.min(snipStartX, endX) * scaleX;
        let cropY = Math.min(snipStartY, endY) * scaleY;
        let cropW = rawW * scaleX;
        let cropH = rawH * scaleY;

        let canvas = document.createElement('canvas');
        canvas.width = Math.max(cropW, 40);
        canvas.height = Math.max(cropH, 20);
        let ctx = canvas.getContext('2d');
        ctx.drawImage(img, cropX, cropY, cropW, cropH, 0, 0, canvas.width, canvas.height);

        if (window.Tesseract) {
            Swal.fire({
                title: 'Membaca Area Bidik...',
                text: 'Memproses potongan teks pada area yang dipilih...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            Tesseract.recognize(canvas, 'ind+eng', {
                tessedit_pageseg_mode: '7'
            }).then(res => {
                Swal.close();
                $('#lens-sniper-box').addClass('d-none');
                let snipText = (res && res.data && res.data.text) ? res.data.text.trim() : '';

                if (snipText) {
                    showSniperActionDialog(snipText);
                } else {
                    Swal.fire('Tidak Terbaca', 'Teks pada area bidik belum terbaca jelas. Coba seleksi area yang lebih luas.', 'warning');
                }
            }).catch(err => {
                Swal.close();
                $('#lens-sniper-box').addClass('d-none');
                Swal.fire('Gagal', 'Terjadi kendala saat membaca area bidik.', 'error');
            });
        } else {
            $('#lens-sniper-box').addClass('d-none');
        }
    });

    function showSniperActionDialog(text) {
        let digits = text.replace(/[^0-9]/g, '');
        let isTokenCandidate = digits.length >= 16;
        let isIdpelCandidate = digits.length >= 10 && digits.length <= 13;
        let isNumberCandidate = digits.length >= 3;

        let optionsHtml = '';
        if (isTokenCandidate) {
            let formattedToken = digits.replace(/(\d{4})(?=\d)/g, '$1-').slice(0, 24);
            optionsHtml += `<button class="btn btn-warning btn-sm btn-block text-left mb-1.5 btn-apply-snip" data-target="field_token_number" data-val="${formattedToken}"><i class="fas fa-key mr-1"></i> Isi Nomor Token (<strong>${formattedToken}</strong>)</button>`;
        }
        if (isIdpelCandidate) {
            optionsHtml += `<button class="btn btn-info btn-sm btn-block text-left mb-1.5 btn-apply-snip" data-target="field_customer_number" data-val="${digits}"><i class="fas fa-id-card mr-1"></i> Isi ID Pelanggan (<strong>${digits}</strong>)</button>`;
            optionsHtml += `<button class="btn btn-secondary btn-sm btn-block text-left mb-1.5 btn-apply-snip" data-target="field_meter_number" data-val="${digits}"><i class="fas fa-tachometer-alt mr-1"></i> Isi Nomor Meter (<strong>${digits}</strong>)</button>`;
        }
        if (isNumberCandidate) {
            let numVal = parseInt(digits, 10);
            optionsHtml += `<button class="btn btn-success btn-sm btn-block text-left mb-1.5 btn-apply-snip" data-target="field_total_amount" data-val="${numVal}"><i class="fas fa-money-bill-wave mr-1"></i> Isi Total Bayar (<strong>Rp ${numVal.toLocaleString('id-ID')}</strong>)</button>`;
            optionsHtml += `<button class="btn btn-outline-primary btn-sm btn-block text-left mb-1.5 btn-apply-snip" data-target="field_base_amount" data-val="${numVal}"><i class="fas fa-receipt mr-1"></i> Isi Tagihan Pokok (<strong>Rp ${numVal.toLocaleString('id-ID')}</strong>)</button>`;
            optionsHtml += `<button class="btn btn-outline-warning btn-sm btn-block text-left mb-1.5 btn-apply-snip" data-target="field_admin_fee" data-val="${numVal}"><i class="fas fa-coins mr-1"></i> Isi Admin Bank (<strong>Rp ${numVal.toLocaleString('id-ID')}</strong>)</button>`;
        }
        optionsHtml += `<button class="btn btn-outline-info btn-sm btn-block text-left mb-1.5 btn-apply-snip" data-target="field_customer_name" data-val="${text}"><i class="fas fa-user mr-1"></i> Isi Nama Pelanggan</button>`;
        if (lastActiveFieldId) {
            optionsHtml += `<button class="btn btn-primary btn-sm btn-block text-left mb-1.5 btn-apply-snip" data-target="${lastActiveFieldId}" data-val="${text}"><i class="fas fa-arrow-right mr-1"></i> Isi ke Kolom yang Sedang Aktif</button>`;
        }

        Swal.fire({
            title: '<i class="fas fa-crosshairs text-info mr-1"></i> Hasil Bidik Teks',
            html: `
                <div class="p-2 mb-2 bg-light border rounded font-weight-bold text-dark text-break font-tabular" style="font-size: 1rem;">
                    "${text}"
                </div>
                <div class="text-left font-weight-bold small text-muted mb-2">Pilih kolom tujuan:</div>
                <div class="text-left">${optionsHtml}</div>
            `,
            showCancelButton: true,
            cancelButtonText: 'Tutup',
            showConfirmButton: false,
            didOpen: () => {
                $('.btn-apply-snip').on('click', function() {
                    let target = $(this).data('target');
                    let val = $(this).data('val');
                    fillField(target, val);
                    Swal.close();
                });
            }
        });
    }

    // Dismiss popover on outside click
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#lens-popover, .lens-box').length) {
            $('#lens-popover').remove();
        }
    });

    // Submit Single Receipt
    $(document).on('click', '#btn-submit-receipt, #btn-submit-save', function(e) {
        e.preventDefault();
        submitSingleReceiptForm();
    });

    $('#form-save-receipt').on('submit', function(e) {
        e.preventDefault();
        submitSingleReceiptForm();
    });

    function submitSingleReceiptForm() {
        let form = $('#form-save-receipt');
        if (!form[0].checkValidity()) {
            form[0].reportValidity();
            return;
        }

        let base = parseMoneyValue($('#field_base_amount').val());
        let admin = parseMoneyValue($('#field_admin_fee').val());
        let pbjt = parseMoneyValue($('#field_tax_pbjt').val());
        let total = parseMoneyValue($('#field_total_amount').val());
        let expectedTotal = Math.round(base + admin + pbjt);
        let roundedTotal = Math.round(total);

        // Verifikasi Aritmatika sebelum menyimpan ke database
        if (base > 0 && total > 0 && expectedTotal !== roundedTotal) {
            let diff = Math.abs(roundedTotal - expectedTotal);
            Swal.fire({
                title: 'Verifikasi Jumlah Bayar',
                icon: 'warning',
                html: `
                    <div class="text-left" style="font-size: 0.85rem; line-height: 1.5;">
                        <p class="mb-2">Terdeteksi <strong>selisih perhitungan</strong> antara rincian tagihan dengan jumlah bayar:</p>
                        <div class="p-2.5 rounded bg-light border mb-2">
                            <div>• Tagihan Pokok: <strong>Rp ${base.toLocaleString('id-ID')}</strong></div>
                            <div>• Biaya Admin: <strong>Rp ${admin.toLocaleString('id-ID')}</strong></div>
                            ${pbjt > 0 ? `<div>• Pajak / PBJT: <strong>Rp ${pbjt.toLocaleString('id-ID')}</strong></div>` : ''}
                            <hr class="my-1.5">
                            <div class="text-primary font-weight-bold">• Tagihan + Admin: <strong>Rp ${expectedTotal.toLocaleString('id-ID')}</strong></div>
                            <div class="text-danger font-weight-bold">• Total Bayar di Form: <strong>Rp ${roundedTotal.toLocaleString('id-ID')}</strong></div>
                            <div class="text-danger font-weight-bold mt-1">Selisih: Rp ${diff.toLocaleString('id-ID')}</div>
                        </div>
                        <p class="mb-0 text-muted" style="font-size: 0.78rem;">Apakah Anda ingin menyesuaikan Total Bayar menjadi <strong>Rp ${expectedTotal.toLocaleString('id-ID')}</strong> sebelum menyimpan?</p>
                    </div>
                `,
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonColor: '#059669',
                denyButtonColor: '#475569',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: `<i class="fas fa-check mr-1"></i> Sesuaikan ke Rp ${expectedTotal.toLocaleString('id-ID')} & Simpan`,
                denyButtonText: `<i class="fas fa-save mr-1"></i> Tetap Simpan Rp ${roundedTotal.toLocaleString('id-ID')}`,
                cancelButtonText: 'Batal & Periksa'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#field_total_amount').val(formatMoneyValue(expectedTotal));
                    updateAmountVerification();
                    executeSubmitReceiptForm();
                } else if (result.isDenied) {
                    executeSubmitReceiptForm();
                }
            });
            return;
        }

        executeSubmitReceiptForm();
    }

    function executeSubmitReceiptForm() {
        let form = $('#form-save-receipt');
        let btn = $('#btn-submit-receipt');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

        $.ajax({
            url: "{{ route('bos.utilitas.store', [], false) }}",
            type: "POST",
            data: form.serialize(),
            success: function(res) {
                btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan Data Struk');
                window.hideModal('#modalScanReceipt');
                tableReceipts.draw(false);

                let savedMonth = (res && res.data && res.data.billing_month) ? res.data.billing_month : $('#field_billing_month').val();
                let savedYear = (res && res.data && res.data.fiscal_year) ? res.data.fiscal_year : $('#field_fiscal_year').val();
                let transDate = (res && res.data && res.data.transaction_date) ? res.data.transaction_date : $('#field_transaction_date').val();
                let displayMonth = savedMonth || 'yang dipilih';

                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil Disimpan!',
                    text: `Bukti pembayaran bulan ${displayMonth} berhasil disimpan. Menampilkan data bulan ${displayMonth}...`,
                    timer: 1600,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                }).then(() => {
                    window.redirectToReceiptPeriod(savedMonth, savedYear, transDate);
                });
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan Data Struk');
                let msg = xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan saat menyimpan data.';
                Swal.fire('Gagal Menyimpan', msg, 'error');
            }
        });
    }

    // ==========================================
    // EDIT RECEIPT MODAL
    // ==========================================
    $(document).on('click', '.btn-edit', function() {
        let id = $(this).data('id');
        $.get("{{ route('bos.utilitas.index', [], false) }}/" + id, function(res) {
            if (res.success && res.receipt) {
                let r = res.receipt;
                $('#edit_receipt_id').val(r.id);
                $('#edit_utility_profile_id').val(r.utility_profile_id || '');

                if (r.transaction_date) {
                    let dt = new Date(r.transaction_date);
                    if (!isNaN(dt.getTime())) {
                        let iso = new Date(dt.getTime() - (dt.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);
                        $('#edit_transaction_date').val(iso);
                    }
                }
                $('#edit_fiscal_year').val(r.fiscal_year);
                $('#edit_billing_month').val(r.billing_month);
                $('#edit_meter_number').val(r.meter_number || '');
                $('#edit_customer_number').val(r.customer_number || '');
                $('#edit_customer_name').val(r.customer_name || '');
                $('#edit_tariff_power').val(r.tariff_power || '');
                $('#edit_kwh_amount').val(r.kwh_amount || '');

                $('#edit_base_amount').val(formatMoneyValue(r.base_amount || 0));
                $('#edit_admin_fee').val(formatMoneyValue(r.admin_fee || 0));
                $('#edit_tax_pbjt').val(formatMoneyValue(r.tax_pbjt || 0));
                $('#edit_total_amount').val(formatMoneyValue(r.total_amount || 0));

                $('#edit_token_number').val(formatTokenNumber(r.token_number || ''));
                $('#edit_receipt_number').val(r.receipt_number || '');
                $('#edit_payment_merchant').val(r.payment_merchant || '');
                $('#edit_notes').val(r.notes || '');

                let editOpt = $('#edit_utility_profile_id').find('option:selected');
                let editBudgeted = editOpt.data('budgeted');
                let editCat = editOpt.data('category') || (r.profile ? r.profile.category : 'listrik_pra');
                let currentKkId = r.bos_kertas_kerja_item_id;

                if (editBudgeted === 0 || editBudgeted === '0') {
                    $('#edit-wrapper-kertas-kerja').addClass('d-none');
                    $('#edit-badge-non-budgeted').removeClass('d-none');
                    $('#edit-alert-non-budgeted').removeClass('d-none');
                    $('#edit_bos_kertas_kerja_item_id').val('');
                } else {
                    $('#edit-wrapper-kertas-kerja').removeClass('d-none');
                    $('#edit-badge-non-budgeted').addClass('d-none');
                    $('#edit-alert-non-budgeted').addClass('d-none');

                    if (currentKkId) {
                        if ($('#edit_bos_kertas_kerja_item_id option[value="' + currentKkId + '"]').length === 0 && r.kertas_kerja_item) {
                            let kki = r.kertas_kerja_item;
                            let kkTotal = kki.total_amount || (kki.price * (kki.volume || 1));
                            let formattedTotal = new Intl.NumberFormat('id-ID').format(kkTotal);
                            let optText = `[${kki.month || ''}] ${kki.description || ''} (Rp ${formattedTotal})`;
                            $('#edit_bos_kertas_kerja_item_id').append(new Option(optText, currentKkId, true, true));
                        }
                        $('#edit_bos_kertas_kerja_item_id').val(String(currentKkId));
                    } else {
                        let bestMatch = window.findBestKertasKerjaOption('#edit_bos_kertas_kerja_item_id', editCat, r.billing_month, r.total_amount);
                        if (bestMatch) {
                            $('#edit_bos_kertas_kerja_item_id').val(bestMatch);
                        } else {
                            $('#edit_bos_kertas_kerja_item_id').val('');
                        }
                    }
                }

                adjustEditFormFieldsByCategory(editCat);

                window.showModal('#modalEditReceipt');
            }
        });
    });

    $('#edit_utility_profile_id').on('change', function() {
        let opt = $(this).find('option:selected');
        let isBudgeted = opt.data('budgeted');
        let cat = opt.data('category') || 'listrik_pra';

        if (isBudgeted === 0 || isBudgeted === '0') {
            $('#edit-wrapper-kertas-kerja').addClass('d-none');
            $('#edit-badge-non-budgeted').removeClass('d-none');
            $('#edit-alert-non-budgeted').removeClass('d-none');
            $('#edit_bos_kertas_kerja_item_id').val('');
        } else {
            $('#edit-wrapper-kertas-kerja').removeClass('d-none');
            $('#edit-badge-non-budgeted').addClass('d-none');
            $('#edit-alert-non-budgeted').addClass('d-none');

            let profileId = $(this).val();
            let billingMonth = $('#edit_billing_month').val();
            let totalAmount = $('#edit_total_amount').val();
            if (profileId && billingMonth && !$('#edit_bos_kertas_kerja_item_id').val()) {
                let bestMatch = window.findBestKertasKerjaOption('#edit_bos_kertas_kerja_item_id', cat, billingMonth, totalAmount);
                if (bestMatch) {
                    $('#edit_bos_kertas_kerja_item_id').val(bestMatch);
                }
            }
        }
        adjustEditFormFieldsByCategory(cat);
    });

    $('#edit_billing_month').on('change input', function() {
        let profileOpt = $('#edit_utility_profile_id').find('option:selected');
        let isBudgeted = profileOpt.data('budgeted');
        if (isBudgeted !== 0 && isBudgeted !== '0') {
            let profileId = $('#edit_utility_profile_id').val();
            let billingMonth = $(this).val();
            let cat = profileOpt.data('category') || 'listrik_pra';
            let totalAmount = $('#edit_total_amount').val();
            if (profileId && billingMonth && !$('#edit_bos_kertas_kerja_item_id').val()) {
                let bestMatch = window.findBestKertasKerjaOption('#edit_bos_kertas_kerja_item_id', cat, billingMonth, totalAmount);
                if (bestMatch) {
                    $('#edit_bos_kertas_kerja_item_id').val(bestMatch);
                }
            }
        }
    });

    function adjustEditFormFieldsByCategory(cat) {
        cat = cat || 'listrik_pra';

        if (cat === 'internet') {
            $('#edit-header-section-2').text('2. Identitas Rekening Transfer');
            $('#edit-group-meter-number').addClass('d-none');
            $('#edit-group-tariff-power').addClass('d-none');
            $('#edit-group-kwh-amount').addClass('d-none');
            $('#edit-group-customer-number').removeClass('d-none').removeClass('col-sm-6 col-12').addClass('col-sm-6 mb-0');
            $('#edit-group-customer-name').removeClass('d-none').removeClass('col-sm-6 col-12 col').addClass('col-sm-6 mb-0');
            $('#edit-label-customer-number').text('No. Rekening Tujuan / IDPEL');
            $('#edit_customer_number').attr('placeholder', 'Contoh: 3243258444 (BCA Menaksopal)');
            $('#edit-label-customer-name').text('Nama Penerima Transfer di Struk');
            $('#edit-box-token-info').addClass('d-none');
            $('#edit-group-tax-pbjt').addClass('d-none');
            $('#edit-group-base-amount').removeClass('col-sm-4').addClass('col-sm-6');
            $('#edit-group-admin-fee').removeClass('col-sm-4').addClass('col-sm-6');
            $('#edit-label-base-amount').text('Nominal Pokok Transfer (Rp)');
            $('#edit-label-admin-fee').text('Biaya Admin Transfer (Rp)');
            $('#edit-label-total-amount').html('TOTAL RP TRANSFER (DIBAYARKAN) <span class="text-danger">*</span>');
        } else if (cat === 'telkom_speedy') {
            $('#edit-header-section-2').text('2. Identitas Pelanggan & Telepon');
            $('#edit-group-meter-number').addClass('d-none');
            $('#edit-group-tariff-power').addClass('d-none');
            $('#edit-group-kwh-amount').addClass('d-none');
            $('#edit-group-customer-number').removeClass('d-none').removeClass('col-sm-6 col-12').addClass('col-sm-6 mb-0');
            $('#edit-group-customer-name').removeClass('d-none').removeClass('col-sm-6 col-12 col').addClass('col-sm-6 mb-0');
            $('#edit-label-customer-number').text('No. Telepon / IDPEL Telkom');
            $('#edit_customer_number').attr('placeholder', 'Contoh: 0354-000479487');
            $('#edit-label-customer-name').text('Nama Pelanggan di Struk');
            $('#edit-box-token-info').addClass('d-none');
            $('#edit-group-tax-pbjt').addClass('d-none');
            $('#edit-group-base-amount').removeClass('col-sm-4').addClass('col-sm-6');
            $('#edit-group-admin-fee').removeClass('col-sm-4').addClass('col-sm-6');
            $('#edit-label-base-amount').text('Tagihan Pokok Telkom (Rp)');
            $('#edit-label-admin-fee').text('Biaya Admin Pos/Bank (Rp)');
            $('#edit-label-total-amount').html('TOTAL RP BAYAR (DIBAYARKAN) <span class="text-danger">*</span>');
        } else if (cat === 'listrik_pasca') {
            $('#edit-header-section-2').text('2. Identitas Pelanggan');
            $('#edit-group-meter-number').addClass('d-none');
            $('#edit-group-kwh-amount').addClass('d-none');
            $('#edit-group-customer-number').removeClass('d-none').removeClass('col-sm-6 col-12').addClass('col-sm-6 mb-1');
            $('#edit-group-tariff-power').removeClass('d-none').removeClass('col-sm-3 col-sm-6').addClass('col-sm-6 mb-1');
            $('#edit-group-customer-name').removeClass('d-none').removeClass('col col-sm-6').addClass('col-12 mb-0');
            $('#edit-label-customer-number').text('ID Pelanggan (IDPEL)');
            $('#edit-label-tariff-power').text('Tarif / Daya');
            $('#edit-label-customer-name').text('Nama Pelanggan di Struk');
            $('#edit-box-token-info').addClass('d-none');
            $('#edit-group-tax-pbjt').removeClass('d-none');
            $('#edit-group-base-amount').removeClass('col-sm-6').addClass('col-sm-4');
            $('#edit-group-admin-fee').removeClass('col-sm-6').addClass('col-sm-4');
            $('#edit-label-base-amount').text('Tagihan Pokok Listrik (Rp)');
            $('#edit-label-admin-fee').text('Biaya Admin Pos/Bank (Rp)');
            $('#edit-label-tax-pbjt').text('Pajak PBJT-TL / PPJ (Rp)');
            $('#edit-label-total-amount').html('TOTAL RP BAYAR (DIBAYARKAN) <span class="text-danger">*</span>');
        } else if (cat === 'listrik_pra') {
            $('#edit-header-section-2').text('2. Identitas Pelanggan & Meter');
            $('#edit-group-meter-number').removeClass('d-none').removeClass('col-sm-6 col-12').addClass('col-sm-6 mb-1');
            $('#edit-group-customer-number').removeClass('d-none').removeClass('col-sm-6 col-12').addClass('col-sm-6 mb-1');
            $('#edit-group-customer-name').removeClass('d-none').removeClass('col col-12').addClass('col-sm-6 mb-0');
            $('#edit-group-tariff-power').removeClass('d-none').removeClass('col-sm-3 col-12').addClass('col-sm-3 mb-0');
            $('#edit-group-kwh-amount').removeClass('d-none').removeClass('col-sm-3 col-12').addClass('col-sm-3 mb-0');
            $('#edit-label-meter-number').text('No. Meter (Prabayar)');
            $('#edit-label-customer-number').text('IDPEL PLN');
            $('#edit-label-customer-name').text('Nama Pelanggan di Struk');
            $('#edit-label-tariff-power').text('Tarif / Daya');
            $('#edit-box-token-info').removeClass('d-none');
            $('#edit-group-tax-pbjt').removeClass('d-none');
            $('#edit-group-base-amount').removeClass('col-sm-6').addClass('col-sm-4');
            $('#edit-group-admin-fee').removeClass('col-sm-6').addClass('col-sm-4');
            $('#edit-label-base-amount').text('Nilai Pokok / Token (Rp)');
            $('#edit-label-admin-fee').text('Biaya Admin (Rp)');
            $('#edit-label-tax-pbjt').text('Pajak PBJT-TL (Rp)');
            $('#edit-label-total-amount').html('TOTAL RP BAYAR <span class="text-danger">*</span>');
        } else {
            $('#edit-header-section-2').text('2. Identitas Pelanggan');
            $('#edit-group-meter-number').addClass('d-none');
            $('#edit-group-tariff-power').addClass('d-none');
            $('#edit-group-kwh-amount').addClass('d-none');
            $('#edit-group-customer-number').removeClass('d-none').removeClass('col-sm-6 col-12').addClass('col-sm-6 mb-0');
            $('#edit-group-customer-name').removeClass('d-none').removeClass('col-sm-6 col-12 col').addClass('col-sm-6 mb-0');
            $('#edit-box-token-info').addClass('d-none');
            $('#edit-group-tax-pbjt').addClass('d-none');
            $('#edit-group-base-amount').removeClass('col-sm-4').addClass('col-sm-6');
            $('#edit-group-admin-fee').removeClass('col-sm-4').addClass('col-sm-6');
        }
    }

    $('.edit-calc-amount').on('input', function() {
        let base = parseMoneyValue($('#edit_base_amount').val());
        let admin = parseMoneyValue($('#edit_admin_fee').val());
        let pbjt = parseMoneyValue($('#edit_tax_pbjt').val());
        $('#edit_total_amount').val(formatMoneyValue(base + admin + pbjt));
    });

    $(document).on('submit', '#form-edit-receipt', function(e) {
        e.preventDefault();
        let id = $('#edit_receipt_id').val();
        if (!id) {
            Swal.fire('Gagal Menyimpan', 'ID bukti pembayaran tidak ditemukan.', 'error');
            return;
        }

        let form = $(this);
        let btnSubmit = form.find('button[type="submit"]');

        btnSubmit.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

        $.ajax({
            url: "{{ route('bos.utilitas.index', [], false) }}/" + id,
            type: "POST",
            data: form.serialize(),
            success: function(res) {
                btnSubmit.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan Perubahan');
                window.hideModal('#modalEditReceipt');
                tableReceipts.draw(false);

                let r = (res && res.receipt) ? res.receipt : ((res && res.data) ? res.data : {});
                let editMonth = r.billing_month || $('#edit_billing_month').val();
                let editYear = r.fiscal_year || $('#edit_fiscal_year').val();
                let transDate = r.transaction_date || $('#edit_transaction_date').val();
                let displayMonth = editMonth || 'yang dipilih';

                Swal.fire({
                    icon: 'success',
                    title: 'Perubahan Disimpan!',
                    text: `Data struk bulan ${displayMonth} berhasil diperbarui. Menampilkan data bulan ${displayMonth}...`,
                    timer: 1600,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                }).then(() => {
                    window.redirectToReceiptPeriod(editMonth, editYear, transDate);
                });
            },
            error: function(xhr) {
                btnSubmit.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan Perubahan');
                let msg = xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan saat memperbarui data.';
                Swal.fire('Gagal Menyimpan', msg, 'error');
            }
        });
    });

    // Copy Token Handler
    $(document).on('click', '.btn-copy-token', function(e) {
        e.preventDefault();
        let token = $(this).data('token');
        if (!token) return;

        navigator.clipboard.writeText(token).then(() => {
            Swal.fire({
                icon: 'success',
                title: 'Token Disalin!',
                text: token,
                timer: 1800,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        }).catch(() => {
            prompt('Salin nomor token:', token);
        });
    });

    $('#btn-copy-form-token').on('click', function() {
        let val = $('#field_token_number').val().replace(/[^0-9]/g, '');
        if (val) {
            navigator.clipboard.writeText(val);
            Swal.fire({ icon: 'success', title: 'Token Disalin!', text: val, timer: 1500, showConfirmButton: false, toast: true, position: 'top-end' });
        }
    });

    // View Receipt Image Lightbox
    $(document).on('click', '.btn-view-receipt', function() {
        let url = $(this).data('url');
        let title = $(this).data('title');
        $('#lightbox-img').attr('src', url);
        $('#lightbox-title').text('Foto Struk: ' + title);
        window.showModal('#modalImageViewer');
    });

    // View Detail Receipt Modal
    $(document).on('click', '.btn-detail', function() {
        let id = $(this).data('id');
        $.get("{{ route('bos.utilitas.index', [], false) }}/" + id, function(res) {
            if (res.success && res.receipt) {
                let r = res.receipt;
                let html = `
                    <div class="row">
                        <div class="col-md-5 text-center mb-3 mb-md-0">
                            ${res.receipt_url ? `<img src="${res.receipt_url}" class="img-fluid rounded border shadow-sm" style="max-height: 380px;">` : `<div class="p-4 bg-white rounded border text-muted">Tidak ada foto struk</div>`}
                        </div>
                        <div class="col-md-7">
                            <h6 class="font-weight-bold text-dark mb-1">${r.profile ? r.profile.name : (r.customer_name || 'Tagihan Utilitas')}</h6>
                            <div class="text-muted mb-3" style="font-size: 0.8rem;">
                                Resi: <span class="font-monospace">${r.receipt_number || '-'}</span> | Merchant: ${r.payment_merchant || '-'}
                            </div>

                            <table class="table table-sm table-bordered text-dark" style="font-size: 0.82rem;">
                                <tr><th class="bg-light" style="width: 38%;">Status AI</th><td>${r.ocr_status_badge || '-'}</td></tr>
                                <tr><th class="bg-light">Tanggal Bayar</th><td class="font-tabular">${r.transaction_date ? r.transaction_date.substring(0, 19).replace('T', ' ') : '-'}</td></tr>
                                <tr><th class="bg-light">Periode Tagihan</th><td>${r.billing_month || '-'} ${r.fiscal_year}</td></tr>
                                <tr><th class="bg-light">No. Meter</th><td class="font-tabular font-weight-bold">${r.meter_number || '-'}</td></tr>
                                <tr><th class="bg-light">ID Pelanggan / Telp</th><td class="font-tabular">${r.customer_number || '-'}</td></tr>
                                <tr><th class="bg-light">Tarif / Daya</th><td>${r.tariff_power || '-'} ${r.kwh_amount ? '(' + r.kwh_amount + ' kWh)' : ''}</td></tr>
                                <tr><th class="bg-light">Nilai Pokok / Token</th><td class="font-tabular">Rp ${Number(r.base_amount || 0).toLocaleString('id-ID')}</td></tr>
                                <tr><th class="bg-light">Biaya Admin</th><td class="font-tabular">Rp ${Number(r.admin_fee || 0).toLocaleString('id-ID')}</td></tr>
                                <tr><th class="bg-light">Pajak PBJT-TL</th><td class="font-tabular">Rp ${Number(r.tax_pbjt || 0).toLocaleString('id-ID')}</td></tr>
                                <tr class="table-primary"><th class="font-weight-bold">TOTAL DIBAYAR</th><td class="font-weight-bold font-tabular text-dark" style="font-size: 0.95rem;">Rp ${Number(r.total_amount || 0).toLocaleString('id-ID')}</td></tr>
                                ${r.token_number ? `<tr class="table-warning"><th class="font-weight-bold">STROOM / TOKEN</th><td class="font-weight-bold font-monospace text-dark" style="font-size: 0.9rem;">${r.token_number}</td></tr>` : ''}
                            </table>
                            ${r.ocr_error_message ? `<div class="alert alert-danger py-1.5 px-2 mt-2"><small><strong>Catatan Error AI:</strong> ${r.ocr_error_message}</small></div>` : ''}
                            ${r.notes ? `<div class="alert alert-secondary py-1.5 px-2 mt-2"><small><strong>Catatan:</strong> ${r.notes}</small></div>` : ''}
                        </div>
                    </div>
                `;
                $('#detail-receipt-body').html(html);
                window.showModal('#modalDetailReceipt');
            }
        });
    });

    // Delete Receipt
    $(document).on('click', '.btn-delete', function(e) {
        e.preventDefault();
        let url = $(this).data('url');
        let name = $(this).data('name');

        Swal.fire({
            title: 'Hapus Bukti Pembayaran?',
            text: `Bukti pembayaran "${name}" akan dihapus dari sistem.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: "DELETE",
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(res) {
                        tableReceipts.draw(false);
                        Swal.fire({
                            icon: 'success',
                            title: 'Dihapus!',
                            text: res.message || 'Data berhasil dihapus.',
                            timer: 2000,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        Swal.fire('Gagal!', xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan.', 'error');
                    }
                });
            }
        });
    });
});
</script>
@stop
