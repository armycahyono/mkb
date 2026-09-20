@extends('adminlte::page')

@section('title', 'Tanggungan Kasbon & Talangan - ' . $fundingSource)

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap" style="padding: 2px 0;">
    <div>
        <h1 class="page-title"><i class="fas fa-user-clock text-warning mr-1"></i> Tanggungan Kasbon & Talangan {{ $fundingSource }}</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-house"></i> Dashboard</a></li>
                <li class="breadcrumb-item"><i class="fas fa-calculator"></i> Penganggaran / {{ $fundingSource }}</li>
                <li class="breadcrumb-item"><a href="{{ route($routePrefix . '.kas-tunai.index') }}">Kas Operasional</a></li>
                <li class="breadcrumb-item active">Tanggungan Kasbon</li>
            </ol>
        </nav>
    </div>
    <div class="header-actions mt-2 mt-md-0">
        <a href="{{ route($routePrefix . '.kas-tunai.index') }}" class="btn btn-sm btn-outline-secondary mr-1">
            <i class="fas fa-arrow-left mr-1"></i> Kembali ke Buku Kas
        </a>
        <button type="button" class="btn btn-sm btn-outline-info" onclick="window.print()">
            <i class="fas fa-print mr-1"></i> Cetak Daftar Tagihan
        </button>
    </div>
</div>
@stop

