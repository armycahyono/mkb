@extends('adminlte::page')

@section('title', 'Daftar Instansi & Mitra')

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap" style="padding: 2px 0;">
    <div>
        <h1 class="page-title"><i class="fas fa-building text-primary mr-1"></i> Daftar Instansi & Mitra</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-house"></i> Dashboard</a></li>
                <li class="breadcrumb-item">Data Pokok</li>
                <li class="breadcrumb-item active">Daftar Instansi & Mitra</li>
            </ol>
        </nav>
    </div>
    <div class="header-actions d-flex align-items-center">
        <button type="button" class="btn btn-sm btn-outline-success shadow-sm mr-2" id="btnOpenImport">
            <i class="fas fa-file-excel mr-1"></i> Upload Excel
        </button>
        <button type="button" class="btn btn-sm btn-primary shadow-sm" id="btnCreateInstansi">
            <i class="fas fa-plus mr-1"></i> Tambah Instansi Baru
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
    #instansiTable {
        font-size: 0.8125rem !important;
        letter-spacing: -0.008em;
    }
    #instansiTable th {
        font-size: 0.74rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 0.4rem 0.5rem !important;
        vertical-align: middle;
        background-color: #f8fafc;
        color: #475569;
    }
    #instansiTable td {
        padding: 0.35rem 0.5rem !important;
        vertical-align: middle;
        line-height: 1.35;
    }

    /* Compact DataTables Pagination & Search */
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
    <span class="badge-filter badge badge-dark px-2 py-1 active" data-category="">
        Semua ({{ $counts['total'] }})
    </span>
    <span class="badge-filter badge badge-primary px-2 py-1" data-category="sekolah">
        <i class="fas fa-graduation-cap mr-1"></i> Sekolah ({{ $counts['sekolah'] }})
    </span>
    <span class="badge-filter badge badge-warning px-2 py-1 text-dark" data-category="dudi">
        <i class="fas fa-industry mr-1"></i> DUDI / Industri ({{ $counts['dudi'] }})
    </span>
    <span class="badge-filter badge badge-info px-2 py-1" data-category="dinas">
        <i class="fas fa-landmark mr-1"></i> Dinas / Instansi ({{ $counts['dinas'] }})
    </span>
    <span class="badge-filter badge badge-secondary px-2 py-1" data-category="lainnya">
        <i class="fas fa-building mr-1"></i> Lainnya ({{ $counts['lainnya'] }})
    </span>
</div>

<div class="card shadow-sm border-0 mb-3">
    <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center flex-wrap" style="border-bottom: 1px solid #edf2f7;">
        <h3 class="card-title font-weight-bold text-dark mb-0" style="font-size: 0.92rem;">
            <i class="fas fa-list-check text-primary mr-1"></i> Daftar Instansi Tujuan & Mitra
        </h3>
        <span class="text-muted small">
            <i class="fas fa-info-circle mr-1"></i> Data ini otomatis muncul sebagai rekomendasi saat pengisian formulir Perjadin.
        </span>
    </div>
    <div class="card-body p-2">
        <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered w-100" id="instansiTable">
                <thead>
                    <tr>
                        <th width="4%" class="text-center">No</th>
                        <th width="32%">Nama Instansi & Narahubung</th>
                        <th width="12%" class="text-center">Kategori</th>
                        <th width="44%">Alamat & Kota</th>
                        <th width="8%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal Tambah / Edit Instansi --}}
