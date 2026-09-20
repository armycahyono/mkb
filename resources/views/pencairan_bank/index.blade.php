@extends('adminlte::page')

@php
    $selectedYear = $selectedYear ?? $activeFiscalYear ?? session('fiscal_year', date('Y'));
@endphp

@section('title', 'TNT ' . $fundingSource . ' (Transaksi Non Tunai)')

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap" style="padding: 2px 0;">
    <div>
        <h1 class="page-title"><i class="fas fa-file-invoice-dollar text-success mr-1"></i> TNT {{ $fundingSource }} <small class="text-muted font-weight-normal" style="font-size: 0.82rem;">(Transaksi Non Tunai)</small></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-house"></i> Dashboard</a></li>
                <li class="breadcrumb-item"><i class="fas fa-calculator"></i> Penganggaran / {{ $fundingSource }}</li>
                <li class="breadcrumb-item active">TNT {{ $fundingSource }}</li>
            </ol>
        </nav>
    </div>
    <div class="header-actions">
        <a href="{{ route($routePrefix . '.perjadin.index') }}" class="btn btn-sm btn-outline-primary mr-1">
            <i class="fas fa-car-side mr-1"></i> Perjadin {{ $fundingSource }}
        </a>
        <a href="{{ route($routePrefix . '.pencairan-bank.create') }}" class="btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus mr-1"></i> Buat Surat TNT Baru
        </a>
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
    .font-tabular {
        font-variant-numeric: tabular-nums;
        font-feature-settings: "tnum";
    }

    #bankTable {
        font-size: 0.8125rem !important;
        letter-spacing: -0.008em;
    }
    #bankTable th {
        font-size: 0.74rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 0.4rem 0.5rem !important;
        vertical-align: middle;
        background-color: #f8fafc;
        color: #475569;
    }
    #bankTable td {
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


    /* Nested Child Row Styling */
    .table-nested-bank th {
        font-size: 0.72rem !important;
        background-color: #f1f5f9 !important;
        color: #334155 !important;
        font-weight: 700 !important;
        padding: 0.35rem 0.45rem !important;
    }
    .table-nested-bank td {
        font-size: 0.78rem !important;
        padding: 0.3rem 0.45rem !important;
        vertical-align: middle;
    }
    .nested-bank-detail {
        background-color: #ffffff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    tr.shown > td {
        background-color: #f0fdfa !important;
    }
</style>
@stop

@section('content')
@php
    $availableYears = $availableYears ?? collect([(int) date('Y')]);
    $activeFiscalYear = $activeFiscalYear ?? (string) session('fiscal_year', date('Y'));
    $selectedYear = $selectedYear ?? $activeFiscalYear;
    $unpaidItemsCount = $unpaidItemsCount ?? 0;
    $unpaidTotalAmount = $unpaidTotalAmount ?? 0;
    $unpaidPerjadinsCount = $unpaidPerjadinsCount ?? 0;
    $unpaidItems = $unpaidItems ?? collect([]);

    $unpaidRencanaCount = $unpaidRencanaCount ?? 0;
    $unpaidRencanaTotalAmount = $unpaidRencanaTotalAmount ?? 0;
    $unpaidRencanaNotas = $unpaidRencanaNotas ?? collect([]);

    $unpaidUtilityCount = $unpaidUtilityCount ?? 0;
    $unpaidUtilityTotalAmount = $unpaidUtilityTotalAmount ?? 0;
    $unpaidUtilityReceipts = $unpaidUtilityReceipts ?? collect([]);

    $totalPendingCount = $totalPendingCount ?? ($unpaidItemsCount + $unpaidRencanaCount + $unpaidUtilityCount);
    $totalPendingAmount = $totalPendingAmount ?? ($unpaidTotalAmount + $unpaidRencanaTotalAmount + $unpaidUtilityTotalAmount);
@endphp

