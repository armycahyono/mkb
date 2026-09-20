@extends('adminlte::page')

@section('title', 'Profil Sekolah')

@section('plugins.Sweetalert2', true)

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap" style="padding: 2px 0;">
    <div>
        <h1 class="page-title"><i class="fas fa-school text-primary mr-1"></i> Profil Sekolah</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-house"></i> Dashboard</a></li>
                <li class="breadcrumb-item">Data Pokok</li>
                <li class="breadcrumb-item active">Profil Sekolah</li>
            </ol>
        </nav>
    </div>
    <div class="header-actions d-flex align-items-center">
        <a href="{{ route('dashboard') }}" class="btn btn-sm btn-secondary mr-2">
            <i class="fas fa-arrow-left mr-1"></i> Dashboard
        </a>
        <button type="submit" form="formProfile" class="btn btn-sm btn-primary shadow-sm" id="btnSaveProfileTop">
            <i class="fas fa-save mr-1"></i> Simpan Perubahan
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
    ::placeholder, .form-control::placeholder {
        color: #94a3b8 !important;
        opacity: 0.75 !important;
        font-weight: 400 !important;
    }
    .form-section-title {
        font-size: 0.88rem;
        font-weight: 700;
        color: #1e293b;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 6px;
        margin-bottom: 14px;
    }
    .card {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        margin-bottom: 1.25rem;
    }
    .card-header {
        background-color: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 0.65rem 1rem;
    }
    .card-title {
        font-size: 0.875rem !important;
        font-weight: 700 !important;
        color: #1e293b;
        margin: 0;
    }

    /* Media Upload Preview Boxes */
    .media-upload-card {
        background-color: #f8fafc;
        border: 1.5px dashed #cbd5e1;
        border-radius: 8px;
        padding: 12px;
        text-align: center;
        transition: all 0.2s ease-in-out;
        position: relative;
    }
    .media-upload-card:hover {
        border-color: #3b82f6;
        background-color: #f1f5f9;
    }
    .media-preview-box {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        min-height: 100px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 8px;
        margin-bottom: 10px;
        overflow: hidden;
    }
    .media-preview-box img {
        max-height: 90px;
        max-width: 100%;
        object-fit: contain;
    }
    .media-preview-box.letterhead-box {
        min-height: 80px;
    }
    .media-preview-box.letterhead-box img {
        max-height: 70px;
        max-width: 100%;
        object-fit: contain;
    }

    /* Nav Pills Tab Styling */
    .nav-pills-custom .nav-link {
        font-size: 0.8125rem;
        font-weight: 600;
        color: #64748b;
        border-radius: 6px;
        padding: 6px 14px;
        transition: all 0.15s;
    }
    .nav-pills-custom .nav-link.active {
        background-color: #3b82f6;
        color: #ffffff;
        box-shadow: 0 1px 3px rgba(59, 130, 246, 0.3);
    }
</style>
@stop

