@extends('adminlte::page')

@php
    $selectedYear = $selectedYear ?? $activeFiscalYear ?? session('fiscal_year', date('Y'));
@endphp

@section('title', 'Perjalanan Dinas (' . $fundingSource . ')')

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap" style="padding: 2px 0;">
    <div>
        <h1 class="page-title"><i class="fas fa-car-side text-primary mr-1"></i> Perjalanan Dinas (Perjadin) {{ $fundingSource }}</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-house"></i> Dashboard</a></li>
                <li class="breadcrumb-item"><i class="fas fa-calculator"></i> Penganggaran / {{ $fundingSource }}</li>
                <li class="breadcrumb-item active">Perjalanan Dinas</li>
            </ol>
        </nav>
    </div>
    <div class="header-actions">
        <button type="button" class="btn btn-sm btn-outline-success mr-1 shadow-sm" id="btnOpenImport">
            <i class="fas fa-file-excel mr-1"></i> Import Excel
        </button>
        <a href="{{ route($routePrefix . '.pencairan-bank.index') }}" class="btn btn-sm btn-outline-info mr-1">
            <i class="fas fa-file-invoice-dollar mr-1"></i> TNT {{ $fundingSource }}
        </a>
        <a href="{{ route($routePrefix . '.perjadin.create') }}" class="btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus mr-1"></i> Tambah Perjadin Baru
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

    #perjadinTable {
        font-size: 0.8125rem !important;
        letter-spacing: -0.008em;
    }
    #perjadinTable th {
        font-size: 0.74rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 0.4rem 0.5rem !important;
        vertical-align: middle;
        background-color: #f8fafc;
        color: #475569;
    }
    #perjadinTable td {
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

</style>
@stop

@section('content')
@php
    $availableYears = $availableYears ?? collect([(int) date('Y')]);
    $selectedYear = $selectedYear ?? (string) date('Y');
@endphp

<div class="card shadow-sm border-0 mb-3">
    <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center flex-wrap" style="border-bottom: 1px solid #edf2f7; gap: 8px;">
        <h3 class="card-title font-weight-bold text-dark mb-0" style="font-size: 0.92rem;">
            <i class="fas fa-list-check text-primary mr-1 me-1"></i> Daftar Kegiatan Perjalanan Dinas {{ $fundingSource }}
        </h3>

        <div class="d-flex align-items-center flex-wrap ms-auto ml-auto" style="gap: 8px;">
            <input type="hidden" id="filterYear" value="{{ $selectedYear }}">
            <span class="badge bg-primary text-white font-weight-normal d-inline-flex align-items-center shadow-xs" style="height: 31px; font-size: 0.8125rem; padding: 0 10px; border-radius: 4px;" title="Tahun Anggaran aktif yang dipilih pada Navbar">
                <i class="fas fa-calendar-alt me-1 mr-1"></i> Tahun Anggaran {{ $selectedYear }}
            </span>

            {{-- Filter Status BKU --}}
            <div class="btn-group btn-group-sm shadow-xs" role="group" id="bkuFilterGroup">
                <button type="button" class="btn btn-sm btn-outline-primary active btn-bku-filter font-weight-bold d-inline-flex align-items-center" data-status="" style="height: 31px; font-size: 0.8125rem; padding: 0.25rem 0.65rem;">
                    Semua <span class="badge badge-light border ml-1 ms-1" id="countTotal">{{ $totalCount ?? 0 }}</span>
                </button>
                <button type="button" class="btn btn-sm btn-outline-warning text-dark btn-bku-filter font-weight-bold d-inline-flex align-items-center" data-status="unassigned" style="height: 31px; font-size: 0.8125rem; padding: 0.25rem 0.65rem;">
                    <i class="fas fa-clock mr-1 me-1 text-warning"></i>Belum BKU <span class="badge badge-warning text-dark ml-1 ms-1" id="countUnassigned">{{ $unassignedBkuCount ?? 0 }}</span>
                </button>
                <button type="button" class="btn btn-sm btn-outline-success btn-bku-filter font-weight-bold d-inline-flex align-items-center" data-status="assigned" style="height: 31px; font-size: 0.8125rem; padding: 0.25rem 0.65rem;">
                    <i class="fas fa-check-circle mr-1 me-1 text-success"></i>Sudah BKU <span class="badge badge-success ml-1 ms-1" id="countAssigned">{{ $assignedBkuCount ?? 0 }}</span>
                </button>
            </div>
        </div>
    </div>
    <div class="card-body p-2">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show py-2 px-3 mb-2" role="alert" style="font-size: 0.8125rem;">
                <i class="fas fa-check-circle mr-1 me-1"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Batch Action Bar (Muncul saat ada baris dicentang) --}}
        <div id="batchActionBar" class="d-none alert alert-info py-2 px-3 mb-2 d-flex align-items-center justify-content-between flex-wrap" style="font-size: 0.8125rem;">
            <div class="d-flex align-items-center mb-1 mb-md-0">
                <i class="fas fa-check-square mr-2 me-2 text-primary fa-lg"></i>
                <span><strong id="selectedCountText">0</strong> kegiatan perjalanan dinas terpilih</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-xs btn-outline-secondary" id="btnDeselectAll">
                    Batal Pilih
                </button>
                <button type="button" class="btn btn-sm btn-primary font-weight-bold shadow-sm" id="btnOpenBatchBku">
                    <i class="fas fa-pen-to-square mr-1 me-1"></i> Isi Nomor BKU Massal
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered w-100" id="perjadinTable">
                <thead>
                    <tr>
                        <th width="3%" class="text-center">
                            <input type="checkbox" id="checkAll" class="form-check-input" title="Pilih Semua di Halaman Ini">
                        </th>
                        <th width="3%" class="text-center">No</th>
                        <th width="28%">Tujuan & Maksud Dinas</th>
                        <th width="12%">Tanggal Pelaksanaan</th>
                        <th width="13%" class="text-center">Nomor BKU</th>
                        <th width="17%">Peserta Dinas</th>
                        <th width="12%" class="text-right">Total Bersih</th>
                        <th width="6%" class="text-center">Status</th>
                        <th width="6%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal Quick Edit BKU (Satuan) --}}
