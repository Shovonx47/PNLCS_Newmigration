<?php

namespace Modules\Addons\WhatsappPro\Services;

use App\Support\BdPhone;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Addons\WhatsappPro\Models\WaSettings;

/**
 * The Softcents WhatsApp gateway.
 *
 * The legacy Message class disabled TLS verification and timeouts ran to 30
 * seconds. Certificates are verified here and the timeout is short: a queued
 * message that cannot be delivered quickly is worth retrying, not worth
 * holding a worker.
 */
class WhatsappClient
{
    public function __construct(private ?WaSettings $settings = null)
    {
        $this->settings ??= WaSettings::current();
    }

    /**
     * @return array{connected: bool, reason: string}
     */
    public function connection(): array
    {
        if (! $this->settings->configured()) {
            return ['connected' => false, 'reason' => 'Add the gateway API key and sender number.'];
        }

        // The gateway takes up to half a minute to answer this (it asks the
        // phone), so the screen must not wait for it on every load. A good
        // answer is kept for ten minutes, a failure for one.
        $cached = Cache::get('whatsapp:connection');

        if (is_array($cached)) {
            return $cached;
        }

        // The gateway answers "is this number on WhatsApp" for its own sender,
        // which is the cheapest proof that both the key and the number work.
        $result = $this->post('/check-number', [
            'api_key' => (string) $this->settings->api_key,
            'sender' => BdPhone::gateway($this->settings->sender),
            'number' => BdPhone::gateway($this->settings->sender),
        ], 45);

        $connection = ($result['success'] ?? false)
            ? ['connected' => true, 'reason' => 'Gateway reachable.']
            : ['connected' => false, 'reason' => $result['error'] ?: 'The gateway refused the sender number.'];

        Cache::put('whatsapp:connection', $connection, $connection['connected'] ? now()->addMinutes(10) : now()->addMinute());

        return $connection;
    }

    /**
     * @return array{success: bool, error: string}
     */
    public function sendText(string $number, string $message): array
    {
        $to = BdPhone::gateway($number);

        if ($to === '') {
            return ['success' => false, 'error' => 'Not a valid Bangladeshi mobile number.'];
        }

        if (trim($message) === '') {
            return ['success' => false, 'error' => 'Refusing to send an empty message.'];
        }

        $payload = [
            'api_key' => (string) $this->settings->api_key,
            'sender' => BdPhone::gateway($this->settings->sender),
            'number' => $to,
            'message' => $message,
        ];

        $footer = trim((string) $this->settings->footer);
        if ($footer !== '') {
            $payload['footer'] = $footer;
        }

        return $this->post('/send-message', $payload);
    }

    /**
     * @return array{success: bool, error: string}
     */
    public function sendMedia(string $number, string $mediaUrl, string $mediaType, string $caption = '', bool $viewOnce = false): array
    {
        $to = BdPhone::gateway($number);

        if ($to === '' || $mediaUrl === '') {
            return ['success' => false, 'error' => 'A number and a media URL are required.'];
        }

        $payload = [
            'api_key' => (string) $this->settings->api_key,
            'sender' => BdPhone::gateway($this->settings->sender),
            'number' => $to,
            'media_type' => $mediaType,
            'caption' => $caption,
            'url' => $mediaUrl,
        ];

        if ($viewOnce && in_array($mediaType, ['image', 'video'], true)) {
            $payload['viewonce'] = true;
        }

        $footer = trim((string) $this->settings->footer);
        if ($footer !== '') {
            $payload['footer'] = $footer;
        }

        return $this->post('/send-media', $payload);
    }

    /**
     * @return array{success: bool, error: string, raw: array}
     */
    private function post(string $path, array $payload, int $timeout = 20): array
    {
        try {
            $response = Http::timeout($timeout)->acceptJson()->asJson()->post(
                rtrim((string) $this->settings->api_domain, '/').$path,
                $payload
            );
        } catch (\Throwable $e) {
            Log::warning('WhatsApp gateway unreachable: '.$e->getMessage());

            return ['success' => false, 'error' => 'Could not reach the WhatsApp gateway.', 'raw' => []];
        }

        $data = $response->json();
        $data = is_array($data) ? $data : [];

        $flag = $data['status'] ?? null;
        $ok = $flag === true || $flag === 1 || $flag === '1' || $flag === 'true'
            || (string) $flag === '200' || ($data['success'] ?? false) === true;

        return [
            'success' => $ok,
            'error' => $ok ? '' : (string) ($data['message'] ?? $data['msg'] ?? 'Gateway returned HTTP '.$response->status()),
            'raw' => $data,
        ];
    }
}
