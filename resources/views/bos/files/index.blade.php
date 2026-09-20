@extends('adminlte::page')

@php
    $selectedYear = $selectedYear ?? $activeFiscalYear ?? session('fiscal_year', date('Y'));
@endphp

@section('title', 'File Kertas Kerja - BOS')

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap" style="padding: 2px 0;">
    <div>
        <h1 class="page-title"><i class="fas fa-file-pdf text-danger mr-1"></i> File Kertas Kerja BOS</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-house"></i> Dashboard</a></li>
                <li class="breadcrumb-item"><i class="fas fa-calculator"></i> Penganggaran / BOS</li>
                <li class="breadcrumb-item active">File Kertas Kerja</li>
            </ol>
        </nav>
    </div>
    <div class="header-actions">
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
            <i class="fas fa-cloud-upload-alt mr-1"></i> Upload File PDF
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
    .font-tabular, #year-total-display, td.text-right, td.font-weight-bold {
        font-variant-numeric: tabular-nums;
        font-feature-settings: "tnum";
    }

    /* Force Table Text Alignment */
    .text-right, .text-end, th.text-right, td.text-right, th.text-end, td.text-end,
    #fileTable td:nth-child(5), #fileTable th:nth-child(5) {
        text-align: right !important;
    }
    .text-center, th.text-center, td.text-center {
        text-align: center !important;
    }
    .text-left, .text-start, th.text-left, td.text-left, th.text-start, td.text-start {
        text-align: left !important;
    }

    /* Badge Visibility & High Contrast */
    .badge.badge-success, .badge.bg-success, span.badge-success {
        background-color: #dcfce7 !important;
        color: #166534 !important;
        border: 1px solid #bbf7d0 !important;
    }
    .badge.badge-secondary, .badge.bg-secondary, span.badge-secondary {
        background-color: #f1f5f9 !important;
        color: #334155 !important;
        border: 1px solid #cbd5e1 !important;
    }
    .badge.badge-warning, .badge.bg-warning, span.badge-warning {
        background-color: #fef3c7 !important;
        color: #92400e !important;
        border: 1px solid #fde68a !important;
    }
    .badge.badge-info, .badge.bg-info, span.badge-info {
        background-color: #e0f2fe !important;
        color: #0369a1 !important;
        border: 1px solid #bae6fd !important;
    }
    .badge.badge-danger, .badge.bg-danger, span.badge-danger {
        background-color: #fee2e2 !important;
        color: #991b1b !important;
        border: 1px solid #fecaca !important;
    }

    /* ── High-Density Data Table (13px Font, Compact Padding) ── */
    #fileTable {
        font-size: 0.8125rem !important; /* 13px */
        letter-spacing: -0.008em;
    }
    #fileTable th {
        font-size: 0.74rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 0.4rem 0.5rem !important;
        vertical-align: middle;
        background-color: #f8fafc;
        color: #475569;
        text-align: center !important;
    }
    #fileTable td {
        padding: 0.35rem 0.5rem !important;
        vertical-align: middle;
        line-height: 1.35;
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
</style>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2 px-3 mb-2" style="font-size: 0.8rem;">
            <i class="fas fa-circle-check mr-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-2 py-2 px-3" role="alert" style="border-left: 4px solid #bd2130; font-size: 0.8rem;">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <div>
                    <strong>Gagal Upload:</strong> {{ session('error') }}
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row align-items-start">
        {{-- Tabel Daftar File --}}
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white py-2 pr-2 pl-3 d-flex justify-content-between align-items-center flex-wrap" style="gap: 8px; border-bottom: 1px solid #edf2f7;">
                    <h3 class="card-title font-weight-bold text-dark mb-0" style="font-size: 0.92rem;">
                        <i class="fas fa-list mr-1 text-primary"></i> Daftar File Kertas Kerja
                    </h3>
                    <div class="d-flex align-items-center ml-auto ms-auto flex-wrap" style="gap: 8px;">
                        <input type="hidden" id="filter-year" value="{{ $selectedYear }}">
                        <span class="badge bg-primary text-white font-weight-normal d-inline-flex align-items-center shadow-xs" style="height: 31px; font-size: 0.8125rem; padding: 0 10px; border-radius: 4px;" title="Tahun Anggaran aktif yang dipilih pada Navbar">
                            <i class="fas fa-calendar-alt me-1 mr-1"></i> Tahun Anggaran {{ $selectedYear }}
                        </span>
                        <div class="input-group input-group-sm shadow-xs" style="width: auto;">
                            <span class="input-group-text bg-light text-muted font-weight-bold" style="height: 31px; font-size: 0.8125rem; border-radius: 4px 0 0 4px;">
                                <i class="fas fa-code-branch text-info me-1 mr-1"></i> Versi:
                            </span>
                            <select id="filter-version" class="form-select form-select-sm font-weight-bold text-dark" style="height: 31px; font-size: 0.8125rem; border-radius: 0 4px 4px 0; cursor: pointer; width: auto; min-width: 90px;">
                                <option value="active" selected>Aktif</option>
                                @php
                                    $initialYear = $years->first() ?? null;
                                    $initialVersions = ($filesData ?? collect())->when($initialYear, fn($c) => $c->where('year', $initialYear))->pluck('version')->unique();
                                @endphp
                                @foreach($initialVersions as $ver)
                                    <option value="{{ $ver }}">{{ $ver }}</option>
                                @endforeach
                                <option value="all">Semua</option>
                            </select>
                        </div>
                        <div class="bg-light border rounded shadow-xs d-inline-flex align-items-center" style="height: 31px; padding: 0 10px; border-radius: 4px;">
                            <small class="text-muted mr-1 font-weight-bold" style="font-size: 0.78rem;">Total:</small>
                            <span id="year-total-display" class="font-weight-bold text-success font-tabular" style="font-size: 0.8125rem;">Rp 0</span>
                        </div>
                    </div>
                </div>
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover table-striped w-100" id="fileTable">
                            <thead>
                                <tr>
                                    <th width="30" class="text-center">#</th>
                                    <th width="50" class="text-center">TAHUN</th>
                                    <th width="65" class="text-center">BULAN</th>
                                    <th style="min-width: 170px;" class="text-center">NAMA FILE</th>
                                    <th width="110" class="text-center">ANGGARAN</th>
                                    <th width="75" class="text-center">VERSI</th>
                                    <th width="90" class="text-center">KETERANGAN</th>
                                    <th width="80" class="text-center">AKSI</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Live Preview PDF (Tinggi Independen & Sticky) --}}
        <div class="col-lg-5" style="position: sticky; top: 15px; z-index: 5;">
            <div class="card shadow-sm border-0 mb-3" style="height: calc(100vh - 120px); min-height: 550px; display: flex; flex-direction: column;">
                <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center flex-shrink-0" style="border-bottom: 1px solid #edf2f7;">
                    <h3 class="card-title font-weight-bold text-dark mb-0" id="previewTitle" style="font-size: 0.92rem;">
                        <i class="fas fa-eye mr-1 text-info"></i> Preview PDF
                    </h3>
                </div>
                <div class="card-body p-0 position-relative bg-light" id="previewContainer" style="flex: 1 1 auto; min-height: 0; overflow: hidden;">
                    <div class="text-center text-muted p-4" id="previewPlaceholder" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 85%; font-size: 0.8rem;">
                        <i class="fas fa-file-pdf fa-3x mb-2 d-block text-secondary"></i>
                        <p class="mb-0">Pilih baris pada tabel di sebelah kiri untuk melihat preview dokumen PDF Kertas Kerja.</p>
                    </div>
                    <iframe id="pdfViewer" style="width: 100%; height: 100%; border: none; display: none; position: absolute; top: 0; left: 0;"></iframe>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Upload --}}
    <div class="modal fade" id="uploadModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form action="{{ route('bos.files.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-light py-2 px-3 border-bottom">
                        <h5 class="modal-title font-weight-bold text-dark" style="font-size: 1rem;"><i class="fas fa-cloud-upload-alt mr-2 text-primary"></i>Upload File Kertas Kerja PDF</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body py-3">
                        <div class="row">
                            <div class="col-md-6 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">Tahun Anggaran <span class="text-danger">*</span></label>
                                <input type="number" id="upload-year" name="year" class="form-control form-control-sm" value="{{ $selectedYear }}" min="2020" max="2099" required>
                            </div>
                            <div class="col-md-6 form-group mb-2">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">Bulan <span class="text-danger">*</span></label>
                                <select id="upload-month" name="month" class="form-control form-control-sm" required>
                                    @foreach(['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'] as $m)
                                        <option value="{{ $m }}">{{ $m }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Alert Dinamis Deteksi Versi & Perubahan --}}
                        <div id="version-check-alert" style="display: none;"></div>

                        <div class="form-group mb-2">
                            <label class="font-weight-bold" style="font-size: 0.8rem;">Versi Dokumen <span class="text-danger">*</span></label>
                            <input type="text" id="upload-version" name="version" class="form-control form-control-sm" placeholder="Contoh: v1.0 / Revisi 1" value="v1.0" required>
                        </div>
                        <div class="form-group mb-2">
                            <label class="font-weight-bold" style="font-size: 0.8rem;">File PDF Kertas Kerja <span class="text-danger">*</span></label>
                            <div class="custom-file">
                                <input type="file" name="file" class="custom-file-input" accept=".pdf" required>
                                <label class="custom-file-label" style="font-size: 0.8rem;">Pilih file PDF (Maks. 10MB)...</label>
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <label class="font-weight-bold" style="font-size: 0.8rem;">Keterangan</label>
                            <textarea name="description" class="form-control form-control-sm" rows="2" placeholder="Catatan opsional..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-upload mr-1"></i> Upload & Proses</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Komparasi Versi Kertas Kerja --}}
    <div class="modal fade" id="modalCompareKertasKerja" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light py-2 px-3 border-bottom">
                    <div class="d-flex align-items-center flex-wrap" style="gap: 10px;">
                        <h5 class="modal-title font-weight-bold text-dark mb-0" style="font-size: 0.98rem;">
                            <i class="fas fa-code-branch text-warning mr-1"></i> Komparasi Revisi Kertas Kerja BOS
                        </h5>
                        <span class="badge badge-secondary font-tabular" id="comp-header-period" style="font-size: 0.8rem;">-</span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3 bg-light">
                    <!-- Selector switcher versi pembanding -->
                    <div class="d-flex justify-content-between align-items-center flex-wrap bg-white p-2 rounded border mb-3" style="gap: 8px;">
                        <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                            <span class="font-weight-bold text-muted" style="font-size: 0.78rem;">Bandingkan Versi:</span>
                            <div class="d-flex align-items-center" style="gap: 4px;">
                                <span id="badge-old-file-label" class="font-weight-bold font-tabular" style="font-size: 0.82rem;">-</span>
                                <span class="text-muted font-weight-bold" style="font-size: 0.76rem;">dengan</span>
                                <select id="select-compare-other" class="form-control form-control-sm font-weight-bold" style="width: 240px; height: 28px; font-size: 0.78rem; padding: 2px 6px;">
                                </select>
                            </div>
                        </div>
                        <div id="comp-active-actions" class="d-flex align-items-center" style="gap: 6px;">
                        </div>
                    </div>

                    <!-- Peringatan Kritis Keterkaitan Realisasi Belanja -->
                    <div id="comp-warning-box" class="alert alert-warning py-2 px-3 mb-3 shadow-sm" style="display: none; font-size: 0.8rem; border-left: 4px solid #f59e0b;">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-exclamation-triangle text-warning mr-2 mt-1"></i>
                            <div>
                                <strong class="text-dark">Peringatan Keterkaitan Transaksi Realisasi Belanja:</strong>
                                <ul class="mb-0 pl-3 mt-1 text-danger font-weight-bold" id="comp-warning-list"></ul>
                            </div>
                        </div>
                    </div>

                    <!-- Ringkasan Finansial Card -->
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm mb-0">
                                <div class="card-body p-2 border-left border-secondary" style="border-left-width: 4px !important;">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small class="text-muted font-weight-bold text-uppercase" style="font-size: 0.7rem;">Versi Acuan Lama</small>
                                        <span id="comp-card-old-badge"></span>
                                    </div>
                                    <div class="h6 font-weight-bold text-dark mb-1" id="comp-card-old-version" style="font-size: 0.95rem;">-</div>
                                    <div class="h5 font-weight-bold text-primary font-tabular mb-1" id="comp-card-old-pagu">Rp 0</div>
                                    <small class="text-muted font-tabular" id="comp-card-old-items"><i class="fas fa-box mr-1"></i>0 item</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm mb-0">
                                <div class="card-body p-2 border-left border-primary" style="border-left-width: 4px !important;">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small class="text-muted font-weight-bold text-uppercase" style="font-size: 0.7rem;">Versi Pembanding Baru</small>
                                        <span id="comp-card-new-badge"></span>
                                    </div>
                                    <div class="h6 font-weight-bold text-dark mb-1" id="comp-card-new-version" style="font-size: 0.95rem;">-</div>
                                    <div class="h5 font-weight-bold text-success font-tabular mb-1" id="comp-card-new-pagu">Rp 0</div>
                                    <small class="text-muted font-tabular" id="comp-card-new-items"><i class="fas fa-box mr-1"></i>0 item</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm mb-0">
                                <div class="card-body p-2 border-left border-info" style="border-left-width: 4px !important;">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small class="text-muted font-weight-bold text-uppercase" style="font-size: 0.7rem;">Perubahan Pagu Anggaran</small>
                                        <span class="badge bg-light text-dark border" style="font-size: 0.68rem;">Total Selisih</span>
                                    </div>
                                    <div class="h5 font-weight-bold font-tabular mb-1" id="comp-card-diff-pagu">Rp 0</div>
                                    <small class="text-muted" id="comp-card-diff-desc" style="font-size: 0.74rem;">-</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Nav Tabs Komparasi -->
                    <ul class="nav nav-tabs bg-white px-2 pt-2 border rounded-top" id="compTab" role="tablist" style="font-size: 0.8rem;">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active font-weight-bold text-dark py-2 px-3" id="tab-modified-btn" data-bs-toggle="tab" data-bs-target="#tab-modified" type="button" role="tab">
                                <i class="fas fa-pen text-warning mr-1"></i> Item Mengalami Perubahan 
                                <span class="badge bg-warning text-dark ml-1" id="tab-count-modified">0</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link font-weight-bold text-dark py-2 px-3" id="tab-removed-btn" data-bs-toggle="tab" data-bs-target="#tab-removed" type="button" role="tab">
                                <i class="fas fa-minus-circle text-danger mr-1"></i> Item Dihapus / Ditiadakan 
                                <span class="badge badge-danger ml-1" id="tab-count-removed">0</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link font-weight-bold text-dark py-2 px-3" id="tab-added-btn" data-bs-toggle="tab" data-bs-target="#tab-added" type="button" role="tab">
                                <i class="fas fa-plus-circle text-success mr-1"></i> Item Ditambahkan Baru 
                                <span class="badge badge-success ml-1" id="tab-count-added">0</span>
                            </button>
                        </li>
                    </ul>

                    <!-- Tab Contents -->
                    <div class="tab-content bg-white p-2 border border-top-0 rounded-bottom" id="compTabContent">
                        <!-- Tab Item Berubah -->
                        <div class="tab-pane fade show active" id="tab-modified" role="tabpanel">
                            <div class="table-responsive" style="max-height: 380px;">
                                <table class="table table-sm table-hover table-bordered mb-0" id="table-comp-modified" style="font-size: 0.78rem;">
                                    <thead class="bg-light sticky-top">
                                        <tr>
                                            <th width="30" class="text-center">#</th>
                                            <th width="110">REKENING</th>
                                            <th>KEGIATAN & URAIAN</th>
                                            <th width="65" class="text-center">SATUAN</th>
                                            <th width="140" class="text-center">VOL (LAMA &rarr; BARU)</th>
                                            <th width="160" class="text-right">HARGA (LAMA &rarr; BARU)</th>
                                            <th width="170" class="text-right">TOTAL (LAMA &rarr; BARU)</th>
                                            <th width="120" class="text-right">SELISIH</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbody-comp-modified">
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Tab Item Dihapus -->
                        <div class="tab-pane fade" id="tab-removed" role="tabpanel">
                            <div class="table-responsive" style="max-height: 380px;">
                                <table class="table table-sm table-hover table-bordered mb-0" id="table-comp-removed" style="font-size: 0.78rem;">
                                    <thead class="bg-light sticky-top">
                                        <tr>
                                            <th width="30" class="text-center">#</th>
                                            <th width="110">REKENING</th>
                                            <th>KEGIATAN</th>
                                            <th>URAIAN BELANJA</th>
                                            <th width="70" class="text-center">VOLUME</th>
                                            <th width="65" class="text-center">SATUAN</th>
                                            <th width="110" class="text-right">HARGA</th>
                                            <th width="125" class="text-right">TOTAL DIHAPUS</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbody-comp-removed">
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Tab Item Ditambah -->
                        <div class="tab-pane fade" id="tab-added" role="tabpanel">
                            <div class="table-responsive" style="max-height: 380px;">
                                <table class="table table-sm table-hover table-bordered mb-0" id="table-comp-added" style="font-size: 0.78rem;">
                                    <thead class="bg-light sticky-top">
                                        <tr>
                                            <th width="30" class="text-center">#</th>
                                            <th width="110">REKENING</th>
                                            <th>KEGIATAN</th>
                                            <th>URAIAN BELANJA</th>
                                            <th width="70" class="text-center">VOLUME</th>
                                            <th width="65" class="text-center">SATUAN</th>
                                            <th width="110" class="text-right">HARGA</th>
                                            <th width="125" class="text-right">TOTAL BARU</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbody-comp-added">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3 bg-light d-flex justify-content-between align-items-center">
                    <small class="text-muted"><i class="fas fa-info-circle mr-1"></i> Komparasi dicocokkan otomatis berdasarkan Kunci Semantik (Kode Rekening + Kegiatan + Uraian).</small>
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Edit --}}
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form id="editForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit-id">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title font-weight-bold" style="font-size: 1rem;"><i class="fas fa-edit mr-2"></i>Edit Informasi File Kertas Kerja</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body py-3">
                        <div class="form-group mb-2">
                            <label class="font-weight-bold" style="font-size: 0.8rem;">Versi Dokumen <span class="text-danger">*</span></label>
                            <input type="text" id="edit-version" name="version" class="form-control form-control-sm" required>
                        </div>
                        <div class="form-group mb-0">
                            <label class="font-weight-bold" style="font-size: 0.8rem;">Keterangan</label>
                            <textarea id="edit-description" name="description" class="form-control form-control-sm" rows="3"></textarea>
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
@stop

