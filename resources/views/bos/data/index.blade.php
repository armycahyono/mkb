@extends('adminlte::page')

@php
    $selectedYear = $selectedYear ?? $activeFiscalYear ?? session('fiscal_year', date('Y'));
@endphp

@section('title', 'Data Kertas Kerja - BOS')

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap" style="padding: 2px 0;">
    <div>
        <h1 class="page-title"><i class="fas fa-table text-primary mr-1"></i> Data Kertas Kerja BOS</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-house"></i> Dashboard</a></li>
                <li class="breadcrumb-item"><i class="fas fa-calculator"></i> Penganggaran / BOS</li>
                <li class="breadcrumb-item active">Data Kertas Kerja</li>
            </ol>
        </nav>
    </div>
    <div class="header-actions">
        <a id="btn-export-excel" href="{{ route('bos.data.export', [], false) }}" class="btn btn-sm btn-outline-success mr-1">
            <i class="fas fa-file-excel mr-1"></i> Export Excel
        </a>
        <button class="btn btn-sm btn-info mr-1" data-bs-toggle="modal" data-bs-target="#importExcelModal">
            <i class="fas fa-file-upload mr-1"></i> Import Excel
        </button>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="fas fa-plus mr-1"></i> Tambah Item Rincian
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
    .font-tabular, #filter-total-display, #table-total-amount, td.text-right, td.font-weight-bold {
        font-variant-numeric: tabular-nums;
        font-feature-settings: "tnum";
    }

    /* Force Table Text Alignment */
    .text-right, .text-end, th.text-right, td.text-right, th.text-end, td.text-end,
    #dataTable td:nth-child(6), #dataTable th:nth-child(6),
    #dataTable td:nth-child(7), #dataTable th:nth-child(7),
    #table-total-amount {
        text-align: right !important;
    }
    .text-center, th.text-center, td.text-center,
    #dataTable td:nth-child(1), #dataTable th:nth-child(1),
    #dataTable td:nth-child(2), #dataTable th:nth-child(2),
    #dataTable td:nth-child(3), #dataTable th:nth-child(3),
    #dataTable td:nth-child(5), #dataTable th:nth-child(5),
    #dataTable td:nth-child(10), #dataTable th:nth-child(10) {
        text-align: center !important;
    }
    .text-left, .text-start, th.text-left, td.text-left, th.text-start, td.text-start,
    #dataTable td:nth-child(4), #dataTable th:nth-child(4),
    #dataTable td:nth-child(8), #dataTable th:nth-child(8),
    #dataTable td:nth-child(9), #dataTable th:nth-child(9) {
        text-align: left !important;
    }

    /* Rampingkan kolom data lain dan fokuskan lebar maksimal ke URAIAN */
    #dataTable th:nth-child(1), #dataTable td:nth-child(1),
    #dataTable th:nth-child(2), #dataTable td:nth-child(2),
    #dataTable th:nth-child(3), #dataTable td:nth-child(3),
    #dataTable th:nth-child(5), #dataTable td:nth-child(5),
    #dataTable th:nth-child(6), #dataTable td:nth-child(6),
    #dataTable th:nth-child(7), #dataTable td:nth-child(7),
    #dataTable th:nth-child(8), #dataTable td:nth-child(8),
    #dataTable th:nth-child(10), #dataTable td:nth-child(10) {
        white-space: nowrap !important;
    }
    #dataTable th:nth-child(4), #dataTable td:nth-child(4) {
        width: auto !important;
        min-width: 250px;
    }

    /* ── High-Density Data Table (13px Font, Compact Padding) ── */
    #dataTable {
        font-size: 0.8125rem !important; /* 13px */
        letter-spacing: -0.008em;
    }
    #dataTable th {
        font-size: 0.74rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 0.4rem 0.5rem !important;
        vertical-align: middle;
        background-color: #f8fafc;
        color: #475569;
    }
    #dataTable td {
        padding: 0.35rem 0.5rem !important;
        vertical-align: middle;
        line-height: 1.35;
    }
    #dataTable tfoot th {
        font-size: 0.8125rem !important;
        padding: 0.4rem 0.5rem !important;
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


    /* ── Teks Item Terserap Habis (Volume Habis, Jumlah Habis: Ungu Muda) ── */
    #dataTable tbody tr.row-exhausted > td,
    #dataTable tbody tr.row-exhausted td,
    #dataTable tbody tr.row-exhausted td .text-dark,
    #dataTable tbody tr.row-exhausted td .text-success,
    #dataTable tbody tr.row-exhausted td strong,
    #dataTable tbody tr.row-exhausted td span:not(.badge) {
        color: #8a58dc !important; /* Ungu Muda */
    }
    #dataTable tbody tr.row-exhausted td i.fa-lock {
        color: #8a58dc !important;
    }
