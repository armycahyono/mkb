@extends('adminlte::page')

@section('title', 'Edit Data Guru & Pegawai')

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap" style="padding: 2px 0;">
    <div>
        <h1 class="page-title"><i class="fas fa-user-edit text-warning mr-1"></i> Edit Data Guru: {{ $guru->full_name_with_title }}</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-house"></i> Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('guru.index') }}">Data Guru & Pegawai</a></li>
                <li class="breadcrumb-item active">Edit Guru</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="{{ route('guru.show', $guru->id) }}" class="btn btn-sm btn-info mr-1">
            <i class="fas fa-eye mr-1"></i> Lihat Profil
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
    .form-section-title {
        font-size: 0.88rem;
        font-weight: 700;
        color: #1e293b;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 6px;
        margin-bottom: 14px;
    }
    ::placeholder, .form-control::placeholder {
        color: #94a3b8 !important;
        opacity: 0.75 !important;
        font-weight: 400 !important;
    }
    .bank-item-row {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 10px;
        margin-bottom: 10px;
        transition: all 0.2s;
    }
    .bank-item-row:hover {
        border-color: #cbd5e1;
        background-color: #f1f5f9;
    }
</style>
@stop

@section('content')
<form action="{{ route('guru.update', $guru->id) }}" method="POST" id="guruForm">
    @csrf
    @method('PUT')

    @if (isset($errors) && $errors->any())
        <div class="alert alert-danger py-2 px-3 mb-3">
            <h6 class="font-weight-bold mb-1"><i class="fas fa-exclamation-triangle mr-1"></i> Terdapat kesalahan pengisian:</h6>
            <ul class="mb-0 pl-3">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        {{-- Kolom Kiri: Biodata Pribadi & Kepegawaian --}}
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body p-3">
                    <div class="form-section-title">
                        <i class="fas fa-id-card text-info mr-1"></i> Identitas Pribadi & Gelar
                    </div>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group mb-2">
                                <label class="small font-weight-bold">Gelar Depan</label>
                                <input type="text" name="title_prefix" class="form-control form-control-sm" placeholder="Contoh: Drs., Dr." value="{{ old('title_prefix', $guru->title_prefix) }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-2">
                                <label class="small font-weight-bold">Nama Lengkap (Tanpa Gelar) <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-sm" placeholder="Nama lengkap sesuai KTP/Ijazah" value="{{ old('name', $guru->name) }}" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group mb-2">
                                <label class="small font-weight-bold">Gelar Belakang</label>
                                <input type="text" name="title_suffix" class="form-control form-control-sm" placeholder="Contoh: S.Pd, M.Pd" value="{{ old('title_suffix', $guru->title_suffix) }}">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-2">
                                <label class="small font-weight-bold">NIP (Nomor Induk Pegawai)</label>
                                <input type="text" name="nip" class="form-control form-control-sm font-tabular" placeholder="18 digit NIP" value="{{ old('nip', $guru->nip) }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-2">
                                <label class="small font-weight-bold">NIK (No. KTP / Kependudukan)</label>
                                <input type="text" name="nik" class="form-control form-control-sm font-tabular" placeholder="16 digit NIK" value="{{ old('nik', $guru->nik) }}">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-2">
                                <label class="small font-weight-bold">NUPTK</label>
                                <input type="text" name="nuptk" class="form-control form-control-sm font-tabular" placeholder="16 digit NUPTK" value="{{ old('nuptk', $guru->nuptk) }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-2">
                                <label class="small font-weight-bold">NPWP</label>
                                <input type="text" name="npwp" class="form-control form-control-sm font-tabular" placeholder="Nomor Pokok Wajib Pajak" value="{{ old('npwp', $guru->npwp) }}">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group mb-2">
                                <label class="small font-weight-bold">Jenis Kelamin <span class="text-danger">*</span></label>
                                <select name="gender" class="form-control form-control-sm" required>
                                    <option value="L" {{ old('gender', $guru->gender) === 'L' ? 'selected' : '' }}>Laki-laki (L)</option>
                                    <option value="P" {{ old('gender', $guru->gender) === 'P' ? 'selected' : '' }}>Perempuan (P)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-2">
                                <label class="small font-weight-bold">Tempat Lahir</label>
                                <input type="text" name="birth_place" class="form-control form-control-sm" placeholder="Kota / Kabupaten" value="{{ old('birth_place', $guru->birth_place) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-2">
                                <label class="small font-weight-bold">Tanggal Lahir</label>
                                <input type="date" name="birth_date" class="form-control form-control-sm" value="{{ old('birth_date', $guru->birth_date ? $guru->birth_date->format('Y-m-d') : '') }}">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group mb-2">
                                <label class="small font-weight-bold">Agama</label>
                                <select name="religion" class="form-control form-control-sm">
                                    <option value="">-- Pilih Agama --</option>
                                    @foreach(['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Khonghucu'] as $rel)
                                        <option value="{{ $rel }}" {{ old('religion', $guru->religion) === $rel ? 'selected' : '' }}>{{ $rel }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-2">
                                <label class="small font-weight-bold">Status Kepegawaian <span class="text-danger">*</span></label>
                                <select name="employment_status" class="form-control form-control-sm" required>
                                    <option value="PNS" {{ old('employment_status', $guru->employment_status) === 'PNS' ? 'selected' : '' }}>PNS (Pegawai Negeri Sipil)</option>
                                    <option value="PPPK" {{ old('employment_status', $guru->employment_status) === 'PPPK' ? 'selected' : '' }}>PPPK</option>
                                    <option value="GTT" {{ old('employment_status', $guru->employment_status) === 'GTT' ? 'selected' : '' }}>GTT (Guru Tidak Tetap)</option>
                                    <option value="GTY" {{ old('employment_status', $guru->employment_status) === 'GTY' ? 'selected' : '' }}>GTY (Guru Tetap Yayasan)</option>
                                    <option value="PTY" {{ old('employment_status', $guru->employment_status) === 'PTY' ? 'selected' : '' }}>PTY (Pegawai Tetap Yayasan)</option>
                                    <option value="Honorer" {{ old('employment_status', $guru->employment_status) === 'Honorer' ? 'selected' : '' }}>Honorer</option>
                                    <option value="PTT" {{ old('employment_status', $guru->employment_status) === 'PTT' ? 'selected' : '' }}>PTT (Pegawai Tidak Tetap / TU)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-2">
                                <label class="small font-weight-bold">Pangkat / Golongan</label>
                                <input type="text" name="pangkat_golongan" class="form-control form-control-sm font-tabular" placeholder="Contoh: Penata / III.c" value="{{ old('pangkat_golongan', $guru->pangkat_golongan) }}">
                            </div>
                        </div>
                    </div>

                    <div class="form-section-title mt-3">
                        <i class="fas fa-map-marker-alt text-danger mr-1"></i> Kontak & Alamat
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-2">
                                <label class="small font-weight-bold">No. Telepon / WhatsApp</label>
                                <input type="text" name="phone" class="form-control form-control-sm font-tabular" placeholder="Contoh: 081234567890" value="{{ old('phone', $guru->phone) }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-2">
                                <label class="small font-weight-bold">Email</label>
                                <input type="email" name="email" class="form-control form-control-sm" placeholder="email@sekolah.sch.id" value="{{ old('email', $guru->email) }}">
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">Alamat Rumah Lengkap</label>
                        <textarea name="address" class="form-control form-control-sm" rows="2" placeholder="Jalan, RT/RW, Kelurahan, Kecamatan, Kota/Kab">{{ old('address', $guru->address) }}</textarea>
                    </div>

                    <div class="custom-control custom-switch mt-2">
                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', $guru->is_active) ? 'checked' : '' }}>
                        <label class="custom-control-label small font-weight-bold text-success" for="is_active">Status Aktif Bekerja di Sekolah</label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Kolom Kanan: Multi-Rekening Bank Dinamis --}}
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center" style="border-bottom: 1px solid #edf2f7;">
                    <h3 class="card-title font-weight-bold text-dark mb-0" style="font-size: 0.9rem;">
                        <i class="fas fa-money-check-alt text-success mr-1"></i> Rekening Bank Pembayaran
                    </h3>
                    <button type="button" class="btn btn-xs btn-outline-primary" id="btnAddAccount">
                        <i class="fas fa-plus mr-1"></i> Tambah Rekening
                    </button>
                </div>
                <div class="card-body p-3">
                    <p class="text-muted small mb-2">
                        Rekening ini akan digunakan saat pencairan pembayaran <strong>Perjalanan Dinas (SPPD)</strong>, <strong>Kasbon</strong>, atau transfer honorarium.
                    </p>

                    <div id="bankAccountsContainer">
                        @php
                            $accounts = $guru->bankAccounts;
                            if ($accounts->isEmpty()) {
                                $accounts = collect([new \App\Models\GuruBankAccount(['bank_name' => '', 'account_number' => '', 'account_holder' => $guru->name, 'is_primary' => true])]);
                            }
                        @endphp

                        @foreach($accounts as $index => $acc)
                            <div class="bank-item-row" data-index="{{ $index }}">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge badge-secondary font-weight-bold bank-row-label">Rekening #{{ $index + 1 }}</span>
                                    <button type="button" class="btn btn-link btn-xs text-danger btn-remove-account" title="Hapus rekening ini" style="{{ count($accounts) <= 1 ? 'display: none;' : '' }}">
                                        <i class="fas fa-trash-alt"></i> Hapus
                                    </button>
                                </div>
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold mb-1">Nama Bank</label>
                                    <input type="text" name="bank_accounts[{{ $index }}][bank_name]" class="form-control form-control-sm" placeholder="Contoh: Bank Jatim / BRI / BNI / Mandiri" value="{{ $acc->bank_name }}">
                                </div>
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold mb-1">Nomor Rekening</label>
                                    <input type="text" name="bank_accounts[{{ $index }}][account_number]" class="form-control form-control-sm font-tabular" placeholder="Nomor rekening (opsional)" value="{{ $acc->account_number }}">
                                </div>
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold mb-1">Nama Pemilik Rekening</label>
                                    <input type="text" name="bank_accounts[{{ $index }}][account_holder]" class="form-control form-control-sm" placeholder="Nama persis di buku tabungan" value="{{ $acc->account_holder }}">
                                </div>
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold mb-1">Keterangan / Keperluan</label>
                                    <input type="text" name="bank_accounts[{{ $index }}][notes]" class="form-control form-control-sm" placeholder="Contoh: Rekening Gaji / Sertifikasi / Pribadi" value="{{ $acc->notes }}">
                                </div>
                                <div class="custom-control custom-radio mt-1">
                                    <input type="radio" id="primary_{{ $index }}" name="primary_account_radio" class="custom-control-input radio-primary-account" value="{{ $index }}" {{ $acc->is_primary ? 'checked' : '' }}>
                                    <input type="hidden" name="bank_accounts[{{ $index }}][is_primary]" class="hidden-is-primary" value="{{ $acc->is_primary ? '1' : '0' }}">
                                    <label class="custom-control-label small font-weight-bold text-primary" for="primary_{{ $index }}">Jadikan Rekening Utama (Default Transfer)</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Tombol Aksi Simpan --}}
            <div class="card shadow-sm border-0">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <a href="{{ route('guru.index') }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-times mr-1"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-sm btn-warning px-4 font-weight-bold shadow-sm">
                        <i class="fas fa-save mr-1"></i> Perbarui Data Guru
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
@stop

