@extends('adminlte::page')

@php
    $selectedYear = $selectedYear ?? $activeFiscalYear ?? session('fiscal_year', date('Y'));
@endphp

@section('title', 'Rencana Belanja BOSP')

@section('plugins.Datatables', true)
@section('plugins.Select2', true)
@section('plugins.Sweetalert2', true)

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap" style="padding: 2px 0;">
    <div>
        <h1 class="page-title"><i class="fas fa-basket-shopping text-info mr-1"></i> Rencana Belanja BOSP</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-house"></i> Dashboard</a></li>
                <li class="breadcrumb-item"><i class="fas fa-calculator"></i> Penganggaran / BOS</li>
                <li class="breadcrumb-item active">Rencana Belanja</li>
            </ol>
        </nav>
    </div>
    <div class="header-actions d-flex align-items-center">
        <button id="btn-bayar-utilitas" class="btn btn-sm btn-warning text-dark font-weight-bold shadow-sm mr-2 me-2" type="button">
            <i class="fas fa-bolt mr-1"></i> Bayar Utilitas
        </button>
        <button id="btn-create-group" class="btn btn-sm btn-info shadow-sm" type="button">
            <i class="fas fa-cart-plus mr-1"></i> Buat Rencana Belanja (<span id="selected-count">0</span> Item Dipilih)
        </button>
    </div>
</div>
@stop

@section('css')
<style>
    /* ── Typography & Font Scaling (Inter + Tabular Numbers) ── */
    body, .content-wrapper, .card, table, .btn, .form-control {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
    }
    
    /* Judul Halaman Ramping */
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

    /* Tabular numbers for all financial figures */
    .font-tabular, td.text-right, td.font-weight-bold {
        font-variant-numeric: tabular-nums;
        font-feature-settings: "tnum";
    }

    /* Force Table Text Alignment */
    .text-right, .text-end, th.text-right, td.text-right, th.text-end, td.text-end,
    #table-unrealized td:nth-child(5), #table-unrealized th:nth-child(5),
    #table-unrealized td:nth-child(6), #table-unrealized th:nth-child(6),
    #table-groups td:nth-child(8), #table-groups th:nth-child(8),
    #table-detail-items td:nth-child(6), #table-detail-items th:nth-child(6),
    #table-detail-items td:nth-child(7), #table-detail-items th:nth-child(7),
    #table-selected-preview td:nth-child(4), #table-selected-preview th:nth-child(4),
    #table-selected-preview td:nth-child(5), #table-selected-preview th:nth-child(5),
    #table-edit-preview td:nth-child(4), #table-edit-preview th:nth-child(4),
    #table-edit-preview td:nth-child(5), #table-edit-preview th:nth-child(5) {
        text-align: right !important;
    }
    .text-center, th.text-center, td.text-center {
        text-align: center !important;
    }
    .text-left, .text-start, th.text-left, td.text-left, th.text-start, td.text-start {
        text-align: left !important;
    }

    /* Badge Visibility & Contrast */
    .badge.badge-success, .badge.bg-success, span.badge-success {
        background-color: #dcfce7 !important;
        color: #166534 !important;
        border: 1px solid #bbf7d0 !important;
    }
    .badge.badge-warning, .badge.bg-warning, span.badge-warning {
        background-color: #fef3c7 !important;
        color: #92400e !important;
        border: 1px solid #fde68a !important;
    }
    .badge.badge-secondary, .badge.bg-secondary, span.badge-secondary {
        background-color: #f1f5f9 !important;
        color: #334155 !important;
        border: 1px solid #cbd5e1 !important;
    }
    .badge.badge-info, .badge.bg-info, span.badge-info {
        background-color: #e0f2fe !important;
        color: #0369a1 !important;
        border: 1px solid #bae6fd !important;
    }

    /* ── High-Density Data Table (13px Font, Compact Padding) ── */
    #table-unrealized, #table-groups, #table-selected-preview, #table-edit-preview, #table-detail-items {
        font-size: 0.8125rem !important; /* 13px */
        letter-spacing: -0.008em;
    }
    #table-unrealized th, #table-groups th {
        font-size: 0.74rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 0.4rem 0.5rem !important;
        vertical-align: middle;
        background-color: #f8fafc;
        color: #475569;
    }
    #table-unrealized td, #table-groups td {
        padding: 0.35rem 0.5rem !important;
        vertical-align: middle;
        line-height: 1.35;
    }
    
    /* Nested Child Row Accordion Styling */
    .table-nested-items th {
        font-size: 0.72rem !important;
        background-color: #f1f5f9 !important;
        color: #334155 !important;
        font-weight: 700 !important;
        padding: 0.35rem 0.45rem !important;
    }
    .table-nested-items td {
        font-size: 0.78rem !important;
        padding: 0.3rem 0.45rem !important;
        vertical-align: middle;
    }
    .nested-group-detail {
        background-color: #ffffff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    tr.shown > td {
        background-color: #f0fdfa !important;
    }

    /* Compact DataTables Pagination & Search */
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        font-size: 0.78rem !important;
    }
    .dataTables_wrapper .dataTables_info {
        font-size: 0.72rem !important;
        color: #64748b !important;
        padding-top: 0.35rem !important;
    }

    .nav-tabs .nav-link {
        font-size: 0.84rem;
        font-weight: 600;
        padding: 0.5rem 1rem;
        color: #64748b;
        cursor: pointer;
    }
    .nav-tabs .nav-link.active {
        color: #0284c7 !important;
        font-weight: 700;
        border-bottom: 2px solid #0284c7;
    }
</style>
@stop

@section('content')
@php
    $indonesianMonths = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    $currentMonthName = $indonesianMonths[(int)date('n')];
