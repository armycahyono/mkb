@extends('adminlte::page')

@section('title', 'Foto BOSP')

@section('plugins.Select2', true)

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
        margin-bottom: 2px !important;
    }
    .modal {
        overflow-x: hidden !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch !important;
    }
    .modal-dialog {
        margin: 1.25rem auto !important;
        max-width: 800px;
    }
    @media (max-width: 767.98px) {
        .modal-dialog {
            margin: 0.5rem !important;
        }
    }
    .photo-preview-box {
        background-color: #f8fafc;
        border: 2px dashed #cbd5e1 !important;
    }
    /* Select2 Compact Style inside Modal */
    .select2-container .select2-selection--single {
        height: 31px !important;
        font-size: 0.82rem !important;
        border-color: #ced4da !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 29px !important;
        padding-left: 8px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 29px !important;
    }
    .select2-dropdown {
        font-size: 0.82rem !important;
    }
    /* Header Action Button */
    .header-actions #btnOpenCreateModal {
        height: 32px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 0 12px !important;
        font-size: 0.82rem !important;
        line-height: 1 !important;
        border-radius: 4px !important;
    }
    /* Card focused on photos (Compact 4-column layout) */
    .photo-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border-radius: 6px !important;
    }
    .photo-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.08) !important;
    }
    .photo-main-wrap {
        position: relative;
        height: 135px;
        overflow: hidden;
        background: #0f172a;
        cursor: pointer;
    }
    .photo-zoom {
        transition: transform 0.35s ease;
    }
    .photo-main-wrap:hover .photo-zoom,
    .sub-photo-box:hover .photo-zoom {
        transform: scale(1.04);
    }
    .sub-photo-box {
        position: relative;
        height: 65px;
        overflow: hidden;
        border-radius: 4px;
        background: #f8fafc;
        cursor: pointer;
    }
    .text-truncate-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>
@stop

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap" style="padding: 2px 0;">
    <div>
        <h1 class="page-title"><i class="fas fa-camera text-info mr-1"></i> Foto BOSP</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 py-0" style="background: transparent; font-size: 0.78rem;">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-house"></i> Dashboard</a></li>
                <li class="breadcrumb-item">BOSP</li>
                <li class="breadcrumb-item active">Foto BOSP</li>
            </ol>
        </nav>
    </div>
    <div class="header-actions mt-2 mt-sm-0">
        <button type="button" class="btn btn-sm btn-primary shadow-sm font-weight-bold" id="btnOpenCreateModal">
            <i class="fas fa-plus mr-1"></i> Tambah Foto
        </button>
    </div>
</div>
@stop

