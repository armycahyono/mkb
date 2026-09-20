@extends('adminlte::page')

@section('title', 'Daftar Supplier / Rekanan')

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap" style="padding: 2px 0;">
    <div>
        <h1 class="page-title"><i class="fas fa-store text-warning mr-1"></i> Daftar Supplier / Rekanan</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-house"></i> Dashboard</a></li>
                <li class="breadcrumb-item">Data Pokok</li>
                <li class="breadcrumb-item active">Daftar Supplier</li>
            </ol>
        </nav>
    </div>
    <div class="header-actions d-flex align-items-center">
        <button type="button" class="btn btn-sm btn-primary shadow-sm" id="btnCreateSupplier">
            <i class="fas fa-plus mr-1"></i> Tambah Supplier Baru
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

    /* High-Density Data Table */
    #supplierTable {
        font-size: 0.8125rem !important;
        letter-spacing: -0.008em;
    }
    #supplierTable th {
        font-size: 0.74rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 0.45rem 0.5rem !important;
        vertical-align: middle;
        background-color: #f8fafc;
        color: #475569;
    }
    #supplierTable td {
        padding: 0.4rem 0.5rem !important;
        vertical-align: middle;
        line-height: 1.35;
    }

    /* Compact DataTables */
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        font-size: 0.78rem !important;
    }
    .dataTables_wrapper .dataTables_info {
        font-size: 0.72rem !important;
        color: #64748b !important;
        padding-top: 0.35rem !important;
    }


    .badge-filter {
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .badge-filter:hover, .badge-filter.active {
        box-shadow: 0 2px 4px rgba(0,0,0,0.12);
        transform: translateY(-1px);
    }
</style>
@stop

@section('content')
{{-- Filter Badges / Strip --}}
<div class="d-flex align-items-center flex-wrap mb-2" style="gap: 6px;">
    <span class="badge-filter badge badge-dark px-2 py-1 active" data-filter-type="type" data-filter-val="">
        Semua ({{ $counts['total'] }})
    </span>
    <span class="badge-filter badge badge-light border px-2 py-1 text-dark" data-filter-type="type" data-filter-val="offline">
        <i class="fas fa-store mr-1 text-secondary"></i> Toko Offline ({{ $counts['offline'] }})
    </span>
    <span class="badge-filter badge badge-info px-2 py-1" data-filter-type="type" data-filter-val="online">
        <i class="fas fa-globe mr-1"></i> Toko Online / Toko Ladang ({{ $counts['online'] }})
    </span>
    <span class="badge-filter badge badge-success px-2 py-1" data-filter-type="pkp" data-filter-val="pkp">
        <i class="fas fa-check-double mr-1"></i> Mitra PKP ({{ $counts['pkp'] }})
    </span>
    <span class="badge-filter badge badge-light border px-2 py-1 text-muted" data-filter-type="active" data-filter-val="1">
        <i class="fas fa-toggle-on mr-1 text-success"></i> Aktif Saja ({{ $counts['active'] }})
    </span>
</div>

<div class="card shadow-sm border-0 mb-3">
    <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center flex-wrap" style="border-bottom: 1px solid #edf2f7;">
        <h3 class="card-title font-weight-bold text-dark mb-0" style="font-size: 0.92rem;">
            <i class="fas fa-list-check text-warning mr-1"></i> Data Master Toko, Penyedia & Rekanan
        </h3>
        <span class="text-muted small">
            <i class="fas fa-info-circle mr-1"></i> Mendukung rekanan toko offline maupun marketplace online (Toko Ladang / SIPLah).
        </span>
    </div>
    <div class="card-body p-2">
        <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered w-100" id="supplierTable">
                <thead>
                    <tr>
                        <th width="3%" class="text-center">No</th>
                        <th width="21%">Nama Toko</th>
                        <th width="10%" class="text-center">Jenis Toko</th>
                        <th width="12%" class="text-center">Kategori</th>
                        <th width="13%">Pajak (NPWP / PKP)</th>
                        <th width="18%">Rekening / VA (TNT)</th>
                        <th width="13%">Alamat</th>
                        <th width="5%" class="text-center">Status</th>
                        <th width="5%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal Tambah / Edit Supplier --}}
<div class="modal fade" id="supplierModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 1060px;">
        <form id="supplierForm" method="POST">
            @csrf
            <div id="methodContainer"></div>
            <input type="hidden" name="id" id="supplierId">
            <div class="modal-content shadow border-0">
                <div class="modal-header py-2 bg-warning text-dark">
                    <h6 class="modal-title font-weight-bold" id="modalTitle" style="font-size: 0.95rem;">
                        <i class="fas fa-store mr-1"></i> Tambah Supplier Baru
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="modalAlert" class="alert alert-danger py-2 px-3 mb-3 d-none" style="font-size: 0.8125rem;"></div>

                    {{-- Pilihan Jenis Toko: 1 Baris Saja --}}
                    <div class="bg-light px-3 py-2 rounded border mb-3 d-flex align-items-center flex-wrap" style="gap: 25px;">
                        <span class="font-weight-bold text-dark" style="font-size: 0.86rem;">
                            Jenis Toko <span class="text-danger">*</span> :
                        </span>
                        <div class="custom-control custom-radio custom-control-inline mb-0 mr-0">
                            <input type="radio" id="typeOffline" name="type" value="offline" class="custom-control-input supplier-type-radio" checked>
                            <label class="custom-control-label font-weight-bold text-dark" for="typeOffline" style="cursor: pointer; font-size: 0.88rem;">
                                <i class="fas fa-store mr-1 text-secondary"></i> Toko Offline
                            </label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline mb-0 mr-0">
                            <input type="radio" id="typeOnline" name="type" value="online" class="custom-control-input supplier-type-radio">
                            <label class="custom-control-label font-weight-bold text-info" for="typeOnline" style="cursor: pointer; font-size: 0.88rem;">
                                <i class="fas fa-globe mr-1"></i> Toko Online
                            </label>
                        </div>
                    </div>

                    {{-- Bagian 1: Identitas Toko --}}
                    <div class="text-secondary font-weight-bold mb-2 pb-1 border-bottom" style="font-size: 0.82rem;">
                        <i class="fas fa-id-card-alt mr-1 text-warning"></i> 1. Identitas Toko / Rekanan
                    </div>
                    <div class="row">
                        <div class="col-md-5 form-group mb-3">
                            <label class="font-weight-bold" style="font-size: 0.8rem;">Nama Toko / Merchant / Penyedia <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="supplierName" class="form-control form-control-sm" placeholder="Contoh: Toko Buku Media / CV. Sumber Rejeki" required>
                        </div>
                        <div class="col-md-4 form-group mb-3">
                            <label class="font-weight-bold" style="font-size: 0.8rem;">Kategori Bidang Usaha <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <select name="category" id="supplierCategory" class="form-control form-control-sm" required>
                                    @foreach($categories as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnTambahKategori" title="Tambah kategori baru">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div id="rowTambahKategori" class="d-none mt-1">
                                <div class="input-group input-group-sm">
                                    <input type="text" id="inputKategoriBaru" class="form-control form-control-sm" placeholder="Nama kategori baru…" maxlength="150">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-success btn-sm" id="btnSimpanKategori">
                                            <i class="fas fa-check mr-1"></i>Simpan
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-sm" id="btnBatalKategori">Batal</button>
                                    </div>
                                </div>
                                <small class="text-muted">Ketik nama kategori lalu klik Simpan. Kategori akan tersimpan otomatis.</small>
                            </div>
                        </div>

                        <div class="col-md-3 form-group mb-3 d-none" id="groupMarketplace">
                            <label class="font-weight-bold text-info" style="font-size: 0.8rem;">Platform Marketplace</label>
                            <input type="text" name="marketplace_name" id="supplierMarketplace" class="form-control form-control-sm font-weight-bold" placeholder="Siplah Toko Ladang" value="Siplah Toko Ladang">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold" style="font-size: 0.8rem;">Nama Pemilik / Pimpinan / PIC</label>
                            <input type="text" name="pic_name" id="supplierPic" class="form-control form-control-sm" placeholder="Nama yang tanda tangan di nota / kuitansi">
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold" style="font-size: 0.8rem;">Status PKP (Pengusaha Kena Pajak) <span class="text-danger">*</span></label>
                            <select name="status_pkp" id="supplierStatusPkp" class="form-control form-control-sm" required>
                                <option value="non_pkp">Non-PKP (Tidak Memungut PPN)</option>
                                <option value="pkp">PKP (Menerbitkan Faktur Pajak PPN)</option>
                            </select>
                        </div>
                    </div>

                    {{-- Bagian 2: Perpajakan --}}
                    <div class="text-secondary font-weight-bold mt-2 mb-2 pb-1 border-bottom" style="font-size: 0.82rem;">
                        <i class="fas fa-calculator mr-1 text-info"></i> 2. Perpajakan & Legalitas (PPh / PPN)
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold" style="font-size: 0.8rem;">NPWP Toko / Rekanan <small class="text-muted">(16 / 20 digit)</small></label>
                            <input type="text" name="npwp" id="supplierNpwp" class="form-control form-control-sm font-tabular" placeholder="Contoh: 01.234.567.8-123.000">
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold" style="font-size: 0.8rem;">NIK Pemilik <small class="text-muted">(Jika Perorangan / Non-NPWP)</small></label>
                            <input type="text" name="nik" id="supplierNik" class="form-control form-control-sm font-tabular" placeholder="16 digit NIK KTP">
                        </div>
                    </div>

                    {{-- Bagian 3A: Rekening Bank Toko Offline (Dukungan Multi Rekening) --}}
                    <div id="sectionOfflineBank">
                        <div class="d-flex justify-content-between align-items-center mt-2 mb-2 pb-1 border-bottom">
                            <span class="text-secondary font-weight-bold" style="font-size: 0.82rem;">
                                <i class="fas fa-university mr-1 text-primary"></i> 3. Rekening Bank Toko Offline
                            </span>
                            <button type="button" class="btn btn-xs btn-outline-primary font-weight-bold" id="btnAddAccountRow">
                                <i class="fas fa-plus mr-1"></i> Tambah No. Rekening
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-1" id="tableBankAccounts">
                                <thead class="bg-light" style="font-size: 0.76rem;">
                                    <tr>
                                        <th width="20%">Nama Bank</th>
                                        <th width="24%">Nomor Rekening</th>
                                        <th width="28%">Nama Pemilik Rekening (Atas Nama)</th>
                                        <th width="18%">Catatan</th>
                                        <th width="5%" class="text-center">Utama</th>
                                        <th width="5%" class="text-center"><i class="fas fa-trash"></i></th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyBankAccounts">
                                    {{-- Diisi secara dinamis via JS --}}
                                </tbody>
                            </table>
                        </div>
                        <small class="text-muted font-italic d-block mb-2" style="font-size: 0.71rem;">
                            * Anda dapat mendaftarkan lebih dari satu rekening bank toko offline, mengisi nama pemilik rekening (atas nama), dan menentukan satu sebagai Rekening Utama.
                        </small>
                    </div>

                    {{-- Bagian 3B: Rekening & Penerima TNT Toko Online --}}
                    <div id="sectionOnlineBank" class="d-none">
                        <div class="text-secondary font-weight-bold mt-2 mb-2 pb-1 border-bottom" style="font-size: 0.82rem;">
                            <i class="fas fa-wallet mr-1 text-info"></i> 3. Bank & Penerima Surat TNT (Marketplace Online)
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group mb-3">
                                <label class="font-weight-bold" style="font-size: 0.8rem;">Bank Tujuan Transfer</label>
                                <input type="text" name="bank_name" id="supplierOnlineBankName" class="form-control form-control-sm font-weight-bold" value="Bank Jatim" placeholder="Bank Jatim">
                            </div>
                            <div class="col-md-6 form-group mb-3">
                                <label class="font-weight-bold text-success" style="font-size: 0.8rem;">Nama Penerima di Surat TNT Bank</label>
                                <input type="text" class="form-control form-control-sm font-weight-bold text-success bg-light" value="PT. LADANG KARYA HUSADA" readonly>
                            </div>
                        </div>
                    </div>

                    {{-- Bagian 4: Kontak & Alamat --}}
                    <div class="text-secondary font-weight-bold mt-2 mb-2 pb-1 border-bottom" style="font-size: 0.82rem;">
                        <i class="fas fa-map-marker-alt mr-1 text-danger"></i> 4. Alamat & Kontak Usaha
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold" style="font-size: 0.8rem;">No. Telepon / WhatsApp</label>
                            <input type="text" name="phone" id="supplierPhone" class="form-control form-control-sm font-tabular" placeholder="Contoh: 08123456789">
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold" style="font-size: 0.8rem;">Email Toko (Opsional)</label>
                            <input type="email" name="email" id="supplierEmail" class="form-control form-control-sm" placeholder="toko@domain.com">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8 form-group mb-3">
                            <label class="font-weight-bold" style="font-size: 0.8rem;">Alamat Lengkap Toko</label>
                            <textarea name="address" id="supplierAddress" rows="2" class="form-control form-control-sm" placeholder="Jl. Raya ..., Dusun ..., Desa ..., Kecamatan ..."></textarea>
                        </div>
                        <div class="col-md-4 form-group mb-3">
                            <label class="font-weight-bold" style="font-size: 0.8rem;">Kota / Kabupaten</label>
                            <input type="text" name="city" id="supplierCity" class="form-control form-control-sm" value="Kediri">
                        </div>
                    </div>

                    <div class="row align-items-center mt-1">
                        <div class="col-md-8 form-group mb-0">
                            <label class="font-weight-bold" style="font-size: 0.8rem;">Catatan Tambahan</label>
                            <input type="text" name="notes" id="supplierNotes" class="form-control form-control-sm" placeholder="Contoh: Langganan ATK dan Cetak Ujian">
                        </div>
                        <div class="col-md-4 form-group mb-0 pt-3">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="supplierIsActive" name="is_active" value="1" checked>
                                <label class="custom-control-label font-weight-bold small text-dark" for="supplierIsActive">Supplier Aktif Digunakan</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 px-4 bg-light border-top">
                    <button type="button" class="btn btn-sm btn-secondary px-3" data-bs-dismiss="modal" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-warning font-weight-bold px-4 text-dark" id="btnSaveSupplier">
                        <i class="fas fa-save mr-1"></i> Simpan Supplier
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@stop

@section('js')
<script>
$(function () {
    let filterCategory = '';
    let filterType = '';
    let filterPkp = '';
    let filterActive = '';
    let accountRowIndex = 0;

    function addAccountRow(bank = 'Bank Jatim', number = '', holder = '', notes = '', isPrimary = false) {
        let idx = accountRowIndex++;
        let checked = isPrimary ? 'checked' : '';
        let rowHtml = `
            <tr class="account-row" data-index="${idx}">
                <td class="p-1">
                    <input type="text" name="bank_accounts[${idx}][bank_name]" class="form-control form-control-sm" placeholder="Contoh: Bank Jatim / BRI" value="${bank || 'Bank Jatim'}" required>
                </td>
                <td class="p-1">
                    <input type="text" name="bank_accounts[${idx}][account_number]" class="form-control form-control-sm font-tabular font-weight-bold" placeholder="Nomor rekening" value="${number || ''}" required>
                </td>
                <td class="p-1">
                    <input type="text" name="bank_accounts[${idx}][account_holder]" class="form-control form-control-sm font-weight-bold text-dark" placeholder="Atas nama pemilik rekening" value="${holder || ''}">
                </td>
                <td class="p-1">
                    <input type="text" name="bank_accounts[${idx}][notes]" class="form-control form-control-sm" placeholder="Contoh: Rekening Toko / Pribadi" value="${notes || ''}">
                </td>
                <td class="p-1 text-center align-middle">
                    <div class="custom-control custom-radio">
                        <input type="radio" id="primaryAccount_${idx}" name="primary_account_radio" class="custom-control-input radio-account-primary" value="${idx}" ${checked}>
                        <label class="custom-control-label" for="primaryAccount_${idx}"></label>
                        <input type="hidden" name="bank_accounts[${idx}][is_primary]" class="hidden-is-primary" value="${isPrimary ? '1' : '0'}">
                    </div>
                </td>
                <td class="p-1 text-center align-middle">
                    <button type="button" class="btn btn-xs btn-outline-danger btn-remove-account" title="Hapus baris rekening"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `;
        $('#tbodyBankAccounts').append(rowHtml);
        updateAccountPrimaryRadio();
    }

    $(document).on('click', '#btnAddAccountRow', function() {
        let count = $('#tbodyBankAccounts tr').length;
        addAccountRow('Bank Jatim', '', '', '', count === 0);
    });

    $(document).on('click', '.btn-remove-account', function() {
        if ($('#tbodyBankAccounts tr').length > 1) {
            let wasChecked = $(this).closest('tr').find('.radio-account-primary').is(':checked');
            $(this).closest('tr').remove();
            if (wasChecked) {
                $('#tbodyBankAccounts tr:first').find('.radio-account-primary').prop('checked', true).trigger('change');
            }
        } else {
            $(this).closest('tr').find('input[type="text"]').val('');
        }
    });

    $(document).on('change', '.radio-account-primary', function() {
        $('.hidden-is-primary').val('0');
        $(this).closest('tr').find('.hidden-is-primary').val('1');
    });

    function updateAccountPrimaryRadio() {
        if ($('.radio-account-primary:checked').length === 0) {
            $('.radio-account-primary:first').prop('checked', true);
            $('.hidden-is-primary:first').val('1');
        }
    }

    function updateTypeUI(type) {
        if (type === 'online') {
            $('#groupMarketplace').removeClass('d-none');
            $('#sectionOnlineBank').removeClass('d-none');
            $('#sectionOfflineBank').addClass('d-none');
            $('#supplierOnlineBankName').prop('disabled', false);
            $('#tbodyBankAccounts input').prop('disabled', true);
        } else {
            $('#groupMarketplace').addClass('d-none');
            $('#sectionOnlineBank').addClass('d-none');
            $('#sectionOfflineBank').removeClass('d-none');
            $('#supplierOnlineBankName').prop('disabled', true);
            $('#tbodyBankAccounts input').prop('disabled', false);
        }
    }

    $('.supplier-type-radio').on('change', function() {
        updateTypeUI($(this).val());
    });

    let supplierTable = $('#supplierTable').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        order: [[1, 'asc']],
        ajax: {
            url: "{{ route('supplier.index', [], false) }}",
            type: "GET",
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            data: function (d) {
                d.category = filterCategory;
                d.type = filterType;
                d.status_pkp = filterPkp;
                d.is_active = filterActive;
            },
            error: function (xhr, error, thrown) {
                console.error('DataTable error:', error, thrown, xhr.responseText);
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center font-tabular' },
            { data: 'name_display', name: 'name' },
            { data: 'type_display', name: 'type', className: 'text-center' },
            { data: 'category_display', name: 'category', className: 'text-center' },
            { data: 'tax_display', name: 'npwp' },
            { data: 'bank_display', name: 'bank_name' },
            { data: 'address_display', name: 'address' },
            { data: 'status_display', name: 'is_active', className: 'text-center', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
        ],
        pageLength: 25,
        language: {
            processing: '<div class="spinner-border spinner-border-sm text-warning" role="status"></div> Memuat data...',
            search: "Cari Supplier:",
            lengthMenu: "Tampil _MENU_ data",
            info: "Menampilkan _START_ - _END_ dari _TOTAL_ supplier",
            infoEmpty: "Tidak ada data supplier",
            emptyTable: "Belum ada supplier terdaftar. Klik 'Tambah Supplier Baru' untuk menambahkan.",
            paginate: { first: "«", previous: "‹", next: "›", last: "»" }
        }
    });

    // Filter Badges
    $('.badge-filter').on('click', function () {
        $('.badge-filter').removeClass('active badge-dark').addClass('badge-light border text-muted');
        $(this).addClass('active badge-dark').removeClass('badge-light border text-muted');

        let fType = $(this).data('filter-type');
        let fVal = $(this).data('filter-val');

        filterCategory = '';
        filterType = '';
        filterPkp = '';
        filterActive = '';

        if (fType === 'category') {
            filterCategory = fVal;
        } else if (fType === 'type') {
            filterType = fVal;
        } else if (fType === 'pkp') {
            filterPkp = fVal;
        } else if (fType === 'active') {
            filterActive = fVal;
        }

        supplierTable.draw();
    });

    // Open Modal Create
    $('#btnCreateSupplier').on('click', function () {
        $('#supplierForm')[0].reset();
        $('#supplierId').val('');
        $('#methodContainer').html('');
        $('#modalTitle').html('<i class="fas fa-store mr-1"></i> Tambah Supplier Baru');
        $('#modalAlert').addClass('d-none').html('');
        $('#supplierCity').val('Kediri');
        $('#supplierStatusPkp').val('non_pkp');
        $('#supplierIsActive').prop('checked', true);
        $('#typeOffline').prop('checked', true);
        $('#supplierMarketplace').val('Siplah Toko Ladang');
        $('#tbodyBankAccounts').empty();
        $('#rowTambahKategori').addClass('d-none');
        addAccountRow('Bank Jatim', '', '', '', true);
        updateTypeUI('offline');
        $('#supplierModal').modal('show');
    });

    // Open Modal Edit
    $(document).on('click', '.btn-edit', function () {
        let id = $(this).data('id');
        let url = "{{ url('supplier') }}/" + id;

        $('#supplierForm')[0].reset();
        $('#modalAlert').addClass('d-none').html('');
        $('#methodContainer').html('@method("PUT")');
        $('#modalTitle').html('<i class="fas fa-edit mr-1"></i> Edit Supplier');
        $('#tbodyBankAccounts').empty();
        $('#rowTambahKategori').addClass('d-none');

        $.get(url, function (res) {
            $('#supplierId').val(res.id);
            $('#supplierName').val(res.name);
            $('#supplierPic').val(res.pic_name);
            ensureCategoryOption(res.category, res.category_label);
            $('#supplierStatusPkp').val(res.status_pkp || 'non_pkp');
            $('#supplierNpwp').val(res.npwp);
            $('#supplierNik').val(res.nik);
            $('#supplierPhone').val(res.phone);
            $('#supplierEmail').val(res.email);
            $('#supplierAddress').val(res.address);
            $('#supplierCity').val(res.city || 'Kediri');
            $('#supplierNotes').val(res.notes);
            $('#supplierIsActive').prop('checked', res.is_active == 1);

            if (res.type === 'online') {
                $('#typeOnline').prop('checked', true);
                $('#supplierMarketplace').val(res.marketplace_name || 'Siplah Toko Ladang');
                $('#supplierOnlineBankName').val(res.bank_name || 'Bank Jatim');
                updateTypeUI('online');
            } else {
                $('#typeOffline').prop('checked', true);
                if (res.bank_accounts && res.bank_accounts.length > 0) {
                    res.bank_accounts.forEach(function(acc) {
                        addAccountRow(acc.bank_name, acc.account_number, acc.account_holder || '', acc.notes || '', acc.is_primary);
                    });
                } else if (res.account_number) {
                    addAccountRow(res.bank_name || 'Bank Jatim', res.account_number, res.account_holder || '', '', true);
                } else {
                    addAccountRow('Bank Jatim', '', '', '', true);
                }
                updateTypeUI('offline');
            }

            $('#supplierModal').modal('show');
        }).fail(function () {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Memuat Data',
                text: 'Tidak dapat mengambil detail data supplier.'
            });
        });
    });

    // Submit Form (Create / Update)
    $('#supplierForm').on('submit', function (e) {
        e.preventDefault();
        let id = $('#supplierId').val();
        let url = id ? "{{ url('supplier') }}/" + id : "{{ route('supplier.store') }}";
        let formData = $(this).serialize();

        let $btn = $('#btnSaveSupplier');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            success: function (res) {
                $('#supplierModal').modal('hide');
                supplierTable.ajax.reload(null, false);
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: res.message || 'Data supplier berhasil disimpan.',
                    timer: 2000,
                    showConfirmButton: false
                });
            },
            error: function (xhr) {
                let msg = 'Terjadi kesalahan saat menyimpan data.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                $('#modalAlert').html(msg).removeClass('d-none');
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan Supplier');
            }
        });
    });

    // Toggle Status Aktif
    $(document).on('click', '.btn-toggle-status', function () {
        let id = $(this).data('id');
        let url = "{{ url('supplier') }}/" + id + "/toggle-status";

        $.post(url, { _token: "{{ csrf_token() }}" }, function (res) {
            supplierTable.ajax.reload(null, false);
        }).fail(function () {
            Swal.fire('Error', 'Gagal mengubah status supplier.', 'error');
        });
    });

    // Delete Supplier
    $(document).on('click', '.btn-delete', function () {
        let url = $(this).data('url');
        let name = $(this).data('name');

        Swal.fire({
            title: 'Hapus Supplier?',
            html: `Apakah Anda yakin ingin menghapus supplier <strong>${name}</strong>?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    data: { _token: "{{ csrf_token() }}" },
                    success: function (res) {
                        supplierTable.ajax.reload(null, false);
                        Swal.fire({
                            icon: 'success',
                            title: 'Terhapus!',
                            text: res.message,
                            timer: 1800,
                            showConfirmButton: false
                        });
                    },
                    error: function () {
                        Swal.fire('Error', 'Gagal menghapus supplier.', 'error');
                    }
                });
            }
        });
    });

    // ── Tambah Kategori Baru ──────────────────────────────────────────────────

    /** Ensure a <option> for the given key+label exists in #supplierCategory and select it */
    function ensureCategoryOption(key, label) {
        if ($('#supplierCategory option[value="' + key + '"]').length === 0) {
            $('#supplierCategory').append($('<option>', { value: key, text: label }));
        }
        $('#supplierCategory').val(key);
    }

    $('#btnTambahKategori').on('click', function () {
        $('#rowTambahKategori').toggleClass('d-none');
        if (!$('#rowTambahKategori').hasClass('d-none')) {
            $('#inputKategoriBaru').val('').focus();
        }
    });

    $('#btnBatalKategori').on('click', function () {
        $('#rowTambahKategori').addClass('d-none');
        $('#inputKategoriBaru').val('');
    });

    $('#btnSimpanKategori').on('click', function () {
        let label = $('#inputKategoriBaru').val().trim();
        if (!label) {
            $('#inputKategoriBaru').focus();
            return;
        }
        let $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: "{{ route('supplier.categories.store', [], false) }}",
            type: 'POST',
            data: { _token: "{{ csrf_token() }}", label: label },
            success: function (res) {
                ensureCategoryOption(res.key, res.label);
                $('#rowTambahKategori').addClass('d-none');
                $('#inputKategoriBaru').val('');
                Swal.fire({ icon: 'success', title: 'Kategori disimpan!', text: res.label, timer: 1500, showConfirmButton: false });
            },
            error: function (xhr) {
                let msg = xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.label
                    ? xhr.responseJSON.errors.label[0]
                    : 'Gagal menyimpan kategori.';
                Swal.fire('Error', msg, 'error');
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fas fa-check mr-1"></i>Simpan');
            }
        });
    });

    // Allow pressing Enter in the new-category input
    $('#inputKategoriBaru').on('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); $('#btnSimpanKategori').trigger('click'); }
    });
});
</script>
@stop
