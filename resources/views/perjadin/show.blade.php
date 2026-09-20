@extends('adminlte::page')

@section('title', 'Detail Perjadin - ' . $perjadin->destination_name)

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap" style="padding: 2px 0;">
    <div>
        <h1 class="page-title"><i class="fas fa-car-side text-primary mr-1"></i> Perjalanan Dinas {{ $fundingSource }} <small class="text-muted font-weight-normal" style="font-size: 0.82rem;">({{ $perjadin->destination_name }})</small></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-house"></i> Dashboard</a></li>
                <li class="breadcrumb-item"><i class="fas fa-calculator"></i> Penganggaran / {{ $fundingSource }}</li>
                <li class="breadcrumb-item"><a href="{{ route($routePrefix . '.perjadin.index') }}">Perjalanan Dinas</a></li>
                <li class="breadcrumb-item active">{{ $perjadin->destination_name }}</li>
            </ol>
        </nav>
    </div>
    <div class="header-actions">
        <a href="{{ route($routePrefix . '.perjadin.print', $perjadin->id) }}" target="_blank" class="btn btn-sm btn-secondary mr-1 font-weight-bold shadow-sm">
            <i class="fas fa-print mr-1"></i> Cetak Semua Kwitansi ({{ $perjadin->items->count() }} Orang)
        </a>
        <a href="{{ route($routePrefix . '.perjadin.edit', $perjadin->id) }}" class="btn btn-sm btn-warning mr-1 font-weight-bold">
            <i class="fas fa-edit mr-1"></i> Edit
        </a>
        <a href="{{ route($routePrefix . '.perjadin.index') }}" class="btn btn-sm btn-outline-secondary">
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
    #tablePeserta th {
        font-size: 0.74rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 0.4rem 0.5rem !important;
        vertical-align: middle;
        background-color: #f8fafc;
        color: #475569;
    }
    #tablePeserta td {
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
                    <i class="fas fa-map-marker-alt text-danger mr-1"></i> Informasi Perjalanan Dinas
                </h6>
            </div>
            <div class="card-body p-3">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td width="28%" class="text-muted small">Tujuan Dinas:</td>
                        <td class="font-weight-bold text-dark">{{ $perjadin->destination_name }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Alamat Tujuan:</td>
                        <td>{{ $perjadin->destination_address ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Maksud Dinas:</td>
                        <td class="font-weight-500">{{ $perjadin->activity_purpose }}</td>
                    </tr>
                    @if($perjadin->kertasKerjaItem)
                    <tr>
                        <td class="text-muted small">Kegiatan Kertas Kerja:</td>
                        <td>
                            <span class="badge badge-success mr-1"><i class="fas fa-link mr-1"></i>Kertas Kerja Terhubung</span>
                            <span class="font-weight-500 text-dark">{{ $perjadin->kertasKerjaItem->kegiatan }}</span>
                            <small class="text-muted d-block font-italic">[{{ $perjadin->kertasKerjaItem->month }} {{ $perjadin->kertasKerjaItem->year }}] {{ $perjadin->kertasKerjaItem->description }} (Tarif: Rp {{ number_format($perjadin->kertasKerjaItem->price, 0, ',', '.') }} / {{ $perjadin->kertasKerjaItem->unit }})</small>
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <td class="text-muted small">Tanggal Pelaksanaan:</td>
                        <td class="font-tabular font-weight-500">
                            {{ $perjadin->departure_date ? $perjadin->departure_date->format('d F Y') : '-' }}
                            @if($perjadin->return_date)
                                s/d {{ $perjadin->return_date->format('d F Y') }}
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Tanggal Pembayaran:</td>
                        <td class="font-tabular">
                            @if($perjadin->payment_date)
                                <span class="text-success font-weight-bold"><i class="fas fa-check-circle mr-1"></i>{{ $perjadin->payment_date->format('d F Y') }}</span>
                            @else
                                <span class="text-warning"><i class="fas fa-clock mr-1"></i>Menunggu Pencairan Bank</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Kota Penerbitan:</td>
                        <td>{{ $perjadin->city }}</td>
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
                    <i class="fas fa-receipt text-success mr-1"></i> Administrasi & Pejabat Penandatangan
                </h6>
            </div>
            <div class="card-body p-3">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td width="40%" class="text-muted small">No. BKU:</td>
                        <td class="font-tabular font-weight-bold">{{ $perjadin->bku_number ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Kode Rekening:</td>
                        <td class="font-tabular font-weight-bold text-primary">{{ $perjadin->account_code ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Status Perjadin:</td>
                        <td>
                            @if($perjadin->status === 'lunas')
                                <span class="badge badge-success"><i class="fas fa-check-double mr-1"></i>Lunas Dibayar</span>
                            @elseif($perjadin->status === 'diajukan_ke_bank')
                                <span class="badge badge-info"><i class="fas fa-file-invoice-dollar mr-1"></i>Diajukan ke TNT</span>
                            @else
                                <span class="badge badge-warning"><i class="fas fa-hourglass-half mr-1"></i>Draft</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Surat TNT:</td>
                        <td>
                            @if($perjadin->tnt_letters && $perjadin->tnt_letters->isNotEmpty())
                                @foreach($perjadin->tnt_letters as $letter)
                                    <span class="badge badge-light border text-primary font-tabular font-weight-bold" style="font-size: 0.85rem;"><i class="fas fa-file-invoice-dollar mr-1 text-info"></i>{{ $letter }}</span>
                                @endforeach
                            @else
                                <span class="text-muted font-italic">- Belum Masuk TNT -</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Kepala Sekolah:</td>
                        <td class="font-weight-500">{{ $perjadin->kepalaSekolah ? $perjadin->kepalaSekolah->full_name_with_title : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Bendahara {{ $fundingSource }}:</td>
                        <td class="font-weight-500">{{ $perjadin->bendahara ? $perjadin->bendahara->full_name_with_title : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Total Biaya:</td>
                        <td class="font-tabular font-weight-bold text-success" style="font-size: 1.1rem;">
                            Rp {{ number_format($perjadin->total_net_amount, 0, ',', '.') }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Tabel Rincian Peserta --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-2 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="card-title font-weight-bold text-dark mb-0">
            <i class="fas fa-users text-primary mr-1"></i> Daftar Guru Peserta & Lembar Kwitansi ({{ $perjadin->items->count() }} Orang)
        </h6>
        <span class="badge badge-info font-tabular">Total: {{ $perjadin->total_terbilang }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-hover mb-0">
                <thead class="bg-light text-dark text-center">
                    <tr>
                        <th width="4%">No</th>
                        <th width="26%">Nama Guru Peserta</th>
                        <th width="24%">Rekening Bank Tujuan</th>
                        <th width="12%" class="text-right">Uang Diterima</th>
                        <th width="8%" class="text-right">PPN</th>
                        <th width="8%" class="text-right">PPh 23</th>
                        <th width="12%" class="text-right">Jumlah Bersih</th>
                        <th width="6%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($perjadin->items as $idx => $item)
                        <tr>
                            <td class="text-center font-tabular align-middle">{{ $idx + 1 }}</td>
                            <td class="align-middle">
                                <div class="font-weight-bold text-dark">{{ $item->guru->full_name_with_title ?? '-' }}</div>
                                @if($item->guru && $item->guru->nip)
                                    <small class="text-muted font-tabular">NIP. {{ $item->guru->nip }}</small>
                                @endif
                            </td>
                            <td class="align-middle small">
                                @if($item->bankAccount)
                                    <strong>{{ $item->bankAccount->bank_name }}</strong><br>
                                    <span class="font-tabular">{{ $item->bankAccount->account_number }} a.n {{ $item->bankAccount->account_holder }}</span>
                                @elseif($item->guru && $item->guru->primaryBankAccount)
                                    <strong>{{ $item->guru->primaryBankAccount->bank_name }}</strong><br>
                                    <span class="font-tabular">{{ $item->guru->primaryBankAccount->account_number }} a.n {{ $item->guru->primaryBankAccount->account_holder }}</span>
                                @else
                                    <span class="text-muted font-italic">- Belum diatur -</span>
                                @endif

                                @if($item->pencairanBankItem && $item->pencairanBankItem->pencairanBank)
                                    <div class="mt-1">
                                        <span class="badge badge-light border text-primary font-tabular" style="font-size: 0.76rem;" title="Nomor Surat TNT"><i class="fas fa-file-invoice-dollar mr-1 text-info"></i>TNT: {{ $item->pencairanBankItem->pencairanBank->letter_number }}</span>
                                    </div>
                                @endif
                            </td>
                            <td class="text-right font-tabular align-middle">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                            <td class="text-right font-tabular align-middle">Rp {{ number_format($item->ppn, 0, ',', '.') }}</td>
                            <td class="text-right font-tabular align-middle">Rp {{ number_format($item->pph23, 0, ',', '.') }}</td>
                            <td class="text-right font-tabular font-weight-bold text-success align-middle">
                                Rp {{ number_format($item->net_amount, 0, ',', '.') }}
                            </td>
                            <td class="text-center align-middle">
                                <a href="{{ route($routePrefix . '.perjadin.print', [$perjadin->id, $item->id]) }}" target="_blank" class="btn btn-xs btn-outline-secondary" title="Cetak Kwitansi Orang Ini">
                                    <i class="fas fa-print mr-1"></i> Kwitansi
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-light font-weight-bold">
                    <tr>
                        <td colspan="6" class="text-right py-2 text-dark font-weight-bold">TOTAL DITERIMA:</td>
                        <td class="text-right py-2 font-tabular text-success font-weight-bold" style="font-size:1.05rem;">
                            Rp {{ number_format($perjadin->total_net_amount, 0, ',', '.') }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@stop
