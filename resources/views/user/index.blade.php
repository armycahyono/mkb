@extends('adminlte::page')

@section('title', 'Manajemen User')

@section('plugins.Datatables', true)
@section('plugins.Select2', true)
@section('plugins.Sweetalert2', true)

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap" style="padding: 2px 0;">
    <div>
        <h1 class="page-title"><i class="fas fa-users-cog text-secondary mr-1"></i> Manajemen User</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-house"></i> Dashboard</a></li>
                <li class="breadcrumb-item">Pengaturan</li>
                <li class="breadcrumb-item active">Manajemen User</li>
            </ol>
        </nav>
    </div>
    <div class="header-actions">
        <button type="button" class="btn btn-sm btn-success" id="btnCreateUser">
            <i class="fas fa-user-plus mr-1"></i> Tambah User Baru
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
    .font-tabular {
        font-variant-numeric: tabular-nums;
        font-feature-settings: "tnum";
    }
    #userTable {
        font-size: 0.8125rem !important;
    }
    #userTable th {
        font-size: 0.74rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 0.45rem 0.6rem !important;
        background-color: #f8fafc;
        color: #475569;
        vertical-align: middle;
    }
    #userTable td {
        padding: 0.45rem 0.6rem !important;
        vertical-align: middle;
        line-height: 1.35;
    }
    .select2-container--bootstrap4 .select2-selection--single {
        height: calc(1.8125rem + 2px) !important;
        font-size: 0.8125rem !important;
        line-height: 1.5;
    }
    .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
        line-height: 1.8125rem !important;
        padding-left: 0.5rem !important;
    }
</style>
@stop

@section('content')
<div class="card shadow-sm border-0 mb-3">
    <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center flex-wrap" style="border-bottom: 1px solid #edf2f7;">
        <h3 class="card-title font-weight-bold text-dark mb-0" style="font-size: 0.92rem;">
            <i class="fas fa-list mr-1 text-primary"></i> Daftar Akun Pengguna Sistem
        </h3>
        <div>
            <span class="text-muted small">Kelola akses akun, kredensial login, dan integrasi bot Telegram.</span>
        </div>
    </div>
    <div class="card-body p-2">
        <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered table-striped w-100" id="userTable">
                <thead>
                    <tr>
                        <th width="40" class="text-center">NO</th>
                        <th>NAMA LENGKAP</th>
                        <th width="130">USERNAME</th>
                        <th width="170">EMAIL</th>
                        <th width="150" class="text-center">PERAN / HAK AKSES</th>
                        <th width="170" class="text-center">STATUS TELEGRAM BOT</th>
                        <th width="120" class="text-center">TERDAFTAR</th>
                        <th width="85" class="text-center">AKSI</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