@section('content')
    {{-- Notifikasi --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2 px-3 small" role="alert">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show py-2 px-3 small" role="alert">
            <i class="fas fa-exclamation-triangle mr-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Bar Filter & Pencarian (Statistik Dihapus) --}}
    <div class="card card-outline card-secondary mb-3 shadow-sm">
        <div class="card-body p-2">
            <form method="GET" action="{{ route('bos.dokumentasi-barang.index') }}" class="row g-2 align-items-center">
                <input type="hidden" name="year" value="{{ $selectedYear }}">
                
                <div class="col-12 col-md-6 mb-1 mb-md-0">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search text-muted"></i></span>
                        </div>
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nomor nota, toko, atau uraian belanja..." value="{{ $search }}">
                    </div>
                </div>

                <div class="col-8 col-md-4 mb-1 mb-md-0">
                    <select name="month" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="">Semua Bulan</option>
                        @foreach($months as $mCode => $mName)
                            <option value="{{ $mCode }}" {{ $selectedMonth == $mCode ? 'selected' : '' }}>{{ $mName }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-4 col-md-2 text-right">
                    <button type="submit" class="btn btn-sm btn-info w-100 font-weight-bold" style="font-size: 0.8rem;">
                        <i class="fas fa-filter mr-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Daftar Kartu Dokumentasi Barang (3 Kartu per Baris pada Desktop) --}}
    @if($dokumentasis->isEmpty())
        <div class="card shadow-sm py-4 text-center text-muted">
            <div class="card-body">
                <i class="fas fa-camera-retro fa-3x mb-2 text-secondary opacity-50"></i>
                <h6 class="font-weight-bold">Belum ada data dokumentasi foto barang untuk periode ini.</h6>
                <p class="small text-muted mb-2">Klik tombol <strong>"Tambah Foto"</strong> untuk mendokumentasikan barang yang datang.</p>
                <button type="button" class="btn btn-primary btn-sm" onclick="$('#btnOpenCreateModal').click()">
                    <i class="fas fa-plus mr-1"></i> Tambah Foto Sekarang
                </button>
            </div>
        </div>
    @else
        <div class="row">
            @foreach($dokumentasis as $doc)
                @php
                    $fotos = $doc->fotos;
                    $fotoCount = $fotos->count();
                    $foto1 = $fotos->firstWhere('urutan', 1);
                    $foto2 = $fotos->firstWhere('urutan', 2);
                    $foto3 = $fotos->firstWhere('urutan', 3);
                @endphp
                {{-- 4 Kartu per Baris pada Desktop (col-lg-3) --}}
                <div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-3">
                    <div class="card h-100 shadow-sm border photo-card overflow-hidden position-relative mb-0">
                        {{-- 1. FOTO UTAMA (HERO / FOTO 1) - Tampil Kompak & Dominan --}}
                        <div class="photo-main-wrap" onclick="openDetailModal({{ $doc->id }})" title="Klik untuk memperbesar Foto 1">
                            @if($foto1 && Storage::disk('public')->exists($foto1->file_path))
                                <img src="{{ Storage::disk('public')->url($foto1->file_path) }}" 
                                     alt="{{ $doc->uraian ?: 'Foto 1' }}" 
                                     class="w-100 h-100 photo-zoom" 
                                     style="object-fit: cover; display: block;">
                                <div class="position-absolute" style="top: 6px; left: 6px;">
                                    <span class="badge shadow-sm text-white" style="font-size: 0.65rem; background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(4px); padding: 2px 6px;">
                                        <i class="fas fa-camera text-info mr-1"></i> Foto 1
                                    </span>
                                </div>
                                <div class="position-absolute" style="top: 6px; right: 6px;">
                                    <span class="badge badge-primary shadow-sm font-weight-bold" style="font-size: 0.65rem; padding: 2px 6px;">
                                        <i class="fas fa-images mr-1"></i> {{ $fotoCount }} Foto
                                    </span>
                                </div>
                            @else
                                <div class="w-100 h-100 d-flex flex-column align-items-center justify-content-center text-muted" onclick="event.stopPropagation(); openEditModal({{ $doc->id }});" style="background: #f1f5f9;">
                                    <i class="fas fa-camera fa-lg text-secondary opacity-50 mb-1"></i>
                                    <span style="font-size: 0.72rem; font-weight: 600;">Belum Ada Foto 1</span>
                                    <span class="text-muted" style="font-size: 0.65rem;">Klik untuk upload</span>
                                </div>
                            @endif
                        </div>

                        {{-- 2. FOTO PENDUKUNG (FOTO 2 & FOTO 3) - Kompak & Proporsional --}}
                        <div class="row g-1 px-1 pt-1 pb-1 bg-light border-bottom m-0">
                            {{-- Slot Foto 2 --}}
                            <div class="col-6 p-1">
                                <div class="sub-photo-box border" onclick="openDetailModal({{ $doc->id }})" title="Foto 2">
                                    @if($foto2 && Storage::disk('public')->exists($foto2->file_path))
                                        <img src="{{ Storage::disk('public')->url($foto2->file_path) }}" 
                                             alt="Foto 2" 
                                             class="w-100 h-100 photo-zoom" 
                                             style="object-fit: cover; display: block;">
                                        <span class="badge position-absolute text-white" style="bottom: 3px; left: 3px; font-size: 0.6rem; padding: 1px 4px; background: rgba(15, 23, 42, 0.75);">
                                            Foto 2
                                        </span>
                                    @else
                                        <div class="w-100 h-100 d-flex flex-column align-items-center justify-content-center text-muted" 
                                             onclick="event.stopPropagation(); openEditModal({{ $doc->id }});" 
                                             style="background: #fafafa; border: 1px dashed #cbd5e1;">
                                            <i class="fas fa-plus text-secondary mb-1" style="font-size: 0.65rem;"></i>
                                            <span style="font-size: 0.62rem; font-weight: 500;">Foto 2 (Kosong)</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Slot Foto 3 --}}
                            <div class="col-6 p-1">
                                <div class="sub-photo-box border" onclick="openDetailModal({{ $doc->id }})" title="Foto 3">
                                    @if($foto3 && Storage::disk('public')->exists($foto3->file_path))
                                        <img src="{{ Storage::disk('public')->url($foto3->file_path) }}" 
                                             alt="Foto 3" 
                                             class="w-100 h-100 photo-zoom" 
                                             style="object-fit: cover; display: block;">
                                        <span class="badge position-absolute text-white" style="bottom: 3px; left: 3px; font-size: 0.6rem; padding: 1px 4px; background: rgba(15, 23, 42, 0.75);">
                                            Foto 3
                                        </span>
                                    @else
                                        <div class="w-100 h-100 d-flex flex-column align-items-center justify-content-center text-muted" 
                                             onclick="event.stopPropagation(); openEditModal({{ $doc->id }});" 
                                             style="background: #fafafa; border: 1px dashed #cbd5e1;">
                                            <i class="fas fa-plus text-secondary mb-1" style="font-size: 0.65rem;"></i>
                                            <span style="font-size: 0.62rem; font-weight: 500;">Foto 3 (Kosong)</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- 3. INFORMASI BELANJA (Padat & Hemat Tempat) --}}
                        <div class="card-body p-2 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-1 text-muted" style="font-size: 0.7rem;">
                                    <span class="text-truncate mr-1" style="max-width: 55%;" title="No. Nota: {{ $doc->no_nota ?: '-' }}">
                                        <i class="fas fa-receipt text-primary mr-1"></i>
                                        <strong class="text-dark">{{ $doc->no_nota ?: '-' }}</strong>
                                    </span>
                                    <span title="Tanggal Pengambilan Foto">
                                        <i class="fas fa-calendar-alt text-muted mr-1"></i> {{ $doc->tanggal_terima ? $doc->tanggal_terima->format('d/m/Y') : '-' }}
                                    </span>
                                </div>

                                <div class="font-weight-bold text-dark text-truncate-2" style="font-size: 0.78rem; line-height: 1.25; margin-bottom: 2px;" title="{{ $doc->uraian }}">
                                    {{ $doc->uraian ?: '(Tanpa uraian belanja)' }}
                                </div>

                                <div class="d-flex justify-content-between align-items-center text-muted" style="font-size: 0.7rem;">
                                    @if($doc->nama_toko)
                                        <span class="text-truncate mr-1" style="max-width: 52%;" title="Toko: {{ $doc->nama_toko }}">
                                            <i class="fas fa-store text-muted mr-1"></i> {{ $doc->nama_toko }}
                                        </span>
                                    @else
                                        <span></span>
                                    @endif
                                    @if($doc->nominal)
                                        <span class="font-weight-bold text-success" style="font-size: 0.75rem;">
                                            Rp {{ number_format($doc->nominal, 0, ',', '.') }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Informasi Akun Pengirim Foto --}}
                                <div class="d-flex align-items-center justify-content-between mt-2 pt-1 border-top text-muted" style="font-size: 0.68rem;">
                                    <div class="text-truncate mr-1" title="Pengirim: {{ $doc->sender_name }} ({{ $doc->sender_detail }})">
                                        <i class="fas fa-user-circle text-info mr-1"></i>
                                        <span class="text-dark font-weight-bold">{{ $doc->sender_name }}</span>
                                        @if($doc->creator?->telegram_username)
                                            <span class="text-primary font-weight-normal ml-1" title="Akun Telegram: @{{ $doc->creator->telegram_username }}">
                                                <i class="fab fa-telegram-plane"></i> {{ '@'.$doc->creator->telegram_username }}
                                            </span>
                                        @endif
                                    </div>
                                    @if($doc->creator_role_label)
                                        <span class="badge badge-light border text-secondary px-1" style="font-size: 0.62rem;">
                                            {{ $doc->creator_role_label }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- 4. FOOTER AKSI --}}
                        <div class="card-footer bg-white py-1 px-2 border-top d-flex justify-content-between align-items-center">
                            <div>
                                <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2" onclick="openDetailModal({{ $doc->id }})" title="Lihat semua foto" style="font-size: 0.7rem;">
                                    <i class="fas fa-eye mr-1"></i> Lihat Foto
                                </button>
                            </div>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-xs btn-outline-info py-0 px-1" onclick="openEditModal({{ $doc->id }})" title="Edit" style="font-size: 0.7rem;">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                <form action="{{ route('bos.dokumentasi-barang.destroy', $doc->id) }}" method="POST" class="d-inline ml-1" onsubmit="return confirm('Apakah Anda yakin ingin menghapus dokumentasi ini beserta fotonya?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-outline-danger py-0 px-1" title="Hapus" style="font-size: 0.7rem;">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="d-flex justify-content-center mt-3">
            {{ $dokumentasis->links() }}
        </div>
    @endif

    {{-- Include Modals --}}
    @include('bos.dokumentasi_barang.partials.form_modal')
    @include('bos.dokumentasi_barang.partials.detail_modal')
@stop

@section('js')
<script>
function openBsModal(modalSelector) {
    let el = document.querySelector(modalSelector);
    if (!el) return;
    $('.modal-backdrop').remove();
    if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
        let inst = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
        inst.show();
    } else {
        $(el).modal('show');
    }
}

