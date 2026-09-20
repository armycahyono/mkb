{{-- Modal Popup Preview Bukti Foto / Nota --}}
<div class="modal fade" id="modalPreviewBuktiFoto" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1065;">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="max-width: 860px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 10px; overflow: hidden;">
            <div class="modal-header bg-dark text-white py-2 px-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center" style="gap: 8px;">
                    <i class="fas fa-image text-info" style="font-size: 1.1rem;"></i>
                    <div>
                        <h6 class="modal-title font-weight-bold m-0" id="previewBuktiTitle" style="font-size: 0.95rem;">
                            Bukti Foto / Nota
                        </h6>
                        <small class="text-white-50 d-block" id="previewBuktiSubtitle" style="font-size: 0.76rem; line-height: 1.2;"></small>
                    </div>
                </div>
                <div class="d-flex align-items-center" style="gap: 6px;">
                    <button type="button" class="btn btn-xs btn-outline-light" id="btnRotateReceipt" title="Putar Gambar 90°">
                        <i class="fas fa-redo-alt mr-1"></i> Putar
                    </button>
                    <a href="#" target="_blank" class="btn btn-xs btn-outline-light" id="btnPreviewOpenTab" title="Buka Gambar Asli di Tab Baru">
                        <i class="fas fa-external-link-alt mr-1"></i> Tab Baru
                    </a>
                    <a href="#" download class="btn btn-xs btn-outline-light" id="btnPreviewDownload" title="Unduh Berkas">
                        <i class="fas fa-download mr-1"></i> Unduh
                    </a>
                    <button type="button" class="close text-white ml-2 btn-dismiss-modal" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" style="opacity: 0.9; text-shadow: none;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
            <div class="modal-body p-2 text-center" style="background-color: #0f172a; min-height: 320px; display: flex; flex-direction: column; justify-content: center; align-items: center; position: relative;">
                {{-- Spinner Loading --}}
                <div id="previewReceiptLoading" class="text-white py-5" style="display: none;">
                    <i class="fas fa-spinner fa-spin fa-2x text-info mb-2"></i>
                    <div class="small text-white-50">Memuat berkas bukti...</div>
                </div>

                {{-- Image Preview Container --}}
                <div id="previewReceiptImageWrapper" style="display: none; width: 100%; max-height: 75vh; overflow: auto; padding: 10px;">
                    <img id="previewReceiptImage" src="" class="img-fluid rounded shadow-sm" style="max-height: 70vh; max-width: 100%; object-fit: contain; transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1); cursor: zoom-in;" alt="Bukti Foto Nota" title="Klik untuk memperbesar/memperkecil">
                </div>

                {{-- PDF Preview Container --}}
                <div id="previewReceiptPdfWrapper" style="display: none; width: 100%; height: 75vh;">
                    <iframe id="previewReceiptPdfFrame" src="" style="width: 100%; height: 100%; border: none; border-radius: 4px; background: #fff;"></iframe>
                </div>

                {{-- Unsupported Format Fallback --}}
                <div id="previewReceiptFallback" class="text-white py-5" style="display: none;">
                    <i class="fas fa-file-alt fa-3x text-warning mb-3"></i>
                    <p class="mb-2 text-light" id="previewReceiptFallbackText">Format dokumen tidak dapat dipratinjau langsung di browser.</p>
                    <a href="#" id="previewReceiptFallbackLink" target="_blank" class="btn btn-sm btn-info">
                        <i class="fas fa-download mr-1"></i> Unduh / Buka Dokumen
                    </a>
                </div>
            </div>
            <div class="modal-footer py-2 px-3 bg-light d-flex justify-content-between align-items-center">
                <div class="small text-muted text-truncate mr-2" id="previewBuktiMeta" style="max-width: 70%;"></div>
                <button type="button" class="btn btn-secondary btn-sm btn-dismiss-modal" data-dismiss="modal" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
