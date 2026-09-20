@extends('adminlte::page')

@section('title', 'Pengaturan & Pengembangan Bot Telegram')

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap" style="padding: 2px 0;">
    <div>
        <h1 class="page-title"><i class="fab fa-telegram-plane text-info mr-1"></i> Pengaturan & Pengembangan Bot Telegram</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-house"></i> Dashboard</a></li>
                <li class="breadcrumb-item">Pengaturan</li>
                <li class="breadcrumb-item active">Bot Telegram</li>
            </ol>
        </nav>
    </div>
    <div class="header-actions">
        <a href="https://t.me/{{ $botMe['username'] ?? 'mkbgrik_bot' }}" target="_blank" class="btn btn-sm btn-outline-info mr-1">
            <i class="fab fa-telegram-plane mr-1"></i> Buka Bot Telegram
        </a>
        <button type="button" class="btn btn-sm btn-primary" id="btnSyncCommandsHeader">
            <i class="fas fa-sync-alt mr-1"></i> Sinkronkan Menu ke Telegram
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
    .nav-tabs .nav-link {
        font-weight: 600;
        font-size: 0.85rem;
        color: #495057;
    }
    .nav-tabs .nav-link.active {
        color: #007bff;
        border-bottom-color: transparent;
    }
    .bot-stat-badge {
        font-size: 0.8rem;
        padding: 0.35rem 0.6rem;
    }
    .table th {
        font-size: 0.74rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        background-color: #f8fafc;
        color: #475569;
        vertical-align: middle;
    }
    .table td {
        vertical-align: middle;
        font-size: 0.8125rem !important;
    }
</style>
@stop