@endphp

    {{-- Tabs View: Available items vs Saved Groups --}}
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header p-0 pt-2 bg-white border-bottom d-flex justify-content-between align-items-center flex-wrap px-3" style="gap: 10px;">
            <ul class="nav nav-tabs border-bottom-0 mb-0" id="panduan-tabs" role="tablist" style="margin-bottom: -1px;">
                <li class="nav-item" role="presentation">
                    <a class="nav-link active font-weight-bold" id="tab-unrealized-link" data-bs-toggle="tab" data-bs-target="#tab-unrealized" data-toggle="pill" href="#tab-unrealized" role="tab" aria-controls="tab-unrealized" aria-selected="true">
                        <i class="fas fa-boxes-packing mr-1 text-primary"></i> Item Belum Terealisasi (Bisa Dibelanjakan)
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link font-weight-bold" id="tab-groups-link" data-bs-toggle="tab" data-bs-target="#tab-groups" data-toggle="pill" href="#tab-groups" role="tab" aria-controls="tab-groups" aria-selected="false">
                        <i class="fas fa-receipt mr-1 text-success"></i> Daftar Rencana Belanja
                    </a>
                </li>
            </ul>
            <div class="d-flex align-items-center py-1 flex-wrap ms-auto ml-auto" style="gap: 8px;">
                <input type="hidden" id="filter-year" value="{{ $selectedYear }}">
                <span class="badge bg-primary text-white font-weight-normal d-inline-flex align-items-center shadow-xs" style="height: 31px; font-size: 0.8125rem; padding: 0 10px; border-radius: 4px;" title="Tahun Anggaran aktif yang dipilih pada Navbar">
                    <i class="fas fa-calendar-alt mr-1 me-1"></i> Tahun Anggaran {{ $selectedYear }}
                </span>
                <div class="input-group input-group-sm shadow-xs" style="width: auto;">
                    <span class="input-group-text bg-light text-muted font-weight-bold" style="height: 31px; font-size: 0.8125rem; border-radius: 4px 0 0 4px;">
                        <i class="fas fa-calendar-check mr-1 me-1 text-info"></i> Alokasi s/d Bulan:
                    </span>
                    <select id="filter-month" class="form-select form-select-sm font-weight-bold text-dark" style="height: 31px; font-size: 0.8125rem; border-radius: 0 4px 4px 0; cursor: pointer; width: auto; min-width: 130px;">
                        @foreach($indonesianMonths as $m)
                            <option value="{{ $m }}" {{ $m == $currentMonthName ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="card-body p-3">
            <div class="tab-content">
                {{-- TAB 1: Unrealized Items --}}
                <div class="tab-pane fade show active" id="tab-unrealized" role="tabpanel" aria-labelledby="tab-unrealized-link">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap" style="gap: 10px;">
                        <div class="alert alert-info py-2 mb-0 flex-grow-1" style="font-size: 0.8rem;">
                            <i class="fas fa-info-circle mr-1"></i> Centang satu atau beberapa barang di bawah ini, lalu klik tombol <strong>"Buat Rencana Belanja"</strong> untuk mengelompokkan barang ke rencana belanja/supplier tertentu.
                        </div>
                        <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                            <div class="d-inline-flex align-items-center bg-light border px-3 py-1 rounded shadow-sm" style="height: 31px;">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="filter-hide-planned" checked>
                                    <label class="custom-control-label small font-weight-bold text-dark cursor-pointer mb-0" for="filter-hide-planned">
                                        Sembunyikan barang yang sudah 100% masuk Rencana
                                    </label>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-success font-weight-bold d-inline-flex align-items-center shadow-xs" id="btn-export-unrealized" style="height: 31px; font-size: 0.8125rem; padding: 0.25rem 0.75rem; border-radius: 4px;" title="Export Excel Barang Belum Dibelanjakan (s/d Bulan ini, dikelompokkan per Program)">
                                <i class="fas fa-file-excel mr-1 me-1"></i> Export Excel
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-hover table-striped w-100" id="table-unrealized">
                            <thead>
                                <tr>
                                    <th width="30" class="text-center">
                                        <input type="checkbox" id="check-all">
                                    </th>
                                    <th width="75" class="text-center">PROGRAM</th>
                                    <th class="text-left">URAIAN / BARANG</th>
                                    <th width="85" class="text-center">SISA VOL</th>
                                    <th width="110" class="text-right text-end">HARGA (RP)</th>
                                    <th width="115" class="text-right text-end">TOTAL (Rp)</th>
                                    <th width="95" class="text-center">ALOKASI BULAN</th>
                                    <th width="260" class="text-left">KEGIATAN / KODE REK</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

                {{-- TAB 2: Saved Groups --}}
                <div class="tab-pane fade" id="tab-groups" role="tabpanel" aria-labelledby="tab-groups-link">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap" style="gap: 8px;">
                        <div class="alert alert-info py-1 px-2 mb-0 d-inline-block" style="font-size: 0.78rem;">
                            <i class="fas fa-info-circle mr-1"></i> Klik tombol <strong>[+]</strong> atau <strong>Judul Rencana</strong> untuk melihat rincian item belanja langsung di bawah baris.
                        </div>
                        <div class="d-flex align-items-center flex-wrap" style="gap: 6px;">
                            <div class="input-group input-group-sm shadow-xs" style="width: auto;">
                                <span class="input-group-text bg-light text-muted font-weight-bold d-inline-flex align-items-center" style="height: 31px; font-size: 0.8125rem; border-radius: 4px 0 0 4px; padding: 0 10px;">
                                    <i class="fas fa-filter mr-1 me-1 text-primary"></i> Status:
                                </span>
                                <select id="filter-group-status" class="form-select form-select-sm custom-select custom-select-sm font-weight-bold text-dark" style="height: 31px; font-size: 0.8125rem; border-radius: 0 4px 4px 0; cursor: pointer; width: auto; min-width: 140px;">
                                    <option value="">Semua Status</option>
                                    <option value="draft">Draft</option>
                                    <option value="ordered">Order</option>
                                    <option value="diajukan_ke_bank">TNT</option>
                                    <option value="realized">Realisasi</option>
                                </select>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-info font-weight-bold d-inline-flex align-items-center shadow-xs" id="btn-expand-all" style="height: 31px; font-size: 0.8125rem; padding: 0.25rem 0.65rem; border-radius: 4px;" title="Buka seluruh rincian rencana belanja">
                                <i class="fas fa-expand-alt mr-1"></i> Buka Semua
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary font-weight-bold d-inline-flex align-items-center shadow-xs" id="btn-collapse-all" style="height: 31px; font-size: 0.8125rem; padding: 0.25rem 0.65rem; border-radius: 4px;" title="Tutup seluruh rincian rencana belanja">
                                <i class="fas fa-compress-alt mr-1"></i> Tutup Semua
                            </button>
                            <button type="button" class="btn btn-sm btn-success font-weight-bold d-inline-flex align-items-center shadow-xs" id="btn-export-groups" style="height: 31px; font-size: 0.8125rem; padding: 0.25rem 0.75rem; border-radius: 4px;" title="Export Excel Rincian Daftar Rencana Belanja (Draft)">
                                <i class="fas fa-file-excel mr-1 me-1"></i> Export Excel
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-hover table-striped w-100" id="table-groups">
                            <thead>
                                <tr>
                                    <th width="32" class="text-center"></th>
                                    <th width="35" class="text-center">NO</th>
                                    <th width="105" class="text-center">NO RB</th>
                                    <th class="text-left">JUDUL RENCANA BELANJA</th>
                                    <th class="text-left">SUPPLIER / TOKO</th>
                                    <th width="95" class="text-center">TGL RENCANA</th>
                                    <th width="85" class="text-center">JUMLAH</th>
                                    <th width="125" class="text-right text-end">TOTAL (RP)</th>
                                    <th width="90" class="text-center">STATUS</th>
                                    <th width="140" class="text-center">AKSI</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Create Rencana Belanja Group -->
    <div class="modal fade" id="createGroupModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document" style="max-width: 1020px;">
            <form id="formCreateGroup">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-light py-2 px-3 border-bottom">
                        <h5 class="modal-title font-weight-bold text-dark" style="font-size: 1rem;"><i class="fas fa-cart-plus mr-2 text-info"></i>Buat Rencana Belanja Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body py-3">
                        <div class="row">
                            <div class="col-md-4 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">No. Rencana Belanja <small class="text-info font-weight-normal">(Otomatis/Bisa Diedit)</small> <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <input type="text" id="create-no-nota" name="no_nota" class="form-control form-control-sm font-tabular font-weight-bold text-info" placeholder="Contoh: RB26-001" required>
                                    <button class="btn btn-outline-secondary" type="button" id="btn-refresh-no-nota" title="Generate ulang nomor"><i class="fas fa-sync-alt"></i></button>
                                </div>
                            </div>
                            <div class="col-md-4 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">Judul Rencana Belanja <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="create-title" class="form-control form-control-sm" placeholder="Contoh: Belanja Alat Tulis Kantor Bulan Januari" required>
                            </div>
                            <div class="col-md-4 form-group mb-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="font-weight-bold mb-0" style="font-size: 0.8rem;">Supplier / Toko</label>
                                    <a href="{{ route('supplier.index') }}" target="_blank" class="small text-primary font-weight-bold" style="font-size: 0.72rem;" title="Buka Master Supplier"><i class="fas fa-external-link-alt mr-1"></i>Master Supplier</a>
                                </div>
                                <select name="supplier_id" id="create-supplier_id" class="form-control form-control-sm">
                                    <option value="">-- Pilih Supplier Terdaftar --</option>
                                    @if(isset($suppliers))
                                        @foreach($suppliers as $sup)
                                            <option value="{{ $sup->id }}" 
                                                data-name="{{ $sup->name }}" 
                                                data-type="{{ $sup->type }}"
                                                data-marketplace="{{ $sup->marketplace_name }}"
                                                data-bank="{{ $sup->bank_name }}" 
                                                data-account="{{ $sup->account_number }}" 
                                                data-holder="{{ $sup->account_holder }}"
                                                data-accounts='@json($sup->bankAccounts)'
                                                data-category="{{ $sup->category_label }}">
                                                {{ $sup->name }} [{{ $sup->type === 'online' ? ($sup->marketplace_name ?: 'Toko Online') : 'Toko Offline' }}] ({{ $sup->category_label }})
                                            </option>
                                        @endforeach
                                    @endif
                                    <option value="custom">-- Input Manual / Toko Lain --</option>
                                </select>
                                <input type="hidden" name="bank_name" id="create-bank_name">
                                <input type="hidden" name="account_number" id="create-account_number">
                                <input type="text" name="supplier_name" id="create-supplier_name" class="form-control form-control-sm mt-1 d-none" placeholder="Ketik nama supplier / toko...">
                                <div id="create-supplier-info" class="small text-muted mt-1 font-italic" style="font-size: 0.72rem;"></div>

                                {{-- Pilihan Rekening Toko Offline jika lebih dari 1 --}}
                                <div id="create-offline-accounts-box" class="mt-2 p-2 border rounded bg-light d-none">
                                    <label class="font-weight-bold text-primary mb-1" style="font-size: 0.76rem;">
                                        <i class="fas fa-university mr-1"></i>Pilih Rekening Bank Toko:
                                    </label>
                                    <select id="create-offline-account-select" class="form-control form-control-sm font-tabular font-weight-bold"></select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">Tgl Rencana Belanja</label>
                                <input type="date" name="planned_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-md-6 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">Catatan Tambahan</label>
                                <input type="text" name="notes" class="form-control form-control-sm" placeholder="Contoh: Diberi diskon 5%...">
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="font-weight-bold text-info mb-0" style="font-size: 0.82rem;"><i class="fas fa-list mr-1"></i>Rincian Barang yang Dipilih:</label>
                                <div class="input-group input-group-sm" style="max-width: 250px;">
                                    <span class="input-group-text bg-light text-muted py-0 px-2"><i class="fas fa-search" style="font-size: 0.75rem;"></i></span>
                                    <input type="text" id="search-selected-preview" class="form-control form-control-sm" placeholder="Cari di daftar barang...">
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm" id="table-selected-preview">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Uraian Barang</th>
                                            <th width="120" class="text-center">Vol Rencana</th>
                                            <th width="80" class="text-center">Satuan</th>
                                            <th width="150" class="text-right">Harga Satuan (Rp)</th>
                                            <th width="160" class="text-right">Total (Rp)</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="4" class="text-right font-weight-bold">Total Rencana Belanja:</th>
                                            <th id="modal-grand-total" class="font-weight-bold text-info font-tabular">Rp 0</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-info"><i class="fas fa-save mr-1"></i> Simpan Rencana Belanja</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Rencana Belanja Group -->
    <div class="modal fade" id="editGroupModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document" style="max-width: 1020px;">
            <form id="formEditGroup">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit-group-id">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title font-weight-bold" style="font-size: 1rem;"><i class="fas fa-edit mr-2"></i>Edit Rencana Belanja</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body py-3">
                        <div class="row">
                            <div class="col-md-4 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">No. Rencana Belanja <span class="text-danger">*</span></label>
                                <input type="text" id="edit-no-nota" name="no_nota" class="form-control form-control-sm font-tabular font-weight-bold text-info" required>
                            </div>
                            <div class="col-md-4 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">Judul Rencana Belanja <span class="text-danger">*</span></label>
                                <input type="text" id="edit-title" name="title" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-4 form-group mb-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="font-weight-bold mb-0" style="font-size: 0.8rem;">Supplier / Toko</label>
                                    <a href="{{ route('supplier.index') }}" target="_blank" class="small text-primary font-weight-bold" style="font-size: 0.72rem;" title="Buka Master Supplier"><i class="fas fa-external-link-alt mr-1"></i>Master Supplier</a>
                                </div>
                                <select name="supplier_id" id="edit-supplier_id" class="form-control form-control-sm">
                                    <option value="">-- Pilih Supplier Terdaftar --</option>
                                    @if(isset($suppliers))
                                        @foreach($suppliers as $sup)
                                            <option value="{{ $sup->id }}" 
                                                data-name="{{ $sup->name }}" 
                                                data-type="{{ $sup->type }}"
                                                data-marketplace="{{ $sup->marketplace_name }}"
                                                data-bank="{{ $sup->bank_name }}" 
                                                data-account="{{ $sup->account_number }}" 
                                                data-holder="{{ $sup->account_holder }}"
                                                data-accounts='@json($sup->bankAccounts)'
                                                data-category="{{ $sup->category_label }}">
                                                {{ $sup->name }} [{{ $sup->type === 'online' ? ($sup->marketplace_name ?: 'Toko Online') : 'Toko Offline' }}] ({{ $sup->category_label }})
                                            </option>
                                        @endforeach
                                    @endif
                                    <option value="custom">-- Input Manual / Toko Lain --</option>
                                </select>
                                <input type="hidden" name="bank_name" id="edit-bank_name">
                                <input type="hidden" name="account_number" id="edit-account_number">
                                <input type="text" id="edit-supplier_name" name="supplier_name" class="form-control form-control-sm mt-1 d-none" placeholder="Ketik nama supplier / toko...">
                                <div id="edit-supplier-info" class="small text-muted mt-1 font-italic" style="font-size: 0.72rem;"></div>

                                {{-- Pilihan Rekening Toko Offline jika lebih dari 1 --}}
                                <div id="edit-offline-accounts-box" class="mt-2 p-2 border rounded bg-light d-none">
                                    <label class="font-weight-bold text-primary mb-1" style="font-size: 0.76rem;">
                                        <i class="fas fa-university mr-1"></i>Pilih Rekening Bank Toko:
                                    </label>
                                    <select id="edit-offline-account-select" class="form-control form-control-sm font-tabular font-weight-bold"></select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">Tgl Rencana Belanja</label>
                                <input type="date" id="edit-planned_date" name="planned_date" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-6 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">Catatan Tambahan</label>
                                <input type="text" id="edit-notes" name="notes" class="form-control form-control-sm">
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="font-weight-bold text-dark mb-0" style="font-size: 0.82rem;"><i class="fas fa-list mr-1"></i>Rincian Barang Rencana:</label>
                                <div class="input-group input-group-sm" style="max-width: 250px;">
                                    <span class="input-group-text bg-light text-muted py-0 px-2"><i class="fas fa-search" style="font-size: 0.75rem;"></i></span>
                                    <input type="text" id="search-edit-preview" class="form-control form-control-sm" placeholder="Cari di daftar barang...">
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm" id="table-edit-preview">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Uraian Barang</th>
                                            <th width="110" class="text-center">Vol Rencana</th>
                                            <th width="75" class="text-center">Satuan</th>
                                            <th width="140" class="text-right">Harga Satuan (Rp)</th>
                                            <th width="150" class="text-right">Total (Rp)</th>
                                            <th width="45" class="text-center" title="Aksi"><i class="fas fa-trash-alt text-muted"></i></th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="4" class="text-right font-weight-bold">Total Rencana Belanja:</th>
                                            <th id="edit-modal-grand-total" class="font-weight-bold text-primary font-tabular">Rp 0</th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-warning"><i class="fas fa-save mr-1"></i> Simpan Perubahan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Detail Rencana Belanja (Preview Items) -->
    <div class="modal fade" id="detailGroupModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document" style="max-width: 1020px;">
            <div class="modal-content">
                <div class="modal-header bg-light py-2 px-3 border-bottom">
                    <h5 class="modal-title font-weight-bold text-dark" style="font-size: 1rem;"><i class="fas fa-list-check mr-2 text-primary"></i>Rincian Rencana Belanja - <span id="detail-group-title"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <div class="row mb-3 bg-light p-2 border rounded mx-0">
                        <div class="col-md-6">
                            <small>
                                <strong>No. Rencana Belanja:</strong> <span id="detail-group-no-nota" class="badge badge-info font-tabular font-weight-bold mr-2">-</span><br>
                                <strong>Supplier / Toko Target:</strong> <span id="detail-group-supplier">-</span><br>
                                <strong>Tgl Rencana Belanja:</strong> <span id="detail-group-date">-</span>
                            </small>
                        </div>
                        <div class="col-md-6 text-md-right">
                            <small>
                                <strong>Alokasi s/d Bulan:</strong> <span id="detail-group-month">-</span><br>
                                <strong>Status Rencana:</strong> <span id="detail-group-status">-</span><br>
                                <span id="detail-group-tnt-box" class="d-none"><strong>Surat TNT:</strong> <span id="detail-group-tnt" class="text-primary font-tabular font-weight-bold ml-1">-</span><br></span>
                            </small>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-primary active btn-view-mode-toggle" id="btn-mode-siplah"><i class="fas fa-layer-group mr-1"></i>Panduan Checkout SIPLah (Terkelompok)</button>
                            <button type="button" class="btn btn-outline-secondary btn-view-mode-toggle" id="btn-mode-flat"><i class="fas fa-list mr-1"></i>Daftar Belanja</button>
                        </div>
                        <span id="detail-mode-badge" class="badge badge-light border text-muted" style="font-size: 0.73rem;"><i class="fas fa-info-circle mr-1"></i>Dikelompokkan per Program/Kegiatan & Kode Rekening</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover table-sm" id="table-detail-items">
                            <thead class="bg-light text-dark font-weight-bold" style="border-bottom: 2px solid #dee2e6;">
                                <tr>
                                    <th width="35" class="text-center">NO</th>
                                    <th>URAIAN / NAMA BARANG</th>
                                    <th width="90" class="text-center">VOL PLAN</th>
                                    <th width="80" class="text-center">SATUAN</th>
                                    <th width="130" class="text-right">HARGA SATUAN</th>
                                    <th width="140" class="text-right">TOTAL</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="5" class="text-right font-weight-bold">TOTAL KESELURUHAN RENCANA BELANJA:</th>
                                    <th id="detail-modal-grand-total" class="text-right font-weight-bold text-primary font-tabular">Rp 0</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div id="detail-group-notes-box" class="alert alert-secondary py-2 mt-2 d-none">
                        <small><strong>Catatan:</strong> <span id="detail-group-notes"></span></small>
                    </div>
                </div>
                <div class="modal-footer py-2 d-flex justify-content-between flex-wrap" style="gap: 8px;">
                    <div>
                        <button type="button" class="btn btn-sm btn-success mr-1" id="detail-btn-wa" title="Buka WhatsApp untuk membagikan daftar belanja ini"><i class="fab fa-whatsapp mr-1"></i> Bagikan ke WhatsApp</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary mr-1" id="detail-btn-copy-wa" title="Salin format teks WhatsApp ke clipboard"><i class="fas fa-copy mr-1"></i> Salin Teks WA</button>
                        <button type="button" class="btn btn-sm btn-outline-primary mr-1" id="detail-btn-toggle-order" title="Tandai pesanan ini sudah diorder"><i class="fas fa-cart-arrow-down mr-1"></i> <span id="detail-btn-order-label">Tandai Sudah Diorder</span></button>
                    </div>
                    <div>
                        <a id="detail-btn-print" href="#" target="_blank" class="btn btn-sm btn-info mr-1"><i class="fas fa-print mr-1"></i> Cetak Rencana Belanja</a>
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Eksekusi Direct Realisasi -->
    <div class="modal fade" id="convertModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form id="formConvert">
                @csrf
                <input type="hidden" id="convert-group-id">
                <div class="modal-content">
                    <div class="modal-header bg-light py-2 px-3 border-bottom">
                        <h5 class="modal-title font-weight-bold text-dark" style="font-size: 1rem;"><i class="fas fa-receipt mr-2 text-success"></i>Eksekusi Jadi Realisasi Belanja (BKU)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body py-3">
                        <div class="alert alert-success py-2 mb-3" style="font-size: 0.8rem;">
                            <i class="fas fa-check-circle mr-1"></i> Transaksi dalam rencana belanja ini akan langsung dimasukkan ke data <strong>Realisasi Belanja BOS</strong>.
                        </div>

                        <div class="form-group mb-2">
                            <label class="font-weight-bold" style="font-size: 0.8rem;">No Transaksi / BPU <small class="text-muted">(Opsional)</small></label>
                            <input type="text" name="no_transaksi" class="form-control form-control-sm" placeholder="Contoh: BPU001">
                        </div>

                        <div class="form-group mb-2">
                            <label class="font-weight-bold" style="font-size: 0.8rem;">Tgl Transaksi BKU <span class="text-danger">*</span></label>
                            <input type="date" name="transaction_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                        </div>

                        <div class="form-group mb-2">
                            <label class="font-weight-bold" style="font-size: 0.8rem;">Tgl Nota / Bukti</label>
                            <input type="date" name="receipt_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-check-double mr-1"></i> Proses Realisasi Sekarang</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Bayar Utilitas (Bundling Tagihan Utilitas ke Rencana Belanja) -->
    <div class="modal fade" id="modalBayarUtilitas" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document" style="max-width: 1120px;">
            <form id="formBayarUtilitas">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="modal-title font-weight-bold" style="font-size: 1.05rem;">
                            <i class="fas fa-bolt mr-2 text-dark"></i>Bayar Tagihan Utilitas (Bundling ke Rencana Belanja & TNT)
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body py-3">
                        <div class="alert alert-info py-2 mb-3 shadow-xs" style="font-size: 0.81rem; line-height: 1.45;">
                            <i class="fas fa-info-circle mr-1"></i>
                            Pilih tagihan utilitas bulan ini (listrik, internet, telepon) yang telah ditalangi untuk dibundel menjadi <strong>1 Paket Rencana Belanja Utilitas</strong>. Paket ini otomatis menarik rekening petugas talangan untuk penarikan Surat TNT Bank Jatim.
                        </div>

                        <div class="row">
                            <div class="col-md-3 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.78rem;">Alokasi Tagihan Bulan <span class="text-danger">*</span></label>
                                <select id="util-filter-month" class="form-select form-select-sm custom-select custom-select-sm font-weight-bold text-dark" style="height: 31px; font-size: 0.8125rem;">
                                    @foreach($indonesianMonths as $m)
                                        <option value="{{ $m }}" {{ $m == $currentMonthName ? 'selected' : '' }}>{{ $m }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.78rem;">No. Rencana Belanja <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <input type="text" id="util-no-nota" name="no_nota" class="form-control form-control-sm font-tabular font-weight-bold text-info" placeholder="Otomatis..." required>
                                    <button class="btn btn-outline-secondary" type="button" id="btn-refresh-util-no-nota" title="Generate ulang nomor"><i class="fas fa-sync-alt"></i></button>
                                </div>
                            </div>
                            <div class="col-md-3 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.78rem;">Tgl Rencana Belanja</label>
                                <input type="date" name="planned_date" id="util-planned-date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-md-3 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.78rem;">Petugas Talangan (Reimburse) <span class="text-danger">*</span></label>
                                <select name="guru_id" id="util-guru-id" class="form-select form-select-sm custom-select custom-select-sm font-weight-bold" required></select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.78rem;">Judul Rencana Belanja</label>
                                <input type="text" name="title" id="util-title" class="form-control form-control-sm" placeholder="Contoh: Bayar Utilitas dan BBM September">
                            </div>
                            <div class="col-md-3 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.78rem;">Bank Tujuan Rekening</label>
                                <input type="text" name="bank_name" id="util-bank-name" class="form-control form-control-sm" readonly>
                            </div>
                            <div class="col-md-3 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.78rem;">Nomor Rekening</label>
                                <input type="text" name="account_number" id="util-account-number" class="form-control form-control-sm font-tabular font-weight-bold text-dark" readonly>
                            </div>
                        </div>

                        {{-- Tabel Struk Utilitas Siap Bayar --}}
                        <div class="form-group mb-2 mt-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="font-weight-bold text-dark mb-0" style="font-size: 0.82rem;">
                                    <i class="fas fa-list-check mr-1 text-warning"></i>Daftar Struk Tagihan Utilitas Belum Masuk Rencana:
                                </label>
                                <a href="{{ route('bos.utilitas.index') }}" target="_blank" class="small text-primary font-weight-bold" style="font-size: 0.74rem;">
                                    <i class="fas fa-external-link-alt mr-1"></i>Buka Modul Struk Utilitas (Upload/Scan)
                                </a>
                            </div>

                            <div class="table-responsive border rounded" style="max-height: 330px; overflow-y: auto;">
                                <table class="table table-bordered table-sm table-hover mb-0" id="table-utilitas-bundling">
                                    <thead class="bg-light sticky-top" style="z-index: 2;">
                                        <tr>
                                            <th width="35" class="text-center">
                                                <input type="checkbox" id="check-all-util">
                                            </th>
                                            <th width="210">Objek Tagihan & Nama Pelanggan</th>
                                            <th width="300">Komponen Kertas Kerja ARKAS Terkait</th>
                                            <th width="125" class="text-right">Nominal Struk</th>
                                            <th width="140" class="text-right">Nominal di-TNT-kan (Rp)</th>
                                            <th width="140" class="text-center">Info Token/Resi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbody-utilitas-bundling">
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <i class="fas fa-spinner fa-spin mr-1"></i> Memuat data struk utilitas...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Total Summary Box --}}
                        <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded border mt-2">
                            <div>
                                <span class="font-weight-bold text-muted" style="font-size: 0.8rem;">
                                    <span id="util-selected-count" class="text-primary font-weight-bold">0</span> Tagihan Dipilih
                                </span>
                                <div id="util-budget-warning" class="text-danger small font-weight-bold mt-0.5 d-none">
                                    <i class="fas fa-exclamation-triangle mr-1"></i> Perhatian: Nominal melebihi sisa anggaran Kertas Kerja pada beberapa item!
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-muted mr-2" style="font-size: 0.82rem;">Total Reimburse Utilitas (TNT):</span>
                                <span id="util-grand-total" class="font-weight-bold text-success font-tabular" style="font-size: 1.15rem;">Rp 0</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer py-2 d-flex justify-content-between">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-warning text-dark font-weight-bold" id="btn-save-util-group">
                            <i class="fas fa-check-circle mr-1"></i> Simpan Paket Rencana Belanja Utilitas
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop

@section('js')
<script>
$(document).ready(function() {
    $.fn.dataTable.ext.errMode = 'none';

    let selectedItems = {};

    function formatThousand(val) {
        if (val === null || val === undefined || val === '') return '';
        let clean = String(val).replace(/\D/g, '');
        if (!clean) return '';
        return new Intl.NumberFormat('id-ID').format(clean);
    }

    function parseThousand(val) {
        if (val === null || val === undefined || val === '') return 0;
        return parseFloat(String(val).replace(/\./g, '').replace(/,/g, '.')) || 0;
    }

    function e(str) {
        return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function formatRupiah(num) {
        if (num === null || num === undefined || num === '') return '0';
        let n = parseFloat(num);
        if (isNaN(n)) return '0';
        return new Intl.NumberFormat('id-ID').format(n);
    }

    $(document).on('input', '.input-thousand', function() {
        let val = $(this).val();
        let num = val.replace(/\D/g, '');
        $(this).val(num ? new Intl.NumberFormat('id-ID').format(num) : '');
    });

    // 1. DataTable Unrealized Items
    let tableUnrealized = $('#table-unrealized').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 50,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
        order: [[2, 'asc']],
        ajax: {
            url: "{{ route('bos.panduan.unrealized-data', [], false) }}",
            data: function(d) {
                d.year = $('#filter-year').val();
                d.month = $('#filter-month').val();
                d.hide_planned = $('#filter-hide-planned').is(':checked') ? 1 : 0;
            }
        },
        columns: [
            {data: 'checkbox', orderable: false, searchable: false, className: 'text-center'},
            {data: 'program_code', defaultContent: '-', className: 'font-tabular text-center'},
            {
                data: 'description',
                className: 'font-weight-500 text-dark',
                render: function(data, type, row) {
                    let text = '<div class="font-weight-bold text-dark">' + e(data) + '</div>';
                    if (row.total_planned_vol > 0) {
                        let plansText = (row.active_plans && row.active_plans.length > 0) ? row.active_plans.join(', ') : 'Rencana Belanja';
                        if (!row.is_allocatable) {
                            text += '<div class="mt-1"><span class="badge badge-warning text-dark" style="font-size: 10px !important; font-weight: normal !important; padding: 1.5px 5px;" data-toggle="tooltip" data-bs-toggle="tooltip" title="Sudah masuk rencana belanja: ' + e(plansText) + '">Sudah di Rencana (' + e(plansText) + ')</span></div>';
                        } else {
                            text += '<div class="mt-1"><span class="badge badge-info" style="font-size: 10px !important; font-weight: normal !important; padding: 1.5px 5px;" data-toggle="tooltip" data-bs-toggle="tooltip" title="Sebagian sudah masuk rencana: ' + e(plansText) + '">' + row.total_planned_vol + ' ' + e(row.unit) + ' di ' + e(plansText) + '</span></div>';
                        }
                    }

                    // Paired pengajuan barang riil
                    if (row.paired_pengajuans && row.paired_pengajuans.length > 0) {
                        row.paired_pengajuans.forEach(function(pb) {
                            let spekText = (pb.spesifikasi && pb.spesifikasi !== '-') ? ('<div class="text-secondary" style="font-size: 0.72rem; line-height: 1.25;"><strong class="text-muted">Spek:</strong> ' + e(pb.spesifikasi) + '</div>') : '';
                            
                            let countBadge = '';
                            if (pb.count && pb.count > 1) {
                                let monthLabel = '';
                                if (pb.months && pb.months.length > 0) {
                                    if (pb.months.length >= 10) {
                                        monthLabel = ' (' + pb.months[0] + ' s.d. ' + pb.months[pb.months.length - 1] + ')';
                                    } else {
                                        monthLabel = ' (' + pb.months.join(', ') + ')';
                                    }
                                }
                                countBadge = ' <span class="badge bg-success text-white font-weight-normal ms-1 ml-1" style="font-size: 0.67rem; padding: 2px 5px;" title="Dipadukan untuk ' + pb.count + ' alokasi pengajuan' + monthLabel + '"><i class="fas fa-sync-alt mr-1"></i>' + pb.count + 'x' + monthLabel + '</span>';
                            }

                            text += '<div class="mt-1 p-1 px-2 rounded border" style="background: #f0fdf4; border-color: #bbf7d0 !important; font-size: 0.75rem;">' +
                                '<div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 4px;">' +
                                    '<span class="text-success font-weight-bold"><i class="fas fa-check-circle mr-1"></i>' + e(pb.nama_barang) + countBadge + '</span>' +
                                    '<span class="text-muted" style="font-size: 0.70rem;"><i class="fas fa-user-tag text-info mr-1"></i>' + e(pb.pemohon) + '</span>' +
                                '</div>' +
                                spekText +
                            '</div>';
                        });
                    }

                    return text;
                }
            },
            {
                data: 'remaining_volume',
                className: 'font-tabular text-center',
                render: function(data, type, row) {
                    let unitHtml = row.unit ? ' <span class="text-muted small">' + e(row.unit) + '</span>' : '';
                    if (!row.is_allocatable) {
                        return '<span class="badge badge-light border text-muted" title="Sisa yang belum masuk rencana: 0">0</span>' + unitHtml;
                    }
                    return '<span class="font-weight-bold">' + data + '</span>' + unitHtml;
                }
            },
            {data: 'price_display', className: 'font-tabular text-right text-end'},
            {data: 'remaining_amount_display', className: 'font-tabular font-weight-bold text-info text-right text-end'},
            {data: 'months_included', className: 'text-center'},
            {
                data: 'kegiatan',
                searchable: false,
                render: function(data, type, row) {
                    if (!data || data === '-') return '-';
                    let clean = e(data);
                    let accountCodeHtml = row.account_code ? '<div class="font-tabular font-monospace text-muted mt-1" style="font-size: 0.72rem;"><i class="fas fa-barcode mr-1 text-secondary"></i>' + e(row.account_code) + '</div>' : '';
                    return '<span class="d-inline-block text-truncate text-secondary" style="max-width: 260px; vertical-align: middle; cursor: help;" data-toggle="tooltip" data-bs-toggle="tooltip" data-placement="top" title="' + clean + '">' + clean + '</span>' + accountCodeHtml;
                }
            }
        ],
        drawCallback: function() {
            $('[data-toggle="tooltip"], [data-bs-toggle="tooltip"]').tooltip();
            // Restore checkbox status
            $('.item-checkbox').each(function() {
                let id = $(this).val();
                if (selectedItems[id]) {
                    $(this).prop('checked', true);
                }
            });
        }
    });

    // 2. DataTable Saved Groups
    let tableGroups = $('#table-groups').DataTable({
        processing: true,
        serverSide: true,
        order: [],
        ajax: {
            url: "{{ route('bos.panduan.groups-data', [], false) }}",
            data: function(d) {
                d.year = $('#filter-year').val();
                d.status = $('#filter-group-status').val();
            }
        },
        columns: [
            {data: 'btn_expand', orderable: false, searchable: false, className: 'text-center cursor-pointer'},
            {data: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center'},
            {data: 'no_nota_display', name: 'no_nota', className: 'text-center font-tabular'},
            {data: 'title', name: 'title', className: 'font-weight-bold text-dark cursor-pointer'},
            {data: 'supplier_name', name: 'supplier_name', defaultContent: '-'},
            {data: 'planned_date_display', name: 'planned_date', className: 'font-tabular text-center'},
            {data: 'items_count', name: 'items_count', orderable: false, searchable: false, className: 'text-center font-tabular'},
            {data: 'total_amount_display', name: 'total_amount', orderable: false, searchable: false, className: 'font-tabular font-weight-bold text-dark text-right text-end'},
            {data: 'status_badge', name: 'status', orderable: false, className: 'text-center'},
            {data: 'action', orderable: false, searchable: false, className: 'text-center text-nowrap'}
        ],
        createdRow: function(row, data, dataIndex) {
            $(row).find('td:not(:last-child)').css('cursor', 'pointer');
        }
    });

    $('#filter-group-status').on('change', function() {
        tableGroups.draw();
    });

    // Format child row HTML for nested items table
    function formatGroupChildRow(rowData) {
        if (!rowData) {
            return '<div class="p-3 text-muted">Data rincian tidak tersedia.</div>';
        }

        let items = rowData.items_data;
        if (typeof items === 'string') {
            try {
                items = JSON.parse(items);
            } catch (err) {
                items = null;
            }
        }

        if (!Array.isArray(items) || items.length === 0) {
            if (Array.isArray(rowData.items) && rowData.items.length > 0) {
                items = rowData.items.map(function(it) {
                    let kk = it.kertas_kerja_item || it.kertasKerjaItem || {};
                    return {
                        id: it.id,
                        account_code: kk.account_code || it.account_code || '-',
                        description: kk.description || it.description || '-',
                        kegiatan: kk.kegiatan || it.kegiatan || '-',
                        volume_plan: it.volume_plan,
                        unit: it.unit || kk.unit || '-',
                        price: it.price,
                        price_display: 'Rp ' + formatRupiah(it.price),
                        total_amount: it.total_amount || (it.volume_plan * it.price),
                        total_amount_display: 'Rp ' + formatRupiah(it.total_amount || (it.volume_plan * it.price))
                    };
                });
            } else {
                items = [];
            }
        }

        let notes = rowData.notes ? ('<div class="mb-2 text-muted" style="font-size: 0.78rem;"><i class="fas fa-sticky-note mr-1 text-warning"></i><strong>Catatan:</strong> ' + e(rowData.notes) + '</div>') : '';
        
        let tableRows = '';
        let grandTotal = 0;

        if (Array.isArray(items) && items.length > 0) {
            items.forEach(function(it, idx) {
                let rowTotal = parseFloat(it.total_amount) || (parseFloat(it.volume_plan) * parseFloat(it.price)) || 0;
                grandTotal += rowTotal;

                let nameHtml = '';
                if (it.nama_pasar) {
                    nameHtml = '<strong class="text-dark">' + e(it.nama_pasar) + '</strong><br><small class="text-muted"><i class="fas fa-link mr-1"></i>ARKAS: ' + e(it.description) + '</small>';
                } else {
                    nameHtml = '<strong class="text-dark">' + e(it.description) + '</strong>';
                }
                if (it.spesifikasi) {
                    nameHtml += '<br><small class="text-secondary"><i class="fas fa-info-circle mr-1"></i>Spek: ' + e(it.spesifikasi) + '</small>';
                }

                tableRows += '<tr>' +
                    '<td class="text-center" width="35">' + (idx + 1) + '</td>' +
                    '<td width="115" class="font-tabular">' + e(it.account_code) + '</td>' +
                    '<td>' + nameHtml + '</td>' +
                    '<td width="230" class="text-muted" style="font-size: 0.76rem;">' + e(it.kegiatan) + '</td>' +
                    '<td width="75" class="text-center font-tabular">' + formatRupiah(it.volume_plan) + '</td>' +
                    '<td width="65" class="text-center">' + e(it.unit) + '</td>' +
                    '<td width="115" class="text-right font-tabular">Rp ' + formatRupiah(it.price) + '</td>' +
                    '<td width="125" class="text-right font-tabular font-weight-bold text-dark">Rp ' + formatRupiah(rowTotal) + '</td>' +
                '</tr>';
            });
        } else {
            tableRows = '<tr><td colspan="8" class="text-center text-muted py-2">Tidak ada rincian item belanja.</td></tr>';
        }

        let supplier = rowData.supplier_name ? rowData.supplier_name : '-';
        let plannedDate = rowData.planned_date_display ? rowData.planned_date_display : (rowData.planned_date ? String(rowData.planned_date).substring(0, 10) : '-');
        let monthYear = (rowData.month || '') + ' ' + (rowData.year || '');
        let printUrl = "{{ url('bos/panduan/print') }}/" + rowData.id;
        let noNotaDisplay = rowData.no_nota ? ('<span class="badge badge-light border text-info font-tabular font-weight-bold mr-2"><i class="fas fa-receipt mr-1"></i>' + e(rowData.no_nota) + '</span>') : '';
        let tntInfo = '';
        if (rowData.tnt_letter_number) {
            tntInfo = ' &nbsp;|&nbsp; <i class="fas fa-file-invoice-dollar mr-1 text-info"></i> Surat TNT: <strong class="text-primary font-tabular">' + e(rowData.tnt_letter_number) + '</strong>';
        }

        return '<div class="nested-group-detail p-3 my-1 border rounded bg-white shadow-sm" style="border-left: 4px solid #17a2b8 !important;">' +
            '<div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom flex-wrap" style="gap: 8px;">' +
                '<div>' +
                    '<h6 class="mb-0 font-weight-bold text-info" style="font-size: 0.88rem;">' + noNotaDisplay + '<strong class="text-dark">' + e(rowData.title) + '</strong></h6>' +
                    '<small class="text-muted"><i class="fas fa-store mr-1"></i> Supplier / Toko: <strong>' + e(supplier) + '</strong> &nbsp;|&nbsp; <i class="fas fa-calendar-alt mr-1"></i> Tgl: <strong>' + e(plannedDate) + '</strong> &nbsp;|&nbsp; Alokasi: <strong>' + e(monthYear) + '</strong>' + tntInfo + '</small>' +
                '</div>' +
                '<div class="d-flex align-items-center" style="gap: 8px;">' +
                    '<div>' + (rowData.status_badge || '') + '</div>' +
                    '<a href="' + printUrl + '" target="_blank" class="btn btn-xs btn-outline-info" title="Cetak Rencana Belanja"><i class="fas fa-print mr-1"></i> Cetak</a>' +
                '</div>' +
            '</div>' +
            notes +
            '<div class="table-responsive">' +
                '<table class="table table-sm table-bordered table-striped mb-0 table-nested-items w-100" style="font-size: 0.8rem;">' +
                    '<thead>' +
                        '<tr>' +
                            '<th class="text-center" width="35">No</th>' +
                            '<th width="115">Kode Rek</th>' +
                            '<th>Nama / Uraian Barang</th>' +
                            '<th width="230">Kegiatan</th>' +
                            '<th class="text-center" width="75">Volume</th>' +
                            '<th class="text-center" width="65">Satuan</th>' +
                            '<th class="text-right" width="115">Harga Satuan</th>' +
                            '<th class="text-right" width="125">Subtotal</th>' +
                        '</tr>' +
                    '</thead>' +
                    '<tbody>' + tableRows + '</tbody>' +
                    '<tfoot>' +
                        '<tr>' +
                            '<th colspan="7" class="text-right font-weight-bold">Total Rencana Belanja:</th>' +
                            '<th class="text-right font-weight-bold text-primary font-tabular">Rp ' + formatRupiah(grandTotal) + '</th>' +
                        '</tr>' +
                    '</tfoot>' +
                '</table>' +
            '</div>' +
        '</div>';
    }

    function toggleGroupChildRow(tr) {
        if (!tr || tr.length === 0) return;
        let row = tableGroups.row(tr);
        if (!row || !row.data()) return;

        let btn = tr.find('.btn-toggle-detail');
        let icon = btn.find('i');

        if (row.child.isShown()) {
            row.child.hide();
            tr.removeClass('shown');
            icon.removeClass('fa-minus').addClass('fa-plus');
        } else {
            let childHtml = formatGroupChildRow(row.data());
            row.child(childHtml).show();
            tr.addClass('shown');
            icon.removeClass('fa-plus').addClass('fa-minus');
        }
    }

    // Direct click on toggle button [+]
    $(document).on('click', '#table-groups .btn-toggle-detail', function(e) {
        e.preventDefault();
        e.stopPropagation();
        let tr = $(this).closest('tr');
        toggleGroupChildRow(tr);
    });

    // Click on row cell (except actions)
    $(document).on('click', '#table-groups tbody td', function(e) {
        // Ignore clicks inside child rows
        if ($(this).closest('.nested-group-detail').length || $(this).closest('tr').hasClass('child')) {
            return;
        }

        // Ignore if clicked on action buttons, links, or inputs
        if ($(e.target).closest('.btn-edit-group, .btn-convert, .btn-delete-group, .btn-show-detail, .btn-toggle-detail, a, button, input').length) {
            return;
        }

        // Ignore last column (action buttons)
        if ($(this).is(':last-child')) {
            return;
        }

        let tr = $(this).closest('tr');
        toggleGroupChildRow(tr);
    });

    // Expand all child rows
    $('#btn-expand-all').on('click', function() {
        tableGroups.rows().every(function() {
            let row = this;
            let tr = $(row.node());
            if (!row.child.isShown()) {
                row.child(formatGroupChildRow(row.data())).show();
                tr.addClass('shown');
                tr.find('.btn-toggle-detail').find('i').removeClass('fa-plus').addClass('fa-minus');
            }
        });
    });

    // Collapse all child rows
    $('#btn-collapse-all').on('click', function() {
        tableGroups.rows().every(function() {
            let row = this;
            let tr = $(row.node());
            if (row.child.isShown()) {
                row.child.hide();
                tr.removeClass('shown');
                tr.find('.btn-toggle-detail').find('i').removeClass('fa-minus').addClass('fa-plus');
            }
        });
    });

    // Tab switching via click delegation
    $('#panduan-tabs a, #panduan-tabs button').on('click', function(e) {
        e.preventDefault();
        let target = $(this).attr('href') || $(this).data('bs-target');
        if (target) {
            $('#panduan-tabs .nav-link').removeClass('active');
            $(this).addClass('active');
            $('.tab-content .tab-pane').removeClass('show active');
            $(target).addClass('show active');
            if (target === '#tab-groups') {
                tableGroups.columns.adjust().draw();
            } else {
                tableUnrealized.columns.adjust().draw();
            }
        }
    });

    // Action button to view in Modal
    $(document).on('click', '.btn-show-detail', function(e) {
        e.preventDefault();
        e.stopPropagation();
        let id = $(this).data('id');
        if (id) {
            showDetailModal(id);
        }
    });

    $('#filter-year, #filter-month').on('change', function() {
        tableUnrealized.draw();
        tableGroups.draw();
        selectedItems = {};
        updateSelectionUI();
    });

    $('#filter-hide-planned').on('change', function() {
        tableUnrealized.draw();
    });

    // Export Excel: Barang Belum Dibelanjakan (s/d Bulan ini, dikelompokkan per Program)
    $('#btn-export-unrealized').on('click', function(e) {
        e.preventDefault();
        let year = $('#filter-year').val();
        let month = $('#filter-month').val();
        let hidePlanned = $('#filter-hide-planned').is(':checked') ? 1 : 0;
        let url = "{{ route('bos.panduan.export-unrealized') }}?year=" + encodeURIComponent(year) +
                  "&month=" + encodeURIComponent(month) +
                  "&hide_planned=" + hidePlanned;
        window.location.href = url;
    });

    // Export Excel: Rincian Daftar Rencana Belanja (Draft)
    $('#btn-export-groups').on('click', function(e) {
        e.preventDefault();
        let year = $('#filter-year').val();
        let status = $('#filter-group-status').val();
        let url = "{{ route('bos.panduan.export-groups') }}?year=" + encodeURIComponent(year) +
                  "&status=" + encodeURIComponent(status);
        window.location.href = url;
    });

    // Checkbox Handling
    $(document).on('change', '.item-checkbox', function() {
        let id = $(this).val();
        let json = $(this).data('json');

        if ($(this).is(':checked')) {
            selectedItems[id] = json;
        } else {
            delete selectedItems[id];
            $('#check-all').prop('checked', false);
        }
        updateSelectionUI();
    });

    $('#check-all').on('change', function() {
        let isChecked = $(this).is(':checked');
        $('.item-checkbox:not(:disabled)').each(function() {
            $(this).prop('checked', isChecked);
            let id = $(this).val();
            let json = $(this).data('json');
            if (isChecked) {
                selectedItems[id] = json;
            } else {
                delete selectedItems[id];
            }
        });
        updateSelectionUI();
    });

    function updateSelectionUI() {
        let count = Object.keys(selectedItems).length;
        $('#selected-count').text(count);
        if (count > 0) {
            $('#btn-create-group').removeClass('btn-secondary').addClass('btn-info');
        } else {
            $('#btn-create-group').removeClass('btn-info').addClass('btn-secondary');
        }
    }

    function fetchNextNoNota() {
        let year = $('#filter-year').val();
        let month = $('#filter-month').val();
        $.ajax({
            url: "{{ route('bos.panduan.next-nota', [], false) }}",
            type: "GET",
            data: { year: year, month: month },
            success: function(res) {
                $('#create-no-nota').val(res.no_nota);
            }
        });
    }

    $('#btn-refresh-no-nota').click(function(e) {
        e.preventDefault();
        fetchNextNoNota();
    });

    // Open Modal Create Group
    $('#btn-create-group').click(function() {
        let count = Object.keys(selectedItems).length;
        if (count === 0) {
            Swal.fire({
                icon: 'info',
                title: 'Pilih Barang Terlebih Dahulu',
                text: 'Silakan beri tanda centang (✓) pada satu atau beberapa item barang di tabel sebelum membuat Rencana Belanja.'
            });
            return;
        }

        let tbody = $('#table-selected-preview tbody');
        tbody.empty();
        $('#search-selected-preview').val('');

        let grandTotal = 0;
        let index = 0;

        // Urutkan item terpilih berdasarkan abjad nama barang (A-Z)
        let items = Object.values(selectedItems);
        items.sort(function(a, b) {
            let descA = ((a.description || '') + '').trim();
            let descB = ((b.description || '') + '').trim();
            return descA.localeCompare(descB, 'id', { sensitivity: 'base', numeric: true });
        });

        items.forEach(function(item) {
            let itemTotal = item.remaining_volume * item.price;
            grandTotal += itemTotal;

            let firstPb = (item.paired_pengajuans && item.paired_pengajuans.length > 0) ? item.paired_pengajuans[0] : null;
            let refHtml = '';
            let marketVal = '';
            let spekVal = '';

            if (firstPb) {
                marketVal = firstPb.nama_barang || '';
                spekVal = firstPb.spesifikasi || '';
                refHtml = '<div class="mt-1 p-1 px-2 rounded border" style="background: #f0fdf4; border-color: #bbf7d0 !important; font-size: 0.72rem; line-height: 1.25;">' +
                    '<div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 4px;">' +
                        '<span class="text-success font-weight-bold"><i class="fas fa-check-circle mr-1"></i>Ref. Pemaduan: ' + e(marketVal) + '</span>' +
                        '<button type="button" class="btn btn-xs btn-outline-success btn-use-market-ref py-0 px-1" style="font-size: 0.68rem;" data-name="' + e(marketVal) + '" data-spek="' + e(spekVal) + '" title="Salin nama ini sebagai nama pasar di SIPLah"><i class="fas fa-magic mr-1"></i>Gunakan Nama Pasar</button>' +
                    '</div>' +
                    (spekVal ? '<div class="text-secondary mt-0.5"><small class="text-muted font-weight-bold">Spek:</small> ' + e(spekVal) + '</div>' : '') +
                '</div>';
            }

            let rowHtml = '<tr>' +
                '<td>' +
                    '<input type="hidden" name="items[' + index + '][bos_kertas_kerja_item_id]" value="' + item.id + '">' +
                    '<strong>' + e(item.description) + '</strong><br><small class="text-muted">' + e(item.account_code) + ' &bull; ' + e(item.kegiatan || '-') + '</small>' +
                    refHtml +
                    '<div class="mt-1 input-group input-group-sm">' +
                        '<span class="input-group-text py-0 px-1 bg-light text-muted font-weight-bold" style="font-size: 0.68rem;" title="Penyesuaian Nama Pasar / Nama di SIPLah (Opsional)">Nama di SIPLah:</span>' +
                        '<input type="text" name="items[' + index + '][nama_pasar]" class="form-control form-control-sm input-nama-pasar py-0" style="font-size: 0.73rem; height: 25px;" placeholder="Default: Sesuai nama Kertas Kerja (ARKAS)">' +
                        '<input type="hidden" name="items[' + index + '][spesifikasi]" class="input-spesifikasi" value="' + e(spekVal) + '">' +
                    '</div>' +
                '</td>' +
                '<td>' +
                    '<input type="number" step="1" min="1" class="form-control form-control-sm input-vol" name="items[' + index + '][volume_plan]" value="' + Math.round(item.remaining_volume) + '" max="' + item.remaining_volume + '" required>' +
                '</td>' +
                '<td>' + e(item.unit) + '</td>' +
                '<td>' +
                    '<input type="text" inputmode="numeric" class="form-control form-control-sm input-price input-thousand font-tabular" name="items[' + index + '][price]" value="' + formatThousand(item.price) + '" required>' +
                '</td>' +
                '<td class="cell-total font-weight-bold font-tabular">Rp ' + formatRupiah(itemTotal) + '</td>' +
            '</tr>';

            tbody.append(rowHtml);
            index++;
        });

        $('#modal-grand-total').text('Rp ' + formatRupiah(grandTotal));
        fetchNextNoNota();
        $('#create-supplier_id').val('').trigger('change');
        $('#createGroupModal').modal('show');
    });

    // Supplier Dropdown Change in Create Modal
    $('#create-supplier_id').on('change', function() {
        let val = $(this).val();
        let $selected = $(this).find(':selected');
        $('#create-offline-accounts-box').addClass('d-none');
        $('#create-offline-account-select').empty();

        if (val === 'custom') {
            $('#create-supplier_name').removeClass('d-none').val('').focus();
            $('#create-supplier-info').html('<span class="text-info"><i class="fas fa-info-circle mr-1"></i>Nama supplier ditulis manual (belum tersimpan di Data Pokok)</span>');
            $('#create-account_number').val('');
            $('#create-bank_name').val('');
        } else if (val !== '') {
            $('#create-supplier_name').addClass('d-none').val($selected.data('name'));
            let bank = $selected.data('bank');
            let acc = $selected.data('account');
            let type = $selected.data('type');
            let marketplace = $selected.data('marketplace');
            let accounts = $selected.data('accounts');
            let info = '';

            if (type === 'online') {
                $('#create-account_number').val('');
                $('#create-bank_name').val(bank || 'Bank Jatim');
                let mpName = marketplace || 'Siplah Toko Ladang';
                info = '<span class="badge badge-info mr-1"><i class="fas fa-globe mr-1"></i>' + mpName + '</span>' +
                       '<span class="text-success font-weight-500 mr-2"><i class="fas fa-check-circle mr-1"></i>Penerima TNT: <strong>PT. LADANG KARYA HUSADA</strong></span>' +
                       '<span class="text-muted small"><i class="fas fa-info-circle mr-1"></i>Nomor VA diinput saat pencairan / TNT Bank</span>';
            } else {
                $('#create-account_number').val(acc || '');
                $('#create-bank_name').val(bank || 'Bank Jatim');

                // Jika toko offline punya multi rekening (> 1)
                if (accounts && accounts.length > 1) {
                    $('#create-offline-accounts-box').removeClass('d-none');
                    let selectHtml = '';
                    accounts.forEach(function(a) {
                        let isPrim = a.is_primary ? ' (Utama)' : '';
                        let note = a.notes ? ' - ' + a.notes : '';
                        selectHtml += `<option value="${a.account_number}" data-bank="${a.bank_name}" ${a.is_primary ? 'selected' : ''}>${a.bank_name}: ${a.account_number}${isPrim}${note}</option>`;
                    });
                    $('#create-offline-account-select').html(selectHtml);
                    let $selAcc = $('#create-offline-account-select').find(':selected');
                    $('#create-account_number').val($selAcc.val());
                    $('#create-bank_name').val($selAcc.data('bank'));

                    info = '<span class="badge badge-light border mr-1"><i class="fas fa-store mr-1"></i>Toko Offline</span>' +
                           '<span class="text-muted"><i class="fas fa-info-circle mr-1"></i>Nama penerima di Surat TNT dikosongi.</span>';
                } else if (bank || acc) {
                    info = '<span class="badge badge-light border mr-1"><i class="fas fa-store mr-1"></i>Toko Offline</span>' +
                           '<span class="text-primary font-weight-500 mr-2"><i class="fas fa-university mr-1"></i>' + (bank || 'Bank') + ': ' + (acc || '-') + '</span>' +
                           '<span class="text-muted small font-italic">(Nama penerima di TNT dikosongi)</span>';
                } else {
                    info = '<span class="text-muted"><i class="fas fa-info-circle mr-1"></i>Rekening bank belum diisi di master supplier</span>';
                }
            }
            $('#create-supplier-info').html(info);
        } else {
            $('#create-supplier_name').addClass('d-none').val('');
            $('#create-supplier-info').empty();
            $('#create-account_number').val('');
            $('#create-bank_name').val('');
        }
    });

    $(document).on('change', '#create-offline-account-select', function() {
        let $opt = $(this).find(':selected');
        $('#create-account_number').val($opt.val());
        $('#create-bank_name').val($opt.data('bank'));
    });

    // Supplier Dropdown Change in Edit Modal
    $('#edit-supplier_id').on('change', function() {
        let val = $(this).val();
        let $selected = $(this).find(':selected');
        $('#edit-offline-accounts-box').addClass('d-none');
        $('#edit-offline-account-select').empty();

        if (val === 'custom') {
            $('#edit-supplier_name').removeClass('d-none').focus();
            $('#edit-supplier-info').html('<span class="text-info"><i class="fas fa-info-circle mr-1"></i>Nama supplier ditulis manual</span>');
            $('#edit-account_number').val('');
            $('#edit-bank_name').val('');
        } else if (val !== '') {
            $('#edit-supplier_name').addClass('d-none').val($selected.data('name'));
            let bank = $selected.data('bank');
            let acc = $selected.data('account');
            let type = $selected.data('type');
            let marketplace = $selected.data('marketplace');
            let accounts = $selected.data('accounts');
            let info = '';

            if (type === 'online') {
                $('#edit-bank_name').val(bank || 'Bank Jatim');
                $('#edit-account_number').val('');
                let mpName = marketplace || 'Siplah Toko Ladang';
                info = '<span class="badge badge-info mr-1"><i class="fas fa-globe mr-1"></i>' + mpName + '</span>' +
                       '<span class="text-success font-weight-500 mr-2"><i class="fas fa-check-circle mr-1"></i>Penerima TNT: <strong>PT. LADANG KARYA HUSADA</strong></span>' +
                       '<span class="text-muted small"><i class="fas fa-info-circle mr-1"></i>Nomor VA diinput saat pencairan / TNT Bank</span>';
            } else {
                $('#edit-account_number').val(acc || '');
                $('#edit-bank_name').val(bank || 'Bank Jatim');

                if (accounts && accounts.length > 1) {
                    $('#edit-offline-accounts-box').removeClass('d-none');
                    let selectHtml = '';
                    accounts.forEach(function(a) {
                        let isPrim = a.is_primary ? ' (Utama)' : '';
                        let note = a.notes ? ' - ' + a.notes : '';
                        selectHtml += `<option value="${a.account_number}" data-bank="${a.bank_name}">${a.bank_name}: ${a.account_number}${isPrim}${note}</option>`;
                    });
                    $('#edit-offline-account-select').html(selectHtml);

                    info = '<span class="badge badge-light border mr-1"><i class="fas fa-store mr-1"></i>Toko Offline</span>' +
                           '<span class="text-muted"><i class="fas fa-info-circle mr-1"></i>Nama penerima di Surat TNT dikosongi.</span>';
                } else if (bank || acc) {
                    info = '<span class="badge badge-light border mr-1"><i class="fas fa-store mr-1"></i>Toko Offline</span>' +
                           '<span class="text-primary font-weight-500 mr-2"><i class="fas fa-university mr-1"></i>' + (bank || 'Bank') + ': ' + (acc || '-') + '</span>' +
                           '<span class="text-muted small font-italic">(Nama penerima di TNT dikosongi)</span>';
                } else {
                    info = '<span class="text-muted"><i class="fas fa-info-circle mr-1"></i>Rekening bank belum diisi di master supplier</span>';
                }
            }
            $('#edit-supplier-info').html(info);
        } else {
            $('#edit-supplier_name').addClass('d-none').val('');
            $('#edit-supplier-info').empty();
            $('#edit-account_number').val('');
            $('#edit-bank_name').val('');
        }
    });

    $(document).on('change', '#edit-offline-account-select', function() {
        let $opt = $(this).find(':selected');
        $('#edit-account_number').val($opt.val());
        $('#edit-bank_name').val($opt.data('bank'));
    });

    // Dynamic calculation inside Modal
    $(document).on('input', '.input-vol, .input-price', function() {
        let row = $(this).closest('tr');
        let vol = parseFloat(row.find('.input-vol').val()) || 0;
        let price = parseThousand(row.find('.input-price').val());
        let total = vol * price;

        row.find('.cell-total').text('Rp ' + formatRupiah(total));

        let grandTotal = 0;
        $('#table-selected-preview tbody tr').each(function() {
            let v = parseFloat($(this).find('.input-vol').val()) || 0;
            let p = parseThousand($(this).find('.input-price').val());
            grandTotal += (v * p);
        });
        $('#modal-grand-total').text('Rp ' + formatRupiah(grandTotal));
    });

    // Live filter pencarian barang pada modal Buat Rencana Belanja Baru
    $('#search-selected-preview').on('input', function() {
        let q = $(this).val().toLowerCase().trim();
        $('#table-selected-preview tbody tr').each(function() {
            let desc = $(this).find('td:first strong').text().toLowerCase();
            let code = $(this).find('td:first small').text().toLowerCase();
            if (!q || desc.indexOf(q) > -1 || code.indexOf(q) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // Save Group
    $('#formCreateGroup').submit(function(e) {
        e.preventDefault();
        let formData = $(this).serializeArray();
        formData.forEach(function(item) {
            if (item.name.indexOf('[price]') > -1) {
                item.value = String(item.value).replace(/\./g, '');
            }
        });
        formData.push({ name: 'year', value: $('#filter-year').val() });
        formData.push({ name: 'month', value: $('#filter-month').val() });

        $.ajax({
            url: "{{ route('bos.panduan.store-group', [], false) }}",
            type: "POST",
            data: $.param(formData),
            success: function(res) {
                $('#createGroupModal').modal('hide');
                $('#formCreateGroup')[0].reset();
                selectedItems = {};
                updateSelectionUI();
                tableUnrealized.draw();
                tableGroups.draw();

                // Switch to tab groups
                $('#tab-groups-link').click();

                Swal.fire('Berhasil!', res.message, 'success');
            },
            error: function(xhr) {
                Swal.fire('Gagal Simpan', xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan.', 'error');
            }
        });
    });

    // Event listener untuk tombol "Gunakan Nama Pasar" dari referensi pemaduan
    $(document).on('click', '.btn-use-market-ref', function(e) {
        e.preventDefault();
        let name = $(this).data('name');
        let spek = $(this).data('spek');
        let td = $(this).closest('td');
        td.find('.input-nama-pasar').val(name).focus();
        if (spek) {
            td.find('.input-spesifikasi').val(spek);
        }
        $(this).html('<i class="fas fa-check mr-1"></i>Diterapkan').removeClass('btn-outline-success').addClass('btn-success');
    });

    // Detail Group Modal & View Mode Toggles
    let currentDetailData = null;
    let currentDetailMode = 'siplah'; // 'siplah' (grouped by Program & Rekening) or 'flat'

    $('#btn-mode-siplah').on('click', function() {
        currentDetailMode = 'siplah';
        $('#btn-mode-siplah').addClass('btn-primary active').removeClass('btn-outline-secondary');
        $('#btn-mode-flat').removeClass('btn-primary active').addClass('btn-outline-secondary');
        renderDetailItems();
    });

    $('#btn-mode-flat').on('click', function() {
        currentDetailMode = 'flat';
        $('#btn-mode-flat').addClass('btn-primary active').removeClass('btn-outline-secondary');
        $('#btn-mode-siplah').removeClass('btn-primary active').addClass('btn-outline-secondary');
        renderDetailItems();
    });

    function renderDetailItems() {
        if (!currentDetailData || !currentDetailData.items) return;

        let thead = $('#table-detail-items thead');
        let tbody = $('#table-detail-items tbody');
        let tfoot = $('#table-detail-items tfoot');
        thead.empty();
        tbody.empty();
        tfoot.empty();

        let items = currentDetailData.items;
        if (items.length === 0) {
            tbody.append('<tr><td colspan="6" class="text-center text-muted py-3">Tidak ada item barang dalam rencana belanja ini.</td></tr>');
            $('#detail-modal-grand-total').text('Rp 0');
            return;
        }

        let grandTotal = 0;

        if (currentDetailMode === 'siplah') {
            $('#detail-mode-badge').html('<i class="fas fa-layer-group mr-1 text-primary"></i>Dikelompokkan per Program/Kegiatan & Kode Rekening');
            thead.html(
                '<tr>' +
                    '<th width="35" class="text-center">NO</th>' +
                    '<th>URAIAN / NAMA BARANG</th>' +
                    '<th width="90" class="text-center">VOL PLAN</th>' +
                    '<th width="80" class="text-center">SATUAN</th>' +
                    '<th width="130" class="text-right">HARGA SATUAN</th>' +
                    '<th width="140" class="text-right">TOTAL</th>' +
                '</tr>'
            );
            tfoot.html(
                '<tr>' +
                    '<th colspan="5" class="text-right font-weight-bold">TOTAL KESELURUHAN RENCANA BELANJA:</th>' +
                    '<th id="detail-modal-grand-total" class="text-right font-weight-bold text-primary font-tabular">Rp 0</th>' +
                '</tr>'
            );

            // Grouping: Program / Kegiatan -> Kode Rekening
            let progGroups = {};
            items.forEach(function(item) {
                let kk = item.kertas_kerja_item || item.kertasKerjaItem || {};
                let pCode = kk.program_code || '-';
                let keg = kk.kegiatan || '-';
                let progKey = pCode + ' - ' + keg;
                let accKey = kk.account_code || item.account_code || '-';

                if (!progGroups[progKey]) {
                    progGroups[progKey] = {
                        program_code: pCode,
                        kegiatan: keg,
                        accounts: {}
                    };
                }
                if (!progGroups[progKey].accounts[accKey]) {
                    progGroups[progKey].accounts[accKey] = [];
                }
                progGroups[progKey].accounts[accKey].push(item);
            });

            let noIndex = 1;
            Object.keys(progGroups).forEach(function(progKey) {
                let prog = progGroups[progKey];
                let progRow = '<tr class="table-primary" style="background-color: #e0f2fe; border-top: 2px solid #0284c7;">' +
                    '<td colspan="6" class="font-weight-bold text-dark py-1" style="font-size: 0.85rem;">' +
                        '<i class="fas fa-folder-open text-primary mr-1"></i> Program / Kegiatan: ' + e(prog.program_code) + ' &bull; ' + e(prog.kegiatan) +
                    '</td>' +
                '</tr>';
                tbody.append(progRow);

                Object.keys(prog.accounts).forEach(function(accKey) {
                    let accItems = prog.accounts[accKey];
                    let accRow = '<tr style="background-color: #f8fafc; border-top: 1px solid #cbd5e1;">' +
                        '<td colspan="6" class="font-weight-bold text-dark pl-4 py-1" style="font-size: 0.82rem;">' +
                            '<i class="fas fa-tag text-info mr-1"></i> Kode Rekening: <span class="badge badge-info font-tabular mr-2">' + e(accKey) + '</span>' +
                            '<small class="text-muted font-weight-normal font-italic">(Checkout barang SIPLah dikelompokkan pada kode rekening ini)</small>' +
                        '</td>' +
                    '</tr>';
                    tbody.append(accRow);

                    let subTotal = 0;
                    accItems.forEach(function(item) {
                        let kk = item.kertas_kerja_item || item.kertasKerjaItem || {};
                        let itemTotal = item.total_amount || (item.volume_plan * item.price);
                        subTotal += itemTotal;
                        grandTotal += itemTotal;

                        let descOfficial = kk.description || item.description || '-';
                        let descHtml = '';
                        if (item.nama_pasar) {
                            descHtml = '<div class="font-weight-bold text-dark" style="font-size: 0.88rem;">' + e(item.nama_pasar) + '</div>' +
                                '<div class="text-muted" style="font-size: 0.75rem;"><i class="fas fa-link mr-1"></i>ARKAS: ' + e(descOfficial) + '</div>';
                        } else {
                            descHtml = '<div class="font-weight-bold text-dark" style="font-size: 0.88rem;">' + e(descOfficial) + '</div>';
                        }
                        if (item.spesifikasi) {
                            descHtml += '<div class="text-secondary" style="font-size: 0.72rem;"><i class="fas fa-info-circle mr-1"></i>Spek: ' + e(item.spesifikasi) + '</div>';
                        }

                        let itemRow = '<tr>' +
                            '<td class="text-center">' + noIndex++ + '</td>' +
                            '<td>' + descHtml + '</td>' +
                            '<td class="text-center font-tabular font-weight-bold">' + formatRupiah(item.volume_plan) + '</td>' +
                            '<td class="text-center">' + e(item.unit) + '</td>' +
                            '<td class="text-right font-tabular">Rp ' + formatRupiah(item.price) + '</td>' +
                            '<td class="text-right font-weight-bold font-tabular text-dark">Rp ' + formatRupiah(itemTotal) + '</td>' +
                        '</tr>';
                        tbody.append(itemRow);
                    });

                    let subTotalRow = '<tr style="background-color: #f1f5f9; border-bottom: 2px solid #cbd5e1;">' +
                        '<td colspan="5" class="text-right font-weight-bold text-secondary py-1" style="font-size: 0.8rem;">' +
                            '<i class="fas fa-calculator mr-1 text-muted"></i>Sub-total Rekening ' + e(accKey) + ':' +
                        '</td>' +
                        '<td class="text-right font-weight-bold font-tabular text-primary py-1" style="font-size: 0.85rem;">' +
                            'Rp ' + formatRupiah(subTotal) +
                        '</td>' +
                    '</tr>';
                    tbody.append(subTotalRow);
                });
            });
        } else {
            // Mode Daftar Belanja (Clean flat list: No, Uraian, Vol, Satuan, Harga, Total)
            $('#detail-mode-badge').html('<i class="fas fa-list mr-1 text-info"></i>Daftar Belanja Standar');
            thead.html(
                '<tr>' +
                    '<th width="35" class="text-center">NO</th>' +
                    '<th>URAIAN</th>' +
                    '<th width="90" class="text-center">VOL</th>' +
                    '<th width="80" class="text-center">SATUAN</th>' +
                    '<th width="130" class="text-right">HARGA</th>' +
                    '<th width="140" class="text-right">TOTAL</th>' +
                '</tr>'
            );
            tfoot.html(
                '<tr>' +
                    '<th colspan="5" class="text-right font-weight-bold">TOTAL:</th>' +
                    '<th id="detail-modal-grand-total" class="text-right font-weight-bold text-primary font-tabular">Rp 0</th>' +
                '</tr>'
            );

            let sortedItems = items.slice().sort(function(a, b) {
                let kkA = a.kertas_kerja_item || a.kertasKerjaItem || {};
                let descA = ((a.nama_pasar || kkA.description || a.description || '') + '').trim();
                let kkB = b.kertas_kerja_item || b.kertasKerjaItem || {};
                let descB = ((b.nama_pasar || kkB.description || b.description || '') + '').trim();
                return descA.localeCompare(descB, 'id', { sensitivity: 'base', numeric: true });
            });

            sortedItems.forEach(function(item, idx) {
                let kk = item.kertas_kerja_item || item.kertasKerjaItem || {};
                let itemTotal = item.total_amount || (item.volume_plan * item.price);
                grandTotal += itemTotal;

                let descOfficial = kk.description || item.description || '-';
                let desc = item.nama_pasar ? item.nama_pasar : descOfficial;
                let spekHtml = item.spesifikasi ? ('<div class="text-muted" style="font-size: 0.72rem;">' + e(item.spesifikasi) + '</div>') : '';

                let tr = '<tr>' +
                    '<td class="text-center">' + (idx + 1) + '</td>' +
                    '<td><div class="font-weight-bold text-dark">' + e(desc) + '</div>' + spekHtml + '</td>' +
                    '<td class="text-center font-tabular font-weight-bold">' + formatRupiah(item.volume_plan) + '</td>' +
                    '<td class="text-center">' + e(item.unit) + '</td>' +
                    '<td class="text-right font-tabular">Rp ' + formatRupiah(item.price) + '</td>' +
                    '<td class="text-right font-weight-bold text-dark font-tabular">Rp ' + formatRupiah(itemTotal) + '</td>' +
                '</tr>';
                tbody.append(tr);
            });
        }

        $('#detail-modal-grand-total').text('Rp ' + formatRupiah(grandTotal));
    }

    // Fungsi untuk menyusun teks format WhatsApp sesuai permintaan pengguna
    function generateWhatsAppText() {
        if (!currentDetailData) return '';

        let lines = [];
        let title = (currentDetailData.title || 'DAFTAR RENCANA BELANJA').toUpperCase();
        lines.push('*' + title + '*');
        lines.push('----------------------------------------');
        lines.push('No. Rencana: ' + (currentDetailData.no_nota || '-'));
        lines.push('Supplier: ' + (currentDetailData.supplier_name || '-'));
        lines.push('----------------------------------------');

        let items = currentDetailData.items || [];
        let grandTotal = 0;

        let sortedItems = items.slice().sort(function(a, b) {
            let kkA = a.kertas_kerja_item || a.kertasKerjaItem || {};
            let descA = ((a.nama_pasar || kkA.description || a.description || '') + '').trim();
            let kkB = b.kertas_kerja_item || b.kertasKerjaItem || {};
            let descB = ((b.nama_pasar || kkB.description || b.description || '') + '').trim();
            return descA.localeCompare(descB, 'id', { sensitivity: 'base', numeric: true });
        });

        sortedItems.forEach(function(item, idx) {
            let kk = item.kertas_kerja_item || item.kertasKerjaItem || {};
            let descOfficial = kk.description || item.description || '-';
            let desc = item.nama_pasar ? item.nama_pasar : descOfficial;
            let total = item.total_amount || (item.volume_plan * item.price);
            grandTotal += total;

            lines.push((idx + 1) + '. *' + desc + '*');
            let detailLine = '   ' + formatRupiah(item.volume_plan) + ' ' + (item.unit || '') + ' x Rp ' + formatRupiah(item.price) + ' = Rp ' + formatRupiah(total);
            lines.push(detailLine);
            if (item.spesifikasi) {
                lines.push('   (Spek: ' + item.spesifikasi + ')');
            }
            lines.push('');
        });

        lines.push('----------------------------------------');
        lines.push('*TOTAL KESELURUHAN: Rp ' + formatRupiah(grandTotal) + '*');
        lines.push('----------------------------------------');

        return lines.join('\n');
    }

    function copyWhatsAppText(text) {
        if (!text) return;

        function indicateSuccess() {
            let btn = $('#detail-btn-copy-wa');
            let origHtml = btn.html();
            btn.html('<i class="fas fa-check mr-1"></i> Tersalin!').removeClass('btn-outline-secondary').addClass('btn-success');
            setTimeout(function() {
                btn.html(origHtml).removeClass('btn-success').addClass('btn-outline-secondary');
            }, 2000);

            Swal.fire({
                icon: 'success',
                title: 'Teks Berhasil Disalin!',
                text: 'Format teks WhatsApp telah tersimpan di clipboard, siap di-paste.',
                timer: 1800,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        }

        // Method 1: Try modern clipboard API if supported and secure
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(function() {
                indicateSuccess();
            }).catch(function() {
                execCommandCopy();
            });
        } else {
            execCommandCopy();
        }

        // Method 2: execCommand using element INSIDE modal to bypass Bootstrap focus trap
        function execCommandCopy() {
            let modalBody = document.querySelector('#detailGroupModal .modal-body') || document.body;
            let textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = 'absolute';
            textArea.style.top = '0';
            textArea.style.left = '0';
            textArea.style.width = '10px';
            textArea.style.height = '10px';
            textArea.style.padding = '0';
            textArea.style.border = 'none';
            textArea.style.outline = 'none';
            textArea.style.boxShadow = 'none';
            textArea.style.background = 'transparent';
            textArea.style.opacity = '0.01';
            textArea.style.zIndex = '-1';
            
            modalBody.appendChild(textArea);
            textArea.focus();
            textArea.select();
            textArea.setSelectionRange(0, text.length);

            let successful = false;
            try {
                successful = document.execCommand('copy');
            } catch (err) {
                successful = false;
            }
            modalBody.removeChild(textArea);

            if (successful) {
                indicateSuccess();
            } else {
                // Method 3: Fallback popup modal with pre-selected text if browser blocks clipboard
                Swal.fire({
                    title: 'Salin Teks WhatsApp',
                    html: '<p class="text-muted small mb-2">Browser membatasi akses clipboard otomatis. Silakan salin teks di bawah ini (Ctrl + C):</p>' +
                          '<textarea id="swal-manual-copy" class="form-control" rows="8" style="font-size: 0.8rem; font-family: monospace;">' + e(text) + '</textarea>',
                    confirmButtonText: 'Tutup',
                    didOpen: function() {
                        let el = document.getElementById('swal-manual-copy');
                        if (el) {
                            el.focus();
                            el.select();
                        }
                    }
                });
            }
        }
    }

    // Tombol Kirim / Bagikan ke WhatsApp
    $(document).on('click', '#detail-btn-wa', function(e) {
        e.preventDefault();
        let waText = generateWhatsAppText();
        if (!waText) return;

        let phone = '';
        if (currentDetailData && currentDetailData.supplier && currentDetailData.supplier.phone) {
            let clean = String(currentDetailData.supplier.phone).replace(/[^0-9]/g, '');
            if (clean.startsWith('08')) {
                phone = '628' + clean.substring(2);
            } else if (clean.startsWith('628')) {
                phone = clean;
            }
        }

        let url = phone 
            ? ('https://api.whatsapp.com/send?phone=' + phone + '&text=' + encodeURIComponent(waText))
            : ('https://api.whatsapp.com/send?text=' + encodeURIComponent(waText));

        window.open(url, '_blank');
    });

    // Tombol Salin Format Teks WhatsApp
    $(document).on('click', '#detail-btn-copy-wa', function(e) {
        e.preventDefault();
        let waText = generateWhatsAppText();
        if (!waText) return;

        copyWhatsAppText(waText);
    });

    // Function to fetch & show Detail Group Modal
    function showDetailModal(id) {
        $.ajax({
            url: "{{ url('bos/panduan/show-group') }}/" + id,
            type: "GET",
            success: function(data) {
                currentDetailData = data;
                $('#detail-group-title').text(data.title);
                $('#detail-group-no-nota').text(data.no_nota || '-');
                let supText = data.supplier_name ? data.supplier_name : '-';
                if (data.account_number) {
                    supText += ' (' + (data.bank_name || 'Bank') + ': ' + data.account_number + ')';
                }
                $('#detail-group-supplier').text(supText);
                $('#detail-group-date').text(data.planned_date ? data.planned_date.substring(0, 10) : '-');
                $('#detail-group-month').text(data.month + ' ' + data.year);

                let statusBadge = '';
                if (data.status === 'realized') {
                    statusBadge = '<span class="badge badge-success font-weight-bold" style="font-size: 0.85rem; padding: 4px 8px; border-radius: 4px;">Realisasi</span>';
                    $('#detail-btn-toggle-order').addClass('d-none');
                } else if (data.status === 'ordered') {
                    statusBadge = '<span class="badge badge-primary font-weight-bold" style="font-size: 0.85rem; padding: 4px 8px; border-radius: 4px; background-color: #0284c7 !important; border: 1px solid #0369a1;">Order</span>';
                    $('#detail-btn-toggle-order').removeClass('d-none').removeClass('btn-outline-primary').addClass('btn-primary')
                        .html('<i class="fas fa-check mr-1"></i> <span id="detail-btn-order-label">Order (Klik utk Batal)</span>')
                        .attr('data-status', 'draft')
                        .attr('title', 'Klik untuk mengembalikan status ke Draft Rencana');
                } else if (data.status === 'diajukan_ke_bank') {
                    statusBadge = '<span class="badge badge-info font-weight-bold" style="font-size: 0.85rem; padding: 4px 8px; border-radius: 4px;">TNT</span>';
                    $('#detail-btn-toggle-order').addClass('d-none');
                } else {
                    statusBadge = '<span class="badge badge-warning text-dark font-weight-bold" style="font-size: 0.85rem; padding: 4px 8px; border-radius: 4px;">Draft</span>';
                    $('#detail-btn-toggle-order').removeClass('d-none').removeClass('btn-primary').addClass('btn-outline-primary')
                        .html('<i class="fas fa-cart-arrow-down mr-1"></i> <span id="detail-btn-order-label">Tandai Order</span>')
                        .attr('data-status', 'ordered')
                        .attr('title', 'Tandai bahwa rencana belanja ini sudah diorder');
                }
                $('#detail-group-status').html(statusBadge);

                if (data.tnt_letter_number) {
                    $('#detail-group-tnt').text(data.tnt_letter_number);
                    $('#detail-group-tnt-box').removeClass('d-none');
                } else {
                    $('#detail-group-tnt-box').addClass('d-none');
                }

                $('#detail-btn-print').attr('href', "{{ url('bos/panduan/print') }}/" + data.id);

                if (data.notes) {
                    $('#detail-group-notes').text(data.notes);
                    $('#detail-group-notes-box').removeClass('d-none');
                } else {
                    $('#detail-group-notes-box').addClass('d-none');
                }

                // Default to SIPLah checkout mode
                currentDetailMode = 'siplah';
                $('#btn-mode-siplah').addClass('btn-primary active').removeClass('btn-outline-secondary');
                $('#btn-mode-flat').removeClass('btn-primary active').addClass('btn-outline-secondary');
                renderDetailItems();

                $('#detailGroupModal').modal('show');
            },
            error: function(xhr) {
                Swal.fire('Gagal', 'Gagal memuat rincian item belanja.', 'error');
            }
        });
    }

    // Open Edit Group Modal
    $(document).on('click', '.btn-edit-group', function() {
        let id = $(this).data('id');
        $.ajax({
            url: "{{ url('bos/panduan/edit-group') }}/" + id,
            type: "GET",
            success: function(data) {
                $('#edit-group-id').val(data.id);
                $('#edit-no-nota').val(data.no_nota || '');
                $('#edit-title').val(data.title);
                $('#edit-planned_date').val(data.planned_date ? data.planned_date.substring(0, 10) : '');
                $('#edit-notes').val(data.notes);

                if (data.supplier_id) {
                    $('#edit-supplier_id').val(data.supplier_id).trigger('change');
                } else if (data.supplier_name) {
                    let matched = false;
                    $('#edit-supplier_id option').each(function() {
                        if ($(this).data('name') && $(this).data('name').toLowerCase() === data.supplier_name.toLowerCase()) {
                            $('#edit-supplier_id').val($(this).val()).trigger('change');
                            matched = true;
                            return false;
                        }
                    });
                    if (!matched) {
                        $('#edit-supplier_id').val('custom').trigger('change');
                        $('#edit-supplier_name').val(data.supplier_name);
                    }
                } else {
                    $('#edit-supplier_id').val('').trigger('change');
                }

                if (data.account_number) {
                    $('#edit-account_number').val(data.account_number);
                }

                let tbody = $('#table-edit-preview tbody');
                tbody.empty();
                $('#search-edit-preview').val('');

                let grandTotal = 0;
                if (data.items && data.items.length > 0) {
                    data.items.sort(function(a, b) {
                        let kkA = a.kertas_kerja_item || a.kertasKerjaItem || {};
                        let descA = ((kkA.description || a.description || '') + '').trim();
                        let kkB = b.kertas_kerja_item || b.kertasKerjaItem || {};
                        let descB = ((kkB.description || b.description || '') + '').trim();
                        return descA.localeCompare(descB, 'id', { sensitivity: 'base', numeric: true });
                    });
                }

                data.items.forEach(function(item, index) {
                    let itemTotal = item.volume_plan * item.price;
                    grandTotal += itemTotal;

                    let kk = item.kertas_kerja_item || item.kertasKerjaItem || {};
                    let desc = kk.description || item.description || '-';
                    let acc = kk.account_code || item.account_code || '-';
                    let keg = kk.kegiatan || item.kegiatan || '-';
                    let currentNamaPasar = item.nama_pasar || '';
                    let currentSpek = item.spesifikasi || '';

                    let firstPb = (kk.pengajuan_barangs && kk.pengajuan_barangs.length > 0) ? kk.pengajuan_barangs[0] : ((kk.pengajuanBarangs && kk.pengajuanBarangs.length > 0) ? kk.pengajuanBarangs[0] : null);
                    let refHtml = '';
                    let marketVal = '';
                    let spekVal = '';
                    if (firstPb) {
                        marketVal = firstPb.nama_barang || '';
                        spekVal = firstPb.spesifikasi || '';
                        refHtml = '<div class="mt-1 p-1 px-2 rounded border" style="background: #f0fdf4; border-color: #bbf7d0 !important; font-size: 0.72rem; line-height: 1.25;">' +
                            '<div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 4px;">' +
                                '<span class="text-success font-weight-bold"><i class="fas fa-check-circle mr-1"></i>Ref. Pemaduan: ' + e(marketVal) + '</span>' +
                                '<button type="button" class="btn btn-xs btn-outline-success btn-use-market-ref py-0 px-1" style="font-size: 0.68rem;" data-name="' + e(marketVal) + '" data-spek="' + e(spekVal) + '" title="Salin nama ini sebagai nama pasar di SIPLah"><i class="fas fa-magic mr-1"></i>Gunakan Nama Pasar</button>' +
                            '</div>' +
                            (spekVal ? '<div class="text-secondary mt-0.5"><small class="text-muted font-weight-bold">Spek:</small> ' + e(spekVal) + '</div>' : '') +
                        '</div>';
                    }

                    let rowHtml = '<tr>' +
                        '<td>' +
                            '<input type="hidden" name="items[' + index + '][id]" value="' + item.id + '">' +
                            '<strong>' + e(desc) + '</strong><br><small class="text-muted">' + e(acc) + ' &bull; ' + e(keg) + '</small>' +
                            refHtml +
                            '<div class="mt-1 input-group input-group-sm">' +
                                '<span class="input-group-text py-0 px-1 bg-light text-muted font-weight-bold" style="font-size: 0.68rem;" title="Penyesuaian Nama Pasar / Nama di SIPLah (Opsional)">Nama di SIPLah:</span>' +
                                '<input type="text" name="items[' + index + '][nama_pasar]" class="form-control form-control-sm input-nama-pasar py-0" style="font-size: 0.73rem; height: 25px;" placeholder="Default: Sesuai nama Kertas Kerja (ARKAS)" value="' + e(currentNamaPasar) + '">' +
                                '<input type="hidden" name="items[' + index + '][spesifikasi]" class="input-spesifikasi" value="' + e(currentSpek || spekVal) + '">' +
                            '</div>' +
                        '</td>' +
                        '<td>' +
                            '<input type="number" step="1" min="1" class="form-control form-control-sm input-vol-edit" name="items[' + index + '][volume_plan]" value="' + Math.round(item.volume_plan) + '" required>' +
                        '</td>' +
                        '<td>' + e(item.unit) + '</td>' +
                        '<td>' +
                            '<input type="text" inputmode="numeric" class="form-control form-control-sm input-price-edit input-thousand font-tabular" name="items[' + index + '][price]" value="' + formatThousand(item.price) + '" required>' +
                        '</td>' +
                        '<td class="cell-total-edit font-weight-bold font-tabular">Rp ' + formatRupiah(itemTotal) + '</td>' +
                        '<td class="text-center align-middle">' +
                            '<button type="button" class="btn btn-xs btn-outline-danger btn-delete-edit-item" title="Hapus barang ini dari rencana belanja">' +
                                '<i class="fas fa-trash-alt"></i>' +
                            '</button>' +
                        '</td>' +
                    '</tr>';

                    tbody.append(rowHtml);
                });

                $('#edit-modal-grand-total').text('Rp ' + formatRupiah(grandTotal));
                $('#editGroupModal').modal('show');
            },
            error: function(xhr) {
                Swal.fire('Gagal', xhr.responseJSON ? xhr.responseJSON.message : 'Gagal memuat data.', 'error');
            }
        });
    });

    // Helper: Recalculate Edit Grand Total
    function recalculateEditGrandTotal() {
        let grandTotal = 0;
        $('#table-edit-preview tbody tr').each(function() {
            let v = parseFloat($(this).find('.input-vol-edit').val()) || 0;
            let p = parseThousand($(this).find('.input-price-edit').val());
            grandTotal += (v * p);
        });
        $('#edit-modal-grand-total').text('Rp ' + formatRupiah(grandTotal));
    }

    // Delete item row from Edit Modal
    $(document).on('click', '.btn-delete-edit-item', function() {
        let tbody = $('#table-edit-preview tbody');
        let totalRows = tbody.find('tr').length;
        if (totalRows <= 1) {
            Swal.fire('Perhatian', 'Rencana belanja harus memiliki minimal 1 barang. Jika ingin membatalkan seluruh rencana belanja, silakan hapus Rencana Belanja melalui tombol aksi di tabel utama.', 'warning');
            return;
        }

        let row = $(this).closest('tr');
        let itemName = row.find('td:first strong').text() || 'barang ini';

        Swal.fire({
            title: 'Hapus Barang?',
            text: 'Keluarkan "' + itemName + '" dari rencana belanja ini?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash-alt mr-1"></i> Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (result.isConfirmed) {
                row.remove();
                // Re-index remaining rows for consistent form array serialization
                tbody.find('tr').each(function(idx) {
                    $(this).find('input[name*="[id]"]').attr('name', 'items[' + idx + '][id]');
                    $(this).find('input[name*="[nama_pasar]"]').attr('name', 'items[' + idx + '][nama_pasar]');
                    $(this).find('input[name*="[spesifikasi]"]').attr('name', 'items[' + idx + '][spesifikasi]');
                    $(this).find('input[name*="[volume_plan]"]').attr('name', 'items[' + idx + '][volume_plan]');
                    $(this).find('input[name*="[price]"]').attr('name', 'items[' + idx + '][price]');
                });
                recalculateEditGrandTotal();
            }
        });
    });

    // Live filter pencarian barang pada modal Edit Rencana Belanja
    $('#search-edit-preview').on('input', function() {
        let q = $(this).val().toLowerCase().trim();
        $('#table-edit-preview tbody tr').each(function() {
            let desc = $(this).find('td:first strong').text().toLowerCase();
            let code = $(this).find('td:first small').text().toLowerCase();
            if (!q || desc.indexOf(q) > -1 || code.indexOf(q) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // Calculation inside Edit Modal
    $(document).on('input', '.input-vol-edit, .input-price-edit', function() {
        let row = $(this).closest('tr');
        let vol = parseFloat(row.find('.input-vol-edit').val()) || 0;
        let price = parseThousand(row.find('.input-price-edit').val());
        let total = vol * price;

        row.find('.cell-total-edit').text('Rp ' + formatRupiah(total));
        recalculateEditGrandTotal();
    });

    // Update Group AJAX
    $('#formEditGroup').submit(function(e) {
        e.preventDefault();
        let id = $('#edit-group-id').val();
        let formData = $(this).serializeArray();
        formData.forEach(function(item) {
            if (item.name.indexOf('[price]') > -1) {
                item.value = String(item.value).replace(/\./g, '');
            }
        });

        $.ajax({
            url: "{{ url('bos/panduan/update-group') }}/" + id,
            type: "PUT",
            data: $.param(formData),
            success: function(res) {
                $('#editGroupModal').modal('hide');
                tableUnrealized.draw();
                tableGroups.draw();
                Swal.fire('Berhasil!', res.message, 'success');
            },
            error: function(xhr) {
                Swal.fire('Gagal Simpan', xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan.', 'error');
            }
        });
    });

    // Open Convert Modal
    $(document).on('click', '.btn-convert', function() {
        let id = $(this).data('id');
        $('#convert-group-id').val(id);
        $('#convertModal').modal('show');
    });

    // Convert to Realisasi
    $('#formConvert').submit(function(e) {
        e.preventDefault();
        let id = $('#convert-group-id').val();
        $.ajax({
            url: "{{ url('bos/panduan/convert') }}/" + id,
            type: "POST",
            data: $(this).serialize(),
            success: function(res) {
                $('#convertModal').modal('hide');
                tableUnrealized.draw();
                tableGroups.draw();
                Swal.fire('Berhasil!', res.message, 'success');
            },
            error: function(xhr) {
                Swal.fire('Gagal Realisasi', xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan.', 'error');
            }
        });
    });

    // Toggle Order Status (Draft <-> Ordered SIPLah) from Datatable Action
    $(document).on('click', '.btn-toggle-order', function(e) {
        e.preventDefault();
        let btn = $(this);
        let id = btn.data('id');
        let targetStatus = btn.data('status');
        let isOrdering = targetStatus === 'ordered';

        let confirmTitle = isOrdering ? 'Tandai Sudah Diorder?' : 'Kembalikan Status ke Draft?';
        let confirmText = isOrdering 
            ? 'Status pesanan belanja akan ditandai sebagai "Sudah Diorder".' 
            : 'Status pesanan akan dikembalikan menjadi Draft Rencana Belanja.';

        Swal.fire({
            title: confirmTitle,
            text: confirmText,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: isOrdering ? '#0284c7' : '#64748b',
            confirmButtonText: isOrdering ? '<i class="fas fa-cart-arrow-down mr-1"></i> Ya, Sudah Diorder!' : '<i class="fas fa-undo mr-1"></i> Ya, Kembalikan ke Draft',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ url('bos/panduan/toggle-order') }}/" + id,
                    type: "POST",
                    data: {
                        _token: '{{ csrf_token() }}',
                        status: targetStatus
                    },
                    success: function(res) {
                        tableGroups.draw(false);
                        Swal.fire({
                            icon: 'success',
                            title: 'Status Diperbarui!',
                            text: res.message,
                            timer: 2000,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                    },
                    error: function(xhr) {
                        Swal.fire('Gagal!', xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan.', 'error');
                    }
                });
            }
        });
    });

    // Toggle Order Status from Inside Detail Modal
    $(document).on('click', '#detail-btn-toggle-order', function(e) {
        e.preventDefault();
        if (!currentDetailData) return;
        let id = currentDetailData.id;
        let targetStatus = $(this).attr('data-status');
        let isOrdering = targetStatus === 'ordered';

        let confirmTitle = isOrdering ? 'Tandai Sudah Diorder?' : 'Kembalikan Status ke Draft?';
        let confirmText = isOrdering 
            ? 'Status pesanan belanja ini akan ditandai sebagai "Sudah Diorder".' 
            : 'Status pesanan akan dikembalikan menjadi Draft Rencana Belanja.';

        Swal.fire({
            title: confirmTitle,
            text: confirmText,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: isOrdering ? '#0284c7' : '#64748b',
            confirmButtonText: isOrdering ? '<i class="fas fa-cart-arrow-down mr-1"></i> Ya, Sudah Diorder!' : '<i class="fas fa-undo mr-1"></i> Ya, Kembalikan ke Draft',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ url('bos/panduan/toggle-order') }}/" + id,
                    type: "POST",
                    data: {
                        _token: '{{ csrf_token() }}',
                        status: targetStatus
                    },
                    success: function(res) {
                        tableGroups.draw(false);
                        showDetailModal(id);
                        Swal.fire({
                            icon: 'success',
                            title: 'Status Diperbarui!',
                            text: res.message,
                            timer: 2000,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                    },
                    error: function(xhr) {
                        Swal.fire('Gagal!', xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan.', 'error');
                    }
                });
            }
        });
    });

    // Delete Group
    $(document).on('click', '.btn-delete-group', function(e) {
        e.preventDefault();
        let url = $(this).data('url');
        Swal.fire({
            title: 'Hapus Rencana Belanja ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(res) {
                        tableUnrealized.draw();
                        tableGroups.draw();
                        Swal.fire('Terhapus!', res.message, 'success');
                    },
                    error: function(xhr) {
                        Swal.fire('Gagal!', xhr.responseJSON ? xhr.responseJSON.message : 'Gagal menghapus.', 'error');
                    }
                });
            }
        });
    });

    // ── INTEGRASI BAYAR UTILITAS KE RENCANA BELANJA ──
    function loadUtilityModalData() {
        const year = $('#filter-year').val() || '{{ $selectedYear }}';
        const month = $('#util-filter-month').val() || $('#filter-month').val() || '{{ $currentMonthName }}';

        $('#tbody-utilitas-bundling').html('<tr><td colspan="6" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin mr-1"></i> Memuat data struk utilitas...</td></tr>');

        $.ajax({
            url: "{{ route('bos.panduan.utility-modal-data') }}",
            type: 'GET',
            data: { year: year, month: month },
            success: function(res) {
                $('#util-no-nota').val(res.no_nota);
                $('#util-title').val(res.title || ('Bayar Utilitas dan BBM ' + res.month));

                let guruHtml = '<option value="">-- Pilih Petugas Talangan --</option>';
                (res.gurus || []).forEach(function(g) {
                    const isSel = (res.default_guru_id && g.id == res.default_guru_id) ? 'selected' : '';
                    guruHtml += `<option value="${g.id}" data-bank="${g.bank_name || ''}" data-account="${g.account_number || ''}" ${isSel}>${g.name}</option>`;
                });
                $('#util-guru-id').html(guruHtml);
                $('#util-guru-id').trigger('change');

                window.utilityReceiptsData = res.receipts || [];
                window.utilityKkItemsData = res.kertas_kerja_items || [];

                renderUtilityTable(res.receipts, res.kertas_kerja_items);
            },
            error: function(xhr) {
                $('#tbody-utilitas-bundling').html('<tr><td colspan="6" class="text-center text-danger py-4"><i class="fas fa-exclamation-triangle mr-1"></i> Gagal memuat data tagihan utilitas.</td></tr>');
            }
        });
    }

    function renderUtilityTable(receipts, kkItems) {
        if (!receipts || receipts.length === 0) {
            $('#tbody-utilitas-bundling').html(`
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="fas fa-folder-open text-secondary fa-2x mb-2 d-block"></i>
                        Tidak ada struk tagihan utilitas belum dibayar untuk bulan ini.<br>
                        <small>Silakan scan / upload struk terlebih dahulu di modul <a href="{{ route('bos.utilitas.index') }}" target="_blank" class="font-weight-bold">Struk Utilitas</a>.</small>
                    </td>
                </tr>
            `);
            $('#check-all-util').prop('checked', false);
            updateUtilityTotals();
            return;
        }

        let rows = '';
        receipts.forEach(function(r) {
            let kkOptions = '<option value="">-- Pilih Komponen KK ARKAS --</option>';
            kkItems.forEach(function(kk) {
                const isSel = (r.suggested_kk_id && kk.id == r.suggested_kk_id) ? 'selected' : '';
                kkOptions += `<option value="${kk.id}" data-price="${kk.price}" data-remaining="${kk.remaining_amount}" ${isSel}>${kk.label}</option>`;
            });

            const iconClass = r.category === 'internet' ? 'fa-wifi text-primary' : (r.category === 'telepon' ? 'fa-phone text-success' : 'fa-bolt text-warning');
            const tokenBadge = r.token_number ? `<span class="badge badge-light border text-dark font-tabular mt-1 d-block" style="font-size: 0.7rem;"><i class="fas fa-key mr-1 text-warning"></i>${r.token_number}</span>` : '';
            const receiptInfo = r.receipt_number ? `<small class="text-muted d-block font-tabular">${r.receipt_number}</small>` : '';

            rows += `
                <tr class="util-row" data-id="${r.id}">
                    <td class="text-center">
                        <input type="checkbox" class="util-checkbox" value="${r.id}" checked>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <i class="fas ${iconClass} mr-2 fa-lg"></i>
                            <div>
                                <strong class="text-dark d-block" style="font-size: 0.82rem;">${r.profile_name}</strong>
                                <small class="text-muted d-block">Pel: ${r.customer_name} (${r.customer_number})</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <select class="form-select form-select-sm custom-select custom-select-sm util-kk-select font-weight-bold" style="font-size: 0.75rem; width: 100%;" required>
                            ${kkOptions}
                        </select>
                        <div class="util-kk-warning small font-weight-bold text-danger mt-1 d-none">
                            <i class="fas fa-exclamation-triangle mr-1"></i> Nominal melebihi sisa anggaran KK!
                        </div>
                    </td>
                    <td class="text-right font-tabular font-weight-bold text-secondary">
                        ${r.total_amount_display}
                    </td>
                    <td class="text-right">
                        <input type="text" class="form-control form-control-sm text-right font-tabular font-weight-bold util-nominal-input" 
                               value="${formatThousand(r.total_amount)}" style="height: 28px; font-size: 0.8125rem;">
                    </td>
                    <td class="text-center">
                        ${tokenBadge}
                        ${receiptInfo}
                        <small class="text-muted font-tabular">${r.transaction_date}</small>
                    </td>
                </tr>
            `;
        });

        $('#tbody-utilitas-bundling').html(rows);
        $('#check-all-util').prop('checked', true);
        updateUtilityTotals();
    }

    function updateUtilityTotals() {
        let totalNominal = 0;
        let count = 0;
        let hasOverBudget = false;

        $('#tbody-utilitas-bundling .util-row').each(function() {
            const chk = $(this).find('.util-checkbox');
            if (chk.is(':checked')) {
                count++;
                const nominalRaw = $(this).find('.util-nominal-input').val().replace(/\D/g, '') || 0;
                const nominal = parseFloat(nominalRaw);
                totalNominal += nominal;

                const selectedKk = $(this).find('.util-kk-select option:selected');
                const remaining = parseFloat(selectedKk.data('remaining') || 0);
                const warn = $(this).find('.util-kk-warning');
                if (selectedKk.val() && nominal > (remaining + 0.001)) {
                    warn.removeClass('d-none');
                    hasOverBudget = true;
                } else {
                    warn.addClass('d-none');
                }
            }
        });

        $('#util-selected-count').text(count);
        $('#util-grand-total').text('Rp ' + formatThousand(totalNominal));

        if (hasOverBudget) {
            $('#util-budget-warning').removeClass('d-none');
        } else {
            $('#util-budget-warning').addClass('d-none');
        }
    }

    $('#btn-bayar-utilitas').on('click', function() {
        const currentM = $('#filter-month').val() || '{{ $currentMonthName }}';
        $('#util-filter-month').val(currentM);
        loadUtilityModalData();
        $('#modalBayarUtilitas').modal('show');
    });

    $('#util-filter-month').on('change', function() {
        loadUtilityModalData();
    });

    $('#util-guru-id').on('change', function() {
        const selected = $(this).find('option:selected');
        $('#util-bank-name').val(selected.data('bank') || 'Bank Jatim');
        $('#util-account-number').val(selected.data('account') || '-');
    });

    $('#btn-refresh-util-no-nota').on('click', function() {
        const year = $('#filter-year').val() || '{{ $selectedYear }}';
        const month = $('#util-filter-month').val();
        $.get("{{ route('bos.panduan.next-nota') }}", { year: year, month: month }, function(res) {
            if (res.no_nota) {
                $('#util-no-nota').val(res.no_nota);
            }
        });
    });

    $(document).on('change', '.util-checkbox', function() {
        updateUtilityTotals();
        const totalRows = $('#tbody-utilitas-bundling .util-checkbox').length;
        const checkedRows = $('#tbody-utilitas-bundling .util-checkbox:checked').length;
        $('#check-all-util').prop('checked', totalRows > 0 && totalRows === checkedRows);
    });

    $('#check-all-util').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('#tbody-utilitas-bundling .util-checkbox').prop('checked', isChecked);
        updateUtilityTotals();
    });

    $(document).on('input', '.util-nominal-input', function() {
        let val = $(this).val();
        $(this).val(formatThousand(val));
        updateUtilityTotals();
    });

    $(document).on('change', '.util-kk-select', function() {
        updateUtilityTotals();
    });

    $('#formBayarUtilitas').on('submit', function(e) {
        e.preventDefault();

        const selectedRows = $('#tbody-utilitas-bundling .util-row').filter(function() {
            return $(this).find('.util-checkbox').is(':checked');
        });

        if (selectedRows.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Belum Ada Tagihan Dipilih',
                text: 'Silakan centang minimal 1 tagihan utilitas untuk dibundel.',
            });
            return;
        }

        const receiptsPayload = [];
        let missingKk = false;

        selectedRows.each(function() {
            const receiptId = $(this).data('id');
            const kkId = $(this).find('.util-kk-select').val();
            const nominal = parseFloat($(this).find('.util-nominal-input').val().replace(/\D/g, '') || 0);

            if (!kkId) {
                missingKk = true;
            }

            receiptsPayload.push({
                id: receiptId,
                bos_kertas_kerja_item_id: kkId,
                nominal: nominal
            });
        });

        if (missingKk) {
            Swal.fire({
                icon: 'warning',
                title: 'Komponen Kertas Kerja Belum Dipilih',
                text: 'Harap pastikan semua tagihan yang dicentang sudah memiliki pilihan Komponen Kertas Kerja ARKAS.',
            });
            return;
        }

        const payload = {
            _token: '{{ csrf_token() }}',
            year: $('#filter-year').val() || '{{ $selectedYear }}',
            month: $('#util-filter-month').val(),
            no_nota: $('#util-no-nota').val(),
            title: $('#util-title').val(),
            guru_id: $('#util-guru-id').val(),
            bank_name: $('#util-bank-name').val(),
            account_number: $('#util-account-number').val(),
            planned_date: $('#util-planned-date').val(),
            receipts: receiptsPayload
        };

        const btn = $('#btn-save-util-group');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

        $.ajax({
            url: "{{ route('bos.panduan.store-utility-group') }}",
            type: 'POST',
            data: payload,
            success: function(res) {
                btn.prop('disabled', false).html('<i class="fas fa-check-circle mr-1"></i> Simpan Paket Rencana Belanja Utilitas');
                $('#modalBayarUtilitas').modal('hide');

                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: res.message,
                    timer: 2000,
                    showConfirmButton: false
                });

                tableUnrealized.draw();
                tableGroups.draw();
                $('#tab-groups-link').tab('show');
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fas fa-check-circle mr-1"></i> Simpan Paket Rencana Belanja Utilitas');
                const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Terjadi kesalahan saat menyimpan paket belanja utilitas.';
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Menyimpan',
                    text: msg
                });
            }
        });
    });
});
</script>
@stop