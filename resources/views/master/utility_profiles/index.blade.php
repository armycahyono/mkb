@extends('adminlte::page')

@section('title', 'Data Pokok Identitas Utilitas')

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap" style="padding: 2px 0;">
    <div>
        <h1 class="page-title"><i class="fas fa-bolt text-warning mr-1"></i> Data Pokok Identitas Utilitas</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-house"></i> Dashboard</a></li>
                <li class="breadcrumb-item">Data Pokok</li>
                <li class="breadcrumb-item active">Identitas Utilitas</li>
            </ol>
        </nav>
    </div>
    <div class="header-actions d-flex align-items-center">
        <a href="{{ route('bos.utilitas.index') }}" class="btn btn-sm btn-outline-secondary mr-2">
            <i class="fas fa-receipt mr-1"></i> Buka Struk Utilitas
        </a>
        <button type="button" class="btn btn-sm btn-primary shadow-sm" id="btnCreateUtility">
            <i class="fas fa-plus mr-1"></i> Tambah Identitas Utilitas
        </button>
    </div>
</div>
@stop

@section('css')
<style>
    body, .content-wrapper, .card, table, .btn, .form-control, .select2 {
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

    #utilityTable {
        font-size: 0.8125rem !important;
        letter-spacing: -0.008em;
    }
    #utilityTable th {
        font-size: 0.74rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 0.5rem 0.5rem !important;
        vertical-align: middle;
        background-color: #f8fafc;
        color: #475569;
    }
    #utilityTable td {
        padding: 0.45rem 0.5rem !important;
        vertical-align: middle;
        line-height: 1.35;
    }

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
{{-- Filter Badges Strip --}}
<div class="d-flex align-items-center justify-content-between flex-wrap mb-2" style="gap: 6px;">
    <div class="d-flex align-items-center flex-wrap" style="gap: 6px;">
        <span class="badge-filter badge badge-dark px-2 py-1 active" data-filter-category="">
            <i class="fas fa-list mr-1"></i> Semua Kategori ({{ $counts['total'] }})
        </span>
        <span class="badge-filter badge badge-light border px-2 py-1" data-filter-category="listrik_pasca">
            <i class="fas fa-bolt text-warning mr-1"></i> Listrik Pasca
        </span>
        <span class="badge-filter badge badge-light border px-2 py-1" data-filter-category="listrik_pra">
            <i class="fas fa-plug text-info mr-1"></i> Listrik Pra/Token
        </span>
        <span class="badge-filter badge badge-light border px-2 py-1" data-filter-category="telkom_speedy">
            <i class="fas fa-phone text-danger mr-1"></i> Telkom / Speedy
        </span>
        <span class="badge-filter badge badge-light border px-2 py-1" data-filter-category="internet">
            <i class="fas fa-wifi text-primary mr-1"></i> Internet Dedicated
        </span>
    </div>

    <div class="d-flex align-items-center flex-wrap" style="gap: 6px;">
        <span class="badge-filter badge badge-light border px-2 py-1 active" data-filter-budget="">
            Semua Anggaran
        </span>
        <span class="badge-filter badge badge-light border px-2 py-1" data-filter-budget="1">
            <i class="fas fa-check text-success mr-1"></i> Dianggarkan BOS ({{ $counts['budgeted'] }})
        </span>
        <span class="badge-filter badge badge-light border px-2 py-1" data-filter-budget="0">
            <i class="fas fa-ban text-secondary mr-1"></i> Non-BOS ({{ $counts['non_budgeted'] }})
        </span>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-2">
        <div class="table-responsive">
            <table class="table table-hover table-bordered table-striped mb-0" id="utilityTable" style="width: 100%;">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 30px;">No</th>
                        <th style="min-width: 160px;">Objek / Nama Tagihan</th>
                        <th class="text-center" style="width: 120px;">Kategori</th>
                        <th style="min-width: 220px;">Identitas Pelanggan</th>
                        <th class="text-center" style="width: 130px;">Status Anggaran</th>
                        <th style="min-width: 180px;">Penerima Reimburse (TNT)</th>
                        <th class="text-center" style="width: 80px;">Status</th>
                        <th class="text-center" style="width: 70px;">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal Form Tambah/Edit Objek Utilitas --}}