</style>
@stop

@section('content')
    {{-- Card Tabel dengan Filter Terintegrasi di Header --}}
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center flex-wrap" style="gap: 8px; border-bottom: 1px solid #edf2f7;">
            <h3 class="card-title font-weight-bold text-dark mb-0" style="font-size: 0.92rem;">
                <i class="fas fa-list mr-1 text-primary"></i> Tabel Rincian Kertas Kerja per Bulan
            </h3>
            <div class="d-flex align-items-center flex-wrap ms-auto ml-auto" style="gap: 8px;">
                <input type="hidden" id="filter-year" value="{{ $selectedYear }}">
                <span class="badge bg-primary text-white font-weight-normal d-inline-flex align-items-center shadow-xs" style="height: 31px; font-size: 0.8125rem; padding: 0 10px; border-radius: 4px;" title="Tahun Anggaran aktif yang dipilih pada Navbar">
                    <i class="fas fa-calendar-alt me-1 mr-1"></i> Tahun Anggaran {{ $selectedYear }}
                </span>
                <div class="input-group input-group-sm shadow-xs" style="width: auto;">
                    <span class="input-group-text bg-light text-muted font-weight-bold" style="height: 31px; font-size: 0.8125rem; border-radius: 4px 0 0 4px;">
                        <i class="fas fa-calendar-check text-info me-1 mr-1"></i> Bulan:
                    </span>
                    <select id="filter-month" class="form-select form-select-sm font-weight-bold text-dark" style="height: 31px; font-size: 0.8125rem; border-radius: 0 4px 4px 0; cursor: pointer; width: auto; min-width: 130px;">
                        <option value="" {{ request()->has('month') && request('month') === '' ? 'selected' : '' }}>Semua Bulan</option>
                        @foreach(['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'] as $m)
                            <option value="{{ $m }}" {{ request('month', 'Januari') == $m ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="input-group input-group-sm shadow-xs" style="width: auto;">
                    <span class="input-group-text bg-light text-muted font-weight-bold" style="height: 31px; font-size: 0.8125rem; border-radius: 4px 0 0 4px;">
                        <i class="fas fa-code-branch text-info me-1 mr-1"></i> Versi:
                    </span>
                    <select id="filter-version" class="form-select form-select-sm font-weight-bold text-dark" style="height: 31px; font-size: 0.8125rem; border-radius: 0 4px 4px 0; cursor: pointer; width: auto; min-width: 150px;">
                        <option value="active" selected>Aktif (Default)</option>
                        <option value="archived">Arsip</option>
                        <option value="all">Semua Versi</option>
                    </select>
                </div>
                <div class="input-group input-group-sm shadow-xs" style="width: auto;">
                    <span class="input-group-text bg-light text-muted font-weight-bold" style="height: 31px; font-size: 0.8125rem; border-radius: 4px 0 0 4px;">
                        <i class="fas fa-search text-info me-1 mr-1"></i> Kegiatan:
                    </span>
                    <input type="text" id="filter-kegiatan" class="form-control form-control-sm text-dark font-weight-500" placeholder="Cari kegiatan..." style="height: 31px; font-size: 0.8125rem; border-radius: 0 4px 4px 0; width: 140px;">
                </div>
                <div class="bg-light border rounded shadow-xs d-inline-flex align-items-center" style="height: 31px; padding: 0 10px; border-radius: 4px;">
                    <small class="text-muted mr-1 font-weight-bold" style="font-size: 0.78rem;">Total Anggaran:</small>
                    <span id="filter-total-display" class="font-weight-bold text-success font-tabular" style="font-size: 0.8125rem;">Rp 0</span>
                </div>
                <div class="d-none d-md-flex align-items-center" style="gap: 4px;">
                    <span class="badge border bg-white shadow-xs" style="color: #8a58dc; font-size: 0.74rem; font-weight: 600; height: 31px; display: inline-flex; align-items: center; padding: 0 8px; border-radius: 4px;" title="Teks warna ungu muda menandakan volume dan alokasi anggaran bulan ini sudah terserap habis oleh Realisasi Belanja">
                        <i class="fas fa-lock mr-1" style="color: #8a58dc;"></i>Teks Ungu: Terserap Habis
                    </span>
                    <span class="badge border bg-white shadow-xs" style="color: #495057; font-size: 0.74rem; font-weight: 500; height: 31px; display: inline-flex; align-items: center; padding: 0 8px; border-radius: 4px;" title="Ikon gembok kuning menandakan anggaran sudah mulai dibelanjakan namun masih memiliki sisa volume/anggaran">
                        <i class="fas fa-lock text-warning mr-1"></i>Gembok: Ada Sisa
                    </span>
                </div>
            </div>
        </div>
        <div class="card-body p-2">
            <div class="table-responsive">
                <table class="table table-sm table-hover table-bordered table-striped w-100" id="dataTable">
                    <thead>
                        <tr>
                            <th width="45" class="text-center">ID</th>
                            <th width="65" class="text-center">BULAN</th>
                            <th width="70" class="text-center">PROGRAM</th>
                            <th class="text-left text-start">URAIAN</th>
                            <th width="75" class="text-center">VOL/SAT</th>
                            <th width="90" class="text-right text-end">HARGA (Rp)</th>
                            <th width="100" class="text-right text-end">JUMLAH (Rp)</th>
                            <th width="105" class="text-left text-start">KODE REKENING</th>
                            <th width="140" class="text-left text-start">KEGIATAN</th>
                            <th width="55" class="text-center">AKSI</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th colspan="6" class="text-right font-weight-bold">TOTAL ANGGARAN:</th>
                            <th id="table-total-amount" class="text-right text-primary font-weight-bold font-tabular">0</th>
                            <th colspan="3"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal Tambah Item --}}
    <div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <form id="createForm">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-light py-2 px-3 border-bottom">
                        <h5 class="modal-title font-weight-bold text-dark" style="font-size: 1rem;"><i class="fas fa-plus mr-2 text-success"></i>Tambah Data Kertas Kerja</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body py-3">
                        <div class="alert alert-info alert-dismissible fade show p-2 mb-3" style="font-size: 0.78rem;">
                            <i class="fas fa-circle-info mr-1"></i>
                            <strong>Petunjuk:</strong> Pilih file PDF yang terdaftar di <em>File Kertas Kerja</em>. Anda dapat menggunakan tombol <strong>Terjemahkan dari PDF</strong> untuk mengekstrak seluruh rincian tabel secara otomatis, atau mengisi form manual di bawah.
                        </div>

                        <div class="form-group bg-light p-2 rounded border mb-3">
                            <label class="text-primary font-weight-bold mb-1" style="font-size: 0.8rem;">File PDF Kertas Kerja Ref <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <select name="bos_kertas_kerja_file_id" id="select-file-id" class="form-control form-control-sm" required>
                                    <option value="">-- Pilih File Kertas Kerja PDF --</option>
                                    @foreach($files as $f)
                                        <option value="{{ $f->id }}" data-year="{{ $f->year }}" data-month="{{ $f->month }}">{{ $f->year }} - {{ $f->month }} ({{ $f->filename }} - {{ $f->version }})</option>
                                    @endforeach
                                </select>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-sm btn-primary" id="btnParseFile">
                                        <i class="fas fa-wand-magic-sparkles mr-1"></i> Terjemahkan dari PDF
                                    </button>
                                </div>
                            </div>
                        </div>

                        <h6 class="font-weight-bold text-secondary mb-2" style="font-size: 0.85rem;"><i class="fas fa-pen mr-1"></i> Form Input Rincian Manual</h6>

                        <div class="row">
                            <div class="col-md-4 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">ID ARKAS / No Urut</label>
                                <input type="text" name="arkas_id" class="form-control form-control-sm" placeholder="Contoh: 1, 2, 4.1">
                            </div>
                            <div class="col-md-4 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">Kode Rekening <span class="text-danger">*</span></label>
                                <input type="text" name="account_code" class="form-control form-control-sm" placeholder="5.1.02.01.01.0026">
                            </div>
                            <div class="col-md-4 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">Kode Program</label>
                                <input type="text" name="program_code" class="form-control form-control-sm" placeholder="03. 01. 01.">
                            </div>
                        </div>
                        <div class="form-group mb-2">
                            <label class="font-weight-bold" style="font-size: 0.8rem;">Kegiatan (Header Baris Kuning) <span class="text-danger">*</span></label>
                            <input type="text" name="kegiatan" class="form-control form-control-sm" placeholder="Pelaksanaan Pendaftaran Peserta Didik Baru (PPDB)">
                        </div>
                        <div class="form-group mb-2">
                            <label class="font-weight-bold" style="font-size: 0.8rem;">Uraian Belanja <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control form-control-sm" rows="2" placeholder="Detail uraian barang/jasa..."></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4 form-group mb-0">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">Volume <span class="text-danger">*</span></label>
                                <input type="number" step="1" min="1" name="volume" id="create-volume" class="form-control form-control-sm" value="1">
                            </div>
                            <div class="col-md-4 form-group mb-0">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">Satuan <span class="text-danger">*</span></label>
                                <input type="text" name="unit" class="form-control form-control-sm" placeholder="rim / buah / kotak">
                            </div>
                            <div class="col-md-4 form-group mb-0">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">Tarif Harga (Rp) <span class="text-danger">*</span></label>
                                <input type="text" inputmode="numeric" name="price" id="create-price" class="form-control form-control-sm font-tabular input-thousand" value="0" autocomplete="off">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-save mr-1"></i> Simpan Manual</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Edit Item --}}
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <form id="editForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit-id">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title font-weight-bold" style="font-size: 1rem;"><i class="fas fa-edit mr-2"></i>Edit Rincian Kertas Kerja</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body py-3">
                        <div class="row">
                            <div class="col-md-4 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">ID ARKAS / No Urut</label>
                                <input type="text" id="edit-arkas_id" name="arkas_id" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-4 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">Kode Rekening <span class="text-danger">*</span></label>
                                <input type="text" id="edit-account_code" name="account_code" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-4 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">Kode Program</label>
                                <input type="text" id="edit-program_code" name="program_code" class="form-control form-control-sm">
                            </div>
                        </div>
                        <div class="form-group mb-2">
                            <label class="font-weight-bold" style="font-size: 0.8rem;">Kegiatan <span class="text-danger">*</span></label>
                            <input type="text" id="edit-kegiatan" name="kegiatan" class="form-control form-control-sm" required>
                        </div>
                        <div class="form-group mb-2">
                            <label class="font-weight-bold" style="font-size: 0.8rem;">Uraian Belanja <span class="text-danger">*</span></label>
                            <textarea id="edit-description" name="description" class="form-control form-control-sm" rows="2" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4 form-group mb-0">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">Volume <span class="text-danger">*</span></label>
                                <input type="number" step="1" min="1" id="edit-volume" name="volume" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-4 form-group mb-0">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">Satuan <span class="text-danger">*</span></label>
                                <input type="text" id="edit-unit" name="unit" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-4 form-group mb-0">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">Tarif Harga (Rp) <span class="text-danger">*</span></label>
                                <input type="text" inputmode="numeric" id="edit-price" name="price" class="form-control form-control-sm font-tabular input-thousand" placeholder="0" autocomplete="off" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save mr-1"></i> Simpan Perubahan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Import Excel Modal -->
    <div class="modal fade" id="importExcelModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form id="importExcelForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-light py-2 px-3 border-bottom">
                        <h5 class="modal-title font-weight-bold text-dark" style="font-size: 1rem;"><i class="fas fa-file-excel mr-1 text-success"></i> Import Excel Data Kertas Kerja</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body py-3">
                        <p class="text-muted" style="font-size: 0.8rem;">Unggah file Excel (.xlsx/.xls) hasil penyesuaian/export untuk memperbarui data rincian kertas kerja secara otomatis.</p>
                        <div class="form-group mb-0">
                            <label class="font-weight-bold" style="font-size: 0.8rem;">File Excel Kertas Kerja <span class="text-danger">*</span></label>
                            <input type="file" name="excel_file" class="form-control-file" accept=".xlsx,.xls,.csv" required>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-info"><i class="fas fa-upload mr-1"></i> Upload & Proses</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop

