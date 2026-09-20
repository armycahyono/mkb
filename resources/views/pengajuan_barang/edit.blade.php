@extends('adminlte::page')

@section('title', 'Ubah Rencana Pengajuan Barang')

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap" style="padding: 2px 0;">
    <div>
        <h1 class="page-title"><i class="fas fa-edit text-warning mr-1"></i> Ubah Rencana Pengajuan Barang</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-house"></i> Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('pengajuan-barang.index') }}">Rencana Pengajuan Barang</a></li>
                <li class="breadcrumb-item active">Ubah</li>
            </ol>
        </nav>
    </div>
    <div class="header-actions">
        <a href="{{ route('pengajuan-barang.index') }}" class="btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left mr-1"></i> Kembali
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
    .form-section-separator {
        display: flex;
        align-items: center;
        margin: 1.1rem 0 0.75rem 0;
    }
    .form-section-separator .sep-label {
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        padding-right: 12px;
        white-space: nowrap;
    }
    .form-section-separator .sep-line {
        flex-grow: 1;
        height: 1px;
        background: #e2e8f0;
    }
</style>
@stop

@section('content')
<div class="container-fluid px-0">
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong><i class="fas fa-exclamation-triangle mr-1"></i> Terjadi kesalahan input:</strong>
            <ul class="mb-0 mt-1 pl-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-2 px-3 border-bottom">
            <h3 class="card-title font-weight-bold text-dark mb-0" style="font-size: 0.92rem;">
                <i class="fas fa-pencil-alt mr-1 text-warning"></i> Form Edit Rencana Pengajuan Barang
            </h3>
        </div>
        <form action="{{ route('pengajuan-barang.update', $pengajuanBarang) }}" method="POST" id="formEditPengajuan">
            @csrf
            @method('PUT')
            <div class="card-body">
                <div class="row">
                    <!-- SEPARATOR 1: TAHUN & PEMOHON -->
                    <div class="col-12">
                        <div class="form-section-separator mt-0">
                            <span class="sep-label text-primary pl-0"><i class="fas fa-calendar-alt mr-1"></i> 1. Tahun Anggaran & Pemohon</span>
                            <span class="sep-line"></span>
                        </div>
                    </div>

                    <div class="col-md-6 form-group">
                        <label for="tahun_anggaran">Tahun Anggaran <span class="text-danger">*</span></label>
                        <input type="number" name="tahun_anggaran" id="tahun_anggaran" 
                               class="form-control font-tabular @error('tahun_anggaran') is-invalid @enderror" 
                               value="{{ old('tahun_anggaran', $pengajuanBarang->tahun_anggaran) }}" required>
                        @error('tahun_anggaran')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6 form-group">
                        <label for="pemohon_id">Pemohon <span class="text-danger">*</span></label>
                        <select name="pemohon_id" id="pemohon_id" class="form-control @error('pemohon_id') is-invalid @enderror">
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('pemohon_id', $pengajuanBarang->pemohon_id) == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }} {{ $user->guru ? '('.$user->guru->nama_lengkap.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('pemohon_id')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- SEPARATOR 2: RINCIAN BARANG -->
                    <div class="col-12">
                        <div class="form-section-separator">
                            <span class="sep-label text-info pl-0"><i class="fas fa-box mr-1"></i> 2. Rincian & Identitas Barang Riil</span>
                            <span class="sep-line"></span>
                        </div>
                    </div>

                    <div class="col-md-6 form-group">
                        <label for="nama_barang">Nama Barang <span class="text-danger">*</span></label>
                        <input type="text" name="nama_barang" id="nama_barang" 
                               class="form-control @error('nama_barang') is-invalid @enderror" 
                               value="{{ old('nama_barang', $pengajuanBarang->nama_barang) }}" required>
                        @error('nama_barang')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6 form-group">
                        <label for="spesifikasi">Spesifikasi Detail <span class="text-danger">*</span></label>
                        <textarea name="spesifikasi" id="spesifikasi" rows="2" 
                                  class="form-control @error('spesifikasi') is-invalid @enderror" required>{{ old('spesifikasi', $pengajuanBarang->spesifikasi) }}</textarea>
                        @error('spesifikasi')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- SEPARATOR 3: VOLUME & BIAYA -->
                    <div class="col-12">
                        <div class="form-section-separator">
                            <span class="sep-label text-success pl-0"><i class="fas fa-money-bill-wave mr-1"></i> 3. Volume & Anggaran Biaya</span>
                            <span class="sep-line"></span>
                        </div>
                    </div>

                    <div class="col-md-3 form-group">
                        <label for="volume">Volume <span class="text-danger">*</span></label>
                        <input type="text" name="volume" id="volume" 
                               class="form-control font-tabular @error('volume') is-invalid @enderror" 
                               value="{{ old('volume', $pengajuanBarang->volume) }}" required>
                        @error('volume')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-3 form-group">
                        <label for="satuan">Satuan <span class="text-danger">*</span></label>
                        <input type="text" name="satuan" id="satuan" 
                               class="form-control @error('satuan') is-invalid @enderror" 
                               value="{{ old('satuan', $pengajuanBarang->satuan) }}" required>
                        @error('satuan')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-3 form-group">
                        <label for="harga">Harga Satuan (Rp) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text font-weight-bold" style="font-size: 0.85rem;">Rp</span>
                            </div>
                            <input type="text" name="harga" id="harga" 
                                   class="form-control text-right font-tabular thousand-separator-input @error('harga') is-invalid @enderror" 
                                   value="{{ old('harga', number_format((float) $pengajuanBarang->harga, 0, ',', '.')) }}" required>
                        </div>
                        @error('harga')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-3 form-group">
                        <label for="jumlah">Jumlah Total (Rp) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text font-weight-bold" style="font-size: 0.85rem;">Rp</span>
                            </div>
                            <input type="text" name="jumlah" id="jumlah" 
                                   class="form-control text-right font-tabular font-weight-bold text-primary @error('jumlah') is-invalid @enderror" 
                                   value="{{ old('jumlah', number_format((float) $pengajuanBarang->jumlah, 0, ',', '.')) }}" required readonly style="background-color: #f8fafc;">
                        </div>
                        <small class="form-text text-muted">Dihitung otomatis (Volume &times; Harga).</small>
                        @error('jumlah')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- SEPARATOR 4: KEGIATAN & WAKTU PELAKSANAAN -->
                    <div class="col-12">
                        <div class="form-section-separator">
                            <span class="sep-label text-purple pl-0" style="color: #7c3aed;"><i class="fas fa-tasks mr-1"></i> 4. Rencana Kegiatan & Bulan Alokasi</span>
                            <span class="sep-line"></span>
                        </div>
                    </div>

                    <div class="col-md-6 form-group">
                        <label for="kegiatan">Kegiatan <span class="text-danger">*</span></label>
                        <input type="text" name="kegiatan" id="kegiatan" 
                               class="form-control @error('kegiatan') is-invalid @enderror" 
                               value="{{ old('kegiatan', $pengajuanBarang->kegiatan) }}" required>
                        @error('kegiatan')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6 form-group">
                        <label for="bulan_pakai">Bulan Pakai <span class="text-danger">*</span></label>
                        <select name="bulan_pakai" id="bulan_pakai" class="form-control @error('bulan_pakai') is-invalid @enderror" required>
                            @php
                                $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                            @endphp
                            @foreach($months as $m)
                                <option value="{{ $m }}" {{ old('bulan_pakai', $pengajuanBarang->bulan_pakai) == $m ? 'selected' : '' }}>{{ $m }}</option>
                            @endforeach
                        </select>
                        @error('bulan_pakai')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- SEPARATOR 5: REALISASI & KETERANGAN -->
                    <div class="col-12">
                        <div class="form-section-separator">
                            <span class="sep-label text-muted pl-0"><i class="fas fa-clipboard-check mr-1"></i> 5. Catatan & Realisasi Barang</span>
                            <span class="sep-line"></span>
                        </div>
                    </div>

                    <div class="col-md-6 form-group">
                        <label for="realisasi">Realisasi (Nama Barang Disetujui)</label>
                        <input type="text" name="realisasi" id="realisasi" 
                               class="form-control @error('realisasi') is-invalid @enderror" 
                               placeholder="Diisi oleh petugas" 
                               value="{{ old('realisasi', $pengajuanBarang->realisasi) }}"
                               @cannot('realisasi', $pengajuanBarang) readonly @endcannot>
                        <small class="form-text text-muted">Ditautkan dengan nama barang yang telah disetujui petugas.</small>
                        @error('realisasi')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6 form-group">
                        <label for="keterangan">Keterangan Tambahan</label>
                        <textarea name="keterangan" id="keterangan" rows="2" 
                                  class="form-control @error('keterangan') is-invalid @enderror" 
                                  placeholder="Catatan tambahan (opsional)">{{ old('keterangan', $pengajuanBarang->keterangan) }}</textarea>
                        @error('keterangan')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light d-flex justify-content-end">
                <a href="{{ route('pengajuan-barang.index') }}" class="btn btn-secondary mr-2">Batal</a>
                <button type="submit" class="btn btn-warning font-weight-bold"><i class="fas fa-save mr-1"></i> Perbarui Pengajuan</button>
            </div>
        </form>
    </div>
</div>
@stop

@section('js')
<script>
    function formatThousands(val) {
        if (!val && val !== 0) return '';
        let str = val.toString().replace(/[^0-9]/g, '');
        if (!str) return '';
        return parseInt(str, 10).toLocaleString('id-ID');
    }

    function parseThousands(val) {
        if (!val) return 0;
        let str = val.toString().replace(/[^0-9]/g, '');
        return str ? parseFloat(str) : 0;
    }

    function calculateTotal() {
        const volEl = document.getElementById('volume');
        const hargaEl = document.getElementById('harga');
        const jumlahEl = document.getElementById('jumlah');

        const volume = parseFloat(volEl ? volEl.value.replace(/[^0-9.]/g, '') : 0) || 0;
        const harga = parseThousands(hargaEl ? hargaEl.value : 0);
        const subtotal = Math.round(volume * harga);

        if (jumlahEl) {
            jumlahEl.value = formatThousands(subtotal);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const hargaInput = document.getElementById('harga');
        const volumeInput = document.getElementById('volume');
        const form = document.getElementById('formEditPengajuan');

        if (hargaInput) {
            hargaInput.addEventListener('input', function() {
                let originalValue = this.value;
                let originalSelection = this.selectionStart;
                let originalLength = originalValue.length;

                let rawVal = originalValue.replace(/[^0-9]/g, '');
                let formatted = rawVal ? parseInt(rawVal, 10).toLocaleString('id-ID') : '';
                this.value = formatted;

                let newLength = formatted.length;
                let newSelection = originalSelection + (newLength - originalLength);
                if (newSelection >= 0 && newSelection <= newLength) {
                    this.setSelectionRange(newSelection, newSelection);
                }
                calculateTotal();
            });
        }

        if (volumeInput) {
            volumeInput.addEventListener('input', calculateTotal);
        }

        if (form) {
            form.addEventListener('submit', function() {
                if (hargaInput) hargaInput.value = hargaInput.value.replace(/\./g, '');
                const jumlahInput = document.getElementById('jumlah');
                if (jumlahInput) jumlahInput.value = jumlahInput.value.replace(/\./g, '');
            });
        }

        calculateTotal();
    });
</script>
@stop