@section('content')
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2 px-3 mb-3 border-0 shadow-sm" role="alert">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show py-2 px-3 mb-3 border-0 shadow-sm" role="alert">
        <div class="font-weight-bold mb-1">
            <i class="fas fa-exclamation-triangle mr-1"></i> Terdapat kesalahan pengisian:
        </div>
        <ul class="mb-0 pl-3 small">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif

    <form id="formProfile" action="{{ route('school.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <input type="hidden" name="active_tab" id="active_tab" value="{{ old('active_tab', '#tab-bosp') }}">

        <div class="row">
            {{-- KOLOM KIRI: Identitas, Kontak & Legalitas --}}
            <div class="col-lg-7">
                {{-- Card 1: Identitas & Kelembagaan --}}
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-header bg-white py-2 px-3">
                        <h3 class="card-title">
                            <i class="fas fa-university text-primary mr-1"></i> Identitas &amp; Kelembagaan Sekolah
                        </h3>
                    </div>
                    <div class="card-body p-3">
                        <div class="form-section-title">
                            <i class="fas fa-id-card text-info mr-1"></i> Data Pokok Sekolah
                        </div>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold">Nama Resmi Sekolah <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control form-control-sm @error('name') is-invalid @enderror"
                                           value="{{ old('name', $profile->name) }}" placeholder="Contoh: SMKS PGRI KRAS" required>
                                    @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold">NPSN <span class="text-muted">(8 Digit)</span></label>
                                    <input type="text" name="npsn" class="form-control form-control-sm font-tabular @error('npsn') is-invalid @enderror"
                                           value="{{ old('npsn', $profile->npsn) }}" placeholder="Contoh: 20511923" maxlength="20">
                                    @error('npsn') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold">Status Sekolah</label>
                                    <select name="school_status" class="form-control form-control-sm @error('school_status') is-invalid @enderror">
                                        <option value="">-- Pilih Status --</option>
                                        @foreach(['Swasta', 'Negeri'] as $opt)
                                            <option value="{{ $opt }}" {{ old('school_status', $profile->school_status) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                        @endforeach
                                    </select>
                                    @error('school_status') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold">Bentuk Kepemilikan</label>
                                    <select name="ownership" class="form-control form-control-sm @error('ownership') is-invalid @enderror">
                                        <option value="">-- Pilih Kepemilikan --</option>
                                        @foreach(['Yayasan', 'Pemerintah Pusat', 'Pemerintah Daerah', 'Lainnya'] as $opt)
                                            <option value="{{ $opt }}" {{ old('ownership', $profile->ownership) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                        @endforeach
                                    </select>
                                    @error('ownership') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold">Tahun Berdiri</label>
                                    <input type="number" name="established_year" class="form-control form-control-sm font-tabular @error('established_year') is-invalid @enderror"
                                           value="{{ old('established_year', $profile->established_year) }}" min="1900" max="{{ date('Y') }}" placeholder="Tahun (YYYY)">
                                    @error('established_year') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold">Nama Yayasan Penyelenggara</label>
                                    <input type="text" name="foundation_name" class="form-control form-control-sm @error('foundation_name') is-invalid @enderror"
                                           value="{{ old('foundation_name', $profile->foundation_name) }}" placeholder="Contoh: YPLP DASMEN PGRI KEDIRI">
                                    @error('foundation_name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold">Kurikulum Pembelajaran</label>
                                    <input type="text" name="curriculum" class="form-control form-control-sm @error('curriculum') is-invalid @enderror"
                                           value="{{ old('curriculum', $profile->curriculum) }}" placeholder="Contoh: Kurikulum Merdeka">
                                    @error('curriculum') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-section-title mt-3">
                            <i class="fas fa-map-marker-alt text-danger mr-1"></i> Kontak &amp; Domisili Resmi
                        </div>

                        <div class="form-group mb-2">
                            <label class="small font-weight-bold">Alamat Lengkap Sekolah</label>
                            <textarea name="address" rows="2" class="form-control form-control-sm @error('address') is-invalid @enderror"
                                      placeholder="Jalan, RT/RW, Dusun, Desa/Kelurahan, Kecamatan, Kabupaten/Kota, Kode Pos">{{ old('address', $profile->address) }}</textarea>
                            @error('address') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold">No. Telepon / Fax</label>
                                    <input type="text" name="phone" class="form-control form-control-sm font-tabular @error('phone') is-invalid @enderror"
                                           value="{{ old('phone', $profile->phone) }}" placeholder="Contoh: (0354) 4740750">
                                    @error('phone') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold">Email Resmi Sekolah</label>
                                    <input type="email" name="email" class="form-control form-control-sm @error('email') is-invalid @enderror"
                                           value="{{ old('email', $profile->email) }}" placeholder="smk_pgri_kras007@yahoo.co.id">
                                    @error('email') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold">Laman / Website</label>
                                    <input type="text" name="website" class="form-control form-control-sm @error('website') is-invalid @enderror"
                                           value="{{ old('website', $profile->website) }}" placeholder="www.smkpgrikras.sch.id">
                                    @error('website') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card 2: Legalitas & Akreditasi --}}
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-header bg-white py-2 px-3">
                        <h3 class="card-title">
                            <i class="fas fa-certificate text-warning mr-1"></i> Legalitas &amp; Status Akreditasi
                        </h3>
                    </div>
                    <div class="card-body p-3">
                        <div class="row">
                            <div class="col-md-7">
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold">Nomor SK Pendirian Sekolah</label>
                                    <input type="text" name="sk_pendirian" class="form-control form-control-sm @error('sk_pendirian') is-invalid @enderror"
                                           value="{{ old('sk_pendirian', $profile->sk_pendirian) }}" placeholder="Contoh: 421.5/123/418.47/2010">
                                    @error('sk_pendirian') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold">Tanggal SK Pendirian</label>
                                    <input type="date" name="sk_date" class="form-control form-control-sm @error('sk_date') is-invalid @enderror"
                                           value="{{ old('sk_date', $profile->sk_date ? $profile->sk_date->format('Y-m-d') : '') }}">
                                    @error('sk_date') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold">Peringkat Akreditasi</label>
                                    <input type="text" name="accreditation" class="form-control form-control-sm @error('accreditation') is-invalid @enderror"
                                           value="{{ old('accreditation', $profile->accreditation) }}" placeholder="Contoh: A / Unggul / B">
                                    @error('accreditation') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold">Tahun Penetapan Akreditasi</label>
                                    <input type="number" name="accreditation_year" class="form-control form-control-sm font-tabular @error('accreditation_year') is-invalid @enderror"
                                           value="{{ old('accreditation_year', $profile->accreditation_year) }}" min="1900" max="{{ date('Y') }}" placeholder="Tahun (YYYY)">
                                    @error('accreditation_year') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN: Rekening Bank, Logo & Kop Surat --}}
            <div class="col-lg-5">
                {{-- Card 3: Rekening Kas Bank Sekolah (BOSP & BPOPP) --}}
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center">
                        <h3 class="card-title">
                            <i class="fas fa-wallet text-success mr-1"></i> Rekening Kas Bank Sekolah
                        </h3>
                        <span class="badge badge-light border text-muted small">SPJ &amp; Kuasa Bank</span>
                    </div>
                    <div class="card-body p-3">
                        <p class="text-muted small mb-3">
                            Informasi rekening ini digunakan otomatis pada berkas <strong>Surat Kuasa Pemindahbukuan</strong>, <strong>Kwitansi</strong>, serta lampiran SPJ keuangan.
                        </p>

                        <ul class="nav nav-pills nav-pills-custom nav-fill mb-3 bg-light p-1 rounded" id="bankTab" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="bosp-tab" data-toggle="pill" data-bs-toggle="pill" href="#tab-bosp" role="tab" aria-controls="tab-bosp" aria-selected="true">
                                    <i class="fas fa-money-bill-wave mr-1 text-primary"></i> Kas BOSP
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="bpopp-tab" data-toggle="pill" data-bs-toggle="pill" href="#tab-bpopp" role="tab" aria-controls="tab-bpopp" aria-selected="false">
                                    <i class="fas fa-hand-holding-usd mr-1 text-success"></i> Kas BPOPP
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content" id="bankTabContent">
                            {{-- Tab BOSP --}}
                            <div class="tab-pane fade show active" id="tab-bosp" role="tabpanel" aria-labelledby="bosp-tab">
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold">Nama Bank BOSP</label>
                                    <input type="text" name="bosp_bank_name" id="bosp_bank_name" class="form-control form-control-sm"
                                           value="{{ old('bosp_bank_name', $profile->bosp_bank_name) }}" placeholder="PT. BANK PEMBANGUNAN DAERAH JAWA TIMUR">
                                </div>
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold">Kantor Cabang Bank</label>
                                    <input type="text" name="bosp_bank_branch" id="bosp_bank_branch" class="form-control form-control-sm"
                                           value="{{ old('bosp_bank_branch', $profile->bosp_bank_branch) }}" placeholder="Contoh: CABANG KEDIRI">
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-2">
                                            <label class="small font-weight-bold">Nomor Rekening BOSP</label>
                                            <input type="text" name="bosp_account_number" id="bosp_account_number" class="form-control form-control-sm font-tabular"
                                                   value="{{ old('bosp_account_number', $profile->bosp_account_number) }}" placeholder="1001003336">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-2">
                                            <label class="small font-weight-bold">Atas Nama Rekening</label>
                                            <input type="text" name="bosp_account_name" id="bosp_account_name" class="form-control form-control-sm"
                                                   value="{{ old('bosp_account_name', $profile->bosp_account_name) }}" placeholder="20511923 SMKS PGRI KRAS (BOSP)">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group mb-0">
                                    <label class="small font-weight-bold">Alamat Kantor Cabang Bank</label>
                                    <input type="text" name="bosp_bank_address" id="bosp_bank_address" class="form-control form-control-sm"
                                           value="{{ old('bosp_bank_address', $profile->bosp_bank_address) }}" placeholder="Jl. PK. Bangsa No. 122 Kediri">
                                </div>
                            </div>

                            {{-- Tab BPOPP --}}
                            <div class="tab-pane fade" id="tab-bpopp" role="tabpanel" aria-labelledby="bpopp-tab">
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold">Nama Bank BPOPP</label>
                                    <input type="text" name="bpopp_bank_name" id="bpopp_bank_name" class="form-control form-control-sm"
                                           value="{{ old('bpopp_bank_name', $profile->bpopp_bank_name) }}" placeholder="PT. BANK PEMBANGUNAN DAERAH JAWA TIMUR">
                                </div>
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold">Kantor Cabang Bank</label>
                                    <input type="text" name="bpopp_bank_branch" id="bpopp_bank_branch" class="form-control form-control-sm"
                                           value="{{ old('bpopp_bank_branch', $profile->bpopp_bank_branch) }}" placeholder="Contoh: CABANG KEDIRI">
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-2">
                                            <label class="small font-weight-bold">Nomor Rekening BPOPP</label>
                                            <input type="text" name="bpopp_account_number" id="bpopp_account_number" class="form-control form-control-sm font-tabular"
                                                   value="{{ old('bpopp_account_number', $profile->bpopp_account_number) }}" placeholder="1001001236">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-2">
                                            <label class="small font-weight-bold">Atas Nama Rekening</label>
                                            <input type="text" name="bpopp_account_name" id="bpopp_account_name" class="form-control form-control-sm"
                                                   value="{{ old('bpopp_account_name', $profile->bpopp_account_name) }}" placeholder="SMKS PGRI KRAS KEDIRI (BPOPP)">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group mb-0">
                                    <label class="small font-weight-bold">Alamat Kantor Cabang Bank</label>
                                    <input type="text" name="bpopp_bank_address" id="bpopp_bank_address" class="form-control form-control-sm"
                                           value="{{ old('bpopp_bank_address', $profile->bpopp_bank_address) }}" placeholder="Jl. PK. Bangsa No. 122 Kediri">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card 4: Identitas Visual & Berkas Kop Surat --}}
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-header bg-white py-2 px-3">
                        <h3 class="card-title">
                            <i class="fas fa-images text-info mr-1"></i> Identitas Visual &amp; Kop Surat
                        </h3>
                    </div>
                    <div class="card-body p-3">
                        {{-- Bagian 1: Logo Sekolah & Yayasan --}}
                        <div class="form-section-title">
                            <i class="fas fa-shield-alt text-primary mr-1"></i> Logo Lembaga
                        </div>
                        <div class="row mb-3">
                            {{-- Logo Sekolah --}}
                            <div class="col-6">
                                <div class="media-upload-card @error('logo_school') border-danger @enderror">
                                    <div class="small font-weight-bold text-dark mb-1">Logo Sekolah</div>
                                    <div class="media-preview-box" id="box_preview_logo_school">
                                        @if($profile->logo_school)
                                            <img src="{{ asset('storage/' . $profile->logo_school) }}" alt="Logo Sekolah" id="preview_logo_school">
                                        @else
                                            <img src="" alt="Logo Sekolah" id="preview_logo_school" class="d-none">
                                            <span class="text-muted" id="placeholder_logo_school"><i class="fas fa-school fa-2x"></i></span>
                                        @endif
                                    </div>
                                    <div id="info_logo_school" class="small text-info font-weight-bold mb-1 d-none" style="font-size: 0.7rem; word-break: break-all;"></div>
                                    @error('logo_school')
                                        <div class="text-danger small font-weight-bold mb-1" style="font-size: 0.72rem;">{{ $message }}</div>
                                    @enderror
                                    <div class="d-flex justify-content-center">
                                        <label for="logo_school" class="btn btn-xs btn-outline-primary mb-0 mr-1" title="Unggah Logo">
                                            <i class="fas fa-upload mr-1"></i> Unggah
                                        </label>
                                        <input type="file" name="logo_school" id="logo_school" class="d-none" accept="image/*"
                                               onchange="previewMedia(this, 'logo_school', 'preview_logo_school', 'placeholder_logo_school', 'info_logo_school', 'btn_remove_logo_school')">
                                        <input type="hidden" name="remove_logo_school" id="remove_logo_school" value="0">
                                        <button type="button" class="btn btn-xs btn-outline-danger {{ $profile->logo_school ? '' : 'd-none' }}" id="btn_remove_logo_school"
                                                onclick="markMediaRemoved('logo_school', 'preview_logo_school', 'placeholder_logo_school', 'info_logo_school', 'btn_remove_logo_school', 'logo_school')" title="Hapus Logo">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                    <div class="text-muted mt-1" style="font-size: 0.68rem;">PNG, JPG, WEBP, SVG (maks 10MB)</div>
                                </div>
                            </div>

                            {{-- Logo Yayasan --}}
                            <div class="col-6">
                                <div class="media-upload-card @error('logo_foundation') border-danger @enderror">
                                    <div class="small font-weight-bold text-dark mb-1">Logo Yayasan</div>
                                    <div class="media-preview-box" id="box_preview_logo_foundation">
                                        @if($profile->logo_foundation)
                                            <img src="{{ asset('storage/' . $profile->logo_foundation) }}" alt="Logo Yayasan" id="preview_logo_foundation">
                                        @else
                                            <img src="" alt="Logo Yayasan" id="preview_logo_foundation" class="d-none">
                                            <span class="text-muted" id="placeholder_logo_foundation"><i class="fas fa-building fa-2x"></i></span>
                                        @endif
                                    </div>
                                    <div id="info_logo_foundation" class="small text-info font-weight-bold mb-1 d-none" style="font-size: 0.7rem; word-break: break-all;"></div>
                                    @error('logo_foundation')
                                        <div class="text-danger small font-weight-bold mb-1" style="font-size: 0.72rem;">{{ $message }}</div>
                                    @enderror
                                    <div class="d-flex justify-content-center">
                                        <label for="logo_foundation" class="btn btn-xs btn-outline-primary mb-0 mr-1" title="Unggah Logo">
                                            <i class="fas fa-upload mr-1"></i> Unggah
                                        </label>
                                        <input type="file" name="logo_foundation" id="logo_foundation" class="d-none" accept="image/*"
                                               onchange="previewMedia(this, 'logo_foundation', 'preview_logo_foundation', 'placeholder_logo_foundation', 'info_logo_foundation', 'btn_remove_logo_foundation')">
                                        <input type="hidden" name="remove_logo_foundation" id="remove_logo_foundation" value="0">
                                        <button type="button" class="btn btn-xs btn-outline-danger {{ $profile->logo_foundation ? '' : 'd-none' }}" id="btn_remove_logo_foundation"
                                                onclick="markMediaRemoved('logo_foundation', 'preview_logo_foundation', 'placeholder_logo_foundation', 'info_logo_foundation', 'btn_remove_logo_foundation', 'logo_foundation')" title="Hapus Logo">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                    <div class="text-muted mt-1" style="font-size: 0.68rem;">PNG, JPG, WEBP, SVG (maks 10MB)</div>
                                </div>
                            </div>
                        </div>

                        {{-- Bagian 2: Kop Surat Resmi (Letterhead) --}}
                        <div class="form-section-title">
                            <i class="fas fa-file-image text-success mr-1"></i> Berkas Banner Kop Surat Resmi
                        </div>

                        {{-- Kop Surat Portrait --}}
                        <div class="media-upload-card mb-2 @error('letterhead_portrait') border-danger @enderror">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small font-weight-bold text-dark">Kop Surat Portrait (A4 Tegak)</span>
                                <span class="badge badge-light border text-info" style="font-size: 0.68rem;">Surat Kuasa Bank &amp; SPJ</span>
                            </div>
                            <div class="media-preview-box letterhead-box" id="box_preview_letterhead_portrait">
                                @if($profile->letterhead_portrait)
                                    <img src="{{ asset('storage/' . $profile->letterhead_portrait) }}" alt="Kop Surat Portrait" id="preview_letterhead_portrait">
                                @else
                                    <img src="" alt="Kop Surat Portrait" id="preview_letterhead_portrait" class="d-none">
                                    <span class="text-muted" id="placeholder_letterhead_portrait"><i class="fas fa-image fa-2x mr-1"></i> <span class="small">Belum ada berkas</span></span>
                                @endif
                            </div>
                            <div id="info_letterhead_portrait" class="small text-info font-weight-bold mb-1 d-none" style="font-size: 0.7rem; word-break: break-all;"></div>
                            @error('letterhead_portrait')
                                <div class="text-danger small font-weight-bold mb-1" style="font-size: 0.72rem;">{{ $message }}</div>
                            @enderror
                            <div class="d-flex justify-content-between align-items-center flex-wrap">
                                <span class="text-muted" style="font-size: 0.68rem;">Rasio lebar:tinggi ± 5:1 (PNG/JPG/WEBP/SVG maks 10MB)</span>
                                <div class="mt-1 mt-sm-0">
                                    <label for="letterhead_portrait" class="btn btn-xs btn-outline-primary mb-0 mr-1">
                                        <i class="fas fa-upload mr-1"></i> Unggah Kop
                                    </label>
                                    <input type="file" name="letterhead_portrait" id="letterhead_portrait" class="d-none" accept="image/*"
                                           onchange="previewMedia(this, 'letterhead_portrait', 'preview_letterhead_portrait', 'placeholder_letterhead_portrait', 'info_letterhead_portrait', 'btn_remove_letterhead_portrait')">
                                    <input type="hidden" name="remove_letterhead_portrait" id="remove_letterhead_portrait" value="0">
                                    <button type="button" class="btn btn-xs btn-outline-danger {{ $profile->letterhead_portrait ? '' : 'd-none' }}" id="btn_remove_letterhead_portrait"
                                            onclick="markMediaRemoved('letterhead_portrait', 'preview_letterhead_portrait', 'placeholder_letterhead_portrait', 'info_letterhead_portrait', 'btn_remove_letterhead_portrait', 'letterhead_portrait')" title="Hapus Kop">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Kop Surat Landscape --}}
                        <div class="media-upload-card @error('letterhead_landscape') border-danger @enderror">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small font-weight-bold text-dark">Kop Surat Landscape (A4 Mendatar)</span>
                                <span class="badge badge-light border text-warning" style="font-size: 0.68rem;">Kwitansi &amp; Dokumen Lebar</span>
                            </div>
                            <div class="media-preview-box letterhead-box" id="box_preview_letterhead_landscape">
                                @if($profile->letterhead_landscape)
                                    <img src="{{ asset('storage/' . $profile->letterhead_landscape) }}" alt="Kop Surat Landscape" id="preview_letterhead_landscape">
                                @else
                                    <img src="" alt="Kop Surat Landscape" id="preview_letterhead_landscape" class="d-none">
                                    <span class="text-muted" id="placeholder_letterhead_landscape"><i class="fas fa-image fa-2x mr-1"></i> <span class="small">Belum ada berkas</span></span>
                                @endif
                            </div>
                            <div id="info_letterhead_landscape" class="small text-info font-weight-bold mb-1 d-none" style="font-size: 0.7rem; word-break: break-all;"></div>
                            @error('letterhead_landscape')
                                <div class="text-danger small font-weight-bold mb-1" style="font-size: 0.72rem;">{{ $message }}</div>
                            @enderror
                            <div class="d-flex justify-content-between align-items-center flex-wrap">
                                <span class="text-muted" style="font-size: 0.68rem;">Rasio lebar:tinggi ± 7:1 (PNG/JPG/WEBP/SVG maks 10MB)</span>
                                <div class="mt-1 mt-sm-0">
                                    <label for="letterhead_landscape" class="btn btn-xs btn-outline-primary mb-0 mr-1">
                                        <i class="fas fa-upload mr-1"></i> Unggah Kop
                                    </label>
                                    <input type="file" name="letterhead_landscape" id="letterhead_landscape" class="d-none" accept="image/*"
                                           onchange="previewMedia(this, 'letterhead_landscape', 'preview_letterhead_landscape', 'placeholder_letterhead_landscape', 'info_letterhead_landscape', 'btn_remove_letterhead_landscape')">
                                    <input type="hidden" name="remove_letterhead_landscape" id="remove_letterhead_landscape" value="0">
                                    <button type="button" class="btn btn-xs btn-outline-danger {{ $profile->letterhead_landscape ? '' : 'd-none' }}" id="btn_remove_letterhead_landscape"
                                            onclick="markMediaRemoved('letterhead_landscape', 'preview_letterhead_landscape', 'placeholder_letterhead_landscape', 'info_letterhead_landscape', 'btn_remove_letterhead_landscape', 'letterhead_landscape')" title="Hapus Kop">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card 5: Panel Aksi Simpan Bawah --}}
                <div class="card shadow-sm border-0">
                    <div class="card-body p-3 d-flex justify-content-between align-items-center flex-wrap">
                        <div class="text-muted small mb-2 mb-sm-0">
                            @if($profile->updated_at)
                                <i class="fas fa-history mr-1"></i> Terakhir diperbarui: <strong>{{ $profile->updated_at->isoFormat('D MMMM Y, HH:mm') }}</strong>
                            @else
                                <i class="fas fa-info-circle mr-1"></i> Data profil baru
                            @endif
                        </div>
                        <div>
                            <a href="{{ route('dashboard') }}" class="btn btn-sm btn-secondary mr-1">
                                <i class="fas fa-times mr-1"></i> Batal
                            </a>
                            <button type="submit" class="btn btn-sm btn-primary shadow-sm" id="btnSaveProfileBottom">
                                <i class="fas fa-save mr-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@stop

