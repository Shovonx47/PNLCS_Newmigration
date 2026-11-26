<?php

namespace Modules\Addons\TelegramBot\Services;

use App\Models\AddonSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The client-facing bot.
 *
 * A second bot from the same operator account as the admin notifier, or the
 * same bot with a separate webhook - the token is a setting, so either works.
 * The admin notifier's chat ids are never used here: a customer's chat is
 * proved by the one-time token in their own dashboard.
 */
class TelegramBot
{
    public function token(): string
    {
        return trim((string) AddonSetting::getSetting('telegram_bot', 'bot_token', ''));
    }

    public function username(): string
    {
        return ltrim(trim((string) AddonSetting::getSetting('telegram_bot', 'bot_username', '')), '@');
    }

    public function secret(): string
    {
        return trim((string) AddonSetting::getSetting('telegram_bot', 'webhook_secret', ''));
    }

    public function enabled(): bool
    {
        return AddonSetting::getSetting('telegram_bot', 'enabled', '0') === '1' && $this->token() !== '';
    }

    public function deepLink(string $token): string
    {
        return 'https://t.me/'.$this->username().'?start='.$token;
    }

    /**
     * @return array{ok: bool, error: string}
     */
    public function send(string $chatId, string $html): array
    {
        if (! $this->enabled() || $chatId === '') {
            return ['ok' => false, 'error' => 'The bot is not configured.'];
        }

        try {
            $response = Http::timeout(15)->asJson()->post(
                'https://api.telegram.org/bot'.$this->token().'/sendMessage',
                [
                    'chat_id' => $chatId,
                    'text' => $html,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('Telegram send failed: '.$e->getMessage());

            return ['ok' => false, 'error' => 'Could not reach Telegram.'];
        }

        if ($response->successful() && $response->json('ok') === true) {
            return ['ok' => true, 'error' => ''];
        }

        return ['ok' => false, 'error' => (string) ($response->json('description') ?? 'HTTP '.$response->status())];
    }

    /**
     * Point Telegram at this installation. Run from the command line, because
     * the address must be public and the operator should see the answer.
     *
     * @return array{ok: bool, message: string}
     */
    public function registerWebhook(string $url): array
    {
        if ($this->token() === '') {
            return ['ok' => false, 'message' => 'Add the bot token first.'];
        }

        try {
            $response = Http::timeout(20)->asJson()->post(
                'https://api.telegram.org/bot'.$this->token().'/setWebhook',
                [
                    'url' => $url,
                    'secret_token' => $this->secret() ?: null,
                    'allowed_updates' => ['message'],
                ]
            );
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Could not reach Telegram: '.$e->getMessage()];
        }

        return [
            'ok' => $response->json('ok') === true,
            'message' => (string) ($response->json('description') ?? 'HTTP '.$response->status()),
        ];
    }
}