function closeBsModal(modalSelector) {
    let el = document.querySelector(modalSelector);
    if (!el) return;
    if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
        let inst = bootstrap.Modal.getInstance(el);
        if (inst) inst.hide();
    }
    $(el).modal('hide');
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open').css('overflow', '').css('padding-right', '');
}

$(document).ready(function() {
    let extraSlotCounter = 3;

    // Inisialisasi Select2 pencarian transaksi di dalam modal
    if ($.fn.select2) {
        $('#selectAvailableNota').select2({
            dropdownParent: $('#modalDokumentasiForm'),
            width: '100%',
            placeholder: '-- Ketik untuk mencari nota / uraian belanja --',
            allowClear: true
        });

        $(document).on('select2:open', function() {
            let searchField = document.querySelector('.select2-container--open .select2-search__field');
            if (searchField) {
                searchField.focus();
            }
        });
    }

    // Universal Close / Dismiss handler
    $(document).on('click', '[data-bs-dismiss="modal"], [data-dismiss="modal"]', function(e) {
        e.preventDefault();
        let modal = $(this).closest('.modal');
        if (modal.length) {
            closeBsModal('#' + modal.attr('id'));
        }
    });

    // Reset & Buka Modal Create
    $('#btnOpenCreateModal').on('click', function() {
        $('#formDokumentasi')[0].reset();
        $('#modalDokumentasiFormLabel').html('<i class="fas fa-camera mr-1"></i> Tambah Dokumentasi Barang');
        $('#formDokumentasi').attr('action', '{{ route("bos.dokumentasi-barang.store") }}');
        $('#formMethodContainer').empty();
        $('#doc_tanggal_terima').val('{{ date("Y-m-d") }}');
        
        // Bersihkan preview & data
        $('.preview-img').addClass('d-none').attr('src', '');
        $('.placeholder-text').removeClass('d-none');
        $('.compressed-photo-data').val('');
        $('.photo-file-input').val('');
        
        if ($.fn.select2) {
            $('#selectAvailableNota').val('').trigger('change');
        } else {
            $('#selectAvailableNota').val('');
        }
        
        // Hapus slot dinamis jika ada
        $('.photo-slot-card[data-slot]').each(function() {
            if (parseInt($(this).data('slot')) > 3) {
                $(this).remove();
            }
        });
        extraSlotCounter = 3;

        openBsModal('#modalDokumentasiForm');
    });

    // Event Klik Tombol Kamera & Galeri
    $(document).on('click', '.btn-open-camera', function(e) {
        e.preventDefault();
        e.stopPropagation();
        let slot = $(this).data('slot');
        let input = document.getElementById('file_camera_' + slot);
        if (input) input.click();
    });

    $(document).on('click', '.btn-open-gallery', function(e) {
        e.preventDefault();
        e.stopPropagation();
        let slot = $(this).data('slot');
        let input = document.getElementById('file_gallery_' + slot);
        if (input) input.click();
    });

    // Tambah slot foto tambahan (Foto 4, Foto 5...)
    $('#btnAddExtraSlot').on('click', function() {
        extraSlotCounter++;
        let slot = extraSlotCounter;
        let slotHtml = `
            <div class="col-12 col-md-4 mb-2 photo-slot-card" data-slot="${slot}">
                <div class="card border h-100 shadow-none mb-0">
                    <div class="card-header bg-light py-1 px-2 d-flex justify-content-between align-items-center">
                        <span class="font-weight-bold text-primary" style="font-size: 0.78rem;">Foto ${slot}</span>
                        <button type="button" class="btn btn-xs btn-outline-danger btn-remove-slot" title="Hapus"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="card-body p-2 text-center">
                        <div class="photo-preview-box rounded border d-flex align-items-center justify-content-center bg-light" style="height: 110px; position: relative; overflow: hidden;">
                            <img src="" class="img-fluid rounded preview-img d-none" style="max-height: 100%; object-fit: contain;">
                            <div class="placeholder-text text-muted p-2 text-center" style="font-size: 0.75rem;">
                                <i class="fas fa-image fa-2x mb-1 text-secondary"></i><br>Foto ${slot}
                            </div>
                        </div>
                        <div class="mt-2 d-flex gap-1">
                            <button type="button" class="btn btn-primary btn-xs flex-fill btn-open-camera" data-slot="${slot}">
                                <i class="fas fa-camera mr-1"></i> Kamera
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-xs flex-fill btn-open-gallery" data-slot="${slot}">
                                <i class="fas fa-folder-open mr-1"></i> Galeri
                            </button>
                        </div>
                        <input type="file" accept="image/*" capture="environment" class="d-none photo-file-input" id="file_camera_${slot}" data-slot="${slot}">
                        <input type="file" accept="image/*" class="d-none photo-file-input" id="file_gallery_${slot}" data-slot="${slot}">
                        <input type="hidden" name="photos[${slot - 1}]" class="compressed-photo-data" id="compressed_photo_${slot}">
                    </div>
                </div>
            </div>
        `;
        $('#photoSlotsContainer').append(slotHtml);
    });

    // Hapus slot dinamis
    $(document).on('click', '.btn-remove-slot', function() {
        $(this).closest('.photo-slot-card').remove();
    });

    // Kompresi Gambar Otomatis (HTML5 Canvas) saat foto dipilih / dijepret
    $(document).on('change', '.photo-file-input', function(e) {
        let file = e.target.files[0];
        if (!file) return;

        let card = $(this).closest('.photo-slot-card');
        let previewImg = card.find('.preview-img');
        let placeholder = card.find('.placeholder-text');
        let hiddenCompressed = card.find('.compressed-photo-data');

        placeholder.html('<i class="fas fa-spinner fa-spin fa-2x text-primary"></i><br><small style="font-size:10px;">Mengompres...</small>');

        let reader = new FileReader();
        reader.onload = function(event) {
            let img = new Image();
            img.onload = function() {
                let maxDimension = 1600;
                let width = img.width;
                let height = img.height;

                if (width > height && width > maxDimension) {
                    height = Math.round((height * maxDimension) / width);
                    width = maxDimension;
                } else if (height > maxDimension) {
                    width = Math.round((width * maxDimension) / height);
                    height = maxDimension;
                }

                let canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;

                let ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                let compressedDataUrl = canvas.toDataURL('image/jpeg', 0.82);

                previewImg.attr('src', compressedDataUrl).removeClass('d-none');
                placeholder.addClass('d-none');
                hiddenCompressed.val(compressedDataUrl);
            };
            img.src = event.target.result;
        };
        reader.readAsDataURL(file);
    });

    // Auto-fill form saat nota kas dipilih dari select2
    $('#selectAvailableNota').on('change', function() {
        let selectedOption = $(this).find('option:selected');
        let noNota = selectedOption.data('no_nota');
        let tanggal = selectedOption.data('tanggal');
        let toko = selectedOption.data('toko');
        let uraian = selectedOption.data('uraian');
        let nominal = selectedOption.data('nominal');
        let kasId = selectedOption.data('kas_id');

        if (noNota) {
            $('#doc_no_nota').val(noNota);
            if (tanggal) $('#doc_tanggal_terima').val(tanggal);
            if (toko) $('#doc_nama_toko').val(toko);
            if (uraian) $('#doc_uraian').val(uraian);
            if (nominal) $('#doc_nominal').val(nominal);
            $('#doc_kas_tunai_id').val(kasId || '');
        }
    });

    // Submit validasi
    $('#formDokumentasi').on('submit', function() {
        let btn = $('#btnSubmitDokumentasi');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');
    });
});