@if($totalPendingCount > 0)
    <div class="card border-0 shadow-sm mb-3" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border-left: 5px solid #f59e0b !important;">
        <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap: 12px;">
                <div class="d-flex align-items-center" style="gap: 14px;">
                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-warning text-white flex-shrink-0" style="width: 44px; height: 44px; box-shadow: 0 2px 6px rgba(245, 158, 11, 0.35);">
                        <i class="fas fa-bell fa-lg text-dark"></i>
                    </div>
                    <div>
                        <h5 class="font-weight-bold text-dark mb-1" style="font-size: 0.95rem;">
                            <i class="fas fa-exclamation-triangle text-warning mr-1"></i>
                            @if($unpaidItemsCount > 0 && $unpaidRencanaCount > 0 && $unpaidUtilityCount > 0)
                                Ada Perjalanan Dinas, Rencana Belanja, dan Utilitas yang Belum Masuk Daftar TNT
                            @elseif($unpaidItemsCount > 0 && $unpaidRencanaCount > 0)
                                Ada Perjalanan Dinas dan Rencana Belanja yang Belum Masuk Daftar TNT
                            @elseif($unpaidItemsCount > 0 && $unpaidUtilityCount > 0)
                                Ada Perjalanan Dinas dan Tagihan Utilitas yang Belum Masuk Daftar TNT
                            @elseif($unpaidRencanaCount > 0 && $unpaidUtilityCount > 0)
                                Ada Rencana Belanja dan Tagihan Utilitas yang Belum Masuk Daftar TNT
                            @elseif($unpaidItemsCount > 0)
                                Ada Perjalanan Dinas yang Belum Masuk Daftar TNT
                            @elseif($unpaidRencanaCount > 0)
                                Ada Rencana Belanja yang Belum Masuk Daftar TNT
                            @elseif($unpaidUtilityCount > 0)
                                Ada Tagihan Utilitas yang Belum Masuk Daftar TNT
                            @else
                                Ada Tagihan Tertunda yang Belum Masuk Daftar TNT
                            @endif
                        </h5>
                        <p class="text-muted mb-0" style="font-size: 0.8125rem; line-height: 1.45;">
                            Total tagihan tertunda <strong class="text-danger font-tabular font-weight-bold">Rp {{ number_format($totalPendingAmount, 0, ',', '.') }}</strong> yang belum dibuatkan TNT {{ $fundingSource }}:
                            <span class="d-inline-flex align-items-center flex-wrap mt-1" style="gap: 6px;">
                                @if($unpaidItemsCount > 0)
                                    <span class="badge badge-info" style="font-size: 0.76rem;"><i class="fas fa-car mr-1"></i>Perjadin: {{ $unpaidPerjadinsCount }} kegiatan ({{ $unpaidItemsCount }} guru) &bull; Rp {{ number_format($unpaidTotalAmount, 0, ',', '.') }}</span>
                                @endif
                                @if($unpaidRencanaCount > 0)
                                    <span class="badge badge-primary" style="font-size: 0.76rem;"><i class="fas fa-cart-shopping mr-1"></i>Rencana Belanja: {{ $unpaidRencanaCount }} paket/nota &bull; Rp {{ number_format($unpaidRencanaTotalAmount, 0, ',', '.') }}</span>
                                @endif
                                @if($unpaidUtilityCount > 0)
                                    <span class="badge badge-warning text-dark" style="font-size: 0.76rem;"><i class="fas fa-bolt mr-1"></i>Utilitas: {{ $unpaidUtilityCount }} tagihan &bull; Rp {{ number_format($unpaidUtilityTotalAmount, 0, ',', '.') }}</span>
                                @endif
                            </span>
                        </p>
                    </div>
                </div>
                <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                    <button type="button" class="btn btn-sm btn-outline-dark font-weight-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalUnpaidTagihan" data-toggle="modal" data-target="#modalUnpaidTagihan">
                        <i class="fas fa-list-ul mr-1"></i> Rincian Tagihan ({{ $totalPendingCount }})
                    </button>
                    <a href="{{ route($routePrefix . '.pencairan-bank.create') }}" class="btn btn-sm btn-primary font-weight-bold shadow-sm">
                        <i class="fas fa-plus mr-1"></i> Buat Surat TNT Sekarang
                    </a>
                </div>
            </div>
        </div>
    </div>
@endif

<div class="card shadow-sm border-0 mb-3">
    <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center flex-wrap" style="border-bottom: 1px solid #edf2f7; gap: 8px;">
        <h3 class="card-title font-weight-bold text-dark mb-0" style="font-size: 0.92rem;">
            <i class="fas fa-file-signature text-success mr-1"></i> Daftar Transaksi Non Tunai (TNT) {{ $fundingSource }}
        </h3>
        <div class="d-flex align-items-center flex-wrap ms-auto ml-auto" style="gap: 8px;">
            <input type="hidden" id="filterYearBank" value="{{ $selectedYear }}">
            <span class="badge bg-primary text-white font-weight-normal d-inline-flex align-items-center" style="height: 31px; font-size: 0.8125rem; padding: 0 10px; border-radius: 4px;" title="Tahun Anggaran aktif yang dipilih pada Navbar">
                <i class="fas fa-calendar-alt me-1 mr-1"></i> Tahun Anggaran {{ $selectedYear }}
            </span>

            <div class="btn-group btn-group-sm shadow-xs" role="group">
                <button type="button" class="btn btn-sm btn-outline-secondary font-weight-bold d-inline-flex align-items-center" id="btn-expand-all" style="height: 31px; font-size: 0.8125rem; padding: 0.25rem 0.65rem;" title="Buka seluruh rincian transaksi">
                    <i class="fas fa-plus mr-1"></i> Buka Semua
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary font-weight-bold d-inline-flex align-items-center" id="btn-collapse-all" style="height: 31px; font-size: 0.8125rem; padding: 0.25rem 0.65rem;" title="Tutup seluruh rincian transaksi">
                    <i class="fas fa-minus mr-1"></i> Tutup Semua
                </button>
            </div>
        </div>
    </div>
    <div class="card-body p-2">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show py-2 px-3 mb-2" role="alert" style="font-size: 0.8125rem;">
                <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered w-100" id="bankTable">
                <thead>
                    <tr>
                        <th width="3%" class="text-center"></th>
                        <th width="4%" class="text-center">No</th>
                        <th width="24%">Nomor & Tanggal Surat</th>
                        <th width="24%">Bank & Rekening Sumber</th>
                        <th width="12%" class="text-center">Jml Rekening</th>
                        <th width="15%" class="text-right">Total Transfer</th>
                        <th width="10%" class="text-center">Status</th>
                        <th width="8%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal Konfirmasi Pencairan --}}
