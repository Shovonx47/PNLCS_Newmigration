<?php

namespace Modules\Addons\SoftcentsSms\Services;

use App\Support\BdPhone;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Addons\SoftcentsSms\Models\SmsSetting;

/**
 * The Softcents SMS gateway (sms.softcents.com).
 *
 * Two calls, as the legacy SmsMessage class made: send-message and
 * check-balance. Both are plain GETs with the credentials in the query, which
 * is the provider's contract; what is not the provider's contract is disabling
 * TLS verification, which the legacy class did on every call. Certificates are
 * verified here.
 */
class SmsClient
{
    public const BASE_URL = 'https://sms.softcents.com';

    public function __construct(private ?SmsSetting $settings = null)
    {
        $this->settings ??= SmsSetting::current();
    }

    public function configured(): bool
    {
        return $this->settings->configured();
    }

    /**
     * @return array{success: bool, balance: string, error: string}
     */
    public function balance(): array
    {
        if (! $this->configured()) {
            return ['success' => false, 'balance' => '0', 'error' => 'SMS credentials are not configured.'];
        }

        try {
            $response = Http::timeout(15)->acceptJson()->get(self::BASE_URL.'/check-balance', [
                'clientId' => (string) $this->settings->client_id,
                'clientSecret' => (string) $this->settings->client_secret,
            ]);
        } catch (\Throwable $e) {
            Log::warning('SMS balance check failed: '.$e->getMessage());

            return ['success' => false, 'balance' => '0', 'error' => 'Could not reach the SMS gateway.'];
        }

        $data = $response->json();
        $data = is_array($data) ? $data : [];

        if (isset($data['balance'])) {
            return ['success' => true, 'balance' => (string) $data['balance'], 'error' => ''];
        }

        return [
            'success' => false,
            'balance' => '0',
            'error' => (string) ($data['message'] ?? 'Gateway returned HTTP '.$response->status()),
        ];
    }

    /**
     * @return array{success: bool, message: string, http_code: int, error: string}
     */
    public function send(string $number, string $message): array
    {
        $to = BdPhone::gateway($number);

        if ($to === '') {
            return ['success' => false, 'message' => '', 'http_code' => 0, 'error' => 'Not a valid Bangladeshi mobile number.'];
        }

        if (! $this->configured()) {
            return ['success' => false, 'message' => '', 'http_code' => 0, 'error' => 'SMS credentials are not configured.'];
        }

        if (trim($message) === '') {
            return ['success' => false, 'message' => '', 'http_code' => 0, 'error' => 'Refusing to send an empty message.'];
        }

        try {
            $response = Http::timeout(20)->acceptJson()->get(self::BASE_URL.'/send-message', [
                'clientId' => (string) $this->settings->client_id,
                'clientSecret' => (string) $this->settings->client_secret,
                'senderId' => (string) $this->settings->sender_id,
                'numbers' => $to,
                'contents' => $message,
            ]);
        } catch (\Throwable $e) {
            Log::warning('SMS send failed: '.$e->getMessage());

            return ['success' => false, 'message' => '', 'http_code' => 0, 'error' => 'Could not reach the SMS gateway.'];
        }

        $data = $response->json();
        $data = is_array($data) ? $data : [];

        $ok = (isset($data['status']) && (string) $data['status'] === '200')
            || ($data['success'] ?? false) === true;

        return [
            'success' => $ok,
            'message' => (string) ($data['message'] ?? ''),
            'http_code' => $response->status(),
            'error' => $ok ? '' : (string) ($data['message'] ?? 'Gateway returned HTTP '.$response->status()),
        ];
    }
}