// Buka Modal Edit
function openEditModal(id) {
    $.ajax({
        url: '{{ url("bos/dokumentasi-barang") }}/' + id,
        type: 'GET',
        success: function(res) {
            let doc = res.dokumentasi;
            $('#modalDokumentasiFormLabel').html('<i class="fas fa-pencil-alt mr-1"></i> Edit Dokumentasi: ' + (doc.no_nota || doc.uraian || 'Barang'));
            $('#formDokumentasi').attr('action', '{{ url("bos/dokumentasi-barang") }}/' + id);
            $('#formMethodContainer').html('@method("PUT")');

            $('#doc_no_nota').val(doc.no_nota || '');
            $('#doc_tanggal_terima').val(doc.tanggal_terima ? doc.tanggal_terima.substring(0, 10) : '');
            $('#doc_nama_toko').val(doc.nama_toko || '');
            $('#doc_uraian').val(doc.uraian || '');
            $('#doc_nominal').val(doc.nominal || '');
            $('#doc_kas_tunai_id').val(doc.kas_tunai_id || '');

            // Reset slot foto
            $('.preview-img').addClass('d-none').attr('src', '');
            $('.placeholder-text').removeClass('d-none');
            $('.compressed-photo-data').val('');
            $('.photo-file-input').val('');

            if ($.fn.select2) {
                $('#selectAvailableNota').val(doc.no_nota || '').trigger('change');
            }

            // Tampilkan foto yang sudah ada
            res.fotos.forEach(function(f) {
                let slotCard = $('.photo-slot-card[data-slot="' + f.urutan + '"]');
                if (slotCard.length > 0) {
                    slotCard.find('.preview-img').attr('src', f.file_url).removeClass('d-none');
                    slotCard.find('.placeholder-text').addClass('d-none');
                }
            });

            openBsModal('#modalDokumentasiForm');
        },
        error: function() {
            alert('Gagal mengambil data dokumentasi.');
        }
    });
}