<div class="modal fade" id="modalQuickBku" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-2 px-3 border-bottom">
                <h6 class="modal-title font-weight-bold text-dark mb-0" style="font-size: 0.875rem;">
                    <i class="fas fa-file-invoice text-primary mr-1 me-1"></i> Isi / Ubah Nomor BKU
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formQuickBku">
                @csrf
                <input type="hidden" id="quickBkuId">
                <div class="modal-body p-3">
                    <div class="mb-2">
                        <label class="small font-weight-bold text-dark mb-0 d-block text-truncate" id="quickBkuDestTitle">-</label>
                        <small class="text-muted d-block" id="quickBkuInfoText">1 Peserta</small>
                    </div>
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold text-dark mb-1">Nomor BKU (sesuai ARKAS)</label>
                        <input type="text" id="quickBkuInput" class="form-control form-control-sm font-tabular font-weight-bold" placeholder="Contoh: BPU070 / 2026 atau BPU070 - BPU072 / 2026" autofocus>
                        <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">
                            Kosongkan jika ingin menghapus nomor BKU.
                        </small>
                    </div>
                    <div class="d-flex justify-content-end mb-1">
                        <button type="button" class="btn btn-xs btn-outline-info" id="btnAutoFormatSingle">
                            <i class="fas fa-magic mr-1 me-1"></i> Format Rentang Otomatis
                        </button>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3 bg-light border-top d-flex justify-content-between">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary font-weight-bold px-3" id="btnSaveQuickBku">
                        <i class="fas fa-save mr-1 me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Batch BKU (Massal) --}}
