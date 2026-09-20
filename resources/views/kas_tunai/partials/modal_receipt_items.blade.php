<div class="modal fade" id="modalViewReceiptItems" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white py-2 px-3 d-flex align-items-center justify-content-between">
                <h5 class="modal-title font-weight-bold m-0" id="modalViewReceiptTitle">
                    <i class="fas fa-file-invoice mr-1 text-warning"></i> Rincian Lengkap Transaksi Kas
                </h5>
                <button type="button" class="close text-white btn-dismiss-modal" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-3" id="viewDetailModalBody">
                {{-- Banner Jika Transaksi Anak SPJ --}}
                <div id="viewChildBanner" class="alert alert-info py-2 px-3 mb-3 small" style="display: none;">
                    <i class="fas fa-link mr-1"></i> Transaksi ini merupakan bagian dari laporan SPJ Kasbon: <strong id="viewParentKasDesc">-</strong>
                </div>

                {{-- Header Ringkasan Transaksi --}}
                <div class="card bg-light border mb-3 p-3">
                    <div class="row align-items-center">
                        <div class="col-md-7 mb-2 mb-md-0">
                            <div class="d-flex align-items-center mb-1">
                                <span class="badge badge-secondary mr-2" id="viewItemCode" style="font-size: 0.85rem;">KT26-0000</span>
                                <span class="badge" id="viewItemCategoryBadge" style="font-size: 0.85rem;">-</span>
                                <span class="badge ml-1" id="viewItemPanjarStatusBadge" style="display: none;">-</span>
                            </div>
                            <h6 class="font-weight-bold text-dark mb-1" id="viewItemDescription">-</h6>
                            <div class="small text-muted">
                                <span><i class="fas fa-calendar-alt mr-1"></i> <span id="viewItemDate">-</span></span> &bull; 
                                <span><i class="fas fa-user mr-1"></i> <span id="viewItemPerson">-</span></span>
                            </div>
                            <div class="small text-muted mt-1" id="viewItemEvidenceRow" style="display: none;">
                                <i class="fas fa-receipt mr-1"></i> No. Bukti / Kuitansi: <span class="font-weight-bold text-dark" id="viewItemEvidence">-</span>
                            </div>
                        </div>
                        <div class="col-md-5 text-md-right border-left">
                            <span class="text-muted small d-block" id="viewItemAmountLabel">Nominal Transaksi:</span>
                            <div class="font-weight-bold" style="font-size: 1.4rem;" id="viewItemTotalAmount">Rp 0</div>
                        </div>
                    </div>
                </div>

                {{-- SUMMARY KHUSUS KASBON (JIKA INI KASBON PANJAR KERJA) --}}
                <div id="sectionPanjarSummary" class="card border-warning mb-3 shadow-xs" style="display: none;">
                    <div class="card-header bg-light py-2 px-3 font-weight-bold small text-dark d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-balance-scale text-warning mr-1"></i> Rekapitulasi SPJ Kasbon</span>
                        <span id="viewPanjarSummaryStatus" class="badge badge-success">-</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="row text-center mb-2">
                            <div class="col-3">
                                <span class="text-muted small d-block">Uang Kasbon</span>
                                <strong class="text-dark font-weight-bold" id="viewSummaryPanjarAmount">Rp 0</strong>
                            </div>
                            <div class="col-3">
                                <span class="text-muted small d-block">Belanja Nota</span>
                                <strong class="text-primary font-weight-bold" id="viewSummaryTotalBelanja">Rp 0</strong>
                            </div>
                            <div class="col-3">
                                <span class="text-muted small d-block">Biaya Penyerta</span>
                                <strong class="text-info font-weight-bold" id="viewSummaryTotalExpenses">Rp 0</strong>
                            </div>
                            <div class="col-3">
                                <span class="text-muted small d-block">Sisa Uang Kembali</span>
                                <strong class="text-success font-weight-bold" id="viewSummaryTotalReturns">Rp 0</strong>
                            </div>
                        </div>
                        <div id="viewSummaryReimburseRow" class="alert alert-warning py-1 px-2 small mb-0 mt-2 text-center" style="display: none;">
                            <i class="fas fa-exclamation-circle mr-1"></i> Pengeluaran melebihi kasbon. Ada kekurangan / reimburse: <strong id="viewSummaryReimburseAmount">Rp 0</strong>
                        </div>
                        <div id="viewSummaryRemainingRow" class="alert alert-info py-1 px-2 small mb-0 mt-2 text-center" style="display: none;">
                            <i class="fas fa-hourglass-half mr-1"></i> Kasbon belum selesai lunas. Sisa tanggungan aktif: <strong id="viewSummaryRemainingAmount">Rp 0</strong>
                        </div>
                    </div>
                </div>

                {{-- MULTI-NOTA SPJ (JIKA KASBON MEMILIKI MULTI-NOTA) --}}
                <div id="sectionPanjarChildReceipts" class="mb-3" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="font-weight-bold small text-primary text-uppercase">
                            <i class="fas fa-store mr-1"></i> Rincian Nota-Nota Belanja Toko:
                        </span>
                    </div>
                    <div id="containerPanjarChildReceiptsList">
                        {{-- Rendered by JS --}}
                    </div>
                </div>

                {{-- BIAYA OPERASIONAL PENYERTA KASBON (TRANSPORT, MAKAN, DLL) --}}
                <div id="sectionPanjarChildExpenses" class="card border-info mb-3" style="display: none;">
                    <div class="card-header bg-light py-2 px-3 font-weight-bold small text-info">
                        <i class="fas fa-car-side mr-1"></i> Biaya Operasional Penyerta (Transport / Konsumsi / Honor)
                    </div>
                    <div class="card-body p-2">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0" style="font-size: 0.8125rem;">
                                <thead class="bg-light text-center">
                                    <tr>
                                        <th style="width: 5%;">No</th>
                                        <th style="width: 40%;">Jenis Biaya</th>
                                        <th style="width: 30%;">No. Bukti / Keterangan</th>
                                        <th style="width: 25%;" class="text-right">Nominal (Rp)</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyChildExpenses">
                                    {{-- Rendered by JS --}}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- TABEL BARANG LANGSUNG (TRANSAKSI BELANJA TUNAI LANGSUNG) --}}
                <div id="sectionDirectItems" class="table-responsive mb-3" style="display: none;">
                    <span class="font-weight-bold small text-primary text-uppercase d-block mb-1">
                        <i class="fas fa-boxes mr-1"></i> Rincian Komoditas Barang Belanja:
                    </span>
                    <table class="table table-sm table-bordered table-striped mb-0" id="tableReceiptDetails" style="font-size: 0.8125rem;">
                        <thead class="bg-secondary text-white text-center">
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th style="width: 35%;">Nama Barang</th>
                                <th style="width: 10%;">Qty</th>
                                <th style="width: 12%;">Satuan</th>
                                <th style="width: 18%;">Harga Satuan</th>
                                <th style="width: 20%;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyReceiptDetails">
                            {{-- Populated by AJAX --}}
                        </tbody>
                        <tfoot>
                            <tr class="bg-light font-weight-bold">
                                <td colspan="5" class="text-right py-2">Total Rincian Belanja:</td>
                                <td class="text-right text-primary py-2" id="viewItemSumTotal">Rp 0</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- Preview Nota Fisik / Lampiran --}}
                <div id="containerReceiptAttachment" class="card border p-2 text-center" style="display: none;">
                    <span class="font-weight-bold small text-muted mb-2 d-block text-left"><i class="fas fa-paperclip mr-1"></i> Lampiran Foto / Berkas Nota:</span>
                    <div id="receiptAttachmentContent"></div>
                </div>
            </div>
            <div class="modal-footer py-2 px-3 bg-light">
                <button type="button" class="btn btn-secondary btn-sm btn-dismiss-modal" data-dismiss="modal" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
