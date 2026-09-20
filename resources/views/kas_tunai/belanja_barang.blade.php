@extends('adminlte::page')

@section('title', 'Rekap Belanja Barang Tunai - ' . $fundingSource)

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap" style="padding: 2px 0;">
    <div>
        <h1 class="page-title"><i class="fas fa-boxes text-info mr-1"></i> Rekap Belanja Barang Tunai {{ $fundingSource }}</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-house"></i> Dashboard</a></li>
                <li class="breadcrumb-item"><i class="fas fa-calculator"></i> Penganggaran / {{ $fundingSource }}</li>
                <li class="breadcrumb-item"><a href="{{ route($routePrefix . '.kas-tunai.index') }}">Kas Operasional</a></li>
                <li class="breadcrumb-item active">Rekap Belanja Barang</li>
            </ol>
        </nav>
    </div>
    <div class="header-actions mt-2 mt-md-0 d-flex align-items-center flex-wrap" style="gap: 6px;">
        <a href="{{ route($routePrefix . '.kas-tunai.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Buku Kas
        </a>
        <a href="{{ route($routePrefix . '.kas-tunai.kasbon') }}" class="btn btn-sm btn-outline-warning">
            <i class="fas fa-user-clock mr-1"></i> Tanggungan Kasbon
        </a>
        <button type="button" class="btn btn-sm btn-primary shadow-xs font-weight-bold" id="btnOpenModalInputItem">
            <i class="fas fa-cart-plus mr-1"></i> Input Item Belanja
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
    .table-belanja {
        font-size: 0.8125rem !important;
    }
    .table-belanja th {
        font-size: 0.74rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 0.5rem 0.55rem !important;
        vertical-align: middle;
        background-color: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
    }
    .table-belanja td {
        padding: 0.5rem 0.55rem !important;
        vertical-align: middle;
    }

    .btn-xs {
        padding: 2px 7px !important;
        font-size: 0.75rem !important;
        line-height: 1.25 !important;
        border-radius: 4px !important;
    }

    .clickable-nota-row {
        cursor: pointer;
        transition: background-color 0.15s ease;
    }
    .clickable-nota-row:hover {
        background-color: #f0f7ff !important;
    }
    .clickable-nota-row.row-expanded {
        background-color: #eaf3ff !important;
        border-left: 3px solid #007bff;
    }
    .chevron-icon {
        transition: transform 0.2s ease;
    }
    .row-expanded .chevron-icon {
        transform: rotate(90deg);
        color: #007bff !important;
    }

    .nota-detail-box {
        background-color: #ffffff;
        border: 1px solid #dbeafe;
        border-radius: 6px;
        box-shadow: inset 0 1px 3px rgba(0,0,0,0.03);
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
</style>
@stop

@section('content')
{{-- Stat Cards --}}
<div class="row mb-3">
    <div class="col-xl-4 col-md-6 mb-2">
        <div class="card stat-card bg-white p-3 border-left border-info" style="border-left-width: 4px !important;">
            <div class="stat-label">Total Belanja Barang Tunai</div>
            <div class="stat-value text-info mt-1">Rp {{ number_format($totalBelanjaNominal, 0, ',', '.') }}</div>
            <small class="text-muted">Akumulasi nilai seluruh komoditas yang terdaftar dalam nota</small>
        </div>
    </div>
    <div class="col-xl-4 col-md-6 mb-2">
        <div class="card stat-card bg-white p-3 border-left border-primary" style="border-left-width: 4px !important;">
            <div class="stat-label">Total Item Komoditas</div>
            <div class="stat-value text-primary mt-1">{{ number_format($totalItemsCount, 0, ',', '.') }} Item</div>
            <small class="text-muted">Banyaknya rincian barang/jasa yang tercatat di pembukuan</small>
        </div>
    </div>
    <div class="col-xl-4 col-md-6 mb-2">
        <div class="card stat-card bg-white p-3 border-left border-success" style="border-left-width: 4px !important;">
            <div class="stat-label">Nota Belanja Kas Tunai</div>
            <div class="stat-value text-success mt-1">{{ $countNotaBelanja }} Transaksi</div>
            <small class="text-muted">
                <span class="font-weight-bold text-success">{{ $countNotaWithItems }} nota</span> sudah ber-item,
                <span class="text-secondary">{{ $countNotaBelanja - $countNotaWithItems }} nota</span> belum diinput
            </small>
        </div>
    </div>
</div>

{{-- Main Card --}}
<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-bottom p-2 d-flex justify-content-between align-items-center flex-wrap">
        {{-- Sub-Tabs --}}
        <ul class="nav nav-pills" id="belanjaTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active font-weight-bold py-1 px-3" id="tab-rekap-nota-link" data-bs-toggle="pill" data-bs-target="#tab-rekap-nota" data-toggle="pill" href="#tab-rekap-nota" role="tab">
                    <i class="fas fa-receipt mr-1"></i> Rekap Per Nota Belanja ({{ $countNotaBelanja }})
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link font-weight-bold py-1 px-3" id="tab-semua-item-link" data-bs-toggle="pill" data-bs-target="#tab-semua-item" data-toggle="pill" href="#tab-semua-item" role="tab">
                    <i class="fas fa-boxes mr-1"></i> Rincian Seluruh Item Barang ({{ $totalItemsCount }})
                </a>
            </li>
        </ul>

        {{-- Filters & Actions --}}
        <div class="d-flex align-items-center flex-wrap ms-auto ml-auto mt-2 mt-md-0 filter-wrapper" style="gap: 8px;">
            <div class="input-group input-group-sm shadow-xs" style="width: auto;">
                <span class="input-group-text bg-light text-muted font-weight-bold" style="height: 31px; font-size: 0.8125rem;">
                    <i class="fas fa-calendar-alt text-info mr-1"></i> Tahun:
                </span>
                <select id="filterYear" class="form-select form-select-sm font-weight-bold text-dark" style="height: 31px; font-size: 0.8125rem; cursor: pointer;">
                    @foreach($availableYears as $yr)
                        <option value="{{ $yr }}" {{ $yr == $selectedYear ? 'selected' : '' }}>{{ $yr }}</option>
                    @endforeach
                </select>
            </div>
            <div class="input-group input-group-sm shadow-xs" style="width: auto;">
                <span class="input-group-text bg-light text-muted font-weight-bold" style="height: 31px; font-size: 0.8125rem;">
                    <i class="fas fa-calendar-check text-info mr-1"></i> Bulan:
                </span>
                @php
                    $months = [
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                    ];
                @endphp
                <select id="filterMonth" class="form-select form-select-sm font-weight-bold text-dark" style="height: 31px; font-size: 0.8125rem; cursor: pointer;">
                    <option value="">Semua Bulan</option>
                    @foreach($months as $num => $name)
                        <option value="{{ $num }}" {{ $selectedMonth == $num ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnReloadTable" title="Muat Ulang Data">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </div>

    <div class="card-body p-2">
        <div class="tab-content" id="belanjaTabsContent">
            {{-- TAB 1: REKAP PER NOTA BELANJA (KLIK BARIS BISA DILIHAT SELURUH ITEMNYA) --}}
            <div class="tab-pane fade show active" id="tab-rekap-nota" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                    <small class="text-muted">
                        <i class="fas fa-info-circle text-info mr-1"></i> Klik baris nota mana saja untuk melihat <strong>seluruh rincian item barang yang dibeli</strong> dalam 1 nota tersebut.
                    </small>
                    <button type="button" class="btn btn-xs btn-primary font-weight-bold btn-trigger-input-item">
                        <i class="fas fa-plus-circle mr-1"></i> Input Item Belanja Baru
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered table-striped w-100 table-belanja" id="tableNotaBelanja">
                        <thead>
                            <tr>
                                <th style="width: 4%;" class="text-center">No</th>
                                <th style="width: 10%;">Tanggal</th>
                                <th style="width: 11%;" class="text-center">No. Nota/Bukti</th>
                                <th style="width: 24%;">Toko / Uraian Belanja</th>
                                <th style="width: 16%;">Pembeli / Penanggung Jawab</th>
                                <th style="width: 14%;" class="text-center">Rincian Barang</th>
                                <th style="width: 11%;" class="text-right">Total Nota (Rp)</th>
                                <th style="width: 10%;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($notaBelanjaList as $idx => $nota)
                                <tr class="clickable-nota-row" data-id="{{ $nota->id }}" title="Klik untuk melihat rincian item dalam 1 nota ini">
                                    <td class="text-center font-weight-bold text-muted">
                                        <i class="fas fa-chevron-right text-muted mr-1 chevron-icon" style="font-size: 0.72rem;"></i>
                                        {{ $idx + 1 }}
                                    </td>
                                    <td>
                                        <i class="fas fa-calendar-alt text-muted mr-1"></i> {{ $nota->formatted_date }}
                                    </td>
                                    <td class="text-center font-weight-bold">
                                        @if($nota->evidence_number)
                                            <span class="badge badge-light border text-dark font-monospace" style="font-size: 0.78rem;">
                                                <i class="fas fa-file-invoice text-info mr-1"></i>{{ $nota->evidence_number }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong class="text-dark">{{ $nota->description }}</strong>
                                        <div class="small text-muted font-monospace">{{ $nota->id_display }} &bull; {{ $nota->category_label }}</div>
                                    </td>
                                    <td>
                                        <i class="fas fa-user-check text-muted mr-1"></i> {{ $nota->person_name }}
                                    </td>
                                    <td class="text-center">
                                        @if($nota->items_count > 0)
                                            <span class="badge badge-info px-2 py-1" style="font-size: 0.75rem;">
                                                <i class="fas fa-boxes mr-1"></i> {{ $nota->items_count }} Item
                                            </span>
                                            <span class="badge {{ $nota->sync_badge }} px-2 py-1 ml-1" style="font-size: 0.72rem;">
                                                {{ $nota->sync_label }}
                                            </span>
                                        @else
                                            <span class="badge badge-light border text-muted px-2 py-1" style="font-size: 0.75rem;">
                                                <i class="fas fa-exclamation-circle text-warning mr-1"></i> Belum Ada Item
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-right font-weight-bold text-dark">
                                        {{ $nota->formatted_amount }}
                                    </td>
                                    <td class="text-center" onclick="event.stopPropagation();">
                                        <div class="btn-group btn-group-xs" role="group">
                                            <button type="button" class="btn btn-xs btn-outline-primary btn-kelola-nota-item"
                                                    data-id="{{ $nota->id }}"
                                                    data-desc="{{ $nota->description }}"
                                                    data-evidence="{{ $nota->evidence_number }}"
                                                    data-amount="{{ $nota->amount }}"
                                                    title="Input / Edit Rincian Item Nota Ini">
                                                <i class="fas fa-plus-circle mr-1"></i> Item
                                            </button>
                                            <button type="button" class="btn btn-xs btn-info btn-view-receipt-items"
                                                    data-id="{{ $nota->id }}"
                                                    title="Lihat Modal Rincian Nota">
                                                <i class="fas fa-receipt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                {{-- Sub-Baris Expandable: Rincian Seluruh Item Barang Dalam 1 Nota --}}
                                <tr class="nota-detail-row" id="notaDetailRow-{{ $nota->id }}" style="display: none;">
                                    <td colspan="8" class="p-0 border-0">
                                        <div class="nota-detail-box m-2 p-3 border-left border-primary" style="border-left-width: 4px !important;">
                                            {{-- Detail Header --}}
                                            <div class="d-flex justify-content-between align-items-center flex-wrap pb-2 mb-3 border-bottom">
                                                <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                                                    <h6 class="font-weight-bold text-dark mb-0" style="font-size: 0.95rem;">
                                                        <i class="fas fa-receipt text-primary mr-1"></i>
                                                        Rincian Item Belanja Nota: <span class="text-primary font-monospace">{{ $nota->id_display }}</span>
                                                    </h6>
                                                    @if($nota->evidence_number)
                                                        <span class="badge badge-light border text-dark font-weight-bold">
                                                            <i class="fas fa-file-invoice text-info mr-1"></i> No. Nota: {{ $nota->evidence_number }}
                                                        </span>
                                                    @endif
                                                    <span class="badge badge-light border text-dark font-weight-bold">
                                                        <i class="fas fa-store text-muted mr-1"></i> {{ $nota->description }}
                                                    </span>
                                                    <span class="badge badge-light border text-dark font-weight-bold">
                                                        <i class="fas fa-user-check text-muted mr-1"></i> {{ $nota->person_name }}
                                                    </span>
                                                    <span class="badge {{ $nota->sync_badge }} font-weight-bold">
                                                        {{ $nota->sync_label }}
                                                    </span>
                                                </div>
                                                <div class="d-flex align-items-center mt-2 mt-md-0" style="gap: 6px;">
                                                    <button type="button" class="btn btn-xs btn-primary font-weight-bold btn-kelola-nota-item"
                                                            data-id="{{ $nota->id }}"
                                                            data-desc="{{ $nota->description }}"
                                                            data-evidence="{{ $nota->evidence_number }}"
                                                            data-amount="{{ $nota->amount }}">
                                                        <i class="fas fa-edit mr-1"></i> Tambah / Edit Item Nota Ini
                                                    </button>
                                                    <button type="button" class="btn btn-xs btn-outline-secondary btn-close-nota-detail" data-id="{{ $nota->id }}">
                                                        <i class="fas fa-times mr-1"></i> Tutup
                                                    </button>
                                                </div>
                                            </div>

                                            {{-- Mini Financial Summary Cards --}}
                                            <div class="row g-2 mb-3">
                                                <div class="col-md-4 mb-2 mb-md-0">
                                                    <div class="p-2 rounded bg-light border">
                                                        <small class="text-muted text-uppercase d-block" style="font-size: 0.68rem; font-weight: 700; letter-spacing: 0.5px;">Nominal Transaksi Kas</small>
                                                        <strong class="text-dark" style="font-size: 1.05rem;">{{ $nota->formatted_amount }}</strong>
                                                        <small class="text-muted d-block" style="font-size: 0.72rem;">Tanggal: {{ $nota->formatted_date }}</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-4 mb-2 mb-md-0">
                                                    <div class="p-2 rounded bg-light border">
                                                        <small class="text-muted text-uppercase d-block" style="font-size: 0.68rem; font-weight: 700; letter-spacing: 0.5px;">Akumulasi Rincian Barang</small>
                                                        <strong class="text-primary" style="font-size: 1.05rem;">{{ $nota->formatted_items_total }}</strong>
                                                        <small class="text-muted d-block" style="font-size: 0.72rem;">{{ $nota->items_count }} item barang tercatat</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-4 mb-2 mb-md-0">
                                                    <div class="p-2 rounded bg-light border">
                                                        <small class="text-muted text-uppercase d-block" style="font-size: 0.68rem; font-weight: 700; letter-spacing: 0.5px;">Status Keselarasan Nota</small>
                                                        <strong class="{{ $nota->sync_status === 'match' ? 'text-success' : ($nota->sync_status === 'empty' ? 'text-secondary' : 'text-danger') }}" style="font-size: 1.05rem;">
                                                            {{ $nota->sync_label }}
                                                        </strong>
                                                        <small class="text-muted d-block" style="font-size: 0.72rem;">Kesesuaian nilai nota vs rincian barang</small>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Items Breakdown Table --}}
                                            <div class="table-responsive border rounded mb-2 bg-white">
                                                <table class="table table-sm table-striped mb-0" style="font-size: 0.8rem;">
                                                    <thead class="bg-light text-muted">
                                                        <tr>
                                                            <th style="width: 4%;" class="text-center">#</th>
                                                            <th style="width: 32%;">Nama Barang / Komoditas & Spesifikasi</th>
                                                            <th style="width: 10%;" class="text-center">Qty</th>
                                                            <th style="width: 10%;" class="text-center">Satuan</th>
                                                            <th style="width: 16%;" class="text-right">Harga Satuan (Rp)</th>
                                                            <th style="width: 16%;" class="text-right">Subtotal (Rp)</th>
                                                            <th style="width: 12%;">Catatan</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse($nota->items as $itemIdx => $item)
                                                            <tr>
                                                                <td class="text-center font-weight-bold text-muted">{{ $itemIdx + 1 }}</td>
                                                                <td>
                                                                    <strong class="text-dark">{{ $item->item_name }}</strong>
                                                                    @if($item->notes)
                                                                        <div class="small text-muted">{{ $item->notes }}</div>
                                                                    @endif
                                                                </td>
                                                                <td class="text-center font-weight-bold">
                                                                    {{ (float) $item->quantity == intval($item->quantity) ? intval($item->quantity) : number_format($item->quantity, 2, ',', '.') }}
                                                                </td>
                                                                <td class="text-center text-muted">
                                                                    {{ $item->unit ?: '-' }}
                                                                </td>
                                                                <td class="text-right">
                                                                    Rp {{ number_format($item->price, 0, ',', '.') }}
                                                                </td>
                                                                <td class="text-right font-weight-bold text-dark">
                                                                    Rp {{ number_format($item->total_price, 0, ',', '.') }}
                                                                </td>
                                                                <td>
                                                                    <span class="text-muted">{{ $item->notes ?: '-' }}</span>
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="7" class="text-center text-muted py-3">
                                                                    <i class="fas fa-boxes fa-2x mb-2 text-warning d-block"></i>
                                                                    Belum ada rincian item barang yang diinput untuk nota ini.
                                                                    <div class="mt-2">
                                                                        <button type="button" class="btn btn-xs btn-primary font-weight-bold btn-kelola-nota-item"
                                                                                data-id="{{ $nota->id }}"
                                                                                data-desc="{{ $nota->description }}"
                                                                                data-evidence="{{ $nota->evidence_number }}"
                                                                                data-amount="{{ $nota->amount }}">
                                                                            <i class="fas fa-plus-circle mr-1"></i> Input Rincian Item Barang Sekarang
                                                                        </button>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                    @if($nota->items_count > 0)
                                                        <tfoot class="bg-light font-weight-bold">
                                                            <tr>
                                                                <td colspan="5" class="text-right">Total Akumulasi Rincian Barang:</td>
                                                                <td class="text-right text-primary">{{ $nota->formatted_items_total }}</td>
                                                                <td></td>
                                                            </tr>
                                                        </tfoot>
                                                    @endif
                                                </table>
                                            </div>

                                            {{-- Lampiran Foto Nota / Kwitansi --}}
                                            @if($nota->receipt_file_url)
                                                <div class="p-2 border rounded bg-light d-flex align-items-center justify-content-between flex-wrap mt-2">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-paperclip text-info fa-lg mr-2"></i>
                                                        <div>
                                                            <strong class="text-dark">Foto Bukti Nota / Kwitansi Tersedia</strong>
                                                            <small class="text-muted d-block">Terlampir pada transaksi {{ $nota->id_display }}</small>
                                                        </div>
                                                    </div>
                                                    <button type="button" class="btn btn-xs btn-outline-info btn-preview-receipt-inline font-weight-bold mt-1 mt-md-0"
                                                            data-url="{{ $nota->receipt_file_url }}"
                                                            data-is-image="{{ $nota->is_receipt_image ? '1' : '0' }}"
                                                            data-title="Bukti Nota {{ $nota->id_display }}"
                                                            data-desc="{{ $nota->description }}"
                                                            data-person="{{ $nota->person_name }}"
                                                            data-evidence="{{ $nota->evidence_number }}"
                                                            data-date="{{ $nota->formatted_date }}">
                                                        <i class="fas fa-search-plus mr-1"></i> Pratinjau Bukti Nota
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="fas fa-receipt fa-2x mb-2 text-muted d-block"></i>
                                        Tidak ada data transaksi nota belanja tunai untuk periode yang dipilih.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- TAB 2: RINCIAN SELURUH ITEM BARANG (DATATABLES FLAT) --}}
            <div class="tab-pane fade" id="tab-semua-item" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                    <small class="text-muted">
                        <i class="fas fa-info-circle text-info mr-1"></i> Menampilkan seluruh komoditas barang per-baris item dengan pencarian cepat.
                    </small>
                    <button type="button" class="btn btn-xs btn-outline-primary d-md-none shadow-xs" id="btnToggleMobileCards" title="Ganti Tampilan Kartu / Tabel">
                        <i class="fas fa-table mr-1"></i> <span id="textToggleView">Mode Tabel</span>
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-striped w-100 table-mobile-cards table-belanja" id="belanjaBarangTable">
                        <thead>
                            <tr>
                                <th style="width: 4%;" class="text-center">No</th>
                                <th style="width: 10%;">Tanggal</th>
                                <th style="width: 25%;">Nama Barang & Catatan</th>
                                <th style="width: 9%;" class="text-center">Qty</th>
                                <th style="width: 12%;" class="text-right">Harga Satuan</th>
                                <th style="width: 12%;" class="text-right">Total Harga</th>
                                <th style="width: 13%;">Toko / Keterangan</th>
                                <th style="width: 13%;">Pembeli / Guru</th>
                                <th style="width: 10%;" class="text-center">No. Nota</th>
                                <th style="width: 8%;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Server-side DataTables --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL: FORM INPUT ITEM BELANJA TUNAI --}}
