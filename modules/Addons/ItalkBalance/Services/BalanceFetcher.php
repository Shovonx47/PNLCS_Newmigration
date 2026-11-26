<?php

namespace Modules\Addons\ItalkBalance\Services;

/**
 * Talks to one IPTSP balance endpoint and returns a balance or a reason.
 *
 * The legacy addons built the URL from whatever an operator typed and then
 * printed the body straight into the page. Two things are done here instead:
 * every answer must be a bare number to count as a balance, and the request
 * goes out over verified HTTPS only.
 */
class BalanceFetcher
{
    public const CONNECT_TIMEOUT = 8;

    public const TIMEOUT = 10;

    /**
     * @return array{ok: bool, balance: ?string, message: string}
     */
    public function fetch(string $baseUrl, string $ipNumber): array
    {
        $pin = trim($ipNumber);

        // The PIN is put in a query string: refuse anything that is not a
        // plain account number so no caller can smuggle in parameters.
        if ($pin === '' || ! preg_match('/^[0-9A-Za-z._+\-]{3,64}$/', $pin)) {
            return ['ok' => false, 'balance' => null, 'message' => 'This service has no valid IP number configured.'];
        }

        $url = $this->buildUrl($baseUrl, $pin);

        if ($url === null) {
            return ['ok' => false, 'balance' => null, 'message' => 'The balance endpoint is not a valid HTTPS URL.'];
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT => self::TIMEOUT,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            // Strict TLS: the legacy addon had these set to false.
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_USERAGENT => 'PNLCS-ItalkBalance/1.0',
        ]);

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0 || $body === false) {
            return ['ok' => false, 'balance' => null, 'message' => 'The balance service could not be reached.'];
        }

        if ($status !== 200) {
            return ['ok' => false, 'balance' => null, 'message' => "The balance service answered HTTP {$status}."];
        }

        $body = trim((string) $body);

        if ($body === '' || preg_match('/<\s*(!doctype|html|body|head)/i', $body) === 1) {
            return ['ok' => false, 'balance' => null, 'message' => 'The balance service returned an unexpected response.'];
        }

        if (! is_numeric($body)) {
            // iTalk/ICC answer "Invalid Account" for a PIN that is not theirs.
            return ['ok' => false, 'balance' => null, 'message' => 'No balance is available for this IP number.'];
        }

        $balance = (float) $body;

        if ($balance < 0 || $balance > 99999999) {
            return ['ok' => false, 'balance' => null, 'message' => 'The balance service returned an out-of-range figure.'];
        }

        return ['ok' => true, 'balance' => number_format($balance, 6, '.', ''), 'message' => 'Balance updated.'];
    }

    /** Build the endpoint URL, appending the PIN whether or not the base ends in "=". */
    public function buildUrl(string $baseUrl, string $pin): ?string
    {
        $base = trim($baseUrl);

        if ($base === '') {
            return null;
        }

        $candidate = str_contains($base, '?')
            ? (str_ends_with($base, '=') ? $base : rtrim($base, '&').'&pin=').urlencode($pin)
            : rtrim($base, '=&').'?pin='.urlencode($pin);

        $parts = parse_url($candidate);

        if (! is_array($parts) || strtolower((string) ($parts['scheme'] ?? '')) !== 'https' || empty($parts['host'])) {
            return null;
        }

        return $candidate;
    }
}
