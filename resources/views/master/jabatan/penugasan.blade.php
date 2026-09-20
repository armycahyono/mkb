@extends('adminlte::page')

@section('title', 'Penetapan Penugasan Jabatan Guru')

@section('plugins.Datatables', true)
@section('plugins.Select2', true)
@section('plugins.Sweetalert2', true)

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap" style="padding: 2px 0;">
    <div>
        <h1 class="page-title"><i class="fas fa-user-check text-primary mr-1"></i> Penetapan Penugasan Jabatan Guru</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-house"></i> Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('jabatan.index') }}">Data Pokok</a></li>
                <li class="breadcrumb-item active">Penugasan Jabatan</li>
            </ol>
        </nav>
    </div>
    <div class="header-actions">
        <a href="{{ route('jabatan.index') }}" class="btn btn-sm btn-outline-secondary mr-1">
            <i class="fas fa-list mr-1"></i> Master Nama Jabatan
        </a>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#penugasanModal" data-toggle="modal" data-target="#penugasanModal" id="btnCreatePenugasan">
            <i class="fas fa-plus mr-1"></i> Tetapkan Jabatan Guru
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
    #penugasanTable {
        font-size: 0.8125rem !important;
    }
    #penugasanTable th {
        font-size: 0.74rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 0.4rem 0.5rem !important;
        background-color: #f8fafc;
        color: #475569;
        vertical-align: middle;
    }
    #penugasanTable td {
        padding: 0.4rem 0.5rem !important;
        vertical-align: middle;
        line-height: 1.35;
    }
    .stat-badge-card {
        border-radius: 6px;
        padding: 10px 14px;
        background: #fff;
        border: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .stat-badge-icon {
        width: 38px;
        height: 38px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
    }
    .btn-period-preset {
        font-size: 0.75rem;
        padding: 4px 8px;
    }
    .child-history-card {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 12px;
        margin: 4px 0;
    }
    .child-history-table th {
        background-color: #eef2f6 !important;
        color: #334155 !important;
        font-size: 0.72rem !important;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 5px 8px !important;
    }
    .child-history-table td {
        padding: 5px 8px !important;
        font-size: 0.76rem !important;
        vertical-align: middle !important;
    }
    tr.shown .btn-toggle-history {
        background-color: #334155 !important;
        color: #fff !important;
        border-color: #334155 !important;
    }
</style>
@stop

@section('content')
@php
    $expiredList = $expiredList ?? collect([]);
    $expiringSoonList = $expiringSoonList ?? collect([]);
    $missingSignatories = $missingSignatories ?? collect([]);
    $expiredSignatories = $expiredSignatories ?? collect([]);
    $totalActive = $totalActive ?? 0;
@endphp

{{-- Alert Khusus Pejabat Penandatangan SPJ Belum Siap / Kadaluarsa --}}
@if($missingSignatories->isNotEmpty() || $expiredSignatories->isNotEmpty())
<div class="alert shadow-sm border-0 mb-3" style="border-left: 5px solid #b91c1c !important; background-color: #fff1f2; color: #881337;">
    <div class="d-flex align-items-start" style="gap: 14px;">
        <div style="font-size: 1.65rem; line-height: 1;" class="text-danger mt-1">
            <i class="fas fa-file-signature"></i>
        </div>
        <div class="flex-grow-1">
            <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 8px;">
                <strong style="font-size: 0.95rem; color: #9f1239;">
                    <i class="fas fa-exclamation-triangle mr-1"></i> Perhatian: Jabatan Penandatangan SPJ Belum Ditetapkan / Habis Masa Berlaku!
                </strong>
                <button type="button" class="btn btn-xs btn-danger font-weight-bold" data-toggle="modal" data-target="#penugasanModal" onclick="$('#btnCreatePenugasan').click()">
                    <i class="fas fa-user-plus mr-1"></i> Tetapkan Pejabat Penandatangan
                </button>
            </div>
            <div class="small mt-1 text-dark" style="font-size: 0.8125rem; line-height: 1.45;">
                Dokumen SPJ (Kwitansi BOS/BPOPP, SPPD Perjadin, dan Surat Pencairan Bank) memerlukan pejabat penandatangan yang sah dan aktif saat ini. Tindakan berikut diperlukan:
            </div>
            <ul class="mb-0 pl-3 mt-2 small" style="color: #4c0519; font-size: 0.8125rem;">
                @foreach($missingSignatories as $jabatan)
                    <li class="mb-1">
                        <span class="badge badge-danger mr-1" style="font-size: 0.7rem;"><i class="fas fa-user-times mr-1"></i>Belum Ada Pejabat</span>
                        Jabatan <b>{{ $jabatan->name }}</b> saat ini <u>belum memiliki pejabat aktif</u> yang bertugas.
                    </li>
                @endforeach
                @foreach($expiredSignatories as $item)
                    <li class="mb-1">
                        <span class="badge badge-warning text-dark mr-1" style="font-size: 0.7rem;"><i class="fas fa-history mr-1"></i>SK Kadaluarsa</span>
                        Jabatan <b>{{ $item['jabatan']->name }}</b>: Masa tugas <b>{{ $item['penugasan']->guru ? $item['penugasan']->guru->full_name_with_title : '-' }}</b> telah berakhir per <b>{{ $item['penugasan']->end_date ? $item['penugasan']->end_date->format('d/m/Y') : '-' }}</b>.
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endif

{{-- Alert Kadaluarsa Umum --}}
@if($expiredList->isNotEmpty())
<div class="alert alert-danger shadow-sm border-0 mb-3 d-flex align-items-start" style="gap: 12px; border-left: 4px solid #dc3545 !important;">
    <div style="font-size: 1.4rem; line-height: 1;"><i class="fas fa-exclamation-circle text-danger"></i></div>
    <div class="flex-grow-1">
        <strong class="d-block" style="font-size: 0.88rem;">Peringatan: Terdapat {{ $expiredList->count() }} Penugasan Jabatan yang Telah Kadaluarsa!</strong>
        <div class="small mt-1 text-dark">
            Pejabat berikut telah melewati batas akhir masa berlaku SK namun statusnya masih aktif:
            <ul class="mb-1 pl-3 mt-1">
                @foreach($expiredList as $exp)
                    <li>
                        <b>{{ $exp->guru->full_name_with_title }}</b> sebagai <b>{{ $exp->jabatan->name }}</b>
                        (Berakhir per {{ $exp->end_date ? $exp->end_date->format('d/m/Y') : '-' }}).
                    </li>
                @endforeach
            </ul>
            <span class="text-muted">Silakan unggah SK perpanjangan baru atau ubah status menjadi demisioner agar arsip SPJ tetap akurat.</span>
        </div>
    </div>
</div>
@endif

{{-- Alert Mendekati Kadaluarsa --}}
@if($expiringSoonList->isNotEmpty())
<div class="alert alert-warning text-dark shadow-sm border-0 mb-3 d-flex align-items-start" style="gap: 12px; border-left: 4px solid #ffc107 !important; background-color: #fffbeb;">
    <div style="font-size: 1.4rem; line-height: 1;"><i class="fas fa-clock text-warning"></i></div>
    <div class="flex-grow-1">
        <strong class="d-block" style="font-size: 0.88rem;">Pemberitahuan: {{ $expiringSoonList->count() }} SK Penugasan Akan Berakhir dalam Waktu Dekat (&le; 30 Hari)</strong>
        <div class="small mt-1">
            Pejabat terkait:
            <span class="font-weight-500">
                @foreach($expiringSoonList as $soon)
                    {{ $soon->guru->name }} ({{ $soon->jabatan->name }} - sisa <b>{{ $soon->days_remaining }} hari</b>){{ !$loop->last ? ', ' : '' }}
                @endforeach
            </span>.
            Harap persiapkan proses penerbitan SK periode berikutnya.
        </div>
    </div>
</div>
@endif

{{-- Ringkasan Metrik / Status Card --}}
<div class="row mb-3">
    <div class="col-md-4 col-sm-6 mb-2">
        <div class="stat-badge-card shadow-sm">
            <div class="stat-badge-icon bg-success-light text-success" style="background-color: #ecfdf5;">
                <i class="fas fa-user-check"></i>
            </div>
            <div>
                <div class="text-muted small text-uppercase font-weight-bold" style="font-size: 0.68rem; letter-spacing: 0.5px;">Pejabat Aktif Menjabat</div>
                <div class="font-weight-bold text-dark font-tabular" style="font-size: 1.25rem; line-height: 1.2;">{{ $totalActive }} <small class="text-muted font-weight-normal" style="font-size: 0.75rem;">Orang</small></div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6 mb-2">
        <div class="stat-badge-card shadow-sm">
            <div class="stat-badge-icon bg-warning-light text-warning" style="background-color: #fffbeb;">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <div>
                <div class="text-muted small text-uppercase font-weight-bold" style="font-size: 0.68rem; letter-spacing: 0.5px;">SK Mendekati Berakhir (&le; 30 Hari)</div>
                <div class="font-weight-bold text-dark font-tabular" style="font-size: 1.25rem; line-height: 1.2;">{{ $expiringSoonList->count() }} <small class="text-muted font-weight-normal" style="font-size: 0.75rem;">Jabatan</small></div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6 mb-2">
        <div class="stat-badge-card shadow-sm">
            <div class="stat-badge-icon bg-danger-light text-danger" style="background-color: #fef2f2;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div>
                <div class="text-muted small text-uppercase font-weight-bold" style="font-size: 0.68rem; letter-spacing: 0.5px;">SK Melewati Batas Akhir</div>
                <div class="font-weight-bold text-dark font-tabular" style="font-size: 1.25rem; line-height: 1.2;">{{ $expiredList->count() }} <small class="text-muted font-weight-normal" style="font-size: 0.75rem;">Kadaluarsa</small></div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-3">
    <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center flex-wrap" style="gap: 8px; border-bottom: 1px solid #edf2f7;">
        {{-- Scope Tabs: Penandatangan SPJ Saja (Default) vs Semua Jabatan --}}
        <div class="btn-group btn-group-sm" role="group" id="scopeTabGroup">
            <button type="button" class="btn btn-primary active font-weight-bold" data-scope="signatory" id="tabSignatory">
                <i class="fas fa-file-signature mr-1"></i> Penandatangan SPJ Saja
                <span class="badge badge-light text-primary ml-1 font-tabular">{{ $signatoryCount ?? ($jabatans ? $jabatans->where('is_signatory', true)->count() : 0) }}</span>
            </button>
            <button type="button" class="btn btn-outline-primary font-weight-bold" data-scope="all" id="tabAll">
                <i class="fas fa-th-list mr-1"></i> Semua Formasi Jabatan
                <span class="badge badge-secondary ml-1 font-tabular">{{ $allCount ?? ($jabatans ? $jabatans->count() : 0) }}</span>
            </button>
        </div>

        {{-- Filter Status Formasi --}}
        <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
            <div class="d-flex align-items-center">
                <span class="text-muted mr-1 font-weight-bold" style="font-size: 0.74rem;">Status Formasi:</span>
                <select id="filter-status-formasi" class="form-control form-control-sm" style="width: 170px; height: 28px; font-size: 0.78rem; padding: 2px 6px;">
                    <option value="">Semua Status</option>
                    <option value="active">🟢 Terisi & Aktif</option>
                    <option value="expiring_soon">🟡 Mendekati Berakhir (&le; 30 Hari)</option>
                    <option value="expired">🔴 SK Kadaluarsa</option>
                    <option value="vacant">⚪ Formasi Kosong</option>
                </select>
            </div>
        </div>
    </div>
    <div class="card-body p-2">
        <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered table-striped w-100" id="penugasanTable">
                <thead>
                    <tr>
                        <th width="35" class="text-center">NO</th>
                        <th width="240">JABATAN SEKOLAH</th>
                        <th width="240">PEJABAT TERAKHIR / AKTIF</th>
                        <th width="180">NOMOR SK & BERKAS</th>
                        <th width="160">MASA TUGAS (TMT)</th>
                        <th width="130" class="text-center">STATUS FORMASI</th>
                        <th width="160" class="text-center">AKSI</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

{{-- Modal Tambah / Edit / Perpanjang / Ganti Penugasan Jabatan --}}
<div class="modal fade" id="penugasanModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="max-width: 820px;">
        <form id="penugasanForm" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" id="penugasanMethod" value="POST">
            <input type="hidden" id="penugasanId" value="">

            <div class="modal-content shadow border-0">
                <div class="modal-header py-2 px-3 bg-light border-bottom">
                    <h6 class="modal-title font-weight-bold text-dark" id="penugasanModalTitle" style="font-size: 0.95rem;">
                        <i class="fas fa-user-check text-primary mr-2"></i>Tetapkan Penugasan Jabatan Guru
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    {{-- Guidance Box: Informasi Jabatan, Pejabat Sebelumnya, dan Saran Tanggal Anti-Gap --}}
                    <div id="modalGuidanceBox" class="p-3 mb-3 rounded border" style="background-color: #f8fafc;">
                        <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
                            <div>
                                <span class="text-muted small text-uppercase font-weight-bold" style="font-size: 0.68rem; letter-spacing: 0.5px;">Formasi Jabatan:</span>
                                <div class="font-weight-bold text-dark" style="font-size: 1.05rem;" id="modalJabatanNameDisplay">Kepala Sekolah</div>
                            </div>
                            <div id="modalJabatanBadges">
                            </div>
                        </div>

                        {{-- Kotak Riwayat Terakhir & Anti-Gap --}}
                        <div id="modalPreviousHolderInfo" class="alert alert-secondary py-2 px-3 mb-2 border-0" style="background-color: #f1f5f9; font-size: 0.8125rem;">
                            <div class="d-flex align-items-start" style="gap: 10px;">
                                <i class="fas fa-history text-secondary mt-1" style="font-size: 1.1rem;" id="modalPrevHolderIcon"></i>
                                <div class="flex-grow-1">
                                    <div id="modalPrevHolderLabel">Pejabat Sebelumnya: <strong id="modalPrevHolderName" class="text-dark">-</strong></div>
                                    <div id="modalTeamHoldersList" class="d-none mt-1"></div>
                                    <div class="text-muted small" id="modalPrevPeriodText">Masa Tugas Terakhir: -</div>
                                    <div class="text-success font-weight-bold mt-1" style="font-size: 0.78rem;" id="modalAntiGapHint">
                                        <i class="fas fa-lightbulb text-warning mr-1"></i> Disarankan mulai tanggal <b>H+1</b> agar tidak terjadi kekosongan masa jabatan.
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Mode Aksi Radio: Perpanjang vs Ganti Orang vs Tambah Anggota Tim --}}
                        <div id="modalActionModeContainer" class="mt-2 pt-2 border-top">
                            <div class="font-weight-bold text-dark small mb-1">Pilihan Tindakan Penugasan:</div>
                            <div class="d-flex flex-wrap" style="gap: 16px;">
                                <div class="custom-control custom-radio" id="modeAddTeamWrapper">
                                    <input type="radio" id="modeAddTeam" name="action_mode" class="custom-control-input" value="add_team">
                                    <label class="custom-control-label small font-weight-bold text-success" for="modeAddTeam">
                                        <i class="fas fa-user-plus mr-1"></i> Tambah Anggota Tim (Aktif Bersamaan)
                                    </label>
                                </div>
                                <div class="custom-control custom-radio" id="modeRenewWrapper">
                                    <input type="radio" id="modeRenew" name="action_mode" class="custom-control-input" value="renew" checked>
                                    <label class="custom-control-label small font-weight-bold text-success" for="modeRenew">
                                        <i class="fas fa-redo-alt mr-1"></i> Perpanjang Masa Tugas (Pejabat Sama)
                                    </label>
                                </div>
                                <div class="custom-control custom-radio" id="modeReplaceWrapper">
                                    <input type="radio" id="modeReplace" name="action_mode" class="custom-control-input" value="replace">
                                    <label class="custom-control-label small font-weight-bold text-primary" for="modeReplace" id="modeReplaceLabel">
                                        <i class="fas fa-user-plus mr-1"></i> Ganti Pejabat Baru (Serah Terima)
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Row 1: Jabatan & Guru --}}
                    <div class="row">
                        {{-- Field Jabatan (hidden dropdown if opened from row, visible if opened from general button) --}}
                        <div class="col-md-6" id="jabatanSelectWrapper">
                            <div class="form-group mb-3">
                                <label class="small font-weight-bold">Jabatan Sekolah <span class="text-danger">*</span></label>
                                <select name="jabatan_id" id="penugasanJabatanId" class="form-control form-control-sm" required>
                                    <option value="">-- Pilih Jabatan --</option>
                                    @foreach($jabatans as $j)
                                        <option value="{{ $j->id }}" data-code="{{ $j->code }}" data-name="{{ $j->name }}" data-signatory="{{ $j->is_signatory ? '1' : '0' }}" data-is-team="{{ $j->is_team ? '1' : '0' }}">
                                            {{ $j->name }} {{ $j->is_signatory ? '(Penandatangan SPJ)' : '' }} {{ $j->is_team ? '[Tim / Kepanitiaan]' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Field Guru --}}
                        <div class="col-md-6" id="guruFieldWrapper">
                            <div class="form-group mb-3">
                                <label class="small font-weight-bold" id="guruFieldLabel">Pejabat / Guru <span class="text-danger">*</span></label>
                                {{-- Mode Renew (Terkunci) --}}
                                <div id="guruLockedBox" class="d-none">
                                    <div class="input-group input-group-sm">
                                        <input type="text" id="guruLockedDisplay" class="form-control form-control-sm bg-white font-weight-bold text-dark" readonly>
                                        <div class="input-group-append">
                                            <span class="input-group-text bg-light text-success font-weight-bold" style="font-size:0.72rem;"><i class="fas fa-lock mr-1"></i>Pejabat Sama</span>
                                        </div>
                                    </div>
                                    <input type="hidden" id="guruLockedId" value="">
                                </div>

                                {{-- Mode Single (Dropdown Standar) --}}
                                <div id="guruDropdownContainer">
                                    <select name="guru_id" id="penugasanGuruId" class="form-control form-control-sm">
                                        <option value="">-- Pilih Guru / Pejabat Baru --</option>
                                        @foreach($gurus as $g)
                                            <option value="{{ $g->id }}">{{ $g->full_name_with_title }} {{ $g->nip ? '(NIP. '.$g->nip.')' : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Mode Multi-select untuk Tim (Select2) --}}
                                <div id="guruMultiDropdownContainer" class="d-none">
                                    <select name="guru_ids[]" id="penugasanGuruIds" class="form-control form-control-sm" multiple="multiple" style="width: 100%;">
                                        @foreach($gurus as $g)
                                            <option value="{{ $g->id }}">{{ $g->full_name_with_title }} {{ $g->nip ? '(NIP. '.$g->nip.')' : '' }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">
                                        <i class="fas fa-info-circle mr-1 text-info"></i>Bisa memilih lebih dari 1 guru sekaligus. Semua anggota terpilih akan memiliki nomor SK dan masa berlaku yang sama.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Row Pergantian Pejabat Otomatis (Muncul saat Create / Ganti) --}}
                    <div id="replaceActiveContainer" class="bg-light p-2 rounded border mb-3 d-none">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="penugasanReplaceActive" name="replace_active" value="1" checked>
                            <label class="custom-control-label small font-weight-bold text-danger" for="penugasanReplaceActive" id="replaceActiveLabel">
                                <i class="fas fa-exchange-alt mr-1"></i> Gantikan pejabat aktif saat ini pada jabatan ini (Serah Terima)
                            </label>
                        </div>
                        <div class="text-muted pl-4" style="font-size: 0.72rem; line-height: 1.35;" id="replaceActiveHelpText">
                            Pejabat sebelumnya akan otomatis disesuaikan tanggal akhirnya ke <b>H-1 tanggal mulai baru</b> dan statusnya diubah menjadi demisioner tanpa menghapus arsip SK lama.
                        </div>
                    </div>

                    {{-- Row 2: Dasar Hukum SK & Berkas PDF --}}
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group mb-3">
                                <label class="small font-weight-bold">Nomor Surat Keputusan (SK)</label>
                                <input type="text" name="sk_number" id="penugasanSkNumber" class="form-control form-control-sm font-tabular" placeholder="Contoh: 800/012/SK/2025">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group mb-3">
                                <label class="small font-weight-bold">Tanggal SK</label>
                                <input type="date" name="sk_date" id="penugasanSkDate" class="form-control form-control-sm font-tabular">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label class="small font-weight-bold">Unggah Berkas SK <small class="text-muted">(PDF, Maks. 5MB)</small></label>
                                <input type="file" name="sk_file" id="penugasanSkFile" class="form-control form-control-sm" accept=".pdf">
                                <div id="existingSkFileContainer" class="mt-1 d-none">
                                    <a href="#" target="_blank" id="existingSkFileLink" class="btn btn-xs btn-outline-danger">
                                        <i class="fas fa-file-pdf mr-1"></i>Lihat SK Tersimpan
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Row Template Periode Cepat --}}
                    <div class="bg-light p-2 rounded border mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="small font-weight-bold text-dark mb-0">Pilihan Cepat Template Periode:</label>
                            <small class="text-muted">Menyesuaikan tanggal mulai & selesai otomatis</small>
                        </div>
                        <div class="btn-group btn-group-sm w-100" role="group" id="btnGroupPeriodType">
                            <button type="button" class="btn btn-outline-primary btn-period-preset active" data-type="tahun_anggaran">
                                <i class="fas fa-calendar-alt mr-1"></i> Tahun Anggaran (1 Jan - 31 Des)
                            </button>
                            <button type="button" class="btn btn-outline-info btn-period-preset" data-type="tahun_pelajaran">
                                <i class="fas fa-graduation-cap mr-1"></i> Tahun Pelajaran (1 Jul - 30 Jun)
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-period-preset" data-type="multi_tahun">
                                <i class="fas fa-layer-group mr-1"></i> Multi-Tahun (4 Thn Kepsek)
                            </button>
                            <button type="button" class="btn btn-outline-dark btn-period-preset" data-type="custom">
                                <i class="fas fa-sliders-h mr-1"></i> Kustom
                            </button>
                        </div>
                        <input type="hidden" name="period_type" id="penugasanPeriodType" value="tahun_anggaran">
                    </div>

                    {{-- Row 3: Masa Berlaku / TMT (2 Kolom Sejajar) --}}
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="small font-weight-bold">Tgl Mulai Menjabat (TMT) <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" id="penugasanStartDate" class="form-control form-control-sm font-tabular" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="small font-weight-bold">Tgl Selesai Masa Tugas <small class="text-muted">(Opsional)</small></label>
                                <input type="date" name="end_date" id="penugasanEndDate" class="form-control form-control-sm font-tabular">
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="period_year" id="penugasanPeriodYear">

                    {{-- Row 4: Catatan --}}
                    <div class="form-group mb-3">
                        <label class="small font-weight-bold">Catatan Penugasan <small class="text-muted">(Opsional)</small></label>
                        <input type="text" name="notes" id="penugasanNotes" class="form-control form-control-sm" placeholder="Contoh: SK Perpanjangan / Tugas Tambahan Kepala Sekolah Periode ke-2">
                    </div>

                    {{-- Status Keaktifan Otomatis --}}
                    <div class="alert alert-light border py-2 px-3 mb-0 d-flex align-items-center" style="gap: 10px;">
                        <i class="fas fa-magic text-primary" style="font-size: 1.1rem;"></i>
                        <div class="small text-muted" style="font-size: 0.75rem; line-height: 1.35;">
                            <b class="text-dark">Status Keaktifan Otomatis:</b> Sistem otomatis menentukan status menjabat aktif atau demisioner berdasarkan tanggal masa berlaku SK di atas.
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3 bg-light border-top">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary px-3 font-weight-bold"><i class="fas fa-save mr-1"></i> Simpan Penugasan</button>
                </div>
            </div>
        </form>
    </div>
