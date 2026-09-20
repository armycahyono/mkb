{{-- Modal Tambah / Edit Dokumentasi Barang --}}
<div class="modal fade" id="modalDokumentasiForm" tabindex="-1" role="dialog" aria-labelledby="modalDokumentasiFormLabel" aria-hidden="true" style="overflow-y: auto !important;">
    <div class="modal-dialog modal-lg" role="document" style="margin: 1.25rem auto;">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-primary text-white py-2 px-3 d-flex justify-content-between align-items-center">
                <h6 class="modal-title font-weight-bold m-0" id="modalDokumentasiFormLabel" style="font-size: 0.95rem;">
                    <i class="fas fa-camera mr-1"></i> Form Dokumentasi Barang
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formDokumentasi" action="{{ route('bos.dokumentasi-barang.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div id="formMethodContainer"></div>
                <input type="hidden" name="kas_tunai_id" id="doc_kas_tunai_id">

                <div class="modal-body p-3">
                    {{-- Dropdown Pencarian Transaksi BOS (Realisasi / Kas) --}}
                    <div class="card card-outline card-secondary mb-3 shadow-none border">
                        <div class="card-header py-1 px-3 bg-light">
                            <span class="font-weight-bold text-dark" style="font-size: 0.82rem;">
                                <i class="fas fa-search mr-1 text-primary"></i> Hubungkan dengan Transaksi (Cari Nota / Kegiatan)
                            </span>
                        </div>
                        <div class="card-body p-2">
                            <select id="selectAvailableNota" class="form-control form-control-sm select2-nota" style="width: 100%;">
                                <option value="">-- Ketik untuk mencari nota / uraian belanja --</option>
                                @if(isset($availableNotas) && count($availableNotas) > 0)
                                    @foreach($availableNotas as $item)
                                        <option value="{{ $item['no_nota'] }}"
                                            data-no_nota="{{ $item['no_nota'] }}"
                                            data-tanggal="{{ $item['tanggal'] }}"
                                            data-uraian="{{ $item['uraian'] }}"
                                            data-nominal="{{ $item['nominal'] }}"
                                            data-toko="{{ $item['toko'] }}"
                                            data-kas_id="{{ $item['kas_id'] ?? '' }}">
                                            {{ $item['no_nota'] }} - {{ $item['uraian'] }} ({{ $item['source'] }})
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                            <small class="text-muted" style="font-size: 0.72rem;">Pilih untuk mengisi otomatis Nomor Nota, Tanggal, dan Uraian.</small>
                        </div>
                    </div>

                    {{-- Form Metadata --}}
                    <div class="row">
                        <div class="col-12 col-md-6 mb-2">
                            <label class="font-weight-bold text-dark mb-1" style="font-size: 0.82rem;">
                                Nomor Nota / Bukti <span class="text-muted font-weight-normal">(Opsional)</span>
                            </label>
                            <input type="text" name="no_nota" id="doc_no_nota" class="form-control form-control-sm" placeholder="Contoh: N26-0001 (Boleh dikosongkan)">
                        </div>
                        <div class="col-12 col-md-6 mb-2">
                            <label class="font-weight-bold text-dark mb-1" style="font-size: 0.82rem;">
                                Tanggal Pengambilan Foto <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="tanggal_terima" id="doc_tanggal_terima" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 col-md-6 mb-2">
                            <label class="font-weight-bold text-dark mb-1" style="font-size: 0.82rem;">Nama Rekanan / Toko</label>
                            <input type="text" name="nama_toko" id="doc_nama_toko" class="form-control form-control-sm" placeholder="Contoh: Toko Barokah ATK">
                        </div>
                        <div class="col-12 col-md-6 mb-2">
                            <label class="font-weight-bold text-dark mb-1" style="font-size: 0.82rem;">Total Nominal Belanja (Rp)</label>
                            <input type="number" step="any" name="nominal" id="doc_nominal" class="form-control form-control-sm" placeholder="0">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="font-weight-bold text-dark mb-1" style="font-size: 0.82rem;">
                            Uraian Belanja <span class="text-danger">*</span>
                        </label>
                        <textarea name="uraian" id="doc_uraian" class="form-control form-control-sm" rows="2" placeholder="Contoh: Pengadaan Papan Tulis Whiteboard dan Spidol" required></textarea>
                    </div>

                    {{-- Slot Foto 1, 2, 3 --}}
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="font-weight-bold text-dark" style="font-size: 0.85rem;">
                            <i class="fas fa-images mr-1 text-primary"></i> Foto Fisik Barang
                        </span>
                    </div>

                    <div class="row g-2 mb-2" id="photoSlotsContainer">
                        {{-- Slot 1 --}}
                        <div class="col-12 col-md-4 mb-2 photo-slot-card" data-slot="1">
                            <div class="card border h-100 shadow-none mb-0">
                                <div class="card-header bg-light py-1 px-2 text-center">
                                    <span class="font-weight-bold text-primary" style="font-size: 0.78rem;">Foto 1</span>
                                </div>
                                <div class="card-body p-2 text-center">
                                    <div class="photo-preview-box rounded border d-flex align-items-center justify-content-center bg-light" style="height: 110px; position: relative; overflow: hidden;">
                                        <img src="" class="img-fluid rounded preview-img d-none" style="max-height: 100%; object-fit: contain;">
                                        <div class="placeholder-text text-muted p-2 text-center" style="font-size: 0.75rem;">
                                            <i class="fas fa-image fa-2x mb-1 text-secondary"></i><br>Foto 1
                                        </div>
                                    </div>
                                    <div class="mt-2 d-flex gap-1">
                                        <button type="button" class="btn btn-primary btn-xs flex-fill btn-open-camera" data-slot="1">
                                            <i class="fas fa-camera mr-1"></i> Kamera
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-xs flex-fill btn-open-gallery" data-slot="1">
                                            <i class="fas fa-folder-open mr-1"></i> Galeri
                                        </button>
                                    </div>
                                    <input type="file" accept="image/*" capture="environment" class="d-none photo-file-input" id="file_camera_1" data-slot="1">
                                    <input type="file" accept="image/*" class="d-none photo-file-input" id="file_gallery_1" data-slot="1">
                                    <input type="hidden" name="photos[0]" class="compressed-photo-data" id="compressed_photo_1">
                                </div>
                            </div>
                        </div>

                        {{-- Slot 2 --}}
                        <div class="col-12 col-md-4 mb-2 photo-slot-card" data-slot="2">
                            <div class="card border h-100 shadow-none mb-0">
                                <div class="card-header bg-light py-1 px-2 text-center">
                                    <span class="font-weight-bold text-primary" style="font-size: 0.78rem;">Foto 2</span>
                                </div>
                                <div class="card-body p-2 text-center">
                                    <div class="photo-preview-box rounded border d-flex align-items-center justify-content-center bg-light" style="height: 110px; position: relative; overflow: hidden;">
                                        <img src="" class="img-fluid rounded preview-img d-none" style="max-height: 100%; object-fit: contain;">
                                        <div class="placeholder-text text-muted p-2 text-center" style="font-size: 0.75rem;">
                                            <i class="fas fa-image fa-2x mb-1 text-secondary"></i><br>Foto 2
                                        </div>
                                    </div>
                                    <div class="mt-2 d-flex gap-1">
                                        <button type="button" class="btn btn-primary btn-xs flex-fill btn-open-camera" data-slot="2">
                                            <i class="fas fa-camera mr-1"></i> Kamera
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-xs flex-fill btn-open-gallery" data-slot="2">
                                            <i class="fas fa-folder-open mr-1"></i> Galeri
                                        </button>
                                    </div>
                                    <input type="file" accept="image/*" capture="environment" class="d-none photo-file-input" id="file_camera_2" data-slot="2">
                                    <input type="file" accept="image/*" class="d-none photo-file-input" id="file_gallery_2" data-slot="2">
                                    <input type="hidden" name="photos[1]" class="compressed-photo-data" id="compressed_photo_2">
                                </div>
                            </div>
                        </div>

                        {{-- Slot 3 --}}
                        <div class="col-12 col-md-4 mb-2 photo-slot-card" data-slot="3">
                            <div class="card border h-100 shadow-none mb-0">
                                <div class="card-header bg-light py-1 px-2 text-center">
                                    <span class="font-weight-bold text-primary" style="font-size: 0.78rem;">Foto 3</span>
                                </div>
                                <div class="card-body p-2 text-center">
                                    <div class="photo-preview-box rounded border d-flex align-items-center justify-content-center bg-light" style="height: 110px; position: relative; overflow: hidden;">
                                        <img src="" class="img-fluid rounded preview-img d-none" style="max-height: 100%; object-fit: contain;">
                                        <div class="placeholder-text text-muted p-2 text-center" style="font-size: 0.75rem;">
                                            <i class="fas fa-image fa-2x mb-1 text-secondary"></i><br>Foto 3
                                        </div>
                                    </div>
                                    <div class="mt-2 d-flex gap-1">
                                        <button type="button" class="btn btn-primary btn-xs flex-fill btn-open-camera" data-slot="3">
                                            <i class="fas fa-camera mr-1"></i> Kamera
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-xs flex-fill btn-open-gallery" data-slot="3">
                                            <i class="fas fa-folder-open mr-1"></i> Galeri
                                        </button>
                                    </div>
                                    <input type="file" accept="image/*" capture="environment" class="d-none photo-file-input" id="file_camera_3" data-slot="3">
                                    <input type="file" accept="image/*" class="d-none photo-file-input" id="file_gallery_3" data-slot="3">
                                    <input type="hidden" name="photos[2]" class="compressed-photo-data" id="compressed_photo_3">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tombol Tambah Slot Foto Tambahan --}}
                    <div class="text-center my-2">
                        <button type="button" class="btn btn-outline-primary btn-xs rounded-pill" id="btnAddExtraSlot">
                            <i class="fas fa-plus-circle mr-1"></i> Tambah Foto Tambahan (Foto 4, 5, dst)
                        </button>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-success btn-sm px-3 font-weight-bold" id="btnSubmitDokumentasi">
                        <i class="fas fa-save mr-1"></i> Simpan Dokumentasi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