@section('js')
<script>
$(document).ready(function() {
    const kertasKerjaFiles = @json($filesData);

    function updateVersionOptions(keepSelection = false) {
        let selectedYear = $('#filter-year').val();
        let selectedMonth = $('#filter-month').val();
        let prevVal = $('#filter-version').val();

        let matchingFiles = kertasKerjaFiles.filter(function(f) {
            let matchY = !selectedYear || f.year == selectedYear;
            let matchM = !selectedMonth || f.month.toLowerCase() === selectedMonth.toLowerCase();
            return matchY && matchM;
        });

        let optionsHtml = '';

        if (selectedMonth && matchingFiles.length > 0) {
            // Urutkan: Aktif paling atas, lalu ID terbesar (versi terbaru)
            matchingFiles.sort(function(a, b) {
                if (a.is_active && !b.is_active) return -1;
                if (!a.is_active && b.is_active) return 1;
                return b.id - a.id;
            });

            let hasActive = matchingFiles.some(f => f.is_active);
            matchingFiles.forEach(function(f, idx) {
                let statusText = f.is_active ? 'aktif (default)' : 'arsip';
                let label = `${f.version} ${statusText}`;
                let isSelected = (keepSelection && prevVal == f.id) || (!keepSelection && (f.is_active || (!hasActive && idx === 0))) ? 'selected' : '';
                optionsHtml += `<option value="${f.id}" ${isSelected}>${label}</option>`;
            });

            let allSelected = (keepSelection && prevVal === 'all') ? 'selected' : '';
            optionsHtml += `<option value="all" ${allSelected}>Semua Versi</option>`;
        } else {
            // Jika memilih "Semua Bulan" atau belum ada file
            optionsHtml += `<option value="active" selected>Aktif (Default)</option>`;
            optionsHtml += `<option value="archived">Arsip</option>`;
            optionsHtml += `<option value="all">Semua Versi</option>`;
        }

        $('#filter-version').html(optionsHtml);
    }

    // Inisialisasi opsi versi dinamis sebelum DataTable dimuat
    updateVersionOptions();

    let table = $('#dataTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 50,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
        order: [],
        ajax: {
            url: "{{ route('bos.data.index', [], false) }}",
            data: function(d) {
                d.year = $('#filter-year').val();
                d.month = $('#filter-month').val();
                d.version_filter = $('#filter-version').val();
                d.kegiatan = $('#filter-kegiatan').val();
            },
            error: function(xhr, error, thrown) {
                $('#dataTable_processing').hide();
                console.error('DataTables Load Error:', xhr.status, error, thrown);
                let statusMsg = xhr.status ? ('Status: ' + xhr.status) : (error === 'timeout' ? 'Status: Timeout' : 'Koneksi Terputus / CORS');
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Memuat Data',
                    text: 'Terjadi kendala saat memuat data tabel dari server (' + statusMsg + '). Silakan coba segarkan halaman.',
                    confirmButtonText: 'Muat Ulang Halaman',
                    showCancelButton: true,
                    cancelButtonText: 'Tutup'
                }).then(function(res) {
                    if (res.isConfirmed) {
                        location.reload();
                    }
                });
            }
        },
        columns: [
            {data: 'arkas_id_display', name: 'arkas_id', className: 'text-center font-tabular text-nowrap'},
            {data: 'month', name: 'month', className: 'text-center text-nowrap'},
            {data: 'program_code', name: 'program_code', defaultContent: '-', className: 'text-center font-tabular text-nowrap'},
            {data: 'description', name: 'description', className: 'font-weight-500 text-dark text-start text-left'},
            {data: 'volume_display', name: 'volume', className: 'font-tabular text-center text-nowrap'},
            {data: 'price_display', name: 'price', className: 'font-tabular text-right text-end text-nowrap'},
            {data: 'total_amount_display', name: 'total_amount', className: 'font-tabular font-weight-bold text-success text-right text-end text-nowrap'},
            {data: 'account_code', name: 'account_code', className: 'font-tabular text-start text-left text-nowrap'},
            {data: 'kegiatan_display', name: 'kegiatan', className: 'text-start text-left'},
            {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center text-nowrap'}
        ],
        createdRow: function(row, data, dataIndex) {
            if (data.is_exhausted) {
                $(row).addClass('row-exhausted');
            }
        },
        footerCallback: function(row, data, start, end, display) {
            let api = this.api();
            let total = api.column(6, {page: 'current'}).data().reduce(function(a, b) {
                let valA = typeof a === 'string' ? parseFloat(a.replace(/[^\d]/g, '')) || 0 : a;
                let valB = typeof b === 'string' ? parseFloat(b.replace(/[^\d]/g, '')) || 0 : b;
                return valA + valB;
            }, 0);
            $('#table-total-amount').html(new Intl.NumberFormat('id-ID').format(total));
        },
        drawCallback: function(settings) {
            $('[data-bs-toggle="tooltip"]').tooltip();
            if (settings.json && settings.json.grandTotal) {
                $('#filter-total-display').html(settings.json.grandTotal);
            }
        }
    });

    function updateExportUrl() {
        let year = $('#filter-year').val();
        let month = $('#filter-month').val();
        let version = $('#filter-version').val();
        let baseUrl = "{{ route('bos.data.export', [], false) }}";
        let params = [];
        if (year) params.push('year=' + encodeURIComponent(year));
        if (month) params.push('month=' + encodeURIComponent(month));
        if (version) params.push('version_filter=' + encodeURIComponent(version));
        $('#btn-export-excel').attr('href', baseUrl + (params.length ? '?' + params.join('&') : ''));
    }

    updateExportUrl();

    $.fn.dataTable.ext.errMode = 'none';

    let searchTimeout = null;

    $('#filter-year, #filter-month').on('change', function() {
        updateVersionOptions();
        updateExportUrl();
        table.draw();
    });

    $('#filter-version').on('change', function() {
        updateExportUrl();
        table.draw();
    });

    $('#filter-kegiatan').on('keyup input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            updateExportUrl();
            table.draw();
        }, 400);
    });

    $('#importExcelForm').submit(function(e) {
        e.preventDefault();
        let formData = new FormData(this);

        Swal.fire({
            title: 'Mengunggah & Memproses Excel...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: "{{ route('bos.data.import', [], false) }}",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            success: function(res) {
                $('#importExcelModal').modal('hide');
                $('#importExcelForm')[0].reset();
                table.draw(false);
                Swal.fire('Berhasil!', res.message, 'success');
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON ? xhr.responseJSON.message : 'Gagal mengunggah data Excel.', 'error');
            }
        });
    });

    $('#btnParseFile').click(function() {
        let fileId = $('#select-file-id').val();
        if (!fileId) {
            Swal.fire('Perhatian', 'Silakan pilih File PDF Kertas Kerja terlebih dahulu.', 'warning');
            return;
        }

        let selectedOption = $('#select-file-id option:selected');
        let fileYear = selectedOption.data('year');
        let fileMonth = selectedOption.data('month');

        Swal.fire({
            title: 'Menerjemahkan PDF...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: "{{ route('bos.data.store', [], false) }}",
            type: "POST",
            data: {
                _token: '{{ csrf_token() }}',
                action: 'parse_file',
                bos_kertas_kerja_file_id: fileId
            },
            success: function(res) {
                $('#createModal').modal('hide');
                if (fileYear) $('#filter-year').val(fileYear);
                if (fileMonth) $('#filter-month').val(fileMonth);
                updateVersionOptions();
                updateExportUrl();
                table.draw();
                Swal.fire('Berhasil Ditambahkan!', res.message, 'success');
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON ? xhr.responseJSON.message : 'Gagal menerjemahkan file PDF.', 'error');
            }
        });
    });

    function formatThousand(val) {
        if (val === null || val === undefined || val === '') return '';
        let clean = String(val).replace(/\D/g, '');
        if (!clean) return '';
        return new Intl.NumberFormat('id-ID').format(clean);
    }

    $(document).on('input', '.input-thousand', function() {
        let val = $(this).val();
        let num = val.replace(/\D/g, '');
        $(this).val(num ? new Intl.NumberFormat('id-ID').format(num) : '');
    });

    $('#createForm').submit(function(e) {
        e.preventDefault();
        let data = $(this).serializeArray();
        data.forEach(function(item) {
            if (item.name === 'price') {
                item.value = String(item.value).replace(/\./g, '');
            }
        });

        $.ajax({
            url: "{{ route('bos.data.store', [], false) }}",
            type: "POST",
            data: $.param(data),
            success: function(res) {
                $('#createModal').modal('hide');
                $('#createForm')[0].reset();
                table.draw(false);
                Swal.fire('Berhasil', res.message, 'success');
            },
            error: function(xhr) {
                Swal.fire('Error', 'Gagal menyimpan data.', 'error');
            }
        });
    });

    $(document).on('click', '.btn-edit', function() {
        let json = $(this).data('json');
        $('#edit-id').val(json.id);
        $('#edit-arkas_id').val(json.arkas_id);
        $('#edit-account_code').val(json.account_code);
        $('#edit-program_code').val(json.program_code);
        $('#edit-kegiatan').val(json.kegiatan);
        $('#edit-description').val(json.description);
        $('#edit-volume').val(json.volume);
        $('#edit-unit').val(json.unit);
        $('#edit-price').val(formatThousand(json.price));
        $('#editModal').modal('show');
    });

    $('#editForm').submit(function(e) {
        e.preventDefault();
        let id = $('#edit-id').val();
        let data = $(this).serializeArray();
        data.forEach(function(item) {
            if (item.name === 'price') {
                item.value = String(item.value).replace(/\./g, '');
            }
        });

        $.ajax({
            url: "{{ url('bos/data') }}/" + id,
            type: "PUT",
            data: $.param(data),
            success: function(res) {
                $('#editModal').modal('hide');
                table.draw(false);
                Swal.fire('Berhasil', res.message, 'success');
            },
            error: function(xhr) {
                Swal.fire('Error', 'Gagal memperbarui rincian.', 'error');
            }
        });
    });

    $(document).on('click', '.btn-delete', function(e) {
        e.preventDefault();
        let url = $(this).data('url');
        Swal.fire({
            title: 'Hapus Rincian Data Kertas Kerja ini?',
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
                        table.draw(false);
                        Swal.fire('Terhapus!', res.message, 'success');
                    }
                });
            }
        });
    });
});
</script>
@stop