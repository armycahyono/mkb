@extends('adminlte::page')

@php
    $selectedYear = $selectedYear ?? $activeFiscalYear ?? session('fiscal_year', date('Y'));
@endphp

@section('title', 'Realisasi BOS')

@section('plugins.Datatables', true)
@section('plugins.Select2', true)
@section('plugins.Sweetalert2', true)

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap" style="padding: 2px 0;">
    <div>
        <h1 class="page-title"><i class="fas fa-receipt text-success mr-1"></i> Realisasi Belanja BOS</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-house"></i> Dashboard</a></li>
                <li class="breadcrumb-item"><i class="fas fa-calculator"></i> Penganggaran / BOS</li>
                <li class="breadcrumb-item active">Realisasi BOS</li>
            </ol>
        </nav>
    </div>
    <div class="header-actions">
        <button class="btn btn-sm btn-primary mr-1" data-bs-toggle="modal" data-bs-target="#modalCreatePenerimaan">
            <i class="fas fa-hand-holding-usd mr-1"></i> Catat Penerimaan Dana
        </button>
        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="fas fa-plus mr-1"></i> Tambah Realisasi Belanja
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
    .font-tabular, .stat-value, #filter-total-display, #table-total-amount, td.text-right, td.font-weight-bold {
        font-variant-numeric: tabular-nums;
        font-feature-settings: "tnum";
    }

    ::placeholder, .form-control::placeholder {
        color: #94a3b8 !important;
        opacity: 0.75 !important;
        font-weight: 400 !important;
    }

    /* Force Table Text Alignment */
    .text-right, .text-end, td.text-right, td.text-end,
    #dataTable td:nth-child(9),
    #dataTable td:nth-child(10),
    #table-total-amount {
        text-align: right !important;
    }
    .text-center, td.text-center {
        text-align: center !important;
    }
    .text-left, .text-start, td.text-left, td.text-start {
        text-align: left !important;
    }
    #dataTable thead th, #tableNotaItems thead th {
        text-align: center !important;
        vertical-align: middle !important;
    }

    /* ── Ultra-Sleek White Metric Strip (Compact & High-Density) ── */
    .stat-card-white {
        background: #ffffff;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        margin-bottom: 0.5rem;
        position: relative;
        overflow: hidden;
        height: 100%;
    }
    .stat-card-white:hover {
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.07);
    }
    .stat-card-white .card-body {
        padding: 0.45rem 0.75rem 0.4rem 0.75rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        height: 100%;
    }
    .stat-card-white .stat-label {
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: #64748b;
        margin-bottom: 0.1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .stat-card-white .stat-value {
        font-size: 1.12rem;
        font-weight: 700;
        line-height: 1.2;
        margin-bottom: 0.1rem;
        letter-spacing: -0.02em;
    }
    .stat-card-white .stat-subtext {
        font-size: 0.68rem;
        color: #94a3b8;
        margin-bottom: 0;
        line-height: 1.15;
    }

    /* Left accent borders */
    .border-left-primary { border-left: 3.5px solid #007bff !important; }
    .border-left-success { border-left: 3.5px solid #28a745 !important; }
    .border-left-warning { border-left: 3.5px solid #ffc107 !important; }
    .border-left-danger  { border-left: 3.5px solid #dc3545 !important; }
    .border-left-purple  { border-left: 3.5px solid #6f42c1 !important; }

    .text-purple { color: #6f42c1 !important; }
    .bg-purple { background-color: #6f42c1 !important; }

    /* ── Data Table Styling: Header & Footer Normal, Khusus Isi (tbody) Dikecilkan ── */
    #dataTable {
        letter-spacing: -0.01em;
    }
    #dataTable thead th {
        font-size: 0.78rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 0.42rem 0.5rem !important;
        vertical-align: middle;
        background-color: #f8fafc;
        color: #475569;
        white-space: nowrap;
    }
    #dataTable tfoot th {
        font-size: 0.8125rem !important;
        padding: 0.42rem 0.5rem !important;
    }

    /* KHUSUS ISI TABEL (tbody): Font diperkecil termasuk badge dan tombol di dalamnya */
    #dataTable tbody td {
        font-size: 0.72rem !important; /* ~11.5px */
        padding: 0.28rem 0.45rem !important;
        vertical-align: middle;
        line-height: 1.3;
    }
    #dataTable tbody .badge {
        font-size: 0.65rem !important;
        padding: 0.15rem 0.32rem !important;
        font-weight: 600;
    }
    #dataTable tbody .btn-xs {
        font-size: 0.67rem !important;
        padding: 0.08rem 0.32rem !important;
        line-height: 1.25;
    }
    #dataTable tbody .inline-edit {
        font-size: 0.72rem !important;
        cursor: pointer;
        display: inline-block;
        padding: 0 2px;
        border-radius: 3px;
        transition: background-color 0.15s ease;
    }
    #dataTable tbody .inline-edit:hover {
        background-color: #e8f4ff;
    }
    #dataTable tbody .inline-input {
        min-width: 75px;
        display: inline-block;
        height: 24px;
        padding: 0.06rem 0.35rem;
        font-size: 0.72rem !important;
    }

    /* Controls DataTables Normal */
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        font-size: 0.78rem !important;
    }
    .dataTables_wrapper .dataTables_info {
        font-size: 0.74rem !important;
        color: #64748b !important;
        padding-top: 0.35rem !important;
    }

    /* ── Select2 Normal Compact for Modal Dropdown ── */
    body .select2-container,
    body .select2-container .select2-selection,
    body .select2-container .select2-selection *,
    body .select2-dropdown,
    body .select2-dropdown *,
    body .select2-results__option,
    body .select2-results__option *,
    #createModal .select2-container *,
    #createModal .select2-dropdown * {
        font-size: 0.78rem !important;
    }
    body .select2-container .select2-selection--single {
        height: 32px !important;
    }
    body .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 30px !important;
        padding-left: 6px !important;
    }
    body .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 30px !important;
    }
    body .select2-results__option {
        padding: 4px 8px !important;
        line-height: 1.35 !important;
    }
    body .select2-search--dropdown .select2-search__field {
        font-size: 0.78rem !important;
        padding: 3px 6px !important;
        height: 28px !important;
    }
</style>
@stop