<div class="modal fade" id="instansiModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="instansiForm" method="POST">
            @csrf
            <div id="methodContainer"></div>
            <input type="hidden" name="id" id="instansiId">
            <div class="modal-content shadow border-0">
                <div class="modal-header py-2 bg-primary text-white">
                    <h6 class="modal-title font-weight-bold" id="modalTitle" style="font-size: 0.88rem;">
                        <i class="fas fa-building mr-1"></i> Tambah Instansi Baru
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    <div id="modalAlert" class="alert alert-danger py-2 px-3 mb-3 d-none" style="font-size: 0.8125rem;"></div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold" style="font-size: 0.78rem;">Nama Instansi / Perusahaan <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="instansiName" class="form-control form-control-sm" placeholder="Contoh: SMPN 1 RINGINREJO atau PT. TELKOM" required>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold" style="font-size: 0.78rem;">Kategori Instansi <span class="text-danger">*</span></label>
                        <select name="category" id="instansiCategory" class="form-control form-control-sm" required>
                            <option value="sekolah">Sekolah / Lembaga Pendidikan</option>
                            <option value="dudi">DUDI / Dunia Usaha & Industri</option>
                            <option value="dinas">Dinas / Instansi Pemerintah</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold" style="font-size: 0.78rem;">Alamat Lengkap</label>
                        <textarea name="address" id="instansiAddress" rows="2" class="form-control form-control-sm" placeholder="Alamat jalan, dusun, desa, kecamatan..."></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold" style="font-size: 0.78rem;">Kota / Kabupaten</label>
                                <input type="text" name="city" id="instansiCity" class="form-control form-control-sm" value="Kediri">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold" style="font-size: 0.78rem;">No. Telepon / HP</label>
                                <input type="text" name="phone" id="instansiPhone" class="form-control form-control-sm" placeholder="08...">
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-2">
                        <label class="font-weight-bold" style="font-size: 0.78rem;">Nama PIC / Narahubung</label>
                        <input type="text" name="pic_name" id="instansiPic" class="form-control form-control-sm" placeholder="Nama petugas/kontak di instansi terkait">
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary font-weight-bold px-3" id="btnSaveInstansi">
                        <i class="fas fa-save mr-1"></i> Simpan Instansi
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modal Upload Excel --}}
<div class="modal fade" id="importModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form id="importForm" enctype="multipart/form-data">
            @csrf
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light py-2 px-3 border-bottom">
                    <h5 class="modal-title font-weight-bold text-dark" style="font-size: 0.95rem;">
                        <i class="fas fa-file-excel text-success mr-1"></i> Upload Excel Data Instansi
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3 px-3">
                    <div class="alert alert-info py-2 px-3 mb-3 small">
                        <i class="fas fa-info-circle mr-1"></i> Gunakan format Excel yang telah disediakan untuk mempercepat penambahan data instansi dan mitra secara massal.
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded border">
                        <div>
                            <span class="font-weight-bold small text-dark d-block">Template Format Excel</span>
                            <small class="text-muted">Unduh contoh tabel dan susunan kolom</small>
                        </div>
                        <a href="{{ route('instansi.template') }}" class="btn btn-xs btn-outline-primary font-weight-bold">
                            <i class="fas fa-download mr-1"></i> Unduh Template
                        </a>
                    </div>

                    <div class="form-group mb-2">
                        <label class="font-weight-bold small">Pilih File Excel (.xlsx / .xls / .csv) <span class="text-danger">*</span></label>
                        <div class="custom-file">
                            <input type="file" name="excel_file" id="excelFile" class="custom-file-input form-control-sm" accept=".xlsx,.xls,.csv" required>
                            <label class="custom-file-label small" for="excelFile">Pilih file excel...</label>
                        </div>
                        <small class="text-muted d-block mt-1" style="font-size: 0.74rem;">
                            * Kolom utama: <strong>Nama Instansi</strong>. Kolom Kategori (sekolah, dudi, dinas, lainnya) otomatis terdeteksi jika tidak diisi.
                        </small>
                    </div>

                    <div id="importAlert" class="alert alert-danger py-2 px-3 small d-none mt-2"></div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-success font-weight-bold px-3" id="btnSubmitImport">
                        <i class="fas fa-upload mr-1"></i> Upload & Proses
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@stop