{{-- Modal Tambah User --}}
<div class="modal fade" id="createUserModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form id="createUserForm">
            @csrf
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white py-2 px-3">
                    <h5 class="modal-title font-weight-bold" style="font-size: 0.95rem;">
                        <i class="fas fa-user-plus mr-1"></i> Tambah User Baru
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="form-group mb-3">
                        <label class="font-weight-bold small text-secondary mb-1">
                            <i class="fas fa-link text-primary mr-1"></i> Tautkan dengan Profil Guru (Opsional)
                        </label>
                        <select name="guru_id" id="createGuruId" class="form-control form-control-sm select2" style="width: 100%;">
                            <option value="">-- Bukan Guru / Akun Non-Guru --</option>
                            @foreach($gurus as $guru)
                                @php
                                    $activeJabatans = $guru->activeJabatans->pluck('jabatan.name')->filter()->join(', ');
                                    $desc = $guru->full_name_with_title . ($activeJabatans ? " ({$activeJabatans})" : '');
                                @endphp
                                <option value="{{ $guru->id }}"
                                    data-name="{{ $guru->name }}"
                                    data-full-name="{{ $guru->full_name_with_title }}"
                                    data-email="{{ $guru->email }}"
                                    data-suggested-role="{{ $guru->suggested_role }}"
                                    data-suggested-role-label="{{ $guru->suggested_role_label }}">
                                    {{ $desc }}
                                </option>
                            @endforeach
                        </select>
                        <div id="createRoleSuggestionHint" class="small text-success mt-1" style="display: none;">
                            <i class="fas fa-magic mr-1"></i> Role disesuaikan otomatis dari SK: <strong id="createRoleSuggestionName"></strong>
                        </div>
                        <small class="text-muted d-block mt-1" style="font-size:0.75rem;">Jika ditautkan ke guru, role akun disarankan otomatis sesuai SK jabatan aktif.</small>
                    </div>
                    <div class="form-group mb-3">
                        <label class="font-weight-bold small text-secondary mb-1">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="createName" class="form-control form-control-sm" placeholder="Contoh: Ahmad Dahlan, S.Pd." required>
                    </div>
                    <div class="form-group mb-3">
                        <label class="font-weight-bold small text-secondary mb-1">Peran Akun & Hak Akses <span class="text-danger">*</span></label>
                        <select name="role" id="createRole" class="form-control form-control-sm" required>
                            <option value="super_admin">👑 Super Admin (Akses mutlak & kontrol penuh sistem)</option>
                            <option value="admin">⚙️ Administrator (Akses administratif seluruh modul)</option>
                            <option value="kepala_sekolah">🛡️ Kepala Sekolah (Pemeriksaan seluruh kegiatan)</option>
                            <option value="bendahara_bos" selected>💼 Bendahara BOS (Akses penuh Data Pokok & BOSP)</option>
                            <option value="bendahara_bpopp">💼 Bendahara BPOPP (Akses penuh Data Pokok & BPOPP)</option>
                            <option value="waka_sarpras">📦 Waka Sarpras (Data Pokok, BOSP & BPOPP kecuali Kas)</option>
                            <option value="team_bosp">📸 Tim BOSP (Data Pokok & BOSP kecuali Kas)</option>
                            <option value="team_bpopp">📸 Tim BPOPP (Data Pokok & BPOPP kecuali Kas)</option>
                        </select>
                        <small class="text-muted">Hak akses web dan bot Telegram disesuaikan secara otomatis berdasarkan peran yang dipilih.</small>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold small text-secondary mb-1">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" id="createUsername" class="form-control form-control-sm" placeholder="Contoh: bendahara1" required>
                            <small class="text-muted">Gunakan huruf, angka, tanpa spasi.</small>
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold small text-secondary mb-1">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="createEmail" class="form-control form-control-sm" placeholder="Contoh: user@sekolah.sch.id" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold small text-secondary mb-1">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" id="createPassword" class="form-control form-control-sm" placeholder="Minimal 6 karakter" required>
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold small text-secondary mb-1">Konfirmasi Password <span class="text-danger">*</span></label>
                            <input type="password" name="password_confirmation" id="createPasswordConfirm" class="form-control form-control-sm" placeholder="Ulangi password" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-3">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-success">
                        <i class="fas fa-save mr-1"></i> Simpan User
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modal Edit User --}}
<div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form id="editUserForm">
            @csrf
            @method('PUT')
            <input type="hidden" id="editUserId">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning py-2 px-3">
                    <h5 class="modal-title font-weight-bold text-dark" style="font-size: 0.95rem;">
                        <i class="fas fa-user-edit mr-1"></i> Edit Data User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="form-group mb-3">
                        <label class="font-weight-bold small text-secondary mb-1">
                            <i class="fas fa-link text-primary mr-1"></i> Tautkan dengan Profil Guru (Opsional)
                        </label>
                        <select name="guru_id" id="editGuruId" class="form-control form-control-sm select2" style="width: 100%;">
                            <option value="">-- Bukan Guru / Akun Non-Guru --</option>
                            @foreach($gurus as $guru)
                                @php
                                    $activeJabatans = $guru->activeJabatans->pluck('jabatan.name')->filter()->join(', ');
                                    $desc = $guru->full_name_with_title . ($activeJabatans ? " ({$activeJabatans})" : '');
                                @endphp
                                <option value="{{ $guru->id }}"
                                    data-name="{{ $guru->name }}"
                                    data-full-name="{{ $guru->full_name_with_title }}"
                                    data-email="{{ $guru->email }}"
                                    data-suggested-role="{{ $guru->suggested_role }}"
                                    data-suggested-role-label="{{ $guru->suggested_role_label }}">
                                    {{ $desc }}
                                </option>
                            @endforeach
                        </select>
                        <div id="editRoleSuggestionHint" class="small text-success mt-1" style="display: none;">
                            <i class="fas fa-magic mr-1"></i> Role disesuaikan otomatis dari SK: <strong id="editRoleSuggestionName"></strong>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label class="font-weight-bold small text-secondary mb-1">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="editName" class="form-control form-control-sm" required>
                    </div>
                    <div class="form-group mb-3">
                        <label class="font-weight-bold small text-secondary mb-1">Peran Akun & Hak Akses <span class="text-danger">*</span></label>
                        <select name="role" id="editRole" class="form-control form-control-sm" required>
                            <option value="super_admin">👑 Super Admin (Akses mutlak & kontrol penuh sistem)</option>
                            <option value="admin">⚙️ Administrator (Akses administratif seluruh modul)</option>
                            <option value="kepala_sekolah">🛡️ Kepala Sekolah (Pemeriksaan seluruh kegiatan)</option>
                            <option value="bendahara_bos">💼 Bendahara BOS (Akses penuh Data Pokok & BOSP)</option>
                            <option value="bendahara_bpopp">💼 Bendahara BPOPP (Akses penuh Data Pokok & BPOPP)</option>
                            <option value="waka_sarpras">📦 Waka Sarpras (Data Pokok, BOSP & BPOPP kecuali Kas)</option>
                            <option value="team_bosp">📸 Tim BOSP (Data Pokok & BOSP kecuali Kas)</option>
                            <option value="team_bpopp">📸 Tim BPOPP (Data Pokok & BPOPP kecuali Kas)</option>
                        </select>
                        <small class="text-muted">Hak akses web dan bot Telegram disesuaikan secara otomatis berdasarkan peran yang dipilih.</small>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold small text-secondary mb-1">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" id="editUsername" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold small text-secondary mb-1">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="editEmail" class="form-control form-control-sm" required>
                        </div>
                    </div>

                    <div class="p-2 border rounded bg-light mb-3">
                        <div class="text-muted small font-weight-bold mb-1">
                            <i class="fas fa-key mr-1"></i> Ganti Password (Opsional)
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group mb-1">
                                <input type="password" name="password" id="editPassword" class="form-control form-control-sm" placeholder="Password baru (kosongkan jika tidak diganti)">
                            </div>
                            <div class="col-md-6 form-group mb-1">
                                <input type="password" name="password_confirmation" id="editPasswordConfirm" class="form-control form-control-sm" placeholder="Ulangi password baru">
                            </div>
                        </div>
                    </div>

                    <div id="telegramStatusSection" class="p-2 border rounded bg-light" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fab fa-telegram-plane text-info mr-1"></i>
                                <span class="small font-weight-bold">Akun Telegram Terhubung:</span>
                                <span id="telegramUsernameText" class="badge badge-info ml-1"></span>
                            </div>
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" name="unlink_telegram" value="1" id="unlinkTelegram">
                                <label class="form-check-label text-danger small font-weight-bold" for="unlinkTelegram">
                                    Putuskan Sambungan
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-3">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-warning font-weight-bold">
                        <i class="fas fa-save mr-1"></i> Perbarui User
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@stop

