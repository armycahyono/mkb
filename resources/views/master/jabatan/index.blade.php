@extends('adminlte::page')

@section('title', 'Master Jabatan Sekolah')

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap" style="padding: 2px 0;">
    <div>
        <h1 class="page-title"><i class="fas fa-id-badge text-success mr-1"></i> Master Jabatan Sekolah</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-house"></i> Dashboard</a></li>
                <li class="breadcrumb-item">Data Pokok</li>
                <li class="breadcrumb-item active">Master Jabatan</li>
            </ol>
        </nav>
    </div>
    <div class="header-actions">
        <a href="{{ route('jabatan.penugasan') }}" class="btn btn-sm btn-outline-info mr-1">
            <i class="fas fa-user-check mr-1"></i> Buka Penetapan Penugasan Guru
        </a>
        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#jabatanModal" data-toggle="modal" data-target="#jabatanModal" id="btnCreateJabatan">
            <i class="fas fa-plus mr-1"></i> Tambah Master Jabatan
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
    #jabatanTable {
        font-size: 0.8125rem !important;
    }
    #jabatanTable th {
        font-size: 0.74rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 0.4rem 0.5rem !important;
        background-color: #f8fafc;
        color: #475569;
        vertical-align: middle;
    }
    #jabatanTable td {
        padding: 0.4rem 0.5rem !important;
        vertical-align: middle;
        line-height: 1.35;
    }
</style>
@stop

@section('content')
<div class="card shadow-sm border-0 mb-3">
    <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center flex-wrap" style="border-bottom: 1px solid #edf2f7;">
        <h3 class="card-title font-weight-bold text-dark mb-0" style="font-size: 0.92rem;">
            <i class="fas fa-list mr-1 text-success"></i> Daftar Master Jabatan & Tugas Tambahan
        </h3>
        <div>
            <span class="text-muted small">Jabatan ini menjadi acuan pilihan penugasan periodik guru dan penandatanganan berkas SPJ.</span>
        </div>
    </div>
    <div class="card-body p-2">
        <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered table-striped w-100" id="jabatanTable">
                <thead>
                    <tr>
                        <th width="40" class="text-center">NO</th>
                        <th>NAMA JABATAN</th>
                        <th>DESKRIPSI / TUGAS</th>
                        <th width="150" class="text-center">STATUS PENUGASAN</th>
                        <th width="85" class="text-center">AKSI</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

{{-- Modal Tambah / Edit Jabatan --}}
<div class="modal fade" id="jabatanModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form id="jabatanForm">
            @csrf
            <input type="hidden" name="_method" id="jabatanMethod" value="POST">
            <input type="hidden" id="jabatanId" value="">

            <div class="modal-content shadow">
                <div class="modal-header py-2 bg-light border-bottom">
                    <h6 class="modal-title font-weight-bold text-dark" id="jabatanModalTitle">Tambah Master Jabatan</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">Nama Jabatan <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="jabatanName" class="form-control form-control-sm" placeholder="Contoh: Bendahara BOSP" required>
                    </div>
                    <input type="hidden" name="code" id="jabatanCode">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">Deskripsi / Peran</label>
                        <textarea name="description" id="jabatanDescription" class="form-control form-control-sm" rows="2" placeholder="Tanggung jawab jabatan ini"></textarea>
                    </div>
                    <div class="custom-control custom-checkbox mt-3">
                        <input type="checkbox" class="custom-control-input" id="is_signatory" name="is_signatory" value="1">
                        <label class="custom-control-label small font-weight-bold text-primary" for="is_signatory">
                            Jabatan ini berhak menandatangani dokumen SPJ / Keuangan (misal: Kepala Sekolah & Bendahara)
                        </label>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-success px-3 font-weight-bold"><i class="fas fa-save mr-1"></i> Simpan Jabatan</button>
                </div>
            </div>
        </form>
    </div>
</div>
@stop

@section('js')
<script>
$(document).ready(function() {
    let table = $('#jabatanTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 25,
        ajax: "{{ route('jabatan.index', [], false) }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center font-tabular'},
            {data: 'name_display', name: 'name'},
            {data: 'description', name: 'description', defaultContent: '-'},
            {data: 'active_holders_count', name: 'active_holders_count', className: 'text-center'},
            {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center'}
        ]
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

    $('#btnCreateJabatan').click(function(e) {
        e.preventDefault();
        $('#jabatanModalTitle').text('Tambah Master Jabatan');
        $('#jabatanForm')[0].reset();
        $('#jabatanMethod').val('POST');
        $('#jabatanId').val('');
        $('#jabatanForm').data('action-url', "{{ route('jabatan.store') }}");
        $('#is_signatory').prop('checked', false);
        showModal('jabatanModal');
    });

    $(document).on('click', '.btn-edit-jabatan', function(e) {
        e.preventDefault();
        let $btn = $(this);
        let id = $btn.data('id');
        let name = $btn.data('name');
        let code = $btn.data('code') || '';
        let description = $btn.data('description') || '';
        let isSignatory = $btn.data('signatory') == 1;
        let updateUrl = $btn.data('update-url');

        // Fallback to data-json if direct attributes not present
        if (!name && $btn.data('json')) {
            let json = $btn.data('json');
            if (typeof json === 'string') {
                try { json = JSON.parse(json); } catch (err) {}
            }
            if (json && typeof json === 'object') {
                id = json.id;
                name = json.name;
                code = json.code || '';
                description = json.description || '';
                isSignatory = json.is_signatory == 1;
            }
        }

        $('#jabatanModalTitle').text('Edit Master Jabatan');
        $('#jabatanMethod').val('PUT');
        $('#jabatanId').val(id);
        $('#jabatanForm').data('action-url', updateUrl || ("{{ url('jabatan') }}/" + id));
        $('#jabatanName').val(name);
        $('#jabatanCode').val(code);
        $('#jabatanDescription').val(description);
        $('#is_signatory').prop('checked', isSignatory);
        showModal('jabatanModal');
    });

    $('#jabatanForm').submit(function(e) {
        e.preventDefault();
        let method = $('#jabatanMethod').val();
        let id = $('#jabatanId').val();
        let url = $('#jabatanForm').data('action-url') || (method === 'POST' ? "{{ route('jabatan.store') }}" : "{{ url('jabatan') }}/" + id);

        let data = {
            _token: '{{ csrf_token() }}',
            name: $('#jabatanName').val(),
            code: $('#jabatanCode').val(),
            description: $('#jabatanDescription').val(),
            is_signatory: $('#is_signatory').is(':checked') ? 1 : 0
        };

        if (method === 'PUT') {
            data._method = 'PUT';
        }

        let submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

        $.ajax({
            url: url,
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(res) {
                hideModal('jabatanModal');
                table.draw(false);
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: res.message,
                    timer: 2000,
                    showConfirmButton: false
                });
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
                submitBtn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan Jabatan');
            }
        });
    });

    $(document).on('click', '.btn-delete-jabatan', function() {
        let url = $(this).data('url');
        let name = $(this).data('name');

        Swal.fire({
            title: 'Hapus Jabatan?',
            html: `Apakah Anda yakin ingin menghapus master jabatan <strong>${name}</strong>?`,
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
                    },
                    error: function(xhr) {
                        Swal.fire('Gagal!', xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan.', 'error');
                    }
                });
            }
        });
    });
});
</script>
@stop