@section('css')
<style>
    body, .content-wrapper, .card, table, .btn, .form-control {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
    }
    h1.page-title {
        font-family: 'Inter', sans-serif !important;
        font-size: 1.15rem !important;
        font-weight: 700 !important;
        letter-spacing: -0.015em;
        margin-bottom: 0;
    }
    .content-header .breadcrumb {
        font-size: 0.76rem !important;
        margin-top: 2px;
    }
    .stat-card {
        border-radius: 8px;
        border: 1px solid rgba(0,0,0,0.06);
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        transition: transform 0.15s ease-in-out;
    }
    .stat-card:hover {
        transform: translateY(-2px);
    }
    .stat-value {
        font-size: 1.35rem;
        font-weight: 700;
        line-height: 1.2;
    }
    .stat-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
        color: #6c757d;
    }
    .table-kasbon {
        font-size: 0.8125rem !important;
    }
    .table-kasbon th {
        font-size: 0.74rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 0.45rem 0.5rem !important;
        vertical-align: middle;
        background-color: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
    }
    .table-kasbon td {
        padding: 0.45rem 0.5rem !important;
        vertical-align: middle;
    }
    .text-center, th.text-center, td.text-center {
        text-align: center !important;
    }
    .text-right, .text-end, th.text-right, td.text-right, th.text-end, td.text-end {
        text-align: right !important;
    }
    .badge-status-new { background-color: #d1fae5; color: #065f46; font-weight: 600; }
    .badge-status-warning { background-color: #fef3c7; color: #92400e; font-weight: 700; }
    .badge-status-danger { background-color: #fee2e2; color: #991b1b; font-weight: 700; }

    .btn-xs {
        padding: 2px 7px !important;
        font-size: 0.75rem !important;
        line-height: 1.25 !important;
        border-radius: 4px !important;
    }

    /* Kasbon Row Click & Expandable Detail */
    .kasbon-row {
        background-color: #ffffff !important;
        cursor: pointer;
        transition: background-color 0.15s ease-in-out;
    }
    .kasbon-row:hover {
        background-color: #f1f5f9 !important;
    }
    .kasbon-row.is-expanded {
        background-color: #eff6ff !important;
        border-left: 4px solid #0284c7 !important;
    }
    .kasbon-row .row-toggle-icon {
        display: inline-block;
        transition: transform 0.2s ease-in-out;
        font-size: 0.72rem;
        width: 14px;
        text-align: center;
        color: #94a3b8;
    }
    .kasbon-row.is-expanded .row-toggle-icon {
        transform: rotate(90deg);
        color: #0284c7 !important;
    }
    .kasbon-detail-row {
        background-color: #f8fafc;
    }
    .kasbon-detail-box {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    #tableKasbonRekap tbody tr {
        cursor: pointer;
        transition: background-color 0.15s ease-in-out;
    }
    #tableKasbonRekap tbody tr:hover {
        background-color: #f1f5f9 !important;
    }
    @media (max-width: 767.98px) {
        .table-mobile-cards tbody tr.kasbon-detail-row td {
            display: block !important;
            width: 100% !important;
            padding: 0 !important;
        }
        .table-mobile-cards tbody tr.kasbon-detail-row td::before {
            display: none !important;
        }
    }

    .modal-header .close,
    .modal-header .close:focus,
    .modal-header .close:hover,
    .modal-header .close:active {
        border: none !important;
        outline: none !important;
        box-shadow: none !important;
        background: transparent !important;
        opacity: 0.85;
        font-size: 1.6rem;
        line-height: 1;
        padding: 0 !important;
        margin: 0 !important;
        cursor: pointer;
    }
    .modal-header .close:hover {
        opacity: 1 !important;
    }

    @media print {
        .main-sidebar, .content-header .header-actions, .card-header, .btn, .nav-pills, .filter-wrapper {
            display: none !important;
        }
        .content-wrapper {
            margin-left: 0 !important;
            padding: 0 !important;
        }
        .stat-card {
            border: 1px solid #ccc !important;
            box-shadow: none !important;
        }
    }
</style>
@stop

@section('content')
{{-- Stat Cards --}}
<div class="row mb-3">
    <div class="col-xl-4 col-md-4 mb-2">
        <div class="card stat-card bg-white p-3 border-left border-danger" style="border-left-width: 4px !important;">
            <div class="stat-label">Total Uang Belum SPJ</div>
            <div class="stat-value text-danger mt-1">Rp {{ number_format($totalPendingAmount, 0, ',', '.') }}</div>
            <small class="text-muted">Total uang kasbon/talangan yang masih di luar</small>
        </div>
    </div>
    <div class="col-xl-4 col-md-4 mb-2">
        <div class="card stat-card bg-white p-3 border-left border-warning" style="border-left-width: 4px !important;">
            <div class="stat-label">Jumlah Orang Membawa Uang</div>
            <div class="stat-value text-warning mt-1">{{ $countPersons }} Orang</div>
            <small class="text-muted">Dari total {{ $countPanjarPending }} transaksi kasbon/panjar aktif</small>
        </div>
    </div>
    <div class="col-xl-4 col-md-4 mb-2">
        <div class="card stat-card bg-white p-3 border-left border-info" style="border-left-width: 4px !important;">
            <div class="stat-label">Kasbon Paling Lama</div>
            <div class="stat-value {{ $oldestDays > 7 ? 'text-danger' : ($oldestDays > 3 ? 'text-warning' : 'text-info') }} mt-1">
                {{ $oldestDays }} Hari
            </div>
            <small class="text-muted">
                @if($oldestDays > 7)
                    <span class="text-danger font-weight-bold"><i class="fas fa-exclamation-triangle"></i> Segera tagih nota SPJ!</span>
                @else
                    Waktu perputaran nota kasbon
                @endif
            </small>
        </div>
    </div>
</div>

{{-- Main Card --}}
<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-bottom p-2 d-flex justify-content-between align-items-center flex-wrap">
        {{-- Inner Sub-Tabs (2 Tabs) --}}
        <ul class="nav nav-pills" id="kasbonTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active font-weight-bold py-1 px-3" id="tab-detail-link" data-bs-toggle="pill" data-bs-target="#tab-detail" data-toggle="pill" href="#tab-detail" role="tab">
                    <i class="fas fa-list-ul mr-1 text-primary"></i> Daftar Kasbon ({{ count($panjarList) }})
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link font-weight-bold py-1 px-3" id="tab-rekap-orang-link" data-bs-toggle="pill" data-bs-target="#tab-rekap-orang" data-toggle="pill" href="#tab-rekap-orang" role="tab">
                    <i class="fas fa-users mr-1 text-info"></i> Rekap Kasbon Per Orang ({{ $countPersons }})
                </a>
            </li>
        </ul>

        {{-- Filters --}}
        <div class="d-flex align-items-center flex-wrap ms-auto ml-auto mt-2 mt-md-0 filter-wrapper" style="gap: 8px;">
            {{-- Filter Status Kasbon --}}
            <div class="input-group input-group-sm shadow-xs" style="width: auto;" id="wrapperKasbonStatusFilter">
                <span class="input-group-text bg-light text-muted font-weight-bold" style="height: 31px; font-size: 0.8125rem;">
                    <i class="fas fa-filter text-primary mr-1"></i> Filter:
                </span>
                <select id="filterKasbonStatus" class="form-select form-select-sm font-weight-bold text-dark" style="height: 31px; font-size: 0.8125rem; cursor: pointer;">
                    <option value="">Semua Kasbon ({{ count($panjarList) }})</option>
                    <option value="unsettled">Kasbon Belum Selesai ({{ $countKasbonPending }})</option>
                    <option value="settled">Kasbon Selesai ({{ $countKasbonSettled }})</option>
                </select>
            </div>

            {{-- Search Input --}}
            <div class="input-group input-group-sm shadow-xs" style="width: 200px;">
                <span class="input-group-text bg-light text-muted" style="height: 31px;">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" id="searchKasbonTable" class="form-control form-control-sm" placeholder="Cari nama / keperluan..." style="height: 31px;">
            </div>

            <button type="button" class="btn btn-sm btn-outline-info shadow-xs" id="btnToggleAllDetails" title="Buka / Tutup Seluruh Riwayat Kasbon" style="height: 31px; font-size: 0.8rem;">
                <i class="fas fa-layer-group mr-1"></i> <span id="textToggleAll">Buka Semua</span>
            </button>
            <span class="badge badge-light border text-primary d-none d-lg-inline-flex align-items-center" style="height: 31px; font-size: 0.76rem;">
                <i class="fas fa-hand-pointer mr-1"></i> Klik baris untuk detail
            </span>
            <button type="button" class="btn btn-sm btn-outline-primary d-md-none shadow-xs" id="btnToggleMobileCards" title="Ganti Tampilan Kartu / Tabel" style="height: 31px; font-size: 0.8rem;">
                <i class="fas fa-table mr-1"></i> <span id="textToggleView">Mode Tabel</span>
            </button>
        </div>
    </div>

    <div class="card-body p-2">
        <div class="tab-content" id="kasbonTabsContent">
            {{-- Tab 1: Daftar Kasbon (Semua: Belum Selesai & Selesai) --}}
            <div class="tab-pane fade show active" id="tab-detail" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover w-100 table-kasbon table-mobile-cards" id="tableKasbonDetail">
                        <thead>
                            <tr>
                                <th style="width: 5%;" class="text-center">No</th>
                                <th style="width: 12%; cursor: pointer;" class="sortable" data-col="date">
                                    Tanggal Kasbon <i class="fas fa-sort-down text-primary ml-1" id="iconSortDate"></i>
                                </th>
                                <th style="width: 20%; cursor: pointer;" class="sortable" data-col="person">
                                    Nama Penerima <i class="fas fa-sort text-muted ml-1"></i>
                                </th>
                                <th style="width: 25%; cursor: pointer;" class="sortable" data-col="desc">
                                    Keperluan Belanja <i class="fas fa-sort text-muted ml-1"></i>
                                </th>
                                <th style="width: 14%; cursor: pointer;" class="text-center sortable" data-col="status">
                                    Status Kasbon <i class="fas fa-sort text-muted ml-1"></i>
                                </th>
                                <th style="width: 12%; cursor: pointer;" class="text-right sortable" data-col="amount">
                                    Nominal (Rp) <i class="fas fa-sort text-muted ml-1"></i>
                                </th>
                                <th style="width: 12%;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $hasSettledDividerRendered = false;
                            @endphp
                            @forelse($panjarList as $idx => $p)
                                {{-- Separator antara kasbon belum selesai dan kasbon selesai --}}
                                @if(!$hasSettledDividerRendered && ($p['completion_status'] ?? '') === 'settled')
                                    @php $hasSettledDividerRendered = true; @endphp
                                    <tr class="kasbon-section-divider table-light font-weight-bold" data-section="settled">
                                        <td colspan="7" class="py-2 px-3 text-secondary border-top border-bottom" style="background-color: #f1f5f9; font-size: 0.82rem;">
                                            <i class="fas fa-check-circle text-success mr-1"></i> <strong>Kasbon Selesai (Sudah Di-SPJ / Lunas)</strong>
                                        </td>
                                    </tr>
                                @endif

                                <tr class="kasbon-row {{ ($p['completion_status'] ?? '') === 'settled' ? 'kasbon-settled-row' : '' }}"
                                    data-id="{{ $p['id'] }}"
                                    data-completion="{{ $p['completion_status'] ?? 'unsettled' }}"
                                    data-status="{{ $p['aging_status'] ?? 'new' }}"
                                    data-date="{{ $p['transaction_date'] ? \Carbon\Carbon::parse($p['transaction_date'])->format('Y-m-d') : '' }}"
                                    data-person="{{ strtolower($p['person_name'] ?? '') }}"
                                    data-desc="{{ strtolower($p['description'] ?? '') }}"
                                    data-amount="{{ ($p['completion_status'] ?? '') === 'settled' ? $p['amount'] : ($p['remaining_amount'] ?? $p['amount']) }}"
                                    data-search="{{ strtolower(($p['person_name'] ?? '') . ' ' . ($p['description'] ?? '') . ' ' . ($p['evidence_number'] ?? '')) }}"
                                    title="Klik baris ini untuk melihat riwayat kasbon & SPJ">
                                    <td class="text-center font-weight-bold td-index" data-label="#">
                                        <i class="fas fa-chevron-right row-toggle-icon mr-1"></i>#{{ $idx + 1 }}
                                    </td>
                                    <td data-label="Tanggal">
                                        <i class="fas fa-calendar-alt text-muted mr-1"></i> {{ $p['formatted_date'] }}
                                    </td>
                                    <td data-label="Penerima">
                                        <span class="text-dark">{{ $p['person_name'] }}</span>
                                        @if($p['guru_id'])
                                            <span class="badge badge-light border text-muted ml-1" style="font-size: 0.7rem;">Guru/Staf</span>
                                        @endif
                                    </td>
                                    <td data-label="Keperluan">
                                        <div class="text-dark">{{ $p['description'] }}</div>
                                        @if(($p['completion_status'] ?? '') === 'settled')
                                            <small class="text-muted"><i class="fas fa-check-circle text-success mr-1"></i>Selesai: {{ $p['settled_date'] ?? '-' }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center" data-label="Status Kasbon">
                                        @if(($p['completion_status'] ?? '') === 'settled')
                                            <span class="badge badge-success px-2 py-1" style="font-size: 0.78rem;">
                                                <i class="fas fa-check-double mr-1"></i> Selesai SPJ
                                            </span>
                                        @elseif(($p['panjar_status'] ?? '') === 'partial')
                                            <span class="badge badge-warning text-dark px-2 py-1" style="font-size: 0.78rem;">
                                                <i class="fas fa-hourglass-half mr-1"></i> Dicicil ({{ $p['aging_days'] }} hr)
                                            </span>
                                        @else
                                            <span class="badge {{ $p['aging_badge_class'] }} px-2 py-1" style="font-size: 0.78rem;">
                                                @if($p['aging_status'] === 'danger')
                                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                                @elseif($p['aging_status'] === 'warning')
                                                    <i class="fas fa-clock mr-1"></i>
                                                @else
                                                    <i class="fas fa-check mr-1"></i>
                                                @endif
                                                {{ $p['aging_label'] }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-right font-weight-bold" data-label="Nominal">
                                        @if(($p['completion_status'] ?? '') === 'settled')
                                            <div class="text-dark">{{ $p['formatted_amount'] }}</div>
                                            <small class="text-success font-weight-bold d-block" style="font-size: 0.7rem;">Lunas</small>
                                        @elseif(($p['panjar_status'] ?? '') === 'partial')
                                            <div class="text-danger">{{ $p['formatted_remaining'] }}</div>
                                            <small class="text-muted d-block" style="font-weight: normal; font-size: 0.7rem;">Awal: {{ $p['formatted_amount'] }}</small>
                                        @else
                                            <div class="text-danger">{{ $p['formatted_amount'] }}</div>
                                            <small class="text-muted d-block" style="font-weight: normal; font-size: 0.7rem;">Belum SPJ</small>
                                        @endif
                                    </td>
                                    <td class="text-center" data-label="Aksi">
                                        <div class="d-flex justify-content-center align-items-center">
                                            @if(($p['completion_status'] ?? '') === 'settled')
                                                <button type="button" class="btn btn-xs btn-outline-success font-weight-bold btn-toggle-row-detail shadow-xs" data-id="{{ $p['id'] }}">
                                                    <i class="fas fa-eye mr-1"></i> Detail SPJ
                                                </button>
                                            @else
                                                <button type="button" class="btn btn-xs {{ (($p['panjar_status'] ?? '') === 'partial') ? 'btn-info' : 'btn-warning' }} font-weight-bold btn-settle-panjar shadow-xs"
                                                        data-id="{{ $p['id'] }}"
                                                        data-amount="{{ $p['amount'] }}"
                                                        data-remaining="{{ $p['remaining_amount'] ?? $p['amount'] }}"
                                                        data-settled="{{ $p['settled_amount'] ?? 0 }}"
                                                        data-status="{{ $p['panjar_status'] ?? 'pending' }}"
                                                        data-date="{{ $p['transaction_date'] ? \Carbon\Carbon::parse($p['transaction_date'])->translatedFormat('d F Y') : '-' }}"
                                                        data-desc="{{ $p['description'] }}"
                                                        data-person="{{ $p['person_name'] }}"
                                                        data-rb-id="{{ $p['bos_rencana_belanja_id'] ?? '' }}"
                                                        data-pi-id="{{ $p['perjadin_item_id'] ?? '' }}">
                                                    <i class="fas fa-check-circle mr-1"></i> {{ (($p['panjar_status'] ?? '') === 'partial') ? 'SPJ Lanjutan' : 'Selesai SPJ' }}
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>

                                {{-- Sub-Baris Riwayat Kasbon & Pembayaran (Collapsible - Tanpa Statistik) --}}
                                <tr class="kasbon-detail-row" id="kasbonDetailRow-{{ $p['id'] }}" style="display: none;">
                                    <td colspan="7" class="p-0 border-0">
                                        <div class="kasbon-detail-box m-2 p-3 border-left {{ ($p['completion_status'] ?? '') === 'settled' ? 'border-success' : 'border-primary' }}" style="border-left-width: 4px !important;">
                                            {{-- Detail Header --}}
                                            <div class="d-flex justify-content-between align-items-center flex-wrap pb-2 mb-2 border-bottom">
                                                <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                                                    <h6 class="font-weight-bold text-dark mb-0" style="font-size: 0.95rem;">
                                                        <i class="fas {{ ($p['completion_status'] ?? '') === 'settled' ? 'fa-check-circle text-success' : 'fa-history text-primary' }} mr-1"></i>
                                                        Riwayat Kasbon & Pembayaran: <span class="text-primary font-monospace">{{ $p['id_display'] ?? ('KT'.str_pad((string) $p['id'], 4, '0', STR_PAD_LEFT)) }}</span>
                                                    </h6>
                                                    <span class="badge badge-light border text-dark">
                                                        <i class="fas fa-user text-muted mr-1"></i> {{ $p['person_name'] }}
                                                    </span>
                                                    @if(($p['completion_status'] ?? '') === 'settled')
                                                        <span class="badge badge-success font-weight-bold"><i class="fas fa-check-double mr-1"></i> Lunas Selesai</span>
                                                    @elseif(($p['panjar_status'] ?? '') === 'partial')
                                                        <span class="badge badge-warning text-dark font-weight-bold"><i class="fas fa-hourglass-half mr-1"></i> Dicicil Sebagian</span>
                                                    @else
                                                        <span class="badge badge-danger font-weight-bold"><i class="fas fa-clock mr-1"></i> Belum Selesai SPJ</span>
                                                    @endif
                                                    <span class="badge badge-info font-weight-bold"><i class="fas fa-wallet mr-1"></i> {{ $p['funding_source'] ?? 'BOSP' }}</span>
                                                </div>
                                                <div class="d-flex align-items-center mt-2 mt-md-0" style="gap: 6px;">
                                                    <button type="button" class="btn btn-xs btn-outline-secondary btn-close-kasbon-detail" data-id="{{ $p['id'] }}">
                                                        <i class="fas fa-times mr-1"></i> Tutup
                                                    </button>
                                                </div>
                                            </div>

                                            {{-- History Table (Statistik Dihapus Sesuai Instruksi) --}}
                                            <div class="table-responsive border rounded mb-2 bg-white">
                                                <table class="table table-sm table-striped mb-0" style="font-size: 0.78rem;">
                                                    <thead class="bg-light text-muted">
                                                        <tr>
                                                            <th style="width: 4%;" class="text-center">#</th>
                                                            <th style="width: 12%;">Tanggal</th>
                                                            <th style="width: 18%;">Jenis Transaksi</th>
                                                            <th style="width: 26%;">Uraian / Keterangan</th>
                                                            <th style="width: 14%;" class="text-right">Nominal (Rp)</th>
                                                            <th style="width: 14%;">No. Bukti / Nota</th>
                                                            <th style="width: 12%;" class="text-center">Bukti / Rincian</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        {{-- 1. Penyerahan Kasbon Awal --}}
                                                        <tr class="bg-white">
                                                            <td class="text-center font-weight-bold text-muted">1</td>
                                                            <td>
                                                                <i class="fas fa-calendar-alt text-muted mr-1"></i> {{ $p['formatted_date'] }}
                                                            </td>
                                                            <td>
                                                                <span class="badge badge-warning text-dark font-weight-bold">
                                                                    <i class="fas fa-hand-holding-usd mr-1"></i> Uang Muka / Kasbon
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <strong>{{ $p['description'] ?: 'Kasbon Belanja' }}</strong>
                                                                <small class="text-muted d-block">Penerima: {{ $p['person_name'] }}</small>
                                                            </td>
                                                            <td class="text-right font-weight-bold {{ ($p['completion_status'] ?? '') === 'settled' ? 'text-dark' : 'text-danger' }}">
                                                                {{ ($p['completion_status'] ?? '') === 'settled' ? '' : '- ' }}{{ $p['formatted_amount'] }}
                                                            </td>
                                                            <td>
                                                                <span class="text-muted">{{ $p['evidence_number'] ?: '-' }}</span>
                                                            </td>
                                                            <td class="text-center">
                                                                @if(!empty($p->receipt_file_url))
                                                                    <button type="button" class="btn btn-xs btn-outline-info btn-preview-receipt"
                                                                            data-url="{{ $p->receipt_file_url }}"
                                                                            data-is-image="{{ $p->is_receipt_image ? '1' : '0' }}"
                                                                            data-title="Bukti Kasbon {{ $p['id_display'] ?? ('KT'.str_pad((string) $p['id'], 4, '0', STR_PAD_LEFT)) }}"
                                                                            data-desc="{{ $p['description'] }}"
                                                                            data-person="{{ $p['person_name'] }}"
                                                                            data-evidence="{{ $p['evidence_number'] }}"
                                                                            data-date="{{ $p['formatted_date'] }}">
                                                                        <i class="fas fa-image mr-1"></i> Bukti
                                                                    </button>
                                                                @else
                                                                    <span class="text-muted">-</span>
                                                                @endif
                                                            </td>
                                                        </tr>

                                                        {{-- 2+. Riwayat Pembayaran / SPJ (childKass) --}}
                                                        @if($p->childKass && $p->childKass->count() > 0)
                                                            @foreach($p->childKass as $cIdx => $child)
                                                                <tr>
                                                                    <td class="text-center font-weight-bold text-muted">{{ $cIdx + 2 }}</td>
                                                                    <td>
                                                                        <i class="fas fa-calendar-check text-success mr-1"></i> {{ $child->formatted_date }}
                                                                    </td>
                                                                    <td>
                                                                        @if($child->category === 'sisa_panjar')
                                                                            <span class="badge badge-success font-weight-bold">
                                                                                <i class="fas fa-coins mr-1"></i> Pengembalian Sisa Uang
                                                                            </span>
                                                                        @elseif($child->category === 'belanja_tunai')
                                                                            <span class="badge badge-danger font-weight-bold">
                                                                                <i class="fas fa-receipt mr-1"></i> Belanja Nota SPJ
                                                                            </span>
                                                                        @elseif($child->category === 'operasional_laporan')
                                                                            <span class="badge badge-info font-weight-bold">
                                                                                <i class="fas fa-tools mr-1"></i> Biaya Lain-lain
                                                                            </span>
                                                                        @else
                                                                            <span class="badge badge-secondary font-weight-bold">
                                                                                {{ $child->category_label }}
                                                                            </span>
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        <div>{{ $child->description ?: '-' }}</div>
                                                                        @if($child->createdBy)
                                                                            <small class="text-muted">Oleh: {{ $child->createdBy->name }}</small>
                                                                        @endif
                                                                    </td>
                                                                    <td class="text-right font-weight-bold {{ $child->transaction_type === 'masuk' ? 'text-success' : 'text-danger' }}">
                                                                        {{ $child->transaction_type === 'masuk' ? '+' : '' }} {{ $child->formatted_amount }}
                                                                    </td>
                                                                    <td>
                                                                        <span class="text-dark">{{ $child->evidence_number ?: '-' }}</span>
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <div class="d-flex justify-content-center align-items-center" style="gap: 4px;">
                                                                            @if(!empty($child->receipt_file_url))
                                                                                <button type="button" class="btn btn-xs btn-outline-info btn-preview-receipt"
                                                                                        data-url="{{ $child->receipt_file_url }}"
                                                                                        data-is-image="{{ $child->is_receipt_image ? '1' : '0' }}"
                                                                                        data-title="Nota {{ $child->id_display }}"
                                                                                        data-desc="{{ $child->description }}"
                                                                                        data-person="{{ $child->person_name }}"
                                                                                        data-evidence="{{ $child->evidence_number }}"
                                                                                        data-date="{{ $child->formatted_date }}"
                                                                                        title="Lihat Bukti Foto / Nota">
                                                                                    <i class="fas fa-image"></i>
                                                                                </button>
                                                                            @endif
                                                                            @if($child->items && $child->items->count() > 0)
                                                                                <button type="button" class="btn btn-xs btn-outline-primary btn-view-receipt-items"
                                                                                        data-id="{{ $child->id }}"
                                                                                        title="Lihat {{ $child->items->count() }} Rincian Barang">
                                                                                    <i class="fas fa-list-ul"></i> ({{ $child->items->count() }})
                                                                                </button>
                                                                            @endif
                                                                            @if(empty($child->receipt_file_url) && (!$child->items || $child->items->count() === 0))
                                                                                <span class="text-muted">-</span>
                                                                            @endif
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        @else
                                                            <tr>
                                                                <td colspan="7" class="text-center py-2 bg-light text-muted">
                                                                    <i class="fas fa-hourglass-half text-warning mr-1"></i>
                                                                    Belum ada riwayat pembayaran atau SPJ untuk kasbon ini. Uang masih berada di tangan <strong>{{ $p['person_name'] }}</strong>.
                                                                </td>
                                                            </tr>
                                                        @endif
                                                    </tbody>
                                                </table>
                                            </div>

                                            {{-- Detail Footer Actions --}}
                                            <div class="d-flex justify-content-between align-items-center flex-wrap pt-1">
                                                <small class="text-muted">
                                                    @if(($p['completion_status'] ?? '') === 'settled')
                                                        <i class="fas fa-check-circle text-success mr-1"></i> Kasbon telah lunas tuntas dipertanggungjawabkan.
                                                    @else
                                                        <i class="fas fa-info-circle mr-1"></i> Selesaikan SPJ untuk menutup atau mencicil tanggungan kasbon ini.
                                                    @endif
                                                </small>
                                                <div class="d-flex align-items-center mt-2 mt-sm-0" style="gap: 6px;">
                                                    @if(($p['completion_status'] ?? '') !== 'settled')
                                                        <button type="button" class="btn btn-xs btn-warning font-weight-bold btn-settle-panjar shadow-xs"
                                                                data-id="{{ $p['id'] }}"
                                                                data-amount="{{ $p['amount'] }}"
                                                                data-remaining="{{ $p['remaining_amount'] ?? $p['amount'] }}"
                                                                data-settled="{{ $p['settled_amount'] ?? 0 }}"
                                                                data-status="{{ $p['panjar_status'] ?? 'pending' }}"
                                                                data-date="{{ $p['transaction_date'] ? \Carbon\Carbon::parse($p['transaction_date'])->translatedFormat('d F Y') : '-' }}"
                                                                data-desc="{{ $p['description'] }}"
                                                                data-person="{{ $p['person_name'] }}"
                                                                data-rb-id="{{ $p['bos_rencana_belanja_id'] ?? '' }}"
                                                                data-pi-id="{{ $p['perjadin_item_id'] ?? '' }}">
                                                            <i class="fas fa-check-circle mr-1"></i> {{ (($p['panjar_status'] ?? '') === 'partial') ? 'Lanjutkan SPJ Cicilan' : 'Selesaikan SPJ Sekarang' }}
                                                        </button>
                                                    @endif
                                                    <button type="button" class="btn btn-xs btn-outline-secondary btn-close-kasbon-detail" data-id="{{ $p['id'] }}">
                                                        <i class="fas fa-chevron-up mr-1"></i> Tutup Riwayat
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="fas fa-check-circle text-success fa-2x mb-2 d-block"></i>
                                        Tidak ada catatan kasbon yang ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(count($panjarList) > 0)
                            <tfoot>
                                <tr class="bg-light font-weight-bold" style="border-top: 2px solid #cbd5e1;">
                                    <td colspan="5" class="text-right py-2">Total Tanggungan Kasbon Belum SPJ:</td>
                                    <td class="text-right text-danger py-2" style="font-size: 0.95rem;">
                                        Rp {{ number_format($totalPendingAmount, 0, ',', '.') }}
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            {{-- Tab 2: Rekapitulasi Per Orang (Dengan Kolom Sorting) --}}
            <div class="tab-pane fade" id="tab-rekap-orang" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover table-striped w-100 table-kasbon" id="tableKasbonRekap">
                        <thead>
                            <tr>
                                <th style="width: 5%;" class="text-center">No</th>
                                <th style="width: 35%; cursor: pointer;" class="sortable-rekap" data-col="name">
                                    Nama Guru / Staf Penerima Uang <i class="fas fa-sort text-muted ml-1"></i>
                                </th>
                                <th style="width: 15%; cursor: pointer;" class="text-center sortable-rekap" data-col="count">
                                    Jumlah Transaksi <i class="fas fa-sort text-muted ml-1"></i>
                                </th>
                                <th style="width: 15%; cursor: pointer;" class="text-center sortable-rekap" data-col="days">
                                    Kasbon Terlama <i class="fas fa-sort text-muted ml-1"></i>
                                </th>
                                <th style="width: 15%; cursor: pointer;" class="text-center sortable-rekap" data-col="status">
                                    Status Tagihan <i class="fas fa-sort text-muted ml-1"></i>
                                </th>
                                <th style="width: 15%; cursor: pointer;" class="text-right sortable-rekap" data-col="amount">
                                    Total Uang Dibawa (Rp) <i class="fas fa-sort-down text-primary ml-1" id="iconSortRekapAmount"></i>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $personIdx = 1; @endphp
                            @forelse($personSummary as $ps)
                                <tr data-name="{{ strtolower($ps['name']) }}"
                                    data-count="{{ $ps['count'] }}"
                                    data-days="{{ $ps['oldest_days'] }}"
                                    data-status="{{ $ps['worst_status'] }}"
                                    data-amount="{{ $ps['total_amount'] }}">
                                    <td class="text-center font-weight-bold">{{ $personIdx++ }}</td>
                                    <td>
                                        <span class="text-dark" style="font-size: 0.875rem;">{{ $ps['name'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-info px-2 py-1">{{ $ps['count'] }} Kasbon</span>
                                    </td>
                                    <td class="text-center font-weight-bold text-muted">
                                        {{ $ps['oldest_days'] }} Hari
                                    </td>
                                    <td class="text-center">
                                        @if($ps['worst_status'] === 'danger')
                                            <span class="badge badge-danger px-2 py-1"><i class="fas fa-exclamation-circle mr-1"></i> Menunggak</span>
                                        @elseif($ps['worst_status'] === 'warning')
                                            <span class="badge badge-warning px-2 py-1"><i class="fas fa-clock mr-1"></i> Perhatian</span>
                                        @else
                                            <span class="badge badge-success px-2 py-1"><i class="fas fa-check mr-1"></i> Baru</span>
                                        @endif
                                    </td>
                                    <td class="text-right font-weight-bold text-danger" style="font-size: 0.95rem;">
                                        {{ $ps['formatted_total_amount'] }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="fas fa-users text-muted fa-2x mb-2 d-block"></i>
                                        Tidak ada staf atau orang yang memiliki tanggungan kasbon.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(count($personSummary) > 0)
                            <tfoot>
                                <tr class="bg-light font-weight-bold" style="border-top: 2px solid #cbd5e1;">
                                    <td colspan="5" class="text-right py-2">Total Akumulasi Seluruh Orang:</td>
                                    <td class="text-right text-danger py-2" style="font-size: 0.95rem;">
                                        Rp {{ number_format($totalPendingAmount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modals --}}
@include('kas_tunai.partials.modal_settle')
@include('kas_tunai.partials.modal_receipt_items')
@include('kas_tunai.partials.modal_preview_receipt')
@include('kas_tunai.partials.mobile_fab_entry')

@stop

@section('js')
<script>
$(document).ready(function() {
    // Toggle Mobile Card / Table View
    $('#btnToggleMobileCards').on('click', function() {
        let $t = $('#tableKasbonDetail');
        $t.toggleClass('force-table-view');
        if ($t.hasClass('force-table-view')) {
            $('#textToggleView').text('Mode Kartu');
            $(this).find('i').removeClass('fa-table').addClass('fa-id-card');
        } else {
            $('#textToggleView').text('Mode Tabel');
            $(this).find('i').removeClass('fa-id-card').addClass('fa-table');
        }
    });

    // Tab Change Listener untuk menyesuaikan filter
    $(document).on('shown.bs.tab', '#kasbonTabs a[data-toggle="pill"], #kasbonTabs a[data-bs-toggle="pill"]', function(e) {
        let target = $(e.target).attr('href') || $(e.target).data('bs-target');
        if (target === '#tab-rekap-orang') {
            $('#wrapperKasbonStatusFilter').hide();
            $('#btnToggleAllDetails').hide();
        } else {
            $('#wrapperKasbonStatusFilter').show();
            $('#btnToggleAllDetails').show();
        }
    });

    // Modal Helpers
    window.showKasModal = function(modalId) {
        let el = document.querySelector(modalId);
        if (!el) return;
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            let inst = bootstrap.Modal.getInstance(el);
            if (!inst) inst = new bootstrap.Modal(el, { backdrop: 'static', keyboard: false });
            inst.show();
        } else if ($.fn.modal) {
            $(el).modal({ backdrop: 'static', keyboard: false, show: true });
        } else {
            $(el).addClass('show').css('display', 'block').attr('aria-modal', 'true').removeAttr('aria-hidden');
            $('body').addClass('modal-open').append('<div class="modal-backdrop fade show custom-kas-backdrop"></div>');
        }
    };

    window.hideKasModal = function(modalId) {
        let el = document.querySelector(modalId);
        if (!el) return;
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            let inst = bootstrap.Modal.getInstance(el);
            if (inst) inst.hide();
        }
        if ($.fn.modal) {
            $(el).modal('hide');
        }
        $(el).removeClass('show').css('display', 'none').attr('aria-hidden', 'true').removeAttr('aria-modal');
        $('.custom-kas-backdrop').remove();
        $('body').removeClass('modal-open').css('padding-right', '');
    };

    $(document).on('click', '.btn-dismiss-modal', function() {
        let $modal = $(this).closest('.modal');
        if ($modal.length) hideKasModal('#' + $modal.attr('id'));
    });

    // Item row helper
    let settleItemIndex = 0;
    window.renderItemRow = function(prefix, index, item = {}) {
        let name = item.item_name || '';
        let qty = item.quantity !== undefined ? item.quantity : 1;
        let unit = item.unit || 'pcs';
        let price = item.price ? Number(item.price).toLocaleString('id-ID') : '';
        let numPrice = item.price || 0;
        let numQty = parseFloat(qty) || 0;
        let total = Math.round(numQty * numPrice);

        return `
        <tr class="item-row-${prefix}" data-index="${index}">
            <td class="p-1">
                <input type="text" name="items[${index}][item_name]" class="form-control form-control-sm item-name-input" placeholder="Nama barang / jasa" value="${name}" required>
            </td>
            <td class="p-1">
                <input type="number" step="any" min="0.01" name="items[${index}][quantity]" class="form-control form-control-sm text-center item-qty-input" placeholder="1" value="${qty}">
            </td>
            <td class="p-1">
                <input type="text" name="items[${index}][unit]" class="form-control form-control-sm text-center item-unit-input" placeholder="pcs/rim/ls" value="${unit}">
            </td>
            <td class="p-1">
                <input type="text" name="items[${index}][price]" class="form-control form-control-sm text-right item-price-input" placeholder="0" value="${price}">
            </td>
            <td class="p-1 text-right align-middle font-weight-bold">
                <span class="item-subtotal-display">Rp ${total ? total.toLocaleString('id-ID') : '0'}</span>
                <input type="hidden" name="items[${index}][total_price]" class="item-subtotal-input" value="${total}">
            </td>
            <td class="p-1 text-center align-middle">
                <button type="button" class="btn btn-xs btn-outline-danger btn-remove-item" title="Hapus"><i class="fas fa-trash-alt"></i></button>
            </td>
        </tr>`;
    };

    window.recalculateItemsTotal = function(prefix) {
        let grandTotal = 0;
        let count = 0;

        $(`#tbody${prefix}Items tr.item-row-${prefix}`).each(function() {
            count++;
            let qty = parseFloat($(this).find('.item-qty-input').val()) || 0;
            let rawPrice = $(this).find('.item-price-input').val().replace(/[^0-9]/g, '');
            let price = parseFloat(rawPrice) || 0;
            let subtotal = Math.round(qty * price);

            $(this).find('.item-subtotal-display').text('Rp ' + subtotal.toLocaleString('id-ID'));
            $(this).find('.item-subtotal-input').val(subtotal);
            grandTotal += subtotal;
        });

        $(`#total${prefix}ItemsDisplay`).text('Rp ' + grandTotal.toLocaleString('id-ID'));
        $(`#count${prefix}ItemsDisplay`).text(count);

        if (count > 0) {
            $(`#empty${prefix}ItemsNote`).hide();
            $(`#tfoot${prefix}Items`).show();
        } else {
            $(`#empty${prefix}ItemsNote`).show();
            $(`#tfoot${prefix}Items`).hide();
        }

        if (prefix === 'Settle') {
            let hasMultiReceipts = $('#receiptListContainer .receipt-card-block').length > 0;
            if (!hasMultiReceipts) {
                if (grandTotal > 0) {
                    $('#inputRealAmount').val('Rp ' + grandTotal.toLocaleString('id-ID'));
                    $('#inputNotaAmount').val('Rp ' + grandTotal.toLocaleString('id-ID'));
                    if (typeof calculateSettleBreakdown === 'function') {
                        calculateSettleBreakdown();
                    }
                }
            }
        }
    };

    // Filter table
    function filterTable() {
        let search = ($('#searchKasbonTable').val() || '').toLowerCase();
        let completionFilter = $('#filterKasbonStatus').val(); // '', 'unsettled', 'settled'

        let visibleUnsettled = 0;
        let visibleSettled = 0;

        $('#tableKasbonDetail tbody tr.kasbon-row').each(function() {
            let id = $(this).data('id');
            let rowSearch = ($(this).data('search') || '').toString();
            let rowCompletion = ($(this).data('completion') || 'unsettled').toString();

            let matchSearch = !search || rowSearch.indexOf(search) > -1;
            let matchCompletion = !completionFilter || rowCompletion === completionFilter;

            let $detailRow = $('#kasbonDetailRow-' + id);

            if (matchSearch && matchCompletion) {
                $(this).show();
                if (rowCompletion === 'settled') {
                    visibleSettled++;
                } else {
                    visibleUnsettled++;
                }
                if ($(this).hasClass('is-expanded')) {
                    $detailRow.show();
                }
            } else {
                $(this).hide();
                $detailRow.hide();
            }
        });

        // Toggle divider kasbon selesai
        let $divider = $('#tableKasbonDetail tbody tr.kasbon-section-divider');
        if ($divider.length) {
            if (!completionFilter && visibleUnsettled > 0 && visibleSettled > 0) {
                $divider.show();
            } else {
                $divider.hide();
            }
        }

        // Update nomor urut baris yang tampil
        let visibleIdx = 1;
        $('#tableKasbonDetail tbody tr.kasbon-row:visible').each(function() {
            let $td = $(this).find('td.td-index');
            if ($td.length) {
                let icon = $(this).hasClass('is-expanded') ? 'fa-chevron-down' : 'fa-chevron-right';
                $td.html('<i class="fas ' + icon + ' row-toggle-icon mr-1"></i>#' + (visibleIdx++));
            }
        });
    }

    $('#searchKasbonTable').on('keyup', filterTable);
    $('#filterKasbonStatus').on('change', filterTable);

    // Sorting Tab 1: Daftar Kasbon (Unsettled selalu di atas, Settled di bawah)
    let currentKasbonCol = 'date';
    let currentKasbonDir = 'desc';

    function sortKasbonTable(col, dir) {
        let rows = $('#tableKasbonDetail tbody > tr.kasbon-row').get();
        rows.sort(function(a, b) {
            let compA = $(a).data('completion') === 'settled' ? 1 : 0;
            let compB = $(b).data('completion') === 'settled' ? 1 : 0;
            if (compA !== compB) {
                return compA - compB; // Unsettled (0) selalu di atas, Settled (1) selalu di bawah
            }

            let valA = $(a).data(col);
            let valB = $(b).data(col);

            if (col === 'amount') {
                valA = parseFloat(valA) || 0;
                valB = parseFloat(valB) || 0;
                return dir === 'asc' ? valA - valB : valB - valA;
            } else {
                valA = (valA || '').toString().toLowerCase();
                valB = (valB || '').toString().toLowerCase();
                if (valA < valB) return dir === 'asc' ? -1 : 1;
                if (valA > valB) return dir === 'asc' ? 1 : -1;
                return 0;
            }
        });

        let $tbody = $('#tableKasbonDetail tbody');
        let $divider = $tbody.find('tr.kasbon-section-divider');
        let dividerAppended = false;

        rows.forEach(function(row) {
            let id = $(row).data('id');
            let isSettled = $(row).data('completion') === 'settled';
            let $detailRow = $('#kasbonDetailRow-' + id);

            if (isSettled && !dividerAppended && $divider.length) {
                $tbody.append($divider);
                dividerAppended = true;
            }
            $tbody.append(row);
            if ($detailRow.length) {
                $tbody.append($detailRow);
            }
        });

        filterTable();
    }

    $(document).on('click', '#tableKasbonDetail th.sortable', function() {
        let col = $(this).data('col');
        let dir = (currentKasbonCol === col && currentKasbonDir === 'asc') ? 'desc' : 'asc';
        currentKasbonCol = col;
        currentKasbonDir = dir;

        $('#tableKasbonDetail th.sortable i').attr('class', 'fas fa-sort text-muted ml-1');
        let icon = dir === 'asc' ? 'fa-sort-up text-primary' : 'fa-sort-down text-primary';
        $(this).find('i').attr('class', 'fas ' + icon + ' ml-1');

        sortKasbonTable(col, dir);
    });

    // Sorting Tab 2: Rekapitulasi Kasbon Per Orang
    let currentRekapCol = 'amount';
    let currentRekapDir = 'desc';

    function sortRekapTable(col, dir) {
        let rows = $('#tableKasbonRekap tbody > tr').get();
        if (rows.length === 0 || $(rows[0]).find('td').length <= 1) return;

        rows.sort(function(a, b) {
            let valA = $(a).data(col);
            let valB = $(b).data(col);

            if (col === 'count' || col === 'days' || col === 'amount') {
                valA = parseFloat(valA) || 0;
                valB = parseFloat(valB) || 0;
                return dir === 'asc' ? valA - valB : valB - valA;
            } else {
                valA = (valA || '').toString().toLowerCase();
                valB = (valB || '').toString().toLowerCase();
                if (valA < valB) return dir === 'asc' ? -1 : 1;
                if (valA > valB) return dir === 'asc' ? 1 : -1;
                return 0;
            }
        });

        let $tbody = $('#tableKasbonRekap tbody');
        let idx = 1;
        rows.forEach(function(row) {
            $tbody.append(row);
            $(row).find('td:first').text(idx++);
        });
    }

    $(document).on('click', '#tableKasbonRekap th.sortable-rekap', function() {
        let col = $(this).data('col');
        let dir = (currentRekapCol === col && currentRekapDir === 'asc') ? 'desc' : 'asc';
        currentRekapCol = col;
        currentRekapDir = dir;

        $('#tableKasbonRekap th.sortable-rekap i').attr('class', 'fas fa-sort text-muted ml-1');
        let icon = dir === 'asc' ? 'fa-sort-up text-primary' : 'fa-sort-down text-primary';
        $(this).find('i').attr('class', 'fas ' + icon + ' ml-1');

        sortRekapTable(col, dir);
    });

    // Klik baris kasbon untuk memunculkan / menutup riwayat kasbon & SPJ
    $(document).on('click', '#tableKasbonDetail tbody tr.kasbon-row', function(e) {
        // Jangan toggle jika yang diklik adalah tombol atau aksi khusus
        if ($(e.target).closest('button, a, input, select, .btn-settle-panjar, .btn-toggle-row-detail').length) {
            return;
        }

        let id = $(this).data('id');
        let $detailRow = $('#kasbonDetailRow-' + id);

        if ($detailRow.length) {
            $(this).toggleClass('is-expanded');
            $detailRow.toggle();
            let isExp = $(this).hasClass('is-expanded');
            let $icon = $(this).find('.row-toggle-icon');
            if (isExp) {
                $icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
            } else {
                $icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
            }
        }
    });

    // Tombol Detail SPJ / Toggle Baris
    $(document).on('click', '.btn-toggle-row-detail', function(e) {
        e.preventDefault();
        let id = $(this).data('id');
        let $row = $(`#tableKasbonDetail tbody tr.kasbon-row[data-id="${id}"]`);
        let $detailRow = $('#kasbonDetailRow-' + id);
        if ($detailRow.length) {
            $row.toggleClass('is-expanded');
            $detailRow.toggle();
            let isExp = $row.hasClass('is-expanded');
            let $icon = $row.find('.row-toggle-icon');
            if (isExp) {
                $icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
            } else {
                $icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
            }
        }
    });

    // Tombol tutup riwayat dari dalam detail box
    $(document).on('click', '.btn-close-kasbon-detail', function(e) {
        e.preventDefault();
        let id = $(this).data('id');
        let $row = $(`#tableKasbonDetail tbody tr.kasbon-row[data-id="${id}"]`);
        $row.removeClass('is-expanded');
        $row.find('.row-toggle-icon').removeClass('fa-chevron-down').addClass('fa-chevron-right');
        $('#kasbonDetailRow-' + id).hide();
    });

    // Tombol Buka / Tutup Semua Riwayat
    $('#btnToggleAllDetails').on('click', function(e) {
        e.preventDefault();
        let $visibleRows = $('#tableKasbonDetail tbody tr.kasbon-row:visible');
        let anyClosed = false;

        $visibleRows.each(function() {
            let id = $(this).data('id');
            if (!$('#kasbonDetailRow-' + id).is(':visible')) {
                anyClosed = true;
                return false;
            }
        });

        if (anyClosed) {
            $visibleRows.each(function() {
                let id = $(this).data('id');
                $(this).addClass('is-expanded');
                $(this).find('.row-toggle-icon').removeClass('fa-chevron-right').addClass('fa-chevron-down');
                $('#kasbonDetailRow-' + id).show();
            });
            $('#textToggleAll').text('Tutup Semua');
            $(this).find('i').removeClass('fa-layer-group').addClass('fa-compress-alt');
        } else {
            $visibleRows.each(function() {
                let id = $(this).data('id');
                $(this).removeClass('is-expanded');
                $(this).find('.row-toggle-icon').removeClass('fa-chevron-down').addClass('fa-chevron-right');
                $('#kasbonDetailRow-' + id).hide();
            });
            $('#textToggleAll').text('Buka Semua');
            $(this).find('i').removeClass('fa-compress-alt').addClass('fa-layer-group');
        }
    });

    // Receipt Modal & Preview Handlers
    let receiptRotation = 0;
    let isReceiptZoomed = false;

    window.openReceiptPreview = function(opts) {
        let url = opts.url;
        let isImage = opts.isImage;
        let title = opts.title || 'Bukti Foto / Nota';
        let desc = opts.desc || '';
        let person = opts.person || '';
        let evidence = opts.evidence || '';
        let date = opts.date || '';

        $('#previewBuktiTitle').text(title);
        let subtitle = [];
        if (date) subtitle.push(date);
        if (person) subtitle.push(person);
        if (evidence) subtitle.push('No: ' + evidence);
        $('#previewBuktiSubtitle').text(subtitle.join(' • '));

        let meta = desc ? 'Keterangan: ' + desc : '';
        $('#previewBuktiMeta').text(meta);

        $('#btnPreviewOpenTab').attr('href', url);
        $('#btnPreviewDownload').attr('href', url);

        receiptRotation = 0;
        isReceiptZoomed = false;
        $('#previewReceiptImage').css({
            'transform': 'rotate(0deg)',
            'max-height': '70vh',
            'cursor': 'zoom-in'
        });

        $('#previewReceiptLoading').hide();
        $('#previewReceiptImageWrapper').hide();
        $('#previewReceiptPdfWrapper').hide();
        $('#previewReceiptFallback').hide();

        if (isImage || (url && url.match(/\.(jpg|jpeg|png|webp|gif)$/i))) {
            $('#btnRotateReceipt').show();
            $('#previewReceiptLoading').show();
            let img = new Image();
            img.onload = function() {
                $('#previewReceiptLoading').hide();
                $('#previewReceiptImage').attr('src', url);
                $('#previewReceiptImageWrapper').show();
            };
            img.onerror = function() {
                $('#previewReceiptLoading').hide();
                $('#previewReceiptFallbackText').text('Gagal memuat berkas gambar.');
                $('#previewReceiptFallbackLink').attr('href', url);
                $('#previewReceiptFallback').show();
            };
            img.src = url;
        } else if (url && url.toLowerCase().indexOf('.pdf') !== -1) {
            $('#btnRotateReceipt').hide();
            $('#previewReceiptPdfFrame').attr('src', url);
            $('#previewReceiptPdfWrapper').show();
        } else {
            $('#btnRotateReceipt').hide();
            let ext = url ? url.split('.').pop().toUpperCase() : 'FILE';
            $('#previewReceiptFallbackText').text('Dokumen format .' + ext + ' tidak dapat dipratinjau langsung.');
            $('#previewReceiptFallbackLink').attr('href', url);
            $('#previewReceiptFallback').show();
        }

        showKasModal('#modalPreviewBuktiFoto');
    };

    $(document).on('click', '#btnRotateReceipt', function(e) {
        e.preventDefault();
        receiptRotation = (receiptRotation + 90) % 360;
        $('#previewReceiptImage').css('transform', 'rotate(' + receiptRotation + 'deg)');
    });

    $(document).on('click', '#previewReceiptImage', function() {
        isReceiptZoomed = !isReceiptZoomed;
        if (isReceiptZoomed) {
            $(this).css('max-height', 'none').css('cursor', 'zoom-out');
        } else {
            $(this).css('max-height', '70vh').css('cursor', 'zoom-in');
        }
    });

    $(document).on('click', '.btn-preview-receipt', function(e) {
        e.preventDefault();
        let btn = $(this);
        window.openReceiptPreview({
            url: btn.data('url'),
            isImage: btn.data('is-image') == 1,
            title: btn.data('title'),
            desc: btn.data('desc'),
            person: btn.data('person'),
            evidence: btn.data('evidence'),
            date: btn.data('date')
        });
    });

    $('#modalPreviewBuktiFoto').on('hidden.bs.modal', function() {
        if ($('#modalViewReceiptItems').hasClass('show')) {
            $('body').addClass('modal-open');
        }
        $('#previewReceiptImage').attr('src', '');
        $('#previewReceiptPdfFrame').attr('src', '');
    });

    // Fungsi Tampilkan Detail Lengkap Transaksi (Modal View Detail Terpadu)
    window.openTransactionDetail = function(id) {
        if (!id) return;
        let url = "{{ url($routePrefix . '/kas-tunai') }}/" + id + "/detail";

        $('#viewChildBanner').hide();
        $('#sectionPanjarSummary').hide();
        $('#sectionPanjarChildReceipts').hide();
        $('#sectionPanjarChildExpenses').hide();
        $('#sectionDirectItems').hide();
        $('#containerReceiptAttachment').hide();
        $('#viewItemEvidenceRow').hide();
        $('#tbodyReceiptDetails').empty();
        $('#containerPanjarChildReceiptsList').empty();
        $('#tbodyChildExpenses').empty();

        $('#modalViewReceiptTitle').html('<i class="fas fa-spinner fa-spin mr-1 text-warning"></i> Memuat Rincian Transaksi #' + id + '...');
        showKasModal('#modalViewReceiptItems');

        $.ajax({
            url: url,
            type: 'GET',
            success: function(res) {
                let kas = res.kas_tunai;
                $('#modalViewReceiptTitle').html('<i class="fas fa-file-invoice mr-1 text-warning"></i> Rincian Lengkap Transaksi ' + kas.code);

                $('#viewItemCode').text(kas.code);
                $('#viewItemDate').text(kas.transaction_date);
                $('#viewItemDescription').text(kas.description);
                $('#viewItemPerson').text(kas.person_name);

                if (kas.evidence_number && kas.evidence_number !== '-') {
                    $('#viewItemEvidence').text(kas.evidence_number);
                    $('#viewItemEvidenceRow').show();
                }

                let catBadgeClass = kas.transaction_type === 'masuk' ? 'badge-success' : 'badge-primary';
                if (kas.category === 'panjar_kerja') catBadgeClass = 'badge-warning text-dark';
                else if (kas.category === 'operasional_laporan') catBadgeClass = 'badge-info text-white';
                $('#viewItemCategoryBadge').attr('class', 'badge ' + catBadgeClass).text(kas.category_label);

                // Status Panjar Badge
                if (kas.is_panjar) {
                    let panjarBadge = kas.panjar_status === 'settled' ? 'badge-success' : (kas.panjar_status === 'partial' ? 'badge-warning text-dark' : 'badge-danger');
                    let panjarLabel = kas.panjar_status === 'settled' ? 'Lunas SPJ' : (kas.panjar_status === 'partial' ? 'Dicicil Sebagian' : 'Belum Selesai SPJ');
                    $('#viewItemPanjarStatusBadge').attr('class', 'badge ' + panjarBadge).text(panjarLabel).show();
                } else {
                    $('#viewItemPanjarStatusBadge').hide();
                }

                if (kas.transaction_type === 'masuk') {
                    $('#viewItemAmountLabel').text('Kas Masuk (Penerimaan):');
                    $('#viewItemTotalAmount').attr('class', 'font-weight-bold text-success').text('+ ' + kas.formatted_amount);
                } else {
                    $('#viewItemAmountLabel').text('Kas Keluar (Pengeluaran):');
                    $('#viewItemTotalAmount').attr('class', 'font-weight-bold text-danger').text('- ' + kas.formatted_amount);
                }

                if (kas.parent_kas) {
                    $('#viewParentKasDesc').text(kas.parent_kas.code + ' - ' + kas.parent_kas.person_name + ' (' + kas.parent_kas.formatted_amount + ') - ' + kas.parent_kas.description);
                    $('#viewChildBanner').show();
                }

                if (kas.is_panjar && res.panjar_summary) {
                    let sum = res.panjar_summary;
                    $('#sectionPanjarSummary').show();
                    $('#viewSummaryPanjarAmount').text(sum.formatted_panjar_amount);
                    $('#viewSummaryTotalBelanja').text(sum.formatted_total_belanja);
                    $('#viewSummaryTotalExpenses').text(sum.formatted_total_expenses);
                    $('#viewSummaryTotalReturns').text(sum.formatted_total_returns);

                    if (sum.total_reimburses > 0) {
                        $('#viewSummaryReimburseAmount').text(sum.formatted_total_reimburses);
                        $('#viewSummaryReimburseRow').show();
                    } else {
                        $('#viewSummaryReimburseRow').hide();
                    }

                    if (sum.remaining > 0) {
                        $('#viewSummaryRemainingAmount').text(sum.formatted_remaining);
                        $('#viewSummaryRemainingRow').show();
                        $('#viewPanjarSummaryStatus').attr('class', 'badge badge-warning text-dark').text('Belum Selesai (Sisa Rp ' + Number(sum.remaining).toLocaleString('id-ID') + ')');
                    } else {
                        $('#viewSummaryRemainingRow').hide();
                        $('#viewPanjarSummaryStatus').attr('class', 'badge badge-success').text('Lunas Tuntas');
                    }
                }

                if (res.child_receipts && res.child_receipts.length > 0) {
                    $('#sectionPanjarChildReceipts').show();
                    let receiptsHtml = '';
                    res.child_receipts.forEach(function(rc, rIdx) {
                        let itemsRows = '';
                        if (rc.items && rc.items.length > 0) {
                            rc.items.forEach(function(it) {
                                itemsRows += `
                                    <tr>
                                        <td class="text-center">${it.index}</td>
                                        <td><strong>${it.item_name}</strong>${it.notes ? '<br><small class="text-muted">' + it.notes + '</small>' : ''}</td>
                                        <td class="text-center">${it.quantity} ${it.unit}</td>
                                        <td class="text-right">${it.formatted_price}</td>
                                        <td class="text-right font-weight-bold text-dark">${it.formatted_total_price}</td>
                                    </tr>
                                `;
                            });
                        } else {
                            itemsRows = `<tr><td colspan="5" class="text-center text-muted py-2">Belum ada rincian barang per item.</td></tr>`;
                        }

                        receiptsHtml += `
                            <div class="card border mb-3 shadow-xs">
                                <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="font-weight-bold text-dark small"><i class="fas fa-receipt text-primary mr-1"></i> Nota #${rIdx + 1}: ${rc.description}</span>
                                        <span class="badge badge-light border text-muted ml-2"><i class="fas fa-calendar-alt mr-1"></i> ${rc.transaction_date}</span>
                                        ${rc.evidence_number && rc.evidence_number !== '-' ? '<span class="badge badge-light border ml-1">No: ' + rc.evidence_number + '</span>' : ''}
                                    </div>
                                    <div class="font-weight-bold text-primary" style="font-size: 1rem;">${rc.formatted_amount}</div>
                                </div>
                                <div class="card-body p-2">
                                    <div class="table-responsive mb-2">
                                        <table class="table table-sm table-bordered mb-0" style="font-size: 0.78rem;">
                                            <thead class="bg-light text-center">
                                                <tr>
                                                    <th style="width: 5%;">No</th>
                                                    <th style="width: 45%;">Nama Komoditas / Barang</th>
                                                    <th style="width: 15%;">Qty</th>
                                                    <th style="width: 15%;">Harga Satuan</th>
                                                    <th style="width: 20%;">Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody>${itemsRows}</tbody>
                                        </table>
                                    </div>
                                    ${rc.receipt_url ? `
                                        <div class="mt-2 text-right">
                                            <button type="button" class="btn btn-xs btn-outline-info btn-preview-receipt-inline" data-url="${rc.receipt_url}" data-is-image="${rc.receipt_is_image ? 1 : 0}" data-title="Bukti Nota #${rIdx + 1}" data-desc="${(rc.description || '').replace(/"/g, '&quot;')}" data-date="${rc.transaction_date}">
                                                <i class="fas fa-image mr-1"></i> Lihat Foto Nota Toko
                                            </button>
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        `;
                    });
                    $('#containerPanjarChildReceiptsList').html(receiptsHtml);
                }

                if (res.child_expenses && res.child_expenses.length > 0) {
                    $('#sectionPanjarChildExpenses').show();
                    let expHtml = '';
                    res.child_expenses.forEach(function(ce, cIdx) {
                        expHtml += `
                            <tr>
                                <td class="text-center">${cIdx + 1}</td>
                                <td>
                                    <strong>${ce.description}</strong>
                                    <small class="text-muted d-block"><i class="fas fa-calendar-alt mr-1"></i> ${ce.transaction_date}</small>
                                </td>
                                <td>${ce.evidence_number || '-'}</td>
                                <td class="text-right font-weight-bold text-info">${ce.formatted_amount}</td>
                            </tr>
                        `;
                    });
                    $('#tbodyChildExpenses').html(expHtml);
                }

                if (res.direct_items && res.direct_items.length > 0) {
                    $('#sectionDirectItems').show();
                    let dRows = '';
                    let directSum = 0;
                    res.direct_items.forEach(function(it) {
                        directSum += it.total_price;
                        dRows += `
                            <tr>
                                <td class="text-center">${it.index}</td>
                                <td><strong>${it.item_name}</strong>${it.notes ? '<br><small class="text-muted">' + it.notes + '</small>' : ''}</td>
                                <td class="text-center">${it.quantity}</td>
                                <td class="text-center">${it.unit}</td>
                                <td class="text-right">${it.formatted_price}</td>
                                <td class="text-right font-weight-bold text-dark">${it.formatted_total_price}</td>
                            </tr>
                        `;
                    });
                    $('#tbodyReceiptDetails').html(dRows);
                    $('#viewItemSumTotal').text('Rp ' + directSum.toLocaleString('id-ID'));
                }

                if (kas.receipt_url) {
                    $('#containerReceiptAttachment').show();
                    if (kas.receipt_is_image) {
                        $('#receiptAttachmentContent').html(`
                            <a href="javascript:void(0)" class="btn-preview-receipt-inline d-inline-block" data-url="${kas.receipt_url}" data-is-image="1" data-title="Bukti ${kas.code}" data-desc="${(kas.description || '').replace(/"/g, '&quot;')}" data-person="${(kas.person_name || '').replace(/"/g, '&quot;')}" data-evidence="${(kas.evidence_number || '').replace(/"/g, '&quot;')}" data-date="${kas.transaction_date || ''}" title="Klik untuk pratinjau modal popup">
                                <img src="${kas.receipt_url}" class="img-fluid rounded border shadow-sm" style="max-height: 380px; object-fit: contain; cursor: pointer;">
                            </a>
                            <div class="mt-2">
                                <button type="button" class="btn btn-xs btn-outline-info btn-preview-receipt-inline" data-url="${kas.receipt_url}" data-is-image="1" data-title="Bukti ${kas.code}" data-desc="${(kas.description || '').replace(/"/g, '&quot;')}" data-person="${(kas.person_name || '').replace(/"/g, '&quot;')}" data-evidence="${(kas.evidence_number || '').replace(/"/g, '&quot;')}" data-date="${kas.transaction_date || ''}">
                                    <i class="fas fa-search-plus mr-1"></i> Perbesar Bukti Foto (Modal Popup)
                                </button>
                            </div>
                        `);
                    } else {
                        $('#receiptAttachmentContent').html(`
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-preview-receipt-inline" data-url="${kas.receipt_url}" data-is-image="0" data-title="Dokumen ${kas.code}" data-desc="${(kas.description || '').replace(/"/g, '&quot;')}" data-person="${(kas.person_name || '').replace(/"/g, '&quot;')}" data-evidence="${(kas.evidence_number || '').replace(/"/g, '&quot;')}" data-date="${kas.transaction_date || ''}">
                                <i class="fas fa-file-pdf text-danger mr-1"></i> Buka Dokumen Nota (${kas.receipt_url.split('.').pop().toUpperCase()})
                            </button>
                        `);
                    }
                }
            },
            error: function(err) {
                $('#modalViewReceiptTitle').html('<i class="fas fa-exclamation-triangle mr-1 text-danger"></i> Gagal Memuat Detail');
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Memuat Detail',
                    text: err.responseJSON?.message || 'Tidak dapat mengambil detail transaksi.'
                });
            }
        });
    };

    $(document).on('click', '.btn-view-receipt-items', function(e) {
        e.preventDefault();
        let id = $(this).data('id');
        openTransactionDetail(id);
    });

    // Settle panjar modal trigger
    let currentPanjarAmount = 0;
    let currentRemainingAmount = 0;
    $(document).on('click', '.btn-settle-panjar', function(e) {
        e.preventDefault();
        let id = $(this).data('id');
        let amount = parseFloat($(this).data('amount')) || 0;
        let remaining = parseFloat($(this).data('remaining'));
        if (isNaN(remaining)) remaining = amount;
        let settled = parseFloat($(this).data('settled')) || 0;
        let desc = $(this).data('desc');
        let person = $(this).data('person');
        let date = $(this).data('date') || '-';
        let rbId = $(this).data('rb-id') || '';
        let piId = $(this).data('pi-id') || '';

        currentPanjarAmount = amount;
        currentRemainingAmount = remaining;
        window.currentRemainingAmount = remaining;

        $('#settlePanjarId').val(id);
        $('#settlePanjarDateDisplay').text(date);
        $('#settlePersonName').text(person || 'Staf / Guru');
        $('#settleDesc').text(desc || '-');
        $('#settlePanjarAmountDisplay').text('Rp ' + amount.toLocaleString('id-ID'));
        $('#settleRemainingDisplay').text('Rp ' + remaining.toLocaleString('id-ID'));

        if (settled > 0) {
            $('#settleSettledAmountDisplay').text('Rp ' + settled.toLocaleString('id-ID'));
            $('#settleAlreadySettledSection').show();
        } else {
            $('#settleAlreadySettledSection').hide();
        }

        $('#settleNotesInput').val('');

        $('#settleRencanaBelanjaId').val(rbId);
        $('#settlePerjadinItemId').val(piId);

        if (typeof window.initSettlePanjarModal === 'function') {
            window.initSettlePanjarModal();
        }

        showKasModal('#modalSettlePanjar');
    });

    // Submit form Settle Panjar
    $('#formSettlePanjar').on('submit', function(e) {
        e.preventDefault();
        let id = $('#settlePanjarId').val();
        let formData = new FormData(this);

        let rawNota = ($('#inputNotaAmount').val() || '').toString().replace(/[^0-9]/g, '') || '0';
        let rawCashReturn = ($('#inputCashReturnAmount').val() || '').toString().replace(/[^0-9]/g, '') || '0';

        let isPartial = ($('#inputIsPartial').val() || '0');

        formData.set('nota_amount', rawNota);
        formData.set('real_amount', rawNota);
        formData.set('other_amount', '0');
        formData.set('cash_return_amount', rawCashReturn);
        formData.set('is_partial', isPartial);

        $('#btnSubmitSettle').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Memproses...');

        let url = "{{ url($routePrefix . '/kas-tunai') }}/" + id + "/settle-panjar";

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(res) {
                hideKasModal('#modalSettlePanjar');
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: res.message,
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => location.reload());
            },
            error: function(err) {
                let msg = err.responseJSON?.message || 'Terjadi kesalahan sistem.';
                Swal.fire({ icon: 'error', title: 'Gagal', text: msg });
            },
            complete: function() {
                $('#btnSubmitSettle').prop('disabled', false).html('<i class="fas fa-check-circle mr-1"></i> Simpan & Selesaikan SPJ');
            }
        });
    });

    $(document).on('click', '.btn-remove-item', function(e) {
        e.preventDefault();
        $(this).closest('tr').remove();
        recalculateItemsTotal('Settle');
    });

    $(document).on('input', '.item-qty-input', function() {
        recalculateItemsTotal('Settle');
    });

    $(document).on('input', '.item-price-input', function() {
        let val = $(this).val().replace(/[^0-9]/g, '');
        $(this).val(val ? Number(val).toLocaleString('id-ID') : '');
        recalculateItemsTotal('Settle');
    });

    // Format Rupiah Input
    $(document).on('input', '.rupiah-input', function() {
        let val = $(this).val().replace(/[^0-9]/g, '');
        $(this).val(val ? Number(val).toLocaleString('id-ID') : '');
    });
});
</script>
@stop