@section('js')
<script>
$(document).ready(function() {
    let selectedCategory = '';

    let table = $('#instansiTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 25,
        ajax: {
            url: "{{ route('instansi.index', [], false) }}",
            data: function(d) {
                d.category = selectedCategory;
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center font-tabular'},
            {data: 'name_display', name: 'name'},
            {data: 'category_display', name: 'category', className: 'text-center'},
            {data: 'address_display', name: 'address'},
            {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center'}
        ]
    });

    // Filter badge click
    $('.badge-filter').on('click', function() {
        $('.badge-filter').removeClass('active');
        $(this).addClass('active');
        selectedCategory = $(this).data('category');
        table.ajax.reload();
    });

    // Custom file input label update
    $('#excelFile').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass("selected").html(fileName || 'Pilih file excel...');
    });

    // Buka Modal Upload
    $('#btnOpenImport').on('click', function() {
        $('#importForm')[0].reset();
        $('#excelFile').next('.custom-file-label').removeClass("selected").html('Pilih file excel...');
        $('#importAlert').addClass('d-none').html('');
        $('#importModal').modal('show');
    });

    // Submit Upload Excel
    $('#importForm').on('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        let btn = $('#btnSubmitImport');

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Mengunggah...');
        $('#importAlert').addClass('d-none').html('');

        $.ajax({
            url: "{{ route('instansi.import') }}",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(resp) {
                btn.prop('disabled', false).html('<i class="fas fa-upload mr-1"></i> Upload & Proses');
                $('#importModal').modal('hide');
                table.ajax.reload(null, false);

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Import Berhasil!',
                        text: resp.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    alert(resp.message);
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fas fa-upload mr-1"></i> Upload & Proses');
                let msg = 'Gagal memproses file Excel.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                $('#importAlert').removeClass('d-none').html(msg);
            }
        });
    });

    // Tambah Baru
    $('#btnCreateInstansi').on('click', function() {
        $('#instansiForm')[0].reset();
        $('#instansiId').val('');
        $('#instansiCity').val('Kediri');
        $('#methodContainer').html('');
        $('#modalTitle').html('<i class="fas fa-building mr-1"></i> Tambah Instansi Baru');
        $('#modalAlert').addClass('d-none').html('');
        $('#instansiModal').modal('show');
    });

    // Edit
    $(document).on('click', '.btn-edit', function() {
        let btn = $(this);
        $('#instansiId').val(btn.data('id'));
        $('#instansiName').val(btn.data('name'));
        $('#instansiCategory').val(btn.data('category'));
        $('#instansiAddress').val(btn.data('address'));
        $('#instansiCity').val(btn.data('city'));
        $('#instansiPhone').val(btn.data('phone'));
        $('#instansiPic').val(btn.data('pic'));

        $('#methodContainer').html('@method("PUT")');
        $('#modalTitle').html('<i class="fas fa-edit mr-1"></i> Edit Instansi: ' + btn.data('name'));
        $('#modalAlert').addClass('d-none').html('');
        $('#instansiModal').modal('show');
    });

    // Submit Form
    $('#instansiForm').on('submit', function(e) {
        e.preventDefault();
        let id = $('#instansiId').val();
        let url = id ? "{{ url('instansi') }}/" + id : "{{ route('instansi.store') }}";
        let btn = $('#btnSaveInstansi');

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');
        $('#modalAlert').addClass('d-none').html('');

        $.ajax({
            url: url,
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(resp) {
                btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan Instansi');
                $('#instansiModal').modal('hide');
                table.ajax.reload(null, false);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: resp.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan Instansi');
                let msg = 'Terjadi kesalahan pengisian.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    let errList = Object.values(xhr.responseJSON.errors).map(e => `<li>${e[0]}</li>`).join('');
                    msg = `<ul class="mb-0 pl-3">${errList}</ul>`;
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                $('#modalAlert').removeClass('d-none').html(msg);
            }
        });
    });

    // Hapus
    $(document).on('click', '.btn-delete', function() {
        let url = $(this).data('url');
        let name = $(this).data('name');

        let doDelete = function() {
            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    _method: 'DELETE'
                },
                dataType: 'json',
                success: function(resp) {
                    table.ajax.reload(null, false);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Terhapus',
                            text: resp.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                }
            });
        };

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Hapus Instansi?',
                text: `Apakah Anda yakin ingin menghapus '${name}'?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    doDelete();
                }
            });
        } else {
            if (confirm(`Apakah Anda yakin ingin menghapus '${name}'?`)) {
                doDelete();
            }
        }
    });
});
</script>
@stop