// Buka Modal Detail & Galeri
function openDetailModal(id) {
    $('#detail_photos_container').html(`
        <div class="col-12 text-center py-4 text-muted">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="mt-2 mb-0" style="font-size: 0.8rem;">Memuat foto...</p>
        </div>
    `);
    openBsModal('#modalDokumentasiDetail');

    $.ajax({
        url: '{{ url("bos/dokumentasi-barang") }}/' + id,
        type: 'GET',
        success: function(res) {
            let doc = res.dokumentasi;
            $('#detail_no_nota').text(doc.no_nota || 'Tanpa Nomor Nota');
            $('#detail_toko').text(doc.nama_toko || '-');
            $('#detail_tanggal').text(doc.tanggal_terima ? doc.tanggal_terima.substring(0, 10) : '-');
            $('#detail_uraian').text(doc.uraian || '(Tidak ada uraian)');
            $('#detail_nominal_badge').text(doc.nominal ? 'Rp ' + Number(doc.nominal).toLocaleString('id-ID') : 'Rp 0');
            $('#detail_sender_name').text(res.sender_name || (doc.creator ? doc.creator.name : 'Tidak diketahui'));
            $('#detail_sender_detail').text(res.sender_detail || '-');

            let container = $('#detail_photos_container');
            container.empty();

            if (res.fotos.length === 0) {
                container.html(`
                    <div class="col-12 text-center py-4 text-muted">
                        <i class="fas fa-images fa-3x mb-2 text-secondary opacity-50"></i>
                        <p class="mb-0" style="font-size: 0.8rem;">Belum ada foto yang tersimpan.</p>
                    </div>
                `);
                return;
            }

            res.fotos.forEach(function(foto) {
                let downloadUrl = '{{ url("bos/dokumentasi-barang/foto") }}/' + foto.id + '/download';
                let col = `
                    <div class="col-12 col-md-4 mb-2">
                        <div class="card h-100 border shadow-none mb-0">
                            <div class="card-header bg-light py-1 px-2 d-flex justify-content-between align-items-center">
                                <strong class="text-primary" style="font-size: 0.78rem;"><i class="fas fa-camera mr-1"></i> Foto ${foto.urutan}</strong>
                                <a href="${downloadUrl}" class="btn btn-xs btn-outline-success py-0 px-2" title="Unduh Foto">
                                    <i class="fas fa-download"></i> Unduh
                                </a>
                            </div>
                            <div class="card-body p-1 text-center bg-dark" style="height: 160px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                <a href="${foto.file_url}" target="_blank">
                                    <img src="${foto.file_url}" class="img-fluid rounded" style="max-height: 150px; object-fit: contain;">
                                </a>
                            </div>
                            <div class="card-footer bg-white p-1 text-center" style="font-size: 0.72rem;">
                                <span class="text-muted text-truncate d-block" title="${foto.keterangan || ''}">
                                    ${foto.keterangan || 'Dokumentasi'}
                                </span>
                            </div>
                        </div>
                    </div>
                `;
                container.append(col);
            });
        },
        error: function() {
            $('#detail_photos_container').html(`
                <div class="col-12 text-center py-4 text-danger">
                    <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                    <p class="mb-0" style="font-size: 0.8rem;">Gagal memuat galeri foto.</p>
                </div>
            `);
        }
    });
}
</script>
@stop
