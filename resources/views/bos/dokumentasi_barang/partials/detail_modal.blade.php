{{-- Modal Detail & Galeri Foto Dokumentasi Barang --}}
<div class="modal fade" id="modalDokumentasiDetail" tabindex="-1" role="dialog" aria-labelledby="modalDokumentasiDetailLabel" aria-hidden="true" style="overflow-y: auto !important;">
    <div class="modal-dialog modal-lg" role="document" style="margin: 1.25rem auto;">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-dark text-white py-2 px-3 d-flex justify-content-between align-items-center">
                <h6 class="modal-title font-weight-bold m-0" id="modalDokumentasiDetailLabel" style="font-size: 0.95rem;">
                    <i class="fas fa-images mr-1"></i> Galeri Foto Dokumentasi
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                {{-- Detail Nota Info --}}
                <div class="card card-outline card-info mb-3">
                    <div class="card-body p-2">
                        <div class="row align-items-center">
                            <div class="col-12 col-md-7">
                                <h6 class="font-weight-bold mb-1 text-primary" id="detail_no_nota">-</h6>
                                <div class="text-muted" style="font-size: 0.78rem;">
                                    <span class="mr-3"><i class="fas fa-store mr-1 text-secondary"></i> <span id="detail_toko">-</span></span>
                                    <span class="mr-3"><i class="fas fa-calendar mr-1 text-secondary"></i> <span id="detail_tanggal">-</span></span>
                                    <span id="detail_nominal_badge" class="badge badge-light border">Rp 0</span>
                                </div>
                                <div class="mt-1" style="font-size: 0.82rem;" id="detail_uraian">-</div>
                            </div>
                            <div class="col-12 col-md-5 mt-2 mt-md-0">
                                <div class="p-2 bg-light rounded border">
                                    <div class="text-muted text-uppercase font-weight-bold mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">
                                        <i class="fas fa-user-circle text-info mr-1"></i> Akun Pengirim Foto:
                                    </div>
                                    <div class="font-weight-bold text-dark text-truncate" id="detail_sender_name" style="font-size: 0.85rem;">-</div>
                                    <div class="text-muted text-truncate mt-1" id="detail_sender_detail" style="font-size: 0.72rem;">-</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Galeri Foto-Foto --}}
                <div class="row" id="detail_photos_container">
                    <div class="col-12 text-center py-4 text-muted">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2 mb-0" style="font-size: 0.8rem;">Memuat foto dokumentasi...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>