@section('content')
    {{-- Financial Summary Dashboard (Clean White Theme, Compact & Slim) --}}
    <div class="row mb-1" id="summary-dashboard">
        <div class="col-lg-3 col-6 mb-2">
            <div class="card stat-card-white border-left-primary">
                <div class="card-body">
                    <div>
                        <div class="stat-label">
                            <span>Pagu Anggaran</span>
                            <span id="summary-year-label" class="badge badge-light border text-muted px-1 py-0" style="font-size: 0.68rem; border-radius: 4px;">{{ date('Y') }}</span>
                        </div>
                        <div id="summary-anggaran" class="stat-value text-primary font-tabular">Rp 0</div>
                    </div>
                    <p class="stat-subtext text-truncate">Sisa Pagu: <span id="summary-saldo" class="font-weight-bold text-dark font-tabular">Rp 0</span></p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6 mb-2">
            <div class="card stat-card-white border-left-info" style="border-left: 3.5px solid #17a2b8 !important;">
                <div class="card-body">
                    <div>
                        <div class="stat-label">
                            <span class="text-info font-weight-bold">Dana Masuk (Cair)</span>
                            <span id="summary-year-label2" class="badge badge-light border text-muted px-1 py-0" style="font-size: 0.68rem; border-radius: 4px;">{{ date('Y') }}</span>
                        </div>
                        <div id="summary-dana-masuk" class="stat-value text-info font-tabular">Rp 0</div>
                    </div>
                    <p class="stat-subtext">Penyaluran BOS & Penerimaan</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6 mb-2">
            <div class="card stat-card-white border-left-purple" id="summary-pct-box">
                <div class="card-body">
                    <div>
                        <div class="stat-label">
                            <span>Total Belanja</span>
                            <span id="summary-year-label3" class="badge badge-light border text-muted px-1 py-0" style="font-size: 0.68rem; border-radius: 4px;">{{ date('Y') }}</span>
                        </div>
                        <div id="summary-realisasi" class="stat-value text-purple font-tabular">Rp 0</div>
                    </div>
                    <div>
                        <div class="progress bg-light" style="height: 4px; border-radius: 3px; margin-bottom: 0.15rem;">
                            <div id="summary-progress-bar" class="progress-bar bg-purple" role="progressbar" style="width: 0%; border-radius: 3px;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <p class="stat-subtext">Penyerapan Pagu: <span id="summary-percentage" class="font-weight-bold">0</span>%</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6 mb-2">
            <div class="card stat-card-white border-left-success" id="summary-saldo-bank-card">
                <div class="card-body">
                    <div>
                        <div class="stat-label">
                            <span>Saldo Rekening Bank</span>
                            <span id="summary-year-label4" class="badge badge-light border text-muted px-1 py-0" style="font-size: 0.68rem; border-radius: 4px;">{{ date('Y') }}</span>
                        </div>
                        <div id="summary-saldo-bank" class="stat-value text-success font-tabular">Rp 0</div>
                    </div>
                    <p class="stat-subtext">Kroscek Buku Kas & Rek. Koran</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabel Realisasi Belanja dengan Filter Terintegrasi di Header --}}
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center flex-wrap" style="gap: 8px; border-bottom: 1px solid #edf2f7;">
            <h3 class="card-title font-weight-bold text-dark mb-0" style="font-size: 0.92rem;">
                <i class="fas fa-list-check text-success mr-1"></i> Daftar Transaksi Realisasi Belanja
            </h3>
            <div class="d-flex align-items-center flex-wrap ms-auto ml-auto" style="gap: 8px;">
                <input type="hidden" id="filter-year" value="{{ $selectedYear }}">
                <span class="badge bg-primary text-white font-weight-normal d-inline-flex align-items-center shadow-xs" style="height: 31px; font-size: 0.8125rem; padding: 0 10px; border-radius: 4px;" title="Tahun Anggaran aktif yang dipilih pada Navbar">
                    <i class="fas fa-calendar-alt me-1 mr-1"></i> Tahun Anggaran {{ $selectedYear }}
                </span>
                <div class="input-group input-group-sm shadow-xs" style="width: auto;">
                    <span class="input-group-text bg-light text-muted font-weight-bold" style="height: 31px; font-size: 0.8125rem; border-radius: 4px 0 0 4px;" title="Filter berdasarkan Bulan Realisasi Transaksi">
                        <i class="fas fa-calendar-check text-info me-1 mr-1"></i> Bulan Realisasi:
                    </span>
                    <select id="filter-month" class="form-select form-select-sm font-weight-bold text-dark" style="height: 31px; font-size: 0.8125rem; border-radius: 0 4px 4px 0; cursor: pointer; width: auto; min-width: 130px;">
                        <option value="">Semua Bulan</option>
                        @foreach(['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'] as $m)
                            <option value="{{ $m }}">{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="bg-light border rounded shadow-xs d-inline-flex align-items-center" style="height: 31px; padding: 0 10px; border-radius: 4px;">
                    <small class="text-muted mr-1 font-weight-bold" style="font-size: 0.73rem;">Total Filter:</small>
                    <span id="filter-total-display" class="font-weight-bold text-success font-tabular" style="font-size: 0.75rem;">Rp 0</span>
                </div>
            </div>
        </div>
        <div class="card-body p-2">
            <div class="table-responsive">
                <table class="table table-sm table-hover table-bordered table-striped w-100" id="dataTable">
                    <thead>
                        <tr>
                            <th width="85" class="text-center text-nowrap">NO. NOTA</th>
                            <th width="65" class="text-center text-nowrap">NO BPU</th>
                            <th width="75" class="text-center text-nowrap">TGL BKU</th>
                            <th width="75" class="text-center text-nowrap">TGL NOTA</th>
                            <th width="65" class="text-center" title="Bulan Realisasi Transaksi Belanja">BULAN</th>
                            <th style="min-width: 350px;" class="text-center">URAIAN / BARANG</th>
                            <th width="50" class="text-center text-nowrap" title="Volume & Satuan">VOL.</th>
                            <th width="80" class="text-center text-nowrap">HARGA</th>
                            <th width="85" class="text-center text-nowrap">TOTAL</th>
                            <th width="95" class="text-center text-nowrap">SALDO BANK</th>
                            <th width="165" class="text-center">KEGIATAN & KODE REK</th>
                            <th width="65" class="text-center">AKSI</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th colspan="8" class="text-right font-weight-bold">Total Belanja Halaman Ini:</th>
                            <th id="table-total-amount" class="text-right font-weight-bold text-success font-tabular">Rp 0</th>
                            <th colspan="3"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Catat Penerimaan Dana BOS -->
    <div class="modal fade" id="modalCreatePenerimaan" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <form id="createPenerimaanForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-primary text-white py-2 px-3">
                        <h5 class="modal-title font-weight-bold" style="font-size: 1rem;">
                            <i class="fas fa-hand-holding-usd mr-2"></i>Catat Penerimaan Dana BOS (Dana Masuk)
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body py-3">
                        <div class="row">
                            <div class="col-md-6 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">Tanggal Cair / Masuk Rekening <span class="text-danger">*</span></label>
                                <input type="date" name="transaction_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">Jenis Penerimaan <span class="text-danger">*</span></label>
                                <select name="category" class="form-control form-control-sm" required>
                                    <option value="salur_tahap_1">Penyaluran BOSP Tahap 1</option>
                                    <option value="salur_tahap_2">Penyaluran BOSP Tahap 2</option>
                                    <option value="jasa_giro">Pendapatan Bunga Bank (Jasa Giro)</option>
                                    <option value="silpa">Sisa SILPA Tahun Lalu</option>
                                    <option value="lainnya">Penerimaan Lainnya</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">No. Referensi / No. Transaksi Bank <small class="text-muted">(Opsional)</small></label>
                                <input type="text" name="reference_number" class="form-control form-control-sm" placeholder="Contoh: TRF-BOS-01 / REF12345">
                            </div>
                            <div class="col-md-6 form-group mb-2">
                                <label class="font-weight-bold text-success" style="font-size: 0.8rem;"><i class="fas fa-coins mr-1"></i>Jumlah Dana Masuk (Rp) <span class="text-danger">*</span></label>
                                <input type="text" inputmode="numeric" name="amount" class="form-control form-control-sm font-tabular input-thousand font-weight-bold text-success" placeholder="0" autocomplete="off" required>
                            </div>
                        </div>

                        <div class="form-group mb-2">
                            <label class="font-weight-bold" style="font-size: 0.8rem;">Uraian Penerimaan <span class="text-danger">*</span></label>
                            <input type="text" name="description" class="form-control form-control-sm" placeholder="Contoh: Penyaluran Dana BOS Reguler Tahap 1 Gelombang 1" required>
                        </div>

                        <div class="form-group mb-2">
                            <label class="font-weight-bold" style="font-size: 0.8rem;">Keterangan / Catatan Tambahan <small class="text-muted">(Opsional)</small></label>
                            <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Catatan tambahan bila ada..."></textarea>
                        </div>

                        <div class="form-group mb-0">
                            <label class="font-weight-bold" style="font-size: 0.8rem;">Upload Bukti / Rekening Koran <small class="text-muted">(PDF / JPG / PNG, Max 5MB)</small></label>
                            <input type="file" name="receipt_file" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-primary" id="btn-save-penerimaan">
                            <i class="fas fa-save mr-1"></i> Simpan Penerimaan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Penerimaan Dana BOS -->
    <div class="modal fade" id="modalEditPenerimaan" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <form id="editPenerimaanForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="edit-penerimaan-id">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-warning text-dark py-2 px-3">
                        <h5 class="modal-title font-weight-bold" style="font-size: 1rem;">
                            <i class="fas fa-edit mr-2"></i>Edit Penerimaan Dana BOS
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body py-3">
                        <div class="row">
                            <div class="col-md-6 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">Tanggal Cair / Masuk Rekening <span class="text-danger">*</span></label>
                                <input type="date" id="edit-penerimaan-date" name="transaction_date" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-6 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">Jenis Penerimaan <span class="text-danger">*</span></label>
                                <select id="edit-penerimaan-category" name="category" class="form-control form-control-sm" required>
                                    <option value="salur_tahap_1">Penyaluran BOSP Tahap 1</option>
                                    <option value="salur_tahap_2">Penyaluran BOSP Tahap 2</option>
                                    <option value="jasa_giro">Pendapatan Bunga Bank (Jasa Giro)</option>
                                    <option value="silpa">Sisa SILPA Tahun Lalu</option>
                                    <option value="lainnya">Penerimaan Lainnya</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">No. Referensi / No. Transaksi Bank <small class="text-muted">(Opsional)</small></label>
                                <input type="text" id="edit-penerimaan-ref" name="reference_number" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-6 form-group mb-2">
                                <label class="font-weight-bold text-success" style="font-size: 0.8rem;"><i class="fas fa-coins mr-1"></i>Jumlah Dana Masuk (Rp) <span class="text-danger">*</span></label>
                                <input type="text" inputmode="numeric" id="edit-penerimaan-amount" name="amount" class="form-control form-control-sm font-tabular input-thousand font-weight-bold text-success" required>
                            </div>
                        </div>

                        <div class="form-group mb-2">
                            <label class="font-weight-bold" style="font-size: 0.8rem;">Uraian Penerimaan <span class="text-danger">*</span></label>
                            <input type="text" id="edit-penerimaan-desc" name="description" class="form-control form-control-sm" required>
                        </div>

                        <div class="form-group mb-2">
                            <label class="font-weight-bold" style="font-size: 0.8rem;">Keterangan / Catatan Tambahan <small class="text-muted">(Opsional)</small></label>
                            <textarea id="edit-penerimaan-notes" name="notes" class="form-control form-control-sm" rows="2"></textarea>
                        </div>

                        <div class="form-group mb-0">
                            <label class="font-weight-bold" style="font-size: 0.8rem;">Ganti Bukti / Rekening Koran <small class="text-muted">(Kosongkan jika tidak diubah)</small></label>
                            <input type="file" name="receipt_file" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                            <div id="edit-penerimaan-file-preview" class="mt-1 d-none">
                                <a href="#" id="edit-penerimaan-file-link" target="_blank" class="btn btn-xs btn-outline-info"><i class="fas fa-file-alt mr-1"></i>Lihat Bukti Saat Ini</a>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-warning" id="btn-update-penerimaan">
                            <i class="fas fa-save mr-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Create Realisasi -->
    <div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document" style="max-width: 1020px;">
            <form id="createForm">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-light py-2 px-3 border-bottom">
                        <h5 class="modal-title font-weight-bold text-dark" style="font-size: 1rem;"><i class="fas fa-plus mr-2 text-success"></i>Tambah Transaksi Realisasi Belanja</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body py-3">
                        <div class="row">
                            <div class="col-md-4 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">Tahun Realisasi <span class="text-danger">*</span></label>
                                <input type="number" id="create-year" name="year" class="form-control form-control-sm" value="{{ $selectedYear }}" required>
                            </div>
                            <div class="col-md-4 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">Bulan Realisasi <span class="text-danger">*</span></label>
                                <select id="create-month" name="month" class="form-control form-control-sm" required>
                                    @foreach(['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'] as $m)
                                        <option value="{{ $m }}" {{ $m == date('F') ? 'selected' : '' }}>{{ $m }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">No Transaksi / BPU <small class="text-muted">(Opsional)</small></label>
                                <input type="text" name="no_transaksi" class="form-control form-control-sm" placeholder="Contoh: BPU001">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">Tgl Transaksi (BKU) <span class="text-danger">*</span></label>
                                <input type="date" name="transaction_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">Tgl Nota / Bukti</label>
                                <input type="date" name="receipt_date" class="form-control form-control-sm">
                            </div>
                        </div>

                        <div class="form-group bg-light p-2 border rounded mb-2">
                            <label class="font-weight-bold text-primary mb-1" style="font-size: 0.8rem;"><i class="fas fa-box-open mr-1"></i>Pilih Objek Belanja Kertas Kerja <span class="text-danger">*</span></label>
                            <small class="form-text text-muted mb-2 d-block" style="font-size: 0.72rem;">Menampilkan barang yang dianggarkan dari <strong>Januari s/d Bulan Realisasi</strong> terpilih yang masih bersisa.</small>
                            <select id="create-item-id" name="bos_kertas_kerja_item_id" class="form-control select2-custom" style="width: 100%;" required>
                                <option value="">-- Pilih Objek Belanja --</option>
                            </select>
                        </div>

                        {{-- Card Detail Info Barang Terpilih --}}
                        <div id="create-item-detail" class="p-2 px-3 mb-2 rounded border bg-light d-none" style="font-size: 0.78rem; border-left: 4px solid #17a2b8 !important;">
                            <div class="text-dark mb-1">
                                <span class="text-muted font-weight-bold">Kegiatan:</span> <span id="detail-kegiatan" class="font-weight-bold text-dark">-</span>
                            </div>
                            <div class="text-dark mb-1">
                                <span class="text-muted font-weight-bold">Kode Rek:</span> <span id="detail-account" class="font-weight-bold text-secondary">-</span>
                            </div>
                            <div class="text-dark mb-2">
                                <span class="text-muted font-weight-bold">Uraian:</span> <span id="detail-desc" class="text-primary font-weight-bold" style="font-size: 0.84rem;">-</span>
                            </div>
                            <div class="d-flex align-items-center flex-wrap pt-1 border-top" style="gap: 12px;">
                                <div>
                                    <span class="text-muted font-weight-bold mr-1">Anggaran Awal:</span>
                                    <span id="detail-budget-vol" class="badge badge-secondary font-tabular px-2 py-1" style="font-size: 0.75rem;">-</span>
                                </div>
                                <div>
                                    <span class="text-success font-weight-bold mr-1">Sisa Volume Kertas Kerja:</span>
                                    <span id="detail-remaining-vol" class="badge badge-success font-weight-bold font-tabular px-2 py-1" style="font-size: 0.8rem;">-</span>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">Volume Realisasi <span class="text-danger">*</span></label>
                                <input type="number" step="1" min="1" id="create-volume" name="volume" class="form-control form-control-sm" placeholder="Contoh: 1 / 20" required>
                            </div>
                            <div class="col-md-3 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">Satuan <span class="text-danger">*</span></label>
                                <input type="text" id="create-unit" name="unit" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">Harga Satuan (Rp) <span class="text-danger">*</span></label>
                                <input type="text" inputmode="numeric" id="create-price" name="price" class="form-control form-control-sm font-tabular input-thousand" placeholder="0" autocomplete="off" required>
                            </div>
                            <div class="col-md-3 form-group mb-2">
                                <label class="text-success font-weight-bold" style="font-size: 0.8rem;"><i class="fas fa-calculator mr-1"></i>Total Belanja (Rp)</label>
                                <input type="text" id="create-total-display" class="form-control form-control-sm font-weight-bold text-success bg-light font-tabular" readonly value="Rp 0">
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <label class="font-weight-bold" style="font-size: 0.8rem;">Keterangan / Catatan Transaksi</label>
                            <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Catatan opsional..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-save mr-1"></i> Simpan Realisasi</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Realisasi -->
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document" style="max-width: 1020px;">
            <form id="editForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit-id">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title font-weight-bold" style="font-size: 1rem;"><i class="fas fa-edit mr-2"></i>Edit Transaksi Realisasi Belanja</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body py-3">
                        <div class="row">
                            <div class="col-md-6 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">No Transaksi / BPU <small class="text-muted">(Opsional)</small></label>
                                <input type="text" id="edit-no_transaksi" name="no_transaksi" class="form-control form-control-sm" placeholder="Contoh: BPU001">
                            </div>
                            <div class="col-md-3 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">Tgl BKU <span class="text-danger">*</span></label>
                                <input type="date" id="edit-transaction_date" name="transaction_date" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">Tgl Nota</label>
                                <input type="date" id="edit-receipt_date" name="receipt_date" class="form-control form-control-sm">
                            </div>
                        </div>

                        <div class="form-group bg-light p-2 border rounded mb-2" style="border-left: 4px solid #17a2b8 !important;">
                            <div class="text-dark mb-1" style="font-size: 0.8rem;">
                                <span class="text-muted font-weight-bold">Kegiatan:</span> <span id="edit-item-kegiatan" class="font-weight-bold text-dark">-</span>
                            </div>
                            <div style="font-size: 0.82rem;">
                                <span class="text-muted font-weight-bold">Objek Belanja:</span> <span id="edit-item-title" class="text-primary font-weight-bold">-</span>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">Volume Realisasi <span class="text-danger">*</span></label>
                                <input type="number" step="any" min="0.01" id="edit-volume" name="volume" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">Satuan <span class="text-danger">*</span></label>
                                <input type="text" id="edit-unit" name="unit" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">Harga Satuan (Rp) <span class="text-danger">*</span></label>
                                <input type="text" inputmode="numeric" id="edit-price" name="price" class="form-control form-control-sm font-tabular input-thousand" placeholder="0" autocomplete="off" required>
                            </div>
                            <div class="col-md-3 form-group mb-2">
                                <label class="text-warning font-weight-bold" style="font-size: 0.8rem;"><i class="fas fa-calculator mr-1"></i>Total Belanja (Rp)</label>
                                <input type="text" id="edit-total-display" class="form-control form-control-sm font-weight-bold text-dark bg-light font-tabular" readonly value="Rp 0">
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <label class="font-weight-bold" style="font-size: 0.8rem;">Keterangan / Catatan</label>
                            <textarea id="edit-notes" name="notes" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-warning"><i class="fas fa-save mr-1"></i> Simpan Perubahan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Rincian Nota & Update Massal Transaksi -->
    <div class="modal fade" id="modalNotaBulkEdit" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document" style="max-width: 1050px;">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light py-2 px-3 border-bottom">
                    <h5 class="modal-title font-weight-bold text-dark" style="font-size: 0.95rem;">
                        <i class="fas fa-receipt mr-2 text-info"></i>Rincian Nota & Update Massal Transaksi
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3 px-3">
                    <!-- Info Nota -->
                    <div class="card bg-light border-0 mb-3 shadow-none">
                        <div class="card-body p-3">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center mb-1">
                                        <span class="badge bg-primary px-2 py-1 mr-2 font-tabular" style="font-size: 0.85rem;" id="modal-nota-number">-</span>
                                        <span class="font-weight-bold text-dark" style="font-size: 0.95rem;" id="modal-nota-title">-</span>
                                    </div>
                                    <div class="text-muted" style="font-size: 0.8rem;">
                                        <i class="fas fa-store mr-1 text-secondary"></i> Toko / Penyedia: <strong class="text-dark" id="modal-nota-supplier">-</strong>
                                    </div>
                                </div>
                                <div class="col-md-6 text-md-end mt-2 mt-md-0">
                                    <div class="text-muted" style="font-size: 0.8rem;">
                                        Alokasi Bulan: <strong class="text-dark" id="modal-nota-month-year">-</strong>
                                    </div>
                                    <div class="d-flex justify-content-md-end align-items-center" style="gap: 10px;">
                                        <span class="badge bg-secondary font-tabular" id="modal-nota-total-items">0 Barang</span>
                                        <span class="font-weight-bold text-success font-tabular" style="font-size: 1.05rem;" id="modal-nota-total-amount">Rp 0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Bulk Update -->
                    <form id="formBulkUpdateNota" class="p-3 border rounded mb-3 bg-white">
                        @csrf
                        <input type="hidden" name="no_nota" id="bulk-no-nota">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-bolt text-warning mr-2"></i>
                            <h6 class="font-weight-bold text-dark mb-0" style="font-size: 0.88rem;">Update Transaksi Massal untuk Semua Barang di Nota Ini</h6>
                        </div>
                        <p class="text-muted mb-3" style="font-size: 0.76rem;">
                            Ubah No. BPU, Tgl Transaksi (BKU), dan Tgl Nota sekaligus untuk seluruh barang dalam nota belanja ini.
                        </p>
                        <div class="row align-items-end g-2">
                            <div class="col-md-3 form-group mb-0">
                                <label class="font-weight-bold text-dark mb-1" style="font-size: 0.78rem;">No Transaksi / BPU</label>
                                <input type="text" name="no_transaksi" id="bulk-no-transaksi" class="form-control form-control-sm" placeholder="Contoh: BPU001">
                            </div>
                            <div class="col-md-3 form-group mb-0">
                                <label class="font-weight-bold text-dark mb-1" style="font-size: 0.78rem;">Tgl Transaksi (BKU) <span class="text-danger">*</span></label>
                                <input type="date" name="transaction_date" id="bulk-transaction-date" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3 form-group mb-0">
                                <label class="font-weight-bold text-dark mb-1" style="font-size: 0.78rem;">Tgl Nota / Bukti</label>
                                <input type="date" name="receipt_date" id="bulk-receipt-date" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3 form-group mb-0">
                                <button type="submit" class="btn btn-sm btn-primary w-100" id="btn-save-bulk-nota">
                                    <i class="fas fa-check-double mr-1"></i> Terapkan Massal
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Tabel Item Rincian Nota -->
                    <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                        <table class="table table-sm table-bordered table-hover mb-0" style="font-size: 0.8rem;" id="tableNotaItems">
                            <thead class="table-light sticky-top bg-light">
                                <tr>
                                    <th width="35" class="text-center">NO</th>
                                    <th width="110" class="text-center">KODE REK</th>
                                    <th class="text-center">URAIAN / NAMA BARANG</th>
                                    <th class="text-center">KEGIATAN</th>
                                    <th width="90" class="text-center">VOLUME</th>
                                    <th width="110" class="text-center">HARGA SATUAN</th>
                                    <th width="120" class="text-center">TOTAL BELANJA</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyNotaItems">
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-3">Memuat data rincian barang...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3 bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
<script>
$(document).ready(function() {
    $.fn.dataTable.ext.errMode = 'none';

    // --- Financial Summary Dashboard ---
    function loadSummary() {
        let year = $('#filter-year').val();
        $('#summary-year-label, #summary-year-label2, #summary-year-label3, #summary-year-label4').text(year);

        $.ajax({
            url: "{{ route('bos.realisasi.summary', [], false) }}",
            type: "GET",
            data: { year: year },
            success: function(data) {
                $('#summary-anggaran').text(data.total_anggaran_display);
                $('#summary-dana-masuk').text(data.total_dana_masuk_display);
                $('#summary-realisasi').text(data.total_realisasi_display);
                $('#summary-saldo-bank').text(data.saldo_bank_display);
                $('#summary-saldo').text(data.saldo_display);
                $('#summary-percentage').text(data.percentage);
                $('#summary-progress-bar').css('width', Math.min(data.percentage, 100) + '%').attr('aria-valuenow', data.percentage);

                // Color-code saldo bank card based on bank balance
                let bankCard = $('#summary-saldo-bank-card');
                let bankVal = $('#summary-saldo-bank');
                if (data.saldo_bank < 0) {
                    bankCard.removeClass('border-left-success border-left-warning').addClass('border-left-danger');
                    bankVal.removeClass('text-success text-warning').addClass('text-danger');
                } else {
                    bankCard.removeClass('border-left-danger border-left-warning').addClass('border-left-success');
                    bankVal.removeClass('text-danger text-warning').addClass('text-success');
                }

                // Color-code percentage card, bar & text based on spending level
                let pctCard = $('#summary-pct-box');
                let pBar = $('#summary-progress-bar');
                let pctVal = $('#summary-percentage').closest('.stat-value');
                if (data.percentage > 100) {
                    pctCard.removeClass('border-left-purple border-left-warning').addClass('border-left-danger');
                    pBar.removeClass('bg-purple bg-warning').addClass('bg-danger');
                    pctVal.removeClass('text-purple text-warning').addClass('text-danger');
                } else if (data.percentage >= 80) {
                    pctCard.removeClass('border-left-purple border-left-danger').addClass('border-left-warning');
                    pBar.removeClass('bg-purple bg-danger').addClass('bg-warning');
                    pctVal.removeClass('text-purple text-danger').addClass('text-warning');
                } else {
                    pctCard.removeClass('border-left-danger border-left-warning').addClass('border-left-purple');
                    pBar.removeClass('bg-danger bg-warning').addClass('bg-purple');
                    pctVal.removeClass('text-danger text-warning').addClass('text-purple');
                }
            }
        });
    }

    // Load summary on page init
    loadSummary();

    let table = $('#dataTable').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        pageLength: 100,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
        order: [[2, 'desc'], [1, 'desc']], // Default urutkan dari transaksi terbaru (Tgl BKU terbaru, kemudian No BPU terbaru)
        ajax: {
            url: "{{ route('bos.realisasi.index', [], false) }}",
            data: function(d) {
                d.year = $('#filter-year').val();
                d.month = $('#filter-month').val();
            }
        },
        columns: [
            {
                data: 'no_nota_display',
                name: 'no_nota',
                className: 'text-center font-tabular'
            },
            {
                data: 'no_transaksi',
                name: 'no_transaksi',
                className: 'text-center font-tabular',
                render: function(data, type, row) {
                    if (row.row_type === 'penerimaan') {
                        return data ? '<span class="badge badge-light border font-tabular">' + escapeHtml(data) + '</span>' : '<span class="text-muted font-italic">-</span>';
                    }
                    let val = escapeHtml(data || '');
                    return '<span class="inline-edit" data-id="' + row.id + '" data-field="no_transaksi" data-type="text" data-raw="' + val + '" title="Klik untuk edit No Transaksi">' + (val ? val : '<span class="text-muted font-italic">-</span>') + '</span>';
                }
            },
            {
                data: 'transaction_date_display',
                name: 'transaction_date',
                className: 'text-center font-tabular',
                render: function(data, type, row) {
                    if (row.row_type === 'penerimaan') {
                        return data;
                    }
                    let rawDate = row.transaction_date ? row.transaction_date.substring(0, 10) : '';
                    return '<span class="inline-edit" data-id="' + row.id + '" data-field="transaction_date" data-type="date" data-raw="' + rawDate + '" title="Klik untuk edit Tgl BKU">' + data + '</span>';
                }
            },
            {
                data: 'receipt_date_display',
                name: 'receipt_date',
                className: 'text-center font-tabular',
                render: function(data, type, row) {
                    if (row.row_type === 'penerimaan') {
                        return '<span class="text-muted">-</span>';
                    }
                    let rawDate = row.receipt_date ? row.receipt_date.substring(0, 10) : '';
                    return '<span class="inline-edit" data-id="' + row.id + '" data-field="receipt_date" data-type="date" data-raw="' + rawDate + '" title="Klik untuk edit Tgl Nota">' + (data !== '-' ? data : '<span class="text-muted font-italic">-</span>') + '</span>';
                }
            },
            {data: 'month', name: 'month', className: 'text-center'},
            {data: 'description', name: 'description', className: 'font-weight-500 text-dark'},
            {
                data: 'volume_display',
                name: 'volume',
                className: 'font-tabular text-center',
                render: function(data, type, row) {
                    if (row.row_type === 'penerimaan') {
                        return '<span class="text-muted">-</span>';
                    }
                    let volFormatted = formatRupiah(row.volume);
                    let unitHtml = row.unit ? '<div class="text-muted font-weight-normal" style="font-size: 0.67rem; line-height: 1;">' + escapeHtml(row.unit) + '</div>' : '';
                    return '<div class="inline-edit font-weight-bold text-primary font-tabular" data-id="' + row.id + '" data-field="volume" data-type="number" data-raw="' + row.volume + '" data-unit="' + escapeHtml(row.unit || '') + '" title="Klik untuk edit Volume" style="line-height: 1.15; cursor: pointer;">' +
                        '<div>' + volFormatted + '</div>' +
                        unitHtml +
                    '</div>';
                }
            },
            {
                data: 'price_display',
                name: 'price',
                className: 'font-tabular text-right text-end',
                render: function(data, type, row) {
                    if (row.row_type === 'penerimaan') {
                        return '<span class="text-muted">-</span>';
                    }
                    return '<span class="inline-edit font-weight-bold font-tabular" data-id="' + row.id + '" data-field="price" data-type="number" data-raw="' + row.price + '" title="Klik untuk edit Harga Satuan">' + data + '</span>';
                }
            },
            {
                data: 'total_amount_display',
                name: 'total_amount',
                searchable: false,
                className: 'font-tabular font-weight-bold text-right text-end'
            },
            {
                data: 'saldo_display',
                name: 'saldo',
                searchable: false,
                className: 'font-tabular font-weight-bold text-right text-end'
            },
            {data: 'kegiatan', name: 'kegiatan'},
            {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center'}
        ],
        footerCallback: function(row, data, start, end, display) {
            let api = this.api();
            let total = 0;
            api.rows({page: 'current'}).every(function() {
                let rowData = this.data();
                if (rowData && rowData.row_type === 'belanja') {
                    total += parseFloat(rowData.nominal_keluar) || 0;
                }
            });
            $('#table-total-amount').html('Rp ' + new Intl.NumberFormat('id-ID').format(total));
        },
        drawCallback: function(settings) {
            $('[data-bs-toggle="tooltip"]').tooltip();
            if (settings.json && settings.json.grandTotal) {
                $('#filter-total-display').html(settings.json.grandTotal);
            }
        }
    });

    $('#filter-year').on('change', function() {
        table.draw();
        loadSummary();
    });

    $('#filter-month').on('change', function() {
        table.draw();
    });

    // Function to load available items into select dropdown
    function loadAvailableItems() {
        let year = $('#create-year').val();
        let month = $('#create-month').val();

        if (!year || !month) return;

        $('#create-item-id').html('<option value="">Memuat objek belanja...</option>');
        $('#create-item-detail').addClass('d-none');

        $.ajax({
            url: "{{ route('bos.realisasi.available-items', [], false) }}",
            type: "GET",
            data: { year: year, month: month },
            success: function(items) {
                let html = '<option value="">-- Pilih / Ketik Objek Belanja --</option>';
                if (items.length === 0) {
                    html = '<option value="">(Tidak ada barang dengan sisa anggaran s/d bulan ' + month + ')</option>';
                } else {
                    items.forEach(function(item) {
                        html += '<option value="' + item.id + '" ' +
                            'data-kegiatan="' + escapeHtml(item.kegiatan) + '" ' +
                            'data-program="' + escapeHtml(item.program_code) + '" ' +
                            'data-account="' + escapeHtml(item.account_code) + '" ' +
                            'data-desc="' + escapeHtml(item.description) + '" ' +
                            'data-budget-vol="' + item.volume_budget + '" ' +
                            'data-remaining-vol="' + item.remaining_volume + '" ' +
                            'data-unit="' + escapeHtml(item.unit) + '" ' +
                            'data-price="' + item.price + '">' +
                            escapeHtml(item.text) + '</option>';
                    });
                }
                $('#create-item-id').html(html);

                // Custom Smart Matcher for Multi-Keyword & Flexible Searching (AND Logic)
                function smartMatcher(params, data) {
                    if (!params.term || $.trim(params.term) === '') {
                        return data;
                    }

                    if (data.children && data.children.length > 0) {
                        let matchGroup = $.extend(true, {}, data);
                        for (let c = data.children.length - 1; c >= 0; c--) {
                            let child = data.children[c];
                            if (!smartMatcher(params, child)) {
                                matchGroup.children.splice(c, 1);
                            }
                        }
                        return matchGroup.children.length > 0 ? matchGroup : null;
                    }

                    let originalText = (data.text || '');
                    let extraData = '';
                    if (data.element) {
                        let $el = $(data.element);
                        extraData = [
                            $el.data('kegiatan') || '',
                            $el.data('program') || '',
                            $el.data('account') || '',
                            $el.data('desc') || '',
                            $el.data('unit') || '',
                            $el.data('price') || ''
                        ].join(' ');
                    }

                    let fullTarget = (originalText + ' ' + extraData).toLowerCase();
                    let terms = $.trim(params.term).toLowerCase().split(/\s+/).filter(Boolean);

                    let matchAll = terms.every(function(t) {
                        return fullTarget.indexOf(t) > -1;
                    });

                    return matchAll ? data : null;
                }

                // Re-initialize or refresh Select2 inside modal with Smart Matcher
                if ($('#create-item-id').hasClass("select2-hidden-accessible")) {
                    $('#create-item-id').select2('destroy');
                }
                $('#create-item-id').select2({
                    dropdownParent: $('#createModal'),
                    placeholder: '-- Ketik / Pilih Objek Belanja --',
                    allowClear: true,
                    minimumResultsForSearch: 0,
                    matcher: smartMatcher
                });
            }
        });
    }

    function escapeHtml(str) {
        return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function e(str) {
        return escapeHtml(str);
    }

    $('#createModal').on('show.bs.modal shown.bs.modal', function() {
        loadAvailableItems();
    });

    $(document).on('select2:open', function() {
        setTimeout(function() {
            let searchField = document.querySelector('.select2-container--open .select2-search__field');
            if (searchField) {
                searchField.focus();
            }
        }, 50);
    });

    $('#create-year, #create-month').on('change', function() {
        loadAvailableItems();
    });

    // Auto-fill form when item is selected via Select2
    $('#create-item-id').on('change select2:select', function() {
        let opt = $(this).find('option:selected');
        if (opt.val()) {
            let kegiatan = opt.data('kegiatan');
            let program = opt.data('program');
            let account = opt.data('account');
            let desc = opt.data('desc');
            let budgetVol = opt.data('budget-vol');
            let remainingVol = opt.data('remaining-vol');
            let unit = opt.data('unit');
            let price = opt.data('price');

            $('#detail-kegiatan').text(kegiatan);
            $('#detail-account').text((program ? '[' + program + '] ' : '') + account);
            $('#detail-desc').text(desc);
            $('#detail-budget-vol').text(budgetVol + ' ' + unit);
            $('#detail-remaining-vol').text(remainingVol + ' ' + unit);
            $('#create-item-detail').removeClass('d-none');

            $('#create-volume').val(remainingVol);
            $('#create-unit').val(unit);
            $('#create-price').val(formatThousand(price));

            calculateCreateTotal();
        } else {
            $('#create-item-detail').addClass('d-none');
            $('#create-total-display').val('Rp 0');
        }
    });

    function formatThousand(val) {
        if (val === null || val === undefined || val === '') return '';
        if (typeof val === 'number') {
            return new Intl.NumberFormat('id-ID').format(Math.round(val));
        }
        let str = String(val).trim();
        if (/^\d+(\.\d+)?$/.test(str)) {
            let num = Math.round(parseFloat(str) || 0);
            return new Intl.NumberFormat('id-ID').format(num);
        }
        let clean = str.replace(/\D/g, '');
        if (!clean) return '';
        return new Intl.NumberFormat('id-ID').format(clean);
    }

    function parseThousand(val) {
        if (val === null || val === undefined || val === '') return 0;
        return parseFloat(String(val).replace(/\./g, '').replace(/,/g, '.')) || 0;
    }

    function formatRupiah(num) {
        if (num === null || num === undefined || num === '') return '0';
        let n = parseFloat(num);
        if (isNaN(n)) return '0';
        return new Intl.NumberFormat('id-ID').format(Math.round(n));
    }

    $(document).on('input', '.input-thousand', function() {
        let val = $(this).val();
        let num = val.replace(/\D/g, '');
        $(this).val(num ? new Intl.NumberFormat('id-ID').format(num) : '');
    });

    function calculateCreateTotal() {
        let vol = parseFloat($('#create-volume').val()) || 0;
        let price = parseThousand($('#create-price').val());
        let total = vol * price;
        $('#create-total-display').val('Rp ' + new Intl.NumberFormat('id-ID').format(total));
    }

    $('#create-volume, #create-price').on('input change', function() {
        calculateCreateTotal();
    });

    // Store Realisasi AJAX
    $('#createForm').submit(function(e) {
        e.preventDefault();
        let data = $(this).serializeArray();
        data.forEach(function(item) {
            if (item.name === 'price') {
                item.value = String(item.value).replace(/\./g, '');
            }
        });

        $.ajax({
            url: "{{ route('bos.realisasi.store', [], false) }}",
            type: "POST",
            data: $.param(data),
            success: function(res) {
                $('#createModal').modal('hide');
                $('#createForm')[0].reset();
                $('#create-item-detail').addClass('d-none');
                table.draw(false);
                loadSummary();
                Swal.fire('Berhasil!', res.message, 'success');
            },
            error: function(xhr) {
                Swal.fire('Gagal Simpan', xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan.', 'error');
            }
        });
    });

    // Edit Realisasi AJAX
    $(document).on('click', '.btn-edit', function() {
        let json = $(this).data('json') || {};
        let id = json.row_id || json.id || $(this).data('id');
        $('#edit-id').val(id);
        $('#edit-no_transaksi').val(json.no_transaksi || '');
        $('#edit-transaction_date').val(json.transaction_date ? json.transaction_date.substring(0, 10) : '');
        $('#edit-receipt_date').val(json.receipt_date ? json.receipt_date.substring(0, 10) : '');
        $('#edit-volume').val(json.volume ? (parseFloat(json.volume) || json.volume) : 1);
        $('#edit-unit').val(json.unit || '');
        $('#edit-price').val(formatThousand(json.price));
        $('#edit-notes').val(json.notes || '');

        let desc = json.uraian || json.description || (json.kertas_kerja_item ? json.kertas_kerja_item.description : '-');
        let acc = json.account_code || (json.kertas_kerja_item ? json.kertas_kerja_item.account_code : '');
        let keg = json.kegiatan || (json.kertas_kerja_item ? json.kertas_kerja_item.kegiatan : '-');
        $('#edit-item-title').text((acc && acc !== '-' ? acc + ' - ' : '') + desc);
        $('#edit-item-kegiatan').text(keg);

        calculateEditTotal();
        $('#editModal').modal('show');
    });

    function calculateEditTotal() {
        let vol = parseFloat($('#edit-volume').val()) || 0;
        let price = parseThousand($('#edit-price').val());
        let total = vol * price;
        $('#edit-total-display').val('Rp ' + new Intl.NumberFormat('id-ID').format(total));
    }

    $('#edit-volume, #edit-price').on('input change', function() {
        calculateEditTotal();
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
            url: "{{ url('bos/realisasi') }}/" + id,
            type: "PUT",
            data: $.param(data),
            success: function(res) {
                $('#editModal').modal('hide');
                table.draw(false);
                loadSummary();
                Swal.fire('Berhasil!', res.message, 'success');
            },
            error: function(xhr) {
                Swal.fire('Gagal Update', xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan.', 'error');
            }
        });
    });

    // Delete Realisasi AJAX
    $(document).on('click', '.btn-delete', function(e) {
        e.preventDefault();
        let url = $(this).data('url');
        Swal.fire({
            title: 'Hapus Transaksi Realisasi ini?',
            text: 'Volume barang akan kembali ke sisa ketersediaan Kertas Kerja.',
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
                        loadSummary();
                        Swal.fire('Terhapus!', res.message, 'success');
                    }
                });
            }
        });
    });

    // --- INLINE EDITING CELL HANDLER ---
    $(document).on('click', '.inline-edit', function(e) {
        e.stopPropagation();
        let span = $(this);
        if (span.find('input').length > 0) return;

        let id = span.data('id');
        let field = span.data('field');
        let type = span.data('type');
        let rawVal = span.data('raw');

        let input = $('<input>', {
            type: type === 'number' ? 'number' : (type === 'date' ? 'date' : 'text'),
            class: 'form-control form-control-sm inline-input font-tabular',
            value: rawVal
        });

        if (type === 'number') {
            input.attr('step', '0.01');
        }

        span.html(input);
        input.focus();

        function saveInlineChange() {
            let newVal = input.val();
            if (newVal == rawVal) {
                table.draw(false);
                return;
            }

            $.ajax({
                url: "{{ url('bos/realisasi') }}/" + id + "/field",
                type: "PATCH",
                data: {
                    _token: '{{ csrf_token() }}',
                    field: field,
                    value: newVal
                },
                success: function(res) {
                    table.draw(false);
                    loadSummary();
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true
                    });
                    Toast.fire({
                        icon: 'success',
                        title: 'Tersimpan!'
                    });
                },
                error: function(xhr) {
                    table.draw(false);
                    Swal.fire('Gagal Simpan', xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan.', 'error');
                }
            });
        }

        input.on('blur', function() {
            saveInlineChange();
        });

        input.on('keydown', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                input.off('blur');
                saveInlineChange();
            } else if (e.which === 27) {
                table.draw(false);
            }
        });
    });

    // --- View Nota Details & Bulk Update Handler ---
    $(document).on('click', '.btn-view-nota', function(evt) {
        evt.preventDefault();
        let $btn = $(this).closest('.btn-view-nota');
        let noNota = $btn.attr('data-nota') || $btn.data('nota') || $.trim($btn.text());
        if (!noNota) {
            console.warn("No. Nota tidak ditemukan pada tombol.");
            return;
        }

        $('#modalNotaBulkEdit').modal('show');
        $('#modal-nota-number').text(noNota);
        $('#bulk-no-nota').val(noNota);
        $('#modal-nota-title').text('Memuat...');
        $('#modal-nota-supplier').text('-');
        $('#modal-nota-month-year').text('-');
        $('#modal-nota-total-items').text('0 Barang');
        $('#modal-nota-total-amount').text('Rp 0');
        $('#tbodyNotaItems').html('<tr><td colspan="7" class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin mr-1"></i> Memuat data rincian barang...</td></tr>');

        $.ajax({
            url: "{{ route('bos.realisasi.nota-details', [], false) }}",
            type: "GET",
            data: { no_nota: noNota },
            dataType: 'json',
            success: function(res) {
                try {
                    $('#modal-nota-title').text(res.title || 'Nota Belanja');
                    $('#modal-nota-supplier').text(res.supplier_name || 'Tidak ada data penyedia');
                    $('#modal-nota-month-year').text((res.month || '') + ' ' + (res.year || ''));
                    $('#modal-nota-total-items').text((res.total_items || 0) + ' Barang');
                    $('#modal-nota-total-amount').text(res.total_amount_display || 'Rp 0');

                    $('#bulk-no-transaksi').val(res.no_transaksi || '');
                    $('#bulk-transaction-date').val(res.transaction_date || '');
                    $('#bulk-receipt-date').val(res.receipt_date || res.transaction_date || '');

                    let rowsHtml = '';
                    if (!res.items || res.items.length === 0) {
                        rowsHtml = '<tr><td colspan="7" class="text-center text-muted py-3">Tidak ada item belanja dalam nota ini.</td></tr>';
                    } else {
                        res.items.forEach(function(item) {
                            let volVal = (typeof item.volume !== 'undefined') ? formatRupiah(item.volume) : '0';
                            rowsHtml += '<tr>' +
                                '<td class="text-center font-tabular">' + item.index + '</td>' +
                                '<td class="font-tabular">' + escapeHtml(item.account_code) + '</td>' +
                                '<td class="font-weight-bold text-dark">' + escapeHtml(item.description) + '</td>' +
                                '<td>' + escapeHtml(item.kegiatan) + '</td>' +
                                '<td class="text-center font-tabular">' + volVal + ' ' + escapeHtml(item.unit) + '</td>' +
                                '<td class="text-right text-end font-tabular">' + (item.price_display || 'Rp 0') + '</td>' +
                                '<td class="text-right text-end font-tabular font-weight-bold text-success">' + (item.total_amount_display || 'Rp 0') + '</td>' +
                                '</tr>';
                        });
                    }
                    $('#tbodyNotaItems').html(rowsHtml);
                } catch (renderErr) {
                    console.error("Error rendering items:", renderErr);
                    $('#tbodyNotaItems').html('<tr><td colspan="7" class="text-center text-danger py-3"><i class="fas fa-exclamation-triangle mr-1"></i> Terjadi kesalahan saat memproses data tampilan: ' + escapeHtml(renderErr.message) + '</td></tr>');
                }
            },
            error: function(xhr, status, errorMsg) {
                console.error("Error loading nota details:", xhr, status, errorMsg);
                let msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : ('Gagal memuat rincian nota (' + (xhr.status || errorMsg || 'Koneksi error') + ')');
                $('#tbodyNotaItems').html('<tr><td colspan="7" class="text-center text-danger py-3"><i class="fas fa-exclamation-triangle mr-1"></i> ' + escapeHtml(msg) + '</td></tr>');
            }
        });
    });

    $('#formBulkUpdateNota').submit(function(e) {
        e.preventDefault();
        let btn = $('#btn-save-bulk-nota');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

        $.ajax({
            url: "{{ route('bos.realisasi.bulk-update-nota', [], false) }}",
            type: "POST",
            data: $(this).serialize(),
            success: function(res) {
                btn.prop('disabled', false).html('<i class="fas fa-check-double mr-1"></i> Terapkan Massal');
                $('#modalNotaBulkEdit').modal('hide');
                table.draw(false);
                loadSummary();
                Swal.fire('Berhasil!', res.message, 'success');
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fas fa-check-double mr-1"></i> Terapkan Massal');
                Swal.fire('Gagal Update', xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan.', 'error');
            }
        });
    });

    // --- PENERIMAAN DANA BOS HANDLERS ---

    // Create Penerimaan Submit
    $('#createPenerimaanForm').submit(function(e) {
        e.preventDefault();
        let btn = $('#btn-save-penerimaan');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

        let formData = new FormData(this);

        $.ajax({
            url: "{{ route('bos.realisasi.penerimaan.store', [], false) }}",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            success: function(res) {
                btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan Penerimaan');
                $('#modalCreatePenerimaan').modal('hide');
                $('#createPenerimaanForm')[0].reset();
                table.draw(false);
                loadSummary();
                Swal.fire('Berhasil!', res.message, 'success');
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan Penerimaan');
                let msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Terjadi kesalahan.';
                Swal.fire('Gagal Simpan', msg, 'error');
            }
        });
    });

    // Open Edit Penerimaan Modal
    $(document).on('click', '.btn-edit-penerimaan', function() {
        let btn = $(this);
        let id = btn.data('id');
        let date = btn.data('date');
        let category = btn.data('category');
        let ref = btn.data('ref');
        let amount = btn.data('amount');
        let desc = btn.data('desc');
        let notes = btn.data('notes');
        let file = btn.data('file');

        $('#edit-penerimaan-id').val(id);
        $('#edit-penerimaan-date').val(date);
        $('#edit-penerimaan-category').val(category);
        $('#edit-penerimaan-ref').val(ref);
        $('#edit-penerimaan-amount').val(formatThousand(amount));
        $('#edit-penerimaan-desc').val(desc);
        $('#edit-penerimaan-notes').val(notes);

        if (file) {
            $('#edit-penerimaan-file-link').attr('href', file);
            $('#edit-penerimaan-file-preview').removeClass('d-none');
        } else {
            $('#edit-penerimaan-file-preview').addClass('d-none');
        }

        $('#modalEditPenerimaan').modal('show');
    });

    // Update Penerimaan Submit
    $('#editPenerimaanForm').submit(function(e) {
        e.preventDefault();
        let id = $('#edit-penerimaan-id').val();
        let btn = $('#btn-update-penerimaan');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

        let formData = new FormData(this);
        formData.append('_method', 'PUT');

        $.ajax({
            url: "{{ url('bos/realisasi/penerimaan') }}/" + id,
            type: "POST", // Laravel handles PUT via POST with _method
            data: formData,
            contentType: false,
            processData: false,
            success: function(res) {
                btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan Perubahan');
                $('#modalEditPenerimaan').modal('hide');
                table.draw(false);
                loadSummary();
                Swal.fire('Berhasil!', res.message, 'success');
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan Perubahan');
                let msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Terjadi kesalahan.';
                Swal.fire('Gagal Update', msg, 'error');
            }
        });
    });

    // Delete Penerimaan
    $(document).on('click', '.btn-delete-penerimaan', function(e) {
        e.preventDefault();
        let url = $(this).data('url');
        Swal.fire({
            title: 'Hapus Data Penerimaan Dana?',
            text: 'Catatan saldo dan kalkulasi Buku Kas Umum akan disesuaikan kembali.',
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
                        loadSummary();
                        Swal.fire('Terhapus!', res.message, 'success');
                    },
                    error: function(xhr) {
                        let msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Gagal menghapus data.';
                        Swal.fire('Gagal', msg, 'error');
                    }
                });
            }
        });
    });
});
</script>
@stop