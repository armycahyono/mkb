@extends('adminlte::page')

@section('title', 'Data Guru & Pegawai')

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap" style="padding: 2px 0;">
    <div>
        <h1 class="page-title"><i class="fas fa-chalkboard-teacher text-info mr-1"></i> Data Guru & Pegawai</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-house"></i> Dashboard</a></li>
                <li class="breadcrumb-item">Data Pokok</li>
                <li class="breadcrumb-item active">Data Guru & Pegawai</li>
            </ol>
        </nav>
    </div>
    <div class="header-actions">
        <a href="{{ route('guru.create') }}" class="btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-user-plus mr-1"></i> Tambah Guru / Pegawai
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
    #guruTable {
        font-size: 0.8125rem !important;
    }
    #guruTable th {
        font-size: 0.74rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 0.4rem 0.5rem !important;
        background-color: #f8fafc;
        color: #475569;
        vertical-align: middle;
    }
    #guruTable td {
        padding: 0.4rem 0.5rem !important;
        vertical-align: middle;
        line-height: 1.35;
    }
</style>
@stop

@section('content')
<div class="card shadow-sm border-0 mb-3">
    <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center flex-wrap" style="gap: 8px; border-bottom: 1px solid #edf2f7;">
        <h3 class="card-title font-weight-bold text-dark mb-0" style="font-size: 0.92rem;">
            <i class="fas fa-list mr-1 text-info"></i> Daftar Guru & Tenaga Kependidikan
        </h3>
        <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
            <div class="d-flex align-items-center">
                <span class="text-muted mr-1 font-weight-bold" style="font-size: 0.74rem;">Kepegawaian:</span>
                <select id="filter-employment" class="form-control form-control-sm" style="width: 120px; height: 28px; font-size: 0.78rem; padding: 2px 6px;">
                    <option value="">Semua Status</option>
                    <option value="PNS">PNS</option>
                    <option value="PPPK">PPPK</option>
                    <option value="GTT">GTT</option>
                    <option value="GTY">GTY</option>
                    <option value="PTY">PTY</option>
                    <option value="Honorer">Honorer</option>
                    <option value="PTT">PTT</option>
                </select>
            </div>
            <div class="d-flex align-items-center">
                <span class="text-muted mr-1 font-weight-bold" style="font-size: 0.74rem;">Status:</span>
                <select id="filter-status" class="form-control form-control-sm" style="width: 110px; height: 28px; font-size: 0.78rem; padding: 2px 6px;">
                    <option value="">Semua</option>
                    <option value="1" selected>Aktif</option>
                    <option value="0">Non-Aktif</option>
                </select>
            </div>
        </div>
    </div>
    <div class="card-body p-2">
        <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered table-striped w-100" id="guruTable">
                <thead>
                    <tr>
                        <th width="35" class="text-center">NO</th>
                        <th>NAMA LENGKAP & NIP</th>
                        <th width="85" class="text-center">JK / AGAMA</th>
                        <th width="140">KEPEGAWAIAN</th>
                        <th width="180">JABATAN AKTIF</th>
                        <th width="180">REKENING UTAMA</th>
                        <th width="130">KONTAK</th>
                        <th width="75" class="text-center">STATUS</th>
                        <th width="85" class="text-center">AKSI</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
$(document).ready(function() {
    let table = $('#guruTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
        ajax: {
            url: "{{ route('guru.index', [], false) }}",
            data: function(d) {
                d.employment_status = $('#filter-employment').val();
                d.status = $('#filter-status').val();
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center font-tabular'},
            {data: 'name_display', name: 'name'},
            {data: 'identity_display', name: 'gender', className: 'text-center'},
            {data: 'employment_display', name: 'employment_status'},
            {data: 'active_jabatans_display', name: 'active_jabatans_display', orderable: false, searchable: false},
            {data: 'bank_account_display', name: 'bank_account_display', orderable: false, searchable: false},
            {data: 'contact_display', name: 'phone', orderable: false},
            {data: 'status_display', name: 'is_active', className: 'text-center'},
            {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center'}
        ],
        drawCallback: function() {
            $('[data-toggle="tooltip"]').tooltip();
        }
    });

    $('#filter-employment, #filter-status').on('change', function() {
        table.draw();
    });

    $(document).on('click', '.btn-delete', function() {
        let url = $(this).data('url');
        let name = $(this).data('name');

        Swal.fire({
            title: 'Hapus Data Guru?',
            html: `Apakah Anda yakin ingin menghapus data <strong>${name}</strong>? Tindakan ini akan mengarsipkan data guru.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash mr-1"></i> Ya, Hapus',
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
                        Swal.fire('Gagal!', xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan sistem.', 'error');
                    }
                });
            }
        });
    });
});
</script>
@stop
