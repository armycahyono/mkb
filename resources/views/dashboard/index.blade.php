@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h1 class="mb-0">Dashboard</h1>
            <p class="mb-0 mt-1" style="font-size:.82rem;color:var(--mkb-text-muted)">
                Manajemen Keuangan Bantuan &mdash; Selamat datang, <strong>{{ Auth::user()->name ?? 'Pengguna' }}</strong>
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge rounded-pill" style="background:#EFF6FF;color:#3B82F6;font-size:.75rem;font-weight:600;padding:6px 12px;">
                <i class="fas fa-circle me-1" style="font-size:.45rem;vertical-align:middle;color:#22C55E"></i>
                Aktif
            </span>
            <span style="font-size:.78rem;color:var(--mkb-text-light)">
                {{ now()->translatedFormat('l, d F Y') }}
            </span>
        </div>
    </div>
@stop

@section('content')
<div class="px-2">

    {{-- ── Row 1: Stat summary cards ─────────────────────────────── --}}
    <div class="row g-3 mb-4">

        {{-- BOS --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="mkb-stat-card">
                <div class="mkb-stat-icon blue">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="mkb-stat-label">Dana BOS</div>
                    <div class="mkb-stat-value">BOSP</div>
                    <div class="mkb-stat-sub">Bantuan Operasional Satuan Pendidikan</div>
                </div>
                <span class="mkb-stat-badge active">Aktif</span>
            </div>
        </div>

        {{-- BPOPP --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="mkb-stat-card" style="opacity:.7">
                <div class="mkb-stat-icon muted">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="mkb-stat-label">Dana BPOPP</div>
                    <div class="mkb-stat-value" style="font-size:1.1rem;color:var(--mkb-text-muted)">BPOPP</div>
                    <div class="mkb-stat-sub">Bantuan Pemerintah Operasional</div>
                </div>
                <span class="mkb-stat-badge soon">Segera</span>
            </div>
        </div>

        {{-- Komite --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="mkb-stat-card" style="opacity:.7">
                <div class="mkb-stat-icon muted">
                    <i class="fas fa-users"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="mkb-stat-label">Dana Komite</div>
                    <div class="mkb-stat-value" style="font-size:1.1rem;color:var(--mkb-text-muted)">Komite</div>
                    <div class="mkb-stat-sub">Dana Sumbangan Komite Sekolah</div>
                </div>
                <span class="mkb-stat-badge soon">Segera</span>
            </div>
        </div>

        {{-- Panitia --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="mkb-stat-card" style="opacity:.7">
                <div class="mkb-stat-icon muted">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="mkb-stat-label">Dana Panitia</div>
                    <div class="mkb-stat-value" style="font-size:1.1rem;color:var(--mkb-text-muted)">Panitia</div>
                    <div class="mkb-stat-sub">Pengelolaan Dana Kepanitiaan</div>
                </div>
                <span class="mkb-stat-badge soon">Segera</span>
            </div>
        </div>

    </div>{{-- end row 1 --}}

    {{-- ── Row 2: Module cards + Quick links ───────────────────────── --}}
    <div class="row g-3 mb-4">

        {{-- Module cards 3-col --}}
        <div class="col-12 col-lg-8">
            <div class="card h-100" style="border-radius:var(--mkb-radius)!important">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="card-title mb-0">Program Dana</h6>
                        <small style="color:var(--mkb-text-muted);font-size:.76rem">Modul pengelolaan keuangan sekolah</small>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">

                        {{-- BOSP Module --}}
                        <div class="col-12 col-sm-6 col-md-4">
                            <div class="mkb-module-card">
                                <div class="mkb-module-icon" style="background:#EFF6FF;color:#3B82F6">
                                    <i class="fas fa-wallet"></i>
                                </div>
                                <div class="mkb-module-title">BOSP</div>
                                <div class="mkb-module-desc">Bantuan Operasional Satuan Pendidikan — pengelolaan anggaran &amp; realisasi.</div>
                                <div class="mkb-module-actions">
                                    <a href="{{ route('bos.realisasi.index') }}" class="mkb-btn mkb-btn-primary">
                                        <i class="fas fa-receipt fa-xs"></i> Realisasi
                                    </a>
                                    <a href="{{ route('bos.data.index') }}" class="mkb-btn mkb-btn-outline">
                                        <i class="fas fa-table fa-xs"></i> Data
                                    </a>
                                </div>
                            </div>
                        </div>

                        {{-- BPOPP Module --}}
                        <div class="col-12 col-sm-6 col-md-4">
                            <div class="mkb-module-card disabled">
                                <div class="mkb-module-icon" style="background:#F8FAFC;color:#94A3B8">
                                    <i class="fas fa-coins"></i>
                                </div>
                                <div class="mkb-module-title">BPOPP</div>
                                <div class="mkb-module-desc">Bantuan Pemerintah Operasional Pemerintah Provinsi. Segera hadir.</div>
                                <div class="mkb-module-actions">
                                    <span class="mkb-btn mkb-btn-muted">
                                        <i class="fas fa-lock fa-xs"></i> Segera Hadir
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Komite Module --}}
                        <div class="col-12 col-sm-6 col-md-4">
                            <div class="mkb-module-card disabled">
                                <div class="mkb-module-icon" style="background:#F8FAFC;color:#94A3B8">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="mkb-module-title">Komite</div>
                                <div class="mkb-module-desc">Dana sumbangan komite sekolah, pencatatan &amp; pelaporan. Segera hadir.</div>
                                <div class="mkb-module-actions">
                                    <span class="mkb-btn mkb-btn-muted">
                                        <i class="fas fa-lock fa-xs"></i> Segera Hadir
                                    </span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        {{-- Quick links panel --}}
        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="card-title mb-0">Akses Cepat</h6>
                    <small style="color:var(--mkb-text-muted);font-size:.76rem">Menu BOSP yang sering digunakan</small>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex flex-column gap-1">
                        <a href="{{ route('bos.files.index') }}" class="mkb-quick-link">
                            <div class="ql-icon" style="background:#EFF6FF;color:#3B82F6">
                                <i class="fas fa-file-pdf fa-xs"></i>
                            </div>
                            <div>
                                <div style="font-weight:600;font-size:.84rem">File Kertas Kerja</div>
                                <div style="font-size:.73rem;color:var(--mkb-text-light)">Upload &amp; kelola berkas PDF</div>
                            </div>
                            <i class="fas fa-chevron-right ms-auto" style="font-size:.65rem;color:var(--mkb-text-light)"></i>
                        </a>
                        <a href="{{ route('bos.data.index') }}" class="mkb-quick-link">
                            <div class="ql-icon" style="background:#F0F9FF;color:#0EA5E9">
                                <i class="fas fa-table fa-xs"></i>
                            </div>
                            <div>
                                <div style="font-weight:600;font-size:.84rem">Data Kertas Kerja</div>
                                <div style="font-size:.73rem;color:var(--mkb-text-light)">Rincian anggaran per kegiatan</div>
                            </div>
                            <i class="fas fa-chevron-right ms-auto" style="font-size:.65rem;color:var(--mkb-text-light)"></i>
                        </a>
                        <a href="{{ route('bos.realisasi.index') }}" class="mkb-quick-link">
                            <div class="ql-icon" style="background:#F0FDF4;color:#22C55E">
                                <i class="fas fa-receipt fa-xs"></i>
                            </div>
                            <div>
                                <div style="font-weight:600;font-size:.84rem">Realisasi BOSP</div>
                                <div style="font-size:.73rem;color:var(--mkb-text-light)">Catat & pantau realisasi belanja</div>
                            </div>
                            <i class="fas fa-chevron-right ms-auto" style="font-size:.65rem;color:var(--mkb-text-light)"></i>
                        </a>
                        <a href="{{ route('bos.panduan.index') }}" class="mkb-quick-link">
                            <div class="ql-icon" style="background:#FFFBEB;color:#F59E0B">
                                <i class="fas fa-basket-shopping fa-xs"></i>
                            </div>
                            <div>
                                <div style="font-weight:600;font-size:.84rem">Panduan Belanja</div>
                                <div style="font-size:.73rem;color:var(--mkb-text-light)">Referensi ketentuan belanja BOSP</div>
                            </div>
                            <i class="fas fa-chevron-right ms-auto" style="font-size:.65rem;color:var(--mkb-text-light)"></i>
                        </a>
                        <a href="{{ route('school.index') }}" class="mkb-quick-link">
                            <div class="ql-icon" style="background:#FFF1F2;color:#F43F5E">
                                <i class="fas fa-school fa-xs"></i>
                            </div>
                            <div>
                                <div style="font-weight:600;font-size:.84rem">Profil Sekolah</div>
                                <div style="font-size:.73rem;color:var(--mkb-text-light)">Informasi &amp; data sekolah</div>
                            </div>
                            <i class="fas fa-chevron-right ms-auto" style="font-size:.65rem;color:var(--mkb-text-light)"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- end row 2 --}}

    {{-- ── Row 3: Welcome info + mini steps ─────────────────────── --}}
    <div class="row g-3">

        <div class="col-12 col-md-7">
            <div class="mkb-welcome">
                <div class="d-flex align-items-start gap-3">
                    <div style="width:44px;height:44px;border-radius:12px;background:rgba(59,130,246,.15);display:flex;align-items:center;justify-content:center;color:#3B82F6;font-size:1.25rem;flex-shrink:0">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div>
                        <h5>Selamat Datang di MKB GRIK</h5>
                        <p>Aplikasi ini digunakan oleh <strong>Bendahara</strong> dan <strong>Panitia Kegiatan</strong> untuk mengelola data anggaran, realisasi belanja, serta laporan keuangan sekolah secara terpadu, transparan, dan akuntabel.</p>
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <a href="{{ route('bos.realisasi.index') }}" class="mkb-btn mkb-btn-primary" style="font-size:.8rem">
                                <i class="fas fa-rocket fa-xs"></i> Mulai Kelola BOSP
                            </a>
                            <a href="{{ route('school.index') }}" class="mkb-btn mkb-btn-outline" style="font-size:.8rem">
                                <i class="fas fa-school fa-xs"></i> Profil Sekolah
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-5">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="card-title mb-0">Alur Kerja BOSP</h6>
                </div>
                <div class="card-body" style="padding:16px 20px!important">
                    <div class="d-flex flex-column gap-3">

                        <div class="d-flex align-items-start gap-3">
                            <div style="width:30px;height:30px;border-radius:50%;background:#EFF6FF;color:#3B82F6;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;flex-shrink:0">1</div>
                            <div>
                                <div style="font-weight:600;font-size:.85rem;color:var(--mkb-text)">Upload Kertas Kerja</div>
                                <div style="font-size:.76rem;color:var(--mkb-text-muted)">Unggah PDF kertas kerja BOSP dari Dapodik/SDS</div>
                            </div>
                        </div>

                        <div class="d-flex align-items-start gap-3">
                            <div style="width:30px;height:30px;border-radius:50%;background:#F0F9FF;color:#0EA5E9;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;flex-shrink:0">2</div>
                            <div>
                                <div style="font-weight:600;font-size:.85rem;color:var(--mkb-text)">Ekstrak Data Anggaran</div>
                                <div style="font-size:.76rem;color:var(--mkb-text-muted)">Sistem membaca dan menyimpan rincian anggaran per kegiatan</div>
                            </div>
                        </div>

                        <div class="d-flex align-items-start gap-3">
                            <div style="width:30px;height:30px;border-radius:50%;background:#F0FDF4;color:#22C55E;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;flex-shrink:0">3</div>
                            <div>
                                <div style="font-weight:600;font-size:.85rem;color:var(--mkb-text)">Catat Realisasi Belanja</div>
                                <div style="font-size:.76rem;color:var(--mkb-text-muted)">Masukkan bukti belanja dan realisasi anggaran harian</div>
                            </div>
                        </div>

                        <div class="d-flex align-items-start gap-3">
                            <div style="width:30px;height:30px;border-radius:50%;background:#FFFBEB;color:#F59E0B;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;flex-shrink:0">4</div>
                            <div>
                                <div style="font-weight:600;font-size:.85rem;color:var(--mkb-text)">Laporan & Rekap</div>
                                <div style="font-size:.76rem;color:var(--mkb-text-muted)">Export laporan realisasi untuk pelaporan ke dinas</div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>{{-- end row 3 --}}

</div>{{-- end .px-2 --}}
@stop

@section('adminlte_css')
<style>
    .content { padding: 16px 24px 24px !important; }
    .min-w-0 { min-width: 0; }
</style>
@stop
