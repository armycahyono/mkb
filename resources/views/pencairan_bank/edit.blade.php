@extends('adminlte::page')

@section('title', 'Edit Surat TNT ' . $fundingSource)

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap" style="padding: 2px 0;">
    <div>
        <h1 class="page-title"><i class="fas fa-edit text-warning mr-1"></i> Edit Surat TNT {{ $fundingSource }} <small class="text-muted font-weight-normal" style="font-size: 0.82rem;">({{ $pencairanBank->letter_number }})</small></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-house"></i> Dashboard</a></li>
                <li class="breadcrumb-item"><i class="fas fa-calculator"></i> Penganggaran / {{ $fundingSource }}</li>
                <li class="breadcrumb-item"><a href="{{ route($routePrefix . '.pencairan-bank.index') }}">TNT {{ $fundingSource }}</a></li>
                <li class="breadcrumb-item active">Edit Surat TNT</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="{{ route($routePrefix . '.pencairan-bank.show', $pencairanBank->id) }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Batal / Kembali
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
<form action="{{ route($routePrefix . '.pencairan-bank.update', $pencairanBank->id) }}" method="POST" id="suratBankForm">
    @csrf
    @method('PUT')

    @if($errors->any())
        <div class="alert alert-danger py-2 px-3 mb-3" style="font-size: 0.8125rem;">
            <h6 class="font-weight-bold mb-1"><i class="fas fa-exclamation-triangle mr-1"></i> Terdapat kesalahan pengisian:</h6>
            <ul class="mb-0 pl-3">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Card 1: Identitas Surat & Rekening Giro Sekolah --}}
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white py-2 px-3 border-bottom">
            <h6 class="card-title font-weight-bold text-dark mb-0" style="font-size: 0.92rem;">
                <i class="fas fa-file-signature text-primary mr-1"></i> 1. Identitas Surat TNT & Rekening Giro Sekolah
            </h6>
        </div>
        <div class="card-body p-3">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">Nomor Surat <span class="text-danger">*</span></label>
                        <input type="text" name="letter_number" class="form-control form-control-sm font-tabular font-weight-bold" value="{{ old('letter_number', $pencairanBank->letter_number) }}" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">Tanggal Surat <span class="text-danger">*</span></label>
                        <input type="date" name="letter_date" class="form-control form-control-sm font-tabular" value="{{ old('letter_date', $pencairanBank->letter_date ? $pencairanBank->letter_date->format('Y-m-d') : '') }}" required>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">Bank Tujuan Surat <span class="text-danger">*</span></label>
                        <input type="text" name="bank_name" class="form-control form-control-sm" value="{{ old('bank_name', $pencairanBank->bank_name) }}" required>
                    </div>
                </div>
            </div>

            <div class="row mt-2">
                <div class="col-md-3">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">Cabang Bank <span class="text-danger">*</span></label>
                        <input type="text" name="bank_branch" class="form-control form-control-sm" value="{{ old('bank_branch', $pencairanBank->bank_branch) }}" required>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">Alamat Kantor Cabang Bank <span class="text-danger">*</span></label>
                        <input type="text" name="bank_address" class="form-control form-control-sm" value="{{ old('bank_address', $pencairanBank->bank_address) }}" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">No. Rekening Giro {{ $fundingSource }} Sekolah <span class="text-danger">*</span></label>
                        <input type="text" name="source_account_number" class="form-control form-control-sm font-tabular font-weight-bold" value="{{ old('source_account_number', $pencairanBank->source_account_number) }}" required>
                    </div>
                </div>
            </div>

            <div class="row mt-2">
                <div class="col-md-4">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">Nama Rekening Giro Sekolah <span class="text-danger">*</span></label>
                        <input type="text" name="source_account_name" class="form-control form-control-sm font-weight-500" value="{{ old('source_account_name', $pencairanBank->source_account_name) }}" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">Pihak 1: Kepala Sekolah <span class="text-danger">*</span></label>
                        <select name="kepala_sekolah_id" class="form-control form-control-sm" required>
                            @foreach($gurus as $g)
                                <option value="{{ $g->id }}" {{ old('kepala_sekolah_id', $pencairanBank->kepala_sekolah_id) == $g->id ? 'selected' : '' }}>
                                    {{ $g->full_name_with_title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">Pihak 2: Bendahara {{ $fundingSource }} <span class="text-danger">*</span></label>
                        <select name="bendahara_id" class="form-control form-control-sm" required>
                            @foreach($gurus as $g)
                                <option value="{{ $g->id }}" {{ old('bendahara_id', $pencairanBank->bendahara_id) == $g->id ? 'selected' : '' }}>
                                    {{ $g->full_name_with_title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Card 2: Daftar Penerima Transfer Pemindahbukuan --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center flex-wrap">
            <h6 class="card-title font-weight-bold text-dark mb-0" style="font-size: 0.92rem;">
                <i class="fas fa-list-ol text-success mr-1"></i> 2. Lampiran Daftar Penerima Transfer (TNT)
            </h6>
            <div class="d-flex align-items-center mt-1 mt-sm-0 flex-wrap" style="gap: 6px;">
                @if($fundingSource === 'BOSP')
                <button type="button" id="btnPullRencanaNota" class="btn btn-sm btn-outline-info font-weight-bold">
                    <i class="fas fa-cart-shopping mr-1"></i> Tarik Rencana Belanja
                </button>
                @endif
                <button type="button" id="btnPullPerjadin" class="btn btn-sm btn-outline-primary font-weight-bold">
                    <i class="fas fa-download mr-1"></i> Tarik Tagihan Perjadin
                </button>
                <button type="button" id="btnAddManual" class="btn btn-sm btn-success font-weight-bold">
                    <i class="fas fa-plus mr-1"></i> Tambah Belanja / Rekanan
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0" id="tableItems">
                    <thead class="bg-light text-dark text-center">
                        <tr>
                            <th width="4%">NO</th>
                            <th width="10%" class="text-center">JENIS</th>
                            <th width="26%">NAMA PENERIMA <span class="text-danger">*</span></th>
                            <th width="22%">NO REK BANK / VA <span class="text-danger">*</span></th>
                            <th width="16%" class="text-right">NILAI TRANSFER (RP) <span class="text-danger">*</span></th>
                            <th width="18%">KETERANGAN</th>
                            <th width="4%">AKSI</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyItems">
                        {{-- Render items --}}
                    </tbody>
                    <tfoot class="bg-light font-weight-bold">
                        <tr>
                            <td colspan="4" class="text-right py-2 text-dark font-weight-bold">
                                TOTAL TRANSFER (<span id="countDisplay">0</span> REKENING):
                            </td>
                            <td class="text-right py-2 font-tabular text-success font-weight-bold" id="totalDisplay" style="font-size:1.05rem; text-align: right;">
                                Rp 0
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white py-3 border-top d-flex justify-content-between align-items-center">
            <span class="text-muted small"><i class="fas fa-info-circle mr-1"></i> Perubahan data akan memperbarui lampiran surat kuasa TNT bank.</span>
            <div>
                <a href="{{ route($routePrefix . '.pencairan-bank.show', $pencairanBank->id) }}" class="btn btn-sm btn-secondary mr-2">Batal</a>
                <button type="submit" class="btn btn-sm btn-primary px-4 font-weight-bold">
                    <i class="fas fa-save mr-1"></i> Simpan Perubahan
                </button>
            </div>
        </div>
    </div>
</form>

{{-- Modal Tarik Tagihan Perjadin --}}
<div class="modal fade" id="modalPullPerjadin" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow">
            <div class="modal-header py-2 bg-primary text-white">
                <h6 class="modal-title font-weight-bold"><i class="fas fa-download mr-1"></i> Pilih Tagihan Perjalanan Dinas yang Belum Dicairkan</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div id="loadingPerjadin" class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-primary mb-2"></i>
                    <div class="text-muted small">Memuat tagihan perjadin belum cair...</div>
                </div>
                <div id="contentPerjadin" style="display: none;">
                    <div class="table-responsive" style="max-height: 380px;">
                        <table class="table table-sm table-hover table-bordered mb-0">
                            <thead class="bg-light text-dark">
                                <tr>
                                    <th width="6%" class="text-center">
                                        <input type="checkbox" id="checkAllPerjadin">
                                    </th>
                                    <th width="34%">Nama Guru & Rekening</th>
                                    <th width="36%">Tujuan & Tanggal Dinas</th>
                                    <th width="24%" class="text-right">Nominal Bersih</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyUnpaidPerjadin">
                                {{-- Loaded via AJAX --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Tutup</button>
                <button type="button" id="btnInsertPerjadin" class="btn btn-sm btn-primary font-weight-bold px-3">
                    <i class="fas fa-plus mr-1"></i> Masukkan ke Surat Bank
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Tarik Rencana Nota --}}
<div class="modal fade" id="modalPullRencanaNota" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content shadow">
            <div class="modal-header py-2 bg-info text-white">
                <h6 class="modal-title font-weight-bold"><i class="fas fa-cart-shopping mr-1"></i> Pilih Rencana Belanja (BOSP)</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div id="loadingRencanaNota" class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-info mb-2"></i>
                    <div class="text-muted small">Memuat rencana belanja...</div>
                </div>
                <div id="contentRencanaNota" style="display: none;">
                    <div class="table-responsive" style="max-height: 380px;">
                        <table class="table table-sm table-hover table-bordered mb-0">
                            <thead class="bg-light text-dark">
                                <tr>
                                    <th width="5%" class="text-center">
                                        <input type="checkbox" id="checkAllRencanaNota">
                                    </th>
                                    <th width="15%" class="text-center">No. Rencana</th>
                                    <th width="30%">Judul Rencana Belanja</th>
                                    <th width="20%">Supplier / Toko</th>
                                    <th width="12%" class="text-center">Jml Barang</th>
                                    <th width="18%" class="text-right">Total Estimasi</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyUnpaidRencanaNota">
                                {{-- Loaded via AJAX --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Tutup</button>
                <button type="button" id="btnInsertRencanaNota" class="btn btn-sm btn-info font-weight-bold px-3 text-white">
                    <i class="fas fa-plus mr-1"></i> Masukkan ke Surat TNT
                </button>
            </div>
        </div>
    </div>
</div>

@stop

@section('js')
<script>
let itemIndex = 0;
const existingItems = @json($pencairanBank->items);
let unpaidPerjadinList = [];
let unpaidRencanaNotaList = [];

function formatRupiah(num) {
    return 'Rp ' + Number(num).toLocaleString('id-ID');
}

function formatVaAccountNumber(accNum, defaultBank = 'Bank Jatim') {
    defaultBank = (defaultBank || 'Bank Jatim').trim();
    if (!accNum) {
        return 'VA. ' + defaultBank + ' ';
    }
    let str = String(accNum).trim();
    if (/^VA\.?/i.test(str)) {
        str = str.replace(/^VA\.?\s*/i, '').trim();
    }

    if (!str) {
        return 'VA. ' + defaultBank + ' ';
    }

    if (/^[A-Za-z\s]+$/i.test(str)) {
        return 'VA. ' + str.trim() + ' ';
    }

    let match = str.match(/^([A-Za-z\s]+?)\.?\s+([0-9][0-9\s\-]*)$/);
    if (match) {
        let bank = match[1].trim();
        let num = match[2].trim();
        return 'VA. ' + bank + ' ' + num;
    }

    if (/^[0-9]/.test(str)) {
        return 'VA. ' + defaultBank + ' ' + str;
    }

    return 'VA. ' + str;
}

function addItemRow(recipient = '', accNum = '', amount = 0, desc = '', type = 'perjadin', perjadinItemId = '', rencanaNotaId = '', isOnline = false, utilityReceiptId = '', isUtilitas = false) {
    if (type === 'perjadin' && (!desc || desc.startsWith('Perjadin:'))) {
        desc = 'Perjadin';
    }
    if (desc && desc.startsWith('Belanja: ')) {
        desc = desc.substring(9).trim();
    }

    if (isOnline || (type === 'rencana_nota' && accNum && accNum.toUpperCase().startsWith('VA.'))) {
        accNum = formatVaAccountNumber(accNum);
    }

    let numericAmount = (amount !== undefined && amount !== null && amount !== '') ? Math.round(Number(amount)) : 0;
    let formattedAmount = numericAmount ? numericAmount.toLocaleString('id-ID') : '0';

    let typeBadge = '';
    if (type === 'perjadin') {
        typeBadge = '<span class="badge badge-info px-2 py-1"><i class="fas fa-car mr-1"></i>Perjadin</span>';
    } else if (type === 'rencana_nota') {
        if (isUtilitas) {
            typeBadge = '<span class="badge badge-warning text-dark px-2 py-1"><i class="fas fa-bolt mr-1"></i>Utilitas</span>';
        } else if (isOnline) {
            typeBadge = '<span class="badge badge-warning text-dark px-2 py-1"><i class="fas fa-store mr-1"></i>Online</span>';
        } else {
            typeBadge = '<span class="badge badge-primary px-2 py-1"><i class="fas fa-cart-shopping mr-1"></i>Rencana Belanja</span>';
        }
    } else if (type === 'utilitas') {
        typeBadge = '<span class="badge badge-warning text-dark px-2 py-1"><i class="fas fa-bolt mr-1"></i>Utilitas</span>';
    } else {
        typeBadge = '<span class="badge badge-secondary px-2 py-1">Belanja</span>';
    }

    let tr = `
    <tr id="itemRow_${itemIndex}" class="bank-item-row">
        <td class="text-center font-tabular row-number align-middle">${$('.bank-item-row').length + 1}</td>
        <td class="text-center align-middle">
            ${typeBadge}
            <input type="hidden" name="items[${itemIndex}][type]" value="${type}">
            <input type="hidden" name="items[${itemIndex}][perjadin_item_id]" value="${perjadinItemId}">
            <input type="hidden" name="items[${itemIndex}][bos_rencana_belanja_id]" value="${rencanaNotaId}">
            <input type="hidden" name="items[${itemIndex}][utility_receipt_id]" value="${utilityReceiptId}">
        </td>
        <td class="align-middle">
            <input type="text" name="items[${itemIndex}][recipient_name]" class="form-control form-control-sm font-weight-bold" value="${recipient}" placeholder="Nama Penerima / Toko" required>
        </td>
        <td class="align-middle">
            <input type="text" name="items[${itemIndex}][account_number]" class="form-control form-control-sm font-tabular input-item-account" value="${accNum}" placeholder="Contoh: VA. Bank Jatim 2555... atau Bank Jatim 1001..." required>
        </td>
        <td class="align-middle text-right">
            <input type="text" class="form-control form-control-sm font-tabular text-right input-item-amount" value="${formattedAmount}" placeholder="0" required autocomplete="off" style="text-align: right;">
            <input type="hidden" name="items[${itemIndex}][amount]" class="input-item-amount-raw" value="${numericAmount}">
        </td>
        <td class="align-middle">
            <input type="text" name="items[${itemIndex}][description]" class="form-control form-control-sm" value="${desc}" placeholder="Keterangan rencana belanja">
        </td>
        <td class="text-center align-middle">
            <button type="button" class="btn btn-xs btn-outline-danger btn-remove-item" data-idx="${itemIndex}" title="Hapus"><i class="fas fa-times"></i></button>
        </td>
    </tr>
    `;

    $('#tbodyItems').append(tr);
    itemIndex++;
    recalculateBankTotal();
}

function recalculateBankTotal() {
    let total = 0;
    let count = 0;
    $('.bank-item-row').each(function(i) {
        $(this).find('.row-number').text(i + 1);
        let amt = parseFloat($(this).find('.input-item-amount-raw').val()) || 0;
        total += amt;
        count++;
    });

    $('#countDisplay').text(count);
    $('#totalDisplay').text(formatRupiah(total));
}

$(document).ready(function() {
    if (existingItems && existingItems.length > 0) {
        existingItems.forEach(function(item) {
            let perjadinId = item.perjadin_item ? item.perjadin_item.id : '';
            let rencanaId = item.rencana_belanja ? item.rencana_belanja.id : '';
            let utilityId = item.utility_receipt ? item.utility_receipt.id : '';
            let isOnline = item.rencana_belanja ? (item.rencana_belanja.is_online || (item.account_number && item.account_number.toUpperCase().startsWith('VA.'))) : (item.account_number && item.account_number.toUpperCase().startsWith('VA.'));
            let isUtilitas = (item.type === 'utilitas' || (item.rencana_belanja && item.rencana_belanja.type === 'utilitas'));
            let desc = item.description || '';
            if (desc.startsWith('Belanja: ')) {
                desc = desc.substring(9).trim();
            }
            addItemRow(item.recipient_name, item.account_number, item.amount, desc, item.type, perjadinId, rencanaId, isOnline, utilityId, isUtilitas);
        });
    } else {
        addItemRow('', '', 0, '', 'belanja', '', '', false);
    }

    $('#btnAddManual').click(function() {
        addItemRow('', '', 0, '', 'belanja', '', '', false);
    });

    $(document).on('focus', '.input-item-account', function() {
        let val = $(this).val();
        if (val.trim() === 'VA.') {
            $(this).val('VA. Bank Jatim ');
            val = 'VA. Bank Jatim ';
        }
        if (/^VA\.?\s*[A-Za-z\s\.]*$/i.test(val) && !/[0-9]/.test(val)) {
            if (!val.endsWith(' ')) {
                val = val.trim() + ' ';
                $(this).val(val);
            }
            let el = this;
            setTimeout(function() {
                el.setSelectionRange(el.value.length, el.value.length);
            }, 30);
        }
    });

    $(document).on('input', '.input-item-amount', function() {
        let input = this;
        let originalValue = input.value;
        let originalSelection = input.selectionStart;
        let originalLength = originalValue.length;

        // Ambil hanya karakter angka
        let rawVal = originalValue.replace(/[^0-9]/g, '');
        let formatted = rawVal ? parseInt(rawVal, 10).toLocaleString('id-ID') : '';
        
        input.value = formatted;

        // Pertahankan posisi kursor saat formatting
        let newLength = formatted.length;
        let newSelection = originalSelection + (newLength - originalLength);
        if (newSelection >= 0 && newSelection <= newLength) {
            input.setSelectionRange(newSelection, newSelection);
        }

        let numVal = rawVal ? parseInt(rawVal, 10) : 0;
        let row = $(input).closest('tr');
        row.find('.input-item-amount-raw').val(numVal);

        recalculateBankTotal();
    });

    $(document).on('focus', '.input-item-amount', function() {
        if ($(this).val() === '0') {
            $(this).select();
        }
    });

    $(document).on('blur', '.input-item-amount', function() {
        if (!$(this).val()) {
            $(this).val('0');
            $(this).closest('tr').find('.input-item-amount-raw').val(0);
            recalculateBankTotal();
        }
    });

    $(document).on('click', '.btn-remove-item', function() {
        let idx = $(this).data('idx');
        $(`#itemRow_${idx}`).remove();
        recalculateBankTotal();
    });

    // Handle Tarik Tagihan Perjadin
    $('#btnPullPerjadin').click(function() {
        let modalEl = document.getElementById('modalPullPerjadin');
        if (window.bootstrap && window.bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else if (window.$ && typeof $(modalEl).modal === 'function') {
            $(modalEl).modal('show');
        }

        $('#loadingPerjadin').show();
        $('#contentPerjadin').hide();

        $.ajax({
            url: "{{ route($routePrefix . '.api.unpaid-perjadins', [], false) }}",
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                unpaidPerjadinList = data;
                $('#loadingPerjadin').hide();
                $('#contentPerjadin').show();

                if (data.length === 0) {
                    $('#tbodyUnpaidPerjadin').html('<tr><td colspan="4" class="text-center py-3 text-muted">Tidak ada tagihan perjadin yang menunggu pencairan.</td></tr>');
                    return;
                }

                let html = '';
                data.forEach(function(item, idx) {
                    html += `
                    <tr>
                        <td class="text-center align-middle">
                            <input type="checkbox" class="check-perjadin-item" data-id="${item.id}" value="${idx}">
                        </td>
                        <td class="align-middle">
                            <div class="font-weight-bold">${item.guru_name}</div>
                            <small class="text-muted font-tabular">${item.bank_name}: ${item.account_number}</small>
                        </td>
                        <td class="align-middle small">
                            <strong>${item.tujuan}</strong><br>
                            <span class="text-muted">Tgl: ${item.tanggal}</span>
                        </td>
                        <td class="text-right align-middle font-tabular font-weight-bold text-success">
                            ${formatRupiah(item.net_amount)}
                        </td>
                    </tr>
                    `;
                });
                $('#tbodyUnpaidPerjadin').html(html);
            }
        });
    });

    $('#checkAllPerjadin').change(function() {
        $('.check-perjadin-item').prop('checked', $(this).is(':checked'));
    });

    $('#btnInsertPerjadin').click(function() {
        let selectedIndexes = [];
        $('.check-perjadin-item:checked').each(function() {
            selectedIndexes.push(parseInt($(this).val()));
        });

        if (selectedIndexes.length === 0) {
            Swal.fire('Pilih Data', 'Pilih minimal satu tagihan perjadin untuk dimasukkan.', 'info');
            return;
        }

        selectedIndexes.forEach(function(i) {
            let item = unpaidPerjadinList[i];
            let accString = `${item.bank_name}. ${item.account_number}`;
            let recipient = item.recipient_name || item.account_holder || item.guru_name;
            addItemRow(recipient, accString, item.net_amount, item.description, 'perjadin', item.id, '');
        });

        let modalEl = document.getElementById('modalPullPerjadin');
        if (window.bootstrap && window.bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).hide();
        } else if (window.$ && typeof $(modalEl).modal === 'function') {
            $(modalEl).modal('hide');
        }
    });

    // Handle Tarik Rencana Belanja / Nota BOS
    $('#btnPullRencanaNota').click(function() {
        let modalEl = document.getElementById('modalPullRencanaNota');
        if (window.bootstrap && window.bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else if (window.$ && typeof $(modalEl).modal === 'function') {
            $(modalEl).modal('show');
        }

        $('#loadingRencanaNota').show();
        $('#contentRencanaNota').hide();

        $.ajax({
            url: "{{ route($routePrefix . '.api.unpaid-rencana-notas', [], false) }}",
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                unpaidRencanaNotaList = data;
                $('#loadingRencanaNota').hide();
                $('#contentRencanaNota').show();

                if (data.length === 0) {
                    $('#tbodyUnpaidRencanaNota').html('<tr><td colspan="6" class="text-center py-3 text-muted">Tidak ada rencana nota belanja yang menunggu proses TNT.</td></tr>');
                    return;
                }

                let html = '';
                data.forEach(function(item, idx) {
                    let conflictHtml = '';
                    if (item.has_conflict && item.conflict_messages && item.conflict_messages.length > 0) {
                        conflictHtml = `
                        <div class="mt-1">
                            <span class="badge badge-danger text-wrap text-left p-1" style="font-size: 0.72rem; line-height: 1.3;">
                                <i class="fas fa-exclamation-triangle mr-1"></i><strong>Perhatian Konflik:</strong><br>${item.conflict_messages.join('<br>')}
                            </span>
                        </div>
                        `;
                    }

                    let summaryHtml = item.items_summary ? `<div class="text-secondary small mt-1" style="font-size: 0.75rem;"><i class="fas fa-box mr-1 text-muted"></i>${item.items_summary}</div>` : '';

                    let typeBadge = '';
                    if (item.type === 'utilitas') {
                        typeBadge = '<span class="badge badge-warning text-dark" style="font-size: 0.68rem;"><i class="fas fa-bolt mr-1"></i>Utilitas</span>';
                    } else if (item.is_online) {
                        typeBadge = `<span class="badge badge-warning text-dark" style="font-size: 0.68rem;"><i class="fas fa-store mr-1"></i>${item.marketplace_name}</span> <span class="badge badge-light border text-success font-tabular" style="font-size: 0.68rem;">VA: ${item.account_number || '-'}</span>`;
                    }

                    html += `
                    <tr class="${item.has_conflict ? 'table-warning' : ''}">
                        <td class="text-center align-middle">
                            <input type="checkbox" class="check-rencana-nota-item" data-id="${item.id}" value="${idx}">
                        </td>
                        <td class="text-center align-middle font-tabular font-weight-bold text-info">
                            ${item.no_nota}
                        </td>
                        <td class="align-middle">
                            <div class="font-weight-bold text-dark">${item.title}</div>
                            <small class="text-muted font-tabular">Tgl Rencana: ${item.planned_date}</small>
                            ${summaryHtml}
                            ${conflictHtml}
                        </td>
                        <td class="align-middle">
                            <span class="font-weight-500">${item.supplier_name}</span>
                            ${item.recipient_name ? `<br><small class="text-dark"><i class="far fa-user mr-1 text-muted"></i>a.n. <strong>${item.recipient_name}</strong></small>` : ''}
                            ${typeBadge ? `<br>${typeBadge}` : ''}
                        </td>
                        <td class="text-center align-middle font-tabular">
                            ${item.items_count} Item
                        </td>
                        <td class="text-right align-middle font-tabular font-weight-bold text-success">
                            ${formatRupiah(item.total_amount)}
                        </td>
                    </tr>
                    `;
                });
                $('#tbodyUnpaidRencanaNota').html(html);
            }
        });
    });

    $('#checkAllRencanaNota').change(function() {
        $('.check-rencana-nota-item').prop('checked', $(this).is(':checked'));
    });

    function doInsertSelectedRencana(selectedIndexes) {
        selectedIndexes.forEach(function(i) {
            let item = unpaidRencanaNotaList[i];
            let accString = '';
            if (item.is_online) {
                accString = formatVaAccountNumber(item.account_number, item.bank_name || 'Bank Jatim');
            } else {
                let bName = (item.bank_name || 'Bank Jatim').trim();
                let rawAcc = (item.account_number || '').trim();
                if (rawAcc) {
                    if (rawAcc.toUpperCase().startsWith(bName.toUpperCase() + '.')) {
                        accString = rawAcc;
                    } else if (rawAcc.toUpperCase().startsWith(bName.toUpperCase() + ' ')) {
                        accString = bName + '. ' + rawAcc.substring(bName.length).trim();
                    } else {
                        accString = `${bName}. ${rawAcc}`;
                    }
                } else {
                    accString = '';
                }
            }
            let recipient = (item.recipient_name !== undefined && item.recipient_name !== null) ? item.recipient_name : '';
            let desc = item.title || item.description || '';
            if (desc.startsWith('Belanja: ')) {
                desc = desc.substring(9).trim();
            }
            let isUtilitas = (item.type === 'utilitas');
            addItemRow(recipient, accString, item.total_amount, desc, 'rencana_nota', '', item.id, item.is_online, '', isUtilitas);
        });

        let modalEl = document.getElementById('modalPullRencanaNota');
        if (window.bootstrap && window.bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).hide();
        } else if (window.$ && typeof $(modalEl).modal === 'function') {
            $(modalEl).modal('hide');
        }
    }

    $('#btnInsertRencanaNota').click(function() {
        let selectedIndexes = [];
        $('.check-rencana-nota-item:checked').each(function() {
            selectedIndexes.push(parseInt($(this).val()));
        });

        if (selectedIndexes.length === 0) {
            Swal.fire('Pilih Data', 'Pilih minimal satu rencana nota untuk dimasukkan.', 'info');
            return;
        }

        let conflictItems = selectedIndexes.map(i => unpaidRencanaNotaList[i]).filter(it => it && it.has_conflict);
        if (conflictItems.length > 0) {
            let details = conflictItems.map(it => `<strong>${it.no_nota} (${it.title}):</strong><br>${it.conflict_messages.join('<br>')}`).join('<hr class="my-2">');
            Swal.fire({
                icon: 'warning',
                title: 'Peringatan: Potensi Transaksi Dobel!',
                html: `<div class="text-left small mb-2 text-danger">Item rencana belanja yang Anda pilih sudah pernah direalisasikan di BKU atau melebihi sisa anggaran:</div><div class="text-left small bg-light p-2 border rounded" style="max-height: 200px; overflow-y: auto;">${details}</div><div class="mt-2 small text-muted">Apakah Anda yakin tetap ingin memasukkan rencana belanja ini ke Surat TNT?</div>`,
                showCancelButton: true,
                confirmButtonText: 'Ya, Tetap Masukkan',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    doInsertSelectedRencana(selectedIndexes);
                }
            });
            return;
        }

        doInsertSelectedRencana(selectedIndexes);
    });
});
</script>
@stop
