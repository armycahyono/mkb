@extends('adminlte::page')

@section('title', 'Master Kategori Transaksi Kas')

@section('plugins.Datatables', true)
@section('plugins.Sweetalert2', true)

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap" style="padding: 2px 0;">
    <div>
        <h1 class="page-title"><i class="fas fa-tags text-success mr-1"></i> Master Kategori Transaksi Kas</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-house"></i> Dashboard</a></li>
                <li class="breadcrumb-item">Data Pokok</li>
                <li class="breadcrumb-item active">Kategori Kas</li>
            </ol>
        </nav>
    </div>
    <div class="header-actions">
        <a href="{{ route('bos.kas-tunai.index') }}" class="btn btn-sm btn-outline-secondary mr-1">
            <i class="fas fa-book mr-1"></i> Buka Buku Kas Tunai
        </a>
        <button type="button" class="btn btn-sm btn-success" id="btnCreateCategory" data-toggle="modal" data-target="#modalCategory" data-bs-toggle="modal" data-bs-target="#modalCategory">
            <i class="fas fa-plus mr-1"></i> Tambah Kategori Baru
        </button>
    </div>
</div>
@stop

@section('css')
<style>
    body, .content-wrapper, .card, table, .btn, .form-control {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
    }
    h1.page-title {
        font-size: 1.15rem !important;
        font-weight: 700 !important;
        letter-spacing: -0.015em;
        margin-bottom: 0;
    }
    .content-header .breadcrumb {
        font-size: 0.76rem !important;
        margin-top: 2px;
    }
    .category-table {
        font-size: 0.8125rem !important;
    }
    .category-table th {
        font-size: 0.74rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 0.45rem 0.5rem !important;
        background-color: #f8fafc;
        color: #475569;
        vertical-align: middle;
    }
    .category-table td {
        padding: 0.45rem 0.5rem !important;
        vertical-align: middle;
        line-height: 1.35;
    }
    .emoji-picker-btn {
        font-size: 1.15rem;
        cursor: pointer;
        padding: 4px 8px;
        border-radius: 4px;
        display: inline-block;
        border: 1px solid #e2e8f0;
        margin-right: 4px;
        margin-bottom: 4px;
        background: #fff;
        user-select: none;
    }
    .emoji-picker-btn:hover {
        background-color: #edf2f7;
    }
    .badge-system {
        background-color: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
    }
    .badge-custom {
        background-color: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
    }
    .btn-toggle-status {
        cursor: pointer !important;
    }