<div class="modal fade" id="modalBatchBku" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-2 px-3 border-bottom">
                <h6 class="modal-title font-weight-bold text-dark mb-0" style="font-size: 0.90rem;">
                    <i class="fas fa-layer-group text-primary mr-1 me-1"></i> Pengisian Nomor BKU Massal (<span id="batchCountBadge">0</span> Kegiatan)
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formBatchBku">
                @csrf
                <div class="modal-body p-3">
                    <div class="alert alert-info py-2 px-3 mb-3" style="font-size: 0.78rem;">
                        <i class="fas fa-info-circle mr-1 me-1"></i> Nomor BKU akan diterapkan ke <strong><span id="batchSelectedText">0</span> kegiatan</strong> yang dipilih, diurutkan secara kronologis berdasarkan tanggal pelaksanaan dinas.
                    </div>

                    <div class="form-group mb-3">
                        <label class="small font-weight-bold text-dark mb-1">Pilih Mode Pengisian:</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="batch_mode" id="modeSequence" value="sequence" checked>
                                <label class="form-check-label small font-weight-bold text-dark" for="modeSequence">
                                    <i class="fas fa-arrow-down-1-9 text-primary mr-1 me-1"></i> Berurutan Otomatis (Rekomendasi)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="batch_mode" id="modeSame" value="same">
                                <label class="form-check-label small font-weight-bold text-dark" for="modeSame">
                                    <i class="fas fa-equals text-secondary mr-1 me-1"></i> Nomor BKU Sama
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Form Bagian Mode Berurutan --}}
                    <div id="sectionSequence">
                        <div class="row">
                            <div class="col-md-7">
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold text-dark mb-1">Nomor BKU Awal (ARKAS)</label>
                                    <input type="text" id="batchStartBku" class="form-control form-control-sm font-tabular font-weight-bold" placeholder="Contoh: BPU070" value="BPU070">
                                    <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">
                                        Sistem akan otomatis menghitung rentang BKU sesuai jumlah guru tiap kegiatan.
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold text-dark mb-1">Tahun Anggaran</label>
                                    <input type="number" id="batchYear" class="form-control form-control-sm font-tabular font-weight-bold" value="{{ date('Y') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Form Bagian Mode Sama --}}
                    <div id="sectionSame" style="display: none;">
                        <div class="form-group mb-2">
                            <label class="small font-weight-bold text-dark mb-1">Nomor BKU</label>
                            <input type="text" id="batchSameBku" class="form-control form-control-sm font-tabular font-weight-bold" placeholder="Contoh: BPU100 / {{ date('Y') }}">
                            <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">
                                Seluruh kegiatan terpilih akan diberi nomor BKU yang sama persis.
                            </small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3 bg-light border-top d-flex justify-content-between">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary font-weight-bold px-3" id="btnSubmitBatchBku">
                        <i class="fas fa-check-double mr-1 me-1"></i> Terapkan ke Semua Terpilih
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('perjadin._import_modal')
@stop

