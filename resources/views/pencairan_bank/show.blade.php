@extends('adminlte::page')

@section('title', 'Detail Surat TNT ' . $fundingSource . ' - ' . $pencairanBank->letter_number)

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap" style="padding: 2px 0;">
    <div>
        <h1 class="page-title"><i class="fas fa-file-invoice-dollar text-success mr-1"></i> Surat TNT {{ $fundingSource }} <small class="text-muted font-weight-normal" style="font-size: 0.82rem;">({{ $pencairanBank->letter_number }})</small></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-house"></i> Dashboard</a></li>
                <li class="breadcrumb-item"><i class="fas fa-calculator"></i> Penganggaran / {{ $fundingSource }}</li>
                <li class="breadcrumb-item"><a href="{{ route($routePrefix . '.pencairan-bank.index') }}">TNT {{ $fundingSource }}</a></li>
                <li class="breadcrumb-item active">{{ $pencairanBank->letter_number }}</li>
            </ol>
        </nav>
    </div>
    <div class="header-actions">
        <a href="{{ route($routePrefix . '.pencairan-bank.export-excel', $pencairanBank->id) }}" class="btn btn-sm btn-success mr-1 shadow-sm font-weight-bold" title="Unduh File Excel Softcopy untuk Bank Jatim">
            <i class="fas fa-file-excel mr-1"></i> Unduh Excel TNT
        </a>
        <a href="{{ route($routePrefix . '.pencairan-bank.print', $pencairanBank->id) }}" target="_blank" class="btn btn-sm btn-secondary mr-1 shadow-sm">
            <i class="fas fa-print mr-1"></i> Cetak 1 Set Dokumen TNT (3 Hal)
        </a>
        @if($pencairanBank->status !== 'dicairkan')
            <button type="button" class="btn btn-sm btn-success mr-1 font-weight-bold shadow-sm" id="btnOpenCairModal">
                <i class="fas fa-check-double mr-1"></i> Tandai Telah Dicairkan
            </button>
        @endif
        <a href="{{ route($routePrefix . '.pencairan-bank.edit', $pencairanBank->id) }}" class="btn btn-sm btn-warning mr-1">
            <i class="fas fa-edit mr-1"></i> Edit
        </a>
        <a href="{{ route($routePrefix . '.pencairan-bank.index') }}" class="btn btn-sm btn-outline-secondary">
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
    #tableItems th {
        font-size: 0.74rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 0.4rem 0.5rem !important;
        vertical-align: middle;
        background-color: #f8fafc;
        color: #475569;
    }
    #tableItems td {
        padding: 0.35rem 0.5rem !important;
        vertical-align: middle;
    }