</style>
@stop

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card card-primary card-outline card-outline-tabs shadow-sm border-0">
            <div class="card-header p-0 border-bottom-0">
                <ul class="nav nav-tabs" id="categoryTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active font-weight-bold" id="tab-keluar-tab" data-toggle="pill" data-bs-toggle="pill" href="#tab-keluar" data-target="#tab-keluar" data-bs-target="#tab-keluar" role="tab">
                            <i class="fas fa-arrow-up text-danger mr-1"></i> Kas Keluar (Pengeluaran)
                            <span class="badge badge-danger ml-1">{{ $keluarCategories->count() }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-weight-bold" id="tab-masuk-tab" data-toggle="pill" data-bs-toggle="pill" href="#tab-masuk" data-target="#tab-masuk" data-bs-target="#tab-masuk" role="tab">
                            <i class="fas fa-arrow-down text-success mr-1"></i> Kas Masuk (Penerimaan)
                            <span class="badge badge-success ml-1">{{ $masukCategories->count() }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-weight-bold" id="tab-semua-tab" data-toggle="pill" data-bs-toggle="pill" href="#tab-semua" data-target="#tab-semua" data-bs-target="#tab-semua" role="tab">
                            <i class="fas fa-list text-primary mr-1"></i> Semua Kategori
                            <span class="badge badge-primary ml-1">{{ $categories->count() }}</span>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body p-2">
                <div class="tab-content" id="categoryTabContent">
                    {{-- TAB 1: KAS KELUAR --}}
                    <div class="tab-pane fade show active" id="tab-keluar" role="tabpanel">
                        @include('kas_category._table', ['cats' => $keluarCategories, 'tableId' => 'tableKeluar', 'defaultType' => 'keluar'])
                    </div>

                    {{-- TAB 2: KAS MASUK --}}
                    <div class="tab-pane fade" id="tab-masuk" role="tabpanel">
                        @include('kas_category._table', ['cats' => $masukCategories, 'tableId' => 'tableMasuk', 'defaultType' => 'masuk'])
                    </div>

                    {{-- TAB 3: SEMUA KATEGORI --}}
                    <div class="tab-pane fade" id="tab-semua" role="tabpanel">
                        @include('kas_category._table', ['cats' => $categories, 'tableId' => 'tableSemua', 'defaultType' => null])
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Tambah / Edit Kategori --}}
<div class="modal fade" id="modalCategory" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white py-2 px-3">
                <h5 class="modal-title font-weight-bold" id="modalCategoryTitle" style="font-size: 0.95rem;">
                    <i class="fas fa-plus mr-1"></i> Tambah Kategori Transaksi Kas
                </h5>
                <button type="button" class="close text-white btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" style="background: none; border: none; font-size: 1.25rem;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formCategory">
                @csrf
                <input type="hidden" name="category_id" id="categoryId" value="">
                <div class="modal-body py-3 px-3">
                    <div class="form-group mb-2">
                        <label class="font-weight-bold text-dark mb-1 small">Jenis Transaksi <span class="text-danger">*</span></label>
                        <div class="d-flex" id="containerCategoryType">
                            <div class="custom-control custom-radio mr-3">
                                <input class="custom-control-input" type="radio" id="typeKeluar" name="type" value="keluar" checked>
                                <label for="typeKeluar" class="custom-control-label font-weight-normal text-danger" style="cursor: pointer;">
                                    <i class="fas fa-arrow-up mr-1"></i> Kas Keluar (Pengeluaran)
                                </label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input class="custom-control-input" type="radio" id="typeMasuk" name="type" value="masuk">
                                <label for="typeMasuk" class="custom-control-label font-weight-normal text-success" style="cursor: pointer;">
                                    <i class="fas fa-arrow-down mr-1"></i> Kas Masuk (Penerimaan)
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-2">
                        <label for="categoryName" class="font-weight-bold text-dark mb-1 small">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="categoryName" name="name" required placeholder="Contoh: Belanja Bahan Praktik, Konsumsi Rapat">
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-7">
                            <label for="categoryCode" class="font-weight-bold text-dark mb-1 small">
                                Kode Sistem (Slug)
                                <small class="text-muted">(opsional, otomatis)</small>
                            </label>
                            <input type="text" class="form-control form-control-sm font-monospace" id="categoryCode" name="code" placeholder="contoh: konsumsi_rapat">
                        </div>
                        <div class="col-md-5">
                            <label for="categorySortOrder" class="font-weight-bold text-dark mb-1 small">Urutan Tampilan</label>
                            <input type="number" class="form-control form-control-sm" id="categorySortOrder" name="sort_order" value="1" min="1">
                        </div>
                    </div>

                    <div class="form-group mb-2">
                        <label for="categoryIcon" class="font-weight-bold text-dark mb-1 small">Ikon / Emoji</label>
                        <div class="input-group input-group-sm mb-1">
                            <input type="text" class="form-control" id="categoryIcon" name="icon" placeholder="Contoh: 🛍️, 💼, 🏦, 🚗, 👥" maxlength="50">
                        </div>
                        <div class="emoji-picker-container">
                            <span class="emoji-picker-btn" data-emoji="🛍️">🛍️</span>
                            <span class="emoji-picker-btn" data-emoji="💼">💼</span>
                            <span class="emoji-picker-btn" data-emoji="🏦">🏦</span>
                            <span class="emoji-picker-btn" data-emoji="💵">💵</span>
                            <span class="emoji-picker-btn" data-emoji="📋">📋</span>
                            <span class="emoji-picker-btn" data-emoji="👥">👥</span>
                            <span class="emoji-picker-btn" data-emoji="🚗">🚗</span>
                            <span class="emoji-picker-btn" data-emoji="💸">💸</span>
                            <span class="emoji-picker-btn" data-emoji="🤝">🤝</span>
                            <span class="emoji-picker-btn" data-emoji="📦">📦</span>
                            <span class="emoji-picker-btn" data-emoji="🏫">🏫</span>
                            <span class="emoji-picker-btn" data-emoji="🔄">🔄</span>
                            <span class="emoji-picker-btn" data-emoji="☕">☕</span>
                            <span class="emoji-picker-btn" data-emoji="🍱">🍱</span>
                            <span class="emoji-picker-btn" data-emoji="🛠️">🛠️</span>
                            <span class="emoji-picker-btn" data-emoji="💻">💻</span>
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <label for="categoryDescription" class="font-weight-bold text-dark mb-1 small">Keterangan / Petunjuk Penggunaan</label>
                        <textarea class="form-control form-control-sm" id="categoryDescription" name="description" rows="2" placeholder="Jelaskan jenis belanja atau transaksi apa saja yang masuk ke kategori ini..."></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3 bg-light justify-content-between">
                    <button type="button" class="btn btn-sm btn-secondary btn-dismiss-modal" data-dismiss="modal" data-bs-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-sm btn-success" id="btnSubmitCategory">
                        <i class="fas fa-save mr-1"></i> <span id="btnSubmitCategoryText">Simpan Kategori</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
    // Universal Modal Helper for Bootstrap 4 / Bootstrap 5 / Vanilla Fallback
    window.showModal = function(selector) {
        let el = typeof selector === 'string' ? document.querySelector(selector) : selector;
        if (!el) return;

        if (window.bootstrap && typeof window.bootstrap.Modal !== 'undefined') {
            try {
                let modal = bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(el) : (bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el));
                modal.show();
                return;
            } catch (e) {
                console.warn('Bootstrap modal error:', e);
            }
        }

        if (window.jQuery && typeof $(el).modal === 'function') {
            try {
                $(el).modal('show');
                return;
            } catch (e) {
                console.warn('jQuery modal error:', e);
            }
        }

        $(el).addClass('show').css('display', 'block').removeAttr('aria-hidden').attr('aria-modal', 'true');
        if (!$('.modal-backdrop').length) {
            $('body').addClass('modal-open').append('<div class="modal-backdrop fade show custom-modal-backdrop"></div>');
        }
    };

    window.hideModal = function(selector) {
        let el = typeof selector === 'string' ? document.querySelector(selector) : selector;
        if (!el) return;

        if (window.bootstrap && typeof window.bootstrap.Modal !== 'undefined') {
            try {
                let modal = bootstrap.Modal.getInstance(el);
                if (modal) {
                    modal.hide();
                }
            } catch (e) {}
        }

        if (window.jQuery && typeof $(el).modal === 'function') {
            try {
                $(el).modal('hide');
            } catch (e) {}
        }

        $(el).removeClass('show').css('display', 'none').attr('aria-hidden', 'true').removeAttr('aria-modal');
        $('body').removeClass('modal-open');
        $('.modal-backdrop, .custom-modal-backdrop').remove();
    };

    $(document).ready(function() {
        // Setup CSRF
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Initialize DataTables if available
        if ($.fn.DataTable) {
            $('.category-table').each(function() {
                if (!$.fn.DataTable.isDataTable(this)) {
                    $(this).DataTable({
                        autoWidth: false,
                        pageLength: 25,
                        order: [[0, 'asc']],
                        language: {
                            search: "Cari Kategori:",
                            lengthMenu: "Tampilkan _MENU_ data",
                            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ kategori",
                            infoEmpty: "Tidak ada data kategori",
                            zeroRecords: "Kategori tidak ditemukan",
                            paginate: {
                                previous: "Sebelumnya",
                                next: "Berikutnya"
                            }
                        }
                    });
                }
            });
        }

        // Explicit Tab Switching Handler (Robust against BS4 / BS5 mismatch)
        $(document).on('click', '#categoryTab .nav-link', function(e) {
            e.preventDefault();
            let target = $(this).attr('href') || $(this).data('bs-target') || $(this).data('target');
            $('#categoryTab .nav-link').removeClass('active');
            $(this).addClass('active');
            $('#categoryTabContent .tab-pane').removeClass('show active');
            $(target).addClass('show active');
        });

        // Emoji Picker click listener
        $(document).on('click', '.emoji-picker-btn', function() {
            let emoji = $(this).data('emoji') || $(this).text().trim();
            $('#categoryIcon').val(emoji);
        });

        // Auto slug from name
        $('#categoryName').on('input', function() {
            if (!$('#categoryId').val()) {
                let text = $(this).val().toLowerCase()
                    .replace(/[^a-z0-9]+/g, '_')
                    .replace(/^_+|_+$/g, '');
                $('#categoryCode').val(text);
            }
        });

        // Open Create Modal
        $(document).on('click', '#btnCreateCategory', function(e) {
            e.preventDefault();
            $('#formCategory')[0].reset();
            $('#categoryId').val('');
            $('#modalCategoryTitle').html('<i class="fas fa-plus mr-1"></i> Tambah Kategori Transaksi Kas');
            $('#btnSubmitCategoryText').text('Simpan Kategori');
            $('#categoryCode').prop('readonly', false);
            $('input[name="type"]').prop('disabled', false);

            let activeTab = $('#categoryTab .nav-link.active').attr('id');
            if (activeTab === 'tab-masuk-tab') {
                $('#typeMasuk').prop('checked', true);
            } else {
                $('#typeKeluar').prop('checked', true);
            }

            showModal('#modalCategory');
        });

        // Edit Category Trigger
        $(document).on('click', '.btn-edit-category', function(e) {
            e.preventDefault();
            let id = $(this).data('id');
            let name = $(this).data('name');
            let code = $(this).data('code');
            let type = $(this).data('type');
            let icon = $(this).data('icon') || '';
            let desc = $(this).data('desc') || '';
            let sort = $(this).data('sort') || 1;
            let isSystem = $(this).data('system') == '1';

            $('#formCategory')[0].reset();
            $('#categoryId').val(id);
            $('#categoryName').val(name);
            $('#categoryCode').val(code);
            $('#categoryIcon').val(icon);
            $('#categoryDescription').val(desc);
            $('#categorySortOrder').val(sort);

            if (type === 'masuk') {
                $('#typeMasuk').prop('checked', true);
            } else {
                $('#typeKeluar').prop('checked', true);
            }

            if (isSystem) {
                $('#categoryCode').prop('readonly', true);
                $('input[name="type"]').prop('disabled', true);
                $('#modalCategoryTitle').html('<i class="fas fa-edit mr-1"></i> Edit Kategori Sistem #' + id + ' <span class="badge badge-warning ml-1">Sistem</span>');
            } else {
                $('#categoryCode').prop('readonly', false);
                $('input[name="type"]').prop('disabled', false);
                $('#modalCategoryTitle').html('<i class="fas fa-edit mr-1"></i> Edit Kategori #' + id);
            }

            $('#btnSubmitCategoryText').text('Simpan Perubahan');
            showModal('#modalCategory');
        });

        // Explicit Modal Close & Dismiss Buttons
        $(document).on('click', '[data-dismiss="modal"], [data-bs-dismiss="modal"], .btn-dismiss-modal, #modalCategory .close, #modalCategory .btn-close', function(e) {
            e.preventDefault();
            hideModal('#modalCategory');
        });

        // Submit Form Create / Update
        $('#formCategory').on('submit', function(e) {
            e.preventDefault();
            let id = $('#categoryId').val();
            let url = id ? "{{ url('kas-category') }}/" + id : "{{ route('kas-category.store') }}";
            let method = id ? 'PUT' : 'POST';

            let formData = $(this).serializeArray();
            if (id) {
                formData.push({ name: '_method', value: 'PUT' });
            }

            $('#btnSubmitCategory').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                success: function(res) {
                    hideModal('#modalCategory');
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: res.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    } else {
                        alert(res.message);
                        location.reload();
                    }
                },
                error: function(err) {
                    let msg = err.responseJSON?.message || 'Terjadi kesalahan sistem.';
                    if (window.Swal) {
                        Swal.fire({ icon: 'error', title: 'Gagal Menyimpan', text: msg });
                    } else {
                        alert('Gagal: ' + msg);
                    }
                },
                complete: function() {
                    $('#btnSubmitCategory').prop('disabled', false).html('<i class="fas fa-save mr-1"></i> ' + (id ? 'Simpan Perubahan' : 'Simpan Kategori'));
                }
            });
        });

        // Toggle Status
        $(document).on('click', '.btn-toggle-status', function(e) {
            e.preventDefault();
            let id = $(this).data('id');
            let $btn = $(this);

            $.ajax({
                url: "{{ url('kas-category') }}/" + id + "/toggle-status",
                type: 'POST',
                success: function(res) {
                    if (res.is_active) {
                        $btn.removeClass('badge-secondary').addClass('badge-success').html('<i class="fas fa-check-circle mr-1"></i> Aktif');
                    } else {
                        $btn.removeClass('badge-success').addClass('badge-secondary').html('<i class="fas fa-times-circle mr-1"></i> Nonaktif');
                    }
                    if (window.Swal) {
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000
                        });
                        Toast.fire({ icon: 'success', title: res.message });
                    }
                },
                error: function(err) {
                    let msg = err.responseJSON?.message || 'Tidak dapat mengubah status.';
                    if (window.Swal) {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: msg });
                    } else {
                        alert(msg);
                    }
                }
            });
        });

        // Delete Category
        $(document).on('click', '.btn-delete-category', function(e) {
            e.preventDefault();
            let id = $(this).data('id');
            let name = $(this).data('name');

            let performDelete = function() {
                $.ajax({
                    url: "{{ url('kas-category') }}/" + id,
                    type: 'POST',
                    data: { _method: 'DELETE' },
                    success: function(res) {
                        if (window.Swal) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Terhapus!',
                                text: res.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => location.reload());
                        } else {
                            alert(res.message);
                            location.reload();
                        }
                    },
                    error: function(err) {
                        let msg = err.responseJSON?.message || 'Tidak dapat menghapus kategori.';
                        if (window.Swal) {
                            Swal.fire({ icon: 'error', title: 'Gagal Menghapus', text: msg });
                        } else {
                            alert(msg);
                        }
                    }
                });
            };

            if (window.Swal) {
                Swal.fire({
                    title: 'Hapus Kategori?',
                    html: `Apakah Anda yakin ingin menghapus kategori <b>"${name}"</b>?<br><small class="text-muted">Kategori yang sudah terpakai tidak dapat dihapus.</small>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-trash mr-1"></i> Ya, Hapus',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        performDelete();
                    }
                });
            } else {
                if (confirm(`Apakah Anda yakin ingin menghapus kategori "${name}"?`)) {
                    performDelete();
                }
            }
        });
    });
</script>
@stop