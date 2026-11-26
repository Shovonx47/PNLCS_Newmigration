<?php

namespace Modules\Addons\ItalkRecharge\Drivers;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Modules\Addons\ItalkRecharge\Contracts\RechargeProvider;
use Modules\Addons\ItalkRecharge\Models\ItalkRechargeProvider;

/**
 * WebVoice REST API: a bearer token from /api/login, a paged client list, and
 * a payment endpoint that answers 201 with the new balance.
 */
class WebvoiceDriver implements RechargeProvider
{
    private ?string $token = null;

    public function __construct(private readonly ItalkRechargeProvider $provider) {}

    public function key(): string
    {
        return 'webvoice';
    }

    public function login(): array
    {
        $base = $this->baseUrl();

        if ($base === null) {
            return ['ok' => false, 'message' => 'The provider base URL is not a valid HTTPS address.'];
        }

        try {
            $response = $this->client()->post($base.'/api/login', [
                'username' => (string) $this->provider->username,
                'password' => (string) $this->provider->password,
            ]);

            $token = $response->json('token');

            if (! is_string($token) || $token === '') {
                return ['ok' => false, 'message' => 'Sign-in was refused by '.$this->provider->name.'.'];
            }

            $this->token = $token;

            return ['ok' => true, 'message' => 'Signed in.'];
        } catch (\Throwable $e) {
            report($e);

            return ['ok' => false, 'message' => 'The provider could not be reached.'];
        }
    }

    public function findAccount(string $pin): array
    {
        $base = $this->baseUrl();

        if ($base === null || $this->token === null) {
            return ['ok' => false, 'id' => null, 'message' => 'The provider session is not established.'];
        }

        try {
            $rows = $this->client()->withToken($this->token)
                ->get($base.'/api/clients', ['page' => 1, 'per_page' => 1000])
                ->json('data');

            if (! is_array($rows)) {
                return ['ok' => false, 'id' => null, 'message' => 'The client list could not be read from '.$this->provider->name.'.'];
            }

            $wanted = trim($pin);

            foreach ($rows as $row) {
                if ((string) ($row['username'] ?? '') === $wanted || (string) ($row['callerid'] ?? '') === $wanted) {
                    return ['ok' => true, 'id' => (string) $row['id'], 'message' => 'Account found.'];
                }
            }

            return ['ok' => false, 'id' => null, 'message' => "PIN {$wanted} was not found at ".$this->provider->name.'.'];
        } catch (\Throwable $e) {
            report($e);

            return ['ok' => false, 'id' => null, 'message' => 'The provider could not be reached.'];
        }
    }

    public function recharge(string $accountId, string $pin, float $amount): array
    {
        $base = $this->baseUrl();

        if ($base === null || $this->token === null) {
            return ['ok' => false, 'message' => 'The provider session is not established.', 'raw' => null];
        }

        try {
            $response = $this->client()->withToken($this->token)
                ->post($base.'/api/clients/'.$accountId.'/payment', [
                    'amount' => $amount,
                    'type' => 'credit',
                    'description' => 'Added via PNLCS by reseller ['.$this->provider->username.']',
                ]);

            $body = $response->json();
            $ok = is_array($body) && isset($body['id'], $body['client_id']);

            return [
                'ok' => $ok,
                'message' => $ok ? 'Recharge successful.' : 'The provider refused the recharge (HTTP '.$response->status().').',
                'raw' => is_array($body) ? ['status' => $response->status(), 'balance_after' => $body['balance_after'] ?? null] : ['status' => $response->status()],
            ];
        } catch (\Throwable $e) {
            report($e);

            return ['ok' => false, 'message' => 'The provider could not be reached.', 'raw' => null];
        }
    }

    private function client(): PendingRequest
    {
        return Http::withOptions([
            'verify' => true,
            'timeout' => 20,
            'connect_timeout' => 8,
        ])->acceptJson()->withHeaders([
            'User-Agent' => 'PNLCS-ItalkRecharge/1.0',
        ]);
    }

    private function baseUrl(): ?string
    {
        $parts = parse_url(trim((string) $this->provider->base_url));

        if (! is_array($parts) || strtolower((string) ($parts['scheme'] ?? '')) !== 'https' || empty($parts['host'])) {
            return null;
        }

        return rtrim('https://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '').($parts['path'] ?? ''), '/');
    }
}
