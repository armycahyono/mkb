<?php

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('telegram:set-webhook {url? : Custom webhook URL} {--info : Tampilkan status webhook saat ini} {--delete : Hapus webhook}')]
#[Description('Mendaftarkan atau memeriksa Webhook Telegram Bot')]
class TelegramSetWebhookCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(TelegramBotService $bot)
    {
        if ($this->option('info')) {
            $info = $bot->getWebhookInfo();
            $this->info('Telegram Webhook Info:');
            $this->line(json_encode($info, JSON_PRETTY_PRINT));

            return 0;
        }

        if ($this->option('delete')) {
            $res = $bot->deleteWebhook();
            $this->info('Webhook deleted: '.json_encode($res));

            return 0;
        }

        $url = $this->argument('url') ?: 'https://mkb.sudahonline.my.id/telegram/webhook';

        $this->info("Setting Telegram Webhook to: {$url}");
        $res = $bot->setWebhook($url);

        if ($res && ($res['ok'] ?? false)) {
            $this->info('SUCCESS: '.($res['description'] ?? 'Webhook was set'));
        } else {
            $this->error('FAILED: '.json_encode($res));
        }

        return 0;
    }
}
