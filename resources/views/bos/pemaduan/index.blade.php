@extends('adminlte::page')

@section('title', 'Pemaduan RRKAS & Kertas Kerja BOS')

@section('plugins.Sweetalert2', true)

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap" style="padding: 2px 0;">
    <div>
        <h1 class="page-title">
            <i class="fas fa-arrows-split-up-and-left text-primary mr-1"></i> Pemaduan RRKAS & Kertas Kerja BOS
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-house"></i> Dashboard</a></li>
                <li class="breadcrumb-item">BOSP</li>
                <li class="breadcrumb-item active">Pemaduan RKAS</li>
            </ol>
        </nav>
    </div>
    <div class="header-actions d-flex align-items-center flex-wrap" style="gap: 8px;">
        <span class="badge badge-success px-3 py-2 font-weight-bold" style="font-size: 0.85rem;">
            <i class="fas fa-check-circle mr-1"></i> Terhubung: {{ $stats['paired'] }} / {{ $stats['total'] }} ({{ $stats['percent'] }}%)
        </span>
        @if($stats['unpaired'] > 0)
            <span class="badge badge-warning text-dark px-3 py-2 font-weight-bold" style="font-size: 0.85rem;">
                <i class="fas fa-exclamation-triangle mr-1"></i> {{ $stats['unpaired'] }} Belum Dipadukan
            </span>
        @endif
        <span class="badge badge-info text-white px-3 py-2 font-weight-bold" style="font-size: 0.85rem;" title="Jumlah seri belanja yang muncul di beberapa bulan">
            <i class="fas fa-repeat mr-1"></i> {{ $stats['recurring_groups'] ?? 0 }} Seri Berulang
        </span>
    </div>
</div>
@stop

