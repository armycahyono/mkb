@extends('adminlte::page')

@section('title', 'Detail Guru: ' . $guru->full_name_with_title)

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap" style="padding: 2px 0;">
    <div>
        <h1 class="page-title"><i class="fas fa-user-circle text-info mr-1"></i> Profil Guru & Pegawai</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-house"></i> Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('guru.index') }}">Data Guru & Pegawai</a></li>
                <li class="breadcrumb-item active">{{ $guru->full_name_with_title }}</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="{{ route('guru.edit', $guru->id) }}" class="btn btn-sm btn-warning mr-1">
            <i class="fas fa-edit mr-1"></i> Edit Profil
        </a>
        <a href="{{ route('guru.index') }}" class="btn btn-sm btn-secondary">
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
    .profile-card {
        background-color: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
    }
    .bank-card-item {
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        transition: all 0.2s;
    }
    .bank-card-item.is-primary {
        border-color: #3b82f6;
        background-color: #f0f7ff;
    }
</style>
@stop

@section('content')
<div class="row">
    {{-- Kolom Kiri: Profil Singkat & Biodata --}}
    <div class="col-lg-5">
        {{-- Header Card Profil --}}
        <div class="card shadow-sm border-0 profile-card mb-3">
            <div class="card-body p-3">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center mr-3 font-weight-bold shadow-sm" style="width: 52px; height: 52px; font-size: 1.25rem; background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;">
                        {{ strtoupper(substr($guru->name, 0, 1)) }}
                    </div>
                    <div>
                        <h5 class="font-weight-bold mb-0 text-dark" style="font-size: 1.05rem;">{{ $guru->full_name_with_title }}</h5>
                        <small class="text-muted font-tabular">
                            {{ $guru->nip ? 'NIP. ' . $guru->nip : 'Non-NIP' }}
                        </small>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-1 mt-2" style="gap: 6px;">
                    <span class="badge badge-light border text-dark font-weight-bold">{{ $guru->employment_status }}</span>
                    @if($guru->pangkat_golongan)
                        <span class="badge badge-info">{{ $guru->pangkat_golongan }}</span>
                    @endif
                    @if($guru->is_active)
                        <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Aktif Bekerja</span>
                    @else
                        <span class="badge badge-secondary">Non-Aktif</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Detail Identitas Lengkap --}}
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white py-2 px-3">
                <h6 class="font-weight-bold text-dark mb-0" style="font-size: 0.88rem;">
                    <i class="fas fa-id-card text-primary mr-1"></i> Data Identitas & Kepegawaian
                </h6>
            </div>
            <div class="card-body p-3 small">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td width="130" class="text-muted">NIK</td>
                        <td class="font-tabular font-weight-500">: {{ $guru->nik ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">NUPTK</td>
                        <td class="font-tabular font-weight-500">: {{ $guru->nuptk ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">NPWP</td>
                        <td class="font-tabular font-weight-500">: {{ $guru->npwp ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Jenis Kelamin</td>
                        <td>: {{ $guru->gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Tempat, Tgl Lahir</td>
                        <td>: {{ $guru->birth_place ?: '-' }}, {{ $guru->birth_date ? $guru->birth_date->translatedFormat('d F Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Agama</td>
                        <td>: {{ $guru->religion ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Telepon / WhatsApp</td>
                        <td class="font-tabular font-weight-500">: {{ $guru->phone ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Email</td>
                        <td>: {{ $guru->email ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Alamat Rumah</td>
                        <td>: {{ $guru->address ?: '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Daftar Rekening Bank --}}
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center">
                <h6 class="font-weight-bold text-dark mb-0" style="font-size: 0.88rem;">
                    <i class="fas fa-money-check-alt text-success mr-1"></i> Rekening Bank Pembayaran
                </h6>
                <a href="{{ route('guru.edit', $guru->id) }}" class="btn btn-xs btn-outline-primary" title="Edit atau tambah rekening">
                    <i class="fas fa-edit mr-1"></i> Kelola Rekening
                </a>
            </div>
            <div class="card-body p-3">
                @if($guru->bankAccounts->isEmpty())
                    <div class="text-center text-muted py-3 small">
                        <i class="fas fa-credit-card fa-2x mb-2 d-block text-secondary"></i>
                        Belum ada rekening bank yang didaftarkan.
                    </div>
                @else
                    @foreach($guru->bankAccounts as $acc)
                        <div class="bank-card-item p-2 mb-2 {{ $acc->is_primary ? 'is-primary' : 'bg-light' }}">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="font-weight-bold text-dark" style="font-size: 0.85rem;">{{ $acc->bank_name }}</span>
                                @if($acc->is_primary)
                                    <span class="badge badge-primary" style="font-size: 0.68rem;"><i class="fas fa-star mr-1"></i>Rekening Utama (Default)</span>
                                @else
                                    <span class="badge badge-light border text-muted" style="font-size: 0.68rem;">Rekening Lain</span>
                                @endif
                            </div>
                            <div class="font-tabular font-weight-bold text-dark" style="font-size: 0.92rem; letter-spacing: 0.5px;">
                                {{ $acc->account_number }}
                            </div>
                            <div class="small text-muted">
                                a.n. {{ $acc->account_holder }}
                                @if($acc->notes)
                                    &bull; <em>{{ $acc->notes }}</em>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    {{-- Kolom Kanan: Riwayat Jabatan & Penugasan Periodik --}}
    <div class="col-lg-7">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center">
                <h6 class="font-weight-bold text-dark mb-0" style="font-size: 0.88rem;">
                    <i class="fas fa-id-badge text-warning mr-1"></i> Riwayat Jabatan & Tugas Tambahan Sekolah
                </h6>
                <a href="{{ route('jabatan.penugasan') }}" class="btn btn-xs btn-outline-info">
                    <i class="fas fa-plus mr-1"></i> Tambah Penugasan Jabatan
                </a>
            </div>
            <div class="card-body p-2">
                @if($guru->jabatans->isEmpty())
                    <div class="text-center text-muted py-4 small">
                        <i class="fas fa-briefcase fa-2x mb-2 d-block text-secondary"></i>
                        Belum ada riwayat penugasan jabatan untuk guru ini.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-striped w-100 mb-0" style="font-size: 0.8rem;">
                            <thead>
                                <tr class="bg-light">
                                    <th>JABATAN</th>
                                    <th>NOMOR SK & TANGGAL</th>
                                    <th width="100" class="text-center">PERIODE</th>
                                    <th width="130">MASA JABATAN</th>
                                    <th width="90" class="text-center">STATUS</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($guru->jabatans as $gj)
                                    <tr>
                                        <td>
                                            <strong>{{ $gj->jabatan->name ?? '-' }}</strong>
                                            @if($gj->jabatan && $gj->jabatan->is_signatory)
                                                <span class="badge badge-primary ml-1" title="Penandatangan Dokumen Keuangan"><i class="fas fa-file-signature"></i></span>
                                            @endif
                                            @if($gj->notes)
                                                <small class="text-muted d-block">{{ $gj->notes }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="font-tabular font-weight-500">{{ $gj->sk_number ?: '-' }}</div>
                                            @if($gj->sk_date)
                                                <small class="text-muted">Tgl SK: {{ $gj->sk_date->format('d/m/Y') }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center font-tabular font-weight-bold">
                                            {{ $gj->period_year }}
                                        </td>
                                        <td class="font-tabular small">
                                            @if($gj->start_date || $gj->end_date)
                                                {{ $gj->start_date ? $gj->start_date->format('d/m/Y') : 'Awal' }}
                                                s/d
                                                {{ $gj->end_date ? $gj->end_date->format('d/m/Y') : 'Sekarang' }}
                                            @else
                                                <span class="text-muted">Sesuai SK Periode</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($gj->is_active)
                                                <span class="badge badge-success">Aktif Menjabat</span>
                                            @else
                                                <span class="badge badge-secondary">Demisioner</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@stop