@section('js')
<script>
    function previewMedia(input, field, previewId, placeholderId, infoId, btnId) {
        if (!input.files || !input.files[0]) return;

        var file = input.files[0];

        // Ensure remove flag is reset when a file is selected
        var removeInput = document.getElementById('remove_' + field);
        if (removeInput) removeInput.value = '0';

        // Client-side file size check (10MB)
        if (file.size > 10 * 1024 * 1024) {
            var sizeMb = (file.size / (1024 * 1024)).toFixed(1);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Ukuran Terlalu Besar',
                    text: 'Ukuran berkas (' + sizeMb + ' MB) melebihi batas 10 MB. Silakan pilih berkas yang lebih kecil.'
                });
            } else {
                alert('Ukuran berkas (' + sizeMb + ' MB) melebihi batas 10 MB. Silakan pilih berkas yang lebih kecil.');
            }
            input.value = '';
            return;
        }

        var preview = document.getElementById(previewId);
        var placeholder = placeholderId ? document.getElementById(placeholderId) : null;
        var info = infoId ? document.getElementById(infoId) : null;
        var btn = btnId ? document.getElementById(btnId) : null;

        var reader = new FileReader();
        reader.onload = function(e) {
            if (preview) {
                preview.src = e.target.result;
                preview.classList.remove('d-none');
            }
            if (placeholder) placeholder.classList.add('d-none');
            if (info) {
                var sizeKb = (file.size / 1024).toFixed(0);
                info.innerHTML = '<i class="fas fa-check-circle text-success mr-1"></i>' + file.name + ' (' + sizeKb + ' KB)';
                info.classList.remove('d-none');
            }
            if (btn) btn.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    }

    function markMediaRemoved(field, previewId, placeholderId, infoId, btnId, inputId) {
        var fileInput = document.getElementById(inputId);
        var hasLocalFile = fileInput && fileInput.files && fileInput.files.length > 0;

        var message = hasLocalFile
            ? 'Batalkan pilihan berkas yang baru dipilih ini?'
            : 'Apakah Anda yakin ingin menghapus berkas ini? Perubahan akan diterapkan setelah klik Simpan.';

        if (confirm(message)) {
            var removeInput = document.getElementById('remove_' + field);
            if (removeInput) removeInput.value = '1';

            if (fileInput) fileInput.value = '';

            var preview = document.getElementById(previewId);
            if (preview) {
                preview.src = '';
                preview.classList.add('d-none');
            }

            var placeholder = document.getElementById(placeholderId);
            if (placeholder) placeholder.classList.remove('d-none');

            var info = infoId ? document.getElementById(infoId) : null;
            if (info) {
                info.innerHTML = '';
                info.classList.add('d-none');
            }

            var btn = btnId ? document.getElementById(btnId) : null;
            if (btn) btn.classList.add('d-none');
        }
    }

    function showBankTab(targetHash) {
        if (!targetHash) return;
        if (targetHash === '#bpopp') targetHash = '#tab-bpopp';
        if (targetHash === '#bosp') targetHash = '#tab-bosp';

        var link = $('#bankTab a[href="' + targetHash + '"]');
        if (!link.length) return;

        // 1. Bootstrap tab plugin activation if available
        if (typeof link.tab === 'function') {
            link.tab('show');
        }

        // 2. Reliable DOM class toggling
        $('#bankTab a').removeClass('active').attr('aria-selected', 'false');
        link.addClass('active').attr('aria-selected', 'true');

        $('#bankTabContent .tab-pane').removeClass('show active');
        $(targetHash).addClass('show active');

        // 3. Keep hidden active_tab input in sync
        $('#active_tab').val(targetHash);
    }

    $(document).ready(function() {
        // Auto-activate tab from URL hash on initial page load
        var currentHash = window.location.hash;
        var urlParams = new URLSearchParams(window.location.search);
        var tabParam = urlParams.get('tab');

        if (currentHash && (currentHash === '#tab-bpopp' || currentHash === '#bpopp' || currentHash === '#tab-bosp' || currentHash === '#bosp')) {
            showBankTab(currentHash);
            var bankCard = $('#bankTab').closest('.card');
            if (bankCard.length) {
                setTimeout(function() {
                    bankCard[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 150);
            }
        } else if (tabParam) {
            if (!tabParam.startsWith('#')) tabParam = '#' + tabParam;
            showBankTab(tabParam);
        } else if ($('#active_tab').val() && $('#active_tab').val() !== '#tab-bosp') {
            showBankTab($('#active_tab').val());
        }

        // React when URL hash changes
        window.addEventListener('hashchange', function() {
            if (window.location.hash) {
                showBankTab(window.location.hash);
            }
        });

        // Tab click handler
        $('#bankTab a').on('click', function(e) {
            e.preventDefault();
            var targetHash = $(this).attr('href');
            showBankTab(targetHash);
            if (history.replaceState) {
                history.replaceState(null, null, targetHash);
            } else {
                window.location.hash = targetHash;
            }
        });

        // Top button triggers form submit
        $('#btnSaveProfileTop').on('click', function(e) {
            e.preventDefault();
            $('#formProfile')[0].requestSubmit ? $('#formProfile')[0].requestSubmit() : $('#formProfile').submit();
        });

        // Form submit feedback
        $('#formProfile').on('submit', function() {
            var btnTop = $('#btnSaveProfileTop');
            var btnBtm = $('#btnSaveProfileBottom');
            btnTop.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');
            btnBtm.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');
        });

        @if(session('success'))
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil Disimpan!',
                    text: "{{ session('success') }}",
                    timer: 2500,
                    showConfirmButton: false
                });
            }
        @endif
    });
</script>
@stop