<div class="modal fade" id="modalUtilityForm" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white py-2 px-3">
                <h5 class="modal-title font-weight-bold" style="font-size: 1rem;" id="modalTitle">
                    <i class="fas fa-bolt mr-1"></i> Tambah Identitas Objek Utilitas
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formUtility">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <input type="hidden" name="id" id="utilityId">

                <div class="modal-body p-3">
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <label class="font-weight-600 mb-1 small">Kode Objek <span class="text-danger">*</span></label>
                            <input type="text" name="code" id="fieldCode" class="form-control form-control-sm" placeholder="Contoh: PLN-PASCA-01" required>
                            <small class="text-muted">Kode unik pengenal sistem</small>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="font-weight-600 mb-1 small">Kategori Utilitas <span class="text-danger">*</span></label>
                            <select name="category" id="fieldCategory" class="form-control form-control-sm" required>
                                <option value="listrik_pasca">Listrik PLN (Pasca Bayar)</option>
                                <option value="listrik_pra">Listrik PLN (Pra Bayar / Token)</option>
                                <option value="telkom_speedy">Telkom / Indihome / Speedy</option>
                                <option value="internet">Internet Dedicated / Fiber</option>
                                <option value="lainnya">Utilitas Lainnya</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="font-weight-600 mb-1 small">Status Anggaran BOS <span class="text-danger">*</span></label>
                            <select name="is_budgeted" id="fieldIsBudgeted" class="form-control form-control-sm" required>
                                <option value="1">Dianggarkan BOS (Masuk RKAS & Kertas Kerja)</option>
                                <option value="0">Non-BOS (Hanya Catat Struk / Internal)</option>
                            </select>
                            <small class="text-muted" id="budgetHint">Jika Non-BOS (seperti SMP PGRI), tidak akan muncul di RKAS & Kertas Kerja BOS.</small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label class="font-weight-600 mb-1 small">Nama Tagihan / Objek <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="fieldName" class="form-control form-control-sm" placeholder="Contoh: Listrik Pasca Bayar SMKN 1" required>
                            <small class="text-muted">Nama yang akan muncul pada dropdown RKAS / Struk</small>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="font-weight-600 mb-1 small">Provider / Rekanan</label>
                            <input type="text" name="provider" id="fieldProvider" class="form-control form-control-sm" placeholder="Contoh: PT PLN (Persero) / PT Telkom Indonesia">
                        </div>
                    </div>

                    <hr class="my-2">
                    <h6 class="font-weight-bold text-secondary small text-uppercase mb-2"><i class="fas fa-id-card mr-1"></i> Data Identitas Pelanggan / Rekening</h6>

                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label class="font-weight-600 mb-1 small">Nama Pelanggan / Pemilik Rekening <span class="text-danger">*</span></label>
                            <input type="text" name="customer_name" id="fieldCustomerName" class="form-control form-control-sm" placeholder="Contoh: SMK PGRI 1 MEJAYAN" required>
                            <small class="text-muted">Nama yang tertera pada lembar struk/tagihan asli</small>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="font-weight-600 mb-1 small">ID Pelanggan (IDPEL)</label>
                            <input type="text" name="customer_number" id="fieldCustomerNumber" class="form-control form-control-sm font-tabular" placeholder="Contoh: 518040004123">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label class="font-weight-600 mb-1 small">Nomor Meter (Khusus PLN)</label>
                            <input type="text" name="meter_number" id="fieldMeterNumber" class="form-control form-control-sm font-tabular" placeholder="Contoh: 14120304050">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="font-weight-600 mb-1 small">Tarif / Daya (Khusus Listrik)</label>
                            <input type="text" name="tariff_power" id="fieldTariffPower" class="form-control form-control-sm" placeholder="Contoh: B1 / 5500 VA atau R1M / 900 VA">
                        </div>
                    </div>

                    <hr class="my-2">
                    <h6 class="font-weight-bold text-secondary small text-uppercase mb-2"><i class="fas fa-money-bill-transfer mr-1"></i> Pengaturan Pencairan & Reimburse (TNT)</h6>

                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label class="font-weight-600 mb-1 small">Default Petugas Reimburse (Guru/Staff)</label>
                            <select name="default_reimburse_guru_id" id="fieldGuruId" class="form-control form-control-sm">
                                <option value="">-- Bendahara BOSP (Pencairan Standar) --</option>
                                @foreach($gurus as $guru)
                                    <option value="{{ $guru->id }}">
                                        {{ $guru->name }}
                                        @if($guru->primaryBankAccount)
                                            ({{ $guru->primaryBankAccount->bank_name }} - {{ $guru->primaryBankAccount->account_number }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Jika tagihan ditalangi oleh guru/staff tertentu, pencairan TNT otomatis ditujukan ke ybs.</small>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="font-weight-600 mb-1 small">Urutan Tampilan</label>
                            <input type="number" name="sort_order" id="fieldSortOrder" class="form-control form-control-sm" value="0">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="font-weight-600 mb-1 small">Status Aktif</label>
                            <select name="is_active" id="fieldIsActive" class="form-control form-control-sm">
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <label class="font-weight-600 mb-1 small">Keterangan / Catatan Tambahan</label>
                        <textarea name="description" id="fieldDescription" class="form-control form-control-sm" rows="2" placeholder="Catatan lokasi meteran, PIC tagihan, atau keterangan lainnya..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-3">
                    <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-sm btn-primary" id="btnSubmitForm">
                        <i class="fas fa-save mr-1"></i> Simpan Data Pokok
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
$(document).ready(function() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    let currentCategory = '';
    let currentBudgeted = '';

    const utilityTable = $('#utilityTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('master.utilitas.index', [], false) }}",
            data: function(d) {
                d.category = currentCategory;
                d.is_budgeted = currentBudgeted;
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'id', className: 'text-center align-middle', orderable: false, searchable: false },
            { data: 'name_display', name: 'name', className: 'align-middle' },
            { data: 'category_display', name: 'category', className: 'text-center align-middle' },
            { data: 'identity_display', name: 'customer_name', className: 'align-middle' },
            { data: 'budget_display', name: 'is_budgeted', className: 'text-center align-middle' },
            { data: 'reimburse_display', name: 'default_reimburse_guru_id', className: 'align-middle' },
            { data: 'status_display', name: 'is_active', className: 'text-center align-middle' },
            { data: 'action', name: 'action', className: 'text-center align-middle', orderable: false, searchable: false }
        ],
        order: [[1, 'asc']],
        pageLength: 25,
        language: {
            processing: '<i class="fas fa-spinner fa-spin mr-1"></i> Memuat data...',
            search: 'Cari Utilitas:',
            lengthMenu: 'Tampilkan _MENU_ data',
            info: 'Menampilkan _START_ s/d _END_ dari _TOTAL_ data',
            infoEmpty: 'Menampilkan 0 data',
            infoFiltered: '(disaring dari _MAX_ total data)',
            emptyTable: 'Belum ada data profil utilitas.',
            zeroRecords: 'Tidak ditemukan profil utilitas yang sesuai.',
            paginate: {
                first: '<i class="fas fa-angle-double-left"></i>',
                previous: '<i class="fas fa-angle-left"></i>',
                next: '<i class="fas fa-angle-right"></i>',
                last: '<i class="fas fa-angle-double-right"></i>'
            }
        }
    });

    // Filter Category
    $('[data-filter-category]').on('click', function() {
        $('[data-filter-category]').removeClass('active badge-dark').addClass('badge-light border');
        $(this).addClass('active badge-dark').removeClass('badge-light border');
        currentCategory = $(this).data('filter-category');
        utilityTable.ajax.reload();
    });

    // Filter Budgeted
    $('[data-filter-budget]').on('click', function() {
        $('[data-filter-budget]').removeClass('active badge-dark').addClass('badge-light border');
        $(this).addClass('active badge-dark').removeClass('badge-light border');
        currentBudgeted = $(this).data('filter-budget');
        utilityTable.ajax.reload();
    });

    // Button Tambah
    $('#btnCreateUtility').on('click', function() {
        $('#formUtility')[0].reset();
        $('#formMethod').val('POST');
        $('#utilityId').val('');
        $('#modalTitle').html('<i class="fas fa-bolt mr-1"></i> Tambah Identitas Objek Utilitas');
        $('#fieldIsBudgeted').val('1');
        $('#fieldIsActive').val('1');
        $('#modalUtilityForm').modal('show');
    });

    // Button Edit
    $(document).on('click', '.btn-edit', function() {
        const data = $(this).data('json');
        $('#formMethod').val('PUT');
        $('#utilityId').val(data.id);
        $('#modalTitle').html('<i class="fas fa-edit mr-1"></i> Edit Objek Utilitas: ' + data.name);

        $('#fieldCode').val(data.code);
        $('#fieldName').val(data.name);
        $('#fieldCategory').val(data.category);
        $('#fieldCustomerName').val(data.customer_name);
        $('#fieldCustomerNumber').val(data.customer_number);
        $('#fieldMeterNumber').val(data.meter_number);
        $('#fieldTariffPower').val(data.tariff_power);
        $('#fieldProvider').val(data.provider);
        $('#fieldGuruId').val(data.default_reimburse_guru_id);
        $('#fieldSortOrder').val(data.sort_order);
        $('#fieldIsBudgeted').val(data.is_budgeted);
        $('#fieldIsActive').val(data.is_active);
        $('#fieldDescription').val(data.description);

        $('#modalUtilityForm').modal('show');
    });

    // Submit Form
    $('#formUtility').on('submit', function(e) {
        e.preventDefault();

        const method = $('#formMethod').val();
        const id = $('#utilityId').val();
        const url = method === 'PUT' 
            ? "{{ url('master/utilitas') }}/" + id
            : "{{ route('master.utilitas.store') }}";

        const btn = $('#btnSubmitForm');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

        $.ajax({
            url: url,
            method: method === 'PUT' ? 'PUT' : 'POST',
            data: $(this).serialize(),
            success: function(res) {
                $('#modalUtilityForm').modal('hide');
                utilityTable.ajax.reload(null, false);
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: res.message,
                    timer: 2000,
                    showConfirmButton: false
                });
            },
            error: function(xhr) {
                let msg = 'Terjadi kesalahan saat menyimpan data.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).map(e => e.join('<br>')).join('<br>');
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Menyimpan',
                    html: msg
                });
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan Data Pokok');
            }
        });
    });

    // Delete
    $(document).on('click', '.btn-delete', function() {
        const url = $(this).data('url');
        const name = $(this).data('name');

        Swal.fire({
            title: 'Hapus Objek Utilitas?',
            html: `Apakah Anda yakin ingin menghapus <b>${name}</b>?<br><small class="text-muted">Jika objek memiliki riwayat struk, statusnya akan otomatis dialihkan ke Nonaktif.</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus/Nonaktifkan',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    method: 'DELETE',
                    success: function(res) {
                        utilityTable.ajax.reload(null, false);
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: res.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Menghapus',
                            text: xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan sistem.'
                        });
                    }
                });
            }
        });
    });
});
</script>
@stop