@section('js')
<script>
$(document).ready(function() {
    $('.custom-file-input').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass("selected").html(fileName);
    });

    $.fn.dataTable.ext.errMode = 'none';

    let table = $('#fileTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 12,
        lengthMenu: [[12, 24, 36, 48, -1], [12, 24, 36, 48, "Semua"]],
        order: [[1, 'desc']],
        ajax: {
            url: "{{ route('bos.files.index', [], false) }}",
            data: function(d) {
                d.year = $('#filter-year').val();
                d.version_filter = $('#filter-version').val();
            },
            error: function(xhr, error, thrown) {
                // Silently ignore aborted requests when navigating away or switching menus
                if (xhr.status === 0 || error === 'abort') {
                    return;
                }
                console.warn('DataTables error (fileTable):', error, thrown);
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center'},
            {data: 'year', name: 'year', className: 'text-center font-tabular'},
            {data: 'month', name: 'month', className: 'text-center'},
            {data: 'filename', name: 'filename', className: 'font-weight-500 text-dark'},
            {data: 'total_amount_display', name: 'items_sum_total_amount', searchable: false, className: 'font-tabular text-danger text-right text-end'},
            {data: 'version_display', name: 'version', className: 'text-center font-tabular'},
            {data: 'description_display', name: 'description_display', orderable: false, searchable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-right text-end'}
        ],
        drawCallback: function(settings) {
            $('[data-bs-toggle="tooltip"]').tooltip();
            if (settings.json && settings.json.grandTotal) {
                $('#year-total-display').html(settings.json.grandTotal);
            }
        }
    });

    let kertasKerjaVersions = @json($filesData ?? []);

    function updateVersionFilterOptions() {
        let selectedYear = $('#filter-year').val();
        let prevVal = $('#filter-version').val();

        let availableVersions = [];
        kertasKerjaVersions.forEach(function(f) {
            if (!selectedYear || f.year == selectedYear) {
                if (f.version && !availableVersions.includes(f.version)) {
                    availableVersions.push(f.version);
                }
            }
        });

        availableVersions.sort(function(a, b) {
            return a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' });
        });

        let optionsHtml = `<option value="active" ${(!prevVal || prevVal === 'active') ? 'selected' : ''}>Aktif</option>`;

        availableVersions.forEach(function(ver) {
            let isSelected = (prevVal === ver) ? 'selected' : '';
            optionsHtml += `<option value="${ver}" ${isSelected}>${ver}</option>`;
        });

        optionsHtml += `<option value="all" ${(prevVal === 'all') ? 'selected' : ''}>Semua</option>`;

        $('#filter-version').html(optionsHtml);
    }

    updateVersionFilterOptions();

    $('#filter-year').change(function() {
        updateVersionFilterOptions();
        table.draw();
    });

    $('#filter-version').change(function() {
        table.draw();
    });

    $(document).on('click', '.btn-preview, #fileTable tbody tr', function(e) {
        if ($(e.target).closest('.btn-edit, .btn-delete, .btn-compare, .btn-set-active, th, thead').length) return;
        
        let rowData = table.row($(this).closest('tr')).data();
        if (rowData && rowData.file_url) {
            $('#fileTable tbody tr').removeClass('table-primary');
            $(this).closest('tr').addClass('table-primary');

            $('#previewPlaceholder').hide();
            $('#pdfViewer').attr('src', rowData.file_url).show();
            $('#previewTitle').html('<i class="fas fa-file-pdf text-danger mr-2"></i> Preview: ' + rowData.filename);
        }
    });

    {{-- Cek Versi Otomatis Saat Pilih Tahun & Bulan di Modal Upload --}}
    function checkUploadVersion() {
        let year = $('#upload-year').val();
        let month = $('#upload-month').val();
        if (!year || !month) return;

        $.ajax({
            url: "{{ route('bos.files.check-version', [], false) }}",
            type: "GET",
            data: { year: year, month: month },
            success: function(res) {
                if (res.exists) {
                    $('#upload-version').val(res.next_version);
                    let alertHtml = `
                        <div class="alert alert-warning py-2 px-3 mb-2 shadow-sm" style="font-size: 0.78rem; border-left: 4px solid #f59e0b;">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-exclamation-triangle text-warning mr-2 mt-1"></i>
                                <div>
                                    <strong class="text-dark">File Kertas Kerja Periode Ini Sudah Ada!</strong><br>
                                    <span>${res.message}</span>
                                </div>
                            </div>
                        </div>
                    `;
                    $('#version-check-alert').html(alertHtml).slideDown(200);
                } else {
                    $('#upload-version').val('v1.0');
                    $('#version-check-alert').slideUp(200).html('');
                }
            }
        });
    }

    $('#upload-year, #upload-month').on('change input', checkUploadVersion);
    $('#uploadModal').on('shown.bs.modal', checkUploadVersion);

    {{-- Logika Komparasi Versi --}}
    let currentCompFileId = null;

    function renderComparisonModal(res) {
        currentCompFileId = res.old_file.id;
        $('#comp-header-period').text(res.old_file.month + ' ' + res.old_file.year);
        
        // Label versi acuan (teks langsung tanpa badge/border)
        let oldStatus = res.old_file.is_active ? 'Aktif' : 'Arsip';
        let oldColor = res.old_file.is_active ? '#198754' : '#212529';
        let oldVerFormatted = (res.old_file.version.charAt(0).toUpperCase() + res.old_file.version.slice(1)) + '-' + oldStatus;

        let newStatus = res.new_file.is_active ? 'Aktif' : 'Arsip';
        let newColor = res.new_file.is_active ? '#198754' : '#212529';
        let newVerFormatted = (res.new_file.version.charAt(0).toUpperCase() + res.new_file.version.slice(1)) + '-' + newStatus;

        $('#badge-old-file-label').html(`<span style="color: ${oldColor};">${oldVerFormatted}</span>`);

        // Populate compare dropdown
        let selectHtml = '';
        if (res.all_versions && res.all_versions.length > 0) {
            res.all_versions.forEach(function(v) {
                if (v.id !== res.old_file.id) {
                    let isSel = (v.id === res.new_file.id) ? 'selected' : '';
                    let statusLabel = v.is_active ? 'Aktif' : 'Arsip';
                    let verName = (v.version.charAt(0).toUpperCase() + v.version.slice(1)) + '-' + statusLabel;
                    selectHtml += `<option value="${v.id}" ${isSel}>${verName} (${v.filename})</option>`;
                }
            });
        }
        $('#select-compare-other').html(selectHtml);

        // Action Set Active buttons
        let actionsHtml = '';
        if (!res.old_file.is_active) {
            actionsHtml += `<button class="btn btn-xs btn-outline-success btn-set-active mr-1" data-id="${res.old_file.id}" data-version="${res.old_file.version}"><i class="fas fa-check mr-1"></i>Jadikan ${oldVerFormatted} Aktif</button>`;
        }
        if (!res.new_file.is_active) {
            actionsHtml += `<button class="btn btn-xs btn-outline-success btn-set-active mr-1" data-id="${res.new_file.id}" data-version="${res.new_file.version}"><i class="fas fa-check mr-1"></i>Jadikan ${newVerFormatted} Aktif</button>`;
        }
        $('#comp-active-actions').html(actionsHtml);

        // Realisasi Warnings
        if (res.realisasi_warnings && res.realisasi_warnings.length > 0) {
            let warnHtml = '';
            res.realisasi_warnings.forEach(function(w) {
                warnHtml += `<li>${w}</li>`;
            });
            $('#comp-warning-list').html(warnHtml);
            $('#comp-warning-box').show();
        } else {
            $('#comp-warning-box').hide();
        }

        // Summary Cards (Format langsung huruf tanpa icon/badge)
        $('#comp-card-old-badge').html(`<span class="font-weight-bold font-tabular" style="color: ${oldColor}; font-size: 0.85rem;">${oldVerFormatted}</span>`);
        $('#comp-card-old-version').text(res.old_file.filename);
        $('#comp-card-old-pagu').text(res.old_file.total_pagu_display);
        $('#comp-card-old-items').html(`<i class="fas fa-box mr-1"></i>${res.old_file.total_items} item rincian`);

        $('#comp-card-new-badge').html(`<span class="font-weight-bold font-tabular" style="color: ${newColor}; font-size: 0.85rem;">${newVerFormatted}</span>`);
        $('#comp-card-new-version').text(res.new_file.filename);
        $('#comp-card-new-pagu').text(res.new_file.total_pagu_display);
        $('#comp-card-new-items').html(`<i class="fas fa-box mr-1"></i>${res.new_file.total_items} item rincian`);

        let diffPagu = res.summary.diff_pagu;
        let diffColor = diffPagu < 0 ? 'text-success' : (diffPagu > 0 ? 'text-danger' : 'text-muted');
        $('#comp-card-diff-pagu').removeClass('text-success text-danger text-muted').addClass(diffColor).text(res.summary.diff_pagu_display);
        $('#comp-card-diff-desc').text(`${res.summary.modified_count} Berubah, ${res.summary.removed_count} Dihapus, ${res.summary.added_count} Ditambah, ${res.summary.unchanged_count} Tetap`);

        // Badges count
        $('#tab-count-modified').text(res.summary.modified_count);
        $('#tab-count-removed').text(res.summary.removed_count);
        $('#tab-count-added').text(res.summary.added_count);

        // 1. Tbody Modified
        let modHtml = '';
        if (res.modified && res.modified.length > 0) {
            res.modified.forEach(function(item, idx) {
                let volClass = item.diff_volume < 0 ? 'text-danger font-weight-bold' : (item.diff_volume > 0 ? 'text-success font-weight-bold' : '');
                let priceClass = item.diff_price < 0 ? 'text-danger font-weight-bold' : (item.diff_price > 0 ? 'text-success font-weight-bold' : '');
                let diffTotalFormatted = (item.diff_total >= 0 ? '+Rp ' : '-Rp ') + new Intl.NumberFormat('id-ID').format(Math.abs(item.diff_total));
                let totalClass = item.diff_total < 0 ? 'text-danger font-weight-bold' : (item.diff_total > 0 ? 'text-success font-weight-bold' : '');

                modHtml += `
                    <tr>
                        <td class="text-center">${idx + 1}</td>
                        <td class="font-tabular">${item.account_code}</td>
                        <td>
                            <div class="font-weight-bold text-dark">${item.description}</div>
                            <small class="text-muted"><i class="fas fa-tag mr-1"></i>${item.kegiatan}</small>
                        </td>
                        <td class="text-center">${item.unit || '-'}</td>
                        <td class="text-center font-tabular ${volClass}">
                            <span class="text-muted">${item.old_volume}</span> &rarr; <span>${item.new_volume}</span>
                            ${item.diff_volume !== 0 ? `<br><small>(${item.diff_volume > 0 ? '+' : ''}${item.diff_volume})</small>` : ''}
                        </td>
                        <td class="text-right font-tabular ${priceClass}">
                            <span class="text-muted">Rp ${new Intl.NumberFormat('id-ID').format(item.old_price)}</span><br>
                            &rarr; <span>Rp ${new Intl.NumberFormat('id-ID').format(item.new_price)}</span>
                        </td>
                        <td class="text-right font-tabular ${totalClass}">
                            <span class="text-muted">Rp ${new Intl.NumberFormat('id-ID').format(item.old_total)}</span><br>
                            &rarr; <span>Rp ${new Intl.NumberFormat('id-ID').format(item.new_total)}</span>
                        </td>
                        <td class="text-right font-tabular ${totalClass}">${diffTotalFormatted}</td>
                    </tr>
                `;
            });
        } else {
            modHtml = '<tr><td colspan="8" class="text-center text-muted py-3">Tidak ada item yang mengalami perubahan volume, harga, atau total anggaran.</td></tr>';
        }
        $('#tbody-comp-modified').html(modHtml);

        // 2. Tbody Removed
        let remHtml = '';
        if (res.removed && res.removed.length > 0) {
            res.removed.forEach(function(item, idx) {
                remHtml += `
                    <tr class="table-danger">
                        <td class="text-center">${idx + 1}</td>
                        <td class="font-tabular">${item.account_code}</td>
                        <td><small class="text-muted">${item.kegiatan}</small></td>
                        <td class="font-weight-bold text-danger">${item.description}</td>
                        <td class="text-center font-tabular">${item.volume}</td>
                        <td class="text-center">${item.unit || '-'}</td>
                        <td class="text-right font-tabular">Rp ${new Intl.NumberFormat('id-ID').format(item.price)}</td>
                        <td class="text-right font-tabular font-weight-bold text-danger">Rp ${new Intl.NumberFormat('id-ID').format(item.total_amount)}</td>
                    </tr>
                `;
            });
        } else {
            remHtml = '<tr><td colspan="8" class="text-center text-muted py-3">Tidak ada item yang dihapus pada revisi ini.</td></tr>';
        }
        $('#tbody-comp-removed').html(remHtml);

        // 3. Tbody Added
        let addHtml = '';
        if (res.added && res.added.length > 0) {
            res.added.forEach(function(item, idx) {
                addHtml += `
                    <tr class="table-success">
                        <td class="text-center">${idx + 1}</td>
                        <td class="font-tabular">${item.account_code}</td>
                        <td><small class="text-muted">${item.kegiatan}</small></td>
                        <td class="font-weight-bold text-success">${item.description}</td>
                        <td class="text-center font-tabular">${item.volume}</td>
                        <td class="text-center">${item.unit || '-'}</td>
                        <td class="text-right font-tabular">Rp ${new Intl.NumberFormat('id-ID').format(item.price)}</td>
                        <td class="text-right font-tabular font-weight-bold text-success">Rp ${new Intl.NumberFormat('id-ID').format(item.total_amount)}</td>
                    </tr>
                `;
            });
        } else {
            addHtml = '<tr><td colspan="8" class="text-center text-muted py-3">Tidak ada item yang ditambahkan baru pada revisi ini.</td></tr>';
        }
        $('#tbody-comp-added').html(addHtml);

        $('#modalCompareKertasKerja').modal('show');
    }

    function loadComparison(fileId, compareWithId = null) {
        Swal.fire({
            title: 'Menganalisis Perubahan...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        let url = "{{ url('bos/files/compare') }}/" + fileId;
        if (compareWithId) {
            url += "?with=" + compareWithId;
        }

        $.ajax({
            url: url,
            type: "GET",
            success: function(res) {
                Swal.close();
                renderComparisonModal(res);
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON ? xhr.responseJSON.message : 'Gagal memuat perbandingan versi.', 'error');
            }
        });
    }

    $(document).on('change', '#select-compare-other', function() {
        let otherId = $(this).val();
        if (otherId && currentCompFileId) {
            loadComparison(currentCompFileId, otherId);
        }
    });

    $(document).on('click', '.btn-compare', function(e) {
        e.stopPropagation();
        let fileId = $(this).data('id');
        loadComparison(fileId);
    });

    $(document).on('click', '.btn-set-active', function(e) {
        e.stopPropagation();
        let fileId = $(this).data('id');
        let version = $(this).data('version');

        Swal.fire({
            title: `Jadikan Versi ${version} sebagai Versi Aktif?`,
            text: "Versi ini akan menjadi acuan resmi perhitungan pada Rencana Belanja dan Transaksi Realisasi Belanja.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            confirmButtonText: 'Ya, Jadikan Aktif!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ url('bos/files/set-active') }}/" + fileId,
                    type: "POST",
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(res) {
                        table.draw(false);
                        if ($('#modalCompareKertasKerja').is(':visible')) {
                            loadComparison(fileId);
                        }
                        Swal.fire('Berhasil!', res.message, 'success');
                    },
                    error: function(xhr) {
                        Swal.fire('Error', 'Gagal mengubah status versi aktif.', 'error');
                    }
                });
            }
        });
    });

    @if(session('comparison_available'))
        Swal.fire({
            title: 'Kertas Kerja Revisi Berhasil Diunggah!',
            html: `File Kertas Kerja <b>{{ session('comparison_available.month') }} {{ session('comparison_available.year') }}</b> versi <b>{{ session('comparison_available.new_version') }}</b> telah diunggah.<br><br>Sistem mendeteksi versi sebelumnya (<b>{{ session('comparison_available.old_version') }}</b>). Apakah Anda ingin langsung melihat <b>komparasi perubahannya</b> sekarang?`,
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#17a2b8',
            confirmButtonText: '<i class="fas fa-code-branch mr-1"></i> Ya, Lihat Komparasi',
            cancelButtonText: 'Nanti Saja'
        }).then((result) => {
            if (result.isConfirmed) {
                loadComparison({{ session('comparison_available.new_id') }}, {{ session('comparison_available.old_id') }});
            }
        });
    @endif

    $(document).on('click', '.btn-edit', function() {
        let id = $(this).data('id');
        let version = $(this).data('version');
        let description = $(this).data('description');

        $('#edit-id').val(id);
        $('#edit-version').val(version);
        $('#edit-description').val(description);
        $('#editModal').modal('show');
    });

    $('#editForm').submit(function(e) {
        e.preventDefault();
        let id = $('#edit-id').val();
        $.ajax({
            url: "{{ url('bos/files') }}/" + id,
            type: "PUT",
            data: $(this).serialize(),
            success: function(res) {
                $('#editModal').modal('hide');
                table.draw(false);
                Swal.fire('Berhasil', res.message, 'success');
            },
            error: function(xhr) {
                Swal.fire('Error', 'Gagal memperbarui data.', 'error');
            }
        });
    });

    $(document).on('click', '.btn-delete', function(e) {
        e.preventDefault();
        let url = $(this).data('url');
        Swal.fire({
            title: 'Hapus File Kertas Kerja?',
            html: `
                <div style="font-size: 0.9rem; line-height: 1.6; text-align: center;">
                    <p class="mb-2 text-dark">
                        Menghapus file ini akan berdampak terHAPUSnya <strong>"Data Kertas Kerja"</strong>, <strong>"Realisasi"</strong> dan <strong>"Panduan Belanja"</strong> yang berasal dari kertas kerja ini.
                    </p>
                    <p class="mb-2 text-dark">
                        Pastikan tidak ada data terpakai pada file yang akan dihapus.
                    </p>
                    <p class="mb-0 text-danger font-weight-bold">
                        Tindakan ini tidak dapat dibatalkan!
                    </p>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
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
                        $('#pdfViewer').hide().attr('src', '');
                        $('#previewPlaceholder').show();
                        $('#previewTitle').html('<i class="fas fa-eye mr-2"></i>Preview PDF');
                        Swal.fire('Terhapus!', res.message, 'success');
                    }
                });
            }
        });
    });
});
</script>
@stop