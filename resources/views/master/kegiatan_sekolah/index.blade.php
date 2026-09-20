@extends('adminlte::page')

@section('title', 'Master Kegiatan Sekolah')

@section('plugins.Sweetalert2', true)
@section('plugins.Select2', true)

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap" style="padding: 2px 0;">
    <div>
        <h1 class="page-title">
            <i class="fas fa-bookmark text-indigo mr-1"></i> Master Program & Kegiatan Sekolah
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-house"></i> Dashboard</a></li>
                <li class="breadcrumb-item">Data Pokok</li>
                <li class="breadcrumb-item active">Kegiatan Sekolah</li>
            </ol>
        </nav>
    </div>
    <div class="header-actions d-flex align-items-center flex-wrap mt-2 mt-md-0" style="gap: 8px;">
        <a href="{{ route('bos.rkas.index') }}" class="btn btn-outline-primary btn-sm shadow-sm font-weight-bold">
            <i class="fas fa-calculator mr-1"></i> Buka Rencana RKAS
        </a>
        <button type="button" class="btn btn-primary btn-sm shadow-sm font-weight-bold" 
                data-toggle="modal" data-target="#modalCreateKegiatan" 
                data-bs-toggle="modal" data-bs-target="#modalCreateKegiatan">
            <i class="fas fa-plus mr-1"></i> Tambah Program Sekolah
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
        font-size: 1.18rem !important;
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
    .stat-card {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        padding: 14px 16px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        transition: transform 0.15s ease;
    }
    .stat-card:hover {
        transform: translateY(-2px);
    }
    .table-ks th {
        font-size: 0.74rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        vertical-align: middle;
        background-color: #f8fafc;
        color: #475569;
        border-bottom: 2px solid #e2e8f0;
    }
    .table-ks td {
        font-size: 0.84rem;
        vertical-align: middle;
    }
    .color-preview-circle {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: inline-block;
        border: 1px solid rgba(0,0,0,0.15);
        vertical-align: middle;
    }
    .swatch-btn {
        width: 26px;
        height: 26px;
        border-radius: 6px;
        border: 2px solid transparent;
        cursor: pointer;
        padding: 0;
        transition: transform 0.1s ease;
    }
    .swatch-btn:hover {
        transform: scale(1.15);
    }
    .swatch-btn.active {
        border-color: #0f172a;
        box-shadow: 0 0 0 2px rgba(15, 23, 42, 0.25);
    }
    .autocomplete-wrapper {
        position: relative;
    }
    .autocomplete-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.15);
        z-index: 1060;
        max-height: 250px;
        overflow-y: auto;
        margin-top: 4px;
        display: none;
    }
    .autocomplete-dropdown.show {
        display: block;
    }
    .autocomplete-item {
        padding: 8px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.8rem;
        transition: background-color 0.1s ease;
    }
    .autocomplete-item:hover {
        background-color: #f8fafc;
    }
    .autocomplete-item:last-child {
        border-bottom: none;
    }
    .badge-arkas-ref {
        background-color: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        font-size: 0.70rem;
        font-family: monospace;
    }
</style>
@stop

@section('content')
<div class="container-fluid px-0">
    {{-- ALERT NOTIFIKASI --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
            <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    {{-- STAT CARDS --}}
    <div class="row mb-3">
        <div class="col-6 col-md-3 mb-2 mb-md-0">
            <div class="stat-card">
                <div class="text-xs text-muted font-weight-bold text-uppercase">Total Program Sekolah</div>
                <div class="d-flex align-items-baseline mt-1">
                    <span class="font-weight-bold text-dark font-tabular" style="font-size: 1.45rem;">
                        {{ $kegiatans->count() }}
                    </span>
                    <span class="text-muted ml-2 small">Kegiatan</span>
                </div>
                <div class="text-xs text-muted mt-1">Unit / Ekstrakurikuler / Divisi</div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-2 mb-md-0">
            <div class="stat-card">
                <div class="text-xs text-success font-weight-bold text-uppercase">Status Aktif</div>
                <div class="d-flex align-items-baseline mt-1">
                    <span class="font-weight-bold text-success font-tabular" style="font-size: 1.45rem;">
                        {{ $kegiatans->where('is_active', true)->count() }}
                    </span>
                    <span class="text-muted ml-2 small">Aktif di Form</span>
                </div>
                <div class="text-xs text-muted mt-1">Siap dipilih di RKAS</div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-2 mb-md-0">
            <div class="stat-card">
                <div class="text-xs text-primary font-weight-bold text-uppercase">Terhubung ke ARKAS</div>
                <div class="d-flex align-items-baseline mt-1">
                    <span class="font-weight-bold text-primary font-tabular" style="font-size: 1.45rem;">
                        {{ $kegiatans->whereNotNull('arkas_kegiatan_kode')->count() }}
                    </span>
                    <span class="text-muted ml-2 small">Terpetakan</span>
                </div>
                <div class="text-xs text-muted mt-1">Otomatis isi kode ARKAS</div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-2 mb-md-0">
            <div class="stat-card">
                <div class="text-xs text-info font-weight-bold text-uppercase">Total Belanja Terkait</div>
                <div class="d-flex align-items-baseline mt-1">
                    <span class="font-weight-bold text-info font-tabular" style="font-size: 1.45rem;">
                        {{ $kegiatans->sum('pengajuan_barangs_count') }}
                    </span>
                    <span class="text-muted ml-2 small">Item Rencana RKAS</span>
                </div>
                <div class="text-xs text-muted mt-1">Pengajuan barang sekolah</div>
            </div>
        </div>
    </div>

    {{-- CARD TABEL PROGRAM SEKOLAH --}}
    <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h6 class="mb-0 font-weight-bold text-dark">
                    <i class="fas fa-list text-primary mr-1"></i> Daftar Program / Kegiatan Internal Sekolah
                </h6>
                <div class="text-muted small mt-1">
                    Program sekolah mempermudah identifikasi belanja konkret (seperti Pramuka, OSIS, TU, PKL) yang secara otomatis dipadankan ke kode kegiatan resmi ARKAS Kemendikbudristek.
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-ks mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 50px;">No</th>
                            <th style="min-width: 180px;">Program / Kegiatan Sekolah</th>
                            <th class="text-center" style="width: 110px;">Kode Singkat</th>
                            <th style="min-width: 280px;">Rujukan Otomatis ARKAS</th>
                            <th class="text-center" style="width: 130px;">Item Belanja</th>
                            <th class="text-center" style="width: 110px;">Status</th>
                            <th class="text-center" style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kegiatans as $idx => $k)
                            <tr>
                                <td class="text-center font-tabular text-muted">{{ $idx + 1 }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="color-preview-circle mr-2 shadow-xs" style="background-color: {{ $k->color }};"></span>
                                        <div>
                                            <span class="badge text-white px-2 py-1 font-weight-bold shadow-xs" 
                                                  style="background-color: {{ $k->color }}; font-size: 0.82rem; letter-spacing: 0.2px;">
                                                <i class="fas fa-bookmark mr-1 opacity-75"></i>{{ $k->nama }}
                                            </span>
                                            @if($k->keterangan)
                                                <div class="text-xs text-muted mt-1">{{ $k->keterangan }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-light border font-tabular font-weight-bold px-2 py-1" style="font-size: 0.78rem;">
                                        {{ $k->kode ?: '-' }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $arkasRef = $k->arkasKegiatan;
                                        if (!$arkasRef && $k->arkas_kegiatan_kode) {
                                            $arkasRef = \App\Models\ArkasKegiatan::where('kode_kegiatan', $k->arkas_kegiatan_kode)
                                                ->orWhere('kegiatan', 'LIKE', "%{$k->arkas_kegiatan_kode}%")
                                                ->first();
                                        }
                                    @endphp
                                    @if($arkasRef)
                                        <div class="font-weight-600 text-dark" style="font-size: 0.82rem;">
                                            <span class="badge badge-primary mr-1" style="font-size: 0.72rem;">{{ $arkasRef->kode_kegiatan }}</span>
                                            {{ $arkasRef->kegiatan }}
                                        </div>
                                        @if($arkasRef->komponen)
                                            <div class="text-xs text-muted mt-0.5">
                                                <i class="fas fa-layer-group text-secondary mr-1"></i>{{ $arkasRef->komponen }}
                                            </div>
                                        @endif
                                    @elseif($k->arkas_kegiatan_kode)
                                        <div class="font-weight-600 text-dark" style="font-size: 0.82rem;">
                                            <span class="badge badge-secondary mr-1" style="font-size: 0.70rem;">Manual</span>
                                            {{ $k->arkas_kegiatan_kode }}
                                        </div>
                                    @else
                                        <span class="text-muted small font-italic">-- Belum Dipetakan --</span>
                                    @endif
                                </td>
                                <td class="text-center font-tabular">
                                    @if($k->pengajuan_barangs_count > 0)
                                        <a href="{{ route('bos.rkas.index', ['kegiatan_sekolah_id' => $k->id]) }}" 
                                           class="badge badge-info px-2 py-1 font-weight-bold" 
                                           title="Lihat item belanja di Rencana RKAS">
                                            <i class="fas fa-boxes mr-1"></i> {{ $k->pengajuan_barangs_count }} item
                                        </a>
                                    @else
                                        <span class="badge badge-light text-muted border px-2 py-1">0 item</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <form method="POST" action="{{ route('kegiatan-sekolah.toggle-status', $k->id) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-xs {{ $k->is_active ? 'btn-success' : 'btn-secondary' }} px-2 py-1 font-weight-bold rounded-pill"
                                                title="Klik untuk mengubah status aktif">
                                            <i class="fas {{ $k->is_active ? 'fa-check-circle' : 'fa-times-circle' }} mr-1"></i>
                                            {{ $k->is_active ? 'Aktif' : 'Non-Aktif' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-default text-primary btn-edit-ks" 
                                                title="Ubah Program"
                                                data-id="{{ $k->id }}"
                                                data-nama="{{ e($k->nama) }}"
                                                data-kode="{{ e($k->kode) }}"
                                                data-arkas-kode="{{ $k->arkas_kegiatan_kode }}"
                                                data-arkas-text="{{ e($k->full_arkas_kegiatan) }}"
                                                data-color="{{ $k->color }}"
                                                data-urutan="{{ $k->urutan }}"
                                                data-keterangan="{{ e($k->keterangan) }}">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <form method="POST" action="{{ route('kegiatan-sekolah.destroy', $k->id) }}" 
                                              class="d-inline"
                                              onsubmit="return confirm('Apakah Anda yakin ingin menghapus program \'{{ $k->nama }}\'?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-default text-danger" title="Hapus Program">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="fas fa-bookmark fa-2x mb-2 text-muted opacity-50 d-block"></i>
                                    Belum ada data kegiatan sekolah. Klik tombol <strong>Tambah Program Sekolah</strong> di atas untuk menambahkan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- MODAL TAMBAH PROGRAM SEKOLAH --}}
<div class="modal fade" id="modalCreateKegiatan" tabindex="-1" role="dialog" aria-labelledby="modalCreateKegiatanLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form method="POST" action="{{ route('kegiatan-sekolah.store') }}" class="modal-content border-0 shadow">
            @csrf
            <div class="modal-header bg-dark text-white py-2.5 px-3">
                <h5 class="modal-title font-weight-bold" id="modalCreateKegiatanLabel" style="font-size: 1.05rem;">
                    <i class="fas fa-plus-circle text-primary mr-1"></i> Tambah Program / Kegiatan Sekolah
                </h5>
                <button type="button" class="btn-close text-white close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3.5">
                <div class="form-group mb-2.5">
                    <label class="small font-weight-bold text-dark mb-1">
                        Nama Program / Kegiatan Sekolah: <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="nama" class="form-control form-control-sm" 
                           placeholder="Contoh: Pramuka, OSIS, Paskibra, Robotik, dll..." required>
                </div>

                <div class="row">
                    <div class="col-6 mb-2.5">
                        <label class="small font-weight-bold text-dark mb-1">
                            Kode Singkat (Opsional):
                        </label>
                        <input type="text" name="kode" class="form-control form-control-sm" 
                               placeholder="Contoh: PRA, OSIS, ROB">
                    </div>
                    <div class="col-6 mb-2.5">
                        <label class="small font-weight-bold text-dark mb-1">
                            Urutan Tampil:
                        </label>
                        <input type="number" name="urutan" class="form-control form-control-sm" 
                               value="{{ ($kegiatans->max('urutan') ?? 0) + 1 }}" min="1">
                    </div>
                </div>

                <div class="form-group mb-2.5">
                    <label class="small font-weight-bold text-dark mb-1">
                        Warna Badge & Preset:
                    </label>
                    <div class="d-flex align-items-center mb-2" style="gap: 8px;">
                        <input type="color" name="color" id="createColorInput" value="#4f46e5" 
                               class="form-control form-control-color p-0 border-0 rounded" style="width: 42px; height: 32px; cursor: pointer;">
                        <span class="text-muted small font-tabular" id="createColorHex">#4f46e5</span>
                    </div>
                    <div class="d-flex flex-wrap align-items-center" style="gap: 6px;">
                        @php
                            $colorPresets = ['#16a34a', '#4f46e5', '#2563eb', '#0891b2', '#0d9488', '#d97706', '#dc2626', '#9333ea', '#db2777', '#475569'];
                        @endphp
                        @foreach($colorPresets as $cp)
                            <button type="button" class="swatch-btn" style="background-color: {{ $cp }};" 
                                    onclick="setColorPreset('create', '{{ $cp }}')"></button>
                        @endforeach
                    </div>
                </div>

                <div class="form-group mb-2.5 autocomplete-wrapper">
                    <label class="small font-weight-bold text-dark d-flex justify-content-between align-items-center mb-1">
                        <span><i class="fas fa-link text-primary mr-1"></i> Pemetaan Otomatis ke Kegiatan ARKAS:</span>
                        <span class="badge badge-light border text-muted" id="createArkasBadge" style="font-size: 0.68rem; display: none;">
                            <i class="fas fa-check-circle text-success mr-1"></i><span id="createArkasBadgeText">Terpetakan</span>
                        </span>
                    </label>
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white border-right-0 text-muted"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" id="createArkasSearchInput" class="form-control form-control-sm border-left-0" 
                               placeholder="Ketik min. 2 huruf (contoh: 03.03.06 atau perpustakaan)..." 
                               autocomplete="off">
                        <input type="hidden" name="arkas_kegiatan_kode" id="createArkasKode">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCreateClearArkas" title="Kosongkan pemetaan" style="display: none;">
                                <i class="fas fa-times"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCreateToggleArkas" title="Tampilkan semua kegiatan ARKAS">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>
                    </div>
                    <div id="dropdownCreateArkas" class="autocomplete-dropdown"></div>
                    <small class="text-muted mt-1 d-block" style="font-size: 0.72rem;">
                        <i class="fas fa-info-circle text-secondary mr-1"></i> Anda bisa <strong>menulis bebas</strong> atau <strong>memilih dari saran</strong> kegiatan ARKAS. Ketika program ini dipilih di RKAS, rujukan ARKAS akan terisi otomatis.
                    </small>
                </div>

                <div class="form-group mb-1">
                    <label class="small font-weight-bold text-dark mb-1">
                        Keterangan Tambahan (Opsional):
                    </label>
                    <textarea name="keterangan" rows="2" class="form-control form-control-sm" 
                              placeholder="Catatan ruang lingkup atau penanggung jawab program..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm font-weight-bold px-3">
                    <i class="fas fa-save mr-1"></i> Simpan Program
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL UBAH PROGRAM SEKOLAH --}}
<div class="modal fade" id="modalEditKegiatan" tabindex="-1" role="dialog" aria-labelledby="modalEditKegiatanLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form method="POST" action="" id="formEditKegiatan" class="modal-content border-0 shadow">
            @csrf
            @method('PUT')
            <div class="modal-header bg-dark text-white py-2.5 px-3">
                <h5 class="modal-title font-weight-bold" id="modalEditKegiatanLabel" style="font-size: 1.05rem;">
                    <i class="fas fa-edit text-primary mr-1"></i> Ubah Program / Kegiatan Sekolah
                </h5>
                <button type="button" class="btn-close text-white close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3.5">
                <div class="form-group mb-2.5">
                    <label class="small font-weight-bold text-dark mb-1">
                        Nama Program / Kegiatan Sekolah: <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="nama" id="editNamaInput" class="form-control form-control-sm" required>
                </div>

                <div class="row">
                    <div class="col-6 mb-2.5">
                        <label class="small font-weight-bold text-dark mb-1">
                            Kode Singkat (Opsional):
                        </label>
                        <input type="text" name="kode" id="editKodeInput" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 mb-2.5">
                        <label class="small font-weight-bold text-dark mb-1">
                            Urutan Tampil:
                        </label>
                        <input type="number" name="urutan" id="editUrutanInput" class="form-control form-control-sm" min="1">
                    </div>
                </div>

                <div class="form-group mb-2.5">
                    <label class="small font-weight-bold text-dark mb-1">
                        Warna Badge & Preset:
                    </label>
                    <div class="d-flex align-items-center mb-2" style="gap: 8px;">
                        <input type="color" name="color" id="editColorInput" 
                               class="form-control form-control-color p-0 border-0 rounded" style="width: 42px; height: 32px; cursor: pointer;">
                        <span class="text-muted small font-tabular" id="editColorHex"></span>
                    </div>
                    <div class="d-flex flex-wrap align-items-center" style="gap: 6px;">
                        @foreach($colorPresets as $cp)
                            <button type="button" class="swatch-btn" style="background-color: {{ $cp }};" 
                                    onclick="setColorPreset('edit', '{{ $cp }}')"></button>
                        @endforeach
                    </div>
                </div>

                <div class="form-group mb-2.5 autocomplete-wrapper">
                    <label class="small font-weight-bold text-dark d-flex justify-content-between align-items-center mb-1">
                        <span><i class="fas fa-link text-primary mr-1"></i> Pemetaan Otomatis ke Kegiatan ARKAS:</span>
                        <span class="badge badge-light border text-muted" id="editArkasBadge" style="font-size: 0.68rem; display: none;">
                            <i class="fas fa-check-circle text-success mr-1"></i><span id="editArkasBadgeText">Terpetakan</span>
                        </span>
                    </label>
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white border-right-0 text-muted"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" id="editArkasSearchInput" class="form-control form-control-sm border-left-0" 
                               placeholder="Ketik min. 2 huruf (contoh: 03.03.06 atau perpustakaan)..." 
                               autocomplete="off">
                        <input type="hidden" name="arkas_kegiatan_kode" id="editArkasKode">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnEditClearArkas" title="Kosongkan pemetaan" style="display: none;">
                                <i class="fas fa-times"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnEditToggleArkas" title="Tampilkan semua kegiatan ARKAS">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>
                    </div>
                    <div id="dropdownEditArkas" class="autocomplete-dropdown"></div>
                    <small class="text-muted mt-1 d-block" style="font-size: 0.72rem;">
                        <i class="fas fa-info-circle text-secondary mr-1"></i> Anda bisa <strong>menulis bebas</strong> atau <strong>memilih dari saran</strong> kegiatan ARKAS.
                    </small>
                </div>

                <div class="form-group mb-1">
                    <label class="small font-weight-bold text-dark mb-1">
                        Keterangan Tambahan (Opsional):
                    </label>
                    <textarea name="keterangan" id="editKeteranganInput" rows="2" class="form-control form-control-sm"></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm font-weight-bold px-3">
                    <i class="fas fa-save mr-1"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@stop

@section('js')
<script>
    const ARKAS_LIST = @json($arkasKegiatans);

    function setColorPreset(mode, color) {
        if (mode === 'create') {
            document.getElementById('createColorInput').value = color;
            document.getElementById('createColorHex').textContent = color;
        } else {
            document.getElementById('editColorInput').value = color;
            document.getElementById('editColorHex').textContent = color;
        }
    }

    function setupArkasAutocomplete(mode) {
        const isCreate = mode === 'create';
        const input = document.getElementById(isCreate ? 'createArkasSearchInput' : 'editArkasSearchInput');
        const hidden = document.getElementById(isCreate ? 'createArkasKode' : 'editArkasKode');
        const dropdown = document.getElementById(isCreate ? 'dropdownCreateArkas' : 'dropdownEditArkas');
        const badge = document.getElementById(isCreate ? 'createArkasBadge' : 'editArkasBadge');
        const badgeText = document.getElementById(isCreate ? 'createArkasBadgeText' : 'editArkasBadgeText');
        const btnClear = document.getElementById(isCreate ? 'btnCreateClearArkas' : 'btnEditClearArkas');
        const btnToggle = document.getElementById(isCreate ? 'btnCreateToggleArkas' : 'btnEditToggleArkas');

        if (!input || !dropdown || !hidden) return;

        function updateUIState(selectedKode, displayText) {
            if (displayText !== undefined) {
                input.value = displayText;
            }
            hidden.value = selectedKode || '';

            if (selectedKode) {
                if (badge && badgeText) {
                    badgeText.textContent = selectedKode;
                    badge.style.display = 'inline-flex';
                }
                if (btnClear) btnClear.style.display = 'inline-block';
            } else {
                if (badge) badge.style.display = 'none';
                if (btnClear) btnClear.style.display = 'none';
            }
        }

        function renderItems(items) {
            if (!items || items.length === 0) {
                dropdown.innerHTML = `
                    <div class="p-2.5 text-muted small d-flex justify-content-between align-items-center" style="cursor: pointer;" onclick="this.closest('.autocomplete-dropdown').classList.remove('show')">
                        <div>
                            <i class="fas fa-info-circle mr-1 text-secondary"></i>
                            Tidak ada di katalog resmi ARKAS. Anda dapat tetap menggunakan isian manual ini.
                        </div>
                        <span class="badge badge-light border text-muted ml-2 flex-shrink-0" style="font-size: 0.68rem; padding: 2px 6px;">Tutup &times;</span>
                    </div>
                `;
            } else {
                let html = items.map(item => `
                    <div class="autocomplete-item select-arkas-item" 
                         data-kode="${item.kode_kegiatan}" 
                         data-text="${item.kode_kegiatan} - ${item.kegiatan}">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="font-weight-bold text-dark" style="font-size: 0.8rem;">
                                <span class="badge badge-arkas-ref mr-1">${item.kode_kegiatan}</span>
                                ${item.kegiatan}
                            </div>
                        </div>
                        ${item.komponen ? `<div class="text-muted mt-0.5" style="font-size: 0.72rem;"><i class="fas fa-layer-group mr-1 text-secondary"></i>${item.komponen}</div>` : ''}
                    </div>
                `).join('');

                dropdown.innerHTML = html;

                dropdown.querySelectorAll('.select-arkas-item').forEach(el => {
                    el.addEventListener('click', function() {
                        const kode = this.dataset.kode;
                        const text = this.dataset.text;
                        updateUIState(kode, text);
                        dropdown.classList.remove('show');
                    });
                });
            }
            dropdown.classList.add('show');
        }

        function search(query) {
            const q = query.trim().toLowerCase();
            if (q.length === 0) {
                dropdown.classList.remove('show');
                return;
            }
            const filtered = ARKAS_LIST.filter(item => {
                const kKode = (item.kode_kegiatan || '').toLowerCase();
                const kNama = (item.kegiatan || '').toLowerCase();
                const kKomp = (item.komponen || '').toLowerCase();
                return kKode.includes(q) || kNama.includes(q) || kKomp.includes(q);
            });
            renderItems(filtered);
        }

        input.addEventListener('input', function() {
            const val = this.value;
            if (val.trim() === '') {
                updateUIState('', '');
                dropdown.classList.remove('show');
            } else {
                const match = val.match(/^([0-9]{2}\.[0-9]{2}\.[0-9]{2})/);
                hidden.value = match ? match[1] : val.trim();
                if (btnClear) btnClear.style.display = 'inline-block';
                if (match && badge && badgeText) {
                    badgeText.textContent = match[1];
                    badge.style.display = 'inline-flex';
                } else if (badge) {
                    badge.style.display = 'none';
                }
                search(val);
            }
        });

        if (btnClear) {
            btnClear.addEventListener('click', function() {
                updateUIState('', '');
                dropdown.classList.remove('show');
                input.focus();
            });
        }

        if (btnToggle) {
            btnToggle.addEventListener('click', function() {
                if (dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                } else {
                    const q = input.value.trim().toLowerCase();
                    if (q.length > 0) {
                        search(q);
                    } else {
                        renderItems(ARKAS_LIST);
                    }
                }
            });
        }

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                dropdown.classList.remove('show');
            } else if (e.key === 'Enter') {
                const firstItem = dropdown.querySelector('.select-arkas-item');
                if (dropdown.classList.contains('show') && firstItem) {
                    e.preventDefault();
                    const kode = firstItem.dataset.kode;
                    const text = firstItem.dataset.text;
                    updateUIState(kode, text);
                    dropdown.classList.remove('show');
                }
            }
        });

        // Auto-resolve saat field kehilangan fokus jika teks cocok dengan kegiatan ARKAS
        input.addEventListener('blur', function() {
            setTimeout(() => {
                const val = input.value.trim();
                if (!val) {
                    updateUIState('', '');
                    return;
                }
                // Jika sudah berformat kode (misal: "03.03.06 - ...")
                const match = val.match(/^([0-9]{2}\.[0-9]{2}\.[0-9]{2})/);
                if (match) {
                    const found = ARKAS_LIST.find(i => i.kode_kegiatan === match[1]);
                    if (found) {
                        updateUIState(found.kode_kegiatan, `${found.kode_kegiatan} - ${found.kegiatan}`);
                    }
                } else {
                    // Cari kecocokan kata kunci di daftar ARKAS
                    const q = val.toLowerCase();
                    const found = ARKAS_LIST.find(i => 
                        (i.kegiatan && i.kegiatan.toLowerCase().includes(q)) ||
                        (i.kode_kegiatan && i.kode_kegiatan.toLowerCase().includes(q)) ||
                        (i.komponen && i.komponen.toLowerCase().includes(q))
                    );
                    if (found) {
                        updateUIState(found.kode_kegiatan, `${found.kode_kegiatan} - ${found.kegiatan}`);
                    }
                }
            }, 250);
        });
    }

    // Helper penutupan dropdown jika klik atau pindah fokus di luar wrapper masing-masing
    function closeArkasDropdowns(target) {
        ['create', 'edit'].forEach(mode => {
            const isCreate = mode === 'create';
            const input = document.getElementById(isCreate ? 'createArkasSearchInput' : 'editArkasSearchInput');
            const dropdown = document.getElementById(isCreate ? 'dropdownCreateArkas' : 'dropdownEditArkas');
            if (input && dropdown && dropdown.classList.contains('show')) {
                const wrapper = input.closest('.autocomplete-wrapper');
                if (!wrapper || (target && !wrapper.contains(target))) {
                    dropdown.classList.remove('show');
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Inisialisasi autocomplete ARKAS
        setupArkasAutocomplete('create');
        setupArkasAutocomplete('edit');

        // Close on outside click or focusin
        document.addEventListener('click', function(e) {
            closeArkasDropdowns(e.target);
        });
        document.addEventListener('focusin', function(e) {
            closeArkasDropdowns(e.target);
        });

        const createColor = document.getElementById('createColorInput');
        if (createColor) {
            createColor.addEventListener('input', function() {
                document.getElementById('createColorHex').textContent = this.value;
            });
        }

        const editColor = document.getElementById('editColorInput');
        if (editColor) {
            editColor.addEventListener('input', function() {
                document.getElementById('editColorHex').textContent = this.value;
            });
        }

        // Edit button handler
        document.querySelectorAll('.btn-edit-ks').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const nama = this.dataset.nama;
                const kode = this.dataset.kode;
                const arkasKode = this.dataset.arkasKode;
                const arkasText = this.dataset.arkasText;
                const color = this.dataset.color || '#4f46e5';
                const urutan = this.dataset.urutan;
                const keterangan = this.dataset.keterangan;

                const form = document.getElementById('formEditKegiatan');
                let url = '{{ route("kegiatan-sekolah.update", ":id") }}';
                form.action = url.replace(':id', id);

                document.getElementById('editNamaInput').value = nama;
                document.getElementById('editKodeInput').value = kode;
                document.getElementById('editUrutanInput').value = urutan;
                document.getElementById('editKeteranganInput').value = keterangan;

                document.getElementById('editColorInput').value = color;
                document.getElementById('editColorHex').textContent = color;

                // Set ARKAS mapping in edit modal
                const editArkasKode = document.getElementById('editArkasKode');
                const editArkasInput = document.getElementById('editArkasSearchInput');
                const editBadge = document.getElementById('editArkasBadge');
                const editBadgeText = document.getElementById('editArkasBadgeText');
                const btnEditClear = document.getElementById('btnEditClearArkas');

                if (editArkasKode && editArkasInput) {
                    editArkasKode.value = arkasKode || '';
                    editArkasInput.value = arkasText || arkasKode || '';
                    if (arkasKode) {
                        if (editBadge && editBadgeText) {
                            editBadgeText.textContent = arkasKode;
                            editBadge.style.display = 'inline-flex';
                        }
                        if (btnEditClear) btnEditClear.style.display = 'inline-block';
                    } else {
                        if (editBadge) editBadge.style.display = 'none';
                        if (btnEditClear) btnEditClear.style.display = 'none';
                    }
                }

                if (window.jQuery) {
                    $('#modalEditKegiatan').modal('show');
                } else {
                    const modalEl = document.getElementById('modalEditKegiatan');
                    if (modalEl) modalEl.classList.add('show');
                }
            });
        });
    });
</script>
@stop