@section('js')
<script>
$(function() {
    function showModal(modalId) {
        let el = document.getElementById(modalId);
        if (window.bootstrap && window.bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(el).show();
        } else if (window.$ && typeof $(el).modal === 'function') {
            $(el).modal('show');
        }
    }

    function hideModal(modalId) {
        let el = document.getElementById(modalId);
        if (window.bootstrap && window.bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(el).hide();
        } else if (window.$ && typeof $(el).modal === 'function') {
            $(el).modal('hide');
        }
    }

    if ($.fn.select2) {
        $('#createGuruId').select2({
            theme: 'bootstrap4',
            dropdownParent: $('#createUserModal'),
            placeholder: '-- Bukan Guru / Akun Non-Guru --',
            allowClear: true
        });
        $('#editGuruId').select2({
            theme: 'bootstrap4',
            dropdownParent: $('#editUserModal'),
            placeholder: '-- Bukan Guru / Akun Non-Guru --',
            allowClear: true
        });
    }

    // Auto-fill & auto-suggest role saat guru dipilih pada Tambah User
    $('#createGuruId').on('change', function() {
        let opt = $(this).find('option:selected');
        let val = $(this).val();
        if (val) {
            let fullName = opt.data('full-name') || opt.data('name');
            let email = opt.data('email');
            let suggestedRole = opt.data('suggested-role');
            let suggestedRoleLabel = opt.data('suggested-role-label');

            if (!$('#createName').val() || $('#createName').data('auto-filled')) {
                $('#createName').val(fullName).data('auto-filled', true);
            }
            if ((!$('#createEmail').val() || $('#createEmail').data('auto-filled')) && email) {
                $('#createEmail').val(email).data('auto-filled', true);
            }
            if (suggestedRole) {
                $('#createRole').val(suggestedRole);
                $('#createRoleSuggestionName').text(suggestedRoleLabel);
                $('#createRoleSuggestionHint').slideDown(150);
            } else {
                $('#createRoleSuggestionHint').slideUp(150);
            }
        } else {
            $('#createRoleSuggestionHint').slideUp(150);
        }
    });

    $('#createName, #createEmail').on('input', function() {
        $(this).data('auto-filled', false);
    });

    // Auto-suggest role saat guru dipilih pada Edit User
    $('#editGuruId').on('select2:select change', function(e) {
        if (e.namespace === 'select2' || e.originalEvent) {
            let opt = $(this).find('option:selected');
            let val = $(this).val();
            if (val) {
                let suggestedRole = opt.data('suggested-role');
                let suggestedRoleLabel = opt.data('suggested-role-label');
                if (suggestedRole) {
                    $('#editRole').val(suggestedRole);
                    $('#editRoleSuggestionName').text(suggestedRoleLabel);
                    $('#editRoleSuggestionHint').slideDown(150);
                } else {
                    $('#editRoleSuggestionHint').slideUp(150);
                }
            } else {
                $('#editRoleSuggestionHint').slideUp(150);
            }
        }
    });

    let table = $('#userTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('user.index', [], false) }}",
            type: "GET",
            error: function(xhr, error, code) {
                console.error('userTable DataTables Ajax Error:', xhr.status, xhr.responseText);
                if (xhr.status === 401) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sesi Berakhir',
                        text: 'Sesi login Anda telah berakhir. Silakan login kembali.',
                        confirmButtonText: 'Login'
                    }).then(() => {
                        window.location.href = "{{ route('login') }}";
                    });
                } else if (xhr.status === 403) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Akses Ditolak',
                        text: 'Anda tidak memiliki hak akses untuk melihat data pengguna.'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Memuat Data',
                        text: 'Terjadi kesalahan saat memuat data tabel pengguna (Kode: ' + xhr.status + ').'
                    });
                }
            }
        },
        pageLength: 10,
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
            { data: 'name_display', name: 'name' },
            { data: 'username', name: 'username', className: 'font-weight-500 font-tabular' },
            { data: 'email', name: 'email', className: 'font-tabular' },
            { data: 'role_display', name: 'role', className: 'text-center' },
            { data: 'telegram_display', name: 'telegram_chat_id', className: 'text-center' },
            { data: 'created_display', name: 'created_at', className: 'text-center' },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[1, 'asc']],
        language: {
            processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"></div> Memuat data...',
            search: "Cari User:",
            lengthMenu: "Tampil _MENU_ data",
            info: "Menampilkan _START_ - _END_ dari _TOTAL_ user",
            infoEmpty: "Tidak ada data user",
            infoFiltered: "(disaring dari _MAX_ total user)",
            emptyTable: "Belum ada data akun pengguna.",
            paginate: { first: "«", previous: "‹", next: "›", last: "»" }
        }
    });

    // Buka Modal Tambah User
    $('#btnCreateUser').click(function(e) {
        e.preventDefault();
        $('#createUserForm')[0].reset();
        $('#createName, #createEmail').data('auto-filled', false);
        if ($.fn.select2) {
            $('#createGuruId').val('').trigger('change.select2');
        } else {
            $('#createGuruId').val('');
        }
        $('#createRoleSuggestionHint').hide();
        showModal('createUserModal');
    });

    // Submit Tambah User
    $('#createUserForm').submit(function(e) {
        e.preventDefault();
        let submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

        $.ajax({
            url: "{{ route('user.store', [], false) }}",
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                hideModal('createUserModal');
                table.draw(false);
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: res.message,
                    timer: 2000,
                    showConfirmButton: false
                });
            },
            error: function(xhr) {
                let msg = 'Terjadi kesalahan sistem.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                Swal.fire('Gagal!', msg, 'error');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan User');
            }
        });
    });

    // Buka Modal Edit User
    $(document).on('click', '.btn-edit-user', function(e) {
        e.preventDefault();
        let $btn = $(this);
        let id = $btn.data('id');
        let guruId = $btn.data('guru-id') || '';
        let name = $btn.data('name');
        let username = $btn.data('username');
        let email = $btn.data('email');
        let hasTelegram = $btn.data('has-telegram') == '1';
        let telegramUser = $btn.data('telegram-user');
        let role = $btn.data('role') || 'bendahara_bos';
        if (role === 'bendahara') role = 'bendahara_bos';
        if (role === 'team_bos') role = 'team_bosp';
        let updateUrl = $btn.data('update-url');

        $('#editUserId').val(id);
        $('#editUserForm').data('action-url', updateUrl);
        if ($.fn.select2) {
            $('#editGuruId').val(guruId).trigger('change.select2');
        } else {
            $('#editGuruId').val(guruId);
        }

        let opt = $('#editGuruId').find('option:selected');
        let suggestedRoleLabel = opt.data('suggested-role-label');
        if (guruId && suggestedRoleLabel) {
            $('#editRoleSuggestionName').text(suggestedRoleLabel);
            $('#editRoleSuggestionHint').show();
        } else {
            $('#editRoleSuggestionHint').hide();
        }

        $('#editName').val(name);
        $('#editUsername').val(username);
        $('#editEmail').val(email);
        $('#editRole').val(role);
        $('#editPassword').val('');
        $('#editPasswordConfirm').val('');
        $('#unlinkTelegram').prop('checked', false);

        if (hasTelegram) {
            $('#telegramUsernameText').text(telegramUser ? '@' + telegramUser : 'Terhubung');
            $('#telegramStatusSection').show();
        } else {
            $('#telegramStatusSection').hide();
        }

        showModal('editUserModal');
    });

    // Submit Edit User
    $('#editUserForm').submit(function(e) {
        e.preventDefault();
        let url = $(this).data('action-url');
        let submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

        $.ajax({
            url: url,
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                hideModal('editUserModal');
                table.draw(false);
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: res.message,
                    timer: 2000,
                    showConfirmButton: false
                });
            },
            error: function(xhr) {
                let msg = 'Terjadi kesalahan sistem.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                Swal.fire('Gagal!', msg, 'error');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Perbarui User');
            }
        });
    });

    // Hapus User
    $(document).on('click', '.btn-delete-user', function(e) {
        e.preventDefault();
        let url = $(this).data('url');
        let name = $(this).data('name');

        Swal.fire({
            title: 'Hapus Akun User?',
            html: `Apakah Anda yakin ingin menghapus akun user <strong>${name}</strong>? Tindakan ini tidak dapat dibatalkan.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        table.draw(false);
                        Swal.fire('Terhapus!', res.message, 'success');
                    },
                    error: function(xhr) {
                        Swal.fire('Gagal!', xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan.', 'error');
                    }
                });
            }
        });
    });
});
</script>
@stop

