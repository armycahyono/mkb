@extends('adminlte::page')

@section('title', 'Referensi ARKAS - BOSP')

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap" style="padding: 2px 0;">
    <div>
        <h1 class="page-title"><i class="fas fa-book-bookmark text-primary mr-1"></i> Referensi ARKAS</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-house"></i> Dashboard</a></li>
                <li class="breadcrumb-item"><i class="fas fa-calculator"></i> Penganggaran / BOS</li>
                <li class="breadcrumb-item active">Referensi ARKAS</li>
            </ol>
        </nav>
    </div>
    <div>
        <span class="badge badge-light border text-muted px-2 py-1" style="font-size: 0.78rem;">
            <i class="fas fa-database mr-1 text-info"></i>Kamus Standar ARKAS BOSP
        </span>
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

    .nav-tabs .nav-link {
        font-size: 0.85rem;
        font-weight: 600;
        color: #495057;
        border-radius: 6px 6px 0 0;
        padding: 8px 16px;
    }
    .nav-tabs .nav-link.active {
        color: #0d6efd !important;
        border-bottom: 2px solid #0d6efd !important;
        background-color: #fff !important;
    }

    .filter-card {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 10px 14px;
        margin-bottom: 12px;
    }

    .table thead th {
        font-size: 0.75rem !important;
        font-weight: 700 !important;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        vertical-align: middle !important;
        background-color: #f1f5f9;
        border-bottom: 2px solid #cbd5e1 !important;
    }

    .table tbody td {
        font-size: 0.82rem;
        vertical-align: middle !important;
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .badge-soft-primary {
        background-color: #e0f2fe;
        color: #0369a1;
        border: 1px solid #bae6fd;
    }
    .badge-soft-success {
        background-color: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
    }
    .badge-soft-warning {
        background-color: #fffbeb;
        color: #b45309;
        border: 1px solid #fde68a;
    }
</style>
@stop

@section('content')
<div class="card card-primary card-outline card-outline-tabs shadow-sm">
    <div class="card-header p-0 border-bottom-0">
        <ul class="nav nav-tabs" id="arkasTab" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active" id="tab-kegiatan-link" data-bs-toggle="tab" data-toggle="tab" data-bs-target="#tab-kegiatan" href="#tab-kegiatan" role="tab" aria-controls="tab-kegiatan" aria-selected="true">
                    <i class="fas fa-list-check mr-1 text-primary"></i> Kegiatan ARKAS
                    <span class="badge badge-primary ml-1 font-weight-normal">{{ number_format($totalKegiatan) }} Data</span>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="tab-barang-link" data-bs-toggle="tab" data-toggle="tab" data-bs-target="#tab-barang" href="#tab-barang" role="tab" aria-controls="tab-barang" aria-selected="false">
                    <i class="fas fa-boxes-stacked mr-1 text-success"></i> Referensi Barang
                    <span class="badge badge-success ml-1 font-weight-normal">{{ number_format($totalBarang) }} Item Unik</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="card-body p-3">
        <div class="tab-content" id="arkasTabContent">

            {{-- ══════════════════════════════════════════════════════════════════ --}}
            {{-- TAB 1: KEGIATAN ARKAS                                            --}}
            {{-- ══════════════════════════════════════════════════════════════════ --}}
            <div class="tab-pane fade show active" id="tab-kegiatan" role="tabpanel" aria-labelledby="tab-kegiatan-link">
                
                {{-- Filter Bar Kegiatan --}}
                <div class="filter-card">
                    <div class="row align-items-end g-2">
                        <div class="col-md mb-2 mb-md-0">
                            <label class="font-weight-bold text-secondary mb-1" style="font-size: 0.75rem;">Filter Standar (SNP):</label>
                            <select id="filter-kegiatan-snp" class="form-control form-control-sm" style="height: 28px; font-size: 0.78rem;">
                                <option value="">-- Semua Standar (SNP) --</option>
                                @foreach($snps as $snp)
                                    <option value="{{ $snp }}">{{ $snp }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md mb-2 mb-md-0">
                            <label class="font-weight-bold text-secondary mb-1" style="font-size: 0.75rem;">Filter Komponen BOSP:</label>
                            <select id="filter-kegiatan-komponen" class="form-control form-control-sm" style="height: 28px; font-size: 0.78rem;">
                                <option value="">-- Semua Komponen --</option>
                                @foreach($komponens as $komp)
                                    <option value="{{ $komp }}">{{ $komp }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md mb-2 mb-md-0">
                            <label class="font-weight-bold text-secondary mb-1" style="font-size: 0.75rem;">Cari Kegiatan / Kode:</label>
                            <div class="input-group input-group-sm">
                                <input type="text" id="filter-kegiatan-search" class="form-control form-control-sm" style="height: 28px; font-size: 0.78rem;" placeholder="Ketik kata kunci atau kode 02.02.01...">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" id="btn-search-kegiatan" style="height: 28px; padding: 0 8px;">
                                        <i class="fas fa-search" style="font-size: 0.75rem;"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-auto col-sm-12 text-md-right">
                            <button type="button" id="btn-reset-kegiatan" class="btn btn-sm btn-outline-secondary d-flex align-items-center justify-content-center" style="height: 28px; min-width: 70px; font-size: 0.75rem;" title="Reset Filter">
                                <i class="fas fa-undo mr-1" style="font-size: 0.7rem;"></i>Reset
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Tabel Kegiatan --}}
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-bordered table-striped w-100" id="tableKegiatan">
                        <thead>
                            <tr>
                                <th width="45" class="text-center">NO</th>
                                <th width="110">SNP</th>
                                <th width="220">KOMPONEN</th>
                                <th width="90" class="text-center">KODE</th>
                                <th>KEGIATAN</th>
                                <th width="320">DEFINISI OPERASIONAL</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            {{-- ══════════════════════════════════════════════════════════════════ --}}
            {{-- TAB 2: REFERENSI BARANG                                          --}}
            {{-- ══════════════════════════════════════════════════════════════════ --}}
            <div class="tab-pane fade" id="tab-barang" role="tabpanel" aria-labelledby="tab-barang-link">

                {{-- Notice Ringkasan Penggabungan --}}
                <div class="alert alert-light border border-info text-dark py-2 px-3 mb-3 d-flex align-items-center" style="font-size: 0.8rem; background-color: #f0fdf4; border-color: #86efac !important;">
                    <i class="fas fa-check-circle text-success mr-2" style="font-size: 1.1rem;"></i>
                    <div>
                        <strong>Pengelompokan Otomatis:</strong> Seluruh item dengan <em>Kode Rekening</em>, <em>Nama Barang</em>, dan <em>Satuan</em> yang sama telah digabungkan menjadi <strong>{{ number_format($totalBarang) }} barang unik</strong>. Kolom harga menampilkan rentang harga termurah hingga tertinggi (Min – Max).
                    </div>
                </div>

                {{-- Filter Bar Barang --}}
                <div class="filter-card">
                    <div class="row align-items-end g-2">
                        <div class="col-md mb-2 mb-md-0">
                            <label class="font-weight-bold text-secondary mb-1" style="font-size: 0.75rem;">Filter Jenis Barang:</label>
                            <select id="filter-barang-jenis" class="form-control form-control-sm" style="height: 28px; font-size: 0.78rem;">
                                <option value="">-- Semua Jenis Barang --</option>
                                @foreach($jenisBarangs as $jb)
                                    <option value="{{ $jb }}">{{ $jb }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md mb-2 mb-md-0">
                            <label class="font-weight-bold text-secondary mb-1" style="font-size: 0.75rem;">Filter Kode Rekening:</label>
                            <select id="filter-barang-rekening" class="form-control form-control-sm" style="height: 28px; font-size: 0.78rem;">
                                <option value="">-- Semua Kode Rekening --</option>
                                @foreach($kodeRekenings as $kr)
                                    <option value="{{ $kr }}">{{ $kr }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md mb-2 mb-md-0">
                            <label class="font-weight-bold text-secondary mb-1" style="font-size: 0.75rem;">Cari Nama Barang / Rekening:</label>
                            <div class="input-group input-group-sm">
                                <input type="text" id="filter-barang-search" class="form-control form-control-sm" style="height: 28px; font-size: 0.78rem;" placeholder="Ketik nama barang (misal: Affur, Kertas, Spidol)...">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" id="btn-search-barang" style="height: 28px; padding: 0 8px;">
                                        <i class="fas fa-search" style="font-size: 0.75rem;"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-auto col-sm-12 text-md-right">
                            <button type="button" id="btn-reset-barang" class="btn btn-sm btn-outline-secondary d-flex align-items-center justify-content-center" style="height: 28px; min-width: 70px; font-size: 0.75rem;" title="Reset Filter">
                                <i class="fas fa-undo mr-1" style="font-size: 0.7rem;"></i>Reset
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Tabel Barang --}}
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-bordered table-striped w-100" id="tableBarang">
                        <thead>
                            <tr>
                                <th width="45" class="text-center">NO</th>
                                <th width="90" class="text-center">JENIS</th>
                                <th width="135">KODE REKENING</th>
                                <th>NAMA BARANG</th>
                                <th width="75" class="text-center">SATUAN</th>
                                <th width="180" class="text-right">RENTANG HARGA MAX</th>
                                <th width="85" class="text-center">KODE BELANJA</th>
                                <th width="125" class="text-center">KETERANGAN</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- MODAL DETAIL DEFINISI OPERASIONAL KEGIATAN                             --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalDefinisi" tabindex="-1" role="dialog" aria-labelledby="modalDefinisiLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-primary text-white py-2 px-3">
                <h5 class="modal-title font-weight-bold" id="modalDefinisiLabel" style="font-size: 0.95rem;">
                    <i class="fas fa-circle-info mr-1"></i> Definisi Operasional Kegiatan ARKAS
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-3">
                <div class="mb-2">
                    <span id="modal-kode" class="badge badge-primary font-monospace px-2 py-1" style="font-size: 0.8rem; letter-spacing: 0.5px;"></span>
                </div>
                <h5 id="modal-kegiatan" class="font-weight-bold text-dark mb-3" style="font-size: 1.05rem; line-height: 1.4;"></h5>

                <div class="card card-outline card-secondary mb-3 shadow-none">
                    <div class="card-header py-1 px-3 bg-light">
                        <span class="font-weight-bold text-secondary" style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="fas fa-file-lines mr-1"></i> Penjelasan & Ruang Lingkup
                        </span>
                    </div>
                    <div class="card-body p-3" style="background-color: #f8fafc;">
                        <p id="modal-deskripsi" class="mb-0 text-dark" style="font-size: 0.85rem; line-height: 1.6; white-space: pre-line;"></p>
                    </div>
                </div>

                <div id="modal-contoh-wrapper" class="card card-outline card-success shadow-none mb-0 d-none">
                    <div class="card-header py-1 px-3 bg-light">
                        <span class="font-weight-bold text-success" style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="fas fa-lightbulb mr-1"></i> Contoh Penerapan / Kegiatan
                        </span>
                    </div>
                    <div class="card-body p-3" style="background-color: #f0fdf4;">
                        <p id="modal-contoh" class="mb-0 text-dark font-weight-500" style="font-size: 0.85rem; line-height: 1.5;"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2 px-3 bg-light">
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
$(document).ready(function() {

    // ── Inisialisasi DataTable: Tab Kegiatan ──────────────────────────
    let tableKegiatan = $('#tableKegiatan').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
        ajax: {
            url: "{{ route('bos.referensi-arkas.data-kegiatan', [], false) }}",
            data: function(d) {
                d.snp = $('#filter-kegiatan-snp').val();
                d.komponen = $('#filter-kegiatan-komponen').val();
                d.search_custom = $('#filter-kegiatan-search').val();
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center font-tabular text-muted'},
            {data: 'snp_badge', name: 'snp'},
            {data: 'komponen', name: 'komponen'},
            {data: 'kode_kegiatan_display', name: 'kode_kegiatan', className: 'text-center'},
            {data: 'kegiatan', name: 'kegiatan', className: 'font-weight-500 text-dark'},
            {data: 'definisi_display', name: 'definisi_operasional'}
        ],
        order: [[3, 'asc']],
        language: {
            search: "Filter cepat:",
            lengthMenu: "Tampilkan _MENU_ data",
            zeroRecords: "Tidak ada kegiatan yang sesuai",
            info: "Menampilkan _START_ s/d _END_ dari _TOTAL_ kegiatan",
            infoEmpty: "Menampilkan 0 kegiatan",
            infoFiltered: "(disaring dari _MAX_ total kegiatan)",
            paginate: {
                first: "Awal",
                last: "Akhir",
                next: "&gt;",
                previous: "&lt;"
            }
        }
    });

    // ── Inisialisasi DataTable: Tab Referensi Barang ───────────────────
    let tableBarangLoaded = false;
    let tableBarang = null;

    function initTableBarang() {
        if (tableBarangLoaded) return;
        
        tableBarang = $('#tableBarang').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
            ajax: {
                url: "{{ route('bos.referensi-arkas.data-barang', [], false) }}",
                data: function(d) {
                    d.jenis_barang = $('#filter-barang-jenis').val();
                    d.kode_rekening = $('#filter-barang-rekening').val();
                    d.search_custom = $('#filter-barang-search').val();
                }
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center font-tabular text-muted'},
                {data: 'jenis_badge', name: 'jenis_barang', className: 'text-center'},
                {data: 'kode_rekening_display', name: 'kode_rekening'},
                {data: 'nama_barang_display', name: 'nama_barang'},
                {data: 'satuan', name: 'satuan', className: 'text-center'},
                {data: 'harga_range_display', name: 'harga_max', className: 'text-right text-end'},
                {data: 'kode_belanja_display', name: 'kode_belanja', className: 'text-center'},
                {data: 'keterangan_varian', name: 'varian_count', className: 'text-center'}
            ],
            order: [[3, 'asc']],
            language: {
                search: "Filter cepat:",
                lengthMenu: "Tampilkan _MENU_ item",
                zeroRecords: "Tidak ada barang yang sesuai",
                info: "Menampilkan _START_ s/d _END_ dari _TOTAL_ barang unik",
                infoEmpty: "Menampilkan 0 barang",
                infoFiltered: "(disaring dari _MAX_ total barang)",
                paginate: {
                    first: "Awal",
                    last: "Akhir",
                    next: "&gt;",
                    previous: "&lt;"
                }
            }
        });

        tableBarangLoaded = true;
    }

    // ── Fungsi Pengaktifan Tab Mandiri (Mendukung Hash URL, Klik, & Redraw) ──
    function activateTab(tabTarget) {
        if (!tabTarget) return;

        let link = $(`#arkasTab a[href="${tabTarget}"], #arkasTab a[data-bs-target="${tabTarget}"]`);
        if (!link.length) return;

        // 1. Coba Bootstrap 5 Tab
        if (window.bootstrap && window.bootstrap.Tab) {
            try {
                bootstrap.Tab.getOrCreateInstance(link[0]).show();
            } catch (err) {}
        }
        // 2. Coba Bootstrap 4 Tab (jQuery)
        if (typeof link.tab === 'function') {
            try {
                link.tab('show');
            } catch (err) {}
        }

        // 3. Fallback CSS langsung (menjamin tab pasti berganti tampil)
        $('#arkasTab .nav-link').removeClass('active').attr('aria-selected', 'false');
        $('.tab-content > .tab-pane').removeClass('show active');

        link.addClass('active').attr('aria-selected', 'true');
        $(tabTarget).addClass('show active');

        // 4. Inisialisasi atau redraw DataTables
        if (tabTarget === '#tab-barang') {
            if (!tableBarangLoaded) {
                initTableBarang();
            } else if (tableBarang) {
                setTimeout(function() {
                    tableBarang.columns.adjust().draw(false);
                }, 100);
            }
        } else if (tabTarget === '#tab-kegiatan') {
            if (tableKegiatan) {
                setTimeout(function() {
                    tableKegiatan.columns.adjust().draw(false);
                }, 100);
            }
        }
    }

    // Tangani event Bootstrap saat tab berganti
    $('#arkasTab a[data-toggle="tab"], #arkasTab a[data-bs-toggle="tab"]').on('shown.bs.tab shown', function (e) {
        let target = $(e.target).attr("href") || $(e.target).data("bs-target");
        if (target) {
            if (history.replaceState) {
                history.replaceState(null, null, target);
            } else {
                window.location.hash = target;
            }
            if (target === '#tab-barang' && !tableBarangLoaded) {
                initTableBarang();
            } else if (target === '#tab-barang' && tableBarang) {
                tableBarang.columns.adjust().draw(false);
            } else if (target === '#tab-kegiatan' && tableKegiatan) {
                tableKegiatan.columns.adjust().draw(false);
            }
        }
    });

    // Tangani klik langsung pada tab link
    $('#arkasTab a.nav-link').on('click', function(e) {
        let target = $(this).attr('href') || $(this).data('bs-target');
        if (target && target.startsWith('#')) {
            e.preventDefault();
            if (history.replaceState) {
                history.replaceState(null, null, target);
            } else {
                window.location.hash = target;
            }
            activateTab(target);
        }
    });

    // Deteksi Hash URL saat halaman pertama kali dimuat (misal: #tab-barang)
    let initialHash = window.location.hash;
    if (initialHash === '#tab-barang' || initialHash === '#tab-kegiatan') {
        activateTab(initialHash);
    }

    // Deteksi perubahan hash pada URL browser (tombol back/forward)
    $(window).on('hashchange', function() {
        let hash = window.location.hash;
        if (hash === '#tab-barang' || hash === '#tab-kegiatan') {
            activateTab(hash);
        }
    });

    // ── Event Filters Kegiatan ────────────────────────────────────────
    $('#filter-kegiatan-snp, #filter-kegiatan-komponen').on('change', function() {
        tableKegiatan.ajax.reload();
    });

    let typingTimerKegiatan;
    $('#filter-kegiatan-search').on('keyup', function() {
        clearTimeout(typingTimerKegiatan);
        typingTimerKegiatan = setTimeout(function() {
            tableKegiatan.ajax.reload();
        }, 400);
    });

    $('#btn-search-kegiatan').on('click', function() {
        tableKegiatan.ajax.reload();
    });

    $('#btn-reset-kegiatan').on('click', function() {
        $('#filter-kegiatan-snp').val('');
        $('#filter-kegiatan-komponen').val('');
        $('#filter-kegiatan-search').val('');
        tableKegiatan.ajax.reload();
    });

    // ── Event Filters Barang ──────────────────────────────────────────
    $('#filter-barang-jenis, #filter-barang-rekening').on('change', function() {
        if (tableBarang) tableBarang.ajax.reload();
    });

    let typingTimerBarang;
    $('#filter-barang-search').on('keyup', function() {
        clearTimeout(typingTimerBarang);
        typingTimerBarang = setTimeout(function() {
            if (tableBarang) tableBarang.ajax.reload();
        }, 400);
    });

    $('#btn-search-barang').on('click', function() {
        if (tableBarang) tableBarang.ajax.reload();
    });

    $('#btn-reset-barang').on('click', function() {
        $('#filter-barang-jenis').val('');
        $('#filter-barang-rekening').val('');
        $('#filter-barang-search').val('');
        if (tableBarang) tableBarang.ajax.reload();
    });

    // ── Modal Detail Definisi Kegiatan ────────────────────────────────
    $(document).on('click', '.btn-lihat-definisi', function() {
        let kode = $(this).data('kode');
        let kegiatan = $(this).data('kegiatan');
        let definisi = $(this).data('definisi') || '';

        $('#modal-kode').text('Kode: ' + kode);
        $('#modal-kegiatan').text(kegiatan);

        // Pisahkan contoh jika ada kata "Contoh:"
        let splitContoh = definisi.split(/Contoh\s*:/i);
        if (splitContoh.length > 1) {
            $('#modal-deskripsi').text(splitContoh[0].trim());
            $('#modal-contoh').text(splitContoh.slice(1).join('Contoh:').trim());
            $('#modal-contoh-wrapper').removeClass('d-none');
        } else {
            $('#modal-deskripsi').text(definisi.trim());
            $('#modal-contoh').text('');
            $('#modal-contoh-wrapper').addClass('d-none');
        }

        $('#modalDefinisi').modal('show');
    });

});
</script>
@stop