</div>
@stop

@section('js')
<script>
$(document).ready(function() {
    let currentScope = 'signatory';

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    let table = $('#penugasanTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 25,
        ajax: {
            url: "{{ route('jabatan.penugasan', [], false) }}",
            data: function(d) {
                d.scope = currentScope;
                d.status = $('#filter-status-formasi').val();
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center font-tabular'},
            {data: 'jabatan_display', name: 'name'},
            {data: 'pejabat_display', name: 'pejabat_display', orderable: false},
            {data: 'sk_display', name: 'sk_display', orderable: false},
            {data: 'masa_tugas_display', name: 'masa_tugas_display', orderable: false},
            {data: 'status_display', name: 'status_display', orderable: false, className: 'text-center'},
            {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center'}
        ]
    });

    // Scope Tab Toggle: Penandatangan SPJ vs Semua Jabatan
    $('#tabSignatory').on('click', function() {
        currentScope = 'signatory';
        $('#tabSignatory').removeClass('btn-outline-primary').addClass('btn-primary active');
        $('#tabAll').removeClass('btn-primary active').addClass('btn-outline-primary');
        table.draw();
    });

    $('#tabAll').on('click', function() {
        currentScope = 'all';
        $('#tabAll').removeClass('btn-outline-primary').addClass('btn-primary active');
        $('#tabSignatory').removeClass('btn-primary active').addClass('btn-outline-primary');
        table.draw();
    });

    $('#filter-status-formasi').on('change', function() {
        table.draw();
    });

    function showModal(modalId) {
        let el = document.getElementById(modalId);
        if (window.bootstrap && window.bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(el).show();
        } else if (window.$ && typeof $(el).modal === 'function') {
            $(el).modal('show');
        }
    }

    function hideModal(modalId) {
        let el = document.getElementById(modalId);
        if (window.bootstrap && window.bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(el).hide();
        } else if (window.$ && typeof $(el).modal === 'function') {
            $(el).modal('hide');
        }
    }

    // Helper Template Periode Cepat
    function applyPeriodPreset(type, baseYear = null, customStartDate = null) {
        if (!baseYear) {
            baseYear = customStartDate ? new Date(customStartDate).getFullYear() : new Date().getFullYear();
        }
        $('#penugasanPeriodType').val(type);
        $('.btn-period-preset').removeClass('active');
        $('.btn-period-preset[data-type="' + type + '"]').addClass('active');

        if (type === 'tahun_anggaran') {
            $('#penugasanPeriodYear').val(baseYear);
            $('#penugasanStartDate').val(customStartDate || (baseYear + '-01-01'));
            $('#penugasanEndDate').val(baseYear + '-12-31');
        } else if (type === 'tahun_pelajaran') {
            let startYear = baseYear;
            let nextYear = parseInt(baseYear) + 1;
            $('#penugasanPeriodYear').val(startYear + '/' + nextYear);
            $('#penugasanStartDate').val(customStartDate || (startYear + '-07-01'));
            $('#penugasanEndDate').val(nextYear + '-06-30');
        } else if (type === 'multi_tahun') {
            let startYear = baseYear;
            let endYear = parseInt(baseYear) + 4;
            $('#penugasanPeriodYear').val(startYear + ' - ' + endYear);
            $('#penugasanStartDate').val(customStartDate || (startYear + '-01-01'));
            $('#penugasanEndDate').val(endYear + '-01-01');
        }
    }

    $('.btn-period-preset').on('click', function() {
        let type = $(this).data('type');
        let currStart = $('#penugasanStartDate').val();
        let currYear = currStart ? new Date(currStart).getFullYear() : new Date().getFullYear();
        applyPeriodPreset(type, currYear, currStart);
    });

    // Inisialisasi Select2 untuk pilihan guru multi-select
    if ($.fn.select2) {
        $('#penugasanGuruIds').select2({
            dropdownParent: $('#penugasanModal'),
            placeholder: '-- Pilih 1 atau Lebih Guru Anggota Tim --',
            allowClear: true,
            width: '100%'
        });
    }

    // Setup Mode: Renew (Pejabat Sama) vs Replace (Ganti Pejabat/Rombak) vs Add Team (Tambah Anggota) vs New (Formasi Kosong)
    function setupMode(mode, lastInfo, isTeam = false) {
        if (isTeam) {
            $('#guruLockedBox').addClass('d-none');
            $('#guruDropdownContainer').addClass('d-none');
            $('#guruMultiDropdownContainer').removeClass('d-none');
            $('#penugasanGuruId').prop('disabled', true);
            $('#penugasanGuruIds').prop('disabled', false);

            if (mode === 'add_team') {
                $('#replaceActiveContainer').addClass('d-none');
                $('#penugasanReplaceActive').prop('checked', false);
                $('#penugasanNotes').val('Penetapan anggota tim');
            } else if (mode === 'replace') {
                $('#replaceActiveContainer').removeClass('d-none');
                $('#penugasanReplaceActive').prop('checked', true);
                $('#replaceActiveLabel').html('<i class="fas fa-exchange-alt mr-1"></i> Rombak seluruh anggota tim (Demisionerkan anggota lama)');
                $('#replaceActiveHelpText').html('Seluruh anggota tim sebelumnya akan otomatis disesuaikan tanggal akhirnya ke <b>H-1 tanggal mulai baru</b> dan statusnya diubah menjadi demisioner.');
                $('#penugasanNotes').val('Rombak susunan anggota tim');
            }

            let pType = (lastInfo && lastInfo.period_type) ? lastInfo.period_type : 'tahun_anggaran';
            let startDate = (lastInfo && lastInfo.suggested_start_date) ? lastInfo.suggested_start_date : null;
            applyPeriodPreset(pType, startDate ? new Date(startDate).getFullYear() : null, startDate);
        } else {
            $('#guruMultiDropdownContainer').addClass('d-none');
            $('#penugasanGuruIds').prop('disabled', true);

            if (mode === 'renew' && lastInfo && lastInfo.guru_id) {
                $('#guruLockedBox').removeClass('d-none');
                $('#guruLockedDisplay').val(lastInfo.guru_name);
                $('#guruLockedId').val(lastInfo.guru_id);
                $('#penugasanGuruId').prop('disabled', true).val(lastInfo.guru_id);
                $('#guruDropdownContainer').addClass('d-none');
                $('#replaceActiveContainer').addClass('d-none');
                $('#penugasanNotes').val('Perpanjangan masa tugas');

                let pType = lastInfo.period_type || 'tahun_anggaran';
                applyPeriodPreset(pType, lastInfo.suggested_start_date ? new Date(lastInfo.suggested_start_date).getFullYear() : null, lastInfo.suggested_start_date);
            } else if (mode === 'replace') {
                $('#guruLockedBox').addClass('d-none');
                $('#guruDropdownContainer').removeClass('d-none');
                $('#penugasanGuruId').prop('disabled', false).val('');
                $('#replaceActiveContainer').removeClass('d-none');
                $('#penugasanReplaceActive').prop('checked', true);
                $('#replaceActiveLabel').html('<i class="fas fa-exchange-alt mr-1"></i> Gantikan pejabat aktif saat ini pada jabatan ini (Serah Terima)');
                $('#replaceActiveHelpText').html('Pejabat sebelumnya akan otomatis disesuaikan tanggal akhirnya ke <b>H-1 tanggal mulai baru</b> dan statusnya diubah menjadi demisioner tanpa menghapus arsip SK lama.');
                $('#penugasanNotes').val('Penggantian pejabat baru (Serah Terima)');

                let startDate = lastInfo ? lastInfo.suggested_start_date : null;
                let pType = (lastInfo && lastInfo.period_type) ? lastInfo.period_type : 'tahun_anggaran';
                applyPeriodPreset(pType, startDate ? new Date(startDate).getFullYear() : null, startDate);
            } else {
                $('#guruLockedBox').addClass('d-none');
                $('#guruDropdownContainer').removeClass('d-none');
                $('#penugasanGuruId').prop('disabled', false).val('');
                $('#replaceActiveContainer').addClass('d-none');
                $('#penugasanNotes').val('');

                let currYear = new Date().getFullYear();
                applyPeriodPreset('tahun_anggaran', currYear);
            }
        }
    }

    $('input[name="action_mode"]').on('change', function() {
        let mode = $(this).val();
        let isTeam = $('#penugasanForm').data('current-is-team') || false;
        let lastInfo = $('#penugasanForm').data('current-last-info');
        setupMode(mode, lastInfo, isTeam);
    });

    // Format Child Row History
    function formatHistoryRow(d) {
        let history = d && d.history ? (typeof d.history === 'string' ? JSON.parse(d.history) : d.history) : [];
        if (!history || history.length === 0) {
            return `
                <div class="child-history-card">
                    <div class="text-center text-muted font-italic py-2" style="font-size: 0.8125rem;">
                        <i class="fas fa-info-circle mr-1 text-info"></i> Belum ada riwayat penetapan pejabat untuk formasi jabatan <b>${escapeHtml(d.name || '')}</b>.
                    </div>
                </div>
            `;
        }

        let rowsHtml = '';
        history.forEach(function(item, idx) {
            let statusBadge = item.is_active
                ? '<span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i>Aktif Menjabat</span>'
                : '<span class="badge badge-secondary px-2 py-1"><i class="fas fa-history mr-1"></i>Demisioner</span>';

            let skFileBtn = item.sk_file_url
                ? `<a href="${item.sk_file_url}" target="_blank" class="btn btn-xs btn-outline-danger" title="Lihat Dokumen PDF"><i class="fas fa-file-pdf mr-1"></i>PDF</a>`
                : '<span class="text-muted">-</span>';

            let nipText = item.guru_nip ? `<small class="text-muted d-block font-tabular">NIP. ${escapeHtml(item.guru_nip)}</small>` : '';

            let itemJson = JSON.stringify(item).replace(/"/g, '&quot;');

            rowsHtml += `
                <tr>
                    <td class="text-center font-tabular">${idx + 1}</td>
                    <td>
                        <strong class="text-dark">${escapeHtml(item.guru_name)}</strong>
                        ${nipText}
                    </td>
                    <td>
                        <span class="font-tabular font-weight-500 text-dark">${escapeHtml(item.sk_number)}</span>
                        ${item.sk_date ? `<small class="text-muted d-block font-tabular">Tgl: ${escapeHtml(item.sk_date)}</small>` : ''}
                    </td>
                    <td class="text-center">${skFileBtn}</td>
                    <td>
                        <span class="badge badge-light border font-tabular mr-1">${escapeHtml(item.period_year || '-')}</span>
                        <small class="text-muted d-block font-tabular">${escapeHtml(item.start_date)} s/d ${escapeHtml(item.end_date)}</small>
                    </td>
                    <td class="text-center">${statusBadge}</td>
                    <td><small class="text-muted">${escapeHtml(item.notes || '-')}</small></td>
                    <td class="text-center text-nowrap">
                        <button type="button" class="btn btn-xs btn-warning btn-edit-penugasan mr-1" data-json="${itemJson}" data-update-url="${item.update_url}" title="Edit Dokumen SK Ini"><i class="fas fa-edit"></i></button>
                        <button type="button" class="btn btn-xs btn-danger btn-delete-penugasan" data-url="${item.delete_url}" title="Hapus Dokumen SK Ini"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `;
        });

        return `
            <div class="child-history-card">
                <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                    <strong class="text-dark" style="font-size: 0.825rem;">
                        <i class="fas fa-history text-primary mr-1"></i> Arsip Riwayat Pejabat & SK: <u>${escapeHtml(d.name || '')}</u>
                    </strong>
                    <span class="badge badge-light border text-muted font-tabular">${d.history.length} Arsip SK Tersimpan</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered child-history-table mb-0">
                        <thead>
                            <tr>
                                <th width="30" class="text-center">#</th>
                                <th>NAMA PEJABAT</th>
                                <th>NOMOR & TGL SK</th>
                                <th width="75" class="text-center">BERKAS</th>
                                <th>MASA TUGAS</th>
                                <th width="125" class="text-center">STATUS</th>
                                <th>CATATAN</th>
                                <th width="70" class="text-center">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rowsHtml}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }

    // Toggle Child Row History
    $('#penugasanTable tbody').on('click', '.btn-toggle-history', function(e) {
        e.preventDefault();
        e.stopPropagation();
        let tr = $(this).closest('tr');
        let row = table.row(tr);

        if (row.child.isShown()) {
            row.child.hide();
            tr.removeClass('shown');
            let count = (row.data() && row.data().history) ? row.data().history.length : 0;
            $(this).html('<i class="fas fa-history mr-1"></i>Riwayat (' + count + ')');
        } else {
            row.child(formatHistoryRow(row.data())).show();
            tr.addClass('shown');
            $(this).html('<i class="fas fa-chevron-up mr-1"></i>Tutup Riwayat');
        }
    });

    // Action: Tetapkan / Perpanjang / Ganti Pejabat dari Baris Tabel
    $(document).on('click', '.btn-assign-jabatan', function(e) {
        e.preventDefault();
        let $btn = $(this);
        let jabatanId = $btn.data('id');
        let jabatanName = $btn.data('name');
        let jabatanCode = $btn.data('code');
        let isSignatory = $btn.data('signatory') == '1';
        let isTeam = $btn.data('is-team') == '1';
        let lastInfo = $btn.data('last');
        if (typeof lastInfo === 'string') {
            try { lastInfo = JSON.parse(lastInfo); } catch(err) { lastInfo = {}; }
        }

        $('#penugasanModalTitle').html('<i class="fas fa-user-edit text-primary mr-2"></i>Penetapan Pejabat: ' + escapeHtml(jabatanName));
        $('#penugasanForm')[0].reset();
        if ($.fn.select2) {
            $('#penugasanGuruIds').val(null).trigger('change');
        }
        $('#penugasanMethod').val('POST');
        $('#penugasanId').val('');
        $('#penugasanForm').data('action-url', "{{ route('guru-jabatan.store') }}");
        $('#penugasanForm').data('current-last-info', lastInfo);
        $('#penugasanForm').data('current-is-team', isTeam);
        $('#existingSkFileContainer').addClass('d-none');

        // Setup Guidance Box
        $('#modalGuidanceBox').removeClass('d-none');
        $('#modalJabatanNameDisplay').text(jabatanName);

        let badgesHtml = '';
        if (isTeam) {
            badgesHtml += '<span class="badge badge-info mr-1 font-weight-bold"><i class="fas fa-users mr-1"></i>Tim / Kepanitiaan (Multi-Anggota)</span>';
        }
        if (isSignatory) {
            badgesHtml += '<span class="badge badge-warning text-dark font-weight-bold"><i class="fas fa-file-signature mr-1"></i>Penandatangan SPJ</span>';
        }
        $('#modalJabatanBadges').html(badgesHtml);

        // Lock Jabatan in Form
        $('#penugasanJabatanId').val(jabatanId);
        $('#jabatanSelectWrapper').addClass('d-none');
        $('#guruFieldWrapper').removeClass('col-md-6').addClass('col-md-12');

        if (isTeam) {
            $('#guruFieldLabel').html('Pilih Guru Anggota Tim <span class="text-danger">*</span>');
            $('#modalPrevHolderIcon').removeClass('text-secondary fa-history').addClass('text-info fa-users');
            $('#modeRenewWrapper').addClass('d-none');
            $('#modeAddTeamWrapper').removeClass('d-none');
            $('#modeReplaceWrapper').removeClass('d-none');
            $('#modeReplaceLabel').html('<i class="fas fa-exchange-alt mr-1"></i> Rombak Tim (Ganti Semua Anggota)');

            let activeCount = lastInfo ? (lastInfo.active_count || 0) : 0;
            let activeMembers = (lastInfo && lastInfo.active_members) ? lastInfo.active_members : [];

            if (activeCount > 0) {
                $('#modalPreviousHolderInfo').removeClass('d-none');
                $('#modalPrevHolderLabel').html('Anggota Tim Aktif Menjabat: <span class="badge badge-success ml-1 font-tabular">' + activeCount + ' Orang</span>');
                $('#modalPrevHolderName').text('');

                let membersListHtml = '<div class="d-flex flex-wrap mt-1" style="gap: 6px;">';
                activeMembers.forEach(function(m) {
                    membersListHtml += '<span class="badge badge-light border text-dark font-weight-500 py-1 px-2"><i class="fas fa-user-check text-success mr-1"></i>' + escapeHtml(m.name) + '</span>';
                });
                membersListHtml += '</div>';
                $('#modalTeamHoldersList').html(membersListHtml).removeClass('d-none');
                $('#modalPrevPeriodText').text('Masa Tugas Berjalan: ' + (lastInfo.last_end_date_formatted ? 's/d ' + lastInfo.last_end_date_formatted : 'Saat ini'));
                $('#modalAntiGapHint').html('<i class="fas fa-info-circle text-info mr-1"></i> Gunakan opsi <b>"Tambah Anggota Tim"</b> untuk menetapkan anggota baru tanpa memengaruhi anggota aktif di atas.');
            } else {
                $('#modalPreviousHolderInfo').removeClass('d-none');
                $('#modalPrevHolderLabel').html('Status Tim: <strong class="text-muted">Belum Memiliki Anggota Aktif</strong>');
                $('#modalTeamHoldersList').addClass('d-none').empty();
                $('#modalPrevPeriodText').text('Formasi tim ini belum memiliki anggota yang aktif bertugas.');
                $('#modalAntiGapHint').html('<i class="fas fa-user-plus text-primary mr-1"></i> Silakan pilih satu atau beberapa guru untuk mengisi keanggotaan tim.');
            }

            $('#modalActionModeContainer').removeClass('d-none');
            $('#modeAddTeam').prop('checked', true);
            setupMode('add_team', lastInfo, true);
        } else {
            $('#guruFieldLabel').html('Pejabat / Guru <span class="text-danger">*</span>');
            $('#modalPrevHolderIcon').removeClass('text-info fa-users').addClass('text-secondary fa-history');
            $('#modalTeamHoldersList').addClass('d-none').empty();
            $('#modeAddTeamWrapper').addClass('d-none');
            $('#modeReplaceLabel').html('<i class="fas fa-user-plus mr-1"></i> Ganti Pejabat Baru (Serah Terima)');

            if (lastInfo && lastInfo.has_previous && lastInfo.guru_id) {
                $('#modalPreviousHolderInfo').removeClass('d-none');
                $('#modalPrevHolderLabel').html('Pejabat Sebelumnya: <strong id="modalPrevHolderName" class="text-dark">' + escapeHtml(lastInfo.guru_name || '-') + '</strong>');
                $('#modalPrevPeriodText').text('Masa Tugas Terakhir: ' + (lastInfo.last_end_date_formatted ? 'Berakhir ' + lastInfo.last_end_date_formatted : 'Belum ditentukan'));
                $('#modalAntiGapHint').html('<i class="fas fa-lightbulb text-warning mr-1"></i> Disarankan mulai tanggal <b>H+1 (' + (lastInfo.suggested_start_date_formatted || '-') + ')</b> agar masa penandatanganan SK tidak terputus.');
                $('#modalActionModeContainer').removeClass('d-none');
                $('#modeRenewWrapper').removeClass('d-none');
                $('#modeRenew').prop('checked', true);
                setupMode('renew', lastInfo, false);
            } else {
                $('#modalPreviousHolderInfo').removeClass('d-none');
                $('#modalPrevHolderLabel').html('Pejabat Sebelumnya: <strong class="text-muted">Belum Pernah Ada Pejabat</strong>');
                $('#modalPrevPeriodText').text('Formasi jabatan ini belum memiliki riwayat pejabat sebelumnya.');
                $('#modalAntiGapHint').html('<i class="fas fa-info-circle text-info mr-1"></i> Silakan tetapkan pejabat pertama untuk jabatan ini.');
                $('#modalActionModeContainer').addClass('d-none');
                $('#modeRenewWrapper').addClass('d-none');
                setupMode('new', null, false);
            }
        }

        showModal('penugasanModal');
    });

    // General Button Create Penugasan (Header)
    $('#btnCreatePenugasan').click(function(e) {
        e.preventDefault();
        $('#penugasanModalTitle').html('<i class="fas fa-user-plus text-primary mr-2"></i>Tetapkan Penugasan Jabatan Guru');
        $('#penugasanForm')[0].reset();
        if ($.fn.select2) {
            $('#penugasanGuruIds').val(null).trigger('change');
        }
        $('#penugasanMethod').val('POST');
        $('#penugasanId').val('');
        $('#penugasanForm').data('action-url', "{{ route('guru-jabatan.store') }}");
        $('#penugasanForm').data('current-last-info', null);
        $('#penugasanForm').data('current-is-team', false);
        $('#existingSkFileContainer').addClass('d-none');

        $('#modalGuidanceBox').addClass('d-none');
        $('#jabatanSelectWrapper').removeClass('d-none');
        $('#guruFieldWrapper').removeClass('col-md-12').addClass('col-md-6');
        $('#guruLockedBox').addClass('d-none');
        $('#penugasanGuruId').prop('disabled', false).val('');
        $('#guruDropdownContainer').removeClass('d-none');
        $('#guruMultiDropdownContainer').addClass('d-none');
        $('#penugasanGuruIds').prop('disabled', true);
        $('#replaceActiveContainer').removeClass('d-none');
        $('#penugasanReplaceActive').prop('checked', true);

        applyPeriodPreset('tahun_anggaran', new Date().getFullYear());
        showModal('penugasanModal');
    });

    // Auto-suggest preset when general dropdown jabatan changes
    $('#penugasanJabatanId').on('change', function() {
        if ($('#penugasanMethod').val() === 'POST' && $('#jabatanSelectWrapper').is(':visible')) {
            let opt = $(this).find('option:selected');
            let isTeam = opt.data('is-team') == '1';
            let text = opt.text().toLowerCase();

            $('#penugasanForm').data('current-is-team', isTeam);

            if (isTeam) {
                $('#guruFieldLabel').html('Pilih Guru Anggota Tim <span class="text-danger">*</span>');
                $('#guruDropdownContainer').addClass('d-none');
                $('#guruMultiDropdownContainer').removeClass('d-none');
                $('#penugasanGuruId').prop('disabled', true);
                $('#penugasanGuruIds').prop('disabled', false);
                $('#replaceActiveContainer').addClass('d-none');
                $('#penugasanReplaceActive').prop('checked', false);
                $('#penugasanNotes').val('Penetapan anggota tim');
            } else {
                $('#guruFieldLabel').html('Pejabat / Guru <span class="text-danger">*</span>');
                $('#guruDropdownContainer').removeClass('d-none');
                $('#guruMultiDropdownContainer').addClass('d-none');
                $('#penugasanGuruId').prop('disabled', false);
                $('#penugasanGuruIds').prop('disabled', true);
                $('#replaceActiveContainer').removeClass('d-none');
                $('#penugasanReplaceActive').prop('checked', true);
                $('#penugasanNotes').val('');
            }

            if (text.includes('kepala sekolah') || text.includes('kepsek')) {
                applyPeriodPreset('multi_tahun');
            } else if (text.includes('waka') || text.includes('wali')) {
                applyPeriodPreset('tahun_pelajaran');
            } else if (text.includes('bendahara') || text.includes('bos') || text.includes('bpopp')) {
                applyPeriodPreset('tahun_anggaran');
            }
        }
    });

    // Edit Single SK Penugasan dari Riwayat
    $(document).on('click', '.btn-edit-penugasan', function(e) {
        e.preventDefault();
        let $btn = $(this);
        let json = $btn.data('json');
        if (typeof json === 'string') {
            try { json = JSON.parse(json); } catch (err) {}
        }
        let updateUrl = $btn.data('update-url');

        $('#penugasanModalTitle').html('<i class="fas fa-edit text-warning mr-2"></i>Edit Dokumen SK Penugasan');
        $('#penugasanForm')[0].reset();
        $('#penugasanMethod').val('PUT');
        $('#penugasanId').val(json.id);
        $('#penugasanForm').data('action-url', updateUrl || ("{{ url('master/guru-jabatan') }}/" + json.id));
        $('#penugasanForm').data('current-is-team', false);

        $('#modalGuidanceBox').addClass('d-none');
        $('#jabatanSelectWrapper').removeClass('d-none');
        $('#guruFieldWrapper').removeClass('col-md-12').addClass('col-md-6');
        $('#guruFieldLabel').html('Pejabat / Guru <span class="text-danger">*</span>');
        $('#guruLockedBox').addClass('d-none');
        $('#penugasanGuruId').prop('disabled', false).val(json.guru_id);
        $('#guruDropdownContainer').removeClass('d-none');
        $('#guruMultiDropdownContainer').addClass('d-none');
        $('#penugasanGuruIds').prop('disabled', true);
        $('#replaceActiveContainer').addClass('d-none');

        $('#penugasanJabatanId').val(json.jabatan_id);
        $('#penugasanSkNumber').val(json.sk_number === '-' ? '' : json.sk_number);
        $('#penugasanSkDate').val(json.sk_date_raw || (json.sk_date ? json.sk_date.substring(0, 10) : ''));
        $('#penugasanPeriodYear').val(json.period_year);
        $('#penugasanStartDate').val(json.start_date_raw || (json.start_date ? json.start_date.substring(0, 10) : ''));
        $('#penugasanEndDate').val(json.end_date_raw || (json.end_date ? json.end_date.substring(0, 10) : ''));
        $('#penugasanNotes').val(json.notes);

        let pType = json.period_type || 'tahun_anggaran';
        $('#penugasanPeriodType').val(pType);
        $('.btn-period-preset').removeClass('active');
        $('.btn-period-preset[data-type="' + pType + '"]').addClass('active');

        if (json.sk_file_url) {
            $('#existingSkFileContainer').removeClass('d-none');
            $('#existingSkFileLink').attr('href', json.sk_file_url);
        } else {
            $('#existingSkFileContainer').addClass('d-none');
        }

        showModal('penugasanModal');
    });

    // Form Submit (Create / Update / Renew / Replace / Add Team)
    $('#penugasanForm').submit(function(e) {
        e.preventDefault();
        let method = $('#penugasanMethod').val();
        let id = $('#penugasanId').val();
        let url = $('#penugasanForm').data('action-url') || (method === 'POST' ? "{{ route('guru-jabatan.store') }}" : "{{ url('master/guru-jabatan') }}/" + id);

        let formData = new FormData(this);
        if (method === 'PUT') {
            formData.set('_method', 'PUT');
        }

        // Handle Guru ID jika mode renew (dropdown disable)
        if ($('#modeRenew').is(':checked') && $('#guruLockedId').val()) {
            formData.set('guru_id', $('#guruLockedId').val());
        }

        if ($('#penugasanReplaceActive').length && $('#penugasanReplaceActive').is(':checked') && !$('#replaceActiveContainer').hasClass('d-none')) {
            formData.set('replace_active', '1');
        } else {
            formData.delete('replace_active');
        }

        // Frontend validation: pastikan guru dipilih
        let isTeam = $('#penugasanForm').data('current-is-team') || false;
        if (method === 'POST') {
            if (isTeam || !$('#guruMultiDropdownContainer').hasClass('d-none')) {
                let selectedGurus = $('#penugasanGuruIds').val();
                if (!selectedGurus || selectedGurus.length === 0) {
                    Swal.fire('Perhatian', 'Pilih setidaknya satu guru anggota tim.', 'warning');
                    return;
                }
            } else if (!$('#guruDropdownContainer').hasClass('d-none') && !$('#penugasanGuruId').val() && !$('#guruLockedId').val()) {
                Swal.fire('Perhatian', 'Silakan pilih guru / pejabat.', 'warning');
                return;
            }
        }

        let submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(res) {
                hideModal('penugasanModal');
                table.draw(false);
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: res.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                setTimeout(function() {
                    location.reload();
                }, 1200);
            },
            error: function(xhr) {
                let msg = 'Terjadi kesalahan sistem.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                Swal.fire('Gagal!', msg, 'error');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan Penugasan');
            }
        });
    });

    // Quick Delete Penugasan
    $(document).on('click', '.btn-delete-penugasan', function(e) {
        e.preventDefault();
        let url = $(this).data('url');

        Swal.fire({
            title: 'Hapus Penugasan?',
            text: 'Apakah Anda yakin ingin menghapus data penetapan penugasan SK ini?',
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
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    },
                    error: function() {
                        Swal.fire('Gagal!', 'Terjadi kesalahan saat menghapus data.', 'error');
                    }
                });
            }
        });
    });
});
</script>
@stop
