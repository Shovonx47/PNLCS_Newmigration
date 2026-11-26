<?php

namespace Modules\Gateways\PipraPay;

use App\Contracts\GatewayModuleInterface;
use App\Models\GatewayLog;
use App\Models\GatewaySettings;
use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PipraPayModule implements GatewayModuleInterface
{
    public const NAME = 'piprapay';

    public function getModuleName(): string
    {
        return 'PipraPay (bKash, Nagad, Rocket & Cards)';
    }

    public function isTokenised(): bool
    {
        return false;
    }

    public function getConfigFields(): array
    {
        return [
            [
                'name' => 'api_key',
                'label' => 'API Key',
                'type' => 'password',
                'required' => true,
            ],
            [
                'name' => 'base_url',
                'label' => 'PipraPay Base URL',
                'type' => 'text',
                'required' => true,
                'description' => 'HTTPS base URL of your PipraPay payment portal (e.g. https://pay.example.com).',
            ],
            [
                'name' => 'version',
                'label' => 'API Version',
                'type' => 'select',
                'default' => 'v3',
                'options' => [
                    'v3' => 'V3+',
                    'v2' => 'V2',
                ],
            ],
            [
                'name' => 'exchange_rate',
                'label' => 'Exchange Rate (1 USD = ? BDT)',
                'type' => 'text',
                'default' => '120',
                'description' => 'Used only when the shop currency is not BDT.',
            ],
        ];
    }

    public function getSetting(string $key, ?string $default = null): ?string
    {
        $val = GatewaySettings::where('gateway', self::NAME)
            ->where('setting', $key)
            ->value('value');

        return ($val !== null && trim((string) $val) !== '') ? trim((string) $val) : $default;
    }

    public function isVersion2(): bool
    {
        $ver = strtolower((string) $this->getSetting('version', 'v3'));

        return in_array($ver, ['v2', '2'], true);
    }

    public function normalizedBaseUrl(): ?string
    {
        $raw = rtrim((string) $this->getSetting('base_url', ''), '/');
        if ($raw === '') {
            return null;
        }

        $parts = parse_url($raw);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($host === '') {
            return null;
        }

        $allowLocalHttp = app()->environment(['local', 'testing']) && in_array($host, ['127.0.0.1', 'localhost'], true);
        if ($scheme !== 'https' && ! ($scheme === 'http' && $allowLocalHttp)) {
            return null;
        }

        return $raw;
    }

    /**
     * Work out the charge amount and currency sent to PipraPay while preserving
     * the exact invoice due amount for settlement.
     *
     * @return array{amount: float, currency: string, rate: float, due: float}
     */
    public function calculateCharge(Invoice $invoice, float $due): array
    {
        $shopCurrency = strtoupper((string) ($invoice->source_currency ?: shop_currency_code()));
        $billingCurrency = strtoupper((string) ($invoice->billing_currency ?: $shopCurrency));

        if ($shopCurrency === 'BDT') {
            return [
                'amount' => round($due, 2),
                'currency' => 'BDT',
                'rate' => 1.0,
                'due' => round($due, 2),
            ];
        }

        if ($billingCurrency === 'BDT' && (float) $invoice->exchange_rate > 0) {
            $rate = (float) $invoice->exchange_rate;

            return [
                'amount' => round($due * $rate, 2),
                'currency' => 'BDT',
                'rate' => $rate,
                'due' => round($due, 2),
            ];
        }

        $configuredRate = (float) $this->getSetting('exchange_rate', '120');
        $rate = $configuredRate > 0 ? $configuredRate : 120.0;

        return [
            'amount' => round($due * $rate, 2),
            'currency' => 'BDT',
            'rate' => $rate,
            'due' => round($due, 2),
        ];
    }

    public function capture(Invoice $invoice, float $amount, array $params = []): array
    {
        $apiKey = (string) $this->getSetting('api_key', '');
        $baseUrl = $this->normalizedBaseUrl();

        if ($apiKey === '' || $baseUrl === null) {
            return [
                'success' => false,
                'message' => 'This payment method is temporarily unavailable. Please choose another option or contact support.',
            ];
        }

        $invoice->loadMissing('client');
        $client = $invoice->client;
        $fullName = trim((string) ($client?->display_name ?: (($client?->first_name ?? '').' '.($client?->last_name ?? '')))) ?: 'Valued Customer';
        $email = trim((string) ($client?->email ?? '')) ?: 'billing@softcents.com';
        $phone = preg_replace('/[^0-9+]/', '', (string) ($client?->full_phone ?: ($client?->phone_number ?? ''))) ?: '01700000000';

        $charge = $this->calculateCharge($invoice, $amount);
        $returnUrl = $params['redirect_url'] ?? route('modules.gateways.piprapay.return', $invoice);
        $cancelUrl = $params['cancel_url'] ?? (route('client.invoices.show', $invoice).'?payment=cancelled');
        $webhookUrl = $params['webhook_url'] ?? route('modules.gateways.piprapay.webhook');

        $metadata = [
            'invoiceid' => (int) $invoice->id,
            'invoice_num' => (string) ($invoice->invoice_num ?: $invoice->id),
            'due_amount' => number_format($charge['due'], 2, '.', ''),
            'rate' => number_format($charge['rate'], 5, '.', ''),
        ];

        try {
            if ($this->isVersion2()) {
                $endpoint = $baseUrl.'/api/create-charge';
                $headers = [
                    'Accept' => 'application/json',
                    'mh-piprapay-api-key' => $apiKey,
                ];
                $payload = [
                    'full_name' => $fullName,
                    'email_mobile' => $email,
                    'amount' => $charge['amount'],
                    'metadata' => $metadata,
                    'redirect_url' => $returnUrl,
                    'cancel_url' => $cancelUrl,
                    'webhook_url' => $webhookUrl,
                    'return_type' => 'GET',
                    'currency' => $charge['currency'],
                ];
            } else {
                $endpoint = $baseUrl.'/checkout/redirect';
                $headers = [
                    'Accept' => 'application/json',
                    'MHS-PIPRAPAY-API-KEY' => $apiKey,
                ];
                $payload = [
                    'full_name' => $fullName,
                    'email_address' => $email,
                    'mobile_number' => $phone,
                    'amount' => $charge['amount'],
                    'metadata' => $metadata,
                    'return_url' => preg_replace('/(https?:\/\/)www\./i', '$1', $returnUrl),
                    'webhook_url' => preg_replace('/(https?:\/\/)www\./i', '$1', $webhookUrl),
                    'currency' => $charge['currency'],
                ];
            }

            $response = Http::withHeaders($headers)
                ->timeout(25)
                ->connectTimeout(10)
                ->asJson()
                ->post($endpoint, $payload);

            $json = $response->json();
            $ppUrl = is_array($json) ? trim((string) ($json['pp_url'] ?? '')) : '';

            if (! $response->successful() || ! $this->isValidRedirectUrl($ppUrl)) {
                Log::warning('PipraPay charge initiation failed', [
                    'invoice_id' => $invoice->id,
                    'status' => $response->status(),
                ]);

                GatewayLog::create([
                    'gateway' => self::NAME,
                    'data' => json_encode(['invoice_id' => $invoice->id, 'amount' => $charge['amount']], JSON_UNESCAPED_SLASHES),
                    'result' => 'init_failed_http_'.$response->status(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Unable to connect to the payment gateway right now. Please try again shortly.',
                ];
            }

            GatewayLog::create([
                'gateway' => self::NAME,
                'data' => json_encode([
                    'invoice_id' => $invoice->id,
                    'pp_id' => $json['pp_id'] ?? null,
                    'charged_bdt' => $charge['amount'],
                    'due_amount' => $charge['due'],
                    'rate' => $charge['rate'],
                ], JSON_UNESCAPED_SLASHES),
                'result' => 'initiated',
            ]);

            return [
                'success' => true,
                'payment_id' => isset($json['pp_id']) ? (string) $json['pp_id'] : null,
                'checkout_url' => $ppUrl,
                'redirect' => true,
            ];
        } catch (\Throwable $e) {
            Log::error('PipraPay capture exception', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Payment service is temporarily unreachable. Please try again in a moment.',
            ];
        }
    }

    /**
     * Verify a PipraPay payment ID server-to-server.
     *
     * @return array{success: bool, invoice_id?: int, transaction_id?: string, charged_amount?: float, metadata?: array, message?: string}
     */
    public function verifyCharge(string $ppId): array
    {
        $ppId = trim($ppId);
        $apiKey = (string) $this->getSetting('api_key', '');
        $baseUrl = $this->normalizedBaseUrl();

        if ($ppId === '' || strlen($ppId) > 128 || $apiKey === '' || $baseUrl === null) {
            return ['success' => false, 'message' => 'Invalid verification parameters.'];
        }

        try {
            $endpoint = $this->isVersion2()
                ? $baseUrl.'/api/verify-payments'
                : $baseUrl.'/verify-payment';

            $headerName = $this->isVersion2() ? 'mh-piprapay-api-key' : 'MHS-PIPRAPAY-API-KEY';

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                $headerName => $apiKey,
            ])
                ->timeout(25)
                ->connectTimeout(10)
                ->asJson()
                ->post($endpoint, ['pp_id' => $ppId]);

            if (! $response->successful()) {
                return ['success' => false, 'message' => 'Verification HTTP '.$response->status()];
            }

            $verified = $response->json();
            if (! is_array($verified)) {
                return ['success' => false, 'message' => 'Invalid JSON from verification endpoint.'];
            }

            $status = strtolower(trim((string) ($verified['status'] ?? '')));
            if ($status !== 'completed') {
                return [
                    'success' => false,
                    'status' => $status,
                    'message' => 'Payment is not completed yet.',
                ];
            }

            $meta = is_array($verified['metadata'] ?? null) ? $verified['metadata'] : [];
            $invoiceId = (int) ($meta['invoiceid'] ?? $meta['invoice_id'] ?? 0);

            return [
                'success' => true,
                'transaction_id' => (string) ($verified['pp_id'] ?? $ppId),
                'invoice_id' => $invoiceId,
                'charged_amount' => (float) ($verified['amount'] ?? 0),
                'metadata' => $meta,
            ];
        } catch (\Throwable $e) {
            Log::error('PipraPay verify exception', ['error' => $e->getMessage()]);

            return ['success' => false, 'message' => 'Verification call failed.'];
        }
    }

    public function validateWebhookHeader(?string $receivedHeaderKey): bool
    {
        $configured = (string) $this->getSetting('api_key', '');
        if ($configured === '') {
            return false;
        }

        if ($receivedHeaderKey !== null && $receivedHeaderKey !== '') {
            return hash_equals($configured, trim($receivedHeaderKey));
        }

        // V2 mandates the header; V3+ webhooks may omit the header and rely on server-to-server verifyCharge().
        return ! $this->isVersion2();
    }

    public function resolveSettlementAmount(Invoice $invoice, float $chargedAmount, array $metadata = []): float
    {
        $charge = $this->calculateCharge($invoice, $invoice->amountDue());
        $expectedCharged = $charge['amount'];
        $rate = (float) ($metadata['rate'] ?? $charge['rate']);
        $quotedDue = (float) ($metadata['due_amount'] ?? $charge['due']);

        if ($expectedCharged > 0 && $chargedAmount + 0.01 >= $expectedCharged && $quotedDue > 0) {
            return $quotedDue;
        }

        if ($rate > 0 && $chargedAmount > 0) {
            return round($chargedAmount / $rate, 2);
        }

        return round($chargedAmount, 2);
    }

    public function processWebhook(array $data): array
    {
        $headerKey = $data['_header_key'] ?? null;
        if (! $this->validateWebhookHeader(is_string($headerKey) ? $headerKey : null)) {
            return ['success' => false, 'unauthorized' => true, 'message' => 'Unauthorized webhook header.'];
        }

        $ppId = trim((string) ($data['pp_id'] ?? ''));
        if ($ppId === '') {
            return ['success' => false, 'message' => 'Missing pp_id.'];
        }

        $verified = $this->verifyCharge($ppId);
        if (! ($verified['success'] ?? false)) {
            return $verified;
        }

        $invoiceId = (int) ($verified['invoice_id'] ?: ($data['metadata']['invoiceid'] ?? 0));
        $invoice = $invoiceId > 0 ? Invoice::find($invoiceId) : null;
        if (! $invoice) {
            return ['success' => false, 'message' => 'Invoice not found.'];
        }

        $meta = ! empty($verified['metadata']) ? $verified['metadata'] : (is_array($data['metadata'] ?? null) ? $data['metadata'] : []);
        $chargedAmount = (float) ($verified['charged_amount'] ?: ($data['amount'] ?? 0));
        $settlementAmount = $this->resolveSettlementAmount($invoice, $chargedAmount, $meta);

        return [
            'success' => true,
            'invoice_id' => $invoice->id,
            'transaction_id' => (string) $verified['transaction_id'],
            'amount' => $settlementAmount,
            'gateway' => self::NAME,
        ];
    }

    public function refund(string $transactionId, float $amount): array
    {
        return [
            'success' => false,
            'message' => 'Automated refunds are not supported by PipraPay; please process refunds from the PipraPay merchant dashboard.',
        ];
    }

    public function getPaymentForm(Invoice $invoice): string
    {
        $due = $invoice->amountDue();
        $charge = $this->calculateCharge($invoice, $due);
        $display = e(invoice_money_fmt($due, $invoice));
        $bdtNote = strtoupper(shop_currency_code()) !== 'BDT'
            ? ' <span class="text-muted" style="font-size:12px;">('.e(number_format($charge['amount'], 2)).' BDT)</span>'
            : '';

        $captureUrl = e(route('modules.gateways.piprapay.capture', $invoice));
        $csrf = e(csrf_token());

        return <<<HTML
<div class="gw-pay-option" style="padding:4px 0;">
    <p style="margin:0 0 12px;font-size:13.5px;color:var(--muted);">
        Pay securely via bKash, Nagad, Rocket, Upay, or local bank cards.{$bdtNote}
    </p>
    <form method="POST" action="{$captureUrl}">
        <input type="hidden" name="_token" value="{$csrf}">
        <button type="submit" class="pn-btn pn-btn-primary" style="width:100%;justify-content:center;padding:11px 18px;font-weight:700;">
            <i class="ri-shield-check-line"></i> Pay {$display} Now
        </button>
    </form>
</div>
HTML;
    }

    private function isValidRedirectUrl(string $url): bool
    {
        if ($url === '') {
            return false;
        }
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($host === '') {
            return false;
        }

        if ($scheme === 'https') {
            return true;
        }

        return $scheme === 'http'
            && app()->environment(['local', 'testing'])
            && in_array($host, ['127.0.0.1', 'localhost'], true);
    }
}
