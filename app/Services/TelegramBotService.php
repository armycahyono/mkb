<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TelegramBotService
{
    protected string $token;

    protected string $baseUrl;

    public function __construct(?string $token = null)
    {
        $this->token = $token ?: (string) config('services.telegram.bot_token', env('TELEGRAM_BOT_TOKEN'));
        $this->baseUrl = "https://api.telegram.org/bot{$this->token}";
    }

    /**
     * Kirim pesan teks ke chat Telegram dengan dukungan HTML parse_mode & Inline Keyboard.
     */
    public function sendMessage(string|int $chatId, string $text, ?array $inlineKeyboard = null, string $parseMode = 'HTML'): ?array
    {
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode,
        ];

        if (! empty($inlineKeyboard)) {
            $payload['reply_markup'] = json_encode([
                'inline_keyboard' => $inlineKeyboard,
            ]);
        }

        return $this->request('sendMessage', $payload);
    }

    /**
     * Edit teks pesan Telegram yang sudah dikirim (misal setelah tombol diklik).
     */
    public function editMessageText(string|int $chatId, int $messageId, string $text, ?array $inlineKeyboard = null, string $parseMode = 'HTML'): ?array
    {
        $payload = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => $parseMode,
        ];

        if ($inlineKeyboard !== null) {
            $payload['reply_markup'] = json_encode([
                'inline_keyboard' => $inlineKeyboard,
            ]);
        }

        return $this->request('editMessageText', $payload);
    }

    /**
     * Konfirmasi Callback Query (agar tombol inline tidak muter terus).
     */
    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null, bool $showAlert = false): ?array
    {
        $payload = [
            'callback_query_id' => $callbackQueryId,
        ];

        if ($text) {
            $payload['text'] = $text;
            $payload['show_alert'] = $showAlert;
        }

        return $this->request('answerCallbackQuery', $payload);
    }

    /**
     * Ambil metadata file dari server Telegram berdasarkan file_id.
     */
    public function getFile(string $fileId): ?array
    {
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $response = $this->request('getFile', ['file_id' => $fileId]);
            if ($response && ! empty($response['result'])) {
                return $response['result'];
            }
            usleep(300000); // 300ms delay before retry
        }

        return null;
    }

    /**
     * Download file foto dari server Telegram dan simpan ke Storage disk 'public'.
     */
    public function downloadFile(string $fileId, string $destinationPath): ?string
    {
        try {
            $fileMeta = $this->getFile($fileId);
            if (! $fileMeta || empty($fileMeta['file_path'])) {
                Log::error("TelegramBot: File path not found for file_id: {$fileId}");

                return null;
            }

            $telegramFilePath = $fileMeta['file_path'];
            $downloadUrl = "https://api.telegram.org/file/bot{$this->token}/{$telegramFilePath}";

            $fileResponse = Http::timeout(45)->retry(3, 1000)->get($downloadUrl);
            if (! $fileResponse->successful()) {
                Log::error("TelegramBot: Failed to download file from: {$downloadUrl}");

                return null;
            }

            Storage::disk('public')->put($destinationPath, $fileResponse->body());

            return $destinationPath;
        } catch (\Exception $e) {
            Log::error('TelegramBot downloadFile error: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Mendaftarkan URL Webhook ke Telegram.
     */
    public function setWebhook(string $url): ?array
    {
        return $this->request('setWebhook', [
            'url' => $url,
            'drop_pending_updates' => true,
        ]);
    }

    /**
     * Mendapatkan informasi status Webhook saat ini.
     */
    public function getWebhookInfo(): ?array
    {
        return $this->request('getWebhookInfo');
    }

    /**
     * Menghapus Webhook Telegram.
     */
    public function deleteWebhook(): ?array
    {
        return $this->request('deleteWebhook', [
            'drop_pending_updates' => true,
        ]);
    }

    /**
     * Mengambil informasi bot saat ini (username, nama, ID).
     */
    public function getMe(): ?array
    {
        return $this->request('getMe');
    }

    /**
     * Mengambil daftar perintah resmi yang saat ini terdaftar di Telegram API.
     */
    public function getMyCommands(): ?array
    {
        return $this->request('getMyCommands');
    }

    /**
     * Mendaftarkan atau memperbarui daftar perintah resmi ke Telegram API (muncul di tombol menu bot).
     * Format: [['command' => 'transaksi', 'description' => '...'], ...]
     */
    public function setMyCommands(array $commands): ?array
    {
        return $this->request('setMyCommands', [
            'commands' => json_encode($commands),
        ]);
    }

    /**
     * Menghapus seluruh perintah resmi dari menu bot Telegram.
     */
    public function deleteMyCommands(): ?array
    {
        return $this->request('deleteMyCommands');
    }

    /**
     * Eksekusi HTTP POST ke Telegram Bot API.
     */
    protected function request(string $endpoint, array $data = []): ?array
    {
        try {
            $response = Http::timeout(15)->post("{$this->baseUrl}/{$endpoint}", $data);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning("TelegramBot API Error on {$endpoint}: ".$response->body());

            return $response->json();
        } catch (\Exception $e) {
            Log::error("TelegramBot API Exception on {$endpoint}: ".$e->getMessage());

            return null;
        }
    }
}
