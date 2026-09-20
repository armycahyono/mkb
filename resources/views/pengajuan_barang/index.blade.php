@extends('adminlte::page')

@section('title', 'Pengajuan Barang')

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap" style="padding: 2px 0;">
    <div>
        <h1 class="page-title"><i class="fas fa-boxes text-info mr-1"></i> Pengajuan Barang</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-house"></i> Dashboard</a></li>
                <li class="breadcrumb-item">Data Pokok</li>
                <li class="breadcrumb-item active">Pengajuan Barang</li>
            </ol>
        </nav>
    </div>
    <div class="header-actions">
        @can('create', App\Models\PengajuanBarang::class)
        <a href="{{ route('pengajuan-barang.create') }}" class="btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus mr-1"></i> Tambah Pengajuan
        </a>
        @endcan
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
    #pengajuanTable {
        font-size: 0.8125rem !important;
    }
    #pengajuanTable th {
        font-size: 0.74rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 0.5rem 0.5rem !important;
        background-color: #f8fafc;
        color: #475569;
        vertical-align: middle;
        text-align: center;
    }
    #pengajuanTable td {
        padding: 0.45rem 0.5rem !important;
        vertical-align: middle;
        line-height: 1.35;
    }
</style>
@stop

@section('content')
<div class="container-fluid px-0">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center flex-wrap" style="border-bottom: 1px solid #edf2f7; gap: 10px;">
            <h3 class="card-title font-weight-bold text-dark mb-0" style="font-size: 0.95rem;">
                <i class="fas fa-list mr-1 text-info"></i> Daftar Pengajuan Barang oleh Guru
            </h3>

            <!-- Filter Tahun dan Cari Barang Sejajar -->
            <form method="GET" action="{{ route('pengajuan-barang.index') }}" class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                <!-- Filter Tahun (Default Tahun Sekarang) -->
                <div class="d-inline-flex align-items-center">
                    <label for="filter_tahun" class="mb-0 mr-1 font-weight-bold text-secondary text-nowrap" style="font-size: 0.85rem;">
                        Tahun :
                    </label>
                    <select name="tahun_anggaran" id="filter_tahun" class="form-control form-control-sm font-weight-bold" onchange="this.form.submit()" style="width: 130px;">
                        <option value="all" {{ $selectedTahun === 'all' ? 'selected' : '' }}>Semua Tahun</option>
                        @foreach($tahunList as $tahun)
                            <option value="{{ $tahun }}" {{ (string)$selectedTahun === (string)$tahun ? 'selected' : '' }}>
                                {{ $tahun }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Cari Barang Sejajar -->
                <div class="d-inline-flex align-items-center">
                    <div class="input-group input-group-sm" style="min-width: 240px; max-width: 320px;">
                        <input type="text" name="search" class="form-control" placeholder="Cari barang / kegiatan / guru..." value="{{ request('search') }}">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit" title="Cari">
                                <i class="fas fa-search mr-1"></i> Cari
                            </button>
                            @if(request('search') || (request('tahun_anggaran') && request('tahun_anggaran') !== (string)date('Y')))
                                <a href="{{ route('pengajuan-barang.index') }}" class="btn btn-outline-secondary" title="Reset Filter">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0" id="pengajuanTable">
                    <thead>
                        <tr>
                            <th style="width: 40px;">NO</th>
                            <th>NAMA BARANG</th>
                            <th>SPESIFIKASI</th>
                            <th style="width: 70px;">VOLUME</th>
                            <th style="width: 80px;">SATUAN</th>
                            <th style="width: 110px;">HARGA</th>
                            <th style="width: 120px;">JUMLAH</th>
                            <th>KEGIATAN</th>
                            <th style="width: 100px;">BULAN PAKAI</th>
                            <th>PEMOHON</th>
                            <th>REALISASI</th>
                            <th>KETERANGAN</th>
                            <th style="width: 120px;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pengajuans as $index => $item)
                            <tr>
                                <td class="text-center font-tabular">{{ $pengajuans->firstItem() + $index }}</td>
                                <td class="font-weight-bold text-dark">{{ $item->nama_barang }}</td>
                                <td>{{ $item->spesifikasi }}</td>
                                <td class="text-center font-tabular">{{ number_format($item->volume, 0, ',', '.') }}</td>
                                <td class="text-center">{{ $item->satuan }}</td>
                                <td class="text-right font-tabular">Rp {{ number_format($item->harga, 0, ',', '.') }}</td>
                                <td class="text-right font-weight-bold font-tabular text-primary">
                                    Rp {{ number_format($item->jumlah ?: ($item->volume * $item->harga), 0, ',', '.') }}
                                </td>
                                <td>{{ $item->kegiatan }}</td>
                                <td class="text-center"><span class="badge badge-light border">{{ $item->bulan_pakai }}</span></td>
                                <td>
                                    <div class="font-weight-bold">{{ $item->guru?->full_name_with_title ?? $item->guru?->name ?? $item->pemohon?->name ?? '-' }}</div>
                                    @if($item->guru && $item->pemohon && $item->pemohon->name !== ($item->guru->full_name_with_title ?? $item->guru->name))
                                        <small class="text-muted"><i class="fas fa-user-circle mr-1"></i>{{ $item->pemohon->name }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($item->realisasi)
                                        <span class="badge badge-success px-2 py-1" style="font-size: 0.8rem;">
                                            <i class="fas fa-check-circle mr-1"></i> {{ $item->realisasi }}
                                        </span>
                                    @else
                                        <span class="badge badge-warning text-dark px-2 py-1" style="font-size: 0.8rem;">
                                            <i class="fas fa-clock mr-1"></i> Belum Realisasi
                                        </span>
                                    @endif

                                    @can('realisasi', $item)
                                        <button type="button" class="btn btn-xs btn-outline-info ml-1 mt-1 d-block" 
                                                data-toggle="modal" 
                                                data-target="#modalRealisasi{{ $item->id }}">
                                            <i class="fas fa-edit mr-1"></i> Set Realisasi
                                        </button>
                                    @endcan
                                </td>
                                <td><small class="text-muted">{{ $item->keterangan ?? '-' }}</small></td>
                                <td class="text-center text-nowrap">
                                    @can('update', $item)
                                        <a href="{{ route('pengajuan-barang.edit', $item) }}" class="btn btn-xs btn-warning mr-1 shadow-sm" title="Ubah Pengajuan">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endcan

                                    @can('delete', $item)
                                        <form action="{{ route('pengajuan-barang.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus rencana pengajuan barang ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-danger shadow-sm" title="Hapus Pengajuan">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>

                            @can('realisasi', $item)
                            <!-- Modal Realisasi -->
                            <div class="modal fade" id="modalRealisasi{{ $item->id }}" tabindex="-1" role="dialog" aria-labelledby="modalLabel{{ $item->id }}" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <form action="{{ route('pengajuan-barang.realisasi', $item) }}" method="POST">
                                            @csrf
                                            <div class="modal-header bg-info text-white">
                                                <h5 class="modal-title" id="modalLabel{{ $item->id }}" style="font-size: 1rem;">
                                                    <i class="fas fa-check-double mr-1"></i> Realisasi Pengajuan Barang
                                                </h5>
                                                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="alert alert-light border">
                                                    <strong>Barang Diajukan:</strong> {{ $item->nama_barang }} ({{ $item->spesifikasi }})<br>
                                                    <strong>Volume:</strong> {{ $item->volume }} {{ $item->satuan }} | <strong>Estimasi:</strong> Rp {{ number_format($item->jumlah, 0, ',', '.') }}<br>
                                                    <strong>Pemohon:</strong> {{ $item->pemohon?->name }}
                                                </div>
                                                <div class="form-group">
                                                    <label for="realisasiInput{{ $item->id }}">Nama Barang yang Telah Disetujui / Keterangan Realisasi <span class="text-danger">*</span></label>
                                                    <input type="text" name="realisasi" id="realisasiInput{{ $item->id }}" 
                                                           class="form-control" 
                                                           placeholder="Contoh: Disetujui (sesuai barang belanja BOS)" 
                                                           value="{{ old('realisasi', $item->realisasi) }}" required>
                                                    <small class="form-text text-muted">Ditautkan dengan nama barang yang telah disetujui oleh petugas.</small>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-info btn-sm"><i class="fas fa-save mr-1"></i> Simpan Realisasi</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endcan
                        @empty
                            <tr>
                                <td colspan="13" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block text-gray-400"></i>
                                    Belum ada data rencana pengajuan barang.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($pengajuans->hasPages())
            <div class="card-footer bg-white py-2 px-3 d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Menampilkan {{ $pengajuans->firstItem() }} - {{ $pengajuans->lastItem() }} dari {{ $pengajuans->total() }} data
                </div>
                <div>
                    {{ $pengajuans->links() }}
                </div>
            </div>
        @endif
    </div>
</div>
@stop
