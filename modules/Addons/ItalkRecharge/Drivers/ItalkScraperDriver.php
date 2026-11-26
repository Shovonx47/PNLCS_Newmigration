<?php

namespace Modules\Addons\ItalkRecharge\Drivers;

use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Modules\Addons\ItalkRecharge\Contracts\RechargeProvider;
use Modules\Addons\ItalkRecharge\Models\ItalkRechargeProvider;

/**
 * The iTalk/ICC reseller portal flow: SSO sign-in, read the PIN list, submit
 * the recharge form.
 *
 * The legacy scraper performed four cURL requests with TLS verification
 * switched off and `logActivity()` printing the reseller username, password
 * and every response header. This driver keeps the same request sequence and
 * form field names (the portal has not changed) but verifies certificates,
 * bounds every call with a timeout, and returns messages the panel can show
 * without leaking credentials.
 */
class ItalkScraperDriver implements RechargeProvider
{
    private CookieJar $jar;

    public function __construct(private readonly ItalkRechargeProvider $provider)
    {
        $this->jar = new CookieJar;
    }

    public function key(): string
    {
        return 'italk';
    }

    public function login(): array
    {
        $base = $this->baseUrl();
        $path = $this->basePath();

        if ($base === null) {
            return ['ok' => false, 'message' => 'The provider base URL is not a valid HTTPS address.'];
        }

        $sso = $base.':2020/OTTSSO';
        $loginPage = $base.$path.'Login.do';
        $logoutPage = $base.$path.'Logout.do';

        $ssoUrl = $sso.'/?redirect='.urlencode($loginPage).'&logout='.urlencode($logoutPage).'&audience=sso&app=billing';

        try {
            // [1] Open the SSO landing page to receive the session cookie.
            $this->client()->get($ssoUrl);

            // [2] Post the reseller credentials; the portal answers 302 with a token.
            $step2 = $this->client(false)->asForm()->post($sso.'/login', [
                'username' => (string) $this->provider->username,
                'password' => (string) $this->provider->password,
                'rememberMe' => 'off',
            ]);

            $location = $step2->header('Location');

            if ($step2->status() !== 302 || $location === '') {
                return ['ok' => false, 'message' => 'Sign-in was refused by '.$this->provider->name.'.'];
            }

            // [3] Follow the token back into the portal.
            $step3 = $this->client()->get($location);
            $body = (string) $step3->body();

            $signedIn = str_contains($body, 'Logout.do')
                || str_contains($body, 'home/index.jsp')
                || str_contains($body, 'Dashboard');

            return $signedIn
                ? ['ok' => true, 'message' => 'Signed in.']
                : ['ok' => false, 'message' => 'The portal did not accept the session for '.$this->provider->name.'.'];
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
            $response = $this->client()->get($base.$this->basePath().'ViewPinRecharge.do?PinBatchID=-2');

            if ($response->status() !== 200 || strlen((string) $response->body()) < 100) {
                return ['ok' => false, 'id' => null, 'message' => 'The PIN list could not be read from '.$this->provider->name.'.'];
            }

            $body = (string) $response->body();
            preg_match_all('/name="pinNO"\s+value="([^"]+)"/i', $body, $pins);
            preg_match_all('/name="AccountID"\s+value="([^"]+)"/i', $body, $accounts);

            $wanted = trim($pin);

            foreach ($pins[1] ?? [] as $index => $found) {
                if (trim((string) $found) === $wanted && isset($accounts[1][$index])) {
                    return ['ok' => true, 'id' => trim((string) $accounts[1][$index]), 'message' => 'Account found.'];
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

        if ($base === null) {
            return ['ok' => false, 'message' => 'The provider base URL is not a valid HTTPS address.', 'raw' => null];
        }

        try {
            $response = $this->client()->asForm()->withHeaders([
                'Referer' => $base.$this->basePath().'pin/pinRecharge.jsp',
            ])->post($base.$this->basePath().'RechargePin.do', [
                'AccountID' => $accountId,
                'pinNO' => $pin,
                'RechargeAmount' => $amount,
                'CreditAmount' => 0,
                'PackageID' => -1,
                'RechargeDescription' => 'Purchase',
                'B2' => 'Recharge',
            ]);

            $body = (string) $response->body();
            $ok = $response->status() === 302
                || str_contains($body, 'Recharge Successful')
                || str_contains($body, 'successfully');

            return [
                'ok' => $ok,
                'message' => $ok ? 'Recharge successful.' : 'The provider refused the recharge (HTTP '.$response->status().').',
                'raw' => ['status' => $response->status()],
            ];
        } catch (\Throwable $e) {
            report($e);

            return ['ok' => false, 'message' => 'The provider could not be reached.', 'raw' => null];
        }
    }

    private function client(bool $followRedirects = true): PendingRequest
    {
        return Http::withOptions([
            'cookies' => $this->jar,
            'allow_redirects' => $followRedirects,
            'verify' => true,
            'timeout' => 20,
            'connect_timeout' => 8,
        ])->withHeaders([
            'User-Agent' => 'PNLCS-ItalkRecharge/1.0',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        ]);
    }

    private function baseUrl(): ?string
    {
        $raw = trim((string) $this->provider->base_url);
        $parts = parse_url($raw);

        if (! is_array($parts) || strtolower((string) ($parts['scheme'] ?? '')) !== 'https' || empty($parts['host'])) {
            return null;
        }

        return 'https://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
    }

    private function basePath(): string
    {
        $path = trim((string) ($this->provider->base_path ?: '/iconiptsp/'), '/');

        return $path === '' ? '/' : '/'.$path.'/';
    }
}
