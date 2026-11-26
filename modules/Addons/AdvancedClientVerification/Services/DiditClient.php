<?php

namespace Modules\Addons\AdvancedClientVerification\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiditClient
{
    public const BASE_URL = 'https://verification.didit.me/v2';

    public function __construct(
        private string $apiKey,
        private string $workflowId = '',
        private string $webhookSecret = '',
    ) {}

    public function isConfigured(): bool
    {
        return trim($this->apiKey) !== '' && trim($this->workflowId) !== '';
    }

    /**
     * Create a Didit v2 verification session over strict TLS.
     *
     * @return array{success: bool, session_id?: string, url?: string, message?: string}
     */
    public function createSession(int $clientId, string $callbackUrl): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'message' => 'Automated identity verification is not configured right now.'];
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->timeout(20)->post(self::BASE_URL.'/session/', [
                'workflow_id' => $this->workflowId,
                'vendor_data' => (string) $clientId,
                'callback' => $callbackUrl,
            ]);

            if (! $response->successful()) {
                Log::warning('Didit session creation failed', [
                    'client_id' => $clientId,
                    'status' => $response->status(),
                ]);

                return ['success' => false, 'message' => 'Unable to start automated verification right now. Please try again shortly.'];
            }

            $body = (array) $response->json();
            $sessionId = trim((string) ($body['session_id'] ?? $body['id'] ?? ''));
            $url = trim((string) ($body['url'] ?? $body['verification_url'] ?? ''));

            if ($sessionId === '' || ! $this->isSafeRedirectUrl($url)) {
                return ['success' => false, 'message' => 'Verification service returned an incomplete session response.'];
            }

            return [
                'success' => true,
                'session_id' => $sessionId,
                'url' => $url,
            ];
        } catch (\Throwable $e) {
            Log::error('Didit session creation threw exception', [
                'client_id' => $clientId,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Unable to reach the verification service right now.'];
        }
    }

    /**
     * Fetch the current state of a Didit v2 session.
     *
     * @return array{success: bool, status?: string, payload?: array<string, mixed>, message?: string}
     */
    public function fetchSession(string $sessionId): array
    {
        $sessionId = trim($sessionId);
        if ($sessionId === '' || trim($this->apiKey) === '' || ! preg_match('/^[A-Za-z0-9_-]{4,128}$/', $sessionId)) {
            return ['success' => false, 'message' => 'Verification session cannot be checked right now.'];
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->timeout(20)->get(self::BASE_URL.'/session/'.rawurlencode($sessionId).'/decision/');

            if ($response->status() === 404) {
                $response = Http::withHeaders([
                    'x-api-key' => $this->apiKey,
                    'Accept' => 'application/json',
                ])->timeout(20)->get(self::BASE_URL.'/session/'.rawurlencode($sessionId));
            }

            if (! $response->successful()) {
                return ['success' => false, 'message' => 'Verification status is still being processed.'];
            }

            $body = (array) $response->json();
            $rawStatus = strtolower(trim((string) ($body['status'] ?? $body['decision']['status'] ?? '')));

            return [
                'success' => true,
                'status' => self::normalizeProviderStatus($rawStatus),
                'payload' => $body,
            ];
        } catch (\Throwable $e) {
            Log::warning('Didit fetchSession failed', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Unable to refresh verification status right now.'];
        }
    }

    /**
     * Verify HMAC-SHA256 webhook signature in constant time. Fails closed when
     * the webhook secret or header is absent.
     */
    public function verifyWebhookSignature(string $rawBody, ?string $signatureHeader): bool
    {
        $secret = trim($this->webhookSecret);
        $provided = trim((string) $signatureHeader);

        if ($secret === '' || $provided === '') {
            return false;
        }

        if (str_starts_with(strtolower($provided), 'sha256=')) {
            $provided = substr($provided, 7);
        }

        $computed = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($computed, strtolower($provided));
    }

    public static function normalizeProviderStatus(string $rawStatus): string
    {
        $s = strtolower(trim($rawStatus));

        return match (true) {
            in_array($s, ['approved', 'verified', 'completed', 'success'], true) => 'verified',
            in_array($s, ['declined', 'rejected', 'failed', 'expired', 'abandoned'], true) => 'failed',
            default => 'pending',
        };
    }

    private function isSafeRedirectUrl(string $url): bool
    {
        if ($url === '') {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return $scheme === 'https' && $host !== '';
    }
}