<div class="modal fade" id="modalInputItemBelanja" tabindex="-1" role="dialog" aria-labelledby="modalInputItemBelanjaLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white py-2 px-3">
                <h5 class="modal-title font-weight-bold" id="modalInputItemBelanjaLabel" style="font-size: 1rem;">
                    <i class="fas fa-cart-plus mr-1"></i> Form Input Item Belanja Tunai
                </h5>
                <button type="button" class="close text-white btn-dismiss-modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form id="formInputItemBelanja" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-3">
                    {{-- Mode Selection: Nota Ada vs Nota Baru --}}
                    <div class="p-2 mb-3 rounded bg-light border d-flex justify-content-between align-items-center flex-wrap" style="gap: 8px;">
                        <div class="font-weight-bold text-dark" style="font-size: 0.85rem;">
                            <i class="fas fa-exchange-alt text-primary mr-1"></i> Pilih Opsi Pencatatan:
                        </div>
                        <div class="btn-group btn-group-toggle" data-toggle="buttons">
                            <label class="btn btn-sm btn-outline-primary active font-weight-bold" id="labelModeExisting">
                                <input type="radio" name="input_mode" id="modeExisting" value="existing" checked>
                                <i class="fas fa-receipt mr-1"></i> Input Item ke Nota yang Sudah Ada
                            </label>
                            <label class="btn btn-sm btn-outline-primary font-weight-bold" id="labelModeNew">
                                <input type="radio" name="input_mode" id="modeNew" value="new">
                                <i class="fas fa-plus-circle mr-1"></i> Catat Belanja Nota Baru
                            </label>
                        </div>
                    </div>

                    {{-- Container Mode: Pilih Nota yang Sudah Ada --}}
                    <div id="containerModeExisting" class="mb-3">
                        <div class="form-group mb-2">
                            <label for="select_kas_tunai_id" class="font-weight-bold text-dark" style="font-size: 0.82rem;">
                                Pilih Transaksi Nota Pengeluaran Kas: <span class="text-danger">*</span>
                            </label>
                            <select id="select_kas_tunai_id" name="kas_tunai_id" class="form-control form-control-sm">
                                <option value="">-- Pilih Nota Transaksi Kas Keluar --</option>
                                @foreach($notaBelanjaList as $n)
                                    <option value="{{ $n->id }}"
                                            data-desc="{{ $n->description }}"
                                            data-evidence="{{ $n->evidence_number }}"
                                            data-amount="{{ $n->amount }}"
                                            data-date="{{ $n->formatted_date }}"
                                            data-person="{{ $n->person_name }}">
                                        [{{ $n->id_display }}] {{ $n->formatted_date }} &bull; {{ $n->description }} &bull; Rp {{ number_format($n->amount, 0, ',', '.') }} ({{ $n->person_name }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Alert info ringkas nota terpilih --}}
                        <div id="boxInfoNotaSelected" class="p-2 rounded bg-white border border-info mb-2" style="display: none; font-size: 0.8rem;">
                            <div class="d-flex justify-content-between align-items-center flex-wrap">
                                <div>
                                    <span class="text-muted">Toko/Keterangan:</span> <strong id="infoNotaDesc" class="text-dark">-</strong> &bull;
                                    <span class="text-muted">No. Bukti:</span> <span id="infoNotaEvidence" class="badge badge-light border">-</span> &bull;
                                    <span class="text-muted">Pembeli:</span> <strong id="infoNotaPerson" class="text-dark">-</strong>
                                </div>
                                <div>
                                    <span class="text-muted">Nominal Kas:</span> <strong id="infoNotaAmount" class="text-primary font-weight-bold">-</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Container Mode: Buat Transaksi Belanja Nota Baru --}}
                    <div id="containerModeNew" class="mb-3 p-3 rounded bg-light border" style="display: none;">
                        <h6 class="font-weight-bold text-primary mb-2 border-bottom pb-1" style="font-size: 0.85rem;">
                            <i class="fas fa-file-invoice mr-1"></i> Identitas Transaksi Belanja Baru
                        </h6>
                        <div class="row g-2">
                            <div class="col-md-3 mb-2">
                                <label class="font-weight-bold text-dark small">Tanggal Transaksi: <span class="text-danger">*</span></label>
                                <input type="date" name="transaction_date" id="input_transaction_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-md-5 mb-2">
                                <label class="font-weight-bold text-dark small">Penanggung Jawab / Guru Pembeli: <span class="text-danger">*</span></label>
                                <select name="guru_id" id="input_guru_id" class="form-control form-control-sm">
                                    <option value="">-- Pilih Guru / Staf Pembeli --</option>
                                    @foreach($gurus as $g)
                                        <option value="{{ $g->id }}">{{ $g->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="font-weight-bold text-dark small">No. Nota / Bukti Fisik:</label>
                                <input type="text" name="evidence_number" id="input_evidence_number" class="form-control form-control-sm" placeholder="Contoh: INV-0921, Nota #550">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="font-weight-bold text-dark small">Toko / Penyedia / Uraian Belanja: <span class="text-danger">*</span></label>
                                <input type="text" name="description" id="input_description" class="form-control form-control-sm" placeholder="Contoh: Toko Buku Murah - Pembelian ATK Kantor">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="font-weight-bold text-dark small">Upload Foto Bukti Nota (Opsional):</label>
                                <input type="file" name="receipt_file" id="input_receipt_file" class="form-control-file form-control-sm border rounded p-1 bg-white" accept="image/*,application/pdf">
                                <small class="text-muted d-block" style="font-size: 0.7rem;">Format JPG, PNG, WEBP, atau PDF (maksimal 20 MB)</small>
                            </div>
                        </div>
                    </div>

                    {{-- Tabel Dinamis Rincian Item Barang --}}
                    <div class="border rounded p-2 bg-white">
                        <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
                            <div>
                                <h6 class="font-weight-bold text-dark mb-0" style="font-size: 0.9rem;">
                                    <i class="fas fa-list-ol text-primary mr-1"></i> Rincian Barang yang Dibeli dalam 1 Nota
                                </h6>
                                <small class="text-muted">Isi seluruh item barang yang tertera pada nota belanja.</small>
                            </div>
                            <button type="button" class="btn btn-xs btn-success font-weight-bold" id="btnAddItemRow">
                                <i class="fas fa-plus mr-1"></i> Tambah Baris Barang
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-1" id="tableInputItems" style="font-size: 0.8rem;">
                                <thead class="bg-light text-muted">
                                    <tr>
                                        <th style="width: 4%;" class="text-center">#</th>
                                        <th style="width: 32%;">Nama Barang / Komoditas <span class="text-danger">*</span></th>
                                        <th style="width: 10%;" class="text-center">Qty <span class="text-danger">*</span></th>
                                        <th style="width: 12%;" class="text-center">Satuan</th>
                                        <th style="width: 16%;" class="text-right">Harga Satuan (Rp) <span class="text-danger">*</span></th>
                                        <th style="width: 16%;" class="text-right">Subtotal (Rp)</th>
                                        <th style="width: 6%;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyInputItems">
                                    {{-- Dynamic Rows populated via JS --}}
                                </tbody>
                                <tfoot class="bg-light font-weight-bold">
                                    <tr>
                                        <td colspan="5" class="text-right align-middle">
                                            Total Akumulasi Rincian Barang:
                                        </td>
                                        <td class="text-right align-middle text-primary" style="font-size: 0.95rem;">
                                            Rp <span id="labelTotalItemBelanja">0</span>
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        {{-- Datalist Satuan Standar --}}
                        <datalist id="listSatuan">
                            <option value="Rim">
                            <option value="Pcs">
                            <option value="Box">
                            <option value="Buah">
                            <option value="Lembar">
                            <option value="Pack">
                            <option value="Paket">
                            <option value="Kg">
                            <option value="Liter">
                            <option value="Meter">
                            <option value="Roll">
                            <option value="Botol">
                            <option value="Set">
                            <option value="Unit">
                            <option value="Lusin">
                            <option value="Dus">
                        </datalist>

                        {{-- Checkbox Penyesuaian Nominal Transaksi --}}
                        <div class="form-check mt-2 pt-1 border-top">
                            <input class="form-check-input" type="checkbox" id="checkSyncAmount" name="sync_amount" value="1" checked>
                            <label class="form-check-label font-weight-bold text-dark" for="checkSyncAmount" style="font-size: 0.8rem; cursor: pointer;">
                                <i class="fas fa-sync text-info mr-1"></i> Sesuaikan total nilai kas tunai dengan total rincian barang (<span id="textSyncAmountPreview" class="text-primary font-weight-bold">Rp 0</span>)
                            </label>
                            <small class="text-muted d-block" style="font-size: 0.72rem;">Jika dicentang, nominal belanja kas tunai akan otomatis sama dengan total penjumlahan seluruh item di atas.</small>
                        </div>
                    </div>
                </div>

                <div class="modal-footer py-2 px-3 bg-light d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-dismiss-modal">
                        <i class="fas fa-times mr-1"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-sm btn-primary font-weight-bold" id="btnSubmitItemBelanja">
                        <i class="fas fa-save mr-1"></i> Simpan Rincian Item Belanja
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modals --}}
@include('kas_tunai.partials.modal_receipt_items')
@include('kas_tunai.partials.mobile_fab_entry')
@include('kas_tunai.partials.modal_preview_receipt')

