@extends('adminlte::page')

@php
    $selectedYear = $selectedYear ?? $activeFiscalYear ?? session('fiscal_year', date('Y'));
@endphp

@section('title', 'Tambah Perjalanan Dinas (' . $fundingSource . ')')

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap" style="padding: 2px 0;">
    <div>
        <h1 class="page-title"><i class="fas fa-plus-circle text-primary mr-1"></i> Tambah Perjalanan Dinas {{ $fundingSource }}</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-house"></i> Dashboard</a></li>
                <li class="breadcrumb-item"><i class="fas fa-calculator"></i> Penganggaran / {{ $fundingSource }}</li>
                <li class="breadcrumb-item"><a href="{{ route($routePrefix . '.perjadin.index') }}">Perjalanan Dinas</a></li>
                <li class="breadcrumb-item active">Tambah Baru</li>
            </ol>
        </nav>
    </div>
    <div class="header-actions d-flex align-items-center gap-2">
        <button type="button" class="btn btn-sm btn-outline-success shadow-sm" id="btnOpenImport">
            <i class="fas fa-file-excel me-1"></i> Import Banyak via Excel
        </button>
        <a href="{{ route($routePrefix . '.perjadin.index') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar
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
    ::placeholder, .form-control::placeholder {
        color: #94a3b8 !important;
        opacity: 0.75 !important;
        font-weight: 400 !important;
    }
    #instansiSuggestBox {
        max-height: 220px;
        overflow-y: auto;
        font-size: 0.8125rem;
    }
    #instansiSuggestBox::-webkit-scrollbar {
        width: 6px;
    }
    #instansiSuggestBox::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 4px;
    }
    #instansiSuggestBox::-webkit-scrollbar-thumb:hover {
        background-color: #94a3b8;
    }
    #instansiSuggestBox .dropdown-item {
        cursor: pointer;
        transition: background-color 0.15s ease;
    }
    #instansiSuggestBox .dropdown-item:hover,
    #instansiSuggestBox .dropdown-item.active {
        background-color: #f1f5f9;
        color: #0f172a;
    }
    .form-section-title {
        font-size: 0.88rem;
        font-weight: 700;
        color: #1e293b;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 6px;
        margin-bottom: 14px;
    }
    #tablePeserta th {
        font-size: 0.74rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 0.4rem 0.5rem !important;
        vertical-align: middle;
        background-color: #f8fafc;
        color: #475569;
    }
    #tablePeserta td {
        padding: 0.35rem 0.5rem !important;
        vertical-align: middle;
    }
</style>
@stop