<div class="modal fade" id="modalConfirmCair" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formConfirmCair" method="POST">
            @csrf
            <div class="modal-content shadow border-0">
                <div class="modal-header py-2 bg-success text-white">
                    <h6 class="modal-title font-weight-bold" style="font-size: 0.88rem;"><i class="fas fa-check-double mr-1"></i> Konfirmasi Pencairan TNT ke Bank</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    <p class="text-muted mb-3" style="font-size: 0.78rem;">
                        Setelah bank berhasil memproses transfer, tandai surat ini sebagai <strong>"Dicairkan"</strong>. Seluruh data kwitansi perjadin yang terhubung otomatis akan berstatus <strong>"Lunas Dibayar"</strong>.
                    </p>
                    <div class="form-group mb-3">
                        <label class="font-weight-bold" style="font-size: 0.78rem;">Tanggal Cair di Bank <span class="text-danger">*</span></label>
                        <input type="date" name="cair_date" id="cairDate" class="form-control form-control-sm font-tabular" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group mb-2">
                        <label class="font-weight-bold" style="font-size: 0.78rem;">Nomor BKU <small class="text-muted">(Opsional, untuk otomatis mengisi BKU Perjadin)</small></label>
                        <input type="text" name="bku_number" id="cairBku" class="form-control form-control-sm font-tabular" placeholder="Contoh: BPU149 / 2025">
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-success font-weight-bold px-3"><i class="fas fa-check mr-1"></i> Simpan & Lunaskan</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modal Rincian Tagihan Belum Masuk TNT (Perjadin & Rencana Belanja) --}}
@if($totalPendingCount > 0)
<div class="modal fade" id="modalUnpaidTagihan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content shadow border-0">
            <div class="modal-header py-2 bg-warning text-dark border-bottom">
                <h6 class="modal-title font-weight-bold" style="font-size: 0.88rem;">
                    <i class="fas fa-file-invoice-dollar mr-1"></i> Rincian Tagihan Belum Masuk TNT ({{ $fundingSource }}) <span class="badge bg-primary text-white ml-1 font-weight-normal" style="font-size: 0.74rem;">Tahun Anggaran {{ $selectedYear }}</span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                {{-- Alert Ringkasan --}}
                <div class="alert alert-light border py-2 px-3 mb-3 small d-flex justify-content-between align-items-center flex-wrap" style="gap: 8px;">
                    <div>
                        <span>Tagihan Tertunda: </span>
                        @if($unpaidItemsCount > 0)
                            <strong class="text-dark mr-2"><i class="fas fa-car text-info mr-1"></i>{{ $unpaidPerjadinsCount }} Perjadin ({{ $unpaidItemsCount }} Guru)</strong>
                        @endif
                        @if($unpaidRencanaCount > 0)
                            <strong class="text-dark"><i class="fas fa-cart-shopping text-primary mr-1"></i>{{ $unpaidRencanaCount }} Rencana Belanja</strong>
                        @endif
                    </div>
                    <span class="font-weight-bold font-tabular text-danger" style="font-size: 0.95rem;">
                        Total Tagihan: Rp {{ number_format($totalPendingAmount, 0, ',', '.') }}
                    </span>
                </div>

                {{-- Nav Tabs --}}
                <ul class="nav nav-pills mb-3" id="unpaidTabs" role="tablist" style="gap: 4px;">
                    @if($unpaidItemsCount > 0)
                    <li class="nav-item" role="presentation">
                        <a class="nav-link py-1 px-3 {{ $unpaidItemsCount > 0 ? 'active' : '' }}" id="tab-perjadin-btn" data-bs-toggle="pill" data-toggle="pill" href="#tab-unpaid-perjadin" role="tab" style="font-size: 0.8125rem; font-weight: 600;">
                            <i class="fas fa-car-side mr-1"></i> Perjalanan Dinas
                            <span class="badge badge-light font-tabular ml-1">{{ $unpaidItemsCount }}</span>
                            <small class="d-none d-sm-inline opacity-75 font-tabular ml-1">(Rp {{ number_format($unpaidTotalAmount, 0, ',', '.') }})</small>
                        </a>
                    </li>
                    @endif
                    @if($unpaidRencanaCount > 0)
                    <li class="nav-item" role="presentation">
                        <a class="nav-link py-1 px-3 {{ $unpaidItemsCount === 0 ? 'active' : '' }}" id="tab-rencana-btn" data-bs-toggle="pill" data-toggle="pill" href="#tab-unpaid-rencana" role="tab" style="font-size: 0.8125rem; font-weight: 600;">
                            <i class="fas fa-cart-shopping mr-1"></i> Rencana Belanja
                            <span class="badge badge-light font-tabular ml-1">{{ $unpaidRencanaCount }}</span>
                            <small class="d-none d-sm-inline opacity-75 font-tabular ml-1">(Rp {{ number_format($unpaidRencanaTotalAmount, 0, ',', '.') }})</small>
                        </a>
                    </li>
                    @endif
                    @if($unpaidUtilityCount > 0)
                    <li class="nav-item" role="presentation">
                        <a class="nav-link py-1 px-3 {{ ($unpaidItemsCount === 0 && $unpaidRencanaCount === 0) ? 'active' : '' }}" id="tab-utilitas-btn" data-bs-toggle="pill" data-toggle="pill" href="#tab-unpaid-utilitas" role="tab" style="font-size: 0.8125rem; font-weight: 600;">
                            <i class="fas fa-bolt mr-1"></i> Tagihan Utilitas
                            <span class="badge badge-light font-tabular ml-1">{{ $unpaidUtilityCount }}</span>
                            <small class="d-none d-sm-inline opacity-75 font-tabular ml-1">(Rp {{ number_format($unpaidUtilityTotalAmount, 0, ',', '.') }})</small>
                        </a>
                    </li>
                    @endif
                </ul>

                <div class="tab-content" id="unpaidTabsContent">
                    {{-- Tab 1: Perjalanan Dinas --}}
                    @if($unpaidItemsCount > 0)
                    <div class="tab-pane fade {{ $unpaidItemsCount > 0 ? 'show active' : '' }}" id="tab-unpaid-perjadin" role="tabpanel">
                        <div class="table-responsive" style="max-height: 380px;">
                            <table class="table table-sm table-hover table-bordered mb-0" style="font-size: 0.78rem;">
                                <thead class="bg-light text-dark">
                                    <tr>
                                        <th width="4%" class="text-center">No</th>
                                        <th width="14%">Tgl Dinas</th>
                                        <th width="30%">Tujuan & Maksud Dinas</th>
                                        <th width="28%">Nama Guru & Rekening</th>
                                        <th width="24%" class="text-right">Nominal Bersih</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($unpaidItems as $idx => $item)
                                        @php
                                            $bank = $item->bankAccount ?? ($item->guru ? $item->guru->primaryBankAccount : null);
                                            $bankName = $bank ? ($bank->bank ? $bank->bank->name : $bank->bank_name) : 'Bank Jatim';
                                            $accNum = $bank ? $bank->account_number : '-';
                                        @endphp
                                        <tr>
                                            <td class="text-center font-tabular">{{ $idx + 1 }}</td>
                                            <td class="font-tabular">{{ $item->perjadin && $item->perjadin->departure_date ? $item->perjadin->departure_date->format('d/m/Y') : '-' }}</td>
                                            <td>
                                                <div class="font-weight-500 text-dark">{{ $item->perjadin->destination_name ?? '-' }}</div>
                                                <small class="text-muted d-block text-truncate" style="max-width: 250px;">{{ $item->perjadin->activity_purpose ?? '-' }}</small>
                                            </td>
                                            <td>
                                                <div class="font-weight-500 text-dark">{{ $item->guru ? $item->guru->full_name_with_title : '-' }}</div>
                                                <small class="text-muted font-tabular">{{ $bankName }}: {{ $accNum }}</small>
                                            </td>
                                            <td class="text-right font-tabular font-weight-bold text-dark">
                                                Rp {{ number_format($item->net_amount, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    {{-- Tab 2: Rencana Belanja --}}
                    @if($unpaidRencanaCount > 0)
                    <div class="tab-pane fade {{ $unpaidItemsCount === 0 ? 'show active' : '' }}" id="tab-unpaid-rencana" role="tabpanel">
                        <div class="table-responsive" style="max-height: 380px;">
                            <table class="table table-sm table-hover table-bordered mb-0" style="font-size: 0.78rem;">
                                <thead class="bg-light text-dark">
                                    <tr>
                                        <th width="4%" class="text-center">No</th>
                                        <th width="14%" class="text-center">No. Rencana</th>
                                        <th width="12%">Tgl Rencana</th>
                                        <th width="28%">Judul Rencana Belanja</th>
                                        <th width="22%">Rekanan / Toko</th>
                                        <th width="8%" class="text-center">Item</th>
                                        <th width="12%" class="text-right">Estimasi Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($unpaidRencanaNotas as $idx => $nota)
                                        @php
                                            $supplierName = $nota->supplier ? $nota->supplier->name : ($nota->supplier_name ?: '-');
                                            $isOnline = $nota->supplier && $nota->supplier->type === 'online';
                                        @endphp
                                        <tr>
                                            <td class="text-center font-tabular">{{ $idx + 1 }}</td>
                                            <td class="text-center font-tabular font-weight-bold text-primary">{{ $nota->no_nota ?: '-' }}</td>
                                            <td class="font-tabular">{{ $nota->planned_date ? $nota->planned_date->format('d/m/Y') : '-' }}</td>
                                            <td>
                                                <div class="font-weight-500 text-dark">{{ $nota->title }}</div>
                                                @if($isOnline)
                                                    <small class="badge badge-warning text-dark"><i class="fas fa-store mr-1"></i>Online / Siplah</small>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="text-dark">{{ $supplierName }}</div>
                                                @if($nota->account_number)
                                                    <small class="text-muted font-tabular">{{ $nota->bank_name ?: 'Bank' }}: {{ $nota->account_number }}</small>
                                                @endif
                                            </td>
                                            <td class="text-center font-tabular">
                                                <span class="badge badge-light border">{{ $nota->items->count() }}</span>
                                            </td>
                                            <td class="text-right font-tabular font-weight-bold text-dark">
                                                Rp {{ number_format($nota->total_amount, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    {{-- Tab 3: Tagihan Utilitas --}}
                    @if($unpaidUtilityCount > 0)
                    <div class="tab-pane fade {{ ($unpaidItemsCount === 0 && $unpaidRencanaCount === 0) ? 'show active' : '' }}" id="tab-unpaid-utilitas" role="tabpanel">
                        <div class="table-responsive" style="max-height: 380px;">
                            <table class="table table-sm table-hover table-bordered mb-0" style="font-size: 0.78rem;">
                                <thead class="bg-light text-dark">
                                    <tr>
                                        <th width="4%" class="text-center">No</th>
                                        <th width="14%">Tgl Bayar</th>
                                        <th width="24%">Objek Tagihan</th>
                                        <th width="24%">Kertas Kerja (BOS)</th>
                                        <th width="18%">Periode</th>
                                        <th width="16%" class="text-right">Nominal Riil</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($unpaidUtilityReceipts as $idx => $receipt)
                                        <tr>
                                            <td class="text-center font-tabular align-middle">{{ $idx + 1 }}</td>
                                            <td class="align-middle font-tabular">
                                                {{ $receipt->transaction_date ? $receipt->transaction_date->format('d/m/Y') : '-' }}
                                            </td>
                                            <td class="align-middle">
                                                <span class="font-weight-bold text-dark">{{ $receipt->profile?->name ?? 'Utilitas' }}</span>
                                                @if(! ($receipt->profile?->is_budgeted ?? true))
                                                    <span class="badge badge-secondary ml-1">Non-Anggaran</span>
                                                @endif
                                            </td>
                                            <td class="align-middle">
                                                @if($receipt->kertasKerjaItem)
                                                    <span class="font-weight-500 text-dark">{{ $receipt->kertasKerjaItem->description }}</span>
                                                @elseif(! ($receipt->profile?->is_budgeted ?? true))
                                                    <span class="text-muted small"><em>Non-BOS (Internal)</em></span>
                                                @else
                                                    <span class="badge badge-warning text-dark"><i class="fas fa-exclamation-circle mr-1"></i>Belum Terhubung</span>
                                                @endif
                                            </td>
                                            <td class="align-middle">
                                                {{ $receipt->billing_month ?: '-' }} {{ $receipt->fiscal_year }}
                                            </td>
                                            <td class="text-right align-middle font-tabular font-weight-bold text-dark">
                                                Rp {{ number_format($receipt->total_amount, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            <div class="modal-footer py-2 d-flex justify-content-between flex-wrap" style="gap: 8px;">
                <div class="d-flex align-items-center" style="gap: 6px;">
                    @if($unpaidItemsCount > 0)
                        <a href="{{ route($routePrefix . '.perjadin.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-car-side mr-1"></i> Buka Perjadin
                        </a>
                    @endif
                    @if($unpaidRencanaCount > 0 && \Illuminate\Support\Facades\Route::has('bos.panduan.index'))
                        <a href="{{ route('bos.panduan.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-cart-shopping mr-1"></i> Buka Rencana Belanja
                        </a>
                    @endif
                    @if($unpaidUtilityCount > 0 && \Illuminate\Support\Facades\Route::has('bos.utilitas.index'))
                        <a href="{{ route('bos.utilitas.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-bolt mr-1"></i> Buka Struk Utilitas
                        </a>
                    @endif
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-secondary mr-1" data-bs-dismiss="modal" data-dismiss="modal">Tutup</button>
                    <a href="{{ route($routePrefix . '.pencairan-bank.create') }}" class="btn btn-sm btn-primary font-weight-bold px-3">
                        <i class="fas fa-plus mr-1"></i> Buat Surat TNT Sekarang
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@stop

@section('js')
<script>
$(document).ready(function() {
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatRupiah(num) {
        if (num === null || num === undefined || isNaN(num)) return '0';
        return Math.round(Number(num)).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    // Suppress intrusive browser alert modals from DataTables on transient aborts/session expirations
    if (window.jQuery && $.fn && $.fn.dataTable) {
        $.fn.dataTable.ext.errMode = 'none';
    }

    let table = $('#bankTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 25,
        order: [],
        ajax: {
            url: "{{ route($routePrefix . '.pencairan-bank.index', [], false) }}",
            data: function(d) {
                d.year = $('#filterYearBank').val();
            },
            error: function(xhr, error, code) {
                if (xhr.status === 401 || xhr.status === 419) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Sesi Berakhir',
                            text: 'Sesi login Anda telah berakhir. Halaman akan dimuat ulang.',
                            confirmButtonText: 'Muat Ulang'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        window.location.reload();
                    }
                } else {
                    console.warn('DataTables Ajax error [bankTable]:', error, code);
                }
            }
        },
        columns: [
            {data: 'btn_expand', name: 'btn_expand', orderable: false, searchable: false, className: 'text-center cursor-pointer'},
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center font-tabular'},
            {data: 'surat_display', name: 'letter_number'},
            {data: 'bank_display', name: 'bank_name'},
            {data: 'rekening_count_display', name: 'total_accounts', className: 'text-center'},
            {data: 'nominal_display', name: 'total_nominal', className: 'text-right'},
            {data: 'status_display', name: 'status', className: 'text-center'},
            {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center'}
        ],
        createdRow: function(row, data, dataIndex) {
            $(row).find('td:not(:last-child)').css('cursor', 'pointer');
        }
    });

    // ── Filter Tahun Anggaran ─────────────────────────
    $('#filterYearBank').change(function() {
        table.ajax.reload();
    });

    // Format child row HTML for nested items table
    function formatBankChildRow(rowData) {
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
                    let desc = it.description || '-';
                    if (desc.indexOf('Belanja: ') === 0) {
                        desc = desc.substring(9);
                    }
                    return {
                        id: it.id,
                        type: it.type,
                        type_badge: it.type === 'perjadin' 
                            ? '<span class="badge badge-info px-2 py-0" style="font-size:0.7rem;"><i class="fas fa-car mr-1"></i>Perjadin</span>' 
                            : '<span class="badge badge-secondary px-2 py-0" style="font-size:0.7rem;">Belanja</span>',
                        recipient_name: (it.recipient_name || '-').toUpperCase(),
                        bank_name: it.bank_name || '-',
                        account_number: it.account_number || '-',
                        amount: parseFloat(it.amount) || 0,
                        amount_display: 'Rp ' + formatRupiah(it.amount),
                        description: desc
                    };
                });
            } else {
                items = [];
            }
        }

        let tableRows = '';
        let grandTotal = 0;

        if (Array.isArray(items) && items.length > 0) {
            items.forEach(function(it, idx) {
                let rowAmount = parseFloat(it.amount) || 0;
                grandTotal += rowAmount;
                let bankAcc = escapeHtml(it.bank_name) + ' - <span class="font-tabular font-weight-bold text-primary">' + escapeHtml(it.account_number) + '</span>';
                
                let descHtml = escapeHtml(it.description);
                if (it.no_nota) {
                    descHtml = '<span class="badge badge-light border text-info font-tabular font-weight-bold mr-1" title="Berasal dari Rencana Belanja No. ' + escapeHtml(it.no_nota) + '"><i class="fas fa-receipt mr-1"></i>' + escapeHtml(it.no_nota) + '</span> ' + descHtml;
                }

                tableRows += '<tr>' +
                    '<td class="text-center" width="35">' + (idx + 1) + '</td>' +
                    '<td class="text-center" width="110">' + (it.type_badge || '') + '</td>' +
                    '<td><strong class="text-dark">' + escapeHtml(it.recipient_name) + '</strong></td>' +
                    '<td>' + bankAcc + '</td>' +
                    '<td class="text-right font-tabular font-weight-bold text-dark" width="140">' + (it.amount_display || ('Rp ' + formatRupiah(rowAmount))) + '</td>' +
                    '<td class="text-dark" style="font-size: 0.8125rem;">' + descHtml + '</td>' +
                '</tr>';
            });
        } else {
            tableRows = '<tr><td colspan="6" class="text-center text-muted py-2">Tidak ada rincian item transfer.</td></tr>';
        }

        let printUrl = "{{ url($routePrefix . '/pencairan-bank/print') }}/" + rowData.id;
        let excelUrl = "{{ url($routePrefix . '/pencairan-bank') }}/" + rowData.id + "/export-excel";
        let letterNum = rowData.letter_number ? escapeHtml(rowData.letter_number) : '-';
        let letterDate = rowData.letter_date ? String(rowData.letter_date).substring(0, 10) : '-';

        return '<div class="nested-bank-detail p-3 my-1 border rounded bg-white shadow-sm" style="border-left: 4px solid #28a745 !important;">' +
            '<div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom flex-wrap" style="gap: 8px;">' +
                '<div>' +
                    '<span class="badge badge-light border text-success font-tabular font-weight-bold mr-2"><i class="fas fa-file-invoice mr-1"></i>' + letterNum + '</span>' +
                    '<strong class="text-dark" style="font-size: 0.88rem;">Rincian Penerima Transfer (' + items.length + ' Rekening)</strong>' +
                    '<small class="text-muted ml-2"><i class="far fa-calendar-alt mr-1"></i>Tgl Surat: <strong>' + letterDate + '</strong></small>' +
                '</div>' +
                '<div class="d-flex align-items-center" style="gap: 8px;">' +
                    '<a href="' + excelUrl + '" class="btn btn-xs btn-outline-success font-weight-bold" title="Unduh File Excel Softcopy untuk Bank Jatim"><i class="fas fa-file-excel mr-1"></i> Unduh Excel TNT</a>' +
                    '<a href="' + printUrl + '" target="_blank" class="btn btn-xs btn-outline-secondary font-weight-bold" title="Cetak Surat TNT"><i class="fas fa-print mr-1"></i> Cetak Surat TNT</a>' +
                '</div>' +
            '</div>' +
            '<div class="table-responsive">' +
                '<table class="table table-sm table-bordered table-striped mb-0 table-nested-bank w-100" style="font-size: 0.8rem;">' +
                    '<thead>' +
                        '<tr>' +
                            '<th class="text-center" width="35">No</th>' +
                            '<th class="text-center" width="110">Jenis</th>' +
                            '<th width="220">Nama Penerima</th>' +
                            '<th width="220">Bank & No. Rekening / VA</th>' +
                            '<th class="text-right" width="140">Nilai Transfer</th>' +
                            '<th>Keterangan / Keperluan</th>' +
                        '</tr>' +
                    '</thead>' +
                    '<tbody>' + tableRows + '</tbody>' +
                    '<tfoot>' +
                        '<tr>' +
                            '<th colspan="4" class="text-right font-weight-bold">Total Nilai Transfer:</th>' +
                            '<th class="text-right font-weight-bold text-success font-tabular">Rp ' + formatRupiah(grandTotal) + '</th>' +
                            '<th></th>' +
                        '</tr>' +
                    '</tfoot>' +
                '</table>' +
            '</div>' +
        '</div>';
    }

    function toggleBankChildRow(tr) {
        if (!tr || tr.length === 0) return;
        let row = table.row(tr);
        if (!row || !row.data()) return;

        let btn = tr.find('.btn-toggle-detail');
        let icon = btn.find('i');

        if (row.child.isShown()) {
            row.child.hide();
            tr.removeClass('shown');
            icon.removeClass('fa-minus').addClass('fa-plus');
            btn.removeClass('btn-outline-danger').addClass('btn-outline-success');
        } else {
            let childHtml = formatBankChildRow(row.data());
            row.child(childHtml).show();
            tr.addClass('shown');
            icon.removeClass('fa-plus').addClass('fa-minus');
            btn.removeClass('btn-outline-success').addClass('btn-outline-danger');
        }
    }

    // Direct click on toggle button [+]
    $(document).on('click', '#bankTable .btn-toggle-detail', function(e) {
        e.preventDefault();
        e.stopPropagation();
        let tr = $(this).closest('tr');
        toggleBankChildRow(tr);
    });

    // Click on row cell (except actions)
    $(document).on('click', '#bankTable tbody td', function(e) {
        // Ignore clicks inside child rows
        if ($(this).closest('.nested-bank-detail').length || $(this).closest('tr').hasClass('child')) {
            return;
        }

        // Ignore if clicked on action buttons, links, or inputs
        if ($(e.target).closest('.btn-cair, .btn-delete, .btn-toggle-detail, a, button, input').length) {
            return;
        }

        // Ignore last column (action buttons)
        if ($(this).is(':last-child')) {
            return;
        }

        let tr = $(this).closest('tr');
        toggleBankChildRow(tr);
    });

    // Expand all child rows
    $('#btn-expand-all').on('click', function() {
        table.rows().every(function() {
            let row = this;
            let tr = $(row.node());
            if (!row.child.isShown()) {
                row.child(formatBankChildRow(row.data())).show();
                tr.addClass('shown');
                tr.find('.btn-toggle-detail').find('i').removeClass('fa-plus').addClass('fa-minus');
                tr.find('.btn-toggle-detail').removeClass('btn-outline-success').addClass('btn-outline-danger');
            }
        });
    });

    // Collapse all child rows
    $('#btn-collapse-all').on('click', function() {
        table.rows().every(function() {
            let row = this;
            let tr = $(row.node());
            if (row.child.isShown()) {
                row.child.hide();
                tr.removeClass('shown');
                tr.find('.btn-toggle-detail').find('i').removeClass('fa-minus').addClass('fa-plus');
                tr.find('.btn-toggle-detail').removeClass('btn-outline-danger').addClass('btn-outline-success');
            }
        });
    });

    @if($totalPendingCount > 0)
    if (typeof Swal !== 'undefined') {
        @php
            if ($unpaidItemsCount > 0 && $unpaidRencanaCount > 0) {
                $toastTitle = "Ada Tagihan Belum Masuk TNT ({$totalPendingCount}) - Tahun {$activeFiscalYear}";
                $toastHtml = '<span class="small font-tabular">Perjadin: ' . $unpaidItemsCount . ' Guru (Rp ' . number_format($unpaidTotalAmount, 0, ',', '.') . ') &bull; Rencana: ' . $unpaidRencanaCount . ' Paket (Rp ' . number_format($unpaidRencanaTotalAmount, 0, ',', '.') . ')</span>';
            } elseif ($unpaidRencanaCount > 0) {
                $toastTitle = "Ada {$unpaidRencanaCount} Rencana Belanja Belum Masuk TNT (Tahun {$activeFiscalYear})";
                $toastHtml = '<span class="small font-tabular">' . $unpaidRencanaCount . ' Rencana Belanja (Rp ' . number_format($unpaidRencanaTotalAmount, 0, ',', '.') . ') belum dimasukkan ke surat TNT.</span>';
            } else {
                $toastTitle = "Ada {$unpaidPerjadinsCount} Perjadin Belum Masuk TNT (Tahun {$activeFiscalYear})";
                $toastHtml = '<span class="small font-tabular">' . $unpaidItemsCount . ' Guru (Rp ' . number_format($unpaidTotalAmount, 0, ',', '.') . ') belum dimasukkan ke surat TNT.</span>';
            }
        @endphp
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'warning',
            title: @json($toastTitle),
            html: @json($toastHtml),
            showConfirmButton: true,
            confirmButtonText: 'Buat TNT',
            showCancelButton: true,
            cancelButtonText: 'Tutup',
            timer: 8000,
            timerProgressBar: true
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "{{ route($routePrefix . '.pencairan-bank.create') }}";
            }
        });
    }

    // Support trigger by old or new modal ID
    $(document).on('click', '[data-target="#modalUnpaidPerjadin"], [data-bs-target="#modalUnpaidPerjadin"]', function(e) {
        e.preventDefault();
        $('#modalUnpaidTagihan').modal('show');
    });
    @endif

    let currentActionUrl = '';

    $(document).on('click', '.btn-cair', function() {
        currentActionUrl = $(this).data('url');
        $('#cairDate').val(new Date().toISOString().split('T')[0]);
        $('#cairBku').val('');

        let el = document.getElementById('modalConfirmCair');
        if (window.bootstrap && window.bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(el).show();
        } else if (window.$ && typeof $(el).modal === 'function') {
            $(el).modal('show');
        }
    });

    $('#formConfirmCair').submit(function(e) {
        e.preventDefault();
        let submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

        $.ajax({
            url: currentActionUrl,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                cair_date: $('#cairDate').val(),
                bku_number: $('#cairBku').val()
            },
            dataType: 'json',
            success: function(res) {
                let el = document.getElementById('modalConfirmCair');
                if (window.bootstrap && window.bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance(el).hide();
                } else if (window.$ && typeof $(el).modal === 'function') {
                    $(el).modal('hide');
                }

                table.draw(false);
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: res.message,
                    timer: 2500,
                    showConfirmButton: false
                });
            },
            error: function(xhr) {
                Swal.fire('Gagal!', xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan sistem.', 'error');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('<i class="fas fa-check mr-1"></i> Simpan & Lunaskan');
            }
        });
    });

    $(document).on('click', '.btn-delete', function() {
        let url = $(this).data('url');
        let name = $(this).data('name');

        Swal.fire({
            title: 'Hapus Surat Kuasa Bank?',
            html: `Apakah Anda yakin ingin menghapus surat nomor <strong>${name}</strong>? Tagihan perjadin yang terhubung akan dikembalikan ke status draft.`,
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
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        table.draw(false);
                        Swal.fire('Terhapus!', res.message, 'success');
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