</style>
@stop

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2 px-3 mb-2" role="alert" style="font-size: 0.8125rem;">
        <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row">
    {{-- Info Box Kiri --}}
    <div class="col-md-7">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white py-2 border-bottom">
                <h6 class="card-title font-weight-bold text-dark mb-0">
                    <i class="fas fa-university text-primary mr-1"></i> Identitas Surat Kuasa & Tujuan Bank
                </h6>
            </div>
            <div class="card-body p-3">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td width="30%" class="text-muted small">Nomor Surat:</td>
                        <td class="font-weight-bold font-tabular text-dark">{{ $pencairanBank->letter_number }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Tanggal Surat:</td>
                        <td class="font-tabular">{{ $pencairanBank->letter_date ? $pencairanBank->letter_date->format('d F Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Bank Tujuan:</td>
                        <td class="font-weight-500">{{ $pencairanBank->bank_name }} - {{ $pencairanBank->bank_branch }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Alamat Bank:</td>
                        <td>{{ $pencairanBank->bank_address }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Perihal Surat:</td>
                        <td class="font-weight-bold text-primary">Kuasa Pemindahbukuan</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    {{-- Info Box Kanan --}}
    <div class="col-md-5">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white py-2 border-bottom">
                <h6 class="card-title font-weight-bold text-dark mb-0">
                    <i class="fas fa-wallet text-success mr-1"></i> Rekening Giro Sumber & Status
                </h6>
            </div>
            <div class="card-body p-3">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td width="38%" class="text-muted small">No. Rekening Giro:</td>
                        <td class="font-tabular font-weight-bold text-dark">{{ $pencairanBank->source_account_number }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Nama Rekening Giro:</td>
                        <td class="font-weight-500">{{ $pencairanBank->source_account_name }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Status Transaksi:</td>
                        <td>
                            @if($pencairanBank->status === 'dicairkan')
                                <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Dicairkan Bank</span>
                                @if($pencairanBank->cair_date)
                                    <small class="text-muted font-tabular d-block">Tgl: {{ $pencairanBank->cair_date->format('d/m/Y') }}</small>
                                @endif
                            @else
                                <span class="badge badge-warning"><i class="fas fa-hourglass-half mr-1"></i>Draft Pengajuan</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Kepala Sekolah:</td>
                        <td>{{ $pencairanBank->kepalaSekolah ? $pencairanBank->kepalaSekolah->full_name_with_title : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Bendahara {{ $fundingSource }}:</td>
                        <td>{{ $pencairanBank->bendahara ? $pencairanBank->bendahara->full_name_with_title : '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Tabel Lampiran Daftar Transfer --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-2 border-bottom d-flex justify-content-between align-items-center flex-wrap">
        <h6 class="card-title font-weight-bold text-dark mb-0">
            <i class="fas fa-list text-primary mr-1"></i> Lampiran Rincian Daftar Transfer ({{ $pencairanBank->items->count() }} Rekening)
        </h6>
        <span class="badge badge-info font-tabular">Total: {{ $pencairanBank->total_terbilang }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-hover mb-0">
                <thead class="bg-light text-dark text-center">
                    <tr>
                        <th width="4%">NO</th>
                        <th width="10%" class="text-center">JENIS</th>
                        <th width="26%">NAMA PENERIMA</th>
                        <th width="22%">NO REK BANK / VA</th>
                        <th width="16%" class="text-right">NILAI TRANSFER</th>
                        <th width="22%">KETERANGAN</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pencairanBank->items as $idx => $item)
                        @php
                            $desc = $item->description ?: '-';
                            if (str_starts_with($desc, 'Belanja: ')) {
                                $desc = substr($desc, 9);
                            }
                        @endphp
                        <tr>
                            <td class="text-center font-tabular align-middle">{{ $idx + 1 }}</td>
                            <td class="text-center align-middle">
                                @if($item->type === 'perjadin')
                                    <span class="badge badge-info px-2 py-1"><i class="fas fa-car mr-1"></i>Perjadin</span>
                                @elseif($item->type === 'rencana_nota' || $item->rencanaBelanja)
                                    @if(str_starts_with(strtoupper($item->account_number), 'VA.'))
                                        <span class="badge badge-warning text-dark px-2 py-1"><i class="fas fa-store mr-1"></i>Online</span>
                                    @else
                                        <span class="badge badge-primary px-2 py-1 font-tabular"><i class="fas fa-cart-shopping mr-1"></i>Rencana Belanja</span>
                                    @endif
                                @elseif($item->type === 'utilitas' || $item->utilityReceipt)
                                    <span class="badge badge-warning text-dark px-2 py-1"><i class="fas fa-bolt mr-1"></i>Utilitas</span>
                                @else
                                    <span class="badge badge-secondary px-2 py-1">Belanja</span>
                                @endif
                            </td>
                            <td class="align-middle font-weight-bold text-dark">
                                {{ strtoupper($item->recipient_name) }}
                            </td>
                            <td class="align-middle font-tabular">
                                {{ $item->account_number }}
                            </td>
                            <td class="text-right font-tabular font-weight-bold text-success align-middle">
                                {{ number_format($item->amount, 0, ',', '.') }}
                            </td>
                            <td class="align-middle text-dark">
                                @if($item->rencanaBelanja && $item->rencanaBelanja->no_nota)
                                    <span class="badge badge-light border text-info font-tabular font-weight-bold mr-1" title="Berasal dari Rencana Belanja No. {{ $item->rencanaBelanja->no_nota }}">
                                        <i class="fas fa-receipt mr-1"></i>{{ $item->rencanaBelanja->no_nota }}
                                    </span>
                                @elseif($item->utilityReceipt)
                                    <span class="badge badge-light border text-warning font-tabular font-weight-bold mr-1" title="Struk Utilitas: {{ $item->utilityReceipt->profile?->name ?? 'Utilitas' }}">
                                        <i class="fas fa-bolt mr-1"></i>{{ $item->utilityReceipt->profile?->name ?? 'Utilitas' }}
                                    </span>
                                @endif
                                <span class="small text-muted">{{ $desc }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-light font-weight-bold">
                    <tr>
                        <td colspan="4" class="text-right py-2 text-dark font-weight-bold">TOTAL:</td>
                        <td class="text-right py-2 font-tabular text-success font-weight-bold" style="font-size:1.05rem;">
                            {{ number_format($pencairanBank->total_nominal, 0, ',', '.') }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

{{-- Modal Konfirmasi Pencairan --}}
<div class="modal fade" id="modalConfirmCair" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formConfirmCair" action="{{ route($routePrefix . '.pencairan-bank.cairkan', $pencairanBank->id) }}" method="POST">
            @csrf
            <div class="modal-content shadow">
                <div class="modal-header py-2 bg-success text-white">
                    <h6 class="modal-title font-weight-bold"><i class="fas fa-check-double mr-1"></i> Konfirmasi Pencairan Bank</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    <p class="small text-muted mb-3">
                        Setelah bank memproses transfer, tandai surat ini sebagai <strong>"Dicairkan"</strong>. Tagihan perjadin yang terhubung otomatis akan berstatus <strong>"Lunas Dibayar"</strong>.
                    </p>
                    <div class="form-group mb-3">
                        <label class="small font-weight-bold">Tanggal Cair di Bank <span class="text-danger">*</span></label>
                        <input type="date" name="cair_date" class="form-control form-control-sm font-tabular" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">Nomor BKU <small class="text-muted">(Opsional, untuk melengkapi BKU Perjadin)</small></label>
                        <input type="text" name="bku_number" class="form-control form-control-sm font-tabular" placeholder="Contoh: BPU149 / 2025">
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-success font-weight-bold px-3"><i class="fas fa-check mr-1"></i> Simpan & Lunaskan</button>
                </div>
            </div>
        </form>
    </div>
</div>
@stop

@section('js')
<script>
$(document).ready(function() {
    $('#btnOpenCairModal').click(function() {
        let el = document.getElementById('modalConfirmCair');
        if (window.bootstrap && window.bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(el).show();
        } else if (window.$ && typeof $(el).modal === 'function') {
            $(el).modal('show');
        }
    });

    $('#formConfirmCair').submit(function(e) {
        e.preventDefault();
        let form = $(this);
        let submitBtn = form.find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(res) {
                let el = document.getElementById('modalConfirmCair');
                if (window.bootstrap && window.bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance(el).hide();
                } else if (window.$ && typeof $(el).modal === 'function') {
                    $(el).modal('hide');
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: res.message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            },
            error: function(xhr) {
                Swal.fire('Gagal!', xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan sistem.', 'error');
                submitBtn.prop('disabled', false).html('<i class="fas fa-check mr-1"></i> Simpan & Lunaskan');
            }
        });
    });
});
</script>
@stop
