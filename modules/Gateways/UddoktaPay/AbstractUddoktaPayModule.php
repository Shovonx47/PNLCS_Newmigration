<?php

namespace Modules\Gateways\UddoktaPay;

use App\Contracts\GatewayModuleInterface;
use App\Models\GatewayLog;
use App\Models\GatewaySettings;
use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class AbstractUddoktaPayModule implements GatewayModuleInterface
{
    abstract public function gatewayKey(): string;

    abstract protected function checkoutEndpoint(): string;

    abstract protected function isInternational(): bool;

    abstract protected function paymentDescription(): string;

    public function isTokenised(): bool
    {
        return false;
    }

    public function getConfigFields(): array
    {
        return [
            [
                'name' => 'api_key',
                'label' => 'UddoktaPay API Key',
                'type' => 'password',
                'required' => true,
            ],
            [
                'name' => 'api_url',
                'label' => 'UddoktaPay API URL',
                'type' => 'text',
                'required' => true,
                'description' => 'HTTPS API URL from your UddoktaPay panel (e.g. https://pay.example.com/api/checkout-v2).',
            ],
            [
                'name' => 'exchange_rate',
                'label' => 'Exchange Rate (1 USD = ? BDT)',
                'type' => 'text',
                'default' => '120',
                'description' => $this->isInternational()
                    ? 'Used to convert BDT invoices into USD for global card checkout.'
                    : 'Used to convert USD invoices into BDT for local MFS checkout.',
            ],
        ];
    }

    public function getSetting(string $key, ?string $default = null): ?string
    {
        $val = GatewaySettings::where('gateway', $this->gatewayKey())
            ->where('setting', $key)
            ->value('value');

        return ($val !== null && trim((string) $val) !== '') ? trim((string) $val) : $default;
    }

    public function normalizedApiBaseUrl(): ?string
    {
        $raw = rtrim((string) $this->getSetting('api_url', ''), '/');
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

        $apiPos = strpos($raw, '/api');
        if ($apiPos !== false) {
            return substr($raw, 0, $apiPos + 4);
        }

        return $raw.'/api';
    }

    /**
     * Work out the gateway charge amount while preserving the exact shop-currency due amount.
     *
     * @return array{amount: float, currency: string, rate: float, due: float}
     */
    public function calculateCharge(Invoice $invoice, float $due): array
    {
        $shopCurrency = strtoupper((string) ($invoice->source_currency ?: shop_currency_code()));
        $configuredRate = (float) $this->getSetting('exchange_rate', '120');
        $rate = $configuredRate > 0 ? $configuredRate : 120.0;

        if ($this->isInternational()) {
            // International gateway charges in USD via checkout-v2/global
            if ($shopCurrency === 'USD') {
                return [
                    'amount' => round($due, 2),
                    'currency' => 'USD',
                    'rate' => 1.0,
                    'due' => round($due, 2),
                ];
            }

            return [
                'amount' => round($due / $rate, 2),
                'currency' => 'USD',
                'rate' => $rate,
                'due' => round($due, 2),
            ];
        }

        // Local gateway charges in BDT via checkout-v2
        if ($shopCurrency === 'BDT') {
            return [
                'amount' => (float) ceil($due),
                'currency' => 'BDT',
                'rate' => 1.0,
                'due' => round($due, 2),
            ];
        }

        return [
            'amount' => (float) ceil($due * $rate),
            'currency' => 'BDT',
            'rate' => $rate,
            'due' => round($due, 2),
        ];
    }

    public function capture(Invoice $invoice, float $amount, array $params = []): array
    {
        $apiKey = (string) $this->getSetting('api_key', '');
        $apiBase = $this->normalizedApiBaseUrl();

        if ($apiKey === '' || $apiBase === null) {
            return [
                'success' => false,
                'message' => 'This payment option is not available right now. Please select another payment method.',
            ];
        }

        $invoice->loadMissing('client');
        $client = $invoice->client;
        $fullName = trim((string) ($client?->display_name ?: (($client?->first_name ?? '').' '.($client?->last_name ?? '')))) ?: 'Valued Customer';
        $email = trim((string) ($client?->email ?? '')) ?: 'billing@softcents.com';

        $charge = $this->calculateCharge($invoice, $amount);
        $gw = $this->gatewayKey();

        $returnUrl = $params['redirect_url'] ?? route("modules.gateways.{$gw}.return", $invoice);
        $cancelUrl = $params['cancel_url'] ?? (route('client.invoices.show', $invoice).'?payment=cancelled');
        $webhookUrl = $params['webhook_url'] ?? route("modules.gateways.{$gw}.webhook");

        $payload = [
            'full_name' => $fullName,
            'email' => $email,
            'amount' => $charge['amount'],
            'metadata' => [
                'invoice_id' => (int) $invoice->id,
                'invoice_num' => (string) ($invoice->invoice_num ?: $invoice->id),
                'due_amount' => number_format($charge['due'], 2, '.', ''),
                'expected_charge' => number_format($charge['amount'], 2, '.', ''),
                'rate' => number_format($charge['rate'], 5, '.', ''),
            ],
            'redirect_url' => $returnUrl,
            'return_type' => 'GET',
            'cancel_url' => $cancelUrl,
            'webhook_url' => $webhookUrl,
        ];

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'RT-UDDOKTAPAY-API-KEY' => $apiKey,
            ])
                ->timeout(30)
                ->connectTimeout(10)
                ->asJson()
                ->post($apiBase.'/'.ltrim($this->checkoutEndpoint(), '/'), $payload);

            $json = $response->json();
            $paymentUrl = is_array($json) ? trim((string) ($json['payment_url'] ?? '')) : '';

            if (! $response->successful() || ! $this->isValidRedirectUrl($paymentUrl)) {
                Log::warning("UddoktaPay ({$gw}) init failed", [
                    'invoice_id' => $invoice->id,
                    'status' => $response->status(),
                ]);

                GatewayLog::create([
                    'gateway' => $gw,
                    'data' => json_encode(['invoice_id' => $invoice->id, 'amount' => $charge['amount']], JSON_UNESCAPED_SLASHES),
                    'result' => 'init_failed_http_'.$response->status(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Could not connect to the payment gateway. Please try again shortly.',
                ];
            }

            GatewayLog::create([
                'gateway' => $gw,
                'data' => json_encode([
                    'invoice_id' => $invoice->id,
                    'charged' => $charge['amount'],
                    'currency' => $charge['currency'],
                    'due_amount' => $charge['due'],
                ], JSON_UNESCAPED_SLASHES),
                'result' => 'initiated',
            ]);

            return [
                'success' => true,
                'checkout_url' => $paymentUrl,
                'redirect' => true,
            ];
        } catch (\Throwable $e) {
            Log::error("UddoktaPay ({$gw}) capture exception", [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Payment service is temporarily unavailable. Please try again.',
            ];
        }
    }

    /**
     * Verify an UddoktaPay payment reference (`invoice_id` in UddoktaPay's payload) server-to-server.
     *
     * @return array{success: bool, status?: string, transaction_id?: string, invoice_id?: int, charged_amount?: float, metadata?: array, message?: string}
     */
    public function verifyPaymentReference(string $upInvoiceId): array
    {
        $upInvoiceId = trim($upInvoiceId);
        $apiKey = (string) $this->getSetting('api_key', '');
        $apiBase = $this->normalizedApiBaseUrl();

        if ($upInvoiceId === '' || strlen($upInvoiceId) > 128 || $apiKey === '' || $apiBase === null) {
            return ['success' => false, 'message' => 'Invalid verification parameters.'];
        }

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'RT-UDDOKTAPAY-API-KEY' => $apiKey,
            ])
                ->timeout(30)
                ->connectTimeout(10)
                ->asJson()
                ->post($apiBase.'/verify-payment', ['invoice_id' => $upInvoiceId]);

            if (! $response->successful()) {
                return ['success' => false, 'message' => 'Verification failed (HTTP '.$response->status().').'];
            }

            $verified = $response->json();
            if (! is_array($verified)) {
                return ['success' => false, 'message' => 'Invalid verification response.'];
            }

            $status = strtoupper(trim((string) ($verified['status'] ?? '')));
            if ($status !== 'COMPLETED') {
                return [
                    'success' => false,
                    'status' => $status,
                    'message' => $status === 'PENDING'
                        ? 'Your payment is pending verification.'
                        : 'Payment has not been completed.',
                ];
            }

            $meta = is_array($verified['metadata'] ?? null) ? $verified['metadata'] : [];
            $localInvoiceId = (int) ($meta['invoice_id'] ?? $meta['invoiceid'] ?? 0);
            $trxId = trim((string) ($verified['transaction_id'] ?? $upInvoiceId));

            return [
                'success' => true,
                'status' => $status,
                'transaction_id' => $trxId,
                'invoice_id' => $localInvoiceId,
                'charged_amount' => (float) ($verified['amount'] ?? $verified['charged_amount'] ?? 0),
                'metadata' => $meta,
            ];
        } catch (\Throwable $e) {
            Log::error('UddoktaPay verify exception', ['error' => $e->getMessage()]);

            return ['success' => false, 'message' => 'Could not verify payment status right now.'];
        }
    }

    public function validateApiKeyHeader(?string $receivedKey): bool
    {
        $configured = (string) $this->getSetting('api_key', '');
        if ($configured === '' || $receivedKey === null || trim($receivedKey) === '') {
            return false;
        }

        return hash_equals($configured, trim($receivedKey));
    }

    public function resolveSettlementAmount(Invoice $invoice, float $chargedAmount, array $metadata = []): ?float
    {
        $charge = $this->calculateCharge($invoice, $invoice->amountDue());
        $expectedCharge = (float) ($metadata['expected_charge'] ?? $charge['amount']);
        $quotedDue = (float) ($metadata['due_amount'] ?? $charge['due']);
        $rate = (float) ($metadata['rate'] ?? $charge['rate']);

        if ($expectedCharge > 0 && $chargedAmount + 0.01 < $expectedCharge) {
            // Partial / under-paid relative to quoted amount: convert back proportionally, never credit full invoice.
            if ($this->isInternational()) {
                return strtoupper(shop_currency_code()) === 'USD'
                    ? round($chargedAmount, 2)
                    : round($chargedAmount * $rate, 2);
            }

            return strtoupper(shop_currency_code()) === 'BDT'
                ? round($chargedAmount, 2)
                : ($rate > 0 ? round($chargedAmount / $rate, 2) : null);
        }

        return $quotedDue > 0 ? $quotedDue : $invoice->amountDue();
    }

    public function processWebhook(array $data): array
    {
        $headerKey = $data['_header_key'] ?? null;
        if (! $this->validateApiKeyHeader(is_string($headerKey) ? $headerKey : null)) {
            return ['success' => false, 'unauthorized' => true, 'message' => 'Invalid API key header.'];
        }

        $upInvoiceId = trim((string) ($data['invoice_id'] ?? ''));
        if ($upInvoiceId === '') {
            return ['success' => false, 'message' => 'Missing UddoktaPay invoice_id.'];
        }

        $verified = $this->verifyPaymentReference($upInvoiceId);
        if (! ($verified['success'] ?? false)) {
            return $verified;
        }

        $invoiceId = (int) ($verified['invoice_id'] ?? 0);
        $invoice = $invoiceId > 0 ? Invoice::find($invoiceId) : null;
        if (! $invoice) {
            return ['success' => false, 'message' => 'Target invoice not found.'];
        }

        $settlement = $this->resolveSettlementAmount(
            $invoice,
            (float) ($verified['charged_amount'] ?? 0),
            (array) ($verified['metadata'] ?? [])
        );

        if ($settlement === null || $settlement <= 0) {
            return ['success' => false, 'message' => 'Invalid settlement amount.'];
        }

        return [
            'success' => true,
            'invoice_id' => $invoice->id,
            'transaction_id' => (string) $verified['transaction_id'],
            'amount' => $settlement,
            'gateway' => $this->gatewayKey(),
        ];
    }

    public function refund(string $transactionId, float $amount): array
    {
        return [
            'success' => false,
            'message' => 'Automated refunds are not supported; please issue refunds via your UddoktaPay dashboard.',
        ];
    }

    public function getPaymentForm(Invoice $invoice): string
    {
        $due = $invoice->amountDue();
        $charge = $this->calculateCharge($invoice, $due);
        $display = e(invoice_money_fmt($due, $invoice));
        $shopCurr = strtoupper(shop_currency_code());
        $convertedHint = $shopCurr !== $charge['currency']
            ? ' <span class="text-muted" style="font-size:12px;">('.e(number_format($charge['amount'], 2).' '.$charge['currency']).')</span>'
            : '';

        $gw = $this->gatewayKey();
        $captureUrl = e(route("modules.gateways.{$gw}.capture", $invoice));
        $csrf = e(csrf_token());
        $desc = e($this->paymentDescription());

        return <<<HTML
<div class="gw-pay-option" style="padding:4px 0;">
    <p style="margin:0 0 12px;font-size:13.5px;color:var(--muted);">
        {$desc}{$convertedHint}
    </p>
    <form method="POST" action="{$captureUrl}">
        <input type="hidden" name="_token" value="{$csrf}">
        <button type="submit" class="pn-btn pn-btn-primary" style="width:100%;justify-content:center;padding:11px 18px;font-weight:700;">
            <i class="ri-bank-card-line"></i> Pay {$display} Now
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