@section('content')
<div class="card shadow-sm border-0 mb-3">
    <div class="card-header p-2 bg-white" style="border-bottom: 1px solid #edf2f7;">
        <ul class="nav nav-tabs card-header-tabs" id="botTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="status-tab" data-bs-toggle="tab" data-toggle="tab" href="#tab-status" role="tab" aria-controls="tab-status" aria-selected="true">
                    <i class="fas fa-server mr-1 text-primary"></i> Status & Webhook
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="commands-tab" data-bs-toggle="tab" data-toggle="tab" href="#tab-commands" role="tab" aria-controls="tab-commands" aria-selected="false">
                    <i class="fas fa-terminal mr-1 text-success"></i> Kelola & Kembangkan Perintah Bot ({{ $commands->count() }})
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="logs-tab" data-bs-toggle="tab" data-toggle="tab" href="#tab-logs" role="tab" aria-controls="tab-logs" aria-selected="false">
                    <i class="fas fa-history mr-1 text-warning"></i> Sesi & Riwayat Interaksi ({{ $conversations->count() }})
                </a>
            </li>
        </ul>
    </div>
    <div class="card-body p-3">
        <div class="tab-content" id="botTabsContent">
            
            {{-- TAB 1: STATUS & WEBHOOK --}}
            <div class="tab-pane fade show active" id="tab-status" role="tabpanel" aria-labelledby="status-tab">
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <div class="card h-100 border shadow-none bg-light">
                            <div class="card-header bg-white py-2 px-3">
                                <h6 class="font-weight-bold mb-0 text-dark">
                                    <i class="fab fa-telegram text-info mr-1"></i> Profil Bot Telegram
                                </h6>
                            </div>
                            <div class="card-body p-3">
                                @if($botMe)
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mr-3" style="width: 50px; height: 50px; font-size: 1.5rem;">
                                            <i class="fab fa-telegram-plane"></i>
                                        </div>
                                        <div>
                                            <h5 class="font-weight-bold mb-0">{{ $botMe['first_name'] ?? 'Bot MKB' }}</h5>
                                            <a href="https://t.me/{{ $botMe['username'] }}" target="_blank" class="text-primary font-weight-bold">
                                                &#64;{{ $botMe['username'] }} <i class="fas fa-external-link-alt small ml-1"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <table class="table table-sm table-borderless mb-0">
                                        <tr>
                                            <td width="130" class="text-muted p-1">ID Bot:</td>
                                            <td class="font-tabular font-weight-bold p-1">{{ $botMe['id'] }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted p-1">Dapat Masuk Grup:</td>
                                            <td class="p-1">
                                                <span class="badge {{ ($botMe['can_join_groups'] ?? false) ? 'badge-success' : 'badge-secondary' }}">
                                                    {{ ($botMe['can_join_groups'] ?? false) ? 'Ya' : 'Tidak' }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted p-1">Status Bot:</td>
                                            <td class="p-1"><span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Aktif & Terverifikasi</span></td>
                                        </tr>
                                    </table>
                                @else
                                    <div class="alert alert-danger mb-0">
                                        <i class="fas fa-exclamation-triangle mr-1"></i> Token bot Telegram tidak terhubung atau tidak valid. Periksa konfigurasi di file <code>.env</code>.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 mb-3">
                        <div class="card h-100 border shadow-none bg-light">
                            <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center">
                                <h6 class="font-weight-bold mb-0 text-dark">
                                    <i class="fas fa-network-wired text-success mr-1"></i> Status Webhook Endpoint
                                </h6>
                                @if(!empty($webhookInfo['url']))
                                    <span class="badge badge-success px-2 py-1"><i class="fas fa-plug mr-1"></i>Tersambung (Live)</span>
                                @else
                                    <span class="badge badge-secondary px-2 py-1"><i class="fas fa-times-circle mr-1"></i>Belum Didaftarkan</span>
                                @endif
                            </div>
                            <div class="card-body p-3">
                                <table class="table table-sm table-borderless mb-3">
                                    <tr>
                                        <td width="130" class="text-muted p-1">URL Webhook:</td>
                                        <td class="p-1 font-tabular">
                                            @if(!empty($webhookInfo['url']))
                                                <code class="text-primary font-weight-bold" style="word-break: break-all;">{{ $webhookInfo['url'] }}</code>
                                            @else
                                                <span class="text-muted font-italic">- Tidak ada webhook aktif -</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted p-1">IP Telegram:</td>
                                        <td class="p-1 font-tabular">{{ $webhookInfo['ip_address'] ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted p-1">Antrean Pesan:</td>
                                        <td class="p-1 font-tabular">
                                            <span class="badge {{ ($webhookInfo['pending_update_count'] ?? 0) > 0 ? 'badge-warning' : 'badge-light border' }}">
                                                {{ $webhookInfo['pending_update_count'] ?? 0 }} pesan
                                            </span>
                                        </td>
                                    </tr>
                                    @if(!empty($webhookInfo['last_error_message']))
                                        <tr>
                                            <td class="text-danger p-1">Error Terakhir:</td>
                                            <td class="p-1 text-danger small font-weight-bold">{{ $webhookInfo['last_error_message'] }}</td>
                                        </tr>
                                    @endif
                                </table>

                                <div class="d-flex flex-wrap gap-2 pt-2 border-top">
                                    <button type="button" class="btn btn-sm btn-success mr-2 mb-1" id="btnSyncWebhook">
                                        <i class="fas fa-sync mr-1"></i> Sinkronkan / Set Webhook
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger mb-1" id="btnDeleteWebhook">
                                        <i class="fas fa-trash-alt mr-1"></i> Hapus Webhook
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card Test Kirim Pesan --}}
                <div class="card border shadow-none bg-light">
                    <div class="card-header bg-white py-2 px-3">
                        <h6 class="font-weight-bold mb-0 text-dark">
                            <i class="fas fa-paper-plane text-warning mr-1"></i> Uji Coba Kirim Pesan Bot (Test Send)
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <form id="formTestSend">
                            @csrf
                            <div class="row">
                                <div class="col-md-4 form-group mb-2">
                                    <label class="small font-weight-bold text-secondary mb-1">Telegram Chat ID Tujuan</label>
                                    <input type="text" name="chat_id" id="testChatId" class="form-control form-control-sm font-tabular" placeholder="Contoh: 123456789 atau pilih akun user..." required>
                                    <small class="text-muted">ID Telegram pengguna yang pernah menekan /start.</small>
                                </div>
                                <div class="col-md-6 form-group mb-2">
                                    <label class="small font-weight-bold text-secondary mb-1">Isi Pesan Uji Coba</label>
                                    <input type="text" name="message" id="testMessage" class="form-control form-control-sm" value="🔔 Tes pengiriman pesan dari sistem MKB berjalan sukses!" required>
                                </div>
                                <div class="col-md-2 form-group mb-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-sm btn-primary w-100">
                                        <i class="fas fa-paper-plane mr-1"></i> Kirim Tes
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- TAB 2: KELOLA & KEMBANGKAN PERINTAH BOT --}}
            <div class="tab-pane fade" id="tab-commands" role="tabpanel" aria-labelledby="commands-tab">
                <div class="d-flex justify-content-between align-items-center flex-wrap mb-3 p-2 bg-light rounded border">
                    <div>
                        <span class="font-weight-bold text-dark d-block">
                            <i class="fas fa-info-circle text-info mr-1"></i> Sinkronisasi Menu Perintah Telegram
                        </span>
                        <small class="text-muted">
                            Daftarkan perintah di bawah ini ke Telegram Bot API agar muncul otomatis pada tombol <code>Menu [/]</code> di aplikasi Telegram pengguna.
                        </small>
                    </div>
                    <div class="mt-2 mt-sm-0">
                        <button type="button" class="btn btn-sm btn-primary mr-2" id="btnSyncCommands">
                            <i class="fas fa-sync-alt mr-1"></i> Sinkronkan Menu ke Telegram
                        </button>
                        <button type="button" class="btn btn-sm btn-success" id="btnCreateCommand">
                            <i class="fas fa-plus mr-1"></i> Tambah Perintah Kustom (FAQ/Info)
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-hover table-bordered table-striped w-100" id="tableCommands">
                        <thead>
                            <tr>
                                <th width="40" class="text-center">NO</th>
                                <th width="150">PERINTAH</th>
                                <th width="90" class="text-center">TIPE</th>
                                <th width="260">DESKRIPSI MENU TELEGRAM</th>
                                <th>RESPON BALASAN / LOGIKA</th>
                                <th width="90" class="text-center">STATUS</th>
                                <th width="90" class="text-center">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($commands as $index => $cmd)
                                <tr>
                                    <td class="text-center font-tabular">{{ $index + 1 }}</td>
                                    <td>
                                        <span class="badge badge-light border text-primary font-weight-bold px-2 py-1 font-tabular" style="font-size: 0.85rem;">
                                            /{{ $cmd->command }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        {!! $cmd->type_badge !!}
                                    </td>
                                    <td>
                                        <div class="font-weight-bold text-dark">{{ $cmd->description }}</div>
                                    </td>
                                    <td>
                                        @if($cmd->isSystem())
                                            <span class="text-muted small font-italic">
                                                <i class="fas fa-code mr-1"></i>Diproses oleh Sistem Laravel (Controller)
                                            </span>
                                        @else
                                            <div class="text-dark small" style="max-height: 50px; overflow-y: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                {{ Str::limit($cmd->response_text, 80) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-xs {{ $cmd->is_active ? 'btn-success' : 'btn-secondary' }} btn-toggle-command" 
                                            data-url="{{ route('telegram-bot.commands.toggle', $cmd->id) }}"
                                            title="Klik untuk {{ $cmd->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                            {{ $cmd->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </button>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        @if($cmd->hasFlowConfig())
                                            <button type="button" class="btn btn-xs btn-info btn-flow-command mr-1"
                                                data-id="{{ $cmd->id }}"
                                                data-command="{{ $cmd->command }}"
                                                data-flow-url="{{ route('telegram-bot.commands.flow.get', $cmd->id) }}"
                                                data-update-url="{{ route('telegram-bot.commands.flow.update', $cmd->id) }}"
                                                data-reset-url="{{ route('telegram-bot.commands.flow.reset', $cmd->id) }}"
                                                title="Atur Alur Percakapan (Workflow Editor)">
                                                <i class="fas fa-project-diagram mr-1"></i>Atur Alur
                                            </button>
                                        @endif
                                        <button type="button" class="btn btn-xs btn-warning btn-edit-command mr-1"
                                            data-id="{{ $cmd->id }}"
                                            data-command="{{ $cmd->command }}"
                                            data-description="{{ $cmd->description }}"
                                            data-is-system="{{ $cmd->isSystem() ? '1' : '0' }}"
                                            data-response="{{ $cmd->response_text }}"
                                            data-sort="{{ $cmd->sort_order }}"
                                            data-update-url="{{ route('telegram-bot.commands.update', $cmd->id) }}"
                                            title="Edit Perintah">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        @if(! $cmd->isSystem())
                                            <button type="button" class="btn btn-xs btn-danger btn-delete-command"
                                                data-url="{{ route('telegram-bot.commands.destroy', $cmd->id) }}"
                                                data-name="/{{ $cmd->command }}"
                                                title="Hapus Perintah">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-xs btn-secondary" disabled title="Perintah sistem tidak dapat dihapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-3">Belum ada data perintah bot.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- TAB 3: RIWAYAT & AKTIVITAS BOT --}}
            <div class="tab-pane fade" id="tab-logs" role="tabpanel" aria-labelledby="logs-tab">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small">Menampilkan 30 aktivitas dan sesi bot terakhir dari pengguna:</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-bordered table-striped w-100">
                        <thead>
                            <tr>
                                <th width="140" class="text-center">WAKTU</th>
                                <th>PENGGUNA MKB</th>
                                <th width="140">CHAT ID</th>
                                <th width="140">PERINTAH</th>
                                <th width="140" class="text-center">TAHAP (STEP)</th>
                                <th>DATA DRAFT (JSON)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($conversations as $conv)
                                <tr>
                                    <td class="text-center font-tabular text-muted small">
                                        {{ $conv->updated_at ? $conv->updated_at->format('d/m/Y H:i:s') : '-' }}
                                    </td>
                                    <td>
                                        @if($conv->user)
                                            <div class="font-weight-bold text-dark mb-0">{{ $conv->user->name }}</div>
                                            <small class="text-muted">{{ $conv->user->email }}</small>
                                        @else
                                            <span class="text-muted font-italic">Belum Terhubung</span>
                                        @endif
                                    </td>
                                    <td class="font-tabular">
                                        <code>{{ $conv->chat_id }}</code>
                                    </td>
                                    <td>
                                        <span class="badge badge-info font-tabular">/{{ $conv->command ?? 'idle' }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-light border">{{ $conv->step ?? '-' }}</span>
                                    </td>
                                    <td>
                                        <div class="text-muted font-tabular small" style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            {{ json_encode($conv->draft_data) }}
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">Belum ada riwayat aktivitas bot yang tercatat.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- Modal Tambah Perintah Kustom --}}
<div class="modal fade" id="createCommandModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form id="createCommandForm">
            @csrf
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white py-2 px-3">
                    <h5 class="modal-title font-weight-bold" style="font-size: 0.95rem;">
                        <i class="fas fa-plus-circle mr-1"></i> Tambah Perintah Kustom (FAQ / Balasan Otomatis)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="form-group mb-3">
                        <label class="font-weight-bold small text-secondary mb-1">Perintah (Command) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text font-weight-bold">/</span>
                            <input type="text" name="command" id="createCommandInput" class="form-control form-control-sm" placeholder="contoh: kontak, rekening, panduan" required>
                        </div>
                        <small class="text-muted">Gunakan huruf kecil, angka, garis bawah (_). Maksimal 32 karakter.</small>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold small text-secondary mb-1">Deskripsi Menu Telegram <span class="text-danger">*</span></label>
                        <input type="text" name="description" id="createDescription" class="form-control form-control-sm" placeholder="Contoh: Informasi nomor kontak bendahara sekolah" required>
                        <small class="text-muted">Teks ini yang akan tampil di tombol Menu Telegram pengguna.</small>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold small text-secondary mb-1">Teks Respon Balasan Bot <span class="text-danger">*</span></label>
                        <textarea name="response_text" id="createResponseText" rows="4" class="form-control form-control-sm" placeholder="Ketik isi pesan balasan bot. Mendukung format HTML seperti &lt;b&gt;tebal&lt;/b&gt;, &lt;i&gt;miring&lt;/i&gt;, emoji, dan nomor telepon." required></textarea>
                        <small class="text-muted">Pesan ini otomatis dikirim ke pengguna saat perintah diketik.</small>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-3">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-success">
                        <i class="fas fa-save mr-1"></i> Simpan Perintah
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modal Edit Perintah --}}
<div class="modal fade" id="editCommandModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form id="editCommandForm">
            @csrf
            @method('PUT')
            <input type="hidden" id="editCommandId">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning py-2 px-3">
                    <h5 class="modal-title font-weight-bold text-dark" style="font-size: 0.95rem;">
                        <i class="fas fa-edit mr-1"></i> Edit Perintah Bot
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="form-group mb-3">
                        <label class="font-weight-bold small text-secondary mb-1">Perintah (Command)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text font-weight-bold">/</span>
                            <input type="text" name="command" id="editCommandInput" class="form-control form-control-sm" required>
                        </div>
                        <small id="systemCommandNotice" class="text-info font-italic" style="display:none;">Nama perintah bawaan sistem tidak dapat diubah.</small>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold small text-secondary mb-1">Deskripsi Menu Telegram <span class="text-danger">*</span></label>
                        <input type="text" name="description" id="editDescription" class="form-control form-control-sm" required>
                    </div>

                    <div class="form-group mb-3" id="editResponseGroup">
                        <label class="font-weight-bold small text-secondary mb-1">Teks Respon Balasan Bot <span class="text-danger">*</span></label>
                        <textarea name="response_text" id="editResponseText" rows="4" class="form-control form-control-sm"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-3">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-warning font-weight-bold">
                        <i class="fas fa-save mr-1"></i> Perbarui Perintah
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modal Pengaturan Alur Langkah Bot (Workflow Editor) --}}
<div class="modal fade" id="modalFlowEditor" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <form id="formFlowEditor">
            @csrf
            <input type="hidden" id="flowCommandId">
            <input type="hidden" id="flowUpdateUrl">
            <input type="hidden" id="flowResetUrl">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-info text-white py-2 px-3">
                    <h5 class="modal-title font-weight-bold" style="font-size: 0.95rem;">
                        <i class="fas fa-project-diagram mr-1"></i> Pengaturan Alur Percakapan: <span id="flowModalTitle" class="badge badge-light text-dark font-tabular ml-1">/transaksi</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3" style="max-height: 75vh; overflow-y: auto;">
                    <div class="alert alert-light border py-2 px-3 mb-3 small text-muted">
                        <i class="fas fa-info-circle text-info mr-1"></i>
                        Anda dapat <b>mengaktifkan / menonaktifkan</b> langkah tertentu (bot otomatis melewatinya), menentukan apakah langkah tersebut <b>wajib diisi</b> atau <b>boleh di-skip (/skip)</b>, dan <b>mengubah kalimat instruksi</b> yang diajukan bot kepada pengguna.
                    </div>

                    <div id="flowLoading" class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-info"></i>
                        <div class="text-muted small mt-2">Memuat struktur alur langkah...</div>
                    </div>

                    <div id="flowStepsContainer" style="display: none;">
                        {{-- Diisi secara dinamis oleh JavaScript --}}
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between align-items-center flex-wrap">
                    <button type="button" class="btn btn-sm btn-outline-danger" id="btnResetFlow">
                        <i class="fas fa-undo mr-1"></i> Reset ke Alur Standar
                    </button>
                    <div>
                        <button type="button" class="btn btn-sm btn-secondary mr-1" data-bs-dismiss="modal" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-info text-white font-weight-bold" id="btnSaveFlow">
                            <i class="fas fa-save mr-1"></i> Simpan Pengaturan Alur
                        </button>
                    </div>
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

    // Simpan posisi Tab saat reload
    $('a[data-toggle="tab"], a[data-bs-toggle="tab"]').on('shown.bs.tab shown', function (e) {
        localStorage.setItem('activeBotTab', $(e.target).attr('href'));
    });
    let activeTab = localStorage.getItem('activeBotTab');
    if (activeTab) {
        let tabEl = $(`a[href="${activeTab}"]`);
        if (tabEl.length) {
            tabEl.tab('show');
        }
    }

    // Sinkronkan Webhook
    $('#btnSyncWebhook').click(function(e) {
        e.preventDefault();
        let btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Mendaftarkan...');

        $.ajax({
            url: "{{ route('telegram-bot.sync-webhook', [], false) }}",
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            dataType: 'json',
            success: function(res) {
                Swal.fire('Berhasil!', res.message, 'success').then(() => location.reload());
            },
            error: function(xhr) {
                Swal.fire('Gagal!', xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan.', 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-sync mr-1"></i> Sinkronkan / Set Webhook');
            }
        });
    });

    // Hapus Webhook
    $('#btnDeleteWebhook').click(function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Hapus Webhook?',
            text: 'Bot Telegram tidak akan menerima pesan masuk jika webhook dihapus.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus Webhook',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('telegram-bot.delete-webhook', [], false) }}",
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    dataType: 'json',
                    success: function(res) {
                        Swal.fire('Terhapus!', res.message, 'success').then(() => location.reload());
                    },
                    error: function(xhr) {
                        Swal.fire('Gagal!', xhr.responseJSON ? xhr.responseJSON.message : 'Gagal menghapus.', 'error');
                    }
                });
            }
        });
    });

    // Form Test Kirim Pesan
    $('#formTestSend').submit(function(e) {
        e.preventDefault();
        let btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Mengirim...');

        $.ajax({
            url: "{{ route('telegram-bot.test-send', [], false) }}",
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                Swal.fire('Terkirim!', res.message, 'success');
            },
            error: function(xhr) {
                Swal.fire('Gagal!', xhr.responseJSON ? xhr.responseJSON.message : 'Pengiriman gagal.', 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-paper-plane mr-1"></i> Kirim Tes');
            }
        });
    });

    // Sinkronkan Perintah ke Telegram
    function triggerSyncCommands(btn) {
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyinkronkan ke Telegram...');

        $.ajax({
            url: "{{ route('telegram-bot.sync-commands', [], false) }}",
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            dataType: 'json',
            success: function(res) {
                Swal.fire({
                    icon: 'success',
                    title: 'Sinkronisasi Sukses!',
                    text: res.message,
                    timer: 2500,
                    showConfirmButton: false
                });
            },
            error: function(xhr) {
                Swal.fire('Gagal!', xhr.responseJSON ? xhr.responseJSON.message : 'Sinkronisasi gagal.', 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-sync-alt mr-1"></i> Sinkronkan Menu ke Telegram');
            }
        });
    }

    $('#btnSyncCommands, #btnSyncCommandsHeader').click(function(e) {
        e.preventDefault();
        triggerSyncCommands($(this));
    });

    // Buka Modal Tambah Perintah
    $('#btnCreateCommand').click(function(e) {
        e.preventDefault();
        $('#createCommandForm')[0].reset();
        showModal('createCommandModal');
    });

    // Submit Tambah Perintah
    $('#createCommandForm').submit(function(e) {
        e.preventDefault();
        let btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

        $.ajax({
            url: "{{ route('telegram-bot.commands.store', [], false) }}",
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                hideModal('createCommandModal');
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: res.message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => location.reload());
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
                btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan Perintah');
            }
        });
    });

    // Buka Modal Edit Perintah
    $(document).on('click', '.btn-edit-command', function(e) {
        e.preventDefault();
        let btn = $(this);
        let id = btn.data('id');
        let command = btn.data('command');
        let description = btn.data('description');
        let isSystem = btn.data('is-system') == '1';
        let response = btn.data('response') || '';
        let updateUrl = btn.data('update-url');

        $('#editCommandId').val(id);
        $('#editCommandForm').data('action-url', updateUrl);
        $('#editCommandInput').val(command);
        $('#editDescription').val(description);
        $('#editResponseText').val(response);

        if (isSystem) {
            $('#editCommandInput').prop('readonly', true);
            $('#systemCommandNotice').show();
            $('#editResponseGroup').hide();
            $('#editResponseText').prop('required', false);
        } else {
            $('#editCommandInput').prop('readonly', false);
            $('#systemCommandNotice').hide();
            $('#editResponseGroup').show();
            $('#editResponseText').prop('required', true);
        }

        showModal('editCommandModal');
    });

    // Submit Edit Perintah
    $('#editCommandForm').submit(function(e) {
        e.preventDefault();
        let url = $(this).data('action-url');
        let btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

        $.ajax({
            url: url,
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                hideModal('editCommandModal');
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: res.message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => location.reload());
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
                btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Perbarui Perintah');
            }
        });
    });

    // Toggle Aktif Perintah
    $(document).on('click', '.btn-toggle-command', function(e) {
        e.preventDefault();
        let btn = $(this);
        let url = btn.data('url');

        $.ajax({
            url: url,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            dataType: 'json',
            success: function(res) {
                if (res.is_active) {
                    btn.removeClass('btn-secondary').addClass('btn-success').text('Aktif');
                } else {
                    btn.removeClass('btn-success').addClass('btn-secondary').text('Nonaktif');
                }
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000
                });
                Toast.fire({
                    icon: 'success',
                    title: res.message
                });
            },
            error: function(xhr) {
                Swal.fire('Gagal!', xhr.responseJSON ? xhr.responseJSON.message : 'Gagal mengubah status.', 'error');
            }
        });
    });

    // Hapus Perintah Kustom
    $(document).on('click', '.btn-delete-command', function(e) {
        e.preventDefault();
        let url = $(this).data('url');
        let name = $(this).data('name');

        Swal.fire({
            title: 'Hapus Perintah?',
            html: `Apakah Anda yakin ingin menghapus perintah <strong>${name}</strong>?`,
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
                    data: { _token: '{{ csrf_token() }}' },
                    dataType: 'json',
                    success: function(res) {
                        Swal.fire('Terhapus!', res.message, 'success').then(() => location.reload());
                    },
                    error: function(xhr) {
                        Swal.fire('Gagal!', xhr.responseJSON ? xhr.responseJSON.message : 'Gagal menghapus.', 'error');
                    }
                });
            }
        });
    });

    // ==========================================
    // PENGATURAN ALUR PERCAKAPAN (FLOW EDITOR)
    // ==========================================
    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function renderFlowSteps(flow) {
        let container = $('#flowStepsContainer');
        container.empty();

        let icons = {
            category: 'fas fa-tags text-primary',
            amount: 'fas fa-money-bill-wave text-success',
            description: 'fas fa-comment-alt text-info',
            person: 'fas fa-user text-primary',
            store_nota: 'fas fa-store-alt text-warning',
            store: 'fas fa-store text-warning',
            nota: 'fas fa-receipt text-danger',
            photos: 'fas fa-camera text-primary',
            uraian: 'fas fa-pencil-alt text-info'
        };

        let stepIndex = 1;
        $.each(flow, function(key, step) {
            let isAmount = (key === 'amount');
            let isEnabled = step.is_enabled;
            let isRequired = step.is_required;

            let card = `
            <div class="card border shadow-none mb-3 flow-step-card" data-step-key="${key}" style="border-radius: 8px;">
                <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center flex-wrap" style="border-bottom: 1px solid #f0f0f0;">
                    <div>
                        <span class="badge badge-secondary mr-2 font-tabular">Langkah ${stepIndex}</span>
                        <i class="${icons[key] || 'fas fa-circle text-secondary'} mr-1"></i>
                        <span class="font-weight-bold text-dark">${step.title}</span>
                    </div>
                    <div class="d-flex align-items-center flex-wrap gap-3 mt-1 mt-sm-0">
                        <div class="form-check form-switch mr-3 mb-0">
                            <input class="form-check-input step-enabled-switch" type="checkbox" id="switch_enabled_${key}" ${isEnabled ? 'checked' : ''} ${isAmount ? 'disabled' : ''}>
                            <label class="form-check-label small font-weight-bold step-enabled-label" for="switch_enabled_${key}">
                                ${isEnabled ? '<span class="text-success"><i class="fas fa-check-circle mr-1"></i>Aktif</span>' : '<span class="text-muted"><i class="fas fa-ban mr-1"></i>Dilewati</span>'}
                            </label>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input step-required-switch" type="checkbox" id="switch_required_${key}" ${isRequired ? 'checked' : ''} ${isAmount ? 'disabled' : ''}>
                            <label class="form-check-label small step-required-label" for="switch_required_${key}">
                                ${isRequired ? '<span class="text-danger font-weight-bold"><i class="fas fa-asterisk mr-1"></i>Wajib Isi</span>' : '<span class="text-secondary"><i class="fas fa-forward mr-1"></i>Boleh /skip</span>'}
                            </label>
                        </div>
                    </div>
                </div>
                <div class="card-body p-3 bg-light step-body" style="${!isEnabled ? 'opacity: 0.55;' : ''}">
                    <div class="form-group mb-1">
                        <label class="small font-weight-bold text-secondary mb-1">Kalimat Pertanyaan / Instruksi Bot:</label>
                        <textarea class="form-control form-control-sm step-prompt-input font-monospace" rows="2" style="font-size: 0.8rem;" ${!isEnabled ? 'disabled' : ''}>${escapeHtml(step.prompt)}</textarea>
                    </div>
                    <small class="text-muted d-block mt-1">
                        <i class="fas fa-lightbulb text-warning mr-1"></i> ${step.help_text || step.description}
                    </small>
                </div>
            </div>`;

            container.append(card);
            stepIndex++;
        });

        $('#flowLoading').hide();
        $('#flowStepsContainer').fadeIn();
    }

    // Toggle switch handler di modal
    $(document).on('change', '.step-enabled-switch', function() {
        let isChecked = $(this).is(':checked');
        let card = $(this).closest('.flow-step-card');
        let label = card.find('.step-enabled-label');
        let textarea = card.find('.step-prompt-input');
        let body = card.find('.step-body');

        if (isChecked) {
            label.html('<span class="text-success"><i class="fas fa-check-circle mr-1"></i>Aktif</span>');
            textarea.prop('disabled', false);
            body.css('opacity', '1');
        } else {
            label.html('<span class="text-muted"><i class="fas fa-ban mr-1"></i>Dilewati</span>');
            textarea.prop('disabled', true);
            body.css('opacity', '0.55');
        }
    });

    $(document).on('change', '.step-required-switch', function() {
        let isChecked = $(this).is(':checked');
        let label = $(this).closest('.flow-step-card').find('.step-required-label');
        if (isChecked) {
            label.html('<span class="text-danger font-weight-bold"><i class="fas fa-asterisk mr-1"></i>Wajib Isi</span>');
        } else {
            label.html('<span class="text-secondary"><i class="fas fa-forward mr-1"></i>Boleh /skip</span>');
        }
    });

    // Buka Modal Atur Alur
    $(document).on('click', '.btn-flow-command', function(e) {
        e.preventDefault();
        let btn = $(this);
        let cmdId = btn.data('id');
        let commandName = btn.data('command');
        let flowUrl = btn.data('flow-url');
        let updateUrl = btn.data('update-url');
        let resetUrl = btn.data('reset-url');

        $('#flowCommandId').val(cmdId);
        $('#flowUpdateUrl').val(updateUrl);
        $('#flowResetUrl').val(resetUrl);
        $('#flowModalTitle').text('/' + commandName);

        $('#flowStepsContainer').hide().empty();
        $('#flowLoading').show();

        showModal('modalFlowEditor');

        $.ajax({
            url: flowUrl,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                renderFlowSteps(res.flow);
            },
            error: function(xhr) {
                hideModal('modalFlowEditor');
                Swal.fire('Gagal!', xhr.responseJSON ? xhr.responseJSON.message : 'Gagal memuat alur langkah.', 'error');
            }
        });
    });

    // Simpan Pengaturan Alur
    $('#formFlowEditor').submit(function(e) {
        e.preventDefault();
        let updateUrl = $('#flowUpdateUrl').val();
        let btn = $('#btnSaveFlow');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

        let stepsData = {};
        $('.flow-step-card').each(function() {
            let key = $(this).data('step-key');
            let isEnabled = $(this).find('.step-enabled-switch').is(':checked') ? 1 : 0;
            let isRequired = $(this).find('.step-required-switch').is(':checked') ? 1 : 0;
            let prompt = $(this).find('.step-prompt-input').val();

            stepsData[key] = {
                is_enabled: isEnabled,
                is_required: isRequired,
                prompt: prompt
            };
        });

        $.ajax({
            url: updateUrl,
            type: 'PUT',
            data: {
                _token: '{{ csrf_token() }}',
                steps: stepsData
            },
            dataType: 'json',
            success: function(res) {
                hideModal('modalFlowEditor');
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: res.message,
                    timer: 2000,
                    showConfirmButton: false
                });
            },
            error: function(xhr) {
                let msg = 'Gagal menyimpan alur langkah.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                Swal.fire('Gagal!', msg, 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan Pengaturan Alur');
            }
        });
    });

    // Reset ke Alur Standar Bawaan
    $('#btnResetFlow').click(function(e) {
        e.preventDefault();
        let resetUrl = $('#flowResetUrl').val();

        Swal.fire({
            title: 'Kembalikan ke Standar?',
            text: 'Semua kustomisasi langkah, status aktif, dan kalimat pertanyaan akan dikembalikan ke pengaturan awal sistem.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Reset ke Standar',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                let btn = $('#btnResetFlow');
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Mereset...');

                $.ajax({
                    url: resetUrl,
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    dataType: 'json',
                    success: function(res) {
                        renderFlowSteps(res.flow);
                        Swal.fire({
                            icon: 'success',
                            title: 'Direset!',
                            text: res.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    },
                    error: function(xhr) {
                        Swal.fire('Gagal!', xhr.responseJSON ? xhr.responseJSON.message : 'Gagal mereset.', 'error');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html('<i class="fas fa-undo mr-1"></i> Reset ke Alur Standar');
                    }
                });
            }
        });
    });
});
</script>
@stop

