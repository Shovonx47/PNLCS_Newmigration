<?php

namespace Modules\Servers\Bdpbx;

use App\Models\Server;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BdpbxClient
{
    public function __construct(
        private readonly string $apiUrl,
        private readonly string $apiKey,
        private readonly int $timeout = 30,
    ) {}

    public static function fromServer(?Server $server, array $productConfig = []): ?self
    {
        $apiUrl = '';
        $apiKey = '';

        if ($server) {
            $host = trim((string) ($server->hostname ?: $server->ip_address));
            if ($host !== '') {
                $port = (int) ($server->port ?: 443);
                $scheme = $port === 80 ? 'http' : 'https';
                if ($scheme === 'http' && ! (app()->environment(['local', 'testing']) && in_array($host, ['127.0.0.1', 'localhost'], true))) {
                    $scheme = 'https';
                }
                $portPart = in_array($port, [80, 443], true) ? '' : ':'.$port;
                $apiUrl = "{$scheme}://{$host}{$portPart}/billing_api.php";
            }
            $apiKey = trim((string) ($server->access_hash ?: $server->password));
        }

        if ($apiUrl === '' && ! empty($productConfig['pbx_api_url'])) {
            $candidate = trim((string) $productConfig['pbx_api_url']);
            if (str_starts_with($candidate, 'https://')
                || (app()->environment(['local', 'testing']) && str_starts_with($candidate, 'http://127.0.0.1'))) {
                $apiUrl = $candidate;
            }
        }

        if ($apiKey === '' && ! empty($productConfig['pbx_api_key'])) {
            $apiKey = trim((string) $productConfig['pbx_api_key']);
        }

        if ($apiUrl === '' || $apiKey === '') {
            return null;
        }

        return new self($apiUrl, $apiKey);
    }

    public function call(string $action, string $domain = '', array $extra = []): array
    {
        $cleanDomain = self::cleanDomain($domain);

        $payload = array_merge([
            'action' => $action,
            'domain' => $cleanDomain,
            'api_key' => $this->apiKey,
        ], $extra);

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'X-API-KEY' => $this->apiKey,
            ])
                ->timeout($this->timeout)
                ->connectTimeout(10)
                ->asJson()
                ->post($this->apiUrl, $payload);

            $decoded = $response->json();

            if (! is_array($decoded)) {
                Log::channel('daily')->warning("BdpbxClient [{$action}] invalid JSON", [
                    'domain' => $cleanDomain,
                    'http_status' => $response->status(),
                ]);

                return [
                    'status' => 'error',
                    'message' => 'Voice platform returned an unexpected response (HTTP '.$response->status().').',
                ];
            }

            Log::channel('daily')->info("BdpbxClient [{$action}]", [
                'domain' => $cleanDomain,
                'status' => $decoded['status'] ?? 'unknown',
            ]);

            return $decoded;
        } catch (\Throwable $e) {
            Log::channel('daily')->error("BdpbxClient [{$action}] transport error", [
                'domain' => $cleanDomain,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => 'Unable to reach the Cloud PBX server right now.',
            ];
        }
    }

    public static function ensureCloudflareDns(string $subdomain, string $targetIp, string $cfToken, string $zoneId): bool
    {
        $cfToken = trim($cfToken);
        $zoneId = trim($zoneId);
        $subdomain = trim($subdomain);
        $targetIp = trim($targetIp);

        if ($cfToken === '' || $zoneId === '' || $subdomain === '' || ! filter_var($targetIp, FILTER_VALIDATE_IP)) {
            return false;
        }

        if (! preg_match('/^[a-f0-9]{32}$/i', $zoneId)) {
            return false;
        }

        try {
            $lookup = Http::withToken($cfToken)
                ->timeout(10)
                ->acceptJson()
                ->get("https://api.cloudflare.com/client/v4/zones/{$zoneId}/dns_records", [
                    'name' => $subdomain,
                    'type' => 'A',
                ]);

            $json = $lookup->json();
            $recordId = is_array($json) ? ($json['result'][0]['id'] ?? null) : null;
            $existingIp = is_array($json) ? ($json['result'][0]['content'] ?? null) : null;

            if ($recordId && $existingIp === $targetIp) {
                return true;
            }

            $body = [
                'type' => 'A',
                'name' => $subdomain,
                'content' => $targetIp,
                'ttl' => 120,
                'proxied' => false,
            ];

            $write = $recordId
                ? Http::withToken($cfToken)->timeout(10)->asJson()->put("https://api.cloudflare.com/client/v4/zones/{$zoneId}/dns_records/{$recordId}", $body)
                : Http::withToken($cfToken)->timeout(10)->asJson()->post("https://api.cloudflare.com/client/v4/zones/{$zoneId}/dns_records", $body);

            return (bool) ($write->json('success') ?? false);
        } catch (\Throwable $e) {
            Log::channel('daily')->warning('BDPBX Cloudflare DNS update failed', [
                'subdomain' => $subdomain,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public static function cleanDomain(string $domain): string
    {
        $clean = preg_replace('#^https?://#i', '', trim($domain));
        $clean = trim(rtrim((string) $clean, '/'));
        if (str_contains($clean, ':')) {
            $clean = explode(':', $clean)[0];
        }

        return strtolower($clean);
    }
}
