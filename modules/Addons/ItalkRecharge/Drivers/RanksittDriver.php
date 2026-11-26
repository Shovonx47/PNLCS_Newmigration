<?php

namespace Modules\Addons\ItalkRecharge\Drivers;

use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Modules\Addons\ItalkRecharge\Contracts\RechargeProvider;
use Modules\Addons\ItalkRecharge\Models\ItalkRechargeProvider;

/**
 * Ranksitt VSM4 REST API.
 *
 * Sign in answers with the reseller's userName rather than a success flag, the
 * client list is a paged DataTable payload, and a top-up that worked may answer
 * with an empty body - all three are the vendor's contract, so they are checked
 * explicitly instead of treating "no error" as success.
 */
class RanksittDriver implements RechargeProvider
{
    private CookieJar $jar;

    public function __construct(private readonly ItalkRechargeProvider $provider)
    {
        $this->jar = new CookieJar;
    }

    public function key(): string
    {
        return 'ranksitt';
    }

    public function login(): array
    {
        $base = $this->baseUrl();

        if ($base === null) {
            return ['ok' => false, 'message' => 'The provider base URL is not a valid HTTPS address.'];
        }

        try {
            $response = $this->client()->post($base.'/api/Account/SignIn', [
                'UserName' => (string) $this->provider->username,
                'Password' => (string) $this->provider->password,
                'RememberMe' => true,
                'TimeZoneOffset' => '6.00',
                'AccountType' => 'reseller',
                'Email' => null,
            ]);

            $body = $response->json();

            return is_array($body) && isset($body['userName'])
                ? ['ok' => true, 'message' => 'Signed in.']
                : ['ok' => false, 'message' => 'Sign-in was refused by '.$this->provider->name.'.'];
        } catch (\Throwable $e) {
            report($e);

            return ['ok' => false, 'message' => 'The provider could not be reached.'];
        }
    }

    public function findAccount(string $pin): array
    {
        $base = $this->baseUrl();

        if ($base === null) {
            return ['ok' => false, 'id' => null, 'message' => 'The provider base URL is not a valid HTTPS address.'];
        }

        try {
            $response = $this->client()->get($base.'/api/ResellerClientsRetail/GetClientsRetailsList', [
                'draw' => 1,
                'start' => 0,
                'length' => 1000,
                'active' => 'true',
                'orderBy' => 'id',
                'orderByType' => 'asc',
            ]);

            $rows = $response->json('data');

            if (! is_array($rows)) {
                return ['ok' => false, 'id' => null, 'message' => 'The client list could not be read from '.$this->provider->name.'.'];
            }

            foreach ($rows as $row) {
                if (isset($row['login']) && (string) $row['login'] === trim($pin)) {
                    return ['ok' => true, 'id' => (string) $row['id'], 'message' => 'Account found.'];
                }
            }

            return ['ok' => false, 'id' => null, 'message' => 'PIN '.trim($pin).' was not found at '.$this->provider->name.'.'];
        } catch (\Throwable $e) {
            report($e);

            return ['ok' => false, 'id' => null, 'message' => 'The provider could not be reached.'];
        }
    }

    public function recharge(string $accountId, string $pin, float $amount): array
    {
        $base = $this->baseUrl();

        if ($base === null) {
            return ['ok' => false, 'message' => 'The provider base URL is not a valid HTTPS address.', 'raw' => null];
        }

        try {
            $response = $this->client()->post($base.'/api/ResellerClients/AddPayment', [
                'clientId' => $accountId,
                'clientType' => 32,
                'amount' => $amount,
                'balance' => 0,
                'credit' => 0,
                'type' => 'Payment',
                'date' => now()->utc()->format('Y-m-d\TH:i:s.000\Z'),
                'description' => 'Added via PNLCS by reseller ['.$this->provider->username.']',
                'addToInvoice' => false,
                'sendConfirmation' => false,
            ]);

            $body = $response->json();

            if (is_array($body) && ! empty($body['error'])) {
                return ['ok' => false, 'message' => 'The provider refused the recharge: '.mb_substr((string) $body['error'], 0, 200), 'raw' => ['error' => true]];
            }

            if (! $response->successful()) {
                return ['ok' => false, 'message' => 'The provider answered HTTP '.$response->status().'.', 'raw' => ['status' => $response->status()]];
            }

            return ['ok' => true, 'message' => 'Recharge successful.', 'raw' => ['status' => $response->status()]];
        } catch (\Throwable $e) {
            report($e);

            return ['ok' => false, 'message' => 'The provider could not be reached.', 'raw' => null];
        }
    }

    private function client(): PendingRequest
    {
        return Http::withOptions([
            'cookies' => $this->jar,
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
