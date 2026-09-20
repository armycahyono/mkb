<div class="modal fade" id="modalSettlePanjar" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <form id="formSettlePanjar" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="settlePanjarId">
            <input type="hidden" name="real_amount" id="inputRealAmount">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark py-2 px-3 d-flex align-items-center justify-content-between">
                    <h6 class="modal-title font-weight-bold m-0" style="font-size: 1rem;">
                        <i class="fas fa-receipt mr-1"></i> Pertanggungjawaban Kasbon (Laporan SPJ)
                    </h6>
                    <button type="button" class="close text-dark btn-dismiss-modal" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-3">
                    {{-- Informasi Awal Kasbon --}}
                    <div class="card mb-2 border shadow-none bg-light">
                        <div class="card-body py-2 px-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="d-flex mb-1" style="font-size: 0.875rem;">
                                        <div class="text-muted" style="width: 100px;">Penerima</div>
                                        <div class="text-muted mr-2">:</div>
                                        <div class="font-weight-bold text-dark" id="settlePersonName">-</div>
                                    </div>
                                    <div class="d-flex mb-1" style="font-size: 0.875rem;">
                                        <div class="text-muted" style="width: 100px;">Tgl Kasbon</div>
                                        <div class="text-muted mr-2">:</div>
                                        <div class="text-dark" id="settlePanjarDateDisplay">-</div>
                                    </div>
                                    <div class="d-flex mb-0" style="font-size: 0.875rem;">
                                        <div class="text-muted" style="width: 100px;">Uraian</div>
                                        <div class="text-muted mr-2">:</div>
                                        <div class="text-dark text-truncate" id="settleDesc">-</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex mb-1" id="settleInitialSection" style="font-size: 0.875rem;">
                                        <div class="text-muted" style="width: 120px;">Kasbon Awal</div>
                                        <div class="text-muted mr-2">:</div>
                                        <div class="font-weight-bold text-dark" id="settlePanjarAmountDisplay">Rp 0</div>
                                    </div>
                                    <div class="d-flex mb-1" id="settleAlreadySettledSection" style="font-size: 0.875rem; display: none;">
                                        <div class="text-muted" style="width: 120px;">Sudah Dibayar</div>
                                        <div class="text-muted mr-2">:</div>
                                        <div class="font-weight-bold text-dark" id="settleSettledAmountDisplay">Rp 0</div>
                                    </div>
                                    <div class="d-flex mb-0" style="font-size: 0.875rem;">
                                        <div class="text-muted font-weight-bold" style="width: 120px;">Sisa Kasbon</div>
                                        <div class="text-muted mr-2">:</div>
                                        <div class="font-weight-bold text-danger" id="settleRemainingDisplay">Rp 0</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tanggal Laporan SPJ --}}
                    <div class="d-flex align-items-center mb-2" style="font-size: 0.875rem;">
                        <span class="font-weight-bold text-dark mr-2" style="width: 100px;">Tgl SPJ <span class="text-danger">*</span> :</span>
                        <div style="width: 180px;">
                            <input type="date" name="settle_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    {{-- NOTA BELANJA --}}
                    <div class="card border mb-2 shadow-none">
                        <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center border-bottom">
                            <span class="font-weight-bold text-dark" style="font-size: 0.95rem;">
                                <i class="fas fa-file-invoice mr-1 text-primary"></i> Nota Belanja
                            </span>
                            <button type="button" class="btn btn-sm btn-outline-primary font-weight-bold" id="btnAddReceiptBlock">
                                <i class="fas fa-plus mr-1"></i> Tambah Nota
                            </button>
                        </div>
                        <div class="card-body p-2" id="containerReceiptBlocks" style="max-height: 480px; overflow-y: auto;">
                            {{-- Receipt Block Items rendered here --}}
                        </div>
                        <div class="card-footer py-2 px-3 bg-light d-flex justify-content-between align-items-center">
                            <span class="text-muted font-weight-bold" style="font-size: 0.875rem;">Total Seluruh Nota:</span>
                            <strong class="text-primary font-weight-bold" id="grandTotalReceiptsDisplay" style="font-size: 1.05rem;">Rp 0</strong>
                        </div>
                    </div>

                    {{-- SISA KASBON & UANG KEMBALI --}}
                    <div class="card border mb-2 shadow-none bg-light">
                        <div class="card-body p-2 px-3">
                            <div class="row align-items-center">
                                <div class="col-md-6 mb-2 mb-md-0">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="small font-weight-bold text-dark mb-0" style="font-size: 0.78rem;">
                                            <i class="fas fa-hand-holding-usd text-success mr-1"></i> Uang Tunai Dikembalikan (Rp)
                                        </label>
                                        <button type="button" class="btn btn-link text-success p-0 font-weight-bold" id="btnAutoFillCashReturn" style="font-size: 0.72rem;" title="Klik untuk mengembalikan sisa penuh">
                                            <i class="fas fa-magic mr-1"></i> Sisa Penuh
                                        </button>
                                    </div>
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text font-weight-bold bg-white text-success">Rp</span>
                                        </div>
                                        <input type="text" name="cash_return_amount" id="inputCashReturnAmount" class="form-control form-control-sm font-weight-bold text-success rupiah-input settle-calc-input" placeholder="0" value="0" autocomplete="off">
                                    </div>
                                    <input type="hidden" name="is_partial" id="inputIsPartial" value="0">
                                    <input type="hidden" name="nota_amount" id="inputNotaAmount" value="0">
                                </div>
                                <div class="col-md-6">
                                    <div class="p-2 rounded bg-white border d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="text-muted small" style="font-size: 0.72rem;">Sisa Tanggungan Kasbon Akhir:</div>
                                            <div class="font-weight-bold" style="font-size: 1.1rem;" id="displayRemainingAfterSettle">Rp 0</div>
                                            <span id="displayRemainingNote" class="d-none"></span>
                                            <span id="settleCalculationText" class="d-none"></span>
                                        </div>
                                        <div class="text-right">
                                            <span id="settleStatusBadge" class="badge badge-secondary p-1 font-weight-bold" style="font-size: 0.78rem;">
                                                Masukkan nota
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Kaitan Dokumen & Catatan --}}
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label class="small text-muted mb-0" style="font-size: 0.75rem;">Kaitkan Rencana Belanja (Opsional)</label>
                            <select name="bos_rencana_belanja_id" id="settleRencanaBelanjaId" class="form-control form-control-sm">
                                <option value="">-- Tidak Terkait --</option>
                                @foreach($rencanaBelanjas as $rb)
                                    <option value="{{ $rb->id }}" data-amount="{{ $rb->total_amount }}">{{ $rb->no_nota }} - {{ $rb->supplier_name ?: $rb->title }} (Rp {{ number_format($rb->total_amount, 0, ',', '.') }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="small text-muted mb-0" style="font-size: 0.75rem;">Kaitkan Kwitansi Perjadin (Opsional)</label>
                            <select name="perjadin_item_id" id="settlePerjadinItemId" class="form-control form-control-sm">
                                <option value="">-- Tidak Terkait --</option>
                                @foreach($perjadinItems as $pi)
                                    <option value="{{ $pi->id }}" data-amount="{{ $pi->net_amount }}">{{ $pi->receipt_number ?: 'KWT-'.$pi->id }} - {{ $pi->guru?->name }} (Rp {{ number_format($pi->net_amount, 0, ',', '.') }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <input type="text" name="notes" id="settleNotesInput" class="form-control form-control-sm" placeholder="Catatan SPJ / keterangan tambahan (opsional)">
                    </div>
                </div>
                <div class="modal-footer py-2 px-3 bg-light d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small text-muted">Total SPJ:</span>
                        <strong class="text-dark font-weight-bold ml-1" id="settleTotalReportedDisplay" style="font-size: 1.05rem;">Rp 0</strong>
                    </div>
                    <div>
                        <button type="button" class="btn btn-secondary btn-sm btn-dismiss-modal mr-1" data-dismiss="modal" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning btn-sm font-weight-bold shadow-sm" id="btnSubmitSettle">
                            <i class="fas fa-check-circle mr-1"></i> Simpan SPJ
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let receiptBlockCount = 0;

    // Template Block Nota
    function renderReceiptBlock(blockIdx, data = {}) {
        return `
        <div class="receipt-block card mb-3 border shadow-none bg-white" data-block-id="${blockIdx}">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                    <span class="font-weight-bold text-primary" style="font-size: 0.95rem;">
                        <i class="fas fa-receipt mr-1"></i> <span class="receipt-block-title">Nota #${blockIdx + 1}</span>
                    </span>
                    ${blockIdx > 0 ? `
                    <button type="button" class="btn btn-link text-danger p-0 small font-weight-bold btn-remove-receipt-block" title="Hapus Nota Ini">
                        <i class="fas fa-trash-alt mr-1"></i> Hapus Nota
                    </button>
                    ` : ''}
                </div>

                <div class="row align-items-end mb-2">
                    <div class="col-md-5 mb-2 mb-md-0">
                        <label class="small font-weight-bold text-dark mb-1">Toko / Uraian Belanja <span class="text-danger">*</span></label>
                        <input type="text" name="receipts[${blockIdx}][person_name]" class="form-control form-control-sm receipt-store-name" placeholder="Nama toko / rekanan / uraian" value="${data.person_name || ''}" required>
                    </div>
                    <div class="col-md-3 mb-2 mb-md-0">
                        <label class="small font-weight-bold text-dark mb-1">No Nota / Bukti</label>
                        <input type="text" name="receipts[${blockIdx}][evidence_number]" class="form-control form-control-sm receipt-evidence-no" placeholder="Contoh: No. 123" value="${data.evidence_number || ''}">
                    </div>
                    <div class="col-md-4 mb-2 mb-md-0">
                        <label class="small font-weight-bold text-primary mb-1">Total Nota (Rp) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend"><span class="input-group-text bg-light font-weight-bold text-primary">Rp</span></div>
                            <input type="text" name="receipts[${blockIdx}][amount]" class="form-control form-control-sm font-weight-bold text-primary rupiah-input receipt-total-amount settle-calc-input" placeholder="0" value="${data.amount ? Number(data.amount).toLocaleString('id-ID') : ''}" required>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="d-flex align-items-center">
                        <label class="small text-muted font-weight-bold mb-0 mr-2">Foto / Scan Bukti Nota :</label>
                        <input type="file" name="receipts[${blockIdx}][receipt_file]" class="receipt-file-input" accept="image/*,application/pdf" style="font-size: 0.8rem;">
                    </div>
                    <div>
                        <button type="button" class="btn btn-xs btn-outline-primary btn-add-block-item font-weight-bold" data-block-id="${blockIdx}">
                            <i class="fas fa-plus mr-1"></i> Tambah Item Barang
                        </button>
                    </div>
                </div>

                {{-- Tabel Rincian Nota --}}
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0 bg-white table-receipt-items" style="font-size: 0.8rem;">
                        <thead class="bg-light text-center">
                            <tr>
                                <th>Nama Barang <span class="text-danger">*</span></th>
                                <th style="width: 90px;">Qty</th>
                                <th style="width: 100px;">Satuan</th>
                                <th style="width: 140px;">Harga Satuan (Rp)</th>
                                <th style="width: 140px;">Subtotal (Rp)</th>
                                <th style="width: 40px;"></th>
                            </tr>
                        </thead>
                        <tbody class="tbody-receipt-items">
                            <tr class="empty-block-items-row">
                                <td colspan="6" class="text-center text-muted py-2 small font-italic">
                                    Belum ada rincian barang. Klik "+ Tambah Item Barang" untuk merinci item nota ini (opsional jika total nota sudah diisi).
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        `;
    }

    // Template Baris Item Barang dalam Nota
    function renderReceiptItemRow(blockIdx, itemIdx, item = {}) {
        let name = item.item_name || '';
        let qty = item.quantity !== undefined ? item.quantity : 1;
        let unit = item.unit || 'pcs';
        let price = item.price ? Number(item.price).toLocaleString('id-ID') : '';
        let numPrice = item.price || 0;
        let numQty = parseFloat(qty) || 0;
        let total = Math.round(numQty * numPrice);

        return `
        <tr class="receipt-item-row">
            <td class="p-1">
                <input type="text" name="receipts[${blockIdx}][items][${itemIdx}][item_name]" class="form-control form-control-sm r-item-name" placeholder="Nama barang" value="${name}" required>
            </td>
            <td class="p-1">
                <input type="number" step="any" min="0.01" name="receipts[${blockIdx}][items][${itemIdx}][quantity]" class="form-control form-control-sm text-center r-item-qty" placeholder="1" value="${qty}">
            </td>
            <td class="p-1">
                <input type="text" name="receipts[${blockIdx}][items][${itemIdx}][unit]" class="form-control form-control-sm text-center r-item-unit" placeholder="pcs" value="${unit}">
            </td>
            <td class="p-1">
                <input type="text" name="receipts[${blockIdx}][items][${itemIdx}][price]" class="form-control form-control-sm text-right rupiah-input r-item-price" placeholder="0" value="${price}">
            </td>
            <td class="p-1 text-right align-middle font-weight-bold">
                <span class="r-item-subtotal-display">Rp ${total ? total.toLocaleString('id-ID') : '0'}</span>
                <input type="hidden" name="receipts[${blockIdx}][items][${itemIdx}][total_price]" class="r-item-subtotal-input" value="${total}">
            </td>
            <td class="p-1 text-center align-middle">
                <button type="button" class="btn btn-xs btn-link text-danger p-0 btn-remove-receipt-item" title="Hapus Item"><i class="fas fa-trash-alt"></i></button>
            </td>
        </tr>
        `;
    }

    // Inisialisasi Modal Settle
    window.initSettlePanjarModal = function() {
        $('#containerReceiptBlocks').empty();
        $('#inputCashReturnAmount').val('0');
        $('#inputIsPartial').val('0');
        receiptBlockCount = 0;

        // Tambah 1 nota default
        addReceiptBlock();
        calculateAllSettleTotals();
    };

    function addReceiptBlock(data = {}) {
        let blockHtml = renderReceiptBlock(receiptBlockCount, data);
        $('#containerReceiptBlocks').append(blockHtml);
        receiptBlockCount++;
        calculateAllSettleTotals();
    }

    $('#btnAddReceiptBlock').on('click', function(e) {
        e.preventDefault();
        addReceiptBlock();
    });

    $(document).on('click', '.btn-remove-receipt-block', function(e) {
        e.preventDefault();
        $(this).closest('.receipt-block').remove();
        // Rename titles
        $('.receipt-block').each(function(idx) {
            $(this).find('.receipt-block-title').text('Nota #' + (idx + 1));
        });
        calculateAllSettleTotals();
    });

    // Tambah item barang dalam satu block nota
    $(document).on('click', '.btn-add-block-item', function(e) {
        e.preventDefault();
        let blockId = $(this).data('block-id');
        let $block = $(this).closest('.receipt-block');
        $block.find('.empty-block-items-row').remove();
        let itemIdx = $block.find('.receipt-item-row').length;
        let rowHtml = renderReceiptItemRow(blockId, itemIdx);
        $block.find('.tbody-receipt-items').append(rowHtml);
    });

    $(document).on('click', '.btn-remove-receipt-item', function(e) {
        e.preventDefault();
        let $block = $(this).closest('.receipt-block');
        $(this).closest('tr').remove();
        if ($block.find('.receipt-item-row').length === 0) {
            $block.find('.tbody-receipt-items').html(`
                <tr class="empty-block-items-row">
                    <td colspan="6" class="text-center text-muted py-2 small font-italic">
                        Belum ada rincian barang. Klik "+ Tambah Item Barang" untuk merinci item nota ini (opsional jika total nota sudah diisi).
                    </td>
                </tr>
            `);
        }
        recalcBlockItemsTotal($block);
    });

    // Recalculate Subtotal Item & Total Nota
    $(document).on('input', '.r-item-qty, .r-item-price', function() {
        let $row = $(this).closest('tr');
        let qty = parseFloat($row.find('.r-item-qty').val()) || 0;
        let rawPrice = $row.find('.r-item-price').val().replace(/[^0-9]/g, '');
        let price = parseFloat(rawPrice) || 0;
        let subtotal = Math.round(qty * price);

        $row.find('.r-item-subtotal-display').text('Rp ' + subtotal.toLocaleString('id-ID'));
        $row.find('.r-item-subtotal-input').val(subtotal);

        let $block = $(this).closest('.receipt-block');
        recalcBlockItemsTotal($block);
    });

    function recalcBlockItemsTotal($block) {
        let blockSum = 0;
        let hasItems = false;
        $block.find('.receipt-item-row').each(function() {
            hasItems = true;
            let sub = parseFloat($(this).find('.r-item-subtotal-input').val()) || 0;
            blockSum += sub;
        });

        if (hasItems && blockSum > 0) {
            $block.find('.receipt-total-amount').val(blockSum.toLocaleString('id-ID'));
        }
        calculateAllSettleTotals();
    }

    // Perhitungan Otomatis Seluruh Komponen SPJ Kasbon
    window.calculateAllSettleTotals = function() {
        let totalReceipts = 0;
        $('.receipt-total-amount').each(function() {
            let val = parseFloat($(this).val().replace(/[^0-9]/g, '')) || 0;
            totalReceipts += val;
        });
        $('#grandTotalReceiptsDisplay').text('Rp ' + totalReceipts.toLocaleString('id-ID'));
        $('#inputRealAmount').val(totalReceipts);
        $('#inputNotaAmount').val(totalReceipts);

        let currentRemaining = window.currentRemainingAmount || 0;
        let cashReturn = parseFloat($('#inputCashReturnAmount').val().replace(/[^0-9]/g, '')) || 0;

        // Rumus: Sisa Kasbon Akhir = Sisa Tanggungan - Total Semua Nota - Uang Tunai Dikembalikan
        let sisaKasbonAkhir = currentRemaining - totalReceipts - cashReturn;

        let $badge = $('#settleStatusBadge');
        let $displayRemaining = $('#displayRemainingAfterSettle');
        let $inputIsPartial = $('#inputIsPartial');

        let totalReported = totalReceipts + cashReturn;

        if (totalReported <= 0) {
            $displayRemaining.attr('class', 'font-weight-bold text-dark').text('Rp ' + currentRemaining.toLocaleString('id-ID'));
            $inputIsPartial.val('0');
            $badge.attr('class', 'badge badge-secondary p-1 font-weight-bold').text('Masukkan nota');
            $('#settleTotalReportedDisplay').text('Rp 0');
            return;
        }

        if (sisaKasbonAkhir > 0) {
            // Masih ada sisa kasbon -> Dibayar Parsial (SPJ Lanjutan)
            $displayRemaining.attr('class', 'font-weight-bold text-warning').text('Rp ' + sisaKasbonAkhir.toLocaleString('id-ID'));
            $inputIsPartial.val('1');
            $badge.attr('class', 'badge badge-warning text-dark p-1 font-weight-bold').html('<i class="fas fa-hourglass-half mr-1"></i> Parsial (SPJ Lanjutan)');
            $('#settleTotalReportedDisplay').text('Rp ' + totalReported.toLocaleString('id-ID'));
        } else if (sisaKasbonAkhir === 0) {
            // Lunas Pas (Selesai Penuh)
            $displayRemaining.attr('class', 'font-weight-bold text-success').text('Rp 0');
            $inputIsPartial.val('0');
            $badge.attr('class', 'badge badge-success p-1 font-weight-bold').html('<i class="fas fa-check-double mr-1"></i> Lunas Selesai');
            $('#settleTotalReportedDisplay').text('Rp ' + currentRemaining.toLocaleString('id-ID'));
        } else {
            // Pengeluaran belanja + uang kembali melebihi sisa kasbon (Nombok)
            let shortage = Math.abs(sisaKasbonAkhir);
            $displayRemaining.attr('class', 'font-weight-bold text-danger').text('- Rp ' + shortage.toLocaleString('id-ID'));
            $inputIsPartial.val('0');
            $badge.attr('class', 'badge badge-danger p-1 font-weight-bold').html('<i class="fas fa-exclamation-circle mr-1"></i> Nombok (Kas Kurang)');
            $('#settleTotalReportedDisplay').text('Rp ' + totalReported.toLocaleString('id-ID'));
        }
    };

    $(document).on('input', '.settle-calc-input', function() {
        calculateAllSettleTotals();
    });

    // Tombol Sisa Penuh (Otomatis isi Uang Tunai Dikembalikan = Sisa Kasbon - Total Nota)
    $('#btnAutoFillCashReturn').on('click', function(e) {
        e.preventDefault();
        let totalReceipts = 0;
        $('.receipt-total-amount').each(function() {
            let val = parseFloat($(this).val().replace(/[^0-9]/g, '')) || 0;
            totalReceipts += val;
        });
        let currentRemaining = window.currentRemainingAmount || 0;
        let diff = currentRemaining - totalReceipts;
        if (diff > 0) {
            $('#inputCashReturnAmount').val(diff.toLocaleString('id-ID'));
        } else {
            $('#inputCashReturnAmount').val('0');
        }
        calculateAllSettleTotals();
    });
});
</script>