@section('js')
<script>
$(document).ready(function() {
    let currentBkuStatus = '';
    let selectedIds = new Set();
    let currentQuickTeacherCount = 1;
    let currentQuickYear = new Date().getFullYear();

    let table = $('#perjadinTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 25,
        ajax: {
            url: "{{ route($routePrefix . '.perjadin.index', [], false) }}",
            data: function(d) {
                d.year = $('#filterYear').val();
                d.bku_status = currentBkuStatus;
            }
        },
        columns: [
            {data: 'checkbox', name: 'checkbox', orderable: false, searchable: false, className: 'text-center'},
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center font-tabular'},
            {data: 'tujuan_display', name: 'destination_name'},
            {data: 'tanggal_display', name: 'departure_date'},
            {data: 'bku_display', name: 'bku_number', className: 'text-center'},
            {data: 'peserta_display', name: 'peserta', orderable: false, searchable: false},
            {data: 'nominal_display', name: 'total_net_amount', className: 'text-right'},
            {data: 'status_display', name: 'status', className: 'text-center'},
            {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center'}
        ],
        drawCallback: function() {
            // Restore checkbox state
            $('.check-perjadin').each(function() {
                let id = $(this).val();
                if (selectedIds.has(id)) {
                    $(this).prop('checked', true);
                }
            });
            updateBatchBar();
        }
    });

    table.on('xhr', function(e, settings, json) {
        if (json && json.counters) {
            $('#countTotal').text(json.counters.total ?? 0);
            $('#countUnassigned').text(json.counters.unassigned ?? 0);
            $('#countAssigned').text(json.counters.assigned ?? 0);
        }
    });

    // ── Filter Tahun Anggaran ─────────────────────────
    $('#filterYear').change(function() {
        table.ajax.reload();
        refreshCounters();
    });

    // ── Filter Status BKU ─────────────────────────────
    $('.btn-bku-filter').click(function() {
        $('.btn-bku-filter').removeClass('active');
        $(this).addClass('active');
        currentBkuStatus = $(this).data('status');
        table.ajax.reload();
    });

    // ── Checkbox Selection ────────────────────────────
    function updateBatchBar() {
        let count = selectedIds.size;
        if (count > 0) {
            $('#selectedCountText').text(count);
            $('#batchActionBar').removeClass('d-none');
        } else {
            $('#batchActionBar').addClass('d-none');
        }

        let totalVisible = $('.check-perjadin').length;
        let checkedVisible = $('.check-perjadin:checked').length;
        $('#checkAll').prop('checked', totalVisible > 0 && totalVisible === checkedVisible);
    }

    $(document).on('change', '.check-perjadin', function() {
        let id = $(this).val();
        if ($(this).is(':checked')) {
            selectedIds.add(id);
        } else {
            selectedIds.delete(id);
        }
        updateBatchBar();
    });

    $('#checkAll').change(function() {
        let isChecked = $(this).is(':checked');
        $('.check-perjadin').each(function() {
            let id = $(this).val();
            $(this).prop('checked', isChecked);
            if (isChecked) {
                selectedIds.add(id);
            } else {
                selectedIds.delete(id);
            }
        });
        updateBatchBar();
    });

    $('#btnDeselectAll').click(function() {
        selectedIds.clear();
        $('.check-perjadin').prop('checked', false);
        $('#checkAll').prop('checked', false);
        updateBatchBar();
    });

    // ── Quick Edit BKU (Satuan) ────────────────────────
    $(document).on('click', '.btn-edit-bku', function() {
        let id = $(this).data('id');
        let bku = $(this).data('bku') || '';
        let dest = $(this).data('destination') || '-';
        let teachers = parseInt($(this).data('teachers'), 10) || 1;
        let yr = parseInt($(this).data('year'), 10) || new Date().getFullYear();

        currentQuickTeacherCount = teachers;
        currentQuickYear = yr;

        $('#quickBkuId').val(id);
        $('#quickBkuInput').val(bku);
        $('#quickBkuDestTitle').text(dest);
        $('#quickBkuInfoText').text(teachers + ' Orang Guru Peserta Dinas · Tahun ' + yr);

        $('#modalQuickBku').modal('show');
    });

    // Format rentang otomatis untuk modal satuan
    $('#btnAutoFormatSingle').click(function() {
        let val = ($('#quickBkuInput').val() || 'BPU001').trim();
        let cleanBase = val.replace(/\s*\/\s*\d{4}$/, '').trim();
        let prefix = 'BPU';
        let startNum = 1;
        let padLength = 3;

        let rangeMatch = cleanBase.match(/^([A-Za-z\s]*?)(\d+)\s*[-–—]\s*(?:[A-Za-z\s]*?)(\d+)$/);
        if (rangeMatch) {
            prefix = rangeMatch[1].trim() || 'BPU';
            padLength = rangeMatch[2].length;
            startNum = parseInt(rangeMatch[2], 10) || 1;
        } else {
            let singleMatch = cleanBase.match(/^([A-Za-z\s]*?)(\d+)$/);
            if (singleMatch) {
                prefix = singleMatch[1].trim() || 'BPU';
                padLength = singleMatch[2].length;
                startNum = parseInt(singleMatch[2], 10) || 1;
            }
        }

        let startFormatted = prefix + String(startNum).padStart(padLength, '0');
        if (currentQuickTeacherCount <= 1) {
            $('#quickBkuInput').val(`${startFormatted} / ${currentQuickYear}`);
        } else {
            let endNum = startNum + currentQuickTeacherCount - 1;
            let endFormatted = prefix + String(endNum).padStart(padLength, '0');
            $('#quickBkuInput').val(`${startFormatted} - ${endFormatted} / ${currentQuickYear}`);
        }
    });

    $('#formQuickBku').submit(function(e) {
        e.preventDefault();
        let id = $('#quickBkuId').val();
        let bkuVal = $('#quickBkuInput').val().trim();
        let btn = $('#btnSaveQuickBku');

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1 me-1"></i> Menyimpan...');

        $.ajax({
            url: `{{ url($routePrefix . '/perjadin') }}/${id}/update-bku`,
            type: 'PATCH',
            data: {
                _token: '{{ csrf_token() }}',
                bku_number: bkuVal
            },
            success: function(res) {
                btn.prop('disabled', false).html('<i class="fas fa-save mr-1 me-1"></i> Simpan');
                $('#modalQuickBku').modal('hide');
                table.draw(false);
                refreshCounters();
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: res.message,
                    timer: 1500,
                    showConfirmButton: false
                });
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fas fa-save mr-1 me-1"></i> Simpan');
                Swal.fire('Gagal!', xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan sistem.', 'error');
            }
        });
    });

    // ── Batch Edit BKU (Massal) ───────────────────────
    $('input[name="batch_mode"]').change(function() {
        if ($(this).val() === 'sequence') {
            $('#sectionSequence').show();
            $('#sectionSame').hide();
        } else {
            $('#sectionSequence').hide();
            $('#sectionSame').show();
        }
    });

    $('#btnOpenBatchBku').click(function() {
        let count = selectedIds.size;
        if (count === 0) {
            Swal.fire('Peringatan', 'Silakan pilih minimal 1 kegiatan perjalanan dinas terlebih dahulu.', 'warning');
            return;
        }

        $('#batchCountBadge').text(count);
        $('#batchSelectedText').text(count);
        $('#modalBatchBku').modal('show');
    });

    $('#formBatchBku').submit(function(e) {
        e.preventDefault();
        let idsArray = Array.from(selectedIds);
        let mode = $('input[name="batch_mode"]:checked').val();
        let btn = $('#btnSubmitBatchBku');

        let postData = {
            _token: '{{ csrf_token() }}',
            ids: idsArray,
            mode: mode,
            bku_start: $('#batchStartBku').val().trim(),
            year: $('#batchYear').val().trim(),
            bku_number: $('#batchSameBku').val().trim()
        };

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1 me-1"></i> Memproses...');

        $.ajax({
            url: "{{ route($routePrefix . '.perjadin.batch-update-bku', [], false) }}",
            type: 'POST',
            data: postData,
            success: function(res) {
                btn.prop('disabled', false).html('<i class="fas fa-check-double mr-1 me-1"></i> Terapkan ke Semua Terpilih');
                $('#modalBatchBku').modal('hide');
                selectedIds.clear();
                updateBatchBar();
                table.draw(false);
                refreshCounters();

                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: res.message,
                    confirmButtonText: 'Selesai'
                });
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fas fa-check-double mr-1 me-1"></i> Terapkan ke Semua Terpilih');
                Swal.fire('Gagal!', xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan sistem.', 'error');
            }
        });
    });

    // Refresh Counter Badges
    function refreshCounters() {
        $.getJSON("{{ route($routePrefix . '.perjadin.index') }}", { action: 'counters', year: $('#filterYear').val() }, function(res) {
            if (res) {
                $('#countTotal').text(res.total ?? 0);
                $('#countUnassigned').text(res.unassigned ?? 0);
                $('#countAssigned').text(res.assigned ?? 0);
            }
        });
    }

    // ── Hapus Data Perjadin ────────────────────────────
    $(document).on('click', '.btn-delete', function() {
        let url = $(this).data('url');
        let name = $(this).data('name');

        Swal.fire({
            title: 'Hapus Perjadin?',
            html: `Apakah Anda yakin ingin menghapus data perjalanan dinas ke <strong>${name}</strong>? Seluruh data kwitansi peserta di dalamnya juga akan terhapus.`,
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
                        refreshCounters();
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