@section('js')
<script>
$(document).ready(function() {
    let accountIndex = {{ count($accounts) }};

    $('#btnAddAccount').click(function() {
        let defaultHolder = $('input[name="name"]').val().trim();
        let html = `
            <div class="bank-item-row" data-index="${accountIndex}">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge badge-secondary font-weight-bold bank-row-label">Rekening #${accountIndex + 1}</span>
                    <button type="button" class="btn btn-link btn-xs text-danger btn-remove-account" title="Hapus rekening ini">
                        <i class="fas fa-trash-alt"></i> Hapus
                    </button>
                </div>
                <div class="form-group mb-2">
                    <label class="small font-weight-bold mb-1">Nama Bank</label>
                    <input type="text" name="bank_accounts[${accountIndex}][bank_name]" class="form-control form-control-sm" placeholder="Contoh: Bank Jatim / BRI / BNI / Mandiri">
                </div>
                <div class="form-group mb-2">
                    <label class="small font-weight-bold mb-1">Nomor Rekening</label>
                    <input type="text" name="bank_accounts[${accountIndex}][account_number]" class="form-control form-control-sm font-tabular" placeholder="Nomor rekening (opsional)">
                </div>
                <div class="form-group mb-2">
                    <label class="small font-weight-bold mb-1">Nama Pemilik Rekening</label>
                    <input type="text" name="bank_accounts[${accountIndex}][account_holder]" class="form-control form-control-sm" placeholder="Nama persis di buku tabungan" value="${defaultHolder}">
                </div>
                <div class="form-group mb-2">
                    <label class="small font-weight-bold mb-1">Keterangan / Keperluan</label>
                    <input type="text" name="bank_accounts[${accountIndex}][notes]" class="form-control form-control-sm" placeholder="Contoh: Rekening Gaji / Sertifikasi / Pribadi">
                </div>
                <div class="custom-control custom-radio mt-1">
                    <input type="radio" id="primary_${accountIndex}" name="primary_account_radio" class="custom-control-input radio-primary-account" value="${accountIndex}">
                    <input type="hidden" name="bank_accounts[${accountIndex}][is_primary]" class="hidden-is-primary" value="0">
                    <label class="custom-control-label small font-weight-bold text-primary" for="primary_${accountIndex}">Jadikan Rekening Utama (Default Transfer)</label>
                </div>
            </div>
        `;

        $('#bankAccountsContainer').append(html);
        accountIndex++;
        updateRemoveButtons();
    });

    $(document).on('click', '.btn-remove-account', function() {
        let row = $(this).closest('.bank-item-row');
        let wasPrimary = row.find('.radio-primary-account').is(':checked');

        row.remove();
        updateRowLabels();
        updateRemoveButtons();

        if (wasPrimary) {
            let firstRadio = $('#bankAccountsContainer .bank-item-row:first').find('.radio-primary-account');
            firstRadio.prop('checked', true).trigger('change');
        }
    });

    $(document).on('change', '.radio-primary-account', function() {
        $('.hidden-is-primary').val('0');
        $(this).closest('.custom-radio').find('.hidden-is-primary').val('1');
    });

    function updateRowLabels() {
        $('#bankAccountsContainer .bank-item-row').each(function(idx) {
            $(this).find('.bank-row-label').text('Rekening #' + (idx + 1));
        });
    }

    function updateRemoveButtons() {
        let total = $('#bankAccountsContainer .bank-item-row').length;
        if (total <= 1) {
            $('#bankAccountsContainer .btn-remove-account').hide();
        } else {
            $('#bankAccountsContainer .btn-remove-account').show();
        }
    }
});
</script>
@stop
