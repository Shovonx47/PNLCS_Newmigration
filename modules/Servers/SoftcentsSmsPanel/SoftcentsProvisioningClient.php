<?php

namespace Modules\Servers\SoftcentsSmsPanel;

use App\Models\Server;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SoftcentsProvisioningClient
{
    public function __construct(
        private readonly string $apiToken,
        private readonly string $baseUrl,
        private readonly string $moduleKey,
        private readonly int $timeout = 20,
    ) {}

    public static function fromServer(?Server $server, string $defaultHost, string $moduleKey): ?self
    {
        if (! $server) {
            return null;
        }

        $token = trim((string) ($server->access_hash ?: $server->password));
        if ($token === '') {
            return null;
        }

        $host = trim((string) ($server->hostname ?: $server->ip_address ?: $defaultHost));
        if ($host === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $host)) {
            $origin = rtrim($host, '/');
        } else {
            $port = (int) ($server->port ?: 443);
            $scheme = $port === 80 ? 'http' : 'https';
            if ($scheme === 'http' && ! (app()->environment(['local', 'testing']) && in_array($host, ['127.0.0.1', 'localhost'], true))) {
                $scheme = 'https';
            }
            $portPart = ($port > 0 && ! in_array($port, [80, 443], true)) ? ':'.$port : '';
            $origin = "{$scheme}://{$host}{$portPart}";
        }

        return new self($token, $origin.'/api', $moduleKey);
    }

    public function request(string $endpoint, string $method = 'GET', array $data = [], string $actionName = ''): array
    {
        $url = rtrim($this->baseUrl, '/').'/'.ltrim($endpoint, '/');
        $method = strtoupper($method);

        try {
            $pending = Http::withToken($this->apiToken)
                ->acceptJson()
                ->timeout($this->timeout)
                ->connectTimeout(10);

            $response = match ($method) {
                'GET' => $pending->get($url, $data),
                'POST' => $pending->asJson()->post($url, $data),
                'PUT' => $pending->asJson()->put($url, $data),
                'PATCH' => $pending->asJson()->patch($url, $data),
                'DELETE' => $pending->asJson()->delete($url, $data),
                default => $pending->get($url, $data),
            };

            $decoded = $response->json();
            if (! is_array($decoded)) {
                $decoded = [];
            }

            $isSuccess = $response->successful() && (($decoded['status'] ?? 'success') !== 'error');
            $message = (string) ($decoded['message'] ?? ($isSuccess ? 'Success' : 'HTTP '.$response->status()));

            Log::channel('daily')->info("SoftcentsProvisioningClient [{$this->moduleKey}] ".($actionName ?: $endpoint), [
                'http_code' => $response->status(),
                'success' => $isSuccess,
            ]);

            return [
                'success' => $isSuccess,
                'http_code' => $response->status(),
                'message' => $message,
                'data' => $decoded,
            ];
        } catch (\Throwable $e) {
            Log::channel('daily')->error("SoftcentsProvisioningClient [{$this->moduleKey}] ".($actionName ?: $endpoint).' failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'http_code' => 0,
                'message' => 'Platform connection unavailable right now.',
                'data' => [],
            ];
        }
    }

    public function ping(): array
    {
        return $this->request('whmcs/ping', 'GET', [], 'TestConnection');
    }

    public function getResources(): array
    {
        return $this->request('whmcs/resources', 'GET', [], 'GetResources');
    }

    public function lookupAccount(string $email = '', string $username = ''): array
    {
        $query = $email !== '' ? ['email' => $email] : ($username !== '' ? ['username' => $username] : []);

        return $this->request('whmcs/accounts/lookup', 'GET', $query, 'LookupAccount');
    }

    public function provisionAccount(array $payload): array
    {
        return $this->request('whmcs/accounts', 'POST', $payload, 'CreateAccount');
    }

    public function getAccount(int $userId): array
    {
        return $this->request('whmcs/accounts/'.$userId, 'GET', [], 'GetAccount');
    }

    public function creditAccount(int $userId, float $amount, string $reference = '', string $description = '', array $meta = []): array
    {
        return $this->request('whmcs/accounts/'.$userId.'/credit', 'POST', array_merge($meta, [
            'amount' => $amount,
            'reference' => $reference !== '' ? $reference : null,
            'description' => $description !== '' ? $description : null,
        ]), 'CreditAccount');
    }

    public function suspendAccount(int $userId, string $reason = ''): array
    {
        return $this->request('whmcs/accounts/'.$userId.'/suspend', 'POST', [
            'reason' => $reason !== '' ? $reason : null,
        ], 'SuspendAccount');
    }

    public function unsuspendAccount(int $userId): array
    {
        return $this->request('whmcs/accounts/'.$userId.'/unsuspend', 'POST', [], 'UnsuspendAccount');
    }

    public function terminateAccount(int $userId, bool $softDelete = false): array
    {
        $body = $softDelete ? ['soft_delete' => true] : [];

        return $this->request('whmcs/accounts/'.$userId.'/terminate', 'POST', $body, 'TerminateAccount');
    }

    public function changePackage(int $userId, array $payload): array
    {
        return $this->request('whmcs/accounts/'.$userId.'/package', 'PUT', $payload, 'ChangePackage');
    }

    public function changePassword(int $userId, string $password): array
    {
        return $this->request('whmcs/accounts/'.$userId.'/password', 'PUT', [
            'password' => $password,
        ], 'ChangePassword');
    }

    public function generateSsoUrl(int $userId): array
    {
        return $this->request('sso/token', 'POST', [
            'user_id' => $userId,
        ], 'SingleSignOn');
    }
}
