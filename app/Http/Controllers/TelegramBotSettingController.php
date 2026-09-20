<?php

namespace App\Http\Controllers;

use App\Models\TelegramCommand;
use App\Models\TelegramConversation;
use App\Services\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TelegramBotSettingController extends Controller
{
    public function __construct(
        protected TelegramBotService $telegram
    ) {}

    /**
     * Halaman Utama Pengaturan Bot Telegram.
     */
    public function index(): View
    {
        $botMe = $this->telegram->getMe()['result'] ?? null;
        $webhookInfo = $this->telegram->getWebhookInfo()['result'] ?? null;
        $commands = TelegramCommand::orderBy('sort_order', 'asc')->orderBy('id', 'asc')->get();
        $syncedCommands = $this->telegram->getMyCommands()['result'] ?? [];

        $conversations = TelegramConversation::with('user')
            ->orderBy('updated_at', 'desc')
            ->take(30)
            ->get();

        return view('telegram.index', compact('botMe', 'webhookInfo', 'commands', 'syncedCommands', 'conversations'));
    }

    /**
     * Pendaftaran / Sinkronisasi Webhook URL ke Telegram.
     */
    public function syncWebhook(Request $request): JsonResponse
    {
        $url = $request->filled('webhook_url')
            ? $request->webhook_url
            : (env('APP_URL') && ! Str::contains(env('APP_URL'), ['localhost', '127.0.0.1'])
                ? rtrim(env('APP_URL'), '/').'/telegram/webhook'
                : 'https://mkb.sudahonline.my.id/telegram/webhook');

        $result = $this->telegram->setWebhook($url);

        if ($result && ($result['ok'] ?? false)) {
            return response()->json([
                'message' => 'Webhook berhasil didaftarkan ke: '.$url,
                'result' => $result,
            ]);
        }

        return response()->json([
            'message' => 'Gagal mendaftarkan webhook: '.($result['description'] ?? 'Koneksi gagal.'),
        ], 400);
    }

    /**
     * Hapus Webhook Telegram.
     */
    public function deleteWebhook(): JsonResponse
    {
        $result = $this->telegram->deleteWebhook();

        if ($result && ($result['ok'] ?? false)) {
            return response()->json([
                'message' => 'Webhook Telegram berhasil dihapus.',
            ]);
        }

        return response()->json([
            'message' => 'Gagal menghapus webhook: '.($result['description'] ?? 'Terjadi kesalahan.'),
        ], 400);
    }

    /**
     * Kirim pesan uji coba ke Chat ID tertentu.
     */
    public function testSend(Request $request): JsonResponse
    {
        $request->validate([
            'chat_id' => 'required|string',
            'message' => 'required|string',
        ]);

        $result = $this->telegram->sendMessage($request->chat_id, $request->message);

        if ($result && ($result['ok'] ?? false)) {
            return response()->json([
                'message' => 'Pesan uji coba berhasil terkirim ke Chat ID: '.$request->chat_id,
            ]);
        }

        return response()->json([
            'message' => 'Gagal mengirim pesan: '.($result['description'] ?? 'Pastikan Chat ID sudah pernah menekan /start pada bot.'),
        ], 400);
    }

    /**
     * Sinkronkan seluruh perintah aktif ke Telegram Bot Menu (setMyCommands).
     */
    public function syncCommandsToTelegram(): JsonResponse
    {
        $activeCommands = TelegramCommand::active()->get();

        $payload = [];
        foreach ($activeCommands as $cmd) {
            $payload[] = [
                'command' => strtolower($cmd->command),
                'description' => Str::limit($cmd->description, 255),
            ];
        }

        $result = $this->telegram->setMyCommands($payload);

        if ($result && ($result['ok'] ?? false)) {
            return response()->json([
                'message' => count($payload).' Perintah resmi berhasil disinkronkan ke Menu Bot Telegram!',
                'count' => count($payload),
            ]);
        }

        return response()->json([
            'message' => 'Gagal sinkronisasi perintah ke Telegram: '.($result['description'] ?? 'Koneksi gagal.'),
        ], 400);
    }

    /**
     * Tambah Perintah Kustom Baru.
     */
    public function storeCommand(Request $request): JsonResponse
    {
        $cleanCommand = strtolower(ltrim(trim($request->command), '/'));
        $request->merge(['command' => $cleanCommand]);

        $validated = $request->validate([
            'command' => 'required|string|regex:/^[a-z0-9_]{1,32}$/|unique:telegram_commands,command',
            'description' => 'required|string|max:255',
            'response_text' => 'required|string',
            'sort_order' => 'nullable|integer',
        ]);

        $maxSort = TelegramCommand::max('sort_order') ?? 0;
        $command = TelegramCommand::create([
            'command' => $validated['command'],
            'description' => $validated['description'],
            'response_type' => 'text',
            'response_text' => $validated['response_text'],
            'is_active' => true,
            'sort_order' => $validated['sort_order'] ?? ($maxSort + 1),
        ]);

        return response()->json([
            'message' => "Perintah '/{$command->command}' berhasil dibuat.",
            'command' => $command,
        ]);
    }

    /**
     * Update Perintah.
     */
    public function updateCommand(Request $request, TelegramCommand $command): JsonResponse
    {
        if ($command->isSystem()) {
            $validated = $request->validate([
                'description' => 'required|string|max:255',
                'sort_order' => 'nullable|integer',
            ]);

            $command->update([
                'description' => $validated['description'],
                'sort_order' => $validated['sort_order'] ?? $command->sort_order,
            ]);
        } else {
            $cleanCommand = strtolower(ltrim(trim($request->command), '/'));
            $request->merge(['command' => $cleanCommand]);

            $validated = $request->validate([
                'command' => 'required|string|regex:/^[a-z0-9_]{1,32}$/|unique:telegram_commands,command,'.$command->id,
                'description' => 'required|string|max:255',
                'response_text' => 'required|string',
                'sort_order' => 'nullable|integer',
            ]);

            $command->update([
                'command' => $validated['command'],
                'description' => $validated['description'],
                'response_text' => $validated['response_text'],
                'sort_order' => $validated['sort_order'] ?? $command->sort_order,
            ]);
        }

        return response()->json([
            'message' => "Perintah '/{$command->command}' berhasil diperbarui.",
            'command' => $command,
        ]);
    }

    /**
     * Toggle status aktif perintah.
     */
    public function toggleCommand(TelegramCommand $command): JsonResponse
    {
        $command->is_active = ! $command->is_active;
        $command->save();

        $statusText = $command->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json([
            'message' => "Perintah '/{$command->command}' berhasil {$statusText}.",
            'is_active' => $command->is_active,
        ]);
    }

    /**
     * Hapus Perintah Kustom.
     */
    public function destroyCommand(TelegramCommand $command): JsonResponse
    {
        if ($command->isSystem()) {
            return response()->json([
                'message' => 'Perintah sistem bawaan tidak dapat dihapus.',
            ], 422);
        }

        $cmdName = $command->command;
        $command->delete();

        return response()->json([
            'message' => "Perintah '/{$cmdName}' berhasil dihapus.",
        ]);
    }

    /**
     * Ambil konfigurasi alur langkah (workflow flow) untuk perintah tertentu.
     */
    public function getFlowConfig(TelegramCommand $command): JsonResponse
    {
        if (! $command->hasFlowConfig()) {
            return response()->json([
                'message' => "Perintah '/{$command->command}' tidak mendukung kustomisasi alur langkah.",
            ], 422);
        }

        return response()->json([
            'command' => $command->command,
            'description' => $command->description,
            'flow' => $command->getEffectiveFlow(),
            'is_customized' => ! empty($command->flow_config),
        ]);
    }

    /**
     * Simpan perubahan konfigurasi alur langkah.
     */
    public function updateFlowConfig(Request $request, TelegramCommand $command): JsonResponse
    {
        if (! $command->hasFlowConfig()) {
            return response()->json([
                'message' => "Perintah '/{$command->command}' tidak mendukung kustomisasi alur langkah.",
            ], 422);
        }

        $validated = $request->validate([
            'steps' => 'required|array',
            'steps.*.is_enabled' => 'required|boolean',
            'steps.*.is_required' => 'required|boolean',
            'steps.*.prompt' => 'required|string',
        ]);

        $defaultFlow = TelegramCommand::defaultFlow($command->command);
        $savedFlow = [];

        foreach ($validated['steps'] as $key => $stepData) {
            if (isset($defaultFlow[$key])) {
                // Jangan izinkan step amount dimatikan pada alur transaksi
                if (in_array($command->command, ['transaksi', 'pengeluaran', 'pemasukan']) && $key === 'amount') {
                    $stepData['is_enabled'] = true;
                    $stepData['is_required'] = true;
                }

                $savedFlow[$key] = [
                    'key' => $key,
                    'title' => $defaultFlow[$key]['title'],
                    'description' => $defaultFlow[$key]['description'],
                    'is_enabled' => (bool) $stepData['is_enabled'],
                    'is_required' => (bool) $stepData['is_required'],
                    'prompt' => trim($stepData['prompt']),
                    'help_text' => $defaultFlow[$key]['help_text'],
                ];
            }
        }

        $command->update([
            'flow_config' => $savedFlow,
        ]);

        return response()->json([
            'message' => "Alur langkah perintah '/{$command->command}' berhasil diperbarui.",
            'flow' => $command->getEffectiveFlow(),
        ]);
    }

    /**
     * Reset alur langkah perintah ke bawaan sistem.
     */
    public function resetFlowConfig(TelegramCommand $command): JsonResponse
    {
        if (! $command->hasFlowConfig()) {
            return response()->json([
                'message' => "Perintah '/{$command->command}' tidak mendukung kustomisasi alur langkah.",
            ], 422);
        }

        $command->update(['flow_config' => null]);

        return response()->json([
            'message' => "Alur langkah '/{$command->command}' berhasil dikembalikan ke standar bawaan sistem.",
            'flow' => $command->getEffectiveFlow(),
        ]);
    }
}
