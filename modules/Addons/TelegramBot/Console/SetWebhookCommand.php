<?php

namespace Modules\Addons\TelegramBot\Console;

use Illuminate\Console\Command;
use Modules\Addons\TelegramBot\Services\TelegramBot;

class SetWebhookCommand extends Command
{
    protected $signature = 'pnlcs:telegram-webhook {--url= : The public address of /telegram/webhook}';

    protected $description = 'Point the client Telegram bot at this installation';

    public function handle(TelegramBot $bot): int
    {
        $url = (string) ($this->option('url') ?: url('/telegram/webhook'));

        $result = $bot->registerWebhook($url);

        $result['ok'] ? $this->info('Webhook set: '.$url) : $this->error('Refused: '.$result['message']);

        return $result['ok'] ? self::SUCCESS : self::FAILURE;
    }
}