@section('content')
<form action="{{ route($routePrefix . '.perjadin.store') }}" method="POST" id="perjadinForm">
    @csrf

    @if($errors->any())
        <div class="alert alert-danger py-2 px-3 mb-3" style="font-size: 0.8125rem;">
            <h6 class="font-weight-bold mb-1"><i class="fas fa-exclamation-triangle mr-1"></i> Terdapat kesalahan pengisian:</h6>
            <ul class="mb-0 pl-3">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Card 1: Informasi Kegiatan Perjadin --}}
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white py-2 px-3 border-bottom">
            <h6 class="card-title font-weight-bold text-dark mb-0" style="font-size: 0.92rem;">
                <i class="fas fa-info-circle text-primary mr-1"></i> 1. Informasi Kegiatan & Tujuan Dinas
            </h6>
        </div>
        <div class="card-body p-3">
            @if(isset($kertasKerjaItems) && $kertasKerjaItems->count() > 0)
            <div class="p-3 mb-3 rounded border shadow-none" style="background-color: #f0fdf4; border-color: #86efac !important;">
                <div class="row">
                    <input type="hidden" id="filterBudgetYear" value="{{ $selectedYear }}">
                    <div class="col-12">
                        <div class="form-group mb-1">
                            <label class="small font-weight-bold text-success mb-1">
                                <i class="fas fa-link mr-1"></i> Hubungkan Kegiatan Kertas Kerja Perjalanan Dinas (BOS) <span class="badge bg-success text-white font-weight-normal ms-1 ml-1" style="font-size: 0.72rem;"><i class="fas fa-calendar-alt mr-1"></i> Tahun Anggaran: {{ $selectedYear }}</span>:
                            </label>
                            <select name="bos_kertas_kerja_item_id" id="bosKertasKerjaItemId" class="form-control form-control-sm font-weight-500 @error('bos_kertas_kerja_item_id') is-invalid @enderror">
                                <option value="">-- Tidak Dihubungkan / Input Bebas --</option>
                                @foreach($kertasKerjaItems as $kk)
                                    @php
                                        $remaining = isset($kk->available_volume) ? (float) $kk->available_volume : max(0, (float) $kk->volume - (float) $kk->realisasis->sum('volume'));
                                        $isExhausted = ($remaining <= 0.001);
                                    @endphp
                                    <option value="{{ $kk->id }}" 
                                        data-year="{{ $kk->year }}"
                                        data-kegiatan="{{ $kk->kegiatan }}"
                                        data-description="{{ $kk->description }}"
                                        data-account="{{ $kk->account_code }}"
                                        data-price="{{ (float) $kk->price }}"
                                        data-unit="{{ $kk->unit }}"
                                        data-remaining="{{ $remaining }}"
                                        {{ $isExhausted ? 'disabled' : '' }}
                                        {{ old('bos_kertas_kerja_item_id') == $kk->id ? 'selected' : '' }}>
                                        [{{ $kk->month }} {{ $kk->year }}] {{ $kk->kegiatan }} &mdash; {{ $kk->description }} 
                                        @if($isExhausted)
                                            (⛔ HABIS - Sisa: 0 {{ $kk->unit }})
                                        @else
                                            (Tarif: Rp {{ number_format($kk->price, 0, ',', '.') }} / {{ $kk->unit }} | Sisa: {{ $remaining }} {{ $kk->unit }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('bos_kertas_kerja_item_id')
                                <div class="invalid-feedback font-weight-bold d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div id="kkItemInfo" class="mt-1 small text-muted font-italic">
                    <i class="fas fa-info-circle mr-1"></i>Menghubungkan kegiatan akan otomatis mengisi tarif transport, kode rekening, dan merealisasikan Kertas Kerja saat TNT dicairkan.
                </div>
            </div>
            @endif

            {{-- Baris 1: Maksud Perjalanan Dinas (Satu Baris Penuh dengan Referensi Pintar) --}}
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="small font-weight-bold text-dark mb-0">
                                Maksud Perjalanan Dinas <span class="text-danger">*</span>
                            </label>
                            <span class="badge badge-light border text-muted" style="font-size: 0.7rem;">
                                <i class="fas fa-magic text-warning mr-1"></i>Referensi Pintar Aktif
                            </span>
                        </div>
                        <div class="position-relative">
                            <input type="text" name="activity_purpose" id="activityPurpose" class="form-control form-control-sm" placeholder="Ketik maksud dinas atau pilih dari saran referensi..." value="{{ old('activity_purpose') }}" autocomplete="off" required>
                            <div id="purposeSuggestBox" class="dropdown-menu shadow-sm w-100 p-1 border" style="display: none; position: absolute; top: 100%; left: 0; z-index: 1050; max-height: 240px; overflow-y: auto; font-size: 0.8125rem;"></div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <small class="text-muted" style="font-size: 0.72rem;">
                                <i class="fas fa-search mr-1"></i>Pilih dari referensi yang pernah ada atau ketik maksud baru (otomatis tersimpan).
                            </small>
                        </div>
                        @if(!empty($existingPurposes) && count($existingPurposes) > 0)
                        <div class="d-flex flex-wrap align-items-center gap-1 mt-1 pt-1" id="quickPurposeChips">
                            <span class="text-muted mr-1" style="font-size: 0.72rem;">Sering digunakan:</span>
                            @foreach(array_slice($existingPurposes, 0, 4) as $qp)
                                <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 rounded-pill btn-quick-purpose text-truncate mb-1 mr-1" style="max-width: 260px; font-size: 0.71rem;" title="{{ $qp }}" data-purpose="{{ $qp }}">
                                    <i class="fas fa-history mr-1 text-info"></i>{{ $qp }}
                                </button>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Baris 2: Tujuan Dinas & Lokasi --}}
            <div class="row">
                <div class="col-md-5">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold text-dark mb-1">
                            Tujuan Dinas (Nama Tempat / Instansi) <span class="text-danger">*</span>
                        </label>
                        <div class="position-relative">
                            <input type="text" name="destination_name" id="destinationName" class="form-control form-control-sm" placeholder="Ketik nama sekolah, kantor dinas, DUDI..." value="{{ old('destination_name') }}" autocomplete="off" required>
                            <div id="instansiSuggestBox" class="dropdown-menu shadow-sm w-100 p-1 border" style="display: none; position: absolute; top: 100%; left: 0; z-index: 1050; max-height: 220px; overflow-y: auto; font-size: 0.8125rem;"></div>
                        </div>
                        <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">
                            <i class="fas fa-search mr-1"></i>Pilih referensi atau ketik instansi baru
                        </small>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold text-dark mb-1">Alamat Tujuan</label>
                        <input type="text" name="destination_address" id="destinationAddress" class="form-control form-control-sm" placeholder="Contoh: Jl. Marabunta, Ds. Selodono, Ringinrejo" value="{{ old('destination_address') }}">
                        <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">
                            <i class="fas fa-map-marker-alt mr-1"></i>Alamat lengkap lokasi tujuan dinas
                        </small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold text-dark mb-1">Kota / Kab. <span class="text-danger">*</span></label>
                        <input type="text" name="city" id="cityInput" class="form-control form-control-sm font-weight-500" value="{{ old('city', 'Kediri') }}" required>
                        <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">
                            <i class="fas fa-city mr-1"></i>Kota kwitansi
                        </small>
                    </div>
                </div>
            </div>

            <hr class="my-2" style="border-top: 1px solid #e2e8f0;">

            {{-- Baris 2: Waktu Pelaksanaan & Tanggal Kwitansi (3 Kolom Sejajar) --}}
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold text-dark mb-1">
                            Tanggal Pelaksanaan Dinas <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="departure_date" id="departureDate" class="form-control form-control-sm font-tabular" value="{{ old('departure_date', date('Y-m-d')) }}" required>
                        <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">
                            <i class="fas fa-calendar-alt mr-1"></i>Tanggal awal kegiatan dinas
                        </small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold text-dark mb-1">
                            Tanggal Selesai <small class="text-muted font-weight-normal">(Jika menginap)</small>
                        </label>
                        <input type="date" name="return_date" class="form-control form-control-sm font-tabular" value="{{ old('return_date') }}">
                        <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">
                            <i class="fas fa-calendar-check mr-1"></i>Kosongkan jika dinas 1 hari selesai
                        </small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold text-dark mb-1">
                            Tanggal Bayar Kwitansi <small class="text-muted font-weight-normal">(Opsional)</small>
                        </label>
                        <input type="date" name="payment_date" id="paymentDate" class="form-control form-control-sm font-tabular" value="{{ old('payment_date') }}">
                        <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">
                            <i class="fas fa-receipt mr-1"></i>Kosongkan jika menunggu pencairan bank
                        </small>
                    </div>
                </div>
            </div>

            <hr class="my-2" style="border-top: 1px solid #e2e8f0;">

            {{-- Baris 3: Pembukuan & Penandatangan (3 Kolom Sejajar Rapi) --}}
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group mb-1">
                        <div class="d-flex align-items-center mb-1" style="height: 18px;">
                            <label class="small font-weight-bold text-dark mb-0">Kode Rekening</label>
                        </div>
                        <input type="text" name="account_code" class="form-control form-control-sm font-tabular" placeholder="Contoh: 5.1.02.04.01.0003" value="{{ old('account_code', $defaultAccountCode) }}">
                        <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">
                            <i class="fas fa-tag mr-1"></i>Kode rekening belanja perjadin
                        </small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-1">
                        <div class="d-flex align-items-center mb-1" style="height: 18px;">
                            <label class="small font-weight-bold text-dark mb-0">Kepala Sekolah <span class="text-danger">*</span></label>
                        </div>
                        <select name="kepala_sekolah_id" class="form-control form-control-sm" required>
                            @foreach($gurus as $g)
                                <option value="{{ $g->id }}" {{ old('kepala_sekolah_id', $defaultKepsekId) == $g->id ? 'selected' : '' }}>
                                    {{ $g->full_name_with_title }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">
                            <i class="fas fa-user-tie mr-1"></i>Pejabat penandatangan SPT/SPPD
                        </small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-1">
                        <div class="d-flex align-items-center mb-1" style="height: 18px;">
                            <label class="small font-weight-bold text-dark mb-0">Bendahara {{ $fundingSource }} <span class="text-danger">*</span></label>
                        </div>
                        <select name="bendahara_id" class="form-control form-control-sm" required>
                            @foreach($gurus as $g)
                                <option value="{{ $g->id }}" {{ old('bendahara_id', $defaultBendaharaId) == $g->id ? 'selected' : '' }}>
                                    {{ $g->full_name_with_title }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">
                            <i class="fas fa-user-check mr-1"></i>Pejabat pembayar kwitansi
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Card 2: Peserta Dinas & Rincian Biaya Kwitansi --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center flex-wrap">
            <h6 class="card-title font-weight-bold text-dark mb-0 py-1" style="font-size: 0.90rem;">
                <i class="fas fa-users text-success mr-1"></i> 2. Guru Peserta Perjadin & Rincian Kwitansi
            </h6>
            <div class="d-flex align-items-center justify-content-end ms-auto ml-auto gap-2">
                <span class="text-muted font-weight-bold me-1 mr-2 text-nowrap" style="font-size: 0.8125rem;">Standar Rp</span>
                <input type="text" id="quickNominal" class="form-control form-control-sm font-tabular text-right font-weight-bold" style="width: 105px; font-size: 0.8125rem;" value="65.000" placeholder="65.000">
                <button type="button" id="btnApplyAll" class="btn btn-sm btn-outline-primary font-weight-bold px-2 me-2 mr-3" style="font-size: 0.8125rem;" title="Terapkan nominal standar ini ke semua peserta">
                    <i class="fas fa-check-double me-1 mr-1"></i> Terapkan
                </button>
                <div class="border-start border-left ps-3 pl-3 d-inline-block">
                    <button type="button" id="btnAddRow" class="btn btn-sm btn-success font-weight-bold shadow-sm px-3" style="font-size: 0.8125rem;">
                        <i class="fas fa-user-plus me-1 mr-1"></i> Tambah Peserta
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0" id="tablePeserta">
                    <thead class="bg-light text-dark text-center">
                        <tr>
                            <th width="4%">No</th>
                            <th width="30%">Nama Guru Peserta <span class="text-danger">*</span></th>
                            <th width="24%">Rekening Bank Tujuan</th>
                            <th width="14%">Uang Diterima (Rp) <span class="text-danger">*</span></th>
                            <th width="7%">PPN</th>
                            <th width="7%">PPh 23</th>
                            <th width="10%">Jumlah Bersih</th>
                            <th width="4%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyPeserta">
                        {{-- Rows rendered by JS --}}
                    </tbody>
                    <tfoot class="bg-light font-weight-bold">
                        <tr>
                            <td colspan="6" class="text-right py-2 text-dark font-weight-bold" style="font-size: 0.8125rem;">TOTAL BIAYA PERJALANAN DINAS:</td>
                            <td class="text-right py-2 font-tabular text-success font-weight-bold" id="totalDisplay" style="font-size: 0.8125rem;">Rp 0</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white py-3 border-top d-flex justify-content-between align-items-center">
            <span class="text-muted small"><i class="fas fa-info-circle mr-1"></i> Setiap guru yang ditambahkan otomatis akan memiliki 1 lembar kwitansi resmi.</span>
            <div>
                <a href="{{ route($routePrefix . '.perjadin.index') }}" class="btn btn-sm btn-secondary mr-2">Batal</a>
                <button type="submit" class="btn btn-sm btn-primary px-4 font-weight-bold">
                    <i class="fas fa-save mr-1"></i> Simpan Perjadin
                </button>
            </div>
        </div>
    </div>
</form>

@include('perjadin._import_modal')
@stop

@section('js')
<script>
const gurusData = @json($gurus);
const instansisData = @json($instansis ?? []);
const purposesData = @json($existingPurposes ?? []);
let rowIndex = 0;

function formatRupiah(num) {
    let n = Math.round(parseFloat(num) || 0);
    return 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(n);
}

function formatNumberRibuan(val) {
    let n = Math.round(parseFloat(val) || 0);
    return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(n);
}

function parseNumberRibuan(str) {
    if (typeof str === 'number') return Math.round(str);
    let clean = String(str || '').replace(/[^\d]/g, '');
    return parseInt(clean, 10) || 0;
}

function terbilangAngka(angka) {
    angka = Math.abs(Math.floor(Number(angka) || 0));
    const bilangan = [
        '', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima',
        'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'
    ];

    let hasil = '';
    if (angka < 12) {
        hasil = ' ' + bilangan[angka];
    } else if (angka < 20) {
        hasil = terbilangAngka(angka - 10) + ' Belas';
    } else if (angka < 100) {
        hasil = terbilangAngka(Math.floor(angka / 10)) + ' Puluh ' + terbilangAngka(angka % 10);
    } else if (angka < 200) {
        hasil = ' Seratus ' + terbilangAngka(angka - 100);
    } else if (angka < 1000) {
        hasil = terbilangAngka(Math.floor(angka / 100)) + ' Ratus ' + terbilangAngka(angka % 100);
    } else if (angka < 2000) {
        hasil = ' Seribu ' + terbilangAngka(angka - 1000);
    } else if (angka < 1000000) {
        hasil = terbilangAngka(Math.floor(angka / 1000)) + ' Ribu ' + terbilangAngka(angka % 1000);
    } else if (angka < 1000000000) {
        hasil = terbilangAngka(Math.floor(angka / 1000000)) + ' Juta ' + terbilangAngka(angka % 1000000);
    } else if (angka < 1000000000000) {
        hasil = terbilangAngka(Math.floor(angka / 1000000000)) + ' Miliar ' + terbilangAngka(angka % 1000000000);
    } else if (angka < 1000000000000000) {
        hasil = terbilangAngka(Math.floor(angka / 1000000000000)) + ' Triliun ' + terbilangAngka(angka % 1000000000000);
    }

    return hasil.replace(/\s+/g, ' ').trim();
}

function terbilangRupiah(angka) {
    angka = Math.floor(Number(angka) || 0);
    if (angka <= 0) {
        return 'Nol Rupiah';
    }
    let kata = terbilangAngka(angka);
    let formatted = kata.toLowerCase().replace(/\b[a-z]/g, function(letter) {
        return letter.toUpperCase();
    });
    return formatted + ' Rupiah';
}

function addPesertaRow(guruId = '', amount = null, ppn = 0, pph23 = 0) {
    if (amount === null || amount === undefined || amount === '') {
        let opt = $('#bosKertasKerjaItemId').find(':selected');
        let kkPrice = parseFloat(opt.data('price')) || 0;
        amount = kkPrice > 0 ? kkPrice : (parseNumberRibuan($('#quickNominal').val()) || 65000);
    }
    let options = '<option value="">-- Pilih Guru --</option>';
    let selectedBankInfo = '<span class="text-muted font-italic">- Belum dipilih -</span>';
    let bankAccId = '';

    gurusData.forEach(function(g) {
        let isSel = (g.id == guruId) ? 'selected' : '';
        let guruName = g.full_name_with_title || g.name || '-';
        options += `<option value="${g.id}" ${isSel}>${guruName}</option>`;
        if (g.id == guruId) {
            if (g.primary_bank_account) {
                selectedBankInfo = `<strong>${g.primary_bank_account.bank_name}</strong><br><small class="font-tabular">${g.primary_bank_account.account_number} a.n ${g.primary_bank_account.account_holder}</small>`;
                bankAccId = g.primary_bank_account.id;
            } else {
                selectedBankInfo = '<span class="text-warning font-italic"><i class="fas fa-exclamation-triangle mr-1"></i>Belum ada rekening</span>';
            }
        }
    });

    let numAmount = parseNumberRibuan(amount);
    let numPpn = parseNumberRibuan(ppn);
    let numPph23 = parseNumberRibuan(pph23);
    let net = Math.max(0, numAmount - numPpn - numPph23);

    let tr = `
    <tr id="row_${rowIndex}" class="peserta-row">
        <td class="text-center font-tabular row-number align-middle">${$('.peserta-row').length + 1}</td>
        <td>
            <select name="items[${rowIndex}][guru_id]" class="form-control form-control-sm select-guru" data-idx="${rowIndex}" required>
                ${options}
            </select>
            <input type="hidden" name="items[${rowIndex}][guru_bank_account_id]" id="bankAccId_${rowIndex}" value="${bankAccId}">
        </td>
        <td class="align-middle small" id="bankInfo_${rowIndex}">
            ${selectedBankInfo}
        </td>
        <td>
            <input type="text" class="form-control form-control-sm font-tabular text-right input-amount" data-idx="${rowIndex}" value="${formatNumberRibuan(numAmount)}" placeholder="0" required>
            <input type="hidden" name="items[${rowIndex}][amount]" id="hiddenAmount_${rowIndex}" value="${numAmount}">
        </td>
        <td>
            <input type="text" class="form-control form-control-sm font-tabular text-right input-ppn" data-idx="${rowIndex}" value="${numPpn > 0 ? formatNumberRibuan(numPpn) : '0'}" placeholder="0">
            <input type="hidden" name="items[${rowIndex}][ppn]" id="hiddenPpn_${rowIndex}" value="${numPpn}">
        </td>
        <td>
            <input type="text" class="form-control form-control-sm font-tabular text-right input-pph" data-idx="${rowIndex}" value="${numPph23 > 0 ? formatNumberRibuan(numPph23) : '0'}" placeholder="0">
            <input type="hidden" name="items[${rowIndex}][pph23]" id="hiddenPph23_${rowIndex}" value="${numPph23}">
        </td>
        <td class="align-middle text-right font-tabular font-weight-bold text-dark">
            <span id="displayNet_${rowIndex}">${formatRupiah(net)}</span>
            <input type="hidden" name="items[${rowIndex}][net_amount]" id="inputNet_${rowIndex}" value="${net}">
        </td>
        <td class="text-center align-middle">
            <button type="button" class="btn btn-xs btn-outline-danger btn-remove-row" data-idx="${rowIndex}" title="Hapus"><i class="fas fa-times"></i></button>
        </td>
    </tr>
    `;

    $('#tbodyPeserta').append(tr);
    rowIndex++;
    recalculateTotal();
}

function recalculateRow(idx) {
    let amount = parseFloat($(`#hiddenAmount_${idx}`).val()) || 0;
    let ppn = parseFloat($(`#hiddenPpn_${idx}`).val()) || 0;
    let pph = parseFloat($(`#hiddenPph23_${idx}`).val()) || 0;
    let net = Math.max(0, amount - ppn - pph);

    $(`#displayNet_${idx}`).text(formatRupiah(net));
    $(`#inputNet_${idx}`).val(net);
    recalculateTotal();
}

function recalculateTotal() {
    let total = 0;
    let count = 0;
    $('.peserta-row').each(function(i) {
        $(this).find('.row-number').text(i + 1);
        let idx = $(this).attr('id').replace('row_', '');
        let net = parseFloat($(`#inputNet_${idx}`).val()) || 0;
        total += net;
        count++;
    });

    $('#totalDisplay').text(formatRupiah(total));
    $('#terbilangDisplay').text(total > 0 ? terbilangRupiah(total) : '-');

    if ($('#btnAutoBkuText').length) {
        $('#btnAutoBkuText').text(count > 1 ? `Rentang (${count} Guru)` : 'Format No. BKU');
    }

    if (typeof updateKkAllocationInfo === 'function') {
        updateKkAllocationInfo();
    }
}

function getDepartureYear() {
    let depDate = $('#departureDate').val();
    if (depDate) {
        let parts = depDate.split('-');
        if (parts.length === 3 && parts[0].length === 4) {
            return parts[0];
        }
    }
    return new Date().getFullYear();
}

function generateBkuRange(rawVal) {
    let val = (rawVal || $('#bkuNumber').val() || '').trim();
    let year = getDepartureYear();
    let teacherCount = $('.peserta-row').length || 1;

    if (!val) {
        val = 'BPU001';
    }

    // Buang suffix tahun lama jika ada (misal: " / 2025")
    let baseBku = val.replace(/\s*\/\s*\d{4}$/, '').trim();

    let prefix = 'BPU';
    let startNum = 1;
    let padLength = 3;

    // Cek jika format rentang "BPU070 - BPU074" atau "BPU070 - 074"
    let rangeMatch = baseBku.match(/^([A-Za-z\s]*?)(\d+)\s*[-–—]\s*(?:[A-Za-z\s]*?)(\d+)$/);
    if (rangeMatch) {
        prefix = rangeMatch[1].trim() || 'BPU';
        let numStr = rangeMatch[2];
        padLength = numStr.length;
        startNum = parseInt(numStr, 10) || 1;
    } else {
        let singleMatch = baseBku.match(/^([A-Za-z\s]*?)(\d+)$/);
        if (singleMatch) {
            prefix = singleMatch[1].trim() || 'BPU';
            let numStr = singleMatch[2];
            padLength = numStr.length;
            startNum = parseInt(numStr, 10) || 1;
        }
    }

    let startFormatted = prefix + String(startNum).padStart(padLength, '0');
    if (teacherCount <= 1) {
        return `${startFormatted} / ${year}`;
    } else {
        let endNum = startNum + teacherCount - 1;
        let endFormatted = prefix + String(endNum).padStart(padLength, '0');
        return `${startFormatted} - ${endFormatted} / ${year}`;
    }
}

$(document).ready(function() {
    // Autocomplete Minimalis & Kompak untuk Tujuan Dinas (Solusi 2)
    let currentMatches = [];
    let activeSuggestIdx = -1;

    function renderSuggestions(matches) {
        currentMatches = matches;
        activeSuggestIdx = -1;
        let $box = $('#instansiSuggestBox');

        if (matches.length === 0) {
            $box.html('<div class="px-2 py-1 text-muted" style="font-size:0.75rem;"><i class="fas fa-info-circle mr-1"></i>Instansi baru (otomatis tersimpan saat form disimpan).</div>').show();
            return;
        }

        let html = '';
        matches.forEach(function(inst, idx) {
            let badge = '<span class="badge badge-secondary" style="font-size:0.68rem; font-weight:500;">Lainnya</span>';
            if (inst.category === 'sekolah') {
                badge = '<span class="badge badge-primary" style="font-size:0.68rem; font-weight:500;"><i class="fas fa-graduation-cap mr-1"></i>Sekolah</span>';
            } else if (inst.category === 'dudi') {
                badge = '<span class="badge badge-warning text-dark" style="font-size:0.68rem; font-weight:500;"><i class="fas fa-industry mr-1"></i>DUDI</span>';
            } else if (inst.category === 'dinas') {
                badge = '<span class="badge badge-info" style="font-size:0.68rem; font-weight:500;"><i class="fas fa-landmark mr-1"></i>Dinas</span>';
            }

            let city = inst.city ? `<small class="text-muted ml-1">· ${inst.city}</small>` : '';

            html += `
            <a href="javascript:void(0)" class="dropdown-item py-1 px-2 d-flex align-items-center justify-content-between rounded instansi-item" data-idx="${idx}">
                <div class="text-truncate mr-2">
                    <span class="font-weight-500 text-dark">${inst.name}</span>${city}
                </div>
                ${badge}
            </a>`;
        });

        $box.html(html).show();
    }

    function selectInstansi(inst) {
        $('#destinationName').val(inst.name);
        if (inst.address) {
            $('#destinationAddress').val(inst.address);
        }
        if (inst.city) {
            $('#cityInput').val(inst.city);
        }
        $('#instansiSuggestBox').hide();
    }

    function filterAndShowSuggestions() {
        let q = $('#destinationName').val().trim().toLowerCase();
        if (q.length === 0) {
            $('#instansiSuggestBox').hide();
            return;
        }

        // Tampilkan semua referensi yang cocok dengan ketikan (bisa di-scroll ke bawah)
        let matches = instansisData.filter(function(item) {
            return item.name.toLowerCase().includes(q) || (item.address && item.address.toLowerCase().includes(q));
        });

        renderSuggestions(matches);
    }

    $('#destinationName').on('input focus', function() {
        filterAndShowSuggestions();
    });

    // Navigasi keyboard (Panah Bawah, Panah Atas, Enter, Escape)
    $('#destinationName').on('keydown', function(e) {
        let $box = $('#instansiSuggestBox');
        if (!$box.is(':visible') || currentMatches.length === 0) return;

        let items = $box.find('.instansi-item');
        if (e.which === 40) { // Arrow Down
            e.preventDefault();
            activeSuggestIdx = (activeSuggestIdx + 1) % items.length;
            items.removeClass('active');
            let $active = items.eq(activeSuggestIdx).addClass('active');
            if ($active.length && $active[0].scrollIntoView) {
                $active[0].scrollIntoView({ block: 'nearest' });
            }
        } else if (e.which === 38) { // Arrow Up
            e.preventDefault();
            activeSuggestIdx = (activeSuggestIdx - 1 + items.length) % items.length;
            items.removeClass('active');
            let $active = items.eq(activeSuggestIdx).addClass('active');
            if ($active.length && $active[0].scrollIntoView) {
                $active[0].scrollIntoView({ block: 'nearest' });
            }
        } else if (e.which === 13) { // Enter
            if (activeSuggestIdx >= 0 && activeSuggestIdx < currentMatches.length) {
                e.preventDefault();
                selectInstansi(currentMatches[activeSuggestIdx]);
            }
        } else if (e.which === 27) { // Escape
            $box.hide();
        }
    });

    // Klik pilihan referensi
    $(document).on('click', '.instansi-item', function(e) {
        e.preventDefault();
        let idx = $(this).data('idx');
        if (currentMatches[idx]) {
            selectInstansi(currentMatches[idx]);
        }
    });

    // Klik di luar untuk menutup popup
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#destinationName, #instansiSuggestBox').length) {
            $('#instansiSuggestBox').hide();
        }
    });

    // Backfill otomatis jika nama diketik lengkap tanpa klik popup
    $('#destinationName').on('change', function() {
        let val = $(this).val().trim();
        let found = instansisData.find(i => i.name.toLowerCase() === val.toLowerCase());
        if (found) {
            if (found.address && !$('#destinationAddress').val()) {
                $('#destinationAddress').val(found.address);
            }
            if (found.city && !$('#cityInput').val()) {
                $('#cityInput').val(found.city);
            }
        }
    });

    // ── Autocomplete Cerdas untuk Maksud Perjalanan Dinas ──
    let currentPurposeMatches = [];
    let activePurposeSuggestIdx = -1;

    function renderPurposeSuggestions(matches) {
        currentPurposeMatches = matches;
        activePurposeSuggestIdx = -1;
        let $box = $('#purposeSuggestBox');

        if (matches.length === 0) {
            $box.html('<div class="px-2 py-1 text-muted" style="font-size:0.75rem;"><i class="fas fa-plus-circle text-primary mr-1"></i>Maksud dinas baru &mdash; otomatis tersimpan sebagai referensi berikutnya saat form disimpan.</div>').show();
            return;
        }

        let html = '';
        matches.forEach(function(purpose, idx) {
            html += `
            <a href="javascript:void(0)" class="dropdown-item py-1 px-2 d-flex align-items-center justify-content-between rounded purpose-item" data-idx="${idx}">
                <div class="text-truncate mr-2">
                    <i class="fas fa-clipboard-check text-success mr-2" style="font-size: 0.8rem;"></i>
                    <span class="font-weight-500 text-dark">${purpose}</span>
                </div>
                <span class="badge badge-light border text-muted" style="font-size:0.68rem; font-weight:500;">Pilih</span>
            </a>`;
        });

        $box.html(html).show();
    }

    function selectPurpose(purposeText) {
        $('#activityPurpose').val(purposeText);
        $('#purposeSuggestBox').hide();
    }

    function filterAndShowPurposeSuggestions() {
        let q = $('#activityPurpose').val().trim().toLowerCase();
        let matches;
        if (q.length === 0) {
            // Tampilkan rekomendasi awal jika input masih kosong
            matches = purposesData.slice(0, 8);
        } else {
            matches = purposesData.filter(function(item) {
                return item.toLowerCase().includes(q);
            });
        }
        renderPurposeSuggestions(matches);
    }

    $('#activityPurpose').on('focus input', function() {
        filterAndShowPurposeSuggestions();
    });

    // Navigasi Keyboard untuk Maksud Perjalanan Dinas (Arrow Down, Up, Enter, Esc)
    $('#activityPurpose').on('keydown', function(e) {
        let $box = $('#purposeSuggestBox');
        if (!$box.is(':visible') || currentPurposeMatches.length === 0) return;

        let items = $box.find('.purpose-item');
        if (e.which === 40) { // Arrow Down
            e.preventDefault();
            activePurposeSuggestIdx = (activePurposeSuggestIdx + 1) % items.length;
            items.removeClass('active bg-light');
            let $active = items.eq(activePurposeSuggestIdx).addClass('active bg-light');
            if ($active.length && $active[0].scrollIntoView) {
                $active[0].scrollIntoView({ block: 'nearest' });
            }
        } else if (e.which === 38) { // Arrow Up
            e.preventDefault();
            activePurposeSuggestIdx = (activePurposeSuggestIdx - 1 + items.length) % items.length;
            items.removeClass('active bg-light');
            let $active = items.eq(activePurposeSuggestIdx).addClass('active bg-light');
            if ($active.length && $active[0].scrollIntoView) {
                $active[0].scrollIntoView({ block: 'nearest' });
            }
        } else if (e.which === 13) { // Enter
            if (activePurposeSuggestIdx >= 0 && activePurposeSuggestIdx < currentPurposeMatches.length) {
                e.preventDefault();
                selectPurpose(currentPurposeMatches[activePurposeSuggestIdx]);
            }
        } else if (e.which === 27) { // Escape
            $box.hide();
        }
    });

    // Klik item saran maksud perjalanan
    $(document).on('click', '.purpose-item', function(e) {
        e.preventDefault();
        let idx = $(this).data('idx');
        if (currentPurposeMatches[idx]) {
            selectPurpose(currentPurposeMatches[idx]);
        }
    });

    // Klik pill chip maksud kegiatan yang sering digunakan
    $(document).on('click', '.btn-quick-purpose', function(e) {
        e.preventDefault();
        let text = $(this).data('purpose');
        if (text) {
            selectPurpose(text);
        }
    });

    // Klik di luar untuk menutup popup maksud dinas
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#activityPurpose, #purposeSuggestBox').length) {
            $('#purposeSuggestBox').hide();
        }
    });

    // Add default 1st row
    addPesertaRow();

    // Cache semua opsi Kertas Kerja untuk filter Tahun Anggaran
    let allKkOptions = [];
    $('#bosKertasKerjaItemId option').each(function() {
        allKkOptions.push({
            value: $(this).val(),
            text: $(this).text(),
            year: $(this).data('year'),
            kegiatan: $(this).data('kegiatan'),
            description: $(this).data('description'),
            account: $(this).data('account'),
            price: $(this).data('price'),
            unit: $(this).data('unit'),
            remaining: $(this).data('remaining'),
            disabled: $(this).is(':disabled'),
            selected: $(this).is(':selected')
        });
    });

    function filterKkByYear(selectedYear) {
        let currentVal = $('#bosKertasKerjaItemId').val();
        let $select = $('#bosKertasKerjaItemId');
        $select.empty();

        let matchedValExists = false;

        allKkOptions.forEach(function(opt) {
            if (!opt.value) {
                $select.append($('<option>', {
                    value: '',
                    text: opt.text
                }));
            } else if (String(opt.year) === String(selectedYear)) {
                let isSelected = (String(opt.value) === String(currentVal)) || opt.selected;
                if (isSelected) {
                    matchedValExists = true;
                }
                let $optEl = $('<option>', {
                    value: opt.value,
                    text: opt.text
                });
                $optEl.attr('data-year', opt.year);
                $optEl.attr('data-kegiatan', opt.kegiatan);
                $optEl.attr('data-description', opt.description);
                $optEl.attr('data-account', opt.account);
                $optEl.attr('data-price', opt.price);
                $optEl.attr('data-unit', opt.unit);
                $optEl.attr('data-remaining', opt.remaining);
                if (opt.disabled) {
                    $optEl.prop('disabled', true);
                }
                if (isSelected) {
                    $optEl.prop('selected', true);
                }
                $select.append($optEl);
            }
        });

        allKkOptions.forEach(function(opt) {
            if (opt.value) {
                opt.selected = (String(opt.value) === String($select.val()));
            }
        });

        if (!matchedValExists && currentVal) {
            $select.val('');
        }
        $select.trigger('change');
    }

    $('#filterBudgetYear').on('change', function() {
        filterKkByYear($(this).val());
    });

    if ($('#filterBudgetYear').length) {
        filterKkByYear($('#filterBudgetYear').val());
    }

    function updateKkAllocationInfo() {
        let opt = $('#bosKertasKerjaItemId').find(':selected');
        let id = $('#bosKertasKerjaItemId').val();
        if (id) {
            let price = parseFloat(opt.data('price')) || 0;
            let unit = opt.data('unit') || 'oh';
            let remaining = parseFloat(opt.data('remaining'));
            if (isNaN(remaining)) remaining = 0;
            let countPeserta = $('.peserta-row').length;

            if (remaining <= 0) {
                $('#kkItemInfo').html('<span class="badge badge-danger mr-2"><i class="fas fa-ban mr-1"></i>Alokasi Habis</span>' +
                    '<span class="text-danger font-weight-bold">Sisa Alokasi: 0 ' + unit + ' &mdash; Kegiatan ini tidak dapat dipilih karena volume alokasi sudah habis!</span>');
            } else if (countPeserta > remaining) {
                $('#kkItemInfo').html('<span class="badge badge-danger mr-2"><i class="fas fa-exclamation-triangle mr-1"></i>Melebihi Kuota</span>' +
                    '<span class="text-dark font-weight-500">Tarif: <strong>Rp ' + formatNumberRibuan(price) + ' / ' + unit + '</strong> &nbsp;|&nbsp; Sisa Alokasi: <strong class="text-danger">' + remaining + ' ' + unit + '</strong></span>' +
                    ' <span class="badge badge-warning text-dark font-weight-bold ml-2 ms-2"><i class="fas fa-users mr-1"></i>Peserta (' + countPeserta + ') melebihi sisa alokasi (' + remaining + ' ' + unit + ')!</span>');
            } else {
                let sisaSetelahIni = remaining - countPeserta;
                $('#kkItemInfo').html('<span class="badge badge-success mr-2"><i class="fas fa-check-circle mr-1"></i>Terhubung ke Kertas Kerja</span>' +
                    '<span class="text-dark font-weight-500">Tarif: <strong>Rp ' + formatNumberRibuan(price) + ' / ' + unit + '</strong> &nbsp;|&nbsp; Sisa Alokasi: <strong class="text-primary">' + remaining + ' ' + unit + '</strong> (Peserta: ' + countPeserta + ' ' + unit + ', Sisa setelah ini: ' + sisaSetelahIni + ' ' + unit + ')</span>');
            }
        } else {
            $('#kkItemInfo').html('<span class="text-muted font-italic"><i class="fas fa-info-circle mr-1"></i>Menghubungkan kegiatan akan otomatis mengisi tarif transport, kode rekening, dan merealisasikan Kertas Kerja saat TNT dicairkan.</span>');
        }
    }

    // Event listener when Kertas Kerja item is chosen
    $('#bosKertasKerjaItemId').on('change', function() {
        let opt = $(this).find(':selected');
        let id = $(this).val();
        if (id) {
            let account = opt.data('account');
            let price = parseFloat(opt.data('price')) || 0;

            if (account) {
                $('input[name="account_code"]').val(account);
            }

            if (price > 0) {
                $('#quickNominal').val(formatNumberRibuan(price));
                $('.peserta-row').each(function() {
                    let idx = $(this).attr('id').replace('row_', '');
                    $(`#hiddenAmount_${idx}`).val(price);
                    $(this).find('.input-amount').val(formatNumberRibuan(price));
                    recalculateRow(idx);
                });
            }
        }
        updateKkAllocationInfo();
    });

    if ($('#bosKertasKerjaItemId').val()) {
        $('#bosKertasKerjaItemId').trigger('change');
    }

    $('#btnAddRow').click(function() {
        let stdNominal = parseNumberRibuan($('#quickNominal').val()) || 65000;
        addPesertaRow('', stdNominal, 0, 0);
    });

    $('#btnApplyAll').click(function() {
        let val = parseNumberRibuan($('#quickNominal').val()) || 0;
        $('.input-amount').each(function() {
            let idx = $(this).data('idx');
            $(this).val(val > 0 ? formatNumberRibuan(val) : '');
            $(`#hiddenAmount_${idx}`).val(val);
            recalculateRow(idx);
        });
    });

    $(document).on('input', '#quickNominal', function() {
        let raw = parseNumberRibuan($(this).val());
        $(this).val(raw > 0 ? formatNumberRibuan(raw) : '');
    });

    $(document).on('input', '.input-amount', function() {
        let idx = $(this).data('idx');
        let raw = parseNumberRibuan($(this).val());
        $(this).val(raw > 0 ? formatNumberRibuan(raw) : '');
        $(`#hiddenAmount_${idx}`).val(raw);
        recalculateRow(idx);
    });

    $(document).on('input', '.input-ppn', function() {
        let idx = $(this).data('idx');
        let raw = parseNumberRibuan($(this).val());
        $(this).val(raw > 0 ? formatNumberRibuan(raw) : '');
        $(`#hiddenPpn_${idx}`).val(raw);
        recalculateRow(idx);
    });

    $(document).on('input', '.input-pph', function() {
        let idx = $(this).data('idx');
        let raw = parseNumberRibuan($(this).val());
        $(this).val(raw > 0 ? formatNumberRibuan(raw) : '');
        $(`#hiddenPph23_${idx}`).val(raw);
        recalculateRow(idx);
    });

    $(document).on('change', '.select-guru', function() {
        let idx = $(this).data('idx');
        let gId = $(this).val();
        let guru = gurusData.find(g => g.id == gId);

        if (guru && guru.primary_bank_account) {
            $(`#bankInfo_${idx}`).html(`<strong>${guru.primary_bank_account.bank_name}</strong><br><small class="font-tabular">${guru.primary_bank_account.account_number} a.n ${guru.primary_bank_account.account_holder}</small>`);
            $(`#bankAccId_${idx}`).val(guru.primary_bank_account.id);
        } else if (guru) {
            $(`#bankInfo_${idx}`).html('<span class="text-warning font-italic"><i class="fas fa-exclamation-triangle mr-1"></i>Belum ada rekening</span>');
            $(`#bankAccId_${idx}`).val('');
        } else {
            $(`#bankInfo_${idx}`).html('<span class="text-muted font-italic">- Belum dipilih -</span>');
            $(`#bankAccId_${idx}`).val('');
        }
    });

    $(document).on('click', '.btn-remove-row', function() {
        if ($('.peserta-row').length <= 1) {
            Swal.fire('Perhatian', 'Minimal harus ada 1 orang peserta perjalanan dinas.', 'warning');
            return;
        }
        let idx = $(this).data('idx');
        $(`#row_${idx}`).remove();
        recalculateTotal();
    });

    $('#btnAutoBku').click(function(e) {
        e.preventDefault();
        let generated = generateBkuRange($('#bkuNumber').val());
        $('#bkuNumber').val(generated).trigger('input');
    });

    $('#departureDate').on('change', function() {
        let bku = $('#bkuNumber').val().trim();
        let year = getDepartureYear();
        if (bku && /\/\s*\d{4}$/.test(bku)) {
            $('#bkuNumber').val(bku.replace(/\/\s*\d{4}$/, `/ ${year}`));
        }
        // Sinkronkan pilihan tahun anggaran jika belum memilih kegiatan spesifik
        if (!$('#bosKertasKerjaItemId').val() && $('#filterBudgetYear option[value="' + year + '"]').length > 0) {
            if ($('#filterBudgetYear').val() !== String(year)) {
                $('#filterBudgetYear').val(year).trigger('change');
            }
        }
    });

    $('#bkuNumber').on('blur', function() {
        let val = $(this).val().trim();
        if (val && !val.includes('/')) {
            let year = getDepartureYear();
            $(this).val(`${val} / ${year}`);
        }
    });

    $('#perjadinForm').on('submit', function(e) {
        let kkId = $('#bosKertasKerjaItemId').val();
        if (kkId) {
            let opt = $('#bosKertasKerjaItemId').find(':selected');
            let remaining = parseFloat(opt.data('remaining'));
            if (isNaN(remaining)) remaining = 0;
            let unit = opt.data('unit') || 'oh';
            let countPeserta = $('.peserta-row').length;

            if (remaining <= 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Sisa Alokasi Habis',
                    text: 'Kegiatan Kertas Kerja yang dipilih sudah habis alokasi volumenya (Sisa: 0 ' + unit + '). Silakan pilih kegiatan lain atau simpan tanpa menghubungkan ke Kertas Kerja.',
                });
                return false;
            }

            if (countPeserta > remaining) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Volume Melebihi Batas',
                    text: 'Jumlah peserta (' + countPeserta + ' orang) melebihi sisa volume alokasi Kertas Kerja (' + remaining + ' ' + unit + '). Silakan sesuaikan jumlah peserta atau pilih kegiatan lain.',
                });
                return false;
            }
        }
    });
});
</script>
@stop
