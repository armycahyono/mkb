@extends('adminlte::page')

@php
    $selectedYear = $selectedYear ?? $activeFiscalYear ?? session('fiscal_year', date('Y'));
    $availableFiscalYears = $availableFiscalYears ?? \App\Helpers\FiscalYearHelper::getAvailableYears();
@endphp

@section('title', 'Kas Operasional ' . $fundingSource)

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap" style="padding: 2px 0;">
    <div>
        <h1 class="page-title"><i class="fas fa-wallet text-success mr-1"></i> Buku Kas Tunai {{ $fundingSource }} <small class="text-muted font-weight-normal" style="font-size: 0.82rem;">(Kas Operasional Mandiri)</small></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-house"></i> Dashboard</a></li>
                <li class="breadcrumb-item"><i class="fas fa-calculator"></i> Penganggaran / {{ $fundingSource }}</li>
                <li class="breadcrumb-item active">Kas Operasional {{ $fundingSource }}</li>
            </ol>
        </nav>
    </div>
    <div class="header-actions mt-2 mt-md-0">
        <button type="button" class="btn btn-sm btn-outline-info mr-1" data-bs-toggle="modal" data-bs-target="#modalKasOpname" data-toggle="modal" data-target="#modalKasOpname">
            <i class="fas fa-calculator mr-1"></i> Kas Opname Brankas
        </button>
        <a href="{{ route('kas-category.index') }}" class="btn btn-sm btn-outline-secondary mr-1" title="Kelola Kategori Transaksi Kas">
            <i class="fas fa-tags mr-1"></i> Kategori
        </a>
        <button type="button" class="btn btn-sm btn-outline-secondary mr-1" data-bs-toggle="modal" data-bs-target="#modalPrintKas" data-toggle="modal" data-target="#modalPrintKas">
            <i class="fas fa-print mr-1"></i> Cetak Buku Kas
        </button>
        <button type="button" class="btn btn-sm btn-success mr-1 shadow-sm font-weight-bold btn-create-masuk" onclick="openCreateModal('masuk')">
            <i class="fas fa-arrow-down mr-1"></i> + Kas Masuk
        </button>
        <button type="button" class="btn btn-sm btn-primary mr-1 shadow-sm font-weight-bold btn-create-belanja" onclick="openCreateModal('keluar', 'belanja_tunai')">
            <i class="fas fa-shopping-cart mr-1"></i> + Belanja Barang
        </button>
        <button type="button" class="btn btn-sm btn-info mr-1 shadow-sm font-weight-bold text-white btn-create-biaya" onclick="openCreateModal('keluar', 'operasional_laporan')">
            <i class="fas fa-file-invoice-dollar mr-1"></i> + Biaya / Kwitansi
        </button>
        <button type="button" class="btn btn-sm btn-warning shadow-sm font-weight-bold text-dark btn-create-kasbon" onclick="openCreateModal('keluar', 'panjar_kerja')">
            <i class="fas fa-hand-holding-usd mr-1"></i> + Berikan Kasbon
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
    #kasTable {
        font-size: 0.8125rem !important;
    }
    #kasTable th {
        font-size: 0.74rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 0.45rem 0.5rem !important;
        vertical-align: middle;
        background-color: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
    }
    #kasTable td {
        padding: 0.45rem 0.5rem !important;
        vertical-align: middle;
    }
    .text-center, th.text-center, td.text-center {
        text-align: center !important;
    }
    .text-right, .text-end, th.text-right, td.text-right, th.text-end, td.text-end {
        text-align: right !important;
    }
    #kasTable td:last-child {
        text-align: right !important;
    }
    #kasTable td:last-child .btn-group {
        display: inline-flex !important;
        margin-left: auto !important;
    }
    .badge-masuk { background-color: #d1fae5; color: #065f46; font-weight: 600; }
    .badge-keluar { background-color: #fee2e2; color: #991b1b; font-weight: 600; }
    .badge-pending { background-color: #fef3c7; color: #92400e; font-weight: 700; }
    .badge-settled { background-color: #e0e7ff; color: #3730a3; font-weight: 600; }

    /* Compact Button */
    .btn-xs {
        padding: 2px 7px !important;
        font-size: 0.75rem !important;
        line-height: 1.25 !important;
        border-radius: 4px !important;
    }

    /* Modal Close Button Clean Edge & No Border */
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
</style>
@stop

@section('content')
{{-- Stat Cards --}}
<div class="row mb-3">
    <div class="col-xl-3 col-md-6 mb-2">
        <div class="card stat-card bg-white p-3 border-left border-success" style="border-left-width: 4px !important;">
            <div class="stat-label">Saldo Kas Tunai Saat Ini</div>
            <div class="stat-value text-success mt-1" id="statSaldoKas">Rp {{ number_format($saldoKas, 0, ',', '.') }}</div>
            <small class="text-muted">
                @if(($saldoAwal ?? 0) != 0)
                    <i class="fas fa-history text-info mr-1"></i> Termasuk Saldo Awal Rp {{ number_format($saldoAwal, 0, ',', '.') }}
                @else
                    Total uang fisik tunai di bendahara
                @endif
            </small>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-2">
        <div class="card stat-card bg-white p-3 border-left border-warning" style="border-left-width: 4px !important;">
            <div class="d-flex justify-content-between align-items-center">
                <div class="stat-label">Kasbon Menggantung (Pending)</div>
                <a href="{{ route($routePrefix . '.kas-tunai.kasbon') }}" class="badge badge-warning text-dark px-2 py-1 shadow-xs" title="Buka Menu Tanggungan Kasbon">
                    Kelola <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
            <div class="stat-value text-warning mt-1" id="statTotalPanjarPending">Rp {{ number_format($totalPanjarPending, 0, ',', '.') }}</div>
            <small class="text-muted">
                <span class="badge badge-warning" id="statCountPanjarPending">{{ $countPanjarPending }} transaksi</span> belum SPJ &bull; 
                <a href="{{ route($routePrefix . '.kas-tunai.kasbon') }}" class="font-weight-bold text-primary">Lihat Tanggungan &rarr;</a>
            </small>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-2">
        <div class="card stat-card bg-white p-3 border-left border-info" style="border-left-width: 4px !important;">
            <div class="stat-label">Kas Siap Pakai (Bebas)</div>
            <div class="stat-value text-info mt-1" id="statKasSiapPakai">Rp {{ number_format($kasSiapPakai, 0, ',', '.') }}</div>
            <small class="text-muted">Saldo kas dikurangi seluruh kasbon pending</small>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-2">
        <div class="card stat-card bg-white p-3 border-left border-secondary" style="border-left-width: 4px !important;">
            <div class="stat-label">Mutasi Tahun {{ $selectedYear !== 'all' ? $selectedYear : 'Semua' }}</div>
            <div class="d-flex justify-content-between mt-1">
                <span class="text-success font-weight-bold" id="statTotalMasuk" style="font-size: 0.95rem;">+ {{ number_format($totalMasuk, 0, ',', '.') }}</span>
                <span class="text-danger font-weight-bold" id="statTotalKeluar" style="font-size: 0.95rem;">- {{ number_format($totalKeluar, 0, ',', '.') }}</span>
            </div>
            <small class="text-muted">
                @if(($saldoAwal ?? 0) != 0)
                    Saldo Pindahan Thn Lalu: Rp {{ number_format($saldoAwal, 0, ',', '.') }}
                @else
                    Akumulasi mutasi kas {{ $fundingSource }}
                @endif
            </small>
        </div>
    </div>
</div>

{{-- Main Card --}}
<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-bottom p-2 d-flex justify-content-between align-items-center flex-wrap">
        <div class="d-flex align-items-center">
            <h5 class="font-weight-bold text-dark mb-0" style="font-size: 0.95rem;">
                <i class="fas fa-book text-primary mr-1"></i> Mutasi Buku Kas Tunai {{ $fundingSource }}
            </h5>
        </div>

        {{-- Quick Filters --}}
        <div class="d-flex align-items-center flex-wrap ms-auto ml-auto mt-2 mt-md-0 filter-wrapper" style="gap: 8px;">
            <input type="hidden" id="filterYear" value="{{ $selectedYear }}">
            <span class="badge bg-primary text-white font-weight-normal d-inline-flex align-items-center shadow-xs" style="height: 31px; font-size: 0.8125rem; padding: 0 10px; border-radius: 4px;" title="Tahun Anggaran aktif yang dipilih pada Navbar">
                <i class="fas fa-calendar-alt mr-1 me-1"></i> Tahun Anggaran {{ $selectedYear }}
            </span>
            <div class="input-group input-group-sm shadow-xs" style="width: auto;">
                <span class="input-group-text bg-light text-muted font-weight-bold" style="height: 31px; font-size: 0.8125rem; border-radius: 4px 0 0 4px;">
                    <i class="fas fa-filter text-info mr-1 me-1"></i> Kategori:
                </span>
                <select id="filterCategory" class="form-select form-select-sm font-weight-bold text-dark" style="height: 31px; font-size: 0.8125rem; border-radius: 0 4px 4px 0; cursor: pointer; max-width: 220px; text-overflow: ellipsis;">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $key => $cat)
                        <option value="{{ $key }}">{{ $cat['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="input-group input-group-sm shadow-xs" style="width: auto;">
                <span class="input-group-text bg-light text-muted font-weight-bold" style="height: 31px; font-size: 0.8125rem; border-radius: 4px 0 0 4px;">
                    <i class="fas fa-calendar-check text-info mr-1 me-1"></i> Bulan:
                </span>
                @php
                    $kasIndonesianMonths = [
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                    ];
                @endphp
                <select id="filterMonth" class="form-select form-select-sm font-weight-bold text-dark" style="height: 31px; font-size: 0.8125rem; border-radius: 0 4px 4px 0; cursor: pointer; max-width: 150px; text-overflow: ellipsis;">
                    <option value="">Semua Bulan</option>
                    @foreach($kasIndonesianMonths as $mNum => $mName)
                        <option value="{{ $mNum }}">{{ $mName }}</option>
                    @endforeach
                </select>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary d-md-none shadow-xs" id="btnToggleMobileCards" title="Ganti Tampilan Kartu / Tabel" style="height: 31px; font-size: 0.8rem;">
                <i class="fas fa-table mr-1"></i> <span id="textToggleView">Mode Tabel</span>
            </button>
        </div>
    </div>

    <div class="card-body p-2">
        <div class="table-responsive">
            <table class="table table-hover table-striped w-100 table-mobile-cards" id="kasTable">
                <thead>
                    <tr>
                        <th style="width: 4%;" class="text-center">NO</th>
                        <th style="width: 7%;" class="text-center">ID TRX</th>
                        <th style="width: 8%;" class="text-center">TANGGAL</th>
                        <th style="width: 11%;">KATEGORI</th>
                        <th style="width: 25%;">URAIAN TRANSAKSI</th>
                        <th style="width: 20%;">PENANGGUNG JAWAB & TOKO</th>
                        <th style="width: 9%;" class="text-right">PENERIMAAN (Rp)</th>
                        <th style="width: 9%;" class="text-right">PENGELUARAN (Rp)</th>
                        <th style="width: 7%;" class="text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

{{-- MODAL 1: Tambah Kas Masuk / Keluar --}}
<div class="modal fade" id="modalCreateKas" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form id="formCreateKas" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="edit_kas_id" id="editKasId" value="">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white py-2 px-3 d-flex align-items-center justify-content-between">
                    <h5 class="modal-title font-weight-bold m-0" id="modalCreateTitle">
                        <i class="fas fa-plus-circle mr-1"></i> Catat Transaksi Kas Tunai
                    </h5>
                    <button type="button" class="close text-white btn-dismiss-modal" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-3">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="small font-weight-bold">Jenis Arus Kas <span class="text-danger">*</span></label>
                            <select name="transaction_type" id="inputTransType" class="form-control form-control-sm" required>
                                <option value="masuk">Kas Masuk (Penerimaan / Uang Masuk)</option>
                                <option value="keluar">Kas Keluar (Pengeluaran / Kasbon / Belanja)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small font-weight-bold">Kategori Transaksi <span class="text-danger">*</span></label>
                            <select name="category" id="inputCategory" class="form-control form-control-sm" required>
                                {{-- Populated by JS based on type --}}
                            </select>
                        </div>
                    </div>

                    <input type="hidden" name="parent_kas_id" id="inputParentKasId">

                    {{-- 1. Pilihan Kasbon Induk jika Kategori: Kembalian Sisa Kasbon (sisa_panjar) --}}
                    <div class="form-group mb-3 p-2 border border-success rounded bg-light" id="containerSelectParentPanjar" style="display: none;">
                        <label class="small font-weight-bold text-success mb-1">
                            <i class="fas fa-hand-holding-usd mr-1"></i> Pilih Kasbon yang Dikembalikan (Pending Panjar) <span class="text-danger">*</span>
                        </label>
                        <select id="selectParentPanjarId" class="form-control form-control-sm">
                            <option value="">-- Pilih Kasbon yang Dikembalikan --</option>
                            @foreach($pendingPanjars as $pp)
                                <option value="{{ $pp->id }}" 
                                        data-guru-id="{{ $pp->guru_id }}" 
                                        data-guru-name="{{ $pp->guru?->name ?? $pp->person_name }}" 
                                        data-amount="{{ $pp->amount }}" 
                                        data-desc="{{ $pp->description }}">
                                    #{{ $pp->id }} - {{ $pp->guru?->name ?? $pp->person_name }} (Kasbon: Rp {{ number_format($pp->amount, 0, ',', '.') }} - {{ $pp->description }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-1">Pilih kasbon agar sisa uang kembalian otomatis tertaut ke kasbon awal dan status kasbon ditandai selesai.</small>
                    </div>

                    {{-- 2. Pilihan Dana Talangan Induk jika Kategori: Pengembalian Dana Talangan (bayar_talangan) --}}
                    <div class="form-group mb-3 p-2 border border-primary rounded bg-light" id="containerSelectParentTalangan" style="display: none;">
                        <label class="small font-weight-bold text-primary mb-1">
                            <i class="fas fa-undo-alt mr-1"></i> Pilih Penerimaan Dana Talangan yang Dilunasi <span class="text-danger">*</span>
                        </label>
                        <select id="selectParentTalanganId" class="form-control form-control-sm">
                            <option value="">-- Pilih Dana Talangan yang Dilunasi --</option>
                            @foreach($danaTalangans as $dt)
                                <option value="{{ $dt->id }}" 
                                        data-guru-id="{{ $dt->guru_id }}" 
                                        data-guru-name="{{ $dt->guru?->name ?? $dt->person_name }}" 
                                        data-amount="{{ $dt->amount }}" 
                                        data-desc="{{ $dt->description }}">
                                    #{{ $dt->id }} - Rp {{ number_format($dt->amount, 0, ',', '.') }} ({{ $dt->description }} - {{ $dt->guru?->name ?? $dt->person_name }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-1">Pilih data penerimaan dana talangan awal yang ingin dikembalikan/dilunasi.</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="small font-weight-bold">Tanggal Transaksi <span class="text-danger">*</span></label>
                            <input type="date" name="transaction_date" id="inputTransDate" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small font-weight-bold">Nominal (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text font-weight-bold bg-light">Rp</span>
                                </div>
                                <input type="text" name="amount" id="inputAmount" class="form-control form-control-sm font-weight-bold rupiah-input" placeholder="0" autocomplete="off" required>
                            </div>
                        </div>
                    </div>

                    {{-- Hubungkan ke Dokumen Terkait --}}
                    <div class="card bg-light border p-2 mb-3" id="cardDokumenTerkait">
                        <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
                            <span class="font-weight-bold small text-dark"><i class="fas fa-link text-info mr-1"></i> Kaitkan ke Dokumen Terkait (Opsional):</span>
                            <div class="d-flex align-items-center flex-wrap mt-1 mt-md-0" style="gap: 10px;">
                                <div class="d-flex align-items-center">
                                    <label class="small text-muted mb-0 mr-1" style="font-size: 0.76rem;"><i class="fas fa-calendar-alt"></i> Tahun:</label>
                                    <select id="selectDocYear" class="form-control form-control-sm py-0 px-1" style="height: 25px; width: 95px; font-size: 0.76rem;">
                                        @foreach($availableFiscalYears as $y)
                                            <option value="{{ $y }}" {{ (string)$selectedYear === (string)$y ? 'selected' : '' }}>{{ $y }}</option>
                                        @endforeach
                                        <option value="all">Semua</option>
                                    </select>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="checkIncludeSettled">
                                    <label class="custom-control-label text-muted" for="checkIncludeSettled" style="font-size: 0.76rem; cursor: pointer;">Tampilkan yang sudah lunas</label>
                                </div>
                                <button type="button" id="btnReloadDocs" class="btn btn-xs btn-outline-secondary" title="Segarkan daftar dokumen">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-2" id="groupRencanaBelanja">
                                <label class="small font-weight-bold text-muted">Nota Rencana Belanja</label>
                                <select name="bos_rencana_belanja_id" id="inputRencanaBelanjaId" class="form-control form-control-sm">
                                    <option value="">-- Tidak Terkait --</option>
                                    @foreach($rencanaBelanjas as $rb)
                                        <option value="{{ $rb->id }}" 
                                                data-amount="{{ $rb->total_amount }}" 
                                                data-supplier="{{ $rb->supplier_name }}" 
                                                data-nota="{{ $rb->no_nota }}" 
                                                data-title="{{ $rb->title }}">
                                            {{ $rb->no_nota }} - {{ $rb->supplier_name ?: $rb->title }} (Rp {{ number_format($rb->total_amount, 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted d-block mt-1">Jika untuk salah satu nota belanja toko tertentu.</small>
                            </div>
                            <div class="col-md-6 mb-2" id="groupPerjadinItem">
                                <label class="small font-weight-bold text-muted">Nota / Kwitansi Perjadin</label>
                                <select name="perjadin_item_id" id="inputPerjadinItemId" class="form-control form-control-sm">
                                    <option value="">-- Tidak Terkait --</option>
                                    @foreach($perjadinItems as $pi)
                                        <option value="{{ $pi->id }}" 
                                                data-amount="{{ $pi->net_amount }}" 
                                                data-guru-id="{{ $pi->guru_id }}" 
                                                data-guru-name="{{ $pi->guru?->name }}" 
                                                data-receipt="{{ $pi->receipt_number }}" 
                                                data-desc="Perjadin: {{ $pi->perjadin?->activity_purpose }} ({{ $pi->guru?->name }})">
                                            {{ $pi->receipt_number ?: 'KWT-'.$pi->id }} - {{ $pi->guru?->name }} (Rp {{ number_format($pi->net_amount, 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted d-block mt-1">Jika untuk kwitansi perjadin guru tertentu.</small>
                            </div>
                        </div>
                    </div>

                    <div class="row" id="rowPersons">
                        <div class="col-md-6 mb-3" id="groupGuruId">
                            <label class="small font-weight-bold" id="labelGuruId">Penanggung Jawab / Pelaksana Transaksi <span class="text-danger">*</span></label>
                            <select name="guru_id" id="inputGuruId" class="form-control form-control-sm select2" style="width: 100%;" required>
                                <option value="">-- Pilih Guru / Bendahara --</option>
                                @foreach($gurus as $g)
                                    <option value="{{ $g->id }}">{{ $g->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1" id="noteGuruId">Jika belanja sendiri oleh Bendahara, pilih nama Bendahara.</small>
                        </div>
                        <div class="col-md-6 mb-3" id="groupPersonName">
                            <label class="small font-weight-bold" id="labelPersonName">Nama Toko / Rekanan / Pihak Penyedia</label>
                            <input type="text" name="person_name" id="inputPersonName" class="form-control form-control-sm" placeholder="Contoh: CV. Graha Media / Toko Barokah / Bengkel">
                            <small class="text-muted d-block mt-1" id="notePersonName">Kosongkan jika kasbon murni atau tidak melibatkan toko.</small>
                        </div>
                    </div>

                    <div class="form-group mb-3" id="groupDescription">
                        <label class="small font-weight-bold" id="labelDescription">Uraian / Keterangan Keperluan <span class="text-danger">*</span></label>
                        <textarea name="description" id="inputDescription" class="form-control form-control-sm" rows="2" placeholder="Contoh: Belanja bahan praktik kelistrikan / Kasbon belanja konsumsi rapat" required></textarea>
                    </div>

                    {{-- Rincian Belanja --}}
                    <div class="card border border-info mb-3" id="sectionCreateItems">
                        <div class="card-header bg-light py-1 px-3 d-flex justify-content-between align-items-center" style="min-height: 34px;">
                            <span class="font-weight-bold small text-primary">
                                <i class="fas fa-receipt mr-1"></i> Rincian Belanja Barang / Nota
                            </span>
                            <button type="button" class="btn btn-xs btn-outline-primary" id="btnAddCreateItem">
                                <i class="fas fa-plus mr-1"></i> Tambah Barang
                            </button>
                        </div>
                        <div class="card-body p-2" style="max-height: 250px; overflow-y: auto;">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0" id="tableCreateItems" style="font-size: 0.8rem;">
                                    <thead class="bg-light text-center">
                                        <tr>
                                            <th style="width: 32%;">Nama Barang <span class="text-danger">*</span></th>
                                            <th style="width: 14%;">Qty</th>
                                            <th style="width: 14%;">Satuan</th>
                                            <th style="width: 18%;">Harga (Rp)</th>
                                            <th style="width: 17%;">Subtotal (Rp)</th>
                                            <th style="width: 5%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyCreateItems">
                                        {{-- Dynamic rows --}}
                                    </tbody>
                                    <tfoot id="tfootCreateItems" style="display: none;">
                                        <tr class="bg-light font-weight-bold">
                                            <td colspan="4" class="text-right py-1">Total Rincian Belanja:</td>
                                            <td class="text-right text-primary py-1" id="totalCreateItemsDisplay">Rp 0</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <div id="emptyCreateItemsNote" class="text-center text-muted py-2 small">
                                <i class="fas fa-info-circle mr-1"></i> Belum ada rincian belanja. Klik <strong>+ Tambah Barang</strong> untuk mencatat item belanja.
                            </div>
                        </div>
                    </div>

                    <div class="row" id="rowEvidence">
                        <div class="col-md-6 mb-3" id="groupEvidenceNumber">
                            <label class="small font-weight-bold" id="labelEvidenceNumber">Nomor Bukti / Kuitansi Fisik</label>
                            <input type="text" name="evidence_number" id="inputEvidenceNumber" class="form-control form-control-sm" placeholder="Contoh: KWT-001 / Nota #1234">
                        </div>
                        <div class="col-md-6 mb-3" id="groupReceiptFile">
                            <label class="small font-weight-bold" id="labelReceiptFile">Upload Foto / Scan Nota Bukti (Opsional)</label>
                            <input type="file" name="receipt_file" id="inputReceiptFile" class="form-control-file form-control-sm" accept="image/*,application/pdf">
                            <div id="currentReceiptFileContainer" class="mt-1 small" style="display: none;"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm btn-dismiss-modal" data-dismiss="modal" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm font-weight-bold" id="btnSubmitKas">
                        <i class="fas fa-save mr-1"></i> <span id="textSubmitKas">Simpan Transaksi</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- MODAL 2: Selesaikan Kasbon / Panjar --}}
@include('kas_tunai.partials.modal_settle')


{{-- MODAL 3: Kas Opname (Pencocokan Uang Fisik Brankas) --}}
<div class="modal fade" id="modalKasOpname" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form action="{{ route($routePrefix . '.kas-tunai.opname') }}" method="POST" target="_blank">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-info text-white py-2 px-3 d-flex align-items-center justify-content-between">
                    <h5 class="modal-title font-weight-bold m-0">
                        <i class="fas fa-calculator mr-1"></i> Kas Opname (Penghitungan Fisik Uang di Brankas)
                    </h5>
                    <button type="button" class="close text-white btn-dismiss-modal" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-3">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="small font-weight-bold">Tanggal Opname</label>
                            <input type="date" name="opname_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small font-weight-bold">Saldo Buku Sistem Saat Ini</label>
                            <div class="form-control form-control-sm bg-light font-weight-bold text-success" id="opnameSaldoBukuDisplay" data-saldo="{{ $saldoKas }}">
                                Rp {{ number_format($saldoKas, 0, ',', '.') }}
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-3">
                            <thead class="bg-light text-center">
                                <tr>
                                    <th>PECAHAN UANG</th>
                                    <th width="140">JUMLAH (LEMBAR)</th>
                                    <th width="180">TOTAL NILAI (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $denoms = [100000, 50000, 20000, 10000, 5000, 2000, 1000];
                                @endphp
                                @foreach($denoms as $d)
                                <tr>
                                    <td class="font-weight-bold text-center">Rp {{ number_format($d, 0, ',', '.') }}</td>
                                    <td>
                                        <input type="number" name="pecahan_{{ $d }}" class="form-control form-control-sm text-center input-denom" data-denom="{{ $d }}" value="0" min="0">
                                    </td>
                                    <td class="text-right font-weight-bold denom-subtotal" id="subtotal_{{ $d }}">Rp 0</td>
                                </tr>
                                @endforeach
                                <tr>
                                    <td class="font-weight-bold text-center">Uang Pecahan Koin / Logam</td>
                                    <td>
                                        <input type="number" name="pecahan_koin" id="inputKoin" class="form-control form-control-sm text-center" value="0" min="0" placeholder="Total nilai koin">
                                    </td>
                                    <td class="text-right font-weight-bold" id="subtotal_koin">Rp 0</td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-light">
                                <tr>
                                    <th colspan="2" class="text-right font-weight-bold">TOTAL FISIK DI BRANKAS:</th>
                                    <th class="text-right font-weight-bold text-primary" style="font-size: 1.1rem;" id="totalFisikDisplay">Rp 0</th>
                                </tr>
                                <tr>
                                    <th colspan="2" class="text-right font-weight-bold">STATUS SELISIH KAS:</th>
                                    <th class="text-right font-weight-bold" id="selisihDisplay">-</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="form-group mb-0">
                        <label class="small font-weight-bold">Catatan Hasil Pemeriksaan Kas Opname</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Contoh: Keadaan kas tunai dalam kondisi baik dan cocok dengan saldo pembukuan."></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm btn-dismiss-modal" data-dismiss="modal" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-info btn-sm font-weight-bold">
                        <i class="fas fa-print mr-1"></i> Cetak Berita Acara Kas Opname
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- MODAL 4: Cetak Buku Kas Tunai --}}
<div class="modal fade" id="modalPrintKas" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="{{ route($routePrefix . '.kas-tunai.print') }}" method="GET" target="_blank">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white py-2 px-3 d-flex align-items-center justify-content-between">
                    <h5 class="modal-title font-weight-bold m-0">
                        <i class="fas fa-print mr-1"></i> Cetak Buku Pembantu Kas Tunai
                    </h5>
                    <button type="button" class="close text-white btn-dismiss-modal" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-3">
                    <div class="form-group mb-3">
                        <label class="small font-weight-bold">Pilih Bulan (Opsional)</label>
                        <select name="month" class="form-control form-control-sm">
                            <option value="">-- Cetak Seluruh Bulan --</option>
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $m == date('n') ? 'selected' : '' }}>
                                    {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label class="small font-weight-bold">Pilih Tahun</label>
                        <select name="year" class="form-control form-control-sm">
                            @for($y = date('Y'); $y >= date('Y') - 3; $y--)
                                <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm btn-dismiss-modal" data-dismiss="modal" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-secondary btn-sm font-weight-bold">
                        <i class="fas fa-print mr-1"></i> Buka Format Cetak
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- MODAL 5: Rincian Belanja & Detail Terpadu Transaksi Kas --}}
@include('kas_tunai.partials.modal_receipt_items')


{{-- Mobile Quick Receipt Entry FAB & Modal --}}
@include('kas_tunai.partials.mobile_fab_entry')

{{-- Modal Popup Preview Bukti Foto / Nota --}}
@include('kas_tunai.partials.modal_preview_receipt')

@stop

@section('js')
<script>
    const categoriesData = @json($categories);
    let kasTable = null;
    let panjarPendingTable = null;
    let currentPanjarAmount = 0;

    // Suppress intrusive browser alert modals from DataTables on transient aborts/network drops
    if (window.jQuery && $.fn && $.fn.dataTable) {
        $.fn.dataTable.ext.errMode = 'none';
    }

    // Resilient Modal Controller (Bootstrap 4, Bootstrap 5, & Fallback)
    window.showKasModal = function(selector) {
        let el = typeof selector === 'string' ? document.querySelector(selector) : selector;
        if (!el) return;

        if (window.bootstrap && typeof window.bootstrap.Modal !== 'undefined') {
            try {
                let modal = bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(el) : (bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el));
                modal.show();
                return;
            } catch (e) {
                console.warn('Bootstrap modal error:', e);
            }
        }

        if (window.jQuery && typeof $(el).modal === 'function') {
            try {
                $(el).modal('show');
                return;
            } catch (e) {
                console.warn('jQuery modal error:', e);
            }
        }

        // Fallback
        $(el).addClass('show').css('display', 'block').removeAttr('aria-hidden').attr('aria-modal', 'true');
        if (!$('.modal-backdrop').length) {
            $('body').addClass('modal-open').append('<div class="modal-backdrop fade show custom-kas-backdrop"></div>');
        }
    };

    window.hideKasModal = function(selector) {
        let el = typeof selector === 'string' ? document.querySelector(selector) : selector;
        if (!el) return;

        if (window.bootstrap && typeof window.bootstrap.Modal !== 'undefined') {
            try {
                let modal = bootstrap.Modal.getInstance(el);
                if (modal) {
                    modal.hide();
                }
            } catch (e) {}
        }

        if (window.jQuery && typeof $(el).modal === 'function') {
            try {
                $(el).modal('hide');
            } catch (e) {}
        }

        $(el).removeClass('show').css('display', 'none').attr('aria-hidden', 'true').removeAttr('aria-modal');
        $('body').removeClass('modal-open');
        $('.modal-backdrop').remove();
    };

    // Global Load Available Documents (AJAX)
    window.loadAvailableDocuments = function(year, includeSettled, selectedValues = {}) {
        let $reloadBtn = $('#btnReloadDocs');
        $reloadBtn.addClass('disabled').find('i').addClass('fa-spin');

        $.ajax({
            url: "{{ route($routePrefix . '.kas-tunai.available-documents', [], false) }}",
            type: 'GET',
            data: {
                year: year,
                include_settled: includeSettled ? 1 : 0
            },
            success: function(res) {
                // 1. Rencana Belanja (Create & Settle modal)
                let $rbSelect = $('#inputRencanaBelanjaId');
                let $settleRbSelect = $('#settleRencanaBelanjaId');
                let currentRb = selectedValues.bos_rencana_belanja_id || $rbSelect.val();
                let currentSettleRb = selectedValues.settle_bos_rencana_belanja_id || $settleRbSelect.val();

                $rbSelect.empty().append('<option value="">-- Tidak Terkait --</option>');
                $settleRbSelect.empty().append('<option value="">-- Tidak Terkait --</option>');

                if (res.rencana_belanjas && res.rencana_belanjas.length > 0) {
                    res.rencana_belanjas.forEach(function(item) {
                        let isSel = String(item.id) === String(currentRb) ? 'selected' : '';
                        let isSettleSel = String(item.id) === String(currentSettleRb) ? 'selected' : '';
                        $rbSelect.append(`<option value="${item.id}" data-amount="${item.total_amount}" data-supplier="${item.supplier_name || ''}" data-nota="${item.no_nota || ''}" data-title="${item.title || ''}" ${isSel}>${item.label}</option>`);
                        $settleRbSelect.append(`<option value="${item.id}" data-amount="${item.total_amount}" data-supplier="${item.supplier_name || ''}" data-nota="${item.no_nota || ''}" data-title="${item.title || ''}" ${isSettleSel}>${item.label}</option>`);
                    });
                }

                // 3. Kwitansi Perjadin (Create & Settle modal)
                let $piSelect = $('#inputPerjadinItemId');
                let $settlePiSelect = $('#settlePerjadinItemId');
                let currentPi = selectedValues.perjadin_item_id || $piSelect.val();
                let currentSettlePi = selectedValues.settle_perjadin_item_id || $settlePiSelect.val();

                $piSelect.empty().append('<option value="">-- Tidak Terkait --</option>');
                $settlePiSelect.empty().append('<option value="">-- Tidak Terkait --</option>');

                if (res.perjadin_items && res.perjadin_items.length > 0) {
                    res.perjadin_items.forEach(function(item) {
                        let isSel = String(item.id) === String(currentPi) ? 'selected' : '';
                        let isSettleSel = String(item.id) === String(currentSettlePi) ? 'selected' : '';
                        $piSelect.append(`<option value="${item.id}" data-amount="${item.net_amount}" data-guru-id="${item.guru_id || ''}" data-guru-name="${item.guru_name || ''}" data-receipt="${item.receipt_number || ''}" data-desc="Perjadin: ${item.activity_purpose} (${item.guru_name})" ${isSel}>${item.label}</option>`);
                        $settlePiSelect.append(`<option value="${item.id}" data-amount="${item.net_amount}" data-guru-id="${item.guru_id || ''}" data-guru-name="${item.guru_name || ''}" data-receipt="${item.receipt_number || ''}" data-desc="Perjadin: ${item.activity_purpose} (${item.guru_name})" ${isSettleSel}>${item.label}</option>`);
                    });
                }

                // 4. Kasbon Pending (Pilihan kembalian sisa panjar)
                if (res.pending_panjars) {
                    let $ppSelect = $('#selectParentPanjarId');
                    let currentPp = selectedValues.parent_kas_id || $ppSelect.val();
                    $ppSelect.empty().append('<option value="">-- Pilih Kasbon yang Dikembalikan --</option>');
                    res.pending_panjars.forEach(function(pp) {
                        let isSel = String(pp.id) === String(currentPp) ? 'selected' : '';
                        $ppSelect.append(`<option value="${pp.id}" data-guru-id="${pp.guru_id || ''}" data-guru-name="${pp.guru_name || ''}" data-amount="${pp.amount}" data-remaining="${pp.remaining_amount}" data-desc="${pp.description}" ${isSel}>${pp.label}</option>`);
                    });
                }
            },
            error: function(err) {
                console.error('Gagal mengambil daftar dokumen:', err);
            },
            complete: function() {
                $reloadBtn.removeClass('disabled').find('i').removeClass('fa-spin');
            }
        });
    };

    let createItemIndex = 0;
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

        if (count > 0) {
            $(`#empty${prefix}ItemsNote`).hide();
            $(`#tfoot${prefix}Items`).show();
        } else {
            $(`#empty${prefix}ItemsNote`).show();
            $(`#tfoot${prefix}Items`).hide();
        }

        if (prefix === 'Create') {
            if (count > 0) {
                $('#inputAmount').val(grandTotal.toLocaleString('id-ID'));
            }
        } else if (prefix === 'Settle') {
            if (count > 0) {
                $('#inputNotaAmount').val(grandTotal.toLocaleString('id-ID'));
                $('#inputRealAmount').val(grandTotal.toLocaleString('id-ID'));
                if (typeof calculateSettleBreakdown === 'function') {
                    calculateSettleBreakdown();
                }
            }
        }
    };

    // Global Open Create Modal
    window.openCreateModal = function(type, targetCategory = null) {
        $('#formCreateKas')[0].reset();
        $('#editKasId').val('');
        $('#currentReceiptFileContainer').hide().empty();
        $('#btnSubmitKas').prop('disabled', false).html('<i class="fas fa-save mr-1"></i> <span id="textSubmitKas">Simpan Transaksi</span>');
        $('#inputAmount').val('');
        $('#inputRencanaBelanjaId').val('');
        $('#inputPerjadinItemId').val('');
        $('#inputGuruId').val('').trigger('change');
        $('#inputParentKasId').val('');
        $('#selectParentPanjarId').val('');
        $('#selectParentTalanganId').val('');
        $('#containerSelectParentPanjar').hide();
        $('#containerSelectParentTalangan').hide();
        $('#inputTransType').val(type);
        populateCategories(type);

        if (targetCategory) {
            $('#inputCategory').val(targetCategory);
            handleCategoryChange(targetCategory);
        }

        // Reset items
        $('#tbodyCreateItems').empty();
        $('#tfootCreateItems').hide();
        $('#emptyCreateItemsNote').show();
        $('#totalCreateItemsDisplay').text('Rp 0');
        createItemIndex = 0;

        let currentFilterYear = $('#filterYear').val();
        if (currentFilterYear) {
            $('#selectDocYear').val(currentFilterYear);
        }
        loadAvailableDocuments($('#selectDocYear').val(), $('#checkIncludeSettled').is(':checked'));

        if (type === 'masuk') {
            $('#modalCreateKas .modal-header').removeClass('bg-primary bg-info bg-warning bg-danger').addClass('bg-success text-white');
            $('#modalCreateTitle').html('<i class="fas fa-arrow-down text-white mr-1"></i> Catat Kas Masuk (Penerimaan)');
        } else if (targetCategory === 'belanja_tunai') {
            $('#modalCreateKas .modal-header').removeClass('bg-success bg-info bg-warning bg-danger').addClass('bg-primary text-white');
            $('#modalCreateTitle').html('<i class="fas fa-shopping-cart text-white mr-1"></i> Catat Belanja Barang Tunai (Ada Nota Toko)');
        } else if (targetCategory === 'operasional_laporan') {
            $('#modalCreateKas .modal-header').removeClass('bg-success bg-primary bg-warning bg-danger').addClass('bg-info text-white');
            $('#modalCreateTitle').html('<i class="fas fa-file-invoice-dollar text-white mr-1"></i> Catat Biaya Operasional / Kwitansi');
        } else if (targetCategory === 'panjar_kerja') {
            $('#modalCreateKas .modal-header').removeClass('bg-success bg-primary bg-info bg-danger').addClass('bg-warning text-dark');
            $('#modalCreateTitle').html('<i class="fas fa-hand-holding-usd text-dark mr-1"></i> Berikan Kasbon / Panjar Belanja Staf');
        } else {
            $('#modalCreateKas .modal-header').removeClass('bg-success bg-info bg-warning bg-danger').addClass('bg-primary text-white');
            $('#modalCreateTitle').html('<i class="fas fa-arrow-up text-white mr-1"></i> Catat Kas Keluar / Kasbon (Pengeluaran)');
        }

        showKasModal('#modalCreateKas');
    };

    // Global Open Edit Modal
    window.openEditModal = function(id) {
        let url = "{{ url($routePrefix . '/kas-tunai') }}/" + id + "/edit";

        $('#formCreateKas')[0].reset();
        $('#editKasId').val(id);
        $('#currentReceiptFileContainer').hide().empty();
        $('#tbodyCreateItems').empty();
        $('#tfootCreateItems').hide();
        $('#emptyCreateItemsNote').show();
        $('#totalCreateItemsDisplay').text('Rp 0');
        createItemIndex = 0;

        $('#modalCreateTitle').html('<i class="fas fa-spinner fa-spin mr-1"></i> Memuat Data Transaksi #' + id + '...');
        $('#btnSubmitKas').prop('disabled', false).html('<i class="fas fa-save mr-1"></i> <span id="textSubmitKas">Simpan Perubahan</span>');
        showKasModal('#modalCreateKas');

        $.ajax({
            url: url,
            type: 'GET',
            success: function(res) {
                $('#editKasId').val(res.id);
                $('#inputTransType').val(res.transaction_type);
                populateCategories(res.transaction_type);
                $('#inputCategory').val(res.category);
                handleCategoryChange(res.category);

                $('#inputParentKasId').val(res.parent_kas_id || '');
                if (res.parent_kas_id) {
                    if (res.category === 'sisa_panjar') {
                        if ($('#selectParentPanjarId option[value="' + res.parent_kas_id + '"]').length === 0) {
                            let labelText = '#' + res.parent_kas_id + (res.parent_kas ? ' - ' + (res.parent_kas.description || '') : ' (Kasbon Terkait)');
                            $('#selectParentPanjarId').append(new Option(labelText, res.parent_kas_id, true, true));
                        } else {
                            $('#selectParentPanjarId').val(res.parent_kas_id);
                        }
                    } else if (res.category === 'bayar_talangan') {
                        if ($('#selectParentTalanganId option[value="' + res.parent_kas_id + '"]').length === 0) {
                            let labelText = '#' + res.parent_kas_id + (res.parent_kas ? ' - ' + (res.parent_kas.description || '') : ' (Talangan Terkait)');
                            $('#selectParentTalanganId').append(new Option(labelText, res.parent_kas_id, true, true));
                        } else {
                            $('#selectParentTalanganId').val(res.parent_kas_id);
                        }
                    }
                }

                $('#inputTransDate').val(res.transaction_date);
                $('#inputAmount').val(Number(res.amount).toLocaleString('id-ID'));
                $('#inputDescription').val(res.description);
                $('#inputPersonName').val(res.person_name || '');
                $('#inputEvidenceNumber').val(res.evidence_number || '');
                $('#inputGuruId').val(res.guru_id || '').trigger('change');

                if (res.receipt_url) {
                    $('#currentReceiptFileContainer')
                        .html(`<span class="text-muted">Bukti saat ini:</span> <a href="${res.receipt_url}" target="_blank" class="text-primary font-weight-bold ml-1"><i class="fas fa-file-invoice mr-1"></i> Lihat Bukti Nota</a> <span class="text-muted small ml-1">(Pilih berkas baru jika ingin mengganti)</span>`)
                        .show();
                }

                if (res.transaction_type === 'masuk') {
                    $('#modalCreateKas .modal-header').removeClass('bg-primary bg-danger').addClass('bg-success text-white');
                    $('#modalCreateTitle').html('<i class="fas fa-edit text-white mr-1"></i> Edit Transaksi Kas Masuk #' + res.id);
                } else {
                    $('#modalCreateKas .modal-header').removeClass('bg-success bg-danger').addClass('bg-primary text-white');
                    $('#modalCreateTitle').html('<i class="fas fa-edit text-white mr-1"></i> Edit Transaksi Kas Keluar #' + res.id);
                }

                if (res.items && res.items.length > 0) {
                    res.items.forEach(function(item) {
                        let rowHtml = renderItemRow('Create', createItemIndex, item);
                        $('#tbodyCreateItems').append(rowHtml);
                        createItemIndex++;
                    });
                    recalculateItemsTotal('Create');
                } else {
                    $('#inputAmount').val(Number(res.amount).toLocaleString('id-ID'));
                }

                let trxYear = res.transaction_date ? res.transaction_date.substring(0, 4) : $('#filterYear').val();
                if (trxYear) {
                    $('#selectDocYear').val(trxYear);
                }
                loadAvailableDocuments($('#selectDocYear').val(), true, {
                    bos_rencana_belanja_id: res.bos_rencana_belanja_id,
                    perjadin_item_id: res.perjadin_item_id
                });
            },
            error: function(err) {
                hideKasModal('#modalCreateKas');
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Memuat Data',
                    text: err.responseJSON?.message || 'Tidak dapat mengambil detail transaksi kas.'
                });
            }
        });
    };

    window.handleCategoryChange = function(cat) {
        if (!cat) return;
        let type = $('#inputTransType').val();

        // 1. Parent Panjar & Parent Talangan
        if (cat === 'sisa_panjar') {
            $('#containerSelectParentPanjar').slideDown(150);
            $('#containerSelectParentTalangan').hide();
            $('#selectParentTalanganId').val('');
            let parentId = $('#selectParentPanjarId').val();
            $('#inputParentKasId').val(parentId || '');
        } else if (cat === 'bayar_talangan') {
            $('#containerSelectParentPanjar').hide();
            $('#containerSelectParentTalangan').slideDown(150);
            $('#selectParentPanjarId').val('');
            let parentId = $('#selectParentTalanganId').val();
            $('#inputParentKasId').val(parentId || '');
        } else {
            $('#containerSelectParentPanjar').hide();
            $('#containerSelectParentTalangan').hide();
            $('#selectParentPanjarId').val('');
            $('#selectParentTalanganId').val('');
            $('#inputParentKasId').val('');
        }

        // 2. Dokumen Terkait (Rencana Belanja / Perjadin)
        let allowRencanaBelanja = ['belanja_tunai', 'panjar_kerja', 'reimburse', 'talangan_belanja'].includes(cat);
        let allowPerjadin = ['perjadin_tunai', 'sisa_perjadin'].includes(cat);

        if (allowRencanaBelanja || allowPerjadin) {
            $('#cardDokumenTerkait').slideDown(150);
            if (allowRencanaBelanja && allowPerjadin) {
                $('#groupRencanaBelanja').show().removeClass('col-md-12').addClass('col-md-6');
                $('#groupPerjadinItem').show().removeClass('col-md-12').addClass('col-md-6');
            } else if (allowRencanaBelanja) {
                $('#groupRencanaBelanja').show().removeClass('col-md-6').addClass('col-md-12');
                $('#groupPerjadinItem').hide();
                if (!$('#editKasId').val()) $('#inputPerjadinItemId').val('');
            } else {
                $('#groupRencanaBelanja').hide();
                if (!$('#editKasId').val()) $('#inputRencanaBelanjaId').val('');
                $('#groupPerjadinItem').show().removeClass('col-md-6').addClass('col-md-12');
            }
        } else {
            $('#cardDokumenTerkait').slideUp(150);
            if (!$('#editKasId').val()) {
                $('#inputRencanaBelanjaId').val('');
                $('#inputPerjadinItemId').val('');
            }
        }

        // 3. Rincian Belanja Barang (Tabel Barang)
        let allowItems = ['belanja_tunai', 'reimburse', 'talangan_belanja'].includes(cat);
        if (allowItems) {
            $('#sectionCreateItems').slideDown(150);
        } else {
            $('#sectionCreateItems').slideUp(150);
            if (!$('#editKasId').val()) {
                $('#tbodyCreateItems').empty();
                $('#tfootCreateItems').hide();
                $('#emptyCreateItemsNote').show();
                $('#totalCreateItemsDisplay').text('Rp 0');
                createItemIndex = 0;
            }
        }

        // 4. Toko / Pihak Penyedia (person_name)
        let personConfig = {
            'belanja_tunai': { show: true, label: 'Nama Toko / Rekanan / Penyedia', placeholder: 'Contoh: Toko Barokah / CV. Graha Media / Bengkel', note: 'Nama toko atau penyedia barang/jasa.' },
            'reimburse': { show: true, label: 'Nama Toko / Tempat Belanja', placeholder: 'Contoh: Toko Buku Siswa / Swalayan', note: 'Nama toko tempat guru membeli barang.' },
            'talangan_belanja': { show: true, label: 'Nama Toko / Mitra Penyedia (SIPLah)', placeholder: 'Contoh: CV. Graha Mitra / Toko Mitra SIPLah', note: 'Nama toko/mitra yang ditalangi pembayarannya.' },
            'operasional_laporan': { show: true, label: 'Pihak Penyedia / Kios / Pengelola', placeholder: 'Contoh: Fotokopi Lancar / Warung Bu Siti / Kantor Pos', note: 'Tempat penyedia jasa operasional/konsumsi/fotokopi.' },
            'dana_talangan_masuk': { show: true, label: 'Nama Pemberi / Pemilik Dana Talangan', placeholder: 'Contoh: H. Ahmad / Kepala Sekolah / Pihak Ketiga', note: 'Nama pihak yang meminjamkan dana talangan tunai.' },
            'bayar_talangan': { show: true, label: 'Nama Penerima Pengembalian Talangan', placeholder: 'Contoh: H. Ahmad / Kepala Sekolah', note: 'Nama pihak yang menerima kembali dana talangan.' },
            'lainnya_masuk': { show: true, label: 'Pihak Pemberi / Pembayar', placeholder: 'Contoh: Panitia Lomba / Vendor Pengembali Selisih', note: 'Nama pihak/instansi yang menyerahkan dana.' },
            'lainnya_keluar': { show: true, label: 'Pihak Penerima Pembayaran', placeholder: 'Contoh: Petugas Kebersihan / Pengelola Lingkungan', note: 'Pihak yang menerima pembayaran kas.' },
            'droping_sekolah': { show: true, label: 'Sumber Dana / Keterangan Lembaga', placeholder: 'Contoh: Kas Utama Sekolah / Rekening Yayasan / Komite', note: 'Sumber asal alokasi dana sekolah.' },
            'tarik_bank': { show: true, label: 'Nama Bank & Rekening', placeholder: 'Contoh: Bank Jatim - Rekening BOSP', note: 'Nama bank tempat penarikan kas tunai dilakukan.' },
            'setor_bank': { show: true, label: 'Bank Tujuan Setoran', placeholder: 'Contoh: Bank Jatim - Rekening BOSP', note: 'Nama bank tempat penyetoran uang kas.' },
        };

        let pCfg = personConfig[cat];
        if (pCfg && pCfg.show) {
            $('#groupPersonName').show();
            $('#groupGuruId').removeClass('col-md-12').addClass('col-md-6');
            $('#labelPersonName').text(pCfg.label);
            $('#inputPersonName').attr('placeholder', pCfg.placeholder);
            $('#notePersonName').text(pCfg.note);
        } else {
            $('#groupPersonName').hide();
            if (!$('#editKasId').val()) $('#inputPersonName').val('');
            $('#groupGuruId').removeClass('col-md-6').addClass('col-md-12');
        }

        // 5. Penanggung Jawab / Guru (guru_id)
        let guruConfig = {
            'droping_sekolah': { label: 'Bendahara / Petugas Penerima', note: 'Pilih nama Bendahara yang menerima uang masuk.' },
            'tarik_bank': { label: 'Bendahara Penarik Kas', note: 'Pilih nama Bendahara yang melakukan penarikan dari bank.' },
            'setor_bank': { label: 'Bendahara Penyetor Kas', note: 'Pilih nama Bendahara yang menyetorkan uang ke bank.' },
            'panjar_kerja': { label: 'Guru / Pegawai Peminjam (Kasbon)', note: 'Pilih nama guru/staf yang menerima uang muka kasbon.' },
            'reimburse': { label: 'Guru yang Mengajukan Reimburse', note: 'Pilih nama guru yang uang pribadinya diganti oleh kas.' },
            'honor_tim': { label: 'Penerima Honor / Koordinator Tim', note: 'Pilih nama penerima honor atau koordinator tim pengelola.' },
            'perjadin_tunai': { label: 'Guru / Pegawai Pelaksana Perjadin', note: 'Pilih nama guru/pegawai yang melaksanakan dinas luar.' },
            'sisa_perjadin': { label: 'Guru Pelaksana Perjadin', note: 'Pilih nama guru yang mengembalikan sisa uang perjalanan dinas.' },
            'sisa_panjar': { label: 'Guru yang Mengembalikan Kasbon', note: 'Pilih nama guru pemegang kasbon yang mengembalikan sisa kas.' },
            'dana_talangan_masuk': { label: 'Bendahara Penerima Talangan', note: 'Pilih nama Bendahara yang menerima fisik uang talangan.' },
            'bayar_talangan': { label: 'Bendahara Pelaksana Pembayaran', note: 'Pilih nama Bendahara yang mengembalikan uang talangan.' },
            'belanja_tunai': { label: 'Penanggung Jawab / Pembeli', note: 'Pilih guru atau bendahara yang melakukan pembelian.' },
        };

        let gCfg = guruConfig[cat] || { label: 'Penanggung Jawab / Pelaksana Transaksi', note: 'Pilih guru atau bendahara pelaksana transaksi.' };
        $('#labelGuruId').html(gCfg.label + ' <span class="text-danger">*</span>');
        $('#noteGuruId').text(gCfg.note);

        // 6. Uraian / Keterangan Keperluan Placeholder
        let descPlaceholders = {
            'droping_sekolah': 'Contoh: Alokasi kas tunai awal tahun ajaran / Droping dana sekolah',
            'tarik_bank': 'Contoh: Penarikan tunai dari rekening Bank Jatim untuk operasional kas sekolah',
            'setor_bank': 'Contoh: Penyetoran kelebihan uang tunai kas ke rekening giro sekolah',
            'panjar_kerja': 'Contoh: Kasbon belanja material praktik kelistrikan / Kasbon konsumsi workshop',
            'belanja_tunai': 'Contoh: Pembelian semen 10 sak, paku, dan batako untuk perbaikan selasar',
            'reimburse': 'Contoh: Ganti uang pembelian konsumsi rapat pleno dewan guru',
            'talangan_belanja': 'Contoh: Pembayaran talangan pesanan buku referensi di Mitra SIPLah',
            'operasional_laporan': 'Contoh: Fotokopi berkas LPJ BOSP dan pembelian materai 10.000',
            'honor_tim': 'Contoh: Pembayaran honor Tim Pelaksana BOS bulan September 2026',
            'perjadin_tunai': 'Contoh: Uang transport dan akomodasi dinas luar koordinasi kurikulum di Surabaya',
            'sisa_perjadin': 'Contoh: Pengembalian sisa uang transport dinas luar ke Malang',
            'sisa_panjar': 'Contoh: Pengembalian sisa kasbon belanja bahan praktik',
            'dana_talangan_masuk': 'Contoh: Penerimaan pinjaman dana talangan tunai untuk pembayaran listrik darurat',
            'bayar_talangan': 'Contoh: Pengembalian pinjaman dana talangan pembayaran listrik darurat',
            'lainnya_masuk': 'Contoh: Penerimaan pengembalian kelebihan transfer dari rekanan',
            'lainnya_keluar': 'Contoh: Pembayaran retribusi kebersihan dan pembuangan sampah lingkungan',
        };
        if (descPlaceholders[cat]) {
            $('#inputDescription').attr('placeholder', descPlaceholders[cat]);
        }

        // 7. Nomor Bukti & Upload Label
        let evidenceLabels = {
            'tarik_bank': { number: 'Nomor Slip Penarikan Bank / Ref TNT', file: 'Upload Foto Slip Penarikan Bank (Opsional)' },
            'setor_bank': { number: 'Nomor Slip Setoran Bank', file: 'Upload Foto Slip Setoran Bank (Opsional)' },
            'droping_sekolah': { number: 'Nomor Kuitansi / Bukti Droping', file: 'Upload Bukti Kuitansi / Transfer (Opsional)' },
            'panjar_kerja': { number: 'Nomor Tanda Terima Kasbon', file: 'Upload Foto Tanda Tangan Kasbon (Opsional)' },
            'honor_tim': { number: 'Nomor Daftar Tanda Terima Honor', file: 'Upload Foto Daftar Tanda Tangan Honor (Opsional)' },
            'perjadin_tunai': { number: 'Nomor Kuitansi / Surat Tugas Perjadin', file: 'Upload Lembar Kuitansi Perjadin (Opsional)' },
            'sisa_perjadin': { number: 'Nomor Kuitansi Rampung Perjadin', file: 'Upload Kuitansi Rampung (Opsional)' },
            'sisa_panjar': { number: 'Nomor Bukti Pengembalian', file: 'Upload Bukti Pengembalian (Opsional)' },
            'dana_talangan_masuk': { number: 'Nomor Tanda Terima Talangan', file: 'Upload Surat Perjanjian / Bukti Talangan (Opsional)' },
            'bayar_talangan': { number: 'Nomor Bukti Lunas Talangan', file: 'Upload Bukti Pembayaran Lunas (Opsional)' },
            'belanja_tunai': { number: 'Nomor Nota / Faktur Toko', file: 'Upload Foto / Scan Nota Toko (Opsional)' },
            'reimburse': { number: 'Nomor Nota Asli Pengeluaran', file: 'Upload Foto Nota Belanja Asli (Opsional)' },
        };
        let evCfg = evidenceLabels[cat] || { number: 'Nomor Bukti / Kuitansi Fisik', file: 'Upload Foto / Scan Nota Bukti (Opsional)' };
        $('#labelEvidenceNumber').text(evCfg.number);
        $('#labelReceiptFile').text(evCfg.file);
    };

    window.populateCategories = function(type) {
        let $select = $('#inputCategory');
        $select.empty();

        for (let key in categoriesData) {
            if (categoriesData[key].type === type) {
                let icon = categoriesData[key].icon ? categoriesData[key].icon + ' ' : '';
                $select.append(`<option value="${key}">${icon}${categoriesData[key].label}</option>`);
            }
        }
        handleCategoryChange($select.val());
    };

    $(document).ready(function() {
        // Init DataTable Utama
        kasTable = $('#kasTable').DataTable({
            processing: true,
            serverSide: true,
            autoWidth: false,
            ajax: {
                url: "{{ route($routePrefix . '.kas-tunai.index', [], false) }}",
                data: function(d) {
                    d.category = $('#filterCategory').val();
                    d.month = $('#filterMonth').val();
                    d.year = $('#filterYear').val();
                },
                error: function(xhr, error, thrown) {
                    $('#kasTable_processing').hide();
                    if (xhr.status === 0 || error === 'abort') {
                        return;
                    }
                    console.warn('DataTable kasTable error:', error, thrown);
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center', width: '4%' },
                { data: 'id_display', name: 'id', className: 'text-center', width: '7%' },
                { data: 'transaction_date', name: 'transaction_date', className: 'text-center', width: '8%' },
                { 
                    data: 'category', 
                    name: 'category',
                    width: '11%',
                    render: function(data, type, row) {
                        let badgeClass = row.transaction_type === 'masuk' ? 'badge-masuk' : 'badge-keluar';
                        if (row.panjar_status === 'pending') badgeClass = 'badge-pending';
                        if (row.panjar_status === 'partial') badgeClass = 'badge-warning text-dark font-weight-bold';
                        if (row.panjar_status === 'settled') badgeClass = 'badge-settled';
                        return `<span class="badge ${badgeClass} px-2 py-1">${data}</span>`;
                    }
                },
                { data: 'description', name: 'description', width: '25%' },
                { data: 'person_display', name: 'person_display', width: '20%' },
                { 
                    data: 'debit', 
                    name: 'debit', 
                    searchable: false,
                    className: 'text-right font-weight-bold text-success',
                    width: '9%',
                    render: function(data) {
                        return data > 0 ? 'Rp ' + Number(data).toLocaleString('id-ID') : '-';
                    }
                },
                { 
                    data: 'kredit', 
                    name: 'kredit', 
                    searchable: false,
                    className: 'text-right font-weight-bold text-danger',
                    width: '9%',
                    render: function(data) {
                        return data > 0 ? 'Rp ' + Number(data).toLocaleString('id-ID') : '-';
                    }
                },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-right text-end text-nowrap', width: '7%' }
            ],
            createdRow: function(row, data, dataIndex) {
                const labels = ['', 'ID Transaksi', 'Tanggal', 'Kategori', 'Uraian', 'Penanggung Jawab & Toko', 'Penerimaan', 'Pengeluaran', 'Aksi'];
                $('td', row).each(function(idx) {
                    if (labels[idx]) {
                        $(this).attr('data-label', labels[idx]);
                    }
                    if (idx === 0) {
                        $(this).addClass('td-index');
                    }
                });
            },
            drawCallback: function() {
                $('#kasTable_processing').hide();
            },
            order: [[2, 'desc']],
            pageLength: 25
        });

        // Realtime update Header Stat Cards saat data tabel dimuat / di-reload
        kasTable.on('xhr.dt', function(e, settings, json, xhr) {
            $('#kasTable_processing').hide();
            if (json && json.summary) {
                if (json.summary.saldo_kas_formatted) $('#statSaldoKas').text(json.summary.saldo_kas_formatted);
                if (json.summary.total_panjar_pending_formatted) $('#statTotalPanjarPending').text(json.summary.total_panjar_pending_formatted);
                if (json.summary.count_panjar_pending !== undefined) $('#statCountPanjarPending').text(json.summary.count_panjar_pending + ' transaksi');
                if (json.summary.kas_siap_pakai_formatted) $('#statKasSiapPakai').text(json.summary.kas_siap_pakai_formatted);
                if (json.summary.total_masuk_formatted) $('#statTotalMasuk').text(json.summary.total_masuk_formatted);
                if (json.summary.total_keluar_formatted) $('#statTotalKeluar').text(json.summary.total_keluar_formatted);
            }
        });

        // Toggle Mobile Card / Table View
        $('#btnToggleMobileCards').on('click', function() {
            let $table = $('#kasTable');
            $table.toggleClass('force-table-view');
            if ($table.hasClass('force-table-view')) {
                $('#textToggleView').text('Mode Kartu');
                $(this).find('i').removeClass('fa-table').addClass('fa-id-card');
            } else {
                $('#textToggleView').text('Mode Tabel');
                $(this).find('i').removeClass('fa-id-card').addClass('fa-table');
            }
        });

        // Explicit Header Button Listeners
        $(document).on('click', '.btn-create-masuk', function(e) {
            e.preventDefault();
            openCreateModal('masuk');
        });
        $(document).on('click', '.btn-create-belanja', function(e) {
            e.preventDefault();
            openCreateModal('keluar', 'belanja_tunai');
        });
        $(document).on('click', '.btn-create-biaya', function(e) {
            e.preventDefault();
            openCreateModal('keluar', 'operasional_laporan');
        });
        $(document).on('click', '.btn-create-kasbon', function(e) {
            e.preventDefault();
            openCreateModal('keluar', 'panjar_kerja');
        });
        $(document).on('click', '[data-target="#modalKasOpname"], [data-bs-target="#modalKasOpname"]', function(e) {
            e.preventDefault();
            showKasModal('#modalKasOpname');
        });
        $(document).on('click', '[data-target="#modalPrintKas"], [data-bs-target="#modalPrintKas"]', function(e) {
            e.preventDefault();
            showKasModal('#modalPrintKas');
        });

        // Explicit Modal Close & Batal Handlers
        $(document).on('click', '[data-dismiss="modal"], [data-bs-dismiss="modal"], .btn-dismiss-modal, .modal .close, .modal .btn-close', function(e) {
            e.preventDefault();
            let modalEl = $(this).closest('.modal')[0];
            hideKasModal(modalEl);
        });

        // Event filter
        $('#filterCategory, #filterMonth, #filterYear').on('change', function() {
            kasTable.ajax.reload();
        });

        // Event listener ganti tahun dokumen / toggle sudah lunas
        $('#selectDocYear, #checkIncludeSettled').on('change', function() {
            loadAvailableDocuments($('#selectDocYear').val(), $('#checkIncludeSettled').is(':checked'));
        });

        $('#btnReloadDocs').on('click', function() {
            loadAvailableDocuments($('#selectDocYear').val(), $('#checkIncludeSettled').is(':checked'));
        });

        // Format Rupiah Input with Thousand Separator
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

        // Event change tipe transaksi di modal create
        $('#inputTransType').on('change', function() {
            let val = $(this).val();
            populateCategories(val);
            if (val === 'masuk') {
                $('#modalCreateKas .modal-header').removeClass('bg-primary bg-danger').addClass('bg-success text-white');
                $('#modalCreateTitle').html('<i class="fas fa-arrow-down text-white mr-1"></i> Catat Kas Masuk (Penerimaan)');
            } else {
                $('#modalCreateKas .modal-header').removeClass('bg-success bg-danger').addClass('bg-primary text-white');
                $('#modalCreateTitle').html('<i class="fas fa-arrow-up text-white mr-1"></i> Catat Kas Keluar / Kasbon (Pengeluaran)');
            }
        });

        // Event change kategori transaksi
        $('#inputCategory').on('change', function() {
            handleCategoryChange($(this).val());
        });

        // Event pilih parent kasbon (sisa_panjar)
        $('#selectParentPanjarId').on('change', function() {
            let parentId = $(this).val();
            $('#inputParentKasId').val(parentId);
            if (parentId) {
                let opt = $(this).find('option:selected');
                let guruId = opt.data('guru-id');
                let desc = opt.data('desc');
                if (guruId) {
                    $('#inputGuruId').val(guruId).trigger('change');
                }
                if (!$('#inputDescription').val() || $('#inputDescription').val().startsWith('Kembalian sisa kasbon')) {
                    $('#inputDescription').val('Kembalian sisa kasbon #' + parentId + (desc ? ' (' + desc + ')' : ''));
                }
            }
        });

        // Event pilih parent dana talangan (bayar_talangan)
        $('#selectParentTalanganId').on('change', function() {
            let parentId = $(this).val();
            $('#inputParentKasId').val(parentId);
            if (parentId) {
                let opt = $(this).find('option:selected');
                let guruId = opt.data('guru-id');
                let desc = opt.data('desc');
                let amount = opt.data('amount');
                if (guruId) {
                    $('#inputGuruId').val(guruId).trigger('change');
                }
                if (amount && (!$('#inputAmount').val() || $('#inputAmount').val() === '0')) {
                    $('#inputAmount').val(Number(amount).toLocaleString('id-ID'));
                }
                if (!$('#inputDescription').val() || $('#inputDescription').val().startsWith('Pengembalian dana talangan')) {
                    $('#inputDescription').val('Pengembalian dana talangan #' + parentId + (desc ? ' (' + desc + ')' : ''));
                }
            }
        });

        // Auto-fill saat pilih Rencana Belanja
        $('#inputRencanaBelanjaId').on('change', function() {
            let $opt = $(this).find('option:selected');
            if ($(this).val()) {
                let amount = parseFloat($opt.data('amount')) || 0;
                let supplier = $opt.data('supplier') || '';
                let nota = $opt.data('nota') || '';
                let title = $opt.data('title') || '';

                if (!$('#inputAmount').val() || $('#inputAmount').val() === '0') {
                    $('#inputAmount').val(amount ? amount.toLocaleString('id-ID') : '');
                }
                if (!$('#inputPersonName').val() && supplier) {
                    $('#inputPersonName').val(supplier);
                }
                if (!$('#inputEvidenceNumber').val() && nota) {
                    $('#inputEvidenceNumber').val(nota);
                }
                if (!$('#inputDescription').val()) {
                    $('#inputDescription').val('Belanja ' + (title || nota) + (supplier ? ' (' + supplier + ')' : ''));
                }
            }
        });

        // Auto-fill saat pilih Kwitansi Perjadin
        $('#inputPerjadinItemId').on('change', function() {
            let $opt = $(this).find('option:selected');
            if ($(this).val()) {
                let amount = parseFloat($opt.data('amount')) || 0;
                let guruId = $opt.data('guru-id') || '';
                let guruName = $opt.data('guru-name') || '';
                let receipt = $opt.data('receipt') || '';
                let desc = $opt.data('desc') || '';

                if (!$('#inputAmount').val() || $('#inputAmount').val() === '0') {
                    $('#inputAmount').val(amount ? amount.toLocaleString('id-ID') : '');
                }
                if (guruId) {
                    $('#inputGuruId').val(guruId).trigger('change');
                }
                if (!$('#inputPersonName').val() && guruName) {
                    $('#inputPersonName').val(guruName);
                }
                if (!$('#inputEvidenceNumber').val() && receipt) {
                    $('#inputEvidenceNumber').val(receipt);
                }
                if (!$('#inputDescription').val() && desc) {
                    $('#inputDescription').val(desc);
                }
            }
        });

        // Auto-fill saat pilih Rencana Belanja di Modal Settle Panjar
        $('#settleRencanaBelanjaId').on('change', function() {
            let $opt = $(this).find('option:selected');
            if ($(this).val()) {
                let amount = parseFloat($opt.data('amount')) || 0;
                if (!$('#inputRealAmount').val() || $('#inputRealAmount').val() === '0') {
                    $('#inputRealAmount').val(amount ? amount.toLocaleString('id-ID') : '').trigger('input');
                }
            }
        });

        // Auto-fill saat pilih Kwitansi Perjadin di Modal Settle Panjar
        $('#settlePerjadinItemId').on('change', function() {
            let $opt = $(this).find('option:selected');
            if ($(this).val()) {
                let amount = parseFloat($opt.data('amount')) || 0;
                if (!$('#inputRealAmount').val() || $('#inputRealAmount').val() === '0') {
                    $('#inputRealAmount').val(amount ? amount.toLocaleString('id-ID') : '').trigger('input');
                }
            }
        });

        // Submit form create / edit kas
        $('#formCreateKas').on('submit', function(e) {
            e.preventDefault();
            let formData = new FormData(this);
            let rawAmount = $('#inputAmount').val().replace(/[^0-9]/g, '');
            formData.set('amount', rawAmount);

            // Clean items price
            formData.forEach((value, key) => {
                if (key.match(/^items\[\d+\]\[price\]$/) && typeof value === 'string') {
                    formData.set(key, value.replace(/[^0-9]/g, ''));
                }
            });

            // Strip fields that are not relevant / hidden for the selected category
            if (!$('#sectionCreateItems').is(':visible')) {
                let itemKeys = [];
                formData.forEach((value, key) => {
                    if (key.startsWith('items[')) itemKeys.push(key);
                });
                itemKeys.forEach(k => formData.delete(k));
            }
            if (!$('#groupPersonName').is(':visible')) {
                formData.delete('person_name');
            }
            if (!$('#cardDokumenTerkait').is(':visible')) {
                formData.delete('bos_rencana_belanja_id');
                formData.delete('perjadin_item_id');
            } else {
                if (!$('#groupRencanaBelanja').is(':visible')) {
                    formData.delete('bos_rencana_belanja_id');
                }
                if (!$('#groupPerjadinItem').is(':visible')) {
                    formData.delete('perjadin_item_id');
                }
            }

            let editId = $('#editKasId').val();
            let submitUrl = editId 
                ? "{{ url($routePrefix . '/kas-tunai') }}/" + editId 
                : "{{ route($routePrefix . '.kas-tunai.store') }}";

            if (editId) {
                formData.append('_method', 'PUT');
            }

            $('#btnSubmitKas').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

            $.ajax({
                url: submitUrl,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(res) {
                    hideKasModal('#modalCreateKas');
                    $('#formCreateKas')[0].reset();
                    if (kasTable && typeof kasTable.ajax === 'object') {
                        kasTable.ajax.reload(null, false);
                    }
                    if (panjarPendingTable && typeof panjarPendingTable.ajax === 'object') {
                        panjarPendingTable.ajax.reload(null, false);
                    }
                    loadAvailableDocuments($('#selectDocYear').val(), $('#checkIncludeSettled').is(':checked'));
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                },
                error: function(err) {
                    let msg = err.responseJSON?.message || 'Terjadi kesalahan sistem.';
                    if (err.responseJSON?.errors) {
                        let errList = Object.values(err.responseJSON.errors).flat();
                        msg = errList.join('<br>');
                    }
                    Swal.fire({ icon: 'error', title: 'Gagal', html: msg });
                },
                complete: function() {
                    $('#btnSubmitKas').prop('disabled', false).html('<i class="fas fa-save mr-1"></i> <span id="textSubmitKas">' + (editId ? 'Simpan Perubahan' : 'Simpan Transaksi') + '</span>');
                }
            });
        });

        // Edit Kas Modal Trigger
        $(document).on('click', '.btn-edit-kas', function(e) {
            e.preventDefault();
            let id = $(this).data('id');
            openEditModal(id);
        });

        let currentRemainingAmount = 0;

        // Settle Panjar Modal Trigger
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

            // Pre-select linked document if exists
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
                    $('#formSettlePanjar')[0].reset();
                    if (kasTable && typeof kasTable.ajax === 'object') {
                        kasTable.ajax.reload(null, false);
                    }
                    if (panjarPendingTable && typeof panjarPendingTable.ajax === 'object') {
                        panjarPendingTable.ajax.reload(null, false);
                    }
                    loadAvailableDocuments($('#selectDocYear').val(), $('#checkIncludeSettled').is(':checked'));
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
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

        // Button Tambah Barang di modalCreateKas
        $('#btnAddCreateItem').on('click', function(e) {
            e.preventDefault();
            let row = renderItemRow('Create', createItemIndex++);
            $('#tbodyCreateItems').append(row);
            recalculateItemsTotal('Create');
        });

        // Remove item row
        $(document).on('click', '.btn-remove-item', function(e) {
            e.preventDefault();
            let $row = $(this).closest('tr');
            let isCreate = $row.hasClass('item-row-Create');
            $row.remove();
            recalculateItemsTotal(isCreate ? 'Create' : 'Settle');
        });

        // Live calculate subtotal when qty or price changes
        $(document).on('input', '.item-qty-input', function() {
            let $row = $(this).closest('tr');
            let isCreate = $row.hasClass('item-row-Create');
            recalculateItemsTotal(isCreate ? 'Create' : 'Settle');
        });

        $(document).on('input', '.item-price-input', function() {
            let val = $(this).val().replace(/[^0-9]/g, '');
            $(this).val(val ? Number(val).toLocaleString('id-ID') : '');
            let $row = $(this).closest('tr');
            let isCreate = $row.hasClass('item-row-Create');
            recalculateItemsTotal(isCreate ? 'Create' : 'Settle');
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

                    // Kategori & Warna
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

                    // Nominal & Label
                    if (kas.transaction_type === 'masuk') {
                        $('#viewItemAmountLabel').text('Kas Masuk (Penerimaan):');
                        $('#viewItemTotalAmount').attr('class', 'font-weight-bold text-success').text('+ ' + kas.formatted_amount);
                    } else {
                        $('#viewItemAmountLabel').text('Kas Keluar (Pengeluaran):');
                        $('#viewItemTotalAmount').attr('class', 'font-weight-bold text-danger').text('- ' + kas.formatted_amount);
                    }

                    // Parent Banner jika ini transaksi anak SPJ
                    if (kas.parent_kas) {
                        $('#viewParentKasDesc').text(kas.parent_kas.code + ' - ' + kas.parent_kas.person_name + ' (' + kas.parent_kas.formatted_amount + ') - ' + kas.parent_kas.description);
                        $('#viewChildBanner').show();
                    }

                    // Kasbon Status Badge & Summary
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

                    // Child Receipts (Multi-Nota SPJ Belanja Toko)
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

                    // Child Expenses (Biaya Penyerta Kasbon)
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

                    // Direct Items (Jika transaksi langsung belanja toko)
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

                    // Direct Receipt File Attachment
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

        // Klik baris pada tabel Kas Operasional untuk membuka detail transaksi
        $('#kasTable tbody').on('click', 'tr', function(e) {
            if ($(e.target).closest('button, a, input, select, .btn').length) {
                return;
            }
            let rowData = kasTable.row(this).data();
            if (rowData && rowData.id) {
                openTransactionDetail(rowData.id);
            }
        });

        // Delete Kas Handler
        $(document).on('click', '.btn-delete-kas', function(e) {
            e.preventDefault();
            let url = $(this).data('url');
            Swal.fire({
                title: 'Hapus Transaksi?',
                text: 'Transaksi yang dihapus akan membatalkan mutasi saldo kas terkait.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        data: { _token: '{{ csrf_token() }}' },
                        success: function(res) {
                            if (kasTable && typeof kasTable.ajax === 'object') {
                                kasTable.ajax.reload(null, false);
                            }
                            if (panjarPendingTable && typeof panjarPendingTable.ajax === 'object') {
                                panjarPendingTable.ajax.reload(null, false);
                            }
                            loadAvailableDocuments($('#selectDocYear').val(), $('#checkIncludeSettled').is(':checked'));
                            Swal.fire({ icon: 'success', title: 'Terhapus', text: res.message, timer: 1200, showConfirmButton: false });
                        },
                        error: function(err) {
                            Swal.fire({ icon: 'error', title: 'Gagal', text: err.responseJSON?.message || 'Gagal menghapus.' });
                        }
                    });
                }
            });
        });

        // Realtime Kas Opname Calculator
        $('.input-denom, #inputKoin').on('input', function() {
            calculateOpname();
        });

        // ==========================================
        // Modal Popup Preview Bukti Foto / Nota
        // ==========================================
        let receiptRotation = 0;
        let isReceiptZoomed = false;

        window.openReceiptPreview = function(opts) {
            let url = opts.url;
            if (!url) return;

            let isImage = String(opts.isImage) === '1' || String(opts.isImage) === 'true';
            let title = opts.title || 'Bukti Foto / Nota';
            let subtitleParts = [];
            if (opts.date) subtitleParts.push('<i class="fas fa-calendar-alt mr-1"></i>' + opts.date);
            if (opts.person) subtitleParts.push('<i class="fas fa-user-check mr-1"></i>' + opts.person);
            let subtitle = subtitleParts.join(' &bull; ');

            let metaParts = [];
            if (opts.evidence) metaParts.push('<strong>No. Bukti:</strong> ' + opts.evidence);
            if (opts.desc) metaParts.push('<strong>Uraian:</strong> ' + opts.desc);
            let meta = metaParts.join(' | ');

            receiptRotation = 0;
            isReceiptZoomed = false;

            $('#previewBuktiTitle').text(title);
            $('#previewBuktiSubtitle').html(subtitle);
            $('#previewBuktiMeta').html(meta);
            $('#btnPreviewOpenTab').attr('href', url);
            $('#btnPreviewDownload').attr('href', url);

            $('#previewReceiptLoading').hide();
            $('#previewReceiptImageWrapper').hide();
            $('#previewReceiptPdfWrapper').hide();
            $('#previewReceiptFallback').hide();
            $('#previewReceiptImage').css('transform', 'none').css('max-height', '70vh').css('cursor', 'zoom-in').attr('src', '');
            $('#previewReceiptPdfFrame').attr('src', '');

            if (isImage) {
                $('#btnRotateReceipt').show();
                $('#previewReceiptLoading').show();
                let img = new Image();
                img.onload = function() {
                    $('#previewReceiptLoading').hide();
                    $('#previewReceiptImage').attr('src', url);
                    $('#previewReceiptImageWrapper').fadeIn(150);
                };
                img.onerror = function() {
                    $('#previewReceiptLoading').hide();
                    $('#previewReceiptFallbackText').text('Gagal memuat gambar bukti.');
                    $('#previewReceiptFallbackLink').attr('href', url);
                    $('#previewReceiptFallback').show();
                };
                img.src = url;
            } else if (url.toLowerCase().indexOf('.pdf') !== -1) {
                $('#btnRotateReceipt').hide();
                $('#previewReceiptPdfFrame').attr('src', url);
                $('#previewReceiptPdfWrapper').show();
            } else {
                $('#btnRotateReceipt').hide();
                let ext = url.split('.').pop().toUpperCase();
                $('#previewReceiptFallbackText').text('Dokumen lampiran format .' + ext + ' tidak dapat dipratinjau langsung.');
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

        $(document).on('click', '.btn-preview-receipt, .btn-preview-receipt-inline', function(e) {
            e.preventDefault();
            let btn = $(this);
            window.openReceiptPreview({
                url: btn.data('url'),
                isImage: btn.data('is-image'),
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

        function calculateOpname() {
            let totalFisik = 0;
            $('.input-denom').each(function() {
                let denom = parseFloat($(this).data('denom')) || 0;
                let count = parseInt($(this).val()) || 0;
                let sub = denom * count;
                $('#subtotal_' + denom).text('Rp ' + sub.toLocaleString('id-ID'));
                totalFisik += sub;
            });

            let koin = parseFloat($('#inputKoin').val()) || 0;
            $('#subtotal_koin').text('Rp ' + koin.toLocaleString('id-ID'));
            totalFisik += koin;

            $('#totalFisikDisplay').text('Rp ' + totalFisik.toLocaleString('id-ID'));

            let saldoBuku = parseFloat($('#opnameSaldoBukuDisplay').data('saldo')) || 0;
            let selisih = totalFisik - saldoBuku;

            let $selisihDisplay = $('#selisihDisplay');
            if (selisih === 0) {
                $selisihDisplay.html('<span class="badge badge-success p-2">COCOK (Selisih Rp 0)</span>');
            } else if (selisih > 0) {
                $selisihDisplay.html('<span class="badge badge-info p-2">LEBIH FISIK (+ Rp ' + selisih.toLocaleString('id-ID') + ')</span>');
            } else {
                $selisihDisplay.html('<span class="badge badge-danger p-2">KURANG FISIK (- Rp ' + Math.abs(selisih).toLocaleString('id-ID') + ')</span>');
            }
        }
    });
</script>
@stop
