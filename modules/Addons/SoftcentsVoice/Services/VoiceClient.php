<?php

namespace Modules\Addons\SoftcentsVoice\Services;

use App\Support\BdPhone;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Addons\SoftcentsVoice\Models\VoiceSettings;

/**
 * vsms.softcents.com.
 *
 * The legacy VoiceClient used curl with CURLOPT_SSL_VERIFYPEER false on every
 * call, including the one that carries the bearer token. Http here verifies the
 * certificate; the token never travels over an unchecked connection.
 */
class VoiceClient
{
    public function __construct(private ?VoiceSettings $settings = null)
    {
        $this->settings ??= VoiceSettings::current();
    }

    public function configured(): bool
    {
        return $this->settings->configured();
    }

    /** @return array{success: bool, balance: string, error: string} */
    public function balance(): array
    {
        $result = $this->request('GET', 'account/balance');

        if (! $result['success']) {
            return ['success' => false, 'balance' => '0', 'error' => $result['error']];
        }

        $data = $result['data'];
        $balance = $data['balance'] ?? $data['credit'] ?? null;

        return [
            'success' => $balance !== null,
            'balance' => $balance !== null ? (string) $balance : '0',
            'error' => $balance !== null ? '' : 'The gateway did not return a balance.',
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function audios(): array
    {
        $result = $this->request('GET', 'audio');

        return $result['success'] && is_array($result['data']) ? $result['data'] : [];
    }

    /** @return array<int, array<string, mixed>> */
    public function callers(): array
    {
        $result = $this->request('GET', 'callers');

        return $result['success'] && is_array($result['data']) ? $result['data'] : [];
    }

    /**
     * @return array{success: bool, call_id: string, error: string}
     */
    public function sendCall(string $phone, string $audioId, string $callerId): array
    {
        $to = BdPhone::e164($phone);

        if ($to === '' || $audioId === '') {
            return ['success' => false, 'call_id' => '', 'error' => 'A phone number and an audio id are required.'];
        }

        $result = $this->request('POST', 'calls', [
            'caller_id' => (int) $callerId,
            'audio_id' => (int) $audioId,
            'phone_number' => $to,
        ]);

        return [
            'success' => $result['success'],
            'call_id' => (string) ($result['data']['id'] ?? $result['data']['call_id'] ?? ''),
            'error' => $result['error'],
        ];
    }

    /**
     * @return array{success: bool, data: array<string, mixed>, error: string}
     */
    public function request(string $method, string $path, array $payload = []): array
    {
        if (! $this->configured()) {
            return ['success' => false, 'data' => [], 'error' => 'Voice credentials are not configured.'];
        }

        $url = rtrim((string) $this->settings->api_base_url, '/').'/'.ltrim($path, '/');

        try {
            $client = Http::timeout(20)->acceptJson()->withToken((string) $this->settings->api_token);
            $response = $method === 'POST' ? $client->asJson()->post($url, $payload) : $client->get($url);
        } catch (\Throwable $e) {
            Log::warning('Voice gateway unreachable: '.$e->getMessage());

            return ['success' => false, 'data' => [], 'error' => 'Could not reach the voice gateway.'];
        }

        $body = $response->json();
        $body = is_array($body) ? $body : [];

        if (! $response->successful()) {
            return ['success' => false, 'data' => $body, 'error' => (string) ($body['message'] ?? 'HTTP '.$response->status())];
        }

        return ['success' => true, 'data' => is_array($body['data'] ?? null) ? $body['data'] : $body, 'error' => ''];
    }
}
