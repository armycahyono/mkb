<?php

namespace App\Http\Controllers;

use App\Helpers\FiscalYearHelper;
use App\Models\BarangDokumentasi;
use App\Models\BarangDokumentasiFoto;
use App\Models\Guru;
use App\Models\KasCategory;
use App\Models\KasTunai;
use App\Models\TelegramCommand;
use App\Models\TelegramConversation;
use App\Models\User;
use App\Services\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TelegramWebhookController extends Controller
{
    public function __construct(
        protected TelegramBotService $bot
    ) {}

    /**
     * Entry point webhook dari Telegram.
     */
    public function handle(Request $request): JsonResponse
    {
        $update = $request->all();

        try {
            if (isset($update['callback_query'])) {
                $this->handleCallbackQuery($update['callback_query']);
            } elseif (isset($update['message'])) {
                $this->handleMessage($update['message']);
            }
        } catch (\Throwable $e) {
            Log::error('TelegramWebhook error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Menangani callback query (tombol inline keyboard diklik).
     */
    protected function handleCallbackQuery(array $callbackQuery): void
    {
        $callbackId = $callbackQuery['id'];
        $chatId = $callbackQuery['message']['chat']['id'] ?? $callbackQuery['from']['id'];
        $messageId = $callbackQuery['message']['message_id'] ?? null;
        $data = $callbackQuery['data'] ?? '';

        $this->bot->answerCallbackQuery($callbackId);

        $user = User::where('telegram_chat_id', (string) $chatId)->first();
        if (! $user) {
            $this->bot->sendMessage($chatId, '🔒 Silakan hubungkan akun MKB Anda terlebih dahulu dengan mengetik /start.');

            return;
        }

        $conversation = TelegramConversation::where('chat_id', (string) $chatId)->first();

        // Callback untuk fitur Kasbon
        if (str_starts_with($data, 'kasbon_page:')) {
            if (! $user->canBotViewSaldo()) {
                $this->bot->sendMessage($chatId, "⛔ <b>Akses Dibatasi:</b> Akun Anda ({$user->role_label}) tidak memiliki akses ke rincian kasbon.");

                return;
            }
            $page = (int) str_replace('kasbon_page:', '', $data);
            $this->sendKasbonInfo($chatId, max(1, $page), $messageId);

            return;
        }

        if (str_starts_with($data, 'kasbon_detail:')) {
            if (! $user->canBotViewSaldo()) {
                $this->bot->sendMessage($chatId, "⛔ <b>Akses Dibatasi:</b> Akun Anda ({$user->role_label}) tidak memiliki akses ke rincian kasbon.");

                return;
            }
            $kasbonId = (int) str_replace('kasbon_detail:', '', $data);
            $this->sendKasbonDetail($chatId, $kasbonId, $messageId);

            return;
        }

        if ($data === 'kasbon_saldo') {
            if (! $user->canBotViewSaldo()) {
                $this->bot->sendMessage($chatId, "⛔ <b>Akses Dibatasi:</b> Akun Anda ({$user->role_label}) tidak memiliki akses ke saldo kas.");

                return;
            }
            $this->sendSaldoInfo($chatId, $messageId);

            return;
        }

        // 0. Redirect langsung dari /transaksi → /pengeluaran atau /pemasukan
        if (str_starts_with($data, 'trx_direct:')) {
            $type = str_replace('trx_direct:', '', $data);
            if (in_array($type, ['keluar', 'masuk'])) {
                $this->startDirectTransaksiConversation((string) $chatId, $user, $type);
            }

            return;
        }

        // 1. Pemilihan Jenis Transaksi (dari /transaksi lama)
        if (str_starts_with($data, 'trx_type:')) {
            if ($user->canBotFotoOnly()) {
                $this->bot->sendMessage($chatId, "⛔ <b>Akses Dibatasi:</b> Akun Anda ({$user->role_label}) hanya dapat mengakses dokumentasi foto (/foto).");

                return;
            }

            if ($user->isKepalaSekolah()) {
                $this->bot->sendMessage($chatId, '⛔ <b>Akses Khusus Bendahara:</b> Pencatatan transaksi baru melalui bot dilakukan oleh Bendahara. Anda dapat memantau saldo dengan /saldo atau bantuan /help.');

                return;
            }

            $typeCode = str_replace('trx_type:', '', $data);

            $types = [
                'keluar_bosp' => ['source' => 'BOSP', 'type' => 'keluar', 'label' => 'Pengeluaran BOSP'],
                'masuk_bosp' => ['source' => 'BOSP', 'type' => 'masuk', 'label' => 'Penerimaan BOSP'],
            ];

            if (isset($types[$typeCode])) {
                $selected = $types[$typeCode];

                if (! $conversation) {
                    $conversation = TelegramConversation::create([
                        'chat_id' => (string) $chatId,
                        'user_id' => $user->id,
                        'command' => 'transaksi',
                    ]);
                }

                $draft = $conversation->draft_data ?: [];
                $draft['funding_source'] = $selected['source'];
                $draft['type'] = $selected['type'];
                $draft['type_label'] = $selected['label'];

                $draft['fiscal_year'] = $draft['fiscal_year'] ?? (int) date('Y');
                $conversation->update([
                    'command' => 'transaksi',
                    'draft_data' => $draft,
                ]);
                $this->promptCategorySelection($chatId, $conversation, $draft, $messageId);
            }

            return;
        }

        // 1b. Pemilihan Tahun Anggaran Belanja
        if (str_starts_with($data, 'trx_year:')) {
            if ($user->canBotFotoOnly()) {
                $this->bot->sendMessage($chatId, "⛔ <b>Akses Dibatasi:</b> Akun Anda ({$user->role_label}) hanya dapat mengakses dokumentasi foto (/foto).");

                return;
            }

            if ($user->isKepalaSekolah()) {
                $this->bot->sendMessage($chatId, '⛔ <b>Akses Khusus Bendahara:</b> Pencatatan transaksi baru melalui bot dilakukan oleh Bendahara.');

                return;
            }

            $year = (int) str_replace('trx_year:', '', $data);
            if (! $conversation) {
                $conversation = TelegramConversation::where('chat_id', (string) $chatId)->first();
            }

            if ($conversation) {
                $draft = $conversation->draft_data ?: [];
                $draft['fiscal_year'] = $year;
                $conversation->update(['draft_data' => $draft]);

                $this->promptCategorySelection($chatId, $conversation, $draft, $messageId);
            }

            return;
        }

        // 2. Pemilihan Kategori Transaksi
        if (str_starts_with($data, 'trx_cat:')) {
            if ($user->canBotFotoOnly()) {
                $this->bot->sendMessage($chatId, "⛔ <b>Akses Dibatasi:</b> Akun Anda ({$user->role_label}) hanya dapat mengakses dokumentasi foto (/foto).");

                return;
            }

            if ($user->isKepalaSekolah()) {
                $this->bot->sendMessage($chatId, '⛔ <b>Akses Khusus Bendahara:</b> Pencatatan transaksi baru melalui bot dilakukan oleh Bendahara. Anda dapat memantau saldo dengan /saldo atau bantuan /help.');

                return;
            }

            $catCode = str_replace('trx_cat:', '', $data);
            $categories = KasTunai::categories();
            $catLabel = $categories[$catCode]['label'] ?? ucfirst(str_replace('_', ' ', $catCode));

            if (! $conversation) {
                $conversation = TelegramConversation::where('chat_id', (string) $chatId)->first();
            }

            if ($conversation) {
                $draft = $conversation->draft_data ?: [];
                $draft['category'] = $catCode;
                $draft['category_label'] = $catLabel;

                // SPECIAL CASE: Kembalian Sisa Kasbon (sisa_panjar)
                if ($catCode === 'sisa_panjar') {
                    $fundingSource = $draft['funding_source'] ?? 'BOSP';
                    $pendingPanjars = KasTunai::where('category', 'panjar_kerja')
                        ->whereIn('panjar_status', ['pending', 'partial'])
                        ->where('funding_source', $fundingSource)
                        ->with(['guru', 'childKass'])
                        ->latest('transaction_date')
                        ->limit(8)
                        ->get();

                    if ($pendingPanjars->isEmpty()) {
                        $this->bot->sendMessage(
                            $chatId,
                            "⚠️ <b>Tidak ada Kasbon (Panjar) yang berstatus Terbuka!</b>\nSaat ini semua kasbon telah diselesaikan / lunas.\n\nKetik /pengeluaran untuk memilih kategori lain."
                        );

                        return;
                    }

                    $conversation->update([
                        'step' => 'waiting_parent_panjar',
                        'draft_data' => $draft,
                    ]);

                    $keyboard = [];
                    foreach ($pendingPanjars as $p) {
                        $guruName = $p->guru?->full_name_with_title ?? $p->person_name ?? 'Tanpa Nama';
                        $remaining = $p->remaining_panjar_amount;
                        $remainingFmt = number_format($remaining, 0, ',', '.');
                        $statusBadge = ($p->panjar_status === 'partial') ? ' [Sebagian]' : '';
                        $btnText = "#{$p->id} - {$guruName} (Sisa: Rp {$remainingFmt}){$statusBadge}";
                        $keyboard[] = [
                            ['text' => $btnText, 'callback_data' => "trx_parent:{$p->id}"],
                        ];
                    }
                    $keyboard[] = [
                        ['text' => '❌ Batalkan', 'callback_data' => 'confirm:no'],
                    ];

                    $promptText = "🤝 <b>PILIH KASBON YANG DIKEMBALIKAN:</b>\nSilakan pilih kasbon yang akan dicicil / diselesaikan sisanya:";
                    if ($messageId) {
                        $this->bot->editMessageText($chatId, $messageId, $promptText, $keyboard);
                    } else {
                        $this->bot->sendMessage($chatId, $promptText, $keyboard);
                    }

                    return;
                }

                // SPECIAL CASE: Pengembalian Dana Talangan (bayar_talangan)
                if ($catCode === 'bayar_talangan') {
                    $fundingSource = $draft['funding_source'] ?? 'BOSP';
                    $danaTalangans = KasTunai::where('category', 'dana_talangan_masuk')
                        ->where('transaction_type', 'masuk')
                        ->where('funding_source', $fundingSource)
                        ->with('guru')
                        ->latest('transaction_date')
                        ->limit(8)
                        ->get();

                    if ($danaTalangans->isEmpty()) {
                        $this->bot->sendMessage(
                            $chatId,
                            "⚠️ <b>Tidak ada data Penerimaan Dana Talangan yang tercatat!</b>\n\nKetik /pemasukan untuk memilih kategori lain."
                        );

                        return;
                    }

                    $conversation->update([
                        'step' => 'waiting_parent_talangan',
                        'draft_data' => $draft,
                    ]);

                    $keyboard = [];
                    foreach ($danaTalangans as $dt) {
                        $guruName = $dt->guru?->full_name_with_title ?? $dt->person_name ?? 'Tanpa Nama';
                        $amountFmt = number_format($dt->amount, 0, ',', '.');
                        $btnText = "#{$dt->id} - Rp {$amountFmt} ({$guruName})";
                        $keyboard[] = [
                            ['text' => $btnText, 'callback_data' => "trx_parent:{$dt->id}"],
                        ];
                    }
                    $keyboard[] = [
                        ['text' => '❌ Batalkan', 'callback_data' => 'confirm:no'],
                    ];

                    $promptText = "💳 <b>PILIH DANA TALANGAN YANG DILUNASI:</b>\nSilakan pilih penerimaan dana talangan awal yang ingin dikembalikan:";
                    if ($messageId) {
                        $this->bot->editMessageText($chatId, $messageId, $promptText, $keyboard);
                    } else {
                        $this->bot->sendMessage($chatId, $promptText, $keyboard);
                    }

                    return;
                }

                $conversation->update([
                    'step' => 'waiting_amount',
                    'draft_data' => $draft,
                ]);

                $flow = $this->getTransaksiFlow($draft['type'] ?? 'keluar');
                $amountPrompt = $flow['amount']['prompt'] ?? "Masukkan <b>NOMINAL</b> transaksi (Rp):\n<i>(Contoh: 750000 atau bisa digabung: 100.000, Pak Army)</i>";
                $typeLabel = $draft['type_label'] ?? 'Transaksi Kas';
                $msgText = "✅ <b>Jenis:</b> {$typeLabel}\n📂 <b>Kategori:</b> {$catLabel}\n\n{$amountPrompt}";

                if ($messageId) {
                    $this->bot->editMessageText($chatId, $messageId, $msgText);
                } else {
                    $this->bot->sendMessage($chatId, $msgText);
                }
            }

            return;
        }

        // 2b. Pemilihan Transaksi Induk (Parent Kasbon / Parent Talangan)
        if (str_starts_with($data, 'trx_parent:')) {
            if ($user->canBotFotoOnly()) {
                $this->bot->sendMessage($chatId, "⛔ <b>Akses Dibatasi:</b> Akun Anda ({$user->role_label}) hanya dapat mengakses dokumentasi foto (/foto).");

                return;
            }

            if ($user->isKepalaSekolah()) {
                $this->bot->sendMessage($chatId, '⛔ <b>Akses Khusus Bendahara:</b> Pencatatan transaksi baru melalui bot dilakukan oleh Bendahara. Anda dapat memantau saldo dengan /saldo atau bantuan /help.');

                return;
            }

            $parentId = (int) str_replace('trx_parent:', '', $data);
            $parentKas = KasTunai::with('guru')->find($parentId);

            if (! $conversation) {
                $conversation = TelegramConversation::where('chat_id', (string) $chatId)->first();
            }

            if ($conversation && $parentKas) {
                $draft = $conversation->draft_data ?: [];
                $draft['parent_kas_id'] = $parentKas->id;
                $draft['guru_id'] = $parentKas->guru_id;
                $draft['person'] = $parentKas->guru?->full_name_with_title ?? $parentKas->person_name;

                if (($draft['category'] ?? '') === 'sisa_panjar') {
                    $draft['description'] = "Kembalian sisa kasbon #{$parentKas->id}".($parentKas->description ? " ({$parentKas->description})" : '');
                    $remaining = $parentKas->remaining_panjar_amount;
                    $draft['parent_remaining_amount'] = $remaining;
                    $promptAmount = "Masukkan <b>NOMINAL SISA KASBON</b> yang dikembalikan (Rp):\n<i>• Sisa tanggungan saat ini: <b>Rp ".number_format($remaining, 0, ',', '.')."</b>\n• Total kasbon awal: Rp ".number_format($parentKas->amount, 0, ',', '.').'</i>';
                } else {
                    $draft['description'] = "Pengembalian dana talangan #{$parentKas->id}".($parentKas->description ? " ({$parentKas->description})" : '');
                    $promptAmount = "Masukkan <b>NOMINAL PENGEMBALIAN DANA TALANGAN</b> (Rp):\n<i>(Nominal awal talangan: Rp ".number_format($parentKas->amount, 0, ',', '.').')</i>';
                }

                $conversation->update([
                    'step' => 'waiting_amount',
                    'draft_data' => $draft,
                ]);

                $msgText = "✅ <b>Tertaut Transaksi Induk:</b> #{$parentKas->id}\n👤 <b>Penanggung Jawab:</b> {$draft['person']}\n📝 <b>Uraian:</b> {$draft['description']}\n\n{$promptAmount}";

                if ($messageId) {
                    $this->bot->editMessageText($chatId, $messageId, $msgText);
                } else {
                    $this->bot->sendMessage($chatId, $msgText);
                }
            }

            return;
        }

        // 2. Konfirmasi Akhir (Simpan / Batal)
        if ($data === 'confirm:yes' && $conversation && $conversation->step === 'waiting_confirmation') {
            $this->finalizeTransaction($chatId, $conversation, $user, $messageId);

            return;
        }

        if ($data === 'confirm:no' && $conversation) {
            $conversation->delete();
            $cancelText = '❌ <b>Transaksi Dibatalkan.</b> Ketik /pengeluaran atau /pemasukan untuk membuat transaksi baru.';
            if ($messageId) {
                $this->bot->editMessageText($chatId, $messageId, $cancelText);
            } else {
                $this->bot->sendMessage($chatId, $cancelText);
            }

            return;
        }

        // Konfirmasi untuk Alur Foto saja
        if ($data === 'confirm_foto:yes' && $conversation && $conversation->step === 'waiting_confirmation_foto') {
            $this->finalizeFotoDokumentasi($chatId, $conversation, $user, $messageId);

            return;
        }

        // Pemilihan Tahun Anggaran untuk Alur Foto
        if (str_starts_with($data, 'foto_year:')) {
            $year = (int) str_replace('foto_year:', '', $data);
            if (! $conversation) {
                $conversation = TelegramConversation::where('chat_id', (string) $chatId)->first();
            }

            if ($conversation && $conversation->command === 'foto') {
                $draft = $conversation->draft_data ?: [];
                $draft['fiscal_year'] = $year;
                $conversation->update([
                    'step' => 'waiting_photo_direct',
                    'draft_data' => $draft,
                ]);

                $promptText = "📸 <b>DOKUMENTASI FOTO BARANG BOSP ({$year})</b>\n━━━━━━━━━━━━━━━━━━\nSilakan langsung <b>kirim 1 s/d 3 foto</b> fisik barang belanja atau nota dari kamera HP Anda:";
                if ($messageId) {
                    $this->bot->editMessageText($chatId, $messageId, $promptText);
                } else {
                    $this->bot->sendMessage($chatId, $promptText);
                }
            }

            return;
        }
    }

    /**
     * Menangani pesan teks atau foto biasa dari pengguna.
     */
    protected function handleMessage(array $message): void
    {
        $chatId = (string) ($message['chat']['id'] ?? '');
        $username = $message['from']['username'] ?? null;
        $text = trim((string) ($message['text'] ?? ($message['caption'] ?? '')));
        $hasPhoto = ! empty($message['photo']);

        $user = User::where('telegram_chat_id', $chatId)->first();
        $conversation = TelegramConversation::where('chat_id', $chatId)->first();

        // 1. Perintah Global Batal
        if ($text === '/batal') {
            if ($conversation) {
                $conversation->delete();
            }
            if ($user && $user->canBotFotoOnly()) {
                $this->bot->sendMessage($chatId, "🔄 Sesi telah dibatalkan.\nKetik /foto untuk upload foto barang, atau /help untuk bantuan.");
            } elseif ($user && $user->isKepalaSekolah()) {
                $this->bot->sendMessage($chatId, "🔄 Sesi telah dibatalkan.\nKetik /saldo untuk cek saldo, atau /help untuk bantuan.");
            } else {
                $this->bot->sendMessage($chatId, "🔄 Sesi telah dibatalkan.\nKetik /pengeluaran atau /pemasukan untuk mencatat transaksi baru, atau /help untuk bantuan.");
            }

            return;
        }

        // 2. Jika Akun Belum Terhubung (Pairing / Login)
        if (! $user) {
            $this->handleUnauthenticatedUser($chatId, $username, $text, $conversation);

            return;
        }

        // 3. Perintah Global User Terdaftar
        if ($text === '/start' || $text === '/help') {
            $this->sendHelpMessage($chatId, $user);

            return;
        }

        if ($text === '/logout') {
            $user->update(['telegram_chat_id' => null, 'telegram_username' => null]);
            if ($conversation) {
                $conversation->delete();
            }
            $this->bot->sendMessage($chatId, '🚪 <b>Akun Berhasil Diputus.</b> Anda telah keluar dari sistem MKB. Ketik /start jika ingin menghubungkan akun kembali.');

            return;
        }

        if ($text === '/saldo') {
            if (! $user->canBotViewSaldo()) {
                $this->bot->sendMessage(
                    $chatId,
                    "⛔ <b>Akses Dibatasi</b>\nAkun Anda ({$user->role_label}) tidak memiliki akses ke kas operasional/saldo.\n\nKetik <b>/foto</b> untuk upload dokumentasi foto barang fisik, atau <b>/help</b> untuk melihat bantuan."
                );

                return;
            }

            $this->sendSaldoInfo($chatId);

            return;
        }

        if (preg_match('/^\/kasbon(?:\s+(?:#?kt-?)?(\d+))?$/i', $text, $matches)) {
            if (! $user->canBotViewSaldo()) {
                $this->bot->sendMessage(
                    $chatId,
                    "⛔ <b>Akses Dibatasi</b>\nAkun Anda ({$user->role_label}) tidak memiliki akses ke rincian kas operasional/kasbon.\n\nKetik <b>/foto</b> untuk upload dokumentasi foto barang fisik, atau <b>/help</b> untuk melihat bantuan."
                );

                return;
            }

            $kasbonId = ! empty($matches[1]) ? (int) $matches[1] : null;
            if ($kasbonId) {
                $this->sendKasbonDetail($chatId, $kasbonId);
            } else {
                $this->sendKasbonInfo($chatId);
            }

            return;
        }

        if ($text === '/transaksi') {
            if ($user->canBotFotoOnly()) {
                $this->bot->sendMessage(
                    $chatId,
                    "⛔ <b>Akses Dibatasi</b>\nAkun Anda terdaftar sebagai <b>{$user->role_label}</b>.\n\nKetik <b>/foto</b> untuk upload dokumentasi foto barang fisik, atau <b>/help</b> untuk melihat bantuan."
                );

                return;
            }

            if ($user->isKepalaSekolah()) {
                $this->bot->sendMessage(
                    $chatId,
                    "⛔ <b>Akses Khusus Bendahara</b>\nAkun Anda terdaftar sebagai <b>Kepala Sekolah</b> (pengawasan & pemeriksaan).\n\nKetik <b>/saldo</b> untuk memeriksa saldo kas tunai, atau <b>/help</b> untuk melihat bantuan."
                );

                return;
            }

            $keyboard = [
                [
                    ['text' => '🔴 Pengeluaran BOSP', 'callback_data' => 'trx_direct:keluar'],
                ],
                [
                    ['text' => '🟢 Pemasukan BOSP', 'callback_data' => 'trx_direct:masuk'],
                ],
            ];

            $this->bot->sendMessage(
                $chatId,
                "📝 <b>PENCATATAN TRANSAKSI KAS BOSP</b>\n━━━━━━━━━━━━━━━━━━\nSilakan pilih jenis transaksi:",
                $keyboard
            );

            return;
        }

        if ($text === '/pengeluaran' || $text === '/pengeluaran_bosp') {
            if ($user->canBotFotoOnly()) {
                $this->bot->sendMessage(
                    $chatId,
                    "⛔ <b>Akses Dibatasi</b>\nAkun Anda terdaftar sebagai <b>{$user->role_label}</b>.\n\nKetik <b>/foto</b> untuk upload dokumentasi foto barang fisik, atau <b>/help</b> untuk melihat bantuan."
                );

                return;
            }

            if ($user->isKepalaSekolah()) {
                $this->bot->sendMessage(
                    $chatId,
                    "⛔ <b>Akses Khusus Bendahara</b>\nAkun Anda terdaftar sebagai <b>Kepala Sekolah</b> (pengawasan & pemeriksaan).\n\nKetik <b>/saldo</b> untuk memeriksa saldo kas tunai, atau <b>/help</b> untuk melihat bantuan."
                );

                return;
            }

            $this->startDirectTransaksiConversation($chatId, $user, 'keluar');

            return;
        }

        if ($text === '/pemasukan' || $text === '/pemasukan_bosp') {
            if ($user->canBotFotoOnly()) {
                $this->bot->sendMessage(
                    $chatId,
                    "⛔ <b>Akses Dibatasi</b>\nAkun Anda terdaftar sebagai <b>{$user->role_label}</b>.\n\nKetik <b>/foto</b> untuk upload dokumentasi foto barang fisik, atau <b>/help</b> untuk melihat bantuan."
                );

                return;
            }

            if ($user->isKepalaSekolah()) {
                $this->bot->sendMessage(
                    $chatId,
                    "⛔ <b>Akses Khusus Bendahara</b>\nAkun Anda terdaftar sebagai <b>Kepala Sekolah</b> (pengawasan & pemeriksaan).\n\nKetik <b>/saldo</b> untuk memeriksa saldo kas tunai, atau <b>/help</b> untuk melihat bantuan."
                );

                return;
            }

            $this->startDirectTransaksiConversation($chatId, $user, 'masuk');

            return;
        }

        if ($text === '/foto') {
            $this->startFotoConversation($chatId, $user);

            return;
        }

        // 4. Jika Sedang Berada di Sesi Percakapan Tertentu (Wizard)
        if ($conversation) {
            if ($conversation->command === 'transaksi') {
                $this->processTransaksiStep($chatId, $text, $hasPhoto, $message, $conversation, $user);

                return;
            }

            if ($conversation->command === 'foto') {
                $this->processFotoStep($chatId, $text, $hasPhoto, $message, $conversation, $user);

                return;
            }
        }

        // 5. Perintah Kustom (Auto-Reply / FAQ / Info Sekolah)
        if ($text && str_starts_with($text, '/') && Schema::hasTable('telegram_commands')) {
            $cmdKey = strtolower(ltrim(explode(' ', $text)[0], '/'));
            $customCmd = TelegramCommand::active()->where('command', $cmdKey)->where('response_type', 'text')->first();
            if ($customCmd && $customCmd->response_text) {
                $this->bot->sendMessage($chatId, $customCmd->response_text);

                return;
            }
        }

        // Pesan Default jika di luar alur
        if ($user->canBotFotoOnly()) {
            $this->bot->sendMessage($chatId, "Halo Bapak/Ibu <b>{$user->name}</b> ({$user->role_label})!\nKetik <b>/foto</b> untuk mengunggah dokumentasi foto barang belanja, atau <b>/help</b> untuk bantuan.");
        } elseif ($user->isKepalaSekolah()) {
            $this->bot->sendMessage($chatId, "Halo Bapak/Ibu <b>{$user->name}</b> (Kepala Sekolah)!\nKetik <b>/saldo</b> untuk melihat informasi saldo kas tunai, atau <b>/help</b> untuk bantuan.");
        } else {
            $this->bot->sendMessage($chatId, "Halo Bapak/Ibu <b>{$user->name}</b> ({$user->role_label})!\nKetik <b>/pengeluaran</b> atau <b>/pemasukan</b> untuk mencatat kas, atau <b>/help</b> untuk melihat menu perintah.");
        }
    }

    /**
     * Menangani proses pairing/login untuk akun yang belum terdaftar.
     */
    protected function handleUnauthenticatedUser(string $chatId, ?string $username, string $text, ?TelegramConversation $conversation): void
    {
        if ($conversation && $conversation->command === 'login') {
            if ($conversation->step === 'waiting_email' || $conversation->step === 'waiting_username') {
                $candidateUser = User::where('username', $text)->orWhere('email', $text)->first();
                if (! $candidateUser) {
                    $this->bot->sendMessage($chatId, "⚠️ Akun dengan Username atau Email <code>{$text}</code> tidak ditemukan di database MKB.\nSilakan masukkan Username yang valid, atau ketik /batal:");

                    return;
                }

                $conversation->update([
                    'step' => 'waiting_password',
                    'draft_data' => ['login' => $text, 'target_user_id' => $candidateUser->id],
                ]);

                $this->bot->sendMessage($chatId, "🔐 Akun ditemukan: <b>{$candidateUser->name}</b>.\nSilakan masukkan <b>Password</b> akun MKB Anda:");

                return;
            }

            if ($conversation->step === 'waiting_password') {
                $draft = $conversation->draft_data ?: [];
                $targetUser = isset($draft['target_user_id']) ? User::find($draft['target_user_id']) : null;

                if ($targetUser && Hash::check($text, $targetUser->password)) {
                    $targetUser->update([
                        'telegram_chat_id' => $chatId,
                        'telegram_username' => $username,
                    ]);

                    $conversation->delete();

                    $welcomeMsg = "🎉 <b>Akun Berhasil Terhubung!</b>\nHalo <b>{$targetUser->name}</b>, akun Telegram Anda kini resmi terhubung sebagai <b>{$targetUser->role_label}</b> MKB SMK PGRI KRAS.\n\n";
                    if ($targetUser->canBotFotoOnly()) {
                        $welcomeMsg .= 'Ketik <b>/foto</b> untuk mendokumentasikan fisik barang belanja, atau <b>/help</b> untuk bantuan.';
                    } elseif ($targetUser->isKepalaSekolah()) {
                        $welcomeMsg .= 'Ketik <b>/saldo</b> untuk memantau saldo kas tunai, atau <b>/help</b> untuk bantuan.';
                    } else {
                        $welcomeMsg .= 'Ketik <b>/pengeluaran</b> atau <b>/pemasukan</b> untuk mencatat transaksi kas baru, atau <b>/help</b> untuk bantuan.';
                    }

                    $this->bot->sendMessage($chatId, $welcomeMsg);

                    return;
                }

                $this->bot->sendMessage($chatId, '❌ <b>Password salah!</b> Silakan ketik kembali password akun Anda, atau ketik /batal untuk membatalkan.');

                return;
            }
        }

        // Jika user mengetik perintah awal
        TelegramConversation::updateOrCreate(
            ['chat_id' => $chatId],
            ['command' => 'login', 'step' => 'waiting_email', 'draft_data' => []]
        );

        $this->bot->sendMessage(
            $chatId,
            "👋 <b>Selamat Datang di Bot MKB SMK PGRI KRAS!</b>\n\nUntuk menjaga keamanan pembukuan sekolah, silakan hubungkan akun MKB Anda terlebih dahulu.\n\nMasukkan <b>Username</b> (atau Email) akun MKB Anda:"
        );
    }

    /**
     * Memulai percakapan langsung pengeluaran atau pemasukan (/pengeluaran atau /pemasukan).
     */
    protected function startDirectTransaksiConversation(string $chatId, User $user, string $type): void
    {
        $typeLabel = ($type === 'keluar') ? 'Pengeluaran BOSP' : 'Pemasukan BOSP';
        $conversation = TelegramConversation::updateOrCreate(
            ['chat_id' => $chatId],
            [
                'user_id' => $user->id,
                'command' => 'transaksi',
                'step' => 'waiting_year',
                'draft_data' => [
                    'funding_source' => 'BOSP',
                    'type' => $type,
                    'type_label' => $typeLabel,
                    'photos' => [],
                ],
            ]
        );
        // Transaksi selalu menggunakan tahun berjalan (pilihan tahun hanya untuk /foto)
        $draft = $conversation->draft_data;
        $draft['fiscal_year'] = (int) date('Y');
        $conversation->update(['draft_data' => $draft]);
        $this->promptCategorySelection($chatId, $conversation, $draft);
    }

    /**
     * Tampilkan pilihan tahun anggaran belanja.
     */
    protected function promptYearSelection(string|int $chatId, array $draft, ?int $messageId = null): void
    {
        $currentYear = (int) date('Y');
        $years = collect([$currentYear, $currentYear - 1, $currentYear - 2]);

        try {
            if (class_exists(FiscalYearHelper::class)) {
                $fromHelper = FiscalYearHelper::getAvailableYears()
                    ->filter(fn ($y) => $y <= $currentYear + 1 && $y >= $currentYear - 2)
                    ->unique()
                    ->sortDesc()
                    ->values();
                if ($fromHelper->isNotEmpty()) {
                    $years = $fromHelper;
                }
            }
        } catch (\Throwable) {
            // fallback
        }

        $keyboard = [];
        $row = [];
        foreach ($years as $yr) {
            $yr = (int) $yr;
            $label = ($yr === $currentYear) ? "📅 {$yr} (Tahun Ini)" : "📅 {$yr}";
            $row[] = ['text' => $label, 'callback_data' => "trx_year:{$yr}"];
            if (count($row) === 2) {
                $keyboard[] = $row;
                $row = [];
            }
        }
        if (! empty($row)) {
            $keyboard[] = $row;
        }
        $keyboard[] = [
            ['text' => '❌ Batalkan', 'callback_data' => 'confirm:no'],
        ];

        $flow = $this->getTransaksiFlow($draft['type'] ?? 'transaksi');
        $typeLabel = $draft['type_label'] ?? 'Transaksi Kas';
        $defaultPrompt = "📅 <b>PILIH TAHUN ANGGARAN BELANJA:</b>\n"
            ."Silakan pilih tahun anggaran pembukuan untuk <b>{$typeLabel}</b>:\n\n"
            .'<i>(Transaksi kas dan dokumen foto akan ditempatkan pada tahun anggaran yang Anda pilih)</i>';
        $promptText = $flow['year']['prompt'] ?? $defaultPrompt;

        if ($messageId) {
            $this->bot->editMessageText($chatId, $messageId, $promptText, $keyboard);
        } else {
            $this->bot->sendMessage($chatId, $promptText, $keyboard);
        }
    }

    /**
     * Tampilkan pilihan kategori transaksi kas.
     */
    protected function promptCategorySelection(string|int $chatId, TelegramConversation $conversation, array $draft, ?int $messageId = null): void
    {
        $flow = $this->getTransaksiFlow($draft['type'] ?? 'transaksi');
        $isCatEnabled = $flow['category']['is_enabled'] ?? true;
        $type = $draft['type'] ?? 'keluar';
        $typeLabel = $draft['type_label'] ?? 'Transaksi Kas';
        $year = $draft['fiscal_year'] ?? date('Y');

        if ($isCatEnabled) {
            $conversation->update([
                'command' => 'transaksi',
                'step' => 'waiting_category',
                'draft_data' => $draft,
            ]);

            $activeCats = collect();
            try {
                $activeCats = KasCategory::where('type', $type)
                    ->where('is_active', true)
                    ->ordered()
                    ->get();
            } catch (\Throwable) {
                $activeCats = collect();
            }

            if ($activeCats->isEmpty()) {
                foreach (KasTunai::categories() as $code => $cat) {
                    if ($cat['type'] === $type) {
                        $activeCats->push((object) [
                            'code' => $code,
                            'name' => $cat['label'],
                            'type' => $cat['type'],
                            'icon' => $cat['icon'] ?? ($cat['type'] === 'masuk' ? '📥' : '🛍️'),
                        ]);
                    }
                }
            }

            $catKeyboard = [];
            foreach ($activeCats as $cat) {
                $icon = $cat->icon ?: ($cat->type === 'masuk' ? '📥' : '🛍️');
                $catKeyboard[] = [
                    ['text' => "{$icon} {$cat->name}", 'callback_data' => "trx_cat:{$cat->code}"],
                ];
            }
            $catKeyboard[] = [
                ['text' => '❌ Batalkan', 'callback_data' => 'confirm:no'],
            ];

            if ($type === 'keluar') {
                $defaultPrompt = "📂 <b>PILIH KATEGORI PENGELUARAN:</b>\nSilakan klik salah satu kategori transaksi di bawah:";
            } else {
                $defaultPrompt = "📂 <b>PILIH KATEGORI PENERIMAAN:</b>\nSilakan klik salah satu kategori transaksi di bawah:";
            }
            $promptText = $flow['category']['prompt'] ?? $defaultPrompt;
            $msgText = "✅ <b>Jenis:</b> {$typeLabel}\n📅 <b>Tahun Anggaran:</b> {$year}\n\n{$promptText}";

            if ($messageId) {
                $this->bot->editMessageText($chatId, $messageId, $msgText, $catKeyboard);
            } else {
                $this->bot->sendMessage($chatId, $msgText, $catKeyboard);
            }
        } else {
            $defaultCat = ($type === 'keluar') ? 'belanja_tunai' : 'lainnya_masuk';
            $categories = KasTunai::categories();
            $catLabel = $categories[$defaultCat]['label'] ?? ucfirst(str_replace('_', ' ', $defaultCat));

            $draft['category'] = $defaultCat;
            $draft['category_label'] = $catLabel;

            $conversation->update([
                'command' => 'transaksi',
                'step' => 'waiting_amount',
                'draft_data' => $draft,
            ]);

            $amountPrompt = $flow['amount']['prompt'] ?? "Masukkan <b>NOMINAL</b> transaksi (Rp):\n<i>(Contoh: 750000 atau bisa digabung: 100.000, Pak Army)</i>";
            $msgText = "✅ <b>Jenis:</b> {$typeLabel}\n📅 <b>Tahun Anggaran:</b> {$year}\n📂 <b>Kategori:</b> {$catLabel}\n\n{$amountPrompt}";

            if ($messageId) {
                $this->bot->editMessageText($chatId, $messageId, $msgText);
            } else {
                $this->bot->sendMessage($chatId, $msgText);
            }
        }
    }

    /**
     * Memulai percakapan transaksi baru (/transaksi).
     */
    protected function startTransaksiConversation(string $chatId, User $user): void
    {
        TelegramConversation::updateOrCreate(
            ['chat_id' => $chatId],
            [
                'user_id' => $user->id,
                'command' => 'transaksi',
                'step' => 'waiting_type',
                'draft_data' => [
                    'photos' => [],
                ],
            ]
        );

        $keyboard = [
            [
                ['text' => '🔴 Pengeluaran BOSP (/pengeluaran)', 'callback_data' => 'trx_type:keluar_bosp'],
            ],
            [
                ['text' => '🟢 Pemasukan BOSP (/pemasukan)', 'callback_data' => 'trx_type:masuk_bosp'],
            ],
            [
                ['text' => '❌ Batalkan', 'callback_data' => 'confirm:no'],
            ],
        ];

        $this->bot->sendMessage(
            $chatId,
            "📝 <b>PENCATATAN TRANSAKSI KAS BOSP</b>\n━━━━━━━━━━━━━━━━━━\n"
            ."💡 <i>Tips: Anda dapat langsung mengetik <b>/pengeluaran</b> atau <b>/pemasukan</b> kapan saja.</i>\n\n"
            .'Silakan pilih jenis transaksi di bawah ini:',
            $keyboard
        );
    }

    /**
     * Memulai percakapan khusus upload foto barang (/foto).
     */
    protected function startFotoConversation(string $chatId, User $user): void
    {
        $conversation = TelegramConversation::updateOrCreate(
            ['chat_id' => $chatId],
            [
                'user_id' => $user->id,
                'command' => 'foto',
                'step' => 'waiting_foto_year',
                'draft_data' => [
                    'photos' => [],
                ],
            ]
        );

        $this->promptFotoYearSelection($chatId);
    }

    /**
     * Tampilkan pilihan tahun anggaran untuk dokumentasi foto.
     */
    protected function promptFotoYearSelection(string|int $chatId, ?int $messageId = null): void
    {
        $currentYear = (int) date('Y');
        $years = collect([$currentYear, $currentYear - 1, $currentYear - 2]);

        try {
            if (class_exists(FiscalYearHelper::class)) {
                $fromHelper = FiscalYearHelper::getAvailableYears()
                    ->filter(fn ($y) => $y <= $currentYear + 1 && $y >= $currentYear - 2)
                    ->unique()
                    ->sortDesc()
                    ->values();
                if ($fromHelper->isNotEmpty()) {
                    $years = $fromHelper;
                }
            }
        } catch (\Throwable) {
            // fallback
        }

        $keyboard = [];
        $row = [];
        foreach ($years as $yr) {
            $yr = (int) $yr;
            $label = ($yr === $currentYear) ? "📅 {$yr} (Tahun Ini)" : "📅 {$yr}";
            $row[] = ['text' => $label, 'callback_data' => "foto_year:{$yr}"];
            if (count($row) === 2) {
                $keyboard[] = $row;
                $row = [];
            }
        }
        if (! empty($row)) {
            $keyboard[] = $row;
        }
        $keyboard[] = [
            ['text' => '❌ Batalkan', 'callback_data' => 'confirm:no'],
        ];

        $promptText = "📸 <b>DOKUMENTASI FOTO BARANG BOSP</b>\n━━━━━━━━━━━━━━━━━━\n"
            ."📅 Pilih <b>tahun anggaran</b> untuk foto yang akan diupload:\n\n"
            .'<i>(Pilih tahun lalu jika foto ini untuk belanja tahun sebelumnya)</i>';

        if ($messageId) {
            $this->bot->editMessageText($chatId, $messageId, $promptText, $keyboard);
        } else {
            $this->bot->sendMessage($chatId, $promptText, $keyboard);
        }
    }

    /**
     * Mengambil alur langkah efektif untuk perintah transaksi kas (/transaksi, /pengeluaran, /pemasukan).
     */
    protected function getTransaksiFlow(?string $typeOrCommand = null): array
    {
        $command = 'transaksi';
        if ($typeOrCommand === 'keluar' || $typeOrCommand === 'pengeluaran') {
            $command = 'pengeluaran';
        } elseif ($typeOrCommand === 'masuk' || $typeOrCommand === 'pemasukan') {
            $command = 'pemasukan';
        }

        if (! Schema::hasTable('telegram_commands')) {
            return TelegramCommand::defaultFlow($command);
        }

        // 1. Cek apakah ada kustomisasi khusus untuk perintah terkait (/pengeluaran atau /pemasukan)
        $cmd = TelegramCommand::where('command', $command)->first();
        if ($cmd && ! empty($cmd->flow_config)) {
            return $cmd->getEffectiveFlow();
        }

        // 2. Jika tidak ada, cek kustomisasi pada perintah induk /transaksi
        $parentCmd = TelegramCommand::where('command', 'transaksi')->first();
        if ($parentCmd && ! empty($parentCmd->flow_config)) {
            return $parentCmd->getEffectiveFlow();
        }

        // 3. Fallback ke defaultFlow bawaan sistem
        return $cmd ? $cmd->getEffectiveFlow() : ($parentCmd ? $parentCmd->getEffectiveFlow() : TelegramCommand::defaultFlow($command));
    }

    /**
     * Menentukan dan menjalankan langkah aktif berikutnya pada alur /transaksi.
     */
    protected function advanceTransaksiNextStep(string $chatId, TelegramConversation $conversation, array $draft, string $currentStep): void
    {
        $flow = $this->getTransaksiFlow($draft['type'] ?? 'transaksi');
        $isMasuk = ($draft['type'] ?? 'keluar') === 'masuk';

        // Jika transaksi ini tertaut ke transaksi induk (sisa kasbon / bayar talangan),
        // Uraian & Penanggung Jawab sudah otomatis terisi dari transaksi induk.
        if (! empty($draft['parent_kas_id']) && $currentStep === 'waiting_amount') {
            if ($flow['photos']['is_enabled'] ?? true) {
                $draft['photos'] = [];
                $conversation->update([
                    'step' => 'waiting_photos',
                    'draft_data' => $draft,
                ]);

                $prompt = $flow['photos']['prompt'] ?? "Silakan upload <b>FOTO NOTA/SLIP/KWITANSI dan BARANG</b>:\n<i>(Kirim foto langsung, atau ketik <b>/skip</b> jika tidak ada)</i>";
                $this->bot->sendMessage($chatId, $prompt);

                return;
            }

            $draft['photos'] = [];
            $conversation->update([
                'step' => 'waiting_confirmation',
                'draft_data' => $draft,
            ]);
            $this->showConfirmation($chatId, $conversation, $draft);

            return;
        }

        // 1. Dari waiting_amount -> waiting_description (jika aktif)
        if ($currentStep === 'waiting_amount') {
            if ($flow['description']['is_enabled'] ?? true) {
                $conversation->update([
                    'step' => 'waiting_description',
                    'draft_data' => $draft,
                ]);

                $cleanAmount = $draft['amount'] ?? 0;
                $formatted = number_format($cleanAmount, 0, ',', '.');
                $defaultDescPrompt = $isMasuk
                    ? "Sekarang masukkan <b>URAIAN PENERIMAAN / TRANSAKSI</b>:\n<i>(Wajib diisi, contoh: Droping Kas Tunai dari Bank)</i>"
                    : "Sekarang masukkan <b>URAIAN BELANJA / TRANSAKSI</b>:\n<i>(Wajib diisi, contoh: Pembelian Kertas HVS dan Spidol)</i>";
                $prompt = $flow['description']['prompt'] ?? $defaultDescPrompt;

                $headerInfo = "💵 <b>Nominal:</b> Rp {$formatted}";
                if (! empty($draft['person'])) {
                    $guruBadge = ! empty($draft['guru_id']) ? ' <i>(Guru Terdaftar)</i>' : '';
                    $headerInfo .= "\n👤 <b>Penanggung Jawab:</b> {$draft['person']}{$guruBadge}";
                }

                $this->bot->sendMessage($chatId, "{$headerInfo}\n\n{$prompt}");

                return;
            }
            $currentStep = 'waiting_description';
        }

        // 2. Dari waiting_description -> waiting_person (jika aktif)
        if ($currentStep === 'waiting_description') {
            if (! empty($draft['person'])) {
                // Lewati langkah penanggung jawab karena sudah diisi saat langkah nominal
                $currentStep = 'waiting_person';
            } elseif ($flow['person']['is_enabled'] ?? true) {
                $conversation->update([
                    'step' => 'waiting_person',
                    'draft_data' => $draft,
                ]);

                $desc = $draft['description'] ?? '-';
                $defaultPersonPrompt = $isMasuk
                    ? "👤 Masukkan <b>NAMA PENANGGUNG JAWAB / PENYETOR / SUMBER DANA</b>:\n<i>(Wajib diisi, contoh: Bank Jatim, Ibu Aliya, Pak Budi)</i>"
                    : "👤 Masukkan <b>NAMA GURU / PENANGGUNG JAWAB TRANSAKSI</b>:\n<i>(Wajib diisi, contoh: Pak Budi, Ibu Siti, atau nama Anda sendiri)</i>";

                $prompt = $flow['person']['prompt'] ?? $defaultPersonPrompt;
                if (str_contains($prompt, '/skip')) {
                    $prompt = $defaultPersonPrompt;
                }

                $this->bot->sendMessage($chatId, "📝 <b>Uraian:</b> {$desc}\n\n{$prompt}");

                return;
            } else {
                $currentStep = 'waiting_person';
            }
        }

        // 3. Dari waiting_person -> waiting_store_nota (jika pengeluaran) atau waiting_photos (jika penerimaan)
        if ($currentStep === 'waiting_person') {
            if (! $isMasuk) {
                $isStoreNotaEnabled = isset($flow['store_nota'])
                    ? ($flow['store_nota']['is_enabled'] ?? true)
                    : (($flow['photo_store_nota']['is_enabled'] ?? true) || ($flow['store']['is_enabled'] ?? true) || ($flow['nota']['is_enabled'] ?? true));

                if ($isStoreNotaEnabled) {
                    $conversation->update([
                        'step' => 'waiting_store_nota',
                        'draft_data' => $draft,
                    ]);

                    $person = $draft['person'] ?? '-';
                    $guruBadge = ! empty($draft['guru_id']) ? ' <i>(Guru Terdaftar)</i>' : '';
                    $defaultPrompt = "📸 <b>FOTO NOTA, NAMA TOKO & NO. NOTA</b>\n"
                        ."Silakan kirim <b>foto nota / kwitansi belanja</b> dengan <b>caption: Nama Toko, No. Nota</b>:\n"
                        ."<i>(Contoh: <code>Toko Barokah, N26-0050</code>)</i>\n\n"
                        ."• <i>Jika tanpa foto nota:</i> Cukup ketik teks <code>Nama Toko, No. Nota</code>\n"
                        .'• <i>Jika tanpa toko/nota/foto:</i> Ketik <b>/skip</b>';

                    $prompt = $flow['store_nota']['prompt'] ?? ($flow['photo_store_nota']['prompt'] ?? $defaultPrompt);
                    $this->bot->sendMessage($chatId, "👤 <b>Penanggung Jawab:</b> {$person}{$guruBadge}\n\n{$prompt}");

                    return;
                }
            } else {
                if ($flow['photos']['is_enabled'] ?? true) {
                    $draft['photos'] = [];
                    $conversation->update([
                        'step' => 'waiting_photos',
                        'draft_data' => $draft,
                    ]);

                    $prompt = $flow['photos']['prompt'] ?? "📸 <b>FOTO BUKTI / SLIP TRANSAKSI</b>\nSilakan upload <b>foto bukti slip setor/tarik bank atau tanda terima kas</b>:\n<i>(Kirim foto langsung, atau ketik <b>/skip</b> jika tidak ada)</i>";
                    $this->bot->sendMessage($chatId, $prompt);

                    return;
                }
            }
            $currentStep = 'waiting_store_nota';
        }

        // 4. Dari waiting_store_nota (atau waiting_store/waiting_nota) -> waiting_photos (Langkah 7) atau waiting_confirmation
        if (in_array($currentStep, ['waiting_store_nota', 'waiting_photo_store_nota', 'waiting_store', 'waiting_nota'])) {
            if ($flow['photos']['is_enabled'] ?? true) {
                $draft['doc_photos'] = $draft['doc_photos'] ?? [];
                $draft['photos'] = $draft['photos'] ?? [];
                $conversation->update([
                    'step' => 'waiting_photos',
                    'draft_data' => $draft,
                ]);

                $defaultPhotoPrompt = $isMasuk
                    ? "📸 <b>FOTO BUKTI / SLIP TRANSAKSI</b>\nSilakan upload <b>foto bukti slip setor/tarik bank atau tanda terima kas</b>:\n<i>(Kirim foto langsung, atau ketik <b>/skip</b> jika tidak ada)</i>"
                    : "📸 <b>FOTO DOKUMENTASI BARANG FISIK</b>\nSilakan upload <b>foto fisik barang / kegiatan belanja</b> (maksimal 3 foto):\n<i>(Kirim 1 s/d 3 foto langsung dari kamera/galeri, atau ketik <b>/skip</b> jika tidak ada foto dokumentasi)</i>";

                $prompt = $flow['photos']['prompt'] ?? $defaultPhotoPrompt;

                $this->bot->sendMessage($chatId, $prompt);

                return;
            }
            $currentStep = 'waiting_photos';
        }

        // 5. Jika semua langkah aktif berikutnya selesai atau dinonaktifkan -> Masuk ke Konfirmasi
        $draft['photos'] = $draft['photos'] ?? [];
        $conversation->update([
            'step' => 'waiting_confirmation',
            'draft_data' => $draft,
        ]);

        $this->showConfirmation($chatId, $conversation, $draft);
    }

    /**
     * Memproses step demi step pada alur /transaksi.
     */
    protected function processTransaksiStep(string $chatId, string $text, bool $hasPhoto, array $message, TelegramConversation $conversation, User $user): void
    {
        $draft = $conversation->draft_data ?: [];
        $step = $conversation->step;
        $flow = $this->getTransaksiFlow($draft['type'] ?? 'transaksi');

        // Step 1: Menunggu Nominal
        if ($step === 'waiting_amount') {
            $rawText = trim($text);
            $amountText = $rawText;
            $personText = null;

            // Ekstrak nama penanggung jawab jika diinput bersamaan dengan nominal
            // Format yang didukung:
            // - Koma: "100.000, army", "100.000, Pak Army"
            // - Baris baru: "100.000\narmy"
            // - Strip: "100.000 - army"
            // - Garis miring: "100.000 / army"
            // - Spasi langsung: "100.000 army"
            if (str_contains($rawText, "\n")) {
                $parts = explode("\n", $rawText, 2);
                $amountText = trim($parts[0]);
                $personText = trim($parts[1]);
            } elseif (str_contains($rawText, ' - ')) {
                $parts = explode(' - ', $rawText, 2);
                $amountText = trim($parts[0]);
                $personText = trim($parts[1]);
            } elseif (str_contains($rawText, ' / ')) {
                $parts = explode(' / ', $rawText, 2);
                $amountText = trim($parts[0]);
                $personText = trim($parts[1]);
            } elseif (str_contains($rawText, ',')) {
                $parts = explode(',', $rawText, 2);
                // Hanya potong jika bagian setelah koma mengandung huruf (bukan format desimal angka)
                if (isset($parts[1]) && preg_match('/[a-zA-Z]/', $parts[1])) {
                    $amountText = trim($parts[0]);
                    $personText = trim($parts[1]);
                }
            } elseif (preg_match('/^((?:rp\.?\s*)?[0-9\.\,]+)\s+([a-zA-Z].*)$/i', $rawText, $matches)) {
                $amountText = trim($matches[1]);
                $personText = trim($matches[2]);
            }

            $cleanAmount = (float) preg_replace('/[^0-9]/', '', $amountText);
            if ($cleanAmount <= 0) {
                $this->bot->sendMessage($chatId, "⚠️ <b>Nominal tidak valid!</b> Masukkan angka yang benar tanpa huruf.\nContoh: <code>750000</code> atau <code>100.000, Army</code>:");

                return;
            }

            $draft['amount'] = $cleanAmount;

            if (! empty($personText)) {
                $rawPerson = trim($personText);
                $isMasuk = ($draft['type'] ?? 'keluar') === 'masuk';

                if ($isMasuk && (str_contains($rawPerson, ',') || str_contains($rawPerson, ' - '))) {
                    $delim = str_contains($rawPerson, ',') ? ',' : ' - ';
                    $parts = explode($delim, $rawPerson, 2);
                    $rawPerson = trim($parts[0]);
                    $draft['evidence_number'] = trim($parts[1]);
                }

                $matchedGuru = Schema::hasTable('gurus') ? Guru::findByNameMatch($rawPerson) : null;
                if ($matchedGuru) {
                    $draft['guru_id'] = $matchedGuru->id;
                    $draft['person'] = $matchedGuru->full_name_with_title;
                } else {
                    $draft['guru_id'] = null;
                    $draft['person'] = $rawPerson;
                }
            }

            $conversation->update(['draft_data' => $draft]);

            $this->advanceTransaksiNextStep($chatId, $conversation, $draft, 'waiting_amount');

            return;
        }

        // Step 2: Menunggu Uraian
        if ($step === 'waiting_description') {
            $isRequired = $flow['description']['is_required'] ?? true;
            if (empty($text) || $text === '-' || $text === '/skip') {
                if ($isRequired) {
                    $this->bot->sendMessage($chatId, "⚠️ <b>Uraian Belanja wajib diisi!</b>\nContoh: <code>Pengadaan Kertas HVS dan Tinta Printer</code>");

                    return;
                }
                $draft['description'] = 'Transaksi Kas BOSP';
            } else {
                $draft['description'] = $text;
            }

            $conversation->update(['draft_data' => $draft]);

            $this->advanceTransaksiNextStep($chatId, $conversation, $draft, 'waiting_description');

            return;
        }

        // Step 3: Menunggu Nama Orang yang Bertransaksi (Penerimaan & Pengeluaran)
        if ($step === 'waiting_person') {
            $isMasuk = ($draft['type'] ?? 'keluar') === 'masuk';

            if (empty($text) || $text === '-' || $text === '/skip') {
                $roleLabel = $isMasuk ? 'Penanggung Jawab / Penyetor' : 'Guru / Penanggung Jawab Transaksi';
                $this->bot->sendMessage(
                    $chatId,
                    "⚠️ <b>{$roleLabel} wajib diisi!</b>\nTidak dapat dilewati (/skip). Setiap transaksi kas harus memiliki penanggung jawab yang jelas.\n\nSilakan ketik nama guru atau penanggung jawab:"
                );

                return;
            }

            $rawPerson = $text;
            $evidenceNum = null;

            if ($isMasuk && (str_contains($text, ',') || str_contains($text, ' - '))) {
                $delim = str_contains($text, ',') ? ',' : ' - ';
                $parts = explode($delim, $text, 2);
                $rawPerson = trim($parts[0]);
                $evidenceNum = trim($parts[1]);
            }

            // Cocokkan otomatis dengan data guru di database jika ada
            $matchedGuru = Schema::hasTable('gurus') ? Guru::findByNameMatch($rawPerson) : null;

            if ($matchedGuru) {
                $draft['guru_id'] = $matchedGuru->id;
                $draft['person'] = $matchedGuru->full_name_with_title;
            } else {
                $draft['guru_id'] = null;
                $draft['person'] = trim($rawPerson);
            }

            if ($evidenceNum) {
                $draft['evidence_number'] = $evidenceNum;
            }

            $conversation->update(['draft_data' => $draft]);

            $this->advanceTransaksiNextStep($chatId, $conversation, $draft, 'waiting_person');

            return;
        }

        // Step 4: Menunggu Foto Dokumentasi, Nama Toko & No. Nota (Alur Gabungan)
        if ($step === 'waiting_store_nota' || $step === 'waiting_photo_store_nota') {
            $isRequired = $flow['store_nota']['is_required'] ?? ($flow['photo_store_nota']['is_required'] ?? false);

            if ($text === '/skip' || $text === '-') {
                if ($isRequired) {
                    $this->bot->sendMessage($chatId, '⚠️ <b>Nama Toko & Nomor Nota wajib diisi!</b> Silakan masukkan nama toko dan nomor nota:');

                    return;
                }

                $draft['store_name'] = null;
                $draft['evidence_number'] = null;
                $draft['photos'] = $draft['photos'] ?? [];
                $conversation->update(['draft_data' => $draft]);

                $this->advanceTransaksiNextStep($chatId, $conversation, $draft, 'waiting_store_nota');

                return;
            }

            if ($text === '/selesai' && ! empty($draft['photos'])) {
                $this->advanceTransaksiNextStep($chatId, $conversation, $draft, 'waiting_store_nota');

                return;
            }

            // Jika user mengirim foto
            if ($hasPhoto) {
                $photoVariants = $message['photo'];
                $highestRes = end($photoVariants);
                $fileId = $highestRes['file_id'];

                $caption = trim((string) ($message['caption'] ?? ''));
                $draft['nota_photo'] = $fileId;
                $photos = $draft['photos'] ?? [];
                if (! in_array($fileId, $photos)) {
                    array_unshift($photos, $fileId);
                    $photos = array_values(array_unique($photos));
                }
                $draft['photos'] = $photos;

                // Jika ada caption, parse sebagai Toko & No Nota
                if (! empty($caption) && $caption !== '-' && strtolower($caption) !== '/skip') {
                    if (str_contains($caption, "\n")) {
                        $parts = explode("\n", $caption, 2);
                        $draft['store_name'] = trim($parts[0]);
                        $draft['evidence_number'] = trim($parts[1]);
                    } elseif (str_contains($caption, ', ')) {
                        $parts = explode(', ', $caption, 2);
                        $draft['store_name'] = trim($parts[0]);
                        $draft['evidence_number'] = trim($parts[1]);
                    } elseif (str_contains($caption, ',')) {
                        $parts = explode(',', $caption, 2);
                        $draft['store_name'] = trim($parts[0]);
                        $draft['evidence_number'] = trim($parts[1]);
                    } elseif (str_contains($caption, ' - ')) {
                        $parts = explode(' - ', $caption, 2);
                        $draft['store_name'] = trim($parts[0]);
                        $draft['evidence_number'] = trim($parts[1]);
                    } elseif (str_contains($caption, ' / ')) {
                        $parts = explode(' / ', $caption, 2);
                        $draft['store_name'] = trim($parts[0]);
                        $draft['evidence_number'] = trim($parts[1]);
                    } else {
                        $draft['store_name'] = trim($caption);
                        $draft['evidence_number'] = null;
                    }

                    $conversation->update(['draft_data' => $draft]);

                    $this->advanceTransaksiNextStep($chatId, $conversation, $draft, 'waiting_store_nota');

                    return;
                }

                // Foto nota tanpa caption
                $conversation->update(['draft_data' => $draft]);

                $this->bot->sendMessage(
                    $chatId,
                    "📸 <b>Foto nota berhasil diterima!</b>\nSilakan ketik <b>Nama Toko & No. Nota</b> (contoh: <code>Toko Barokah, N26-0050</code>), atau ketik <b>/skip</b> jika tanpa nama toko/nota."
                );

                return;
            }

            // Jika user mengirim teks saja (tanpa foto)
            if (! empty($text)) {
                if (str_contains($text, "\n")) {
                    $parts = explode("\n", $text, 2);
                    $draft['store_name'] = trim($parts[0]);
                    $draft['evidence_number'] = trim($parts[1]);
                } elseif (str_contains($text, ', ')) {
                    $parts = explode(', ', $text, 2);
                    $draft['store_name'] = trim($parts[0]);
                    $draft['evidence_number'] = trim($parts[1]);
                } elseif (str_contains($text, ',')) {
                    $parts = explode(',', $text, 2);
                    $draft['store_name'] = trim($parts[0]);
                    $draft['evidence_number'] = trim($parts[1]);
                } elseif (str_contains($text, ' - ')) {
                    $parts = explode(' - ', $text, 2);
                    $draft['store_name'] = trim($parts[0]);
                    $draft['evidence_number'] = trim($parts[1]);
                } elseif (str_contains($text, ' / ')) {
                    $parts = explode(' / ', $text, 2);
                    $draft['store_name'] = trim($parts[0]);
                    $draft['evidence_number'] = trim($parts[1]);
                } else {
                    $draft['store_name'] = trim($text);
                    $draft['evidence_number'] = null;
                }

                $draft['photos'] = $draft['photos'] ?? [];
                $conversation->update(['draft_data' => $draft]);

                $this->advanceTransaksiNextStep($chatId, $conversation, $draft, 'waiting_store_nota');

                return;
            }
        }

        // Backward compatibility: waiting_store
        if ($step === 'waiting_store') {
            $draft['person_name'] = ($text === '-' || $text === '/skip') ? null : $text;
            $conversation->update(['draft_data' => $draft]);
            $this->advanceTransaksiNextStep($chatId, $conversation, $draft, 'waiting_store');

            return;
        }

        // Backward compatibility: waiting_nota
        if ($step === 'waiting_nota') {
            $draft['evidence_number'] = ($text === '-' || $text === '/skip') ? null : $text;
            $conversation->update(['draft_data' => $draft]);
            $this->advanceTransaksiNextStep($chatId, $conversation, $draft, 'waiting_nota');

            return;
        }

        // Step 5: Menunggu Lampiran Foto
        if ($step === 'waiting_photos' || ($step === 'waiting_confirmation' && $hasPhoto)) {
            $isRequired = $flow['photos']['is_required'] ?? false;
            $isKeluar = ($draft['type'] ?? 'keluar') === 'keluar';

            if ($text === '/skip' || $text === '/selesai') {
                $docPhotos = $draft['doc_photos'] ?? [];
                if (empty($docPhotos) && empty($draft['photos']) && $isRequired) {
                    $this->bot->sendMessage($chatId, '⚠️ <b>Lampiran Foto wajib diunggah!</b> Silakan kirim minimal 1 foto dokumentasi:');

                    return;
                }

                $this->showConfirmation($chatId, $conversation, $draft);

                return;
            }

            if ($hasPhoto) {
                $photoVariants = $message['photo'];
                $highestRes = end($photoVariants);
                $fileId = $highestRes['file_id'];

                $count = 0;
                $updatedDraft = [];

                DB::transaction(function () use ($conversation, $fileId, &$count, &$updatedDraft) {
                    $conv = TelegramConversation::where('id', $conversation->id)->lockForUpdate()->first();
                    if (! $conv) {
                        return;
                    }
                    $draft = $conv->draft_data ?: [];
                    $docPhotos = $draft['doc_photos'] ?? [];
                    $photos = $draft['photos'] ?? [];

                    if (! in_array($fileId, $docPhotos) && count($docPhotos) < 3) {
                        $docPhotos[] = $fileId;
                    }

                    if (! in_array($fileId, $photos)) {
                        $photos[] = $fileId;
                    }

                    $draft['doc_photos'] = $docPhotos;
                    $draft['photos'] = $photos;
                    $count = count($docPhotos);

                    if ($count >= 3) {
                        $conv->update([
                            'step' => 'waiting_confirmation',
                            'draft_data' => $draft,
                        ]);
                    } else {
                        $conv->update(['draft_data' => $draft]);
                    }

                    $updatedDraft = $draft;
                });

                if ($count >= 3) {
                    $this->bot->sendMessage($chatId, '📸 <b>3 Foto dokumentasi berhasil diterima!</b>');
                    $this->showConfirmation($chatId, $conversation, $updatedDraft);
                } else {
                    $label = $isKeluar ? 'Foto dokumentasi' : 'Foto bukti';
                    $this->bot->sendMessage(
                        $chatId,
                        "📸 <b>{$label} ke-{$count} berhasil diterima!</b>\nSilakan kirim foto dokumentasi berikutnya (maks. 3 foto), atau ketik <b>/selesai</b> untuk melanjutkan."
                    );
                }

                return;
            }

            $helpLabel = $isKeluar ? 'foto fisik barang / kegiatan belanja (maksimal 3 foto)' : 'foto bukti slip/transaksi';
            $this->bot->sendMessage($chatId, "Silakan kirim {$helpLabel}, atau ketik <b>/selesai</b> / <b>/skip</b> jika sudah selesai.");

            return;
        }
    }

    /**
     * Menampilkan kartu ringkasan konfirmasi transaksi kas.
     */
    protected function showConfirmation(string $chatId, TelegramConversation $conversation, array $draft): void
    {
        $conversation->update(['step' => 'waiting_confirmation']);

        $year = $draft['fiscal_year'] ?? date('Y');
        $formattedAmount = number_format($draft['amount'] ?? 0, 0, ',', '.');
        $photoCount = count($draft['photos'] ?? []);
        $isMasuk = ($draft['type'] ?? 'keluar') === 'masuk';

        $msg = "📋 <b>KONFIRMASI TRANSAKSI KAS</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━━\n";
        $msg .= '• <b>Jenis:</b> '.($draft['type_label'] ?? 'Transaksi Kas')."\n";
        $msg .= "• <b>Tahun Anggaran:</b> {$year}\n";
        if (! empty($draft['category_label'])) {
            $msg .= '• <b>Kategori:</b> '.$draft['category_label']."\n";
        }
        $msg .= '• <b>Tanggal:</b> '.date('d/m/Y')."\n";
        $msg .= "• <b>Nominal:</b> Rp {$formattedAmount}\n";
        $msg .= '• <b>Uraian:</b> '.($draft['description'] ?? '-')."\n";
        if (! empty($draft['parent_kas_id'])) {
            $msg .= "• <b>Tertaut Kas Induk:</b> #{$draft['parent_kas_id']}\n";
        }

        $personLabel = $isMasuk ? 'Penyetor / Sumber' : 'Penanggung Jawab';
        $guruBadge = ! empty($draft['guru_id']) ? ' <i>(Guru Terdaftar)</i>' : '';
        $msg .= "• <b>{$personLabel}:</b> ".($draft['person'] ?? '-')."{$guruBadge}\n";

        if (! $isMasuk) {
            $storeNameDisplay = $draft['store_name'] ?? null;
            $msg .= '• <b>Toko:</b> '.($storeNameDisplay ?: '-')."\n";
            $msg .= '• <b>No. Nota:</b> '.($draft['evidence_number'] ?? '-')."\n";
            $details = [];
            if (! empty($draft['nota_photo'])) {
                $details[] = '1 Nota';
            }
            $docCount = count($draft['doc_photos'] ?? []);
            if ($docCount > 0) {
                $details[] = "{$docCount} Dokumentasi Barang";
            }
            $detailStr = ! empty($details) ? ' ('.implode(', ', $details).')' : '';
            $msg .= "• <b>Lampiran Foto:</b> {$photoCount} Foto{$detailStr}\n";
        } elseif (! empty($draft['evidence_number'])) {
            $msg .= '• <b>No. Bukti:</b> '.$draft['evidence_number']."\n";
            $msg .= "• <b>Lampiran Foto:</b> {$photoCount} Foto\n";
        } else {
            $msg .= "• <b>Lampiran Foto:</b> {$photoCount} Foto\n";
        }
        $msg .= "━━━━━━━━━━━━━━━━━━\n";
        $msg .= 'Apakah data transaksi di atas sudah benar?';

        $keyboard = [
            [
                ['text' => '✅ Ya, Simpan ke Buku Kas', 'callback_data' => 'confirm:yes'],
                ['text' => '❌ Batalkan', 'callback_data' => 'confirm:no'],
            ],
        ];

        $this->bot->sendMessage($chatId, $msg, $keyboard);
    }

    /**
     * Finalisasi penyimpanan transaksi kas dan foto ke database MKB.
     */
    protected function finalizeTransaction(string $chatId, TelegramConversation $conversation, User $user, ?int $messageId = null): void
    {
        $draft = $conversation->draft_data ?: [];
        $selectedYear = (int) ($draft['fiscal_year'] ?? date('Y'));
        $currentYear = (int) date('Y');

        if ($selectedYear === $currentYear) {
            $today = now()->toDateString();
        } else {
            $today = date("{$selectedYear}-m-d");
            if (! checkdate((int) date('m'), (int) date('d'), $selectedYear)) {
                $today = "{$selectedYear}-12-31";
            }
        }
        $year = $selectedYear;

        $category = $draft['category'] ?? (($draft['type'] === 'keluar') ? 'belanja_tunai' : 'lainnya_masuk');
        $panjarStatus = ($category === 'panjar_kerja') ? 'pending' : 'none';

        $person = $draft['person'] ?? null;
        $store = $draft['store_name'] ?? null;
        $isKeluar = ($draft['type'] ?? 'keluar') === 'keluar';

        $guruId = $draft['guru_id'] ?? null;
        if (! $guruId && Schema::hasTable('gurus')) {
            $userGuru = Guru::findByNameMatch($user->name);
            if ($userGuru) {
                $guruId = $userGuru->id;
            }
        }

        // Pada tabel kas_tunais:
        // - Jika transaksi pengeluaran (keluar): kolom person_name murni menyimpan Nama Toko (bukan nama guru).
        //   Penanggung jawab guru tersimpan di guru_id. Jika tidak ada toko, person_name bernilai null.
        // - Jika transaksi penerimaan (masuk): person_name menyimpan nama penyetor / sumber dana.
        if ($isKeluar) {
            $kasPersonName = $store ?: ($guruId ? null : $person);
        } else {
            $kasPersonName = $guruId ? ($store ?: null) : ($person ?: $store);
        }

        // 1. Simpan Transaksi Kas Tunai
        $kas = KasTunai::create([
            'funding_source' => $draft['funding_source'] ?? 'BOSP',
            'transaction_date' => $today,
            'transaction_type' => $draft['type'] ?? 'keluar',
            'category' => $category,
            'amount' => $draft['amount'] ?? 0,
            'description' => $draft['description'] ?? 'Transaksi via Telegram',
            'person_name' => $kasPersonName,
            'guru_id' => $guruId,
            'parent_kas_id' => $draft['parent_kas_id'] ?? null,
            'panjar_status' => $panjarStatus,
            'evidence_number' => $draft['evidence_number'] ?? null,
            'created_by' => $user->id,
        ]);

        // Jika kategori sisa_panjar dan tertaut parent kasbon, evaluasi apakah pelunasan sebagian atau lunas penuh
        $parentKasbonInfo = null;
        if ($category === 'sisa_panjar' && ! empty($draft['parent_kas_id'])) {
            $parentKas = KasTunai::with('childKass')->find($draft['parent_kas_id']);
            if ($parentKas) {
                // Karena transaksi anak ($kas) sudah disimpan di database, remaining_panjar_amount sudah mencerminkan sisa terkini
                $newRemaining = $parentKas->remaining_panjar_amount;

                if ($newRemaining > 0) {
                    $parentKas->update([
                        'panjar_status' => 'partial',
                        'updated_by' => $user->id,
                    ]);
                    $parentKasbonInfo = [
                        'status' => 'partial',
                        'remaining' => $newRemaining,
                    ];
                } else {
                    $parentKas->update([
                        'panjar_status' => 'settled',
                        'updated_by' => $user->id,
                    ]);
                    $parentKasbonInfo = [
                        'status' => 'settled',
                        'remaining' => 0,
                    ];
                }
            }
        }

        // 2. Simpan Dokumentasi Foto jika ada
        $photos = $draft['photos'] ?? [];
        $savedPhotosCount = 0;

        if (! empty($photos)) {
            $doc = BarangDokumentasi::create([
                'funding_source' => $draft['funding_source'] ?? 'BOS',
                'year' => $year,
                'no_nota' => $draft['evidence_number'] ?? ('KAS-'.$kas->id),
                'tanggal_terima' => $today,
                'nama_toko' => $store ?: null,
                'uraian' => $draft['description'] ?? null,
                'nominal' => $draft['amount'] ?? 0,
                'kas_tunai_id' => $kas->id,
                'created_by' => $user->id,
            ]);

            $dir = "dokumentasi_barang/{$year}/{$doc->safe_nota_name}";
            $urutan = 0;

            foreach ($photos as $fileId) {
                $tempFilename = 'foto_temp_'.Str::random(8).'.jpg';
                $tempPath = "{$dir}/{$tempFilename}";

                $downloaded = $this->bot->downloadFile($fileId, $tempPath);
                if ($downloaded) {
                    $urutan++;
                    $finalFilename = "foto_{$urutan}_".Str::random(6).'.jpg';
                    $finalPath = "{$dir}/{$finalFilename}";
                    Storage::disk('public')->move($tempPath, $finalPath);

                    $isNotaPhoto = (! empty($draft['nota_photo']) && $fileId === $draft['nota_photo']);
                    $keterangan = $isNotaPhoto
                        ? 'Foto Nota'
                        : ($draft['description'] ?? 'Dokumentasi Barang');

                    BarangDokumentasiFoto::create([
                        'barang_dokumentasi_id' => $doc->id,
                        'urutan' => $urutan,
                        'file_path' => $finalPath,
                        'keterangan' => $keterangan,
                    ]);

                    if (empty($kas->receipt_file) && Schema::hasColumn('kas_tunais', 'receipt_file')) {
                        $kas->update(['receipt_file' => $finalPath]);
                    }

                    $savedPhotosCount++;
                }
            }
        }

        // Hapus sesi percakapan
        $conversation->delete();

        $amountFmt = number_format($draft['amount'] ?? 0, 0, ',', '.');
        $successMsg = "🎉 <b>TRANSAKSI BERHASIL DISIMPAN!</b>\n";
        $successMsg .= "━━━━━━━━━━━━━━━━━━\n";
        $successMsg .= "• <b>ID Kas:</b> #{$kas->id}\n";
        $successMsg .= "• <b>Tahun Anggaran:</b> {$year}\n";
        if (! empty($draft['category_label'])) {
            $successMsg .= "• <b>Kategori:</b> {$draft['category_label']}\n";
        }
        if (! empty($draft['parent_kas_id'])) {
            $successMsg .= "• <b>Tertaut Kas Induk:</b> #{$draft['parent_kas_id']}\n";
            if ($category === 'sisa_panjar') {
                if ($parentKasbonInfo && $parentKasbonInfo['status'] === 'partial') {
                    $remFmt = number_format($parentKasbonInfo['remaining'], 0, ',', '.');
                    $successMsg .= "• <b>Status Kasbon:</b> ⏳ Sebagian (Partial) — <b>Sisa Tanggungan: Rp {$remFmt}</b>\n";
                } else {
                    $successMsg .= "• <b>Status Kasbon:</b> ✅ Kasbon Induk #{$draft['parent_kas_id']} Telah Lunas (Settled)\n";
                }
            }
        }
        $successMsg .= "• <b>Nominal:</b> Rp {$amountFmt}\n";
        $successMsg .= '• <b>Uraian:</b> '.($draft['description'] ?? '-')."\n";

        $personLabel = $isKeluar ? 'Penanggung Jawab' : 'Penyetor / Sumber';
        $guruNameDisplay = $person ?: '-';
        if ($guruId && Schema::hasTable('gurus')) {
            $g = Guru::find($guruId);
            if ($g) {
                $guruNameDisplay = $g->full_name_with_title;
            }
        }
        $guruBadge = $guruId ? ' <i>(Guru Terdaftar)</i>' : '';
        $successMsg .= "• <b>{$personLabel}:</b> {$guruNameDisplay}{$guruBadge}\n";

        if (! empty($store)) {
            $successMsg .= "• <b>Toko:</b> {$store}\n";
        }
        if (! empty($draft['evidence_number'])) {
            $evidenceLabel = $isKeluar ? 'No. Nota' : 'No. Bukti';
            $successMsg .= "• <b>{$evidenceLabel}:</b> {$draft['evidence_number']}\n";
        }
        if ($panjarStatus === 'pending') {
            $successMsg .= "• <b>Status:</b> ⏳ Kasbon Pending (Buka web untuk Selesai SPJ)\n";
        }
        if ($savedPhotosCount > 0) {
            $successMsg .= "• <b>Foto:</b> {$savedPhotosCount} Foto tayang di menu <b>Foto BOSP</b> web.\n";
        }
        $successMsg .= "━━━━━━━━━━━━━━━━━━\n";
        $successMsg .= "Data langsung tercatat di pembukuan MKB web.\nKetik <b>/pengeluaran</b> atau <b>/pemasukan</b> untuk mencatat lagi, atau <b>/saldo</b> untuk cek saldo.";

        if ($messageId) {
            $this->bot->editMessageText($chatId, $messageId, $successMsg);
        } else {
            $this->bot->sendMessage($chatId, $successMsg);
        }
    }

    /**
     * Mengambil alur langkah efektif untuk perintah /foto.
     */
    protected function getFotoFlow(): array
    {
        if (! Schema::hasTable('telegram_commands')) {
            return TelegramCommand::defaultFlow('foto');
        }

        $cmd = TelegramCommand::where('command', 'foto')->first();

        return $cmd ? $cmd->getEffectiveFlow() : TelegramCommand::defaultFlow('foto');
    }

    /**
     * Memproses step demi step pada alur /foto (khusus upload foto barang).
     */
    protected function processFotoStep(string $chatId, string $text, bool $hasPhoto, array $message, TelegramConversation $conversation, User $user): void
    {
        $draft = $conversation->draft_data ?: [];
        $step = $conversation->step;
        $flow = $this->getFotoFlow();

        if ($step === 'waiting_photo_direct' || ($step === 'waiting_foto_uraian' && $hasPhoto)) {
            if ($hasPhoto) {
                $photoVariants = $message['photo'];
                $highestRes = end($photoVariants);
                $fileId = $highestRes['file_id'];

                $count = 0;
                $updatedDraft = [];

                DB::transaction(function () use ($conversation, $fileId, &$count, &$updatedDraft) {
                    $conv = TelegramConversation::where('id', $conversation->id)->lockForUpdate()->first();
                    if (! $conv) {
                        return;
                    }
                    $draft = $conv->draft_data ?: [];
                    $photos = $draft['photos'] ?? [];

                    if (! in_array($fileId, $photos) && count($photos) < 3) {
                        $photos[] = $fileId;
                    }

                    $draft['photos'] = $photos;
                    $count = count($photos);

                    if ($count >= 3) {
                        $conv->update([
                            'step' => 'waiting_foto_uraian',
                            'draft_data' => $draft,
                        ]);
                    } else {
                        $conv->update(['draft_data' => $draft]);
                    }

                    $updatedDraft = $draft;
                });

                $uraianPrompt = $flow['uraian']['prompt'] ?? 'Masukkan <b>URAIAN BELANJA</b> untuk dokumentasi ini:';

                if ($count >= 3) {
                    $this->bot->sendMessage($chatId, "📸 <b>{$count} Foto berhasil diterima!</b>\n\n{$uraianPrompt}");
                } else {
                    $this->bot->sendMessage(
                        $chatId,
                        "📸 <b>Foto {$count} diterima!</b>\nKirim foto berikutnya (maks. 3 foto), atau ketik <b>/selesai</b> untuk melanjutkan."
                    );
                }

                return;
            }

            if ($text === '/selesai' && ! empty($draft['photos'])) {
                $conversation->update([
                    'step' => 'waiting_foto_uraian',
                    'draft_data' => $draft,
                ]);
                $uraianPrompt = $flow['uraian']['prompt'] ?? 'Masukkan <b>URAIAN BELANJA</b> untuk dokumentasi ini:';
                $this->bot->sendMessage($chatId, $uraianPrompt);

                return;
            }

            $this->bot->sendMessage($chatId, 'Silakan kirim foto fisik barang atau nota terlebih dahulu.');

            return;
        }

        if ($step === 'waiting_foto_uraian') {
            $isRequired = $flow['uraian']['is_required'] ?? true;
            if (empty($text) || $text === '-' || $text === '/skip') {
                if ($isRequired) {
                    $this->bot->sendMessage($chatId, '⚠️ <b>Uraian Belanja wajib diisi:</b>');

                    return;
                }
                $text = 'Dokumentasi Barang BOSP';
            }

            $draft['uraian'] = $text;

            // Jika step toko aktif
            if ($flow['store']['is_enabled'] ?? false) {
                $conversation->update([
                    'step' => 'waiting_foto_toko',
                    'draft_data' => $draft,
                ]);
                $prompt = $flow['store']['prompt'] ?? "Masukkan <b>NAMA TOKO / SUMBER BARANG</b>:\n<i>(Ketik <code>-</code> atau <b>/skip</b> jika tidak ada)</i>";
                $this->bot->sendMessage($chatId, $prompt);

                return;
            }

            // Jika step nota aktif
            if ($flow['nota']['is_enabled'] ?? true) {
                $conversation->update([
                    'step' => 'waiting_foto_nota',
                    'draft_data' => $draft,
                ]);
                $prompt = $flow['nota']['prompt'] ?? "Masukkan <b>NOMOR NOTA</b>:\n<i>(Ketik <code>-</code> atau <b>/skip</b> jika belum ada)</i>";
                $this->bot->sendMessage($chatId, $prompt);

                return;
            }

            // Langsung konfirmasi
            $this->showConfirmationFoto($chatId, $conversation, $draft);

            return;
        }

        if ($step === 'waiting_foto_toko') {
            $isRequired = $flow['store']['is_required'] ?? false;
            if ($text === '-' || $text === '/skip') {
                if ($isRequired) {
                    $this->bot->sendMessage($chatId, '⚠️ <b>Nama Toko / Sumber Barang wajib diisi:</b>');

                    return;
                }
                $draft['nama_toko'] = null;
            } else {
                $draft['nama_toko'] = $text;
            }

            if ($flow['nota']['is_enabled'] ?? true) {
                $conversation->update([
                    'step' => 'waiting_foto_nota',
                    'draft_data' => $draft,
                ]);
                $prompt = $flow['nota']['prompt'] ?? "Masukkan <b>NOMOR NOTA</b>:\n<i>(Ketik <code>-</code> atau <b>/skip</b> jika belum ada)</i>";
                $this->bot->sendMessage($chatId, $prompt);

                return;
            }

            $this->showConfirmationFoto($chatId, $conversation, $draft);

            return;
        }

        if ($step === 'waiting_foto_nota') {
            $isRequired = $flow['nota']['is_required'] ?? false;
            if ($text === '-' || $text === '/skip') {
                if ($isRequired) {
                    $this->bot->sendMessage($chatId, '⚠️ <b>Nomor Nota wajib diisi:</b>');

                    return;
                }
                $draft['no_nota'] = null;
            } else {
                $draft['no_nota'] = $text;
            }

            $this->showConfirmationFoto($chatId, $conversation, $draft);

            return;
        }
    }

    /**
     * Menampilkan konfirmasi untuk alur foto dokumentasi.
     */
    protected function showConfirmationFoto(string $chatId, TelegramConversation $conversation, array $draft): void
    {
        $conversation->update([
            'step' => 'waiting_confirmation_foto',
            'draft_data' => $draft,
        ]);

        $photoCount = count($draft['photos'] ?? []);
        $fotoYear = $draft['fiscal_year'] ?? (int) date('Y');
        $msg = "📋 <b>KONFIRMASI DOKUMENTASI FOTO BOSP</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━━\n";
        $msg .= "• <b>Tahun Anggaran:</b> {$fotoYear}\n";
        $msg .= "• <b>Uraian:</b> {$draft['uraian']}\n";
        if (! empty($draft['nama_toko'])) {
            $msg .= "• <b>Toko:</b> {$draft['nama_toko']}\n";
        }
        $msg .= '• <b>No. Nota:</b> '.($draft['no_nota'] ?: '-')."\n";
        $msg .= "• <b>Total Foto:</b> {$photoCount} Foto\n";
        $msg .= "━━━━━━━━━━━━━━━━━━\n";
        $msg .= 'Simpan foto ini ke galeri Foto BOSP?';

        $keyboard = [
            [
                ['text' => '✅ Ya, Simpan Foto', 'callback_data' => 'confirm_foto:yes'],
                ['text' => '❌ Batalkan', 'callback_data' => 'confirm:no'],
            ],
        ];

        $this->bot->sendMessage($chatId, $msg, $keyboard);
    }

    /**
     * Finalisasi alur foto dokumentasi saja.
     */
    protected function finalizeFotoDokumentasi(string $chatId, TelegramConversation $conversation, User $user, ?int $messageId = null): void
    {
        $draft = $conversation->draft_data ?: [];
        $year = (int) ($draft['fiscal_year'] ?? date('Y'));

        $doc = BarangDokumentasi::create([
            'funding_source' => 'BOS',
            'year' => $year,
            'no_nota' => $draft['no_nota'] ?? null,
            'tanggal_terima' => now()->toDateString(),
            'uraian' => $draft['uraian'] ?? 'Dokumentasi Barang',
            'created_by' => $user->id,
        ]);

        $dir = "dokumentasi_barang/{$year}/{$doc->safe_nota_name}";
        $urutan = 0;
        $savedCount = 0;

        foreach ($draft['photos'] ?? [] as $fileId) {
            $tempFilename = 'foto_temp_'.Str::random(8).'.jpg';
            $tempPath = "{$dir}/{$tempFilename}";

            $downloaded = $this->bot->downloadFile($fileId, $tempPath);
            if ($downloaded) {
                $urutan++;
                $finalFilename = "foto_{$urutan}_".Str::random(6).'.jpg';
                $finalPath = "{$dir}/{$finalFilename}";
                Storage::disk('public')->move($tempPath, $finalPath);

                BarangDokumentasiFoto::create([
                    'barang_dokumentasi_id' => $doc->id,
                    'urutan' => $urutan,
                    'file_path' => $finalPath,
                    'keterangan' => $draft['uraian'],
                ]);
                $savedCount++;
            }
        }

        $conversation->delete();

        $msg = "🎉 <b>FOTO BOSP BERHASIL DISIMPAN!</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━━\n";
        $msg .= "• <b>Uraian:</b> {$draft['uraian']}\n";
        $msg .= '• <b>No. Nota:</b> '.($draft['no_nota'] ?: '-')."\n";
        $msg .= "• <b>Foto Tersimpan:</b> {$savedCount} Foto\n";
        $msg .= "━━━━━━━━━━━━━━━━━━\n";
        $msg .= 'Foto langsung muncul di menu <b>Foto BOSP</b> web MKB.';

        if ($messageId) {
            $this->bot->editMessageText($chatId, $messageId, $msg);
        } else {
            $this->bot->sendMessage($chatId, $msg);
        }
    }

    /**
     * Tampilkan pesan bantuan / menu.
     */
    protected function sendHelpMessage(string $chatId, User $user): void
    {
        if ($user->canBotFotoOnly()) {
            $msg = "👋 Halo <b>{$user->name}</b> ({$user->role_label})!\n";
            $msg .= "Selamat datang di <b>Dokumentasi Foto Belanja BOS SMK PGRI KRAS</b>.\n\n";
            $msg .= "📋 <b>DAFTAR PERINTAH:</b>\n";
            $msg .= "• <b>/foto</b> - Upload dokumentasi foto barang fisik BOSP\n";
            $msg .= "• <b>/batal</b> - Batalkan sesi upload foto yang aktif\n";
            $msg .= "• <b>/help</b> - Bantuan & informasi perintah\n";
            $msg .= "• <b>/logout</b> - Putus tautan akun dari Telegram ini\n\n";
            $msg .= '<i>Silakan ketik <b>/foto</b> untuk mulai mendokumentasikan fisik barang belanja.</i>';

            $this->bot->sendMessage($chatId, $msg);

            return;
        }

        if ($user->isKepalaSekolah()) {
            $msg = "👋 Halo <b>{$user->name}</b> (Kepala Sekolah)!\n";
            $msg .= "Selamat datang di <b>Monitoring Keuangan MKB SMK PGRI KRAS</b>.\n\n";
            $msg .= "📋 <b>DAFTAR PERINTAH:</b>\n";
            $msg .= "• <b>/saldo</b> - Cek saldo kas tunai BOSP & kasbon saat ini\n";
            $msg .= "• <b>/kasbon</b> - Cek rincian seluruh kasbon / panjar kerja aktif\n";
            $msg .= "• <b>/foto</b> - Upload dokumentasi foto barang fisik BOSP\n";
            $msg .= "• <b>/help</b> - Bantuan & informasi perintah\n";
            $msg .= "• <b>/logout</b> - Putus tautan akun dari Telegram ini\n\n";
            $msg .= '<i>Silakan ketik atau klik salah satu perintah di atas untuk memulai pemeriksaan.</i>';

            $this->bot->sendMessage($chatId, $msg);

            return;
        }

        $msg = "👋 Halo <b>{$user->name}</b> ({$user->role_label})!\n";
        $msg .= "Selamat datang di <b>Bot Keuangan MKB SMK PGRI KRAS</b>.\n\n";

        $activeCommands = Schema::hasTable('telegram_commands')
            ? TelegramCommand::active()->get()
            : collect();
        if ($activeCommands->isNotEmpty()) {
            $msg .= "📋 <b>DAFTAR PERINTAH:</b>\n";
            foreach ($activeCommands as $ac) {
                $msg .= "• <b>/{$ac->command}</b> - ".e($ac->description)."\n";
            }
            $msg .= "\n";
        } else {
            $msg .= "📋 <b>DAFTAR PERINTAH:</b>\n";
            $msg .= "• <b>/pengeluaran</b> - Catat pengeluaran kas BOSP\n";
            $msg .= "• <b>/pemasukan</b> - Catat penerimaan kas BOSP\n";
            $msg .= "• <b>/kasbon</b> - Cek rincian seluruh kasbon / panjar kerja aktif\n";
            $msg .= "• <b>/foto</b> - Upload dokumentasi foto barang fisik BOSP\n";
            $msg .= "• <b>/saldo</b> - Cek sisa saldo kas tunai saat ini\n";
            $msg .= "• <b>/batal</b> - Batalkan sesi pengisian transaksi yang aktif\n";
            $msg .= "• <b>/logout</b> - Putus tautan akun dari Telegram ini\n\n";
        }

        $msg .= '<i>Silakan ketik atau klik salah satu perintah di atas untuk memulai.</i>';

        $this->bot->sendMessage($chatId, $msg);
    }

    /**
     * Tampilkan informasi saldo kas tunai secara real-time.
     */
    protected function sendSaldoInfo(string|int $chatId, ?int $messageId = null): void
    {
        $bospMasuk = (float) KasTunai::where('funding_source', 'BOSP')->where('transaction_type', 'masuk')->sum('amount');
        $bospKeluar = (float) KasTunai::where('funding_source', 'BOSP')->where('transaction_type', 'keluar')->sum('amount');
        $bospSaldo = $bospMasuk - $bospKeluar;

        $openPanjars = KasTunai::where('funding_source', 'BOSP')
            ->where('category', 'panjar_kerja')
            ->whereIn('panjar_status', ['pending', 'partial'])
            ->with('childKass')
            ->get();
        $totalPanjarPending = (float) $openPanjars->sum(fn ($k) => $k->remaining_panjar_amount);
        $countPanjarPending = $openPanjars->count();
        $kasSiapPakai = $bospSaldo - $totalPanjarPending;

        $msg = "💰 <b>INFORMASI SALDO KAS TUNAI BOSP</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━━\n";
        $msg .= '📌 <b>Kas Tunai BOSP:</b> Rp '.number_format($bospSaldo, 0, ',', '.')."\n";
        $msg .= '  • Masuk : Rp '.number_format($bospMasuk, 0, ',', '.')."\n";
        $msg .= '  • Keluar: Rp '.number_format($bospKeluar, 0, ',', '.')."\n";

        $keyboard = [];
        if ($countPanjarPending > 0) {
            $msg .= "\n⏳ <b>Kasbon / Panjar Belum Lunas:</b> Rp ".number_format($totalPanjarPending, 0, ',', '.')." ({$countPanjarPending} transaksi)\n";
            $msg .= '✨ <b>Kas Siap Pakai:</b> Rp '.number_format($kasSiapPakai, 0, ',', '.')."\n";
            $msg .= "💡 <i>Ketik /kasbon untuk melihat rincian kasbon belum lunas.</i>\n";

            $keyboard[] = [
                ['text' => '📋 Cek Rincian Kasbon', 'callback_data' => 'kasbon_page:1'],
            ];
        }

        $msg .= "━━━━━━━━━━━━━━━━━━\n";
        $msg .= 'Ketik <b>/pengeluaran</b> atau <b>/pemasukan</b> untuk mencatat transaksi baru.';

        if ($messageId) {
            $this->bot->editMessageText($chatId, $messageId, $msg, ! empty($keyboard) ? $keyboard : null);
        } else {
            $this->bot->sendMessage($chatId, $msg, ! empty($keyboard) ? $keyboard : null);
        }
    }

    /**
     * Tampilkan rincian seluruh kasbon (panjar kerja) yang belum lunas.
     */
    protected function sendKasbonInfo(string|int $chatId, int $page = 1, ?int $messageId = null): void
    {
        $openPanjars = KasTunai::where('category', 'panjar_kerja')
            ->whereIn('panjar_status', ['pending', 'partial'])
            ->with(['guru', 'childKass'])
            ->orderBy('transaction_date', 'asc')
            ->get();

        if ($openPanjars->isEmpty()) {
            $msg = "📋 <b>RINCIAN SELURUH KASBON / PANJAR KERJA</b>\n";
            $msg .= "━━━━━━━━━━━━━━━━━━\n";
            $msg .= "✅ <b>Alhamdulillah, tidak ada kasbon yang aktif / terbuka!</b>\n\n";
            $msg .= "Seluruh kasbon dan panjar belanja telah diselesaikan (lunas).\n";
            $msg .= 'Ketik <b>/saldo</b> untuk cek saldo kas, atau <b>/pengeluaran</b> untuk transaksi baru.';

            if ($messageId) {
                $this->bot->editMessageText($chatId, $messageId, $msg);
            } else {
                $this->bot->sendMessage($chatId, $msg);
            }

            return;
        }

        $totalAwal = (float) $openPanjars->sum('amount');
        $totalSisa = (float) $openPanjars->sum(fn ($k) => $k->remaining_panjar_amount);
        $totalSettled = (float) $openPanjars->sum(fn ($k) => $k->settled_panjar_amount);
        $totalCount = $openPanjars->count();
        $totalPersons = $openPanjars->map(fn ($k) => $k->guru?->full_name_with_title ?: ($k->person_name ?: 'Staf / Guru'))->unique()->count();

        $perPage = 5;
        $totalPages = (int) ceil($totalCount / $perPage);
        $currentPage = max(1, min($page, $totalPages));
        $offset = ($currentPage - 1) * $perPage;
        $pagePanjars = $openPanjars->slice($offset, $perPage);

        $msg = "📋 <b>RINCIAN SELURUH KASBON (PANJAR BELANJA)</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━━\n";
        $msg .= '⏳ <b>Total Sisa Kasbon:</b> Rp '.number_format($totalSisa, 0, ',', '.')."\n";
        $msg .= '💵 <b>Total Pengajuan Awal:</b> Rp '.number_format($totalAwal, 0, ',', '.')."\n";
        if ($totalSettled > 0) {
            $msg .= '✅ <b>Sudah Diselesaikan:</b> Rp '.number_format($totalSettled, 0, ',', '.')."\n";
        }
        $msg .= "👥 <b>Penanggung Jawab:</b> {$totalPersons} orang ({$totalCount} transaksi)";
        if ($totalPages > 1) {
            $msg .= " • <i>Hal {$currentPage}/{$totalPages}</i>";
        }
        $msg .= "\n━━━━━━━━━━━━━━━━━━\n\n";

        $idx = $offset + 1;
        foreach ($pagePanjars as $p) {
            $idStr = $p->id_display;
            $guruName = $p->guru?->full_name_with_title ?: ($p->person_name ?: 'Staf / Guru');
            $funding = $p->funding_source ?: 'BOSP';
            $tgl = $p->transaction_date ? $p->transaction_date->translatedFormat('d M Y') : '-';
            $days = $p->transaction_date ? (int) $p->transaction_date->diffInDays(now()) : 0;

            if ($days <= 3) {
                $agingBadge = "🟢 Baru ({$days} hr)";
            } elseif ($days <= 7) {
                $agingBadge = "🟡 Perhatian ({$days} hr)";
            } else {
                $agingBadge = "🔴 Menunggak ({$days} hr)";
            }

            $rem = $p->remaining_panjar_amount;
            $statusStr = ($p->panjar_status === 'partial') ? 'Dicicil Sebagian' : 'Belum SPJ';

            $msg .= "<b>{$idx}. {$idStr}</b> • <b>[{$funding}]</b>\n";
            $msg .= "   👤 <b>PJ:</b> {$guruName}\n";
            if (! empty($p->description)) {
                $msg .= '   📝 <b>Untuk:</b> '.e($p->description)."\n";
            }
            $msg .= "   📅 <b>Tgl:</b> {$tgl} | {$agingBadge}\n";
            $msg .= '   💵 <b>Awal:</b> Rp '.number_format($p->amount, 0, ',', '.')."\n";
            if ($p->settled_panjar_amount > 0) {
                $msg .= '   🔄 <b>Sudah SPJ:</b> Rp '.number_format($p->settled_panjar_amount, 0, ',', '.')."\n";
            }
            $msg .= '   ⚠️ <b>Sisa: Rp '.number_format($rem, 0, ',', '.')."</b> [⏳ {$statusStr}]\n\n";
            $idx++;
        }

        $msg .= '<i>Klik tombol di bawah untuk melihat rincian detail atau navigasi:</i>';

        $keyboard = [];

        // Tombol rincian detail kasbon per item yang tampil di halaman ini
        $detailButtons = [];
        foreach ($pagePanjars as $p) {
            $detailButtons[] = [
                'text' => '🔍 '.$p->id_display,
                'callback_data' => "kasbon_detail:{$p->id}",
            ];
        }
        foreach (array_chunk($detailButtons, 2) as $row) {
            $keyboard[] = $row;
        }

        // Pagination buttons if multi-page
        if ($totalPages > 1) {
            $navRow = [];
            if ($currentPage > 1) {
                $prev = $currentPage - 1;
                $navRow[] = ['text' => '◀️ Hal '.$prev, 'callback_data' => "kasbon_page:{$prev}"];
            }
            $navRow[] = ['text' => "📄 {$currentPage}/{$totalPages}", 'callback_data' => "kasbon_page:{$currentPage}"];
            if ($currentPage < $totalPages) {
                $next = $currentPage + 1;
                $navRow[] = ['text' => 'Hal '.$next.' ▶️', 'callback_data' => "kasbon_page:{$next}"];
            }
            $keyboard[] = $navRow;
        }

        // Action row: Segarkan & Saldo
        $keyboard[] = [
            ['text' => '🔄 Segarkan', 'callback_data' => "kasbon_page:{$currentPage}"],
            ['text' => '💰 Saldo Kas', 'callback_data' => 'kasbon_saldo'],
        ];

        if ($messageId) {
            $this->bot->editMessageText($chatId, $messageId, $msg, $keyboard);
        } else {
            $this->bot->sendMessage($chatId, $msg, $keyboard);
        }
    }

    /**
     * Tampilkan rincian mendalam dari satu transaksi kasbon.
     */
    protected function sendKasbonDetail(string|int $chatId, int $kasbonId, ?int $messageId = null): void
    {
        $kas = KasTunai::where('category', 'panjar_kerja')
            ->with(['guru', 'childKass'])
            ->find($kasbonId);

        if (! $kas) {
            $this->bot->sendMessage($chatId, '⚠️ Transaksi kasbon tidak ditemukan atau sudah dihapus.');

            return;
        }

        $idDisplay = $kas->id_display;
        $guruName = $kas->guru?->full_name_with_title ?: ($kas->person_name ?: 'Staf / Guru');
        $phone = $kas->guru?->phone ? " ({$kas->guru->phone})" : '';
        $funding = $kas->funding_source ?: 'BOSP';
        $tgl = $kas->transaction_date ? $kas->transaction_date->translatedFormat('d F Y') : '-';
        $days = $kas->transaction_date ? (int) $kas->transaction_date->diffInDays(now()) : 0;

        if ($days <= 3) {
            $agingText = "🟢 Baru ({$days} hari lalu)";
        } elseif ($days <= 7) {
            $agingText = "🟡 Perhatian ({$days} hari lalu)";
        } else {
            $agingText = "🔴 Menunggak ({$days} hari lalu)";
        }

        $statusLabel = match ($kas->panjar_status) {
            'settled' => '✅ Lunas / Selesai SPJ',
            'partial' => '⏳ Dicicil Sebagian (Belum Lunas)',
            default => '⏳ Belum Diselesaikan / Belum SPJ',
        };

        $msg = "🔍 <b>DETAIL KASBON {$idDisplay}</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━━\n";
        $msg .= "👤 <b>Penanggung Jawab:</b> {$guruName}{$phone}\n";
        $msg .= "📌 <b>Sumber Dana:</b> {$funding}\n";
        $msg .= "📅 <b>Tanggal Penyerahan:</b> {$tgl}\n";
        $msg .= "⏱️ <b>Status Aging:</b> {$agingText}\n";
        if (! empty($kas->description)) {
            $msg .= '📝 <b>Keperluan:</b> '.e($kas->description)."\n";
        }
        $msg .= "\n💰 <b>STATUS KEUANGAN:</b>\n";
        $msg .= '• Nominal Kasbon Awal: <b>Rp '.number_format($kas->amount, 0, ',', '.')."</b>\n";
        $msg .= '• Sudah Di-SPJ / Dikembalikan: Rp '.number_format($kas->settled_panjar_amount, 0, ',', '.')."\n";
        $msg .= '• Sisa Tanggungan: <b>Rp '.number_format($kas->remaining_panjar_amount, 0, ',', '.')."</b>\n";
        $msg .= "• Status Kasbon: <b>{$statusLabel}</b>\n";

        // Riwayat SPJ / Pengembalian Sisa (childKass)
        if ($kas->childKass && $kas->childKass->isNotEmpty()) {
            $msg .= "\n📜 <b>RIWAYAT PENGEMBALIAN / SPJ:</b>\n";
            $cIdx = 1;
            foreach ($kas->childKass as $child) {
                $cDate = $child->transaction_date ? $child->transaction_date->translatedFormat('d/m/Y') : '-';
                $cNominal = number_format($child->amount, 0, ',', '.');
                $cKet = e($child->description ?: $child->category_label);
                $msg .= "{$cIdx}. [{$cDate}] Rp {$cNominal}\n   <i>{$cKet}</i>\n";
                $cIdx++;
            }
        }

        $msg .= "━━━━━━━━━━━━━━━━━━\n";
        $msg .= '<i>Ketik /kasbon untuk kembali ke daftar seluruh kasbon.</i>';

        $keyboard = [
            [
                ['text' => '🔙 Kembali ke Daftar Kasbon', 'callback_data' => 'kasbon_page:1'],
                ['text' => '🔄 Segarkan', 'callback_data' => "kasbon_detail:{$kas->id}"],
            ],
        ];

        if ($messageId) {
            $this->bot->editMessageText($chatId, $messageId, $msg, $keyboard);
        } else {
            $this->bot->sendMessage($chatId, $msg, $keyboard);
        }
    }
}
