<?php

namespace Modules\Servers\Bhpanel;

use App\Models\Server;
use App\Models\Service;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BhpanelClient
{
    public const PAGE_TIMEOUT = 8;

    public const CLICK_TIMEOUT = 30;

    public const ACTION_TIMEOUT = 120;

    /** @var array<string, bool> */
    private static array $verifiedOwnCache = [];

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $token,
        private readonly string $scheme = 'https',
    ) {}

    public static function fromServer(?Server $server): ?self
    {
        if (! $server) {
            return null;
        }

        $host = trim((string) ($server->hostname ?: $server->ip_address));
        if ($host === '' || preg_match('/^[A-Za-z0-9.\-]+\z|^\[[0-9A-Fa-f:]+\]\z/', $host) !== 1) {
            return null;
        }

        $token = trim((string) ($server->access_hash ?: $server->password));
        if (preg_match('/^bhp_live_[0-9a-f]{64}\z/', $token) !== 1) {
            return null;
        }

        $port = (int) ($server->port ?: 2304);
        $allowLocalHttp = app()->environment(['local', 'testing']) && in_array($host, ['127.0.0.1', 'localhost'], true);
        $scheme = ($allowLocalHttp && (int) $server->port === 80) ? 'http' : 'https';

        return new self($host, $port, $token, $scheme);
    }

    public static function cleanDomain(?string $domain): string
    {
        $d = strtolower(trim((string) $domain));

        return str_starts_with($d, 'www.') ? substr($d, 4) : $d;
    }

    public static function encodedUser(?string $username): string
    {
        return rawurlencode(strtolower(trim((string) $username)));
    }

    public static function isValidSsoUrl(string $url): bool
    {
        if (preg_match('#^https://[^/\s"<>]+/sso/[0-9a-f]{64}\z#', $url) === 1) {
            return true;
        }

        return app()->environment(['local', 'testing'])
            && preg_match('#^http://(127\.0\.0\.1|localhost)(:[0-9]+)?/sso/[0-9a-f]{64}\z#', $url) === 1;
    }

    /**
     * Send one authenticated request to BHPanel `/v1/*`.
     *
     * @return array{ok: bool, data: mixed, error: string, code?: string, transport?: bool}
     */
    public function api(
        string $method,
        string $path,
        ?array $body = null,
        int $timeout = self::ACTION_TIMEOUT,
        ?Service $expectForService = null,
    ): array {
        $method = strtoupper($method);

        if ($expectForService !== null) {
            $wantDomain = self::cleanDomain($expectForService->domain);
            $user = self::encodedUser($expectForService->username);
            if ($wantDomain !== '' && $user !== '' && preg_match('#^/v1/accounts/([^/?]+)#', $path, $pm) === 1 && $pm[1] === $user) {
                if ($method === 'GET') {
                    $path .= (str_contains($path, '?') ? '&' : '?').'expect_domain='.rawurlencode($wantDomain);
                } else {
                    $body = is_array($body) ? $body : [];
                    $body['expect_domain'] = $wantDomain;
                }
            }
        }

        $url = "{$this->scheme}://{$this->host}:{$this->port}{$path}";

        try {
            $pending = Http::withToken($this->token)
                ->acceptJson()
                ->timeout(max(3, $timeout))
                ->connectTimeout(min(15, max(3, $timeout)))
                ->withoutRedirecting();

            $response = match ($method) {
                'GET' => $pending->get($url),
                'POST' => $body !== null ? $pending->asJson()->post($url, $body) : $pending->post($url),
                'PUT' => $pending->asJson()->put($url, $body ?? []),
                'DELETE' => $body !== null ? $pending->asJson()->delete($url, $body) : $pending->delete($url),
                default => $pending->get($url),
            };

            $status = $response->status();
            $decoded = $response->json();

            Log::channel('daily')->info("BhpanelClient {$method} {$path}", [
                'http_status' => $status,
                'ok' => is_array($decoded) ? ($decoded['ok'] ?? null) : null,
            ]);

            $done = is_array($decoded) && ($decoded['ok'] ?? null) === true && $status >= 200 && $status < 300;
            if (! $done && (! is_array($decoded) || ($decoded['ok'] ?? null) !== false)) {
                return [
                    'ok' => false,
                    'data' => null,
                    'transport' => true,
                    'error' => "Control panel returned unexpected HTTP {$status}.",
                ];
            }

            if (! $done) {
                $msg = is_string($decoded['error'] ?? null) && $decoded['error'] !== ''
                    ? $decoded['error']
                    : "Request refused (HTTP {$status}).";

                return [
                    'ok' => false,
                    'data' => null,
                    'code' => (string) ($decoded['code'] ?? ''),
                    'error' => $msg,
                ];
            }

            return [
                'ok' => true,
                'data' => $decoded['data'] ?? null,
                'error' => '',
            ];
        } catch (\Throwable $e) {
            Log::channel('daily')->warning("BhpanelClient transport failure {$method} {$path}", [
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'data' => null,
                'transport' => true,
                'error' => 'Could not reach the hosting control panel right now.',
            ];
        }
    }

    /**
     * Verify that the panel account named by `$service->username` exists and holds `$service->domain`.
     * Returns `null` when verified, `'gone'` when `not_found` and `$goneIsDone` is true, or an error string.
     */
    public function ownAccount(Service $service, bool $goneIsDone = false, int $timeout = self::CLICK_TIMEOUT, ?bool &$transient = null): ?string
    {
        $transient = false;
        $user = self::encodedUser($service->username);
        if ($user === '') {
            return 'Service has no control panel username.';
        }

        $r = $this->api('GET', '/v1/accounts/'.$user, null, $timeout, null);
        if (! $r['ok']) {
            if (($r['code'] ?? '') === 'not_found') {
                return $goneIsDone ? 'gone' : 'Control panel account was not found.';
            }
            if (($r['code'] ?? '') === 'forbidden') {
                return 'Server API token is missing Read permission.';
            }
            $transient = true;

            return $r['error'];
        }

        $have = strtolower((string) ($r['data']['account']['domain'] ?? ''));
        $want = self::cleanDomain($service->domain);
        if ($have === '' || $have !== $want) {
            return 'Control panel account ownership mismatch (domain does not match this service).';
        }

        return null;
    }

    /**
     * Execute a call scoped to `$service`'s verified account (`/v1/accounts/{user}{$suffix}`).
     *
     * @return array{ok: bool, data: mixed, error: string, code?: string, transport?: bool}
     */
    public function accountCall(Service $service, string $method, string $suffix, ?array $body = null, int $timeout = self::CLICK_TIMEOUT): array
    {
        $cacheKey = $service->id.'|'.self::encodedUser($service->username).'|'.self::cleanDomain($service->domain);
        if (! isset(self::$verifiedOwnCache[$cacheKey])) {
            $err = $this->ownAccount($service, false, $timeout);
            if ($err !== null) {
                return ['ok' => false, 'data' => null, 'error' => $err, 'code' => 'not_own'];
            }
            self::$verifiedOwnCache[$cacheKey] = true;
        }

        return $this->api($method, '/v1/accounts/'.self::encodedUser($service->username).$suffix, $body, $timeout, $service);
    }

    public static function resetCache(): void
    {
        self::$verifiedOwnCache = [];
    }
}