@section('css')
<style>
    body, .content-wrapper, .card, table, .btn, .form-control {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
    }
    h1.page-title {
        font-size: 1.15rem !important;
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
    .table-mapping th {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        vertical-align: middle;
    }
    .table-mapping td {
        font-size: 0.85rem;
        vertical-align: top;
    }
    .paired-card {
        background-color: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 8px;
        padding: 10px 12px;
        margin-bottom: 6px;
    }
    .unpaired-box {
        border: 2px dashed #fcd34d;
        background-color: #fffbeb;
        border-radius: 8px;
        padding: 12px;
        text-align: center;
    }
    .pengajuan-option-card {
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        padding: 10px 12px;
        margin-bottom: 8px;
        cursor: pointer;
        transition: all 0.15s ease;
        background-color: #ffffff;
    }
    .pengajuan-option-card:hover {
        border-color: #3b82f6;
        background-color: #f8fafc;
    }
    .pengajuan-option-card.selected {
        border-color: #2563eb;
        background-color: #eff6ff;
    }
    .month-pill {
        display: inline-block;
        font-size: 0.70rem;
        padding: 1px 6px;
        border-radius: 4px;
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
        margin: 1px;
    }
    .month-pill.paired {
        background: #dcfce7;
        color: #166534;
        border-color: #bbf7d0;
        font-weight: bold;
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

    <!-- NAVIGASI 3 PILIHAN TAMPILAN (SEMUA RINCI / SERI BERULANG / BELANJA SEKALI) -->
    <div class="d-flex align-items-center flex-wrap mb-3" style="gap: 8px;">
        <a href="{{ route('bos.pemaduan.index', array_merge(request()->except(['page', 'view_mode', 'karakter']), ['view_mode' => 'single'])) }}"
           class="btn btn-sm {{ $viewMode === 'single' ? 'btn-primary shadow-sm font-weight-bold' : 'btn-outline-secondary bg-white' }}"
           style="border-radius: 8px; padding: 7px 16px;">
            <i class="fas fa-calendar-days mr-1"></i> 📄 Semua Data Rinci
            <span class="badge {{ $viewMode === 'single' ? 'badge-light text-primary' : 'badge-secondary' }} font-tabular ml-1">
                {{ $stats['total'] }}
            </span>
        </a>

        <a href="{{ route('bos.pemaduan.index', array_merge(request()->except(['page', 'view_mode', 'karakter']), ['view_mode' => 'grouped'])) }}"
           class="btn btn-sm {{ $viewMode === 'grouped' ? 'btn-primary shadow-sm font-weight-bold' : 'btn-outline-secondary bg-white' }}"
           style="border-radius: 8px; padding: 7px 16px;">
            <i class="fas fa-boxes-packing mr-1"></i> 📦 Seri Belanja Berulang
            <span class="badge {{ $viewMode === 'grouped' ? 'badge-light text-primary' : 'badge-primary' }} font-tabular ml-1">
                {{ $stats['recurring_groups'] ?? 0 }} Seri
            </span>
        </a>

        <a href="{{ route('bos.pemaduan.index', array_merge(request()->except(['page', 'view_mode', 'karakter']), ['view_mode' => 'sekali'])) }}"
           class="btn btn-sm {{ $viewMode === 'sekali' ? 'btn-primary shadow-sm font-weight-bold' : 'btn-outline-secondary bg-white' }}"
           style="border-radius: 8px; padding: 7px 16px;">
            <i class="fas fa-receipt mr-1"></i> 1️⃣ Belanja Sekali
            @if(isset($stats['single_items']))
                <span class="badge {{ $viewMode === 'sekali' ? 'badge-light text-primary' : 'badge-secondary' }} font-tabular ml-1">
                    {{ $stats['single_items'] }}
                </span>
            @endif
        </a>
    </div>

    <!-- FILTER CARD RINGKAS -->
    <div class="card shadow-sm border-0 mb-3" style="border-radius: 12px;">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('bos.pemaduan.index') }}" id="formFilter">
                <input type="hidden" name="view_mode" value="{{ $viewMode }}">

                <div class="row align-items-end">
                    <input type="hidden" name="year" value="{{ $selectedYear }}">

                    <!-- 1. Bulan Anggaran -->
                    <div class="col-md-4 col-sm-6 mb-2">
                        <label class="small font-weight-bold text-muted mb-1">
                            Bulan Anggaran
                            @if($viewMode === 'grouped')
                                <small class="text-info font-weight-normal ml-1">(Filter alokasi seri)</small>
                            @endif
                        </label>
                        <select name="month" class="form-control form-control-sm" onchange="document.getElementById('formFilter').submit();">
                            <option value="Semua" {{ $selectedMonth === 'Semua' ? 'selected' : '' }}>Semua Bulan</option>
                            @foreach($months as $m)
                                <option value="{{ $m }}" {{ $selectedMonth === $m ? 'selected' : '' }}>{{ $m }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 2. Status Pemaduan -->
                    <div class="col-md-4 col-sm-6 mb-2">
                        <label class="small font-weight-bold text-muted mb-1">Status Pemaduan</label>
                        <select name="status" class="form-control form-control-sm" onchange="document.getElementById('formFilter').submit();">
                            <option value="all" {{ $selectedStatus === 'all' ? 'selected' : '' }}>Semua Data ({{ $stats['total'] }})</option>
                            <option value="unpaired" {{ $selectedStatus === 'unpaired' ? 'selected' : '' }}>Belum Dipadukan ({{ $stats['unpaired'] }})</option>
                            <option value="paired" {{ $selectedStatus === 'paired' ? 'selected' : '' }}>Sudah Dipadukan ({{ $stats['paired'] }})</option>
                        </select>
                    </div>

                    <!-- 3. Link Rencana RKAS -->
                    <div class="col-md-4 col-sm-12 mb-2 text-right">
                        <a href="{{ route('bos.rkas.index') }}" class="btn btn-sm btn-outline-success font-weight-bold w-100" title="Kelola Rencana RKAS">
                            <i class="fas fa-calculator mr-1"></i> Kelola Rencana RKAS
                        </a>
                    </div>

                    <!-- 5. Search Bar Baris Bawah -->
                    <div class="col-12 mt-1">
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" class="form-control" placeholder="Cari nama barang SSH, rekening, kegiatan, nama barang riil, pemohon..." value="{{ $search }}">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-1"></i> Cari Data</button>
                                @if($search || $selectedMonth !== 'Semua' || $selectedStatus !== 'all' || $viewMode !== 'single')
                                    <a href="{{ route('bos.pemaduan.index', ['year' => $selectedYear, 'view_mode' => $viewMode]) }}" class="btn btn-default border" title="Reset Filter">
                                        <i class="fas fa-times mr-1"></i> Reset
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- MAIN TABLE CARD -->
    <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
        @if($viewMode === 'grouped')
            <!-- ======================================================= -->
            <!-- TAMPILAN TERKELOMPOK (GROUPED MODE)                     -->
            <!-- ======================================================= -->
            <div class="card-header bg-light py-2 px-3 border-bottom d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <span class="font-weight-bold text-dark" style="font-size: 0.92rem;">
                        <i class="fas fa-boxes-packing text-primary mr-1"></i> Tampilan Terkelompok per Seri Belanja
                    </span>
                    <span class="badge badge-info ml-2">{{ $groupedItems->total() }} Kelompok Seri</span>
                </div>
                <small class="text-muted">
                    Memadukan seluruh bulan sekaligus dalam 1 kali klik untuk belanja rutin (listrik, BBM, dll).
                </small>
            </div>

            <div class="card-body p-0 table-responsive">
                <table class="table table-hover table-striped mb-0 table-mapping">
                    <thead class="thead-light">
                        <tr>
                            <th class="text-center" style="width: 40px;">No</th>
                            <th style="width: 44%;">
                                <div><i class="fas fa-file-invoice text-primary mr-1"></i> Nama Barang SSH (Kertas Kerja BOS)</div>
                                <small class="text-muted font-weight-normal">Seri Belanja Resmi ARKAS Kemdikbud</small>
                            </th>
                            <th style="width: 42%;">
                                <div><i class="fas fa-boxes-stacked text-success mr-1"></i> Barang Riil yang Dipadukan</div>
                                <small class="text-muted font-weight-normal">Rencana RKAS Guru / Sekolah</small>
                            </th>
                            <th class="text-center" style="width: 14%;">Aksi Paduan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($groupedItems as $index => $group)
                            <tr>
                                <td class="text-center text-muted font-tabular align-middle font-weight-bold">
                                    {{ $groupedItems->firstItem() + $index }}
                                </td>

                                <!-- SISI KERTAS KERJA (NAMA BARANG SSH) -->
                                <td class="align-top">
                                    <!-- Baris 1: Nama Barang SSH -->
                                    <div class="font-weight-bold text-dark" style="font-size: 0.95rem;">
                                        {{ $group->description }}
                                    </div>

                                    <!-- Baris 2: [total_months] bulan | Kegiatan -->
                                    <div class="text-secondary small mt-1 font-tabular">
                                        <span class="font-weight-bold text-dark" title="Bulan alokasi: {{ implode(', ', $group->months_list) }}" style="cursor: help;">
                                            {{ $group->total_months }} bulan
                                        </span>
                                        @if($group->kegiatan)
                                            <span class="mx-1 text-muted">|</span>
                                            <span>{{ $group->kegiatan }}</span>
                                        @endif
                                    </div>

                                    <!-- Baris 3: Bulan-bulan alokasi penganggaran (misal: jan | feb ... atau mei | sep) -->
                                    <div class="mt-1 d-flex flex-wrap align-items-center font-tabular" style="gap: 3px; font-size: 0.8rem;">
                                        @foreach($group->monthly_items as $mIdx => $mItem)
                                            @php
                                                $mPaired = $mItem->pengajuanBarangs->isNotEmpty();
                                                $mName = strtolower(substr($mItem->month, 0, 3));
                                            @endphp
                                            @if($mIdx > 0)<span class="text-muted" style="opacity: 0.4;">|</span>@endif
                                            <span class="{{ $mPaired ? 'text-success font-weight-bold' : 'text-muted' }}"
                                                  title="{{ $mItem->month }}: {{ $mPaired ? 'Sudah terpadu' : 'Belum terpadu' }}"
                                                  style="{{ $mPaired ? 'background-color: #dcfce7; padding: 1px 4px; border-radius: 3px;' : '' }}">
                                                {{ $mName }}
                                            </span>
                                        @endforeach
                                    </div>

                                    <!-- Baris 4: harga: Rp. [harga] x [total_volume] [satuan]            Rp. [total_amount] -->
                                    <div class="mt-2 pt-1 border-top d-flex justify-content-between align-items-center flex-wrap" style="gap: 8px;">
                                        <span class="small text-muted font-tabular">
                                            harga: <strong>Rp. {{ number_format((float)$group->price, 0, ',', '.') }}</strong> x {{ (float)$group->total_volume }}{{ $group->sample_unit ? ' ' . strtolower($group->sample_unit) : '' }}
                                        </span>
                                        <span class="font-weight-bold text-success font-tabular" style="font-size: 0.95rem;">
                                            Rp. {{ number_format((float)$group->total_amount, 0, ',', '.') }}
                                        </span>
                                    </div>
                                </td>

                                <!-- PADUAN RENCANA RKAS -->
                                <td class="align-top">
                                    <div class="mb-1">
                                        @if($group->is_fully_paired)
                                            <span class="badge badge-success font-weight-bold px-2 py-1">
                                                <i class="fas fa-check-double mr-1"></i> {{ $group->paired_items_count }}/{{ $group->total_items }} Bulan Terpadu Penuh
                                            </span>
                                        @elseif($group->is_partially_paired)
                                            <span class="badge badge-warning text-dark font-weight-bold px-2 py-1">
                                                <i class="fas fa-clock mr-1"></i> {{ $group->paired_items_count }}/{{ $group->total_items }} Bulan Terpadu Sebagian
                                            </span>
                                        @else
                                            <span class="badge badge-secondary px-2 py-1">
                                                <i class="fas fa-exclamation-circle mr-1"></i> Belum Dipadukan (0/{{ $group->total_items }} Bulan)
                                            </span>
                                        @endif
                                    </div>

                                    @forelse($group->paired_pengajuan_groups as $pg)
                                        <div class="paired-card shadow-sm p-2 mb-2" style="border-left: 4px solid #10b981; border-radius: 8px; background-color: #f8fafc;">
                                            <div class="d-flex align-items-start justify-content-between">
                                                <div class="font-weight-bold text-success" style="font-size: 0.92rem;">
                                                    <i class="fas fa-boxes-stacked mr-1"></i> {{ $pg->nama_barang }}
                                                </div>
                                                <span class="badge {{ $pg->total_months > 1 ? 'badge-primary' : 'badge-secondary' }} font-tabular font-weight-bold">
                                                    <i class="fas fa-calendar-check mr-1"></i> {{ $pg->total_months }} Bulan Terpadu
                                                </span>
                                            </div>

                                            @if($pg->spesifikasi && $pg->spesifikasi !== '-')
                                                <div class="small text-muted mt-1" style="font-size: 0.8rem; line-height: 1.3;">
                                                    <span class="font-weight-bold text-secondary">Spesifikasi:</span> {{ $pg->spesifikasi }}
                                                </div>
                                            @endif

                                            <div class="small text-secondary mt-1 d-flex justify-content-between align-items-center flex-wrap" style="font-size: 0.8rem;">
                                                <div>
                                                    <i class="fas fa-user-tag mr-1 text-info"></i> {{ $pg->pemohon_nama }}
                                                    @if($pg->kegiatan)
                                                        &bull; <span class="text-muted">{{ $pg->kegiatan }}</span>
                                                    @endif
                                                </div>
                                                <div class="font-tabular font-weight-bold text-dark">
                                                    @if($pg->total_months > 1)
                                                        @ Rp {{ number_format((float)$pg->harga, 0, ',', '.') }} (Total: Rp {{ number_format((float)$pg->total_anggaran, 0, ',', '.') }})
                                                    @else
                                                        Vol: {{ (float)$pg->total_volume }} &bull; Rp {{ number_format((float)$pg->total_anggaran, 0, ',', '.') }}
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- Bulan-bulan Terpadu Pills -->
                                            <div class="mt-2 d-flex flex-wrap align-items-center" style="gap: 4px;">
                                                <span class="text-muted" style="font-size: 0.72rem;">Bulan:</span>
                                                @foreach($pg->months as $pm)
                                                    <span class="badge badge-success font-tabular" style="font-size: 0.72rem; padding: 2px 6px; font-weight: 500;">
                                                        {{ substr($pm, 0, 3) }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @empty
                                        <div class="small text-muted font-italic mt-1">
                                            Klik tombol "Padukan Sekaligus" untuk memadukan seluruh alokasi bulan secara instan.
                                        </div>
                                    @endforelse

                                    <!-- Collapsible Detail Tiap Bulan -->
                                    <div class="mt-1">
                                        <a href="javascript:void(0)" class="text-xs text-primary font-weight-bold" onclick="toggleGroupDetails('grp-detail-{{ $index }}')">
                                            <i class="fas fa-list-ul mr-1"></i> Rincian {{ count($group->monthly_items) }} Bulan Alokasi
                                        </a>
                                        <div id="grp-detail-{{ $index }}" class="d-none mt-2 p-2 bg-white rounded border">
                                            <table class="table table-sm table-bordered mb-0" style="font-size: 0.78rem;">
                                                <thead class="bg-light">
                                                    <tr>
                                                        <th>Bulan</th>
                                                        <th>Volume</th>
                                                        <th>Jumlah</th>
                                                        <th>Pengajuan Terhubung</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($group->monthly_items as $mRow)
                                                        <tr>
                                                            <td class="font-weight-bold">{{ $mRow->month }}</td>
                                                            <td class="font-tabular text-center">{{ (float)$mRow->volume }} {{ $mRow->unit }}</td>
                                                            <td class="font-tabular text-right">Rp {{ number_format((float)$mRow->total_amount, 0, ',', '.') }}</td>
                                                            <td>
                                                                @forelse($mRow->pengajuanBarangs as $mpb)
                                                                    <span class="text-success font-weight-bold">{{ $mpb->nama_barang }}</span>
                                                                @empty
                                                                    <span class="text-muted font-italic">- Belum -</span>
                                                                @endforelse
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </td>

                                <!-- AKSI PADUAN KELOMPOK -->
                                <td class="text-center align-middle">
                                    <div class="d-flex flex-column" style="gap: 6px;">
                                        <button type="button" class="btn btn-sm btn-primary font-weight-bold shadow-sm"
                                                data-id="{{ $group->first_id }}"
                                                data-year="{{ $selectedYear }}"
                                                data-account="{{ $group->account_code }}"
                                                data-kegiatan="{{ $group->kegiatan }}"
                                                data-desc="{{ $group->description }}"
                                                data-price="{{ (float)$group->price }}"
                                                data-price-formatted="Rp {{ number_format((float)$group->price, 0, ',', '.') }}"
                                                data-months-count="{{ $group->total_months }}"
                                                data-months-list="{{ implode(', ', $group->months_list) }}"
                                                onclick="handleOpenBatchPair(this)">
                                            <i class="fas fa-link mr-1"></i> {{ $group->is_fully_paired ? 'Ganti Paduan' : ($group->is_partially_paired ? 'Lengkapi Semua' : 'Padukan Sekaligus') }}
                                        </button>

                                        @if($group->paired_items_count > 0)
                                            <button type="button" class="btn btn-xs btn-outline-danger font-weight-bold"
                                                    data-id="{{ $group->first_id }}"
                                                    data-year="{{ $selectedYear }}"
                                                    data-account="{{ $group->account_code }}"
                                                    data-kegiatan="{{ $group->kegiatan }}"
                                                    data-desc="{{ $group->description }}"
                                                    data-price="{{ (float)$group->price }}"
                                                    data-paired-count="{{ $group->paired_items_count }}"
                                                    onclick="handleConfirmBatchUnpair(this)">
                                                <i class="fas fa-unlink mr-1"></i> Lepas Semua ({{ $group->paired_items_count }} Bln)
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="fas fa-clipboard-list mb-2" style="font-size: 2.5rem; opacity: 0.4;"></i>
                                    <h6 class="font-weight-bold mb-1">Tidak Ada Seri Belanja Kertas Kerja Ditemukan</h6>
                                    <p class="small mb-0">Coba ubah filter karakter belanja, tahun, atau kata kunci pencarian Anda.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($groupedItems->hasPages())
                <div class="card-footer bg-white border-top py-2 px-3 d-flex justify-content-between align-items-center flex-wrap">
                    <div class="small text-muted mb-1 mb-md-0">
                        Menampilkan {{ $groupedItems->firstItem() }} sampai {{ $groupedItems->lastItem() }} dari total {{ $groupedItems->total() }} seri kelompok
                    </div>
                    <div>
                        {{ $groupedItems->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            @endif

        @else
            <!-- ======================================================= -->
            <!-- TAMPILAN STANDAR (RINCI PER BULAN)                      -->
            <!-- ======================================================= -->
            <div class="card-header bg-light py-2 px-3 border-bottom d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <span class="font-weight-bold text-dark" style="font-size: 0.92rem;">
                        @if($viewMode === 'sekali')
                            <i class="fas fa-receipt text-primary mr-1"></i> Tampilan Belanja Sekali (Insidental)
                        @else
                            <i class="fas fa-calendar-days text-primary mr-1"></i> Tampilan Semua Data Rinci per Alokasi Bulan
                        @endif
                    </span>
                    <span class="badge badge-secondary ml-2">{{ $items->total() }} Baris</span>
                </div>
                <small class="text-muted">
                    @if($viewMode === 'sekali')
                        Menampilkan belanja yang hanya dialokasikan 1 kali dalam setahun (tidak berulang).
                    @else
                        Menampilkan seluruh baris alokasi kertas kerja per bulan.
                    @endif
                </small>
            </div>

            <div class="card-body p-0 table-responsive">
                <table class="table table-hover table-striped mb-0 table-mapping">
                    <thead class="thead-light">
                        <tr>
                            <th class="text-center" style="width: 45px;">No</th>
                            <th style="width: 42%;">
                                <div class="d-flex align-items-center justify-content-between">
                                    <span><i class="fas fa-file-invoice text-primary mr-1"></i> Kertas Kerja BOS (ARKAS Kemdikbud)</span>
                                    <small class="text-muted font-weight-normal">Nama barang SSH / Resmi</small>
                                </div>
                            </th>
                            <th style="width: 43%;">
                                <div class="d-flex align-items-center justify-content-between">
                                    <span><i class="fas fa-boxes-stacked text-success mr-1"></i> Barang Riil yang Diajukan Guru / Sekolah</span>
                                    <small class="text-muted font-weight-normal">Acuan Belanja Sebenarnya</small>
                                </div>
                            </th>
                            <th class="text-center" style="width: 15%;">Aksi Paduan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $index => $item)
                            @php
                                $isPaired = $item->pengajuanBarangs->count() > 0;
                            @endphp
                            <tr>
                                <td class="text-center text-muted font-tabular align-middle font-weight-bold">
                                    {{ $items->firstItem() + $index }}
                                </td>

                                <!-- SISI KIRI: KERTAS KERJA BOS -->
                                <td class="align-top">
                                    <div class="font-weight-bold text-dark" style="font-size: 0.95rem;">
                                        {{ $item->description }}
                                    </div>
                                    <div class="mt-1 d-flex align-items-center flex-wrap text-muted small" style="gap: 8px;">
                                        <span class="badge badge-light border font-tabular">
                                            <i class="fas fa-tag mr-1 text-secondary"></i>{{ $item->account_code }}
                                        </span>
                                        <span class="badge badge-info font-weight-bold font-tabular">
                                            <i class="fas fa-calendar-alt mr-1"></i>{{ $item->month }} {{ $item->year }}
                                        </span>
                                        @if($item->kegiatan)
                                            <span class="badge badge-light border text-truncate" style="max-width: 280px;" title="{{ $item->kegiatan }}">
                                                <i class="fas fa-tasks mr-1 text-primary"></i>{{ $item->kegiatan }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="mt-2 p-2 bg-light rounded d-flex justify-content-between align-items-center font-tabular small" style="border: 1px solid #e2e8f0;">
                                        <div>
                                            Alokasi: <strong>{{ (float) $item->volume }} {{ $item->unit }}</strong>
                                            &bull; Harga: <strong>Rp {{ number_format((float) $item->price, 0, ',', '.') }}</strong>
                                        </div>
                                        <div class="font-weight-bold text-primary" style="font-size: 0.9rem;">
                                            Rp {{ number_format((float) $item->total_amount, 0, ',', '.') }}
                                        </div>
                                    </div>
                                </td>

                                <!-- SISI KANAN: BARANG RIIL YANG DIAJUKAN -->
                                <td class="align-top">
                                    @if($isPaired)
                                        @foreach($item->pengajuanBarangs as $pb)
                                            <div class="paired-card shadow-sm">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="font-weight-bold text-success" style="font-size: 0.92rem;">
                                                        <i class="fas fa-check-circle mr-1"></i> {{ $pb->nama_barang }}
                                                    </div>
                                                    <span class="badge badge-success font-weight-bold">Terpadu</span>
                                                </div>

                                                @if($pb->spesifikasi && $pb->spesifikasi !== '-')
                                                    <div class="small text-muted mt-1 p-1 bg-white rounded" style="border: 1px dashed #cbd5e1; font-size: 0.8rem; line-height: 1.35;">
                                                        <strong>Spesifikasi:</strong> {{ $pb->spesifikasi }}
                                                    </div>
                                                @endif

                                                <div class="mt-1 d-flex justify-content-between align-items-center flex-wrap small text-secondary" style="font-size: 0.78rem;">
                                                    <div>
                                                        <i class="fas fa-user-tag mr-1 text-info"></i>
                                                        Pemohon: <strong>{{ $pb->guru ? $pb->guru->name : ($pb->pemohon ? $pb->pemohon->name : 'Bendahara/Sekolah') }}</strong>
                                                        @if($pb->kegiatan)
                                                            &bull; {{ $pb->kegiatan }}
                                                        @endif
                                                    </div>
                                                    <div class="font-tabular font-weight-bold text-dark">
                                                        {{ $pb->volume }} {{ $pb->satuan }} @ Rp {{ number_format((float)$pb->harga, 0, ',', '.') }}
                                                    </div>
                                                </div>

                                                @if($pb->pivot && $pb->pivot->notes)
                                                    <div class="mt-1 text-xs text-muted font-italic">
                                                        <i class="fas fa-sticky-note mr-1 text-warning"></i> Catatan: {{ $pb->pivot->notes }}
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="unpaired-box">
                                            <div class="font-weight-bold text-warning mb-1" style="font-size: 0.88rem;">
                                                <i class="fas fa-exclamation-triangle mr-1"></i> Belum Dipadukan dengan Rencana RKAS
                                            </div>
                                            <div class="small text-muted mb-2">
                                                Penanggung jawab belanja belum memiliki referensi nama barang riil dan spesifikasinya.
                                            </div>
                                        </div>
                                    @endif
                                </td>

                                <!-- KOLOM AKSI -->
                                <td class="text-center align-middle">
                                    <div class="d-flex flex-column" style="gap: 6px;">
                                        <button type="button" class="btn btn-sm btn-primary font-weight-bold shadow-sm btn-open-pair"
                                                data-id="{{ $item->id }}"
                                                data-desc="{{ $item->description }}"
                                                data-account="{{ $item->account_code }}"
                                                data-month="{{ $item->month }} {{ $item->year }}"
                                                data-vol="{{ (float)$item->volume }} {{ $item->unit }}"
                                                data-price="Rp {{ number_format((float)$item->price, 0, ',', '.') }}"
                                                data-total="Rp {{ number_format((float)$item->total_amount, 0, ',', '.') }}"
                                                onclick="handleOpenPair(this)">
                                            <i class="fas fa-link mr-1"></i> {{ $isPaired ? 'Ganti / Tambah' : 'Padukan' }}
                                        </button>

                                        @if($isPaired)
                                            @foreach($item->pengajuanBarangs as $pb)
                                                <button type="button" class="btn btn-xs btn-outline-danger btn-unpair"
                                                        data-kk-id="{{ $item->id }}"
                                                        data-pb-id="{{ $pb->id }}"
                                                        data-pb-name="{{ $pb->nama_barang }}"
                                                        onclick="handleConfirmUnpair(this)">
                                                    <i class="fas fa-unlink mr-1"></i> Lepas Paduan
                                                </button>
                                            @endforeach
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="fas fa-clipboard-list mb-2" style="font-size: 2.5rem; opacity: 0.4;"></i>
                                    <h6 class="font-weight-bold mb-1">Tidak Ada Data Kertas Kerja Ditemukan</h6>
                                    <p class="small mb-0">Coba ubah filter tahun, bulan, atau kata kunci pencarian Anda.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($items->hasPages())
                <div class="card-footer bg-white border-top py-2 px-3 d-flex justify-content-between align-items-center flex-wrap">
                    <div class="small text-muted mb-1 mb-md-0">
                        Menampilkan {{ $items->firstItem() }} sampai {{ $items->lastItem() }} dari total {{ $items->total() }} baris
                    </div>
                    <div>
                        {{ $items->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>

<!-- ======================================================= -->
<!-- MODAL 1: PEMADUAN TUNGGAL / SINGLE (POP-UP)              -->
<!-- ======================================================= -->
<div class="modal fade" id="modalPairPengajuan" tabindex="-1" role="dialog" aria-labelledby="modalPairPengajuanLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <form id="formPairSubmit" method="POST" action="{{ route('bos.pemaduan.pair') }}">
                @csrf
                <input type="hidden" name="bos_kertas_kerja_item_id" id="target_kertas_kerja_item_id">

                <div class="modal-header bg-dark text-white py-2 px-3">
                    <h5 class="modal-title font-weight-bold" id="modalPairPengajuanLabel" style="font-size: 0.95rem;">
                        <i class="fas fa-link text-primary mr-1"></i> Padukan Kertas Kerja dengan Pengajuan Riil
                    </h5>
                    <button type="button" class="btn-close text-white close" data-dismiss="modal" data-bs-dismiss="modal" onclick="window.hideModal('#modalPairPengajuan')" aria-label="Close"></button>
                </div>

                <div class="modal-body p-3">
                    <!-- TARGET KERTAS KERJA SUMMARY BOX -->
                    <div class="card mb-3 border-0 bg-light" style="border-radius: 8px; border-left: 4px solid #007bff !important;">
                        <div class="card-body py-2 px-3">
                            <div class="small font-weight-bold text-primary text-uppercase mb-1">
                                Target Kertas Kerja BOS (ARKAS):
                            </div>
                            <div class="font-weight-bold text-dark" id="target_kk_desc" style="font-size: 1rem;">-</div>
                            <div class="small text-muted mt-1 font-tabular">
                                Rekening: <span class="font-weight-bold text-dark" id="target_kk_account">-</span> &bull;
                                Bulan: <span class="badge badge-info" id="target_kk_month">-</span> &bull;
                                Volume: <span class="font-weight-bold text-dark" id="target_kk_vol">-</span> &bull;
                                Satuan: <span id="target_kk_price">-</span> &bull;
                                Total: <span class="font-weight-bold text-success" id="target_kk_total">-</span>
                            </div>
                        </div>
                    </div>

                    <!-- SEARCH INPUT PADA MODAL -->
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold text-muted mb-1">Pilih Barang Riil dari Pengajuan Guru / RKAS:</label>
                        <div class="input-group input-group-sm">
                            <input type="text" id="inputSearchPengajuan" class="form-control" placeholder="Ketik kata kunci untuk mencari barang riil...">
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                        </div>
                    </div>

                    <!-- LIST BARANG PENGAJUAN -->
                    <div id="pengajuanListContainer" style="max-height: 280px; overflow-y: auto; padding-right: 4px;">
                        <div class="text-center py-4 text-muted">
                            <div class="spinner-border spinner-border-sm text-primary mr-1"></div> Memuat daftar pengajuan...
                        </div>
                    </div>

                    <!-- CATATAN TAMBAHAN (OPSIONAL) -->
                    <div class="form-group mt-3 mb-0">
                        <label class="small font-weight-bold text-muted mb-1">Catatan Tambahan (Opsional):</label>
                        <input type="text" name="notes" id="pair_notes" class="form-control form-control-sm" placeholder="Contoh: Barang dibeli di toko langganan, spek disesuaikan...">
                    </div>
                </div>

                <div class="modal-footer py-2 px-3 bg-light d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" data-bs-dismiss="modal" onclick="window.hideModal('#modalPairPengajuan')">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm font-weight-bold px-3" id="btnSubmitPair" disabled>
                        <i class="fas fa-save mr-1"></i> Simpan Paduan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ======================================================= -->
<!-- MODAL 2: PEMADUAN SEKALIGUS (BATCH PAIRING MODAL)        -->
<!-- ======================================================= -->
<div class="modal fade" id="modalBatchPair" tabindex="-1" role="dialog" aria-labelledby="modalBatchPairLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <form id="formBatchPairSubmit" method="POST" action="{{ route('bos.pemaduan.batch-pair') }}">
                @csrf
                <input type="hidden" name="bos_kertas_kerja_item_id" id="batch_kk_id">
                <input type="hidden" name="year" id="batch_kk_year">
                <input type="hidden" name="account_code" id="batch_kk_account">
                <input type="hidden" name="kegiatan" id="batch_kk_kegiatan">
                <input type="hidden" name="description" id="batch_kk_desc_input">
                <input type="hidden" name="price" id="batch_kk_price_input">
                <input type="hidden" name="pengajuan_group_key" id="batch_selected_pengajuan_key">

                <div class="modal-header bg-primary text-white py-2 px-3">
                    <h5 class="modal-title font-weight-bold" id="modalBatchPairLabel" style="font-size: 0.95rem;">
                        <i class="fas fa-repeat mr-1"></i> Padukan Sekaligus Belanja Berulang (Multi-Bulan)
                    </h5>
                    <button type="button" class="btn-close text-white close" data-dismiss="modal" data-bs-dismiss="modal" onclick="window.hideModal('#modalBatchPair')" aria-label="Close"></button>
                </div>

                <div class="modal-body p-3">
                    <!-- SUMMARY SERI KERTAS KERJA -->
                    <div class="card mb-3 border-0 bg-light" style="border-radius: 8px; border-left: 4px solid #2563eb !important;">
                        <div class="card-body py-2 px-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="small font-weight-bold text-primary text-uppercase">
                                    Seri Kertas Kerja BOS (ARKAS):
                                </div>
                                <span class="badge badge-primary font-tabular font-weight-bold" id="batch_display_months_count">
                                    0 Bulan
                                </span>
                            </div>
                            <div class="font-weight-bold text-dark mt-1" id="batch_display_desc" style="font-size: 1.05rem;">-</div>
                            <div class="small text-muted mt-1 font-tabular">
                                Rekening: <span class="font-weight-bold text-dark" id="batch_display_account">-</span> &bull;
                                Harga: <span class="font-weight-bold text-dark" id="batch_display_price">-</span> &bull;
                                Kegiatan: <span class="text-secondary" id="batch_display_kegiatan">-</span>
                            </div>
                            <div class="mt-2 small font-weight-bold text-secondary">
                                Bulan Alokasi: <span id="batch_display_months_list" class="text-primary font-weight-normal font-tabular"></span>
                            </div>
                        </div>
                    </div>

                    <!-- SEARCH INPUT PENGAJUAN BERULANG -->
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold text-muted mb-1">
                            Pilih Seri Rencana RKAS (Pengajuan Barang) yang Bersesuaian:
                        </label>
                        <div class="input-group input-group-sm">
                            <input type="text" id="inputSearchBatchPengajuan" class="form-control" placeholder="Cari nama barang riil berulang (contoh: Listrik PLN, BBM, Internet)...">
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                        </div>
                    </div>

                    <!-- LIST SERI PENGAJUAN -->
                    <div id="batchPengajuanListContainer" style="max-height: 280px; overflow-y: auto; padding-right: 4px;">
                        <div class="text-center py-4 text-muted">
                            <div class="spinner-border spinner-border-sm text-primary mr-1"></div> Memuat daftar pengajuan berulang...
                        </div>
                    </div>

                    <!-- CATATAN PADUAN -->
                    <div class="form-group mt-3 mb-0">
                        <label class="small font-weight-bold text-muted mb-1">Catatan Paduan Sekaligus (Opsional):</label>
                        <input type="text" name="notes" id="batch_notes" class="form-control form-control-sm" placeholder="Catatan untuk seluruh bulan (contoh: Belanja rutin bulanan disetujui)...">
                    </div>
                </div>

                <div class="modal-footer py-2 px-3 bg-light d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" data-bs-dismiss="modal" onclick="window.hideModal('#modalBatchPair')">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm font-weight-bold px-3" id="btnSubmitBatchPair" disabled>
                        <i class="fas fa-check-double mr-1"></i> Padukan Seluruh Bulan Sekaligus
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- HIDDEN FORMS UNTUK UNPAIR -->
<form id="formUnpair" method="POST" action="{{ route('bos.pemaduan.unpair') }}" style="display: none;">
    @csrf
    <input type="hidden" name="bos_kertas_kerja_item_id" id="unpair_kk_id">
    <input type="hidden" name="pengajuan_barang_id" id="unpair_pb_id">
</form>

<form id="formBatchUnpair" method="POST" action="{{ route('bos.pemaduan.batch-unpair') }}" style="display: none;">
    @csrf
    <input type="hidden" name="bos_kertas_kerja_item_id" id="batch_unpair_id">
    <input type="hidden" name="year" id="batch_unpair_year">
    <input type="hidden" name="account_code" id="batch_unpair_account">
    <input type="hidden" name="kegiatan" id="batch_unpair_kegiatan">
    <input type="hidden" name="description" id="batch_unpair_desc">
    <input type="hidden" name="price" id="batch_unpair_price">
</form>
@stop

@section('js')
<script>
    // Universal modal helpers
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

    let availablePengajuans = [];
    let availableBatchPengajuans = [];
    const currentYear = '{{ $selectedYear }}';

    // Toggle accordion detail bulan
    function toggleGroupDetails(elementId) {
        const el = document.getElementById(elementId);
        if (el) {
            el.classList.toggle('d-none');
        }
    }

    // ==========================================
    // SINGLE PAIR LOGIC
    // ==========================================
    function handleOpenPair(btn) {
        const id = btn.dataset.id;
        const desc = btn.dataset.desc;
        const account = btn.dataset.account;
        const month = btn.dataset.month;
        const vol = btn.dataset.vol;
        const price = btn.dataset.price;
        const total = btn.dataset.total;

        document.getElementById('target_kertas_kerja_item_id').value = id;
        document.getElementById('target_kk_desc').textContent = desc;
        document.getElementById('target_kk_account').textContent = account;
        document.getElementById('target_kk_month').textContent = month;
        document.getElementById('target_kk_vol').textContent = vol;
        document.getElementById('target_kk_price').textContent = price;
        document.getElementById('target_kk_total').textContent = total;

        document.getElementById('btnSubmitPair').disabled = true;
        document.getElementById('inputSearchPengajuan').value = '';

        window.showModal('#modalPairPengajuan');
        fetchPengajuan(id);
    }

    function fetchPengajuan(kkItemId, keyword = '') {
        const container = document.getElementById('pengajuanListContainer');
        container.innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary mr-1"></div> Memuat daftar pengajuan...</div>';

        const url = new URL('{{ route('bos.pemaduan.available-pengajuan') }}', window.location.origin);
        url.searchParams.append('year', currentYear);
        if (kkItemId) url.searchParams.append('kertas_kerja_item_id', kkItemId);
        if (keyword) url.searchParams.append('search', keyword);

        fetch(url)
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    availablePengajuans = res.data;
                    renderPengajuanList(availablePengajuans);
                } else {
                    container.innerHTML = '<div class="alert alert-danger small py-2">Gagal mengambil data pengajuan.</div>';
                }
            })
            .catch(err => {
                console.error(err);
                container.innerHTML = '<div class="alert alert-danger small py-2">Terjadi kesalahan koneksi.</div>';
            });
    }

    function renderPengajuanList(list) {
        const container = document.getElementById('pengajuanListContainer');
        if (!list || list.length === 0) {
            container.innerHTML = `
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-inbox mb-1" style="font-size: 1.5rem; opacity: 0.5;"></i>
                    <div class="small">Tidak ada data pengajuan barang yang sesuai.</div>
                </div>
            `;
            document.getElementById('btnSubmitPair').disabled = true;
            return;
        }

        let html = '';
        list.forEach(p => {
            const isChecked = p.is_paired_with_this ? 'checked' : '';
            const cardClass = p.is_paired_with_this ? 'pengajuan-option-card selected' : 'pengajuan-option-card';
            const priceFormatted = new Intl.NumberFormat('id-ID').format(p.harga);
            const totalFormatted = new Intl.NumberFormat('id-ID').format(p.total);

            html += `
                <div class="${cardClass}" onclick="selectPengajuanCard(this, ${p.id})">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="custom-control custom-radio form-check">
                            <input type="radio" id="radio_pb_${p.id}" name="pengajuan_barang_id" value="${p.id}" class="custom-control-input form-check-input" ${isChecked} onchange="onRadioChanged()">
                            <label class="custom-control-label form-check-label font-weight-bold text-dark" for="radio_pb_${p.id}" style="cursor: pointer; font-size: 0.9rem;">
                                ${escapeHtml(p.nama_barang)}
                            </label>
                        </div>
                        ${p.is_paired_with_this ? '<span class="badge badge-success font-weight-bold">Sedang Terhubung</span>' : ''}
                    </div>

                    ${p.spesifikasi && p.spesifikasi !== '-' ? `
                        <div class="small text-muted mt-1 pl-4" style="line-height: 1.35;">
                            <strong>Spesifikasi:</strong> ${escapeHtml(p.spesifikasi)}
                        </div>
                    ` : ''}

                    <div class="d-flex justify-content-between align-items-center flex-wrap small text-secondary mt-1 pl-4" style="font-size: 0.78rem;">
                        <div>
                            <i class="fas fa-user-tag mr-1 text-info"></i> Pemohon: <strong>${escapeHtml(p.pemohon_nama)}</strong>
                            &bull; Kegiatan: ${escapeHtml(p.kegiatan || '-')}
                        </div>
                        <div class="font-tabular font-weight-bold text-dark">
                            ${p.volume} ${escapeHtml(p.satuan || 'Unit')} @ Rp ${priceFormatted} (Total: Rp ${totalFormatted})
                        </div>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
        if (document.querySelector('input[name="pengajuan_barang_id"]:checked')) {
            document.getElementById('btnSubmitPair').disabled = false;
        }
    }

    function selectPengajuanCard(element, id) {
        const radio = document.getElementById('radio_pb_' + id);
        if (radio) {
            radio.checked = true;
            document.querySelectorAll('#pengajuanListContainer .pengajuan-option-card').forEach(el => el.classList.remove('selected'));
            element.classList.add('selected');
            document.getElementById('btnSubmitPair').disabled = false;
        }
    }

    function onRadioChanged() {
        document.getElementById('btnSubmitPair').disabled = false;
    }

    function handleConfirmUnpair(btn) {
        const kkId = btn.dataset.kkId;
        const pbId = btn.dataset.pbId;
        const pbName = btn.dataset.pbName;

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Lepas Paduan Data?',
                text: `Apakah Anda yakin ingin melepaskan paduan barang '${pbName}' dari item kertas kerja ini?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Lepaskan',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    doUnpair(kkId, pbId);
                }
            });
        } else {
            if (confirm(`Lepaskan paduan barang '${pbName}'?`)) {
                doUnpair(kkId, pbId);
            }
        }
    }

    function doUnpair(kkId, pbId) {
        document.getElementById('unpair_kk_id').value = kkId;
        document.getElementById('unpair_pb_id').value = pbId;
        document.getElementById('formUnpair').submit();
    }

    // ==========================================
    // BATCH PAIR LOGIC (SEKALIGUS UNTUK GROUP)
    // ==========================================
    function handleOpenBatchPair(btn) {
        const id = btn.dataset.id || '';
        const year = btn.dataset.year;
        const account = btn.dataset.account;
        const kegiatan = btn.dataset.kegiatan;
        const desc = btn.dataset.desc;
        const price = btn.dataset.price;
        const priceFormatted = btn.dataset.priceFormatted;
        const monthsCount = btn.dataset.monthsCount;
        const monthsList = btn.dataset.monthsList;

        document.getElementById('batch_kk_id').value = id;
        document.getElementById('batch_kk_year').value = year;
        document.getElementById('batch_kk_account').value = account;
        document.getElementById('batch_kk_kegiatan').value = kegiatan;
        document.getElementById('batch_kk_desc_input').value = desc;
        document.getElementById('batch_kk_price_input').value = price;
        document.getElementById('batch_selected_pengajuan_key').value = '';

        document.getElementById('batch_display_desc').textContent = desc;
        document.getElementById('batch_display_account').textContent = account;
        document.getElementById('batch_display_kegiatan').textContent = kegiatan;
        document.getElementById('batch_display_price').textContent = priceFormatted;
        document.getElementById('batch_display_months_count').textContent = monthsCount + ' Bulan Alokasi';
        document.getElementById('batch_display_months_list').textContent = monthsList;

        document.getElementById('btnSubmitBatchPair').disabled = true;
        document.getElementById('inputSearchBatchPengajuan').value = '';

        window.showModal('#modalBatchPair');
        fetchBatchPengajuan();
    }

    function fetchBatchPengajuan(keyword = '') {
        const container = document.getElementById('batchPengajuanListContainer');
        container.innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary mr-1"></div> Memuat daftar pengajuan berulang...</div>';

        const url = new URL('{{ route('bos.pemaduan.available-recurring-pengajuan') }}', window.location.origin);
        url.searchParams.append('year', currentYear);
        if (keyword) url.searchParams.append('search', keyword);

        fetch(url)
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    availableBatchPengajuans = res.data;
                    renderBatchPengajuanList(availableBatchPengajuans);
                } else {
                    container.innerHTML = '<div class="alert alert-danger small py-2">Gagal mengambil data pengajuan berulang.</div>';
                }
            })
            .catch(err => {
                console.error(err);
                container.innerHTML = '<div class="alert alert-danger small py-2">Terjadi kesalahan koneksi.</div>';
            });
    }

    function renderBatchPengajuanList(list) {
        const container = document.getElementById('batchPengajuanListContainer');
        if (!list || list.length === 0) {
            container.innerHTML = `
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-inbox mb-1" style="font-size: 1.5rem; opacity: 0.5;"></i>
                    <div class="small">Tidak ada data Rencana RKAS yang sesuai.</div>
                </div>
            `;
            document.getElementById('btnSubmitBatchPair').disabled = true;
            return;
        }

        let html = '';
        list.forEach((p, idx) => {
            const priceFormatted = new Intl.NumberFormat('id-ID').format(p.harga);
            const totalFormatted = new Intl.NumberFormat('id-ID').format(p.total_anggaran);
            const monthsString = p.months.join(', ');

            html += `
                <div class="pengajuan-option-card" onclick="selectBatchPengajuanCard(this, '${p.group_key}')">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="custom-control custom-radio form-check">
                            <input type="radio" id="radio_batch_${idx}" name="batch_radio_select" value="${p.group_key}" class="custom-control-input form-check-input" onchange="onBatchRadioChanged('${p.group_key}')">
                            <label class="custom-control-label form-check-label font-weight-bold text-dark" for="radio_batch_${idx}" style="cursor: pointer; font-size: 0.92rem;">
                                ${escapeHtml(p.nama_barang)}
                            </label>
                        </div>
                        <span class="badge ${p.total_months > 1 ? 'badge-primary' : 'badge-secondary'} font-weight-bold font-tabular">
                            <i class="fas fa-calendar-alt mr-1"></i> ${p.total_months} Bulan
                        </span>
                    </div>

                    ${p.spesifikasi && p.spesifikasi !== '-' ? `
                        <div class="small text-muted mt-1 pl-4" style="line-height: 1.35;">
                            <strong>Spesifikasi:</strong> ${escapeHtml(p.spesifikasi)}
                        </div>
                    ` : ''}

                    <div class="d-flex justify-content-between align-items-center flex-wrap small text-secondary mt-1 pl-4" style="font-size: 0.78rem;">
                        <div>
                            <i class="fas fa-user-tag mr-1 text-info"></i> Pemohon: <strong>${escapeHtml(p.pemohon_nama)}</strong>
                            &bull; Kegiatan: ${escapeHtml(p.kegiatan || '-')}
                        </div>
                        <div class="font-tabular font-weight-bold text-success">
                            Rp ${priceFormatted} / bln (Total: Rp ${totalFormatted})
                        </div>
                    </div>

                    <div class="mt-1 pl-4 text-xs text-muted font-tabular">
                        <i class="fas fa-check-circle mr-1 text-primary"></i> Alokasi: ${escapeHtml(monthsString)}
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    function selectBatchPengajuanCard(element, key) {
        document.getElementById('batch_selected_pengajuan_key').value = key;
        document.querySelectorAll('#batchPengajuanListContainer .pengajuan-option-card').forEach(el => el.classList.remove('selected'));
        element.classList.add('selected');
        const radio = element.querySelector('input[type="radio"]');
        if (radio) radio.checked = true;
        document.getElementById('btnSubmitBatchPair').disabled = false;
    }

    function onBatchRadioChanged(key) {
        document.getElementById('batch_selected_pengajuan_key').value = key;
        document.getElementById('btnSubmitBatchPair').disabled = false;
    }

    function handleConfirmBatchUnpair(btn) {
        const id = btn.dataset.id || '';
        const year = btn.dataset.year;
        const account = btn.dataset.account;
        const kegiatan = btn.dataset.kegiatan;
        const desc = btn.dataset.desc;
        const price = btn.dataset.price;
        const count = btn.dataset.pairedCount || '';

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Lepas Paduan Seluruh Bulan?',
                html: `Apakah Anda yakin ingin melepaskan seluruh paduan data (<strong>${count} bulan</strong>) untuk item kertas kerja: <br><br><strong>${desc}</strong>?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-unlink mr-1"></i> Ya, Lepas Semua',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    doBatchUnpair(id, year, account, kegiatan, desc, price);
                }
            });
        } else {
            if (confirm(`Lepaskan paduan seluruh ${count} bulan untuk '${desc}'?`)) {
                doBatchUnpair(id, year, account, kegiatan, desc, price);
            }
        }
    }

    function doBatchUnpair(id, year, account, kegiatan, desc, price) {
        document.getElementById('batch_unpair_id').value = id || '';
        document.getElementById('batch_unpair_year').value = year;
        document.getElementById('batch_unpair_account').value = account;
        document.getElementById('batch_unpair_kegiatan').value = kegiatan;
        document.getElementById('batch_unpair_desc').value = desc;
        document.getElementById('batch_unpair_price').value = price;
        document.getElementById('formBatchUnpair').submit();
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Universal dismiss button click handler
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('[data-dismiss="modal"], [data-bs-dismiss="modal"]');
            if (btn) {
                const modal = btn.closest('.modal');
                if (modal) {
                    window.hideModal(modal);
                }
            }
        });

        // Search in single modal
        let searchTimeout = null;
        const searchInput = document.getElementById('inputSearchPengajuan');
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                clearTimeout(searchTimeout);
                const val = e.target.value;
                const kkId = document.getElementById('target_kertas_kerja_item_id').value;
                searchTimeout = setTimeout(() => {
                    fetchPengajuan(kkId, val);
                }, 300);
            });
        }

        // Search in batch modal
        let batchSearchTimeout = null;
        const batchSearchInput = document.getElementById('inputSearchBatchPengajuan');
        if (batchSearchInput) {
            batchSearchInput.addEventListener('input', function(e) {
                clearTimeout(batchSearchTimeout);
                const val = e.target.value;
                batchSearchTimeout = setTimeout(() => {
                    fetchBatchPengajuan(val);
                }, 300);
            });
        }
    });
</script>
@stop