@stop

@section('js')
<script>
$(document).ready(function() {
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

    // Helper Rupiah Formatting
    function formatRupiah(num) {
        return Number(num || 0).toLocaleString('id-ID');
    }
    function parseRupiah(str) {
        if (!str) return 0;
        let clean = String(str).replace(/[^0-9]/g, '');
        return Number(clean || 0);
    }

    // ==========================================
    // Expandable Row Nota Belanja (Tab 1)
    // ==========================================
    $(document).on('click', '.clickable-nota-row', function(e) {
        if ($(e.target).closest('.btn, a, input, select').length) {
            return;
        }
        let id = $(this).data('id');
        let $detailRow = $('#notaDetailRow-' + id);
        let isExpanded = $(this).hasClass('row-expanded');

        if (isExpanded) {
            $(this).removeClass('row-expanded');
            $detailRow.slideUp(180);
        } else {
            $(this).addClass('row-expanded');
            $detailRow.slideDown(200);
        }
    });

    $(document).on('click', '.btn-close-nota-detail', function(e) {
        e.preventDefault();
        let id = $(this).data('id');
        let $row = $('.clickable-nota-row[data-id="' + id + '"]');
        $row.removeClass('row-expanded');
        $('#notaDetailRow-' + id).slideUp(180);
    });

    // ==========================================
    // DataTables (Tab 2)
    // ==========================================
    let dataTable = $('#belanjaBarangTable').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        order: [[1, 'desc']],
        ajax: {
            url: "{{ route($routePrefix . '.kas-tunai.belanja-barang.data', [], false) }}",
            data: function(d) {
                d.year = $('#filterYear').val();
                d.month = $('#filterMonth').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center font-weight-bold' },
            { data: 'date', name: 'kasTunai.transaction_date' },
            { data: 'item_name_formatted', name: 'item_name' },
            { data: 'quantity_formatted', name: 'quantity', className: 'text-center' },
            { data: 'price_formatted', name: 'price', className: 'text-right' },
            { data: 'total_price_formatted', name: 'total_price', className: 'text-right font-weight-bold text-dark' },
            { data: 'supplier', name: 'kasTunai.description' },
            { data: 'person_name', name: 'kasTunai.guru.name' },
            { data: 'evidence_number', name: 'kasTunai.evidence_number', className: 'text-center' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        language: {
            processing: '<i class="fas fa-spinner fa-spin fa-2x"></i> Memuat data...',
            search: "Cari Barang:",
            lengthMenu: "Tampil _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ item barang",
            infoEmpty: "Tidak ada data item belanja",
            infoFiltered: "(disaring dari _MAX_ total item)",
            zeroRecords: "Tidak ditemukan data barang yang sesuai",
            paginate: {
                first: "Awal",
                last: "Akhir",
                next: "Selanjutnya",
                previous: "Sebelumnya"
            }
        }
    });

    // Toggle Mobile Cards
    $('#btnToggleMobileCards').on('click', function() {
        let $t = $('#belanjaBarangTable');
        $t.toggleClass('force-table-view');
        if ($t.hasClass('force-table-view')) {
            $('#textToggleView').text('Mode Kartu');
            $(this).find('i').removeClass('fa-table').addClass('fa-id-card');
        } else {
            $('#textToggleView').text('Mode Tabel');
            $(this).find('i').removeClass('fa-id-card').addClass('fa-table');
        }
    });

    // Filter Year & Month Change
    $('#filterYear, #filterMonth').on('change', function() {
        let y = $('#filterYear').val();
        let m = $('#filterMonth').val();
        let queryParams = new URLSearchParams(window.location.search);
        queryParams.set('year', y);
        if (m) queryParams.set('month', m);
        else queryParams.delete('month');
        window.location.search = queryParams.toString();
    });

    $('#btnReloadTable').on('click', function() {
        location.reload();
    });

    // ==========================================
    // Modal Form Input Item Belanja Tunai
    // ==========================================
    let itemRowCount = 0;

    function createItemRowHtml(idx, item = {}) {
        let name = item.item_name || '';
        let qty = item.quantity || 1;
        let unit = item.unit || '';
        let price = item.price ? formatRupiah(item.price) : '';
        let total = item.total_price ? formatRupiah(item.total_price) : '';
        let notes = item.notes || '';

        return `
            <tr class="item-input-row" data-row="${idx}">
                <td class="text-center font-weight-bold text-muted align-middle row-number">${idx}</td>
                <td>
                    <input type="text" name="items[${idx}][item_name]" class="form-control form-control-sm item-name" placeholder="Nama barang / komoditas *" value="${name}" required>
                    <input type="text" name="items[${idx}][notes]" class="form-control form-control-sm item-notes mt-1" placeholder="Catatan spesifikasi (opsional)" value="${notes}" style="font-size: 0.74rem;">
                </td>
                <td>
                    <input type="number" step="any" min="0.01" name="items[${idx}][quantity]" class="form-control form-control-sm text-center item-qty" value="${qty}" required>
                </td>
                <td>
                    <input type="text" list="listSatuan" name="items[${idx}][unit]" class="form-control form-control-sm text-center item-unit" placeholder="Pcs/Rim" value="${unit}">
                </td>
                <td>
                    <input type="text" name="items[${idx}][price]" class="form-control form-control-sm text-right item-price rupiah-input" placeholder="0" value="${price}" required>
                </td>
                <td>
                    <input type="text" name="items[${idx}][total_price]" class="form-control form-control-sm text-right font-weight-bold item-subtotal bg-light" placeholder="0" value="${total}" readonly>
                </td>
                <td class="text-center align-middle">
                    <button type="button" class="btn btn-xs btn-outline-danger btn-remove-item-row" title="Hapus baris barang ini">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }

    function renumberItemRows() {
        itemRowCount = 0;
        $('#tbodyInputItems tr.item-input-row').each(function() {
            itemRowCount++;
            $(this).attr('data-row', itemRowCount);
            $(this).find('.row-number').text(itemRowCount);
        });
        calculateItemsTotal();
    }

    function calculateItemsTotal() {
        let grandTotal = 0;
        $('#tbodyInputItems tr.item-input-row').each(function() {
            let qty = parseFloat($(this).find('.item-qty').val()) || 0;
            let price = parseRupiah($(this).find('.item-price').val());
            let subtotal = Math.round(qty * price);
            $(this).find('.item-subtotal').val(formatRupiah(subtotal));
            grandTotal += subtotal;
        });

        $('#labelTotalItemBelanja').text(formatRupiah(grandTotal));
        $('#textSyncAmountPreview').text('Rp ' + formatRupiah(grandTotal));
    }

    function addItemRow(itemData = {}) {
        itemRowCount++;
        let html = createItemRowHtml(itemRowCount, itemData);
        $('#tbodyInputItems').append(html);
        calculateItemsTotal();
    }

    $('#btnAddItemRow').on('click', function() {
        addItemRow();
    });

    $(document).on('click', '.btn-remove-item-row', function() {
        if ($('#tbodyInputItems tr.item-input-row').length <= 1) {
            alert('Minimal satu baris rincian barang harus tetap ada.');
            return;
        }
        $(this).closest('tr').remove();
        renumberItemRows();
    });

    $(document).on('input', '.item-qty', function() {
        calculateItemsTotal();
    });

    $(document).on('input', '.rupiah-input', function() {
        let val = parseRupiah($(this).val());
        $(this).val(val > 0 ? formatRupiah(val) : '');
        calculateItemsTotal();
    });

    // Toggle Input Mode (Existing Nota vs New Nota)
    $('input[name="input_mode"]').on('change', function() {
        let mode = $(this).val();
        if (mode === 'new') {
            $('#labelModeNew').addClass('active');
            $('#labelModeExisting').removeClass('active');
            $('#containerModeExisting').slideUp(150);
            $('#containerModeNew').slideDown(150);
            $('#select_kas_tunai_id').removeAttr('required');
            $('#input_transaction_date, #input_guru_id, #input_description').attr('required', true);
        } else {
            $('#labelModeExisting').addClass('active');
            $('#labelModeNew').removeClass('active');
            $('#containerModeNew').slideUp(150);
            $('#containerModeExisting').slideDown(150);
            $('#select_kas_tunai_id').attr('required', true);
            $('#input_transaction_date, #input_guru_id, #input_description').removeAttr('required');
        }
    });

    // When selecting existing nota in dropdown
    $('#select_kas_tunai_id').on('change', function() {
        let id = $(this).val();
        if (!id) {
            $('#boxInfoNotaSelected').hide();
            return;
        }

        let $opt = $(this).find('option:selected');
        $('#infoNotaDesc').text($opt.data('desc') || '-');
        $('#infoNotaEvidence').text($opt.data('evidence') || 'Tidak ada no nota');
        $('#infoNotaPerson').text($opt.data('person') || '-');
        $('#infoNotaAmount').text('Rp ' + formatRupiah($opt.data('amount')));
        $('#boxInfoNotaSelected').fadeIn(150);

        // Load existing items via AJAX
        let url = "{{ url($routePrefix . '/kas-tunai') }}/" + id + "/items";
        $('#tbodyInputItems').html('<tr><td colspan="7" class="text-center py-3 text-muted"><i class="fas fa-spinner fa-spin mr-1"></i> Memuat rincian barang...</td></tr>');

        $.ajax({
            url: url,
            type: 'GET',
            success: function(res) {
                $('#tbodyInputItems').empty();
                itemRowCount = 0;
                if (res.items && res.items.length > 0) {
                    res.items.forEach(function(it) {
                        addItemRow(it);
                    });
                } else {
                    addItemRow(); // 1 blank row
                }
            },
            error: function() {
                $('#tbodyInputItems').empty();
                addItemRow();
            }
        });
    });

    // Trigger Open Modal Input Item (New / Clean)
    $('#btnOpenModalInputItem, .btn-trigger-input-item').on('click', function(e) {
        e.preventDefault();
        $('#formInputItemBelanja')[0].reset();
        $('#modeExisting').prop('checked', true).trigger('change');
        $('#select_kas_tunai_id').val('').trigger('change');
        $('#boxInfoNotaSelected').hide();
        $('#tbodyInputItems').empty();
        itemRowCount = 0;
        addItemRow(); // 1 blank row
        showKasModal('#modalInputItemBelanja');
    });

    // Trigger Open Modal for a Specific Nota
    $(document).on('click', '.btn-kelola-nota-item', function(e) {
        e.preventDefault();
        let id = $(this).data('id');
        if (!id) return;

        $('#formInputItemBelanja')[0].reset();
        $('#modeExisting').prop('checked', true).trigger('change');
        $('#select_kas_tunai_id').val(id).trigger('change');
        showKasModal('#modalInputItemBelanja');
    });

    // Submit Form Input Item Belanja
    $('#formInputItemBelanja').on('submit', function(e) {
        e.preventDefault();

        let mode = $('input[name="input_mode"]:checked').val();
        let url = '';
        if (mode === 'new') {
            url = "{{ route($routePrefix . '.kas-tunai.belanja-barang.store') }}";
        } else {
            let kasId = $('#select_kas_tunai_id').val();
            if (!kasId) {
                alert('Silakan pilih nota transaksi kas keluar yang akan diisi item barangnya.');
                return;
            }
            url = "{{ url($routePrefix . '/kas-tunai') }}/" + kasId + "/save-items";
        }

        // Validate items count
        let hasItem = false;
        $('#tbodyInputItems tr.item-input-row').each(function() {
            let name = $(this).find('.item-name').val().trim();
            if (name) hasItem = true;
        });
        if (!hasItem) {
            alert('Minimal satu baris rincian barang harus diisi nama barangnya.');
            return;
        }

        let formData = new FormData(this);
        formData.forEach((value, key) => {
            if (key.match(/^items\[\d+\]\[(price|total_price)\]$/) && typeof value === 'string') {
                formData.set(key, value.replace(/[^0-9]/g, ''));
            }
        });
        let $btn = $('#btnSubmitItemBelanja');
        let originalText = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                hideKasModal('#modalInputItemBelanja');
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message || 'Rincian item barang belanja berhasil disimpan.',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    alert(res.message || 'Rincian item barang belanja berhasil disimpan.');
                    location.reload();
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html(originalText);
                let msg = 'Gagal menyimpan item belanja.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    let firstErr = Object.values(xhr.responseJSON.errors)[0];
                    if (firstErr && firstErr[0]) msg = firstErr[0];
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Perhatian', text: msg });
                } else {
                    alert(msg);
                }
            }
        });
    });

    // ==========================================
    // View Receipt Details Modal (Modal Existing)
    // ==========================================
    $(document).on('click', '.btn-view-receipt-items', function(e) {
        e.preventDefault();
        let id = $(this).data('id');
        if (!id) return;

        let url = "{{ url($routePrefix . '/kas-tunai') }}/" + id + "/items";

        $('#tbodyReceiptDetails').html('<tr><td colspan="6" class="text-center py-3"><i class="fas fa-spinner fa-spin mr-1"></i> Memuat rincian nota...</td></tr>');
        $('#containerReceiptAttachment').hide();
        showKasModal('#modalViewReceiptItems');

        $.ajax({
            url: url,
            type: 'GET',
            success: function(res) {
                let kas = res.kas_tunai;
                $('#viewItemDateCategory').text(kas.transaction_date + ' (' + kas.category_label + ')');
                $('#viewItemDescription').text(kas.description);
                $('#viewItemPerson').text(kas.person_name);
                $('#viewItemEvidence').text(kas.evidence_number);
                $('#viewItemTotalAmount').text(kas.formatted_amount);
                $('#viewItemSumTotal').text(res.formatted_total_items_amount);

                let tbody = '';
                if (res.items && res.items.length > 0) {
                    res.items.forEach(function(item) {
                        tbody += `
                            <tr>
                                <td class="text-center">${item.index}</td>
                                <td>
                                    <strong>${item.item_name}</strong>
                                    ${item.notes ? '<br><small class="text-muted">' + item.notes + '</small>' : ''}
                                </td>
                                <td class="text-center">${item.quantity}</td>
                                <td class="text-center">${item.unit}</td>
                                <td class="text-right">${item.formatted_price}</td>
                                <td class="text-right font-weight-bold text-dark">${item.formatted_total_price}</td>
                            </tr>
                        `;
                    });
                } else {
                    tbody = '<tr><td colspan="6" class="text-center text-muted py-3">Tidak ada rincian barang per-item untuk transaksi ini.</td></tr>';
                }
                $('#tbodyReceiptDetails').html(tbody);

                // Receipt Attachment
                if (kas.receipt_url) {
                    $('#containerReceiptAttachment').show();
                    if (kas.receipt_is_image) {
                        $('#receiptAttachmentContent').html(`
                            <a href="javascript:void(0)" class="btn-preview-receipt-inline d-inline-block" data-url="${kas.receipt_url}" data-is-image="1" data-title="Bukti ${kas.id_display || ('KT' + String(kas.id))}" data-desc="${(kas.description || '').replace(/"/g, '&quot;')}" data-person="${(kas.person_name || '').replace(/"/g, '&quot;')}" data-evidence="${(kas.evidence_number || '').replace(/"/g, '&quot;')}" data-date="${kas.transaction_date || ''}" title="Klik untuk pratinjau modal popup">
                                <img src="${kas.receipt_url}" class="img-fluid rounded border shadow-sm" style="max-height: 380px; object-fit: contain; cursor: pointer;">
                            </a>
                            <div class="mt-2">
                                <button type="button" class="btn btn-xs btn-outline-info btn-preview-receipt-inline" data-url="${kas.receipt_url}" data-is-image="1" data-title="Bukti ${kas.id_display || ('KT' + String(kas.id))}" data-desc="${(kas.description || '').replace(/"/g, '&quot;')}" data-person="${(kas.person_name || '').replace(/"/g, '&quot;')}" data-evidence="${(kas.evidence_number || '').replace(/"/g, '&quot;')}" data-date="${kas.transaction_date || ''}">
                                    <i class="fas fa-search-plus mr-1"></i> Perbesar Bukti Foto (Modal Popup)
                                </button>
                            </div>
                        `);
                    } else {
                        $('#receiptAttachmentContent').html(`
                            <div class="py-3">
                                <i class="fas fa-file-pdf text-danger fa-3x mb-2 d-block"></i>
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-preview-receipt-inline" data-url="${kas.receipt_url}" data-is-image="0" data-title="Dokumen ${kas.id_display || ('KT' + String(kas.id))}" data-desc="${(kas.description || '').replace(/"/g, '&quot;')}" data-person="${(kas.person_name || '').replace(/"/g, '&quot;')}" data-evidence="${(kas.evidence_number || '').replace(/"/g, '&quot;')}" data-date="${kas.transaction_date || ''}">
                                    <i class="fas fa-file-pdf text-danger mr-1"></i> Pratinjau Dokumen Nota (${kas.receipt_url.split('.').pop().toUpperCase()})
                                </button>
                            </div>
                        `);
                    }
                }
            },
            error: function() {
                $('#tbodyReceiptDetails').html('<tr><td colspan="6" class="text-center text-danger py-3"><i class="fas fa-exclamation-triangle mr-1"></i> Gagal memuat rincian nota.</td></tr>');
            }
        });
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
        if ($('#modalViewReceiptItems').hasClass('show') || $('#modalInputItemBelanja').hasClass('show')) {
            $('body').addClass('modal-open');
        }
        $('#previewReceiptImage').attr('src', '');
        $('#previewReceiptPdfFrame').attr('src', '');
    });
});
</script>
@stop
