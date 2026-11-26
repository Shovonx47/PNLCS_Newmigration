<?php

namespace Modules\Gateways\ResellerGateway;

use App\Contracts\GatewayModuleInterface;
use App\Models\GatewayLog;
use App\Models\GatewaySettings;
use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Addons\ResellerCenter\Models\Reseller;
use Modules\Addons\ResellerCenter\Models\ResellerClient;
use Modules\Addons\ResellerCenter\Models\ResellerGateway;

class ResellerGatewayModule implements GatewayModuleInterface
{
    public const NAME = 'resellergateway';

    public function getModuleName(): string
    {
        return 'Reseller Managed Gateway';
    }

    public function isTokenised(): bool
    {
        return false;
    }

    public function getConfigFields(): array
    {
        return [
            [
                'name' => 'exchange_rate',
                'label' => 'USD to BDT Exchange Rate',
                'type' => 'text',
                'default' => '120',
                'description' => 'Used when a reseller PipraPay gateway settles a non-BDT invoice.',
            ],
            [
                'name' => 'fallback_note',
                'label' => 'Fallback Instructions',
                'type' => 'textarea',
                'default' => 'Please use the payment instructions provided by your partner reseller or contact support.',
                'description' => 'Shown when no active reseller gateway is assigned for the customer.',
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

    /**
     * Resolve the active reseller and prioritized enabled gateway for this invoice's client.
     *
     * @return array{reseller: Reseller, gateway: ResellerGateway, settings: array<string, mixed>}|null
     */
    public function resolveActiveResellerGateway(Invoice $invoice): ?array
    {
        if (! Schema::hasTable('reseller_clients') || ! Schema::hasTable('resellers') || ! Schema::hasTable('reseller_gateways')) {
            return null;
        }

        $link = ResellerClient::where('client_id', (int) $invoice->client_id)->first();
        if (! $link) {
            return null;
        }

        $reseller = Reseller::whereKey($link->reseller_id)->where('status', 'active')->first();
        if (! $reseller) {
            return null;
        }

        $enabled = ResellerGateway::where('reseller_id', $reseller->id)
            ->where('enabled', true)
            ->orderBy('id')
            ->get();

        if ($enabled->isEmpty()) {
            return null;
        }

        $chosen = $enabled->firstWhere('gateway', 'piprapay')
            ?? $enabled->firstWhere('gateway', 'paypal')
            ?? $enabled->firstWhere('gateway', 'banktransfer')
            ?? $enabled->first();

        if (! $chosen) {
            return null;
        }

        return [
            'reseller' => $reseller,
            'gateway' => $chosen,
            'settings' => is_array($chosen->settings) ? $chosen->settings : [],
        ];
    }

    public function getPaymentForm(Invoice $invoice): string
    {
        $resolved = $this->resolveActiveResellerGateway($invoice);
        if (! $resolved) {
            $fallback = e((string) $this->getSetting(
                'fallback_note',
                'No partner reseller payment gateway is configured for your account yet. Please choose another payment method or contact support.'
            ));

            return '<div class="gw-pay-option" style="padding:4px 0;"><p style="margin:0;font-size:13.5px;color:var(--muted);">'.$fallback.'</p></div>';
        }

        $reseller = $resolved['reseller'];
        $gw = $resolved['gateway'];
        $cfg = $resolved['settings'];
        $brandName = e((string) ($reseller->company_name ?: 'Reseller Partner'));
        $label = e((string) ($cfg['display_name'] ?? ucfirst($gw->gateway)));

        if ($gw->gateway === 'banktransfer') {
            $instructions = nl2br(e((string) ($cfg['instructions'] ?? 'Please remit invoice total using your partner reseller billing instructions.')));

            return <<<HTML
<div class="gw-pay-option" style="padding:4px 0;">
    <div style="font-size:13px;font-weight:700;margin-bottom:6px;">{$brandName} — {$label}</div>
    <div style="font-size:13px;color:var(--muted);line-height:1.55;">{$instructions}</div>
</div>
HTML;
        }

        $due = $invoice->amountDue();
        $display = e(invoice_money_fmt($due, $invoice));
        $captureUrl = e(route('modules.gateways.resellergateway.capture', $invoice));
        $csrf = e(csrf_token());

        return <<<HTML
<div class="gw-pay-option" style="padding:4px 0;">
    <p style="margin:0 0 12px;font-size:13.5px;color:var(--muted);">
        Pay via <strong>{$brandName}</strong> ({$label}).
    </p>
    <form method="POST" action="{$captureUrl}">
        <input type="hidden" name="_token" value="{$csrf}">
        <button type="submit" class="pn-btn pn-btn-primary" style="width:100%;justify-content:center;padding:11px 18px;font-weight:700;">
            Pay {$display} via {$label}
        </button>
    </form>
</div>
HTML;
    }

    public function capture(Invoice $invoice, float $amount, array $params = []): array
    {
        $resolved = $this->resolveActiveResellerGateway($invoice);
        if (! $resolved) {
            return [
                'success' => false,
                'message' => 'No active payment gateway is configured for this account\'s reseller.',
            ];
        }

        /** @var Reseller $reseller */
        $reseller = $resolved['reseller'];
        /** @var ResellerGateway $gw */
        $gw = $resolved['gateway'];
        $cfg = $resolved['settings'];

        $returnUrl = $params['redirect_url'] ?? route('modules.gateways.resellergateway.return', $invoice);
        $webhookUrl = $params['webhook_url'] ?? route('modules.gateways.resellergateway.webhook', ['reseller' => $reseller->id]);

        if ($gw->gateway === 'paypal') {
            $paypalEmail = trim((string) ($cfg['paypal_email'] ?? $reseller->paypal_email ?? ''));
            if (! filter_var($paypalEmail, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Reseller PayPal merchant email is not configured.'];
            }

            $query = http_build_query([
                'cmd' => '_xclick',
                'business' => $paypalEmail,
                'item_name' => ($reseller->company_name ?: 'Hosting').' Invoice #'.($invoice->invoice_num ?: $invoice->id),
                'amount' => number_format(max(0.01, $amount), 2, '.', ''),
                'currency_code' => strtoupper((string) ($invoice->source_currency ?: shop_currency_code())),
                'invoice' => (string) $invoice->id,
                'return' => $returnUrl,
                'cancel_return' => route('client.invoices.show', $invoice).'?payment=cancelled',
            ]);

            GatewayLog::create([
                'gateway' => self::NAME,
                'data' => json_encode(['invoice_id' => $invoice->id, 'reseller_id' => $reseller->id, 'proxy' => 'paypal'], JSON_UNESCAPED_SLASHES),
                'result' => 'initiated_paypal',
            ]);

            return [
                'success' => true,
                'checkout_url' => 'https://www.paypal.com/cgi-bin/webscr?'.$query,
                'redirect' => true,
            ];
        }

        if ($gw->gateway !== 'piprapay') {
            return [
                'success' => false,
                'message' => 'Reseller gateway '.$gw->gateway.' uses manual instructions rather than instant redirect.',
            ];
        }

        $baseUrl = $this->normalizeGatewayUrl((string) ($cfg['base_url'] ?? ''));
        $apiKey = trim((string) ($cfg['api_key'] ?? ''));
        if ($baseUrl === null || $apiKey === '') {
            return [
                'success' => false,
                'message' => 'Reseller PipraPay credentials are incomplete or use an insecure endpoint.',
            ];
        }

        $invoice->loadMissing('client');
        $client = $invoice->client;
        $fullName = trim((string) ($client?->display_name ?: (($client?->first_name ?? '').' '.($client?->last_name ?? '')))) ?: 'Customer';
        $email = trim((string) ($client?->email ?? '')) ?: 'billing@softcents.com';
        $phone = preg_replace('/[^0-9+]/', '', (string) ($client?->full_phone ?: ($client?->phone_number ?? ''))) ?: '01700000000';

        $charge = $this->calculateBdtCharge($invoice, $amount);

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'MHS-PIPRAPAY-API-KEY' => $apiKey,
            ])
                ->timeout(25)
                ->connectTimeout(10)
                ->asJson()
                ->post($baseUrl.'/checkout/redirect', [
                    'full_name' => $fullName,
                    'email_address' => $email,
                    'mobile_number' => $phone,
                    'amount' => $charge['amount'],
                    'currency' => 'BDT',
                    'metadata' => [
                        'invoiceid' => (int) $invoice->id,
                        'reseller_id' => (int) $reseller->id,
                        'due_amount' => number_format($charge['due'], 2, '.', ''),
                        'rate' => number_format($charge['rate'], 5, '.', ''),
                    ],
                    'return_url' => $returnUrl,
                    'webhook_url' => $webhookUrl,
                ]);

            $json = $response->json();
            $ppUrl = is_array($json) ? trim((string) ($json['pp_url'] ?? '')) : '';

            if (! $response->successful() || $this->normalizeGatewayUrl($ppUrl) === null) {
                return [
                    'success' => false,
                    'message' => 'Unable to initiate reseller payment session.',
                ];
            }

            GatewayLog::create([
                'gateway' => self::NAME,
                'data' => json_encode([
                    'invoice_id' => $invoice->id,
                    'reseller_id' => $reseller->id,
                    'proxy' => 'piprapay',
                    'pp_id' => $json['pp_id'] ?? null,
                ], JSON_UNESCAPED_SLASHES),
                'result' => 'initiated_piprapay',
            ]);

            return [
                'success' => true,
                'payment_id' => isset($json['pp_id']) ? (string) $json['pp_id'] : null,
                'checkout_url' => $ppUrl,
                'redirect' => true,
            ];
        } catch (\Throwable $e) {
            Log::error('ResellerGateway capture exception', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);

            return ['success' => false, 'message' => 'Reseller gateway is temporarily unreachable.'];
        }
    }

    /**
     * @return array{success: bool, invoice_id?: int, transaction_id?: string, charged_amount?: float, metadata?: array, message?: string}
     */
    public function verifyPipraPayCharge(ResellerGateway $gw, string $ppId): array
    {
        $cfg = is_array($gw->settings) ? $gw->settings : [];
        $baseUrl = $this->normalizeGatewayUrl((string) ($cfg['base_url'] ?? ''));
        $apiKey = trim((string) ($cfg['api_key'] ?? ''));

        if ($ppId === '' || $baseUrl === null || $apiKey === '') {
            return ['success' => false, 'message' => 'Invalid reseller verification config.'];
        }

        try {
            $resp = Http::withHeaders([
                'Accept' => 'application/json',
                'MHS-PIPRAPAY-API-KEY' => $apiKey,
            ])
                ->timeout(20)
                ->asJson()
                ->post($baseUrl.'/verify-payment', ['pp_id' => $ppId]);

            if (! $resp->successful()) {
                return ['success' => false, 'message' => 'Verification HTTP '.$resp->status()];
            }

            $json = $resp->json();
            if (! is_array($json) || strtolower(trim((string) ($json['status'] ?? ''))) !== 'completed') {
                return ['success' => false, 'message' => 'Payment not completed.'];
            }

            $meta = is_array($json['metadata'] ?? null) ? $json['metadata'] : [];

            return [
                'success' => true,
                'transaction_id' => (string) ($json['pp_id'] ?? $ppId),
                'invoice_id' => (int) ($meta['invoiceid'] ?? $meta['invoice_id'] ?? 0),
                'charged_amount' => (float) ($json['amount'] ?? 0),
                'metadata' => $meta,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function processWebhook(array $data): array
    {
        $resellerId = (int) ($data['_reseller_id'] ?? $data['metadata']['reseller_id'] ?? 0);
        if ($resellerId <= 0) {
            return ['success' => false, 'message' => 'Missing reseller identifier.'];
        }

        $gw = ResellerGateway::where('reseller_id', $resellerId)
            ->where('gateway', 'piprapay')
            ->where('enabled', true)
            ->first();

        if (! $gw) {
            return ['success' => false, 'unauthorized' => true, 'message' => 'Reseller PipraPay not active.'];
        }

        $cfg = is_array($gw->settings) ? $gw->settings : [];
        $expectedKey = trim((string) ($cfg['api_key'] ?? ''));
        $headerKey = trim((string) ($data['_header_key'] ?? ''));
        if ($expectedKey === '' || $headerKey === '' || ! hash_equals($expectedKey, $headerKey)) {
            return ['success' => false, 'unauthorized' => true, 'message' => 'Invalid API key signature.'];
        }

        $ppId = trim((string) ($data['pp_id'] ?? ''));
        if ($ppId === '') {
            return ['success' => false, 'message' => 'Missing pp_id.'];
        }

        $verified = $this->verifyPipraPayCharge($gw, $ppId);
        if (! ($verified['success'] ?? false)) {
            return $verified;
        }

        $invoiceId = (int) ($verified['invoice_id'] ?: ($data['metadata']['invoiceid'] ?? 0));
        $invoice = $invoiceId > 0 ? Invoice::find($invoiceId) : null;
        if (! $invoice) {
            return ['success' => false, 'message' => 'Invoice not found.'];
        }

        $settlement = $this->resolveSettlementAmount(
            $invoice,
            (float) ($verified['charged_amount'] ?? 0),
            (array) ($verified['metadata'] ?? [])
        );

        return [
            'success' => true,
            'invoice_id' => $invoice->id,
            'transaction_id' => (string) $verified['transaction_id'],
            'amount' => $settlement,
            'gateway' => self::NAME,
        ];
    }

    public function refund(string $transactionId, float $amount): array
    {
        return [
            'success' => false,
            'message' => 'Process refunds directly via the assigned reseller\'s payment dashboard.',
        ];
    }

    /**
     * @return array{amount: float, rate: float, due: float}
     */
    public function calculateBdtCharge(Invoice $invoice, float $due): array
    {
        $sourceCurrency = strtoupper((string) ($invoice->source_currency ?: shop_currency_code()));
        if ($sourceCurrency === 'BDT') {
            return ['amount' => round($due, 2), 'rate' => 1.0, 'due' => round($due, 2)];
        }

        $rate = (float) ($invoice->billing_currency === 'BDT' && (float) $invoice->exchange_rate > 0
            ? $invoice->exchange_rate
            : ($this->getSetting('exchange_rate', '120') ?: 120));
        $rate = $rate > 0 ? $rate : 120.0;

        return ['amount' => round($due * $rate, 2), 'rate' => $rate, 'due' => round($due, 2)];
    }

    public function resolveSettlementAmount(Invoice $invoice, float $chargedAmount, array $metadata = []): float
    {
        $charge = $this->calculateBdtCharge($invoice, $invoice->amountDue());
        $rate = (float) ($metadata['rate'] ?? $charge['rate']);
        $quotedDue = (float) ($metadata['due_amount'] ?? $charge['due']);

        if ($charge['amount'] > 0 && $chargedAmount + 0.01 >= $charge['amount'] && $quotedDue > 0) {
            return $quotedDue;
        }

        if ($rate > 0 && $chargedAmount > 0) {
            return round($chargedAmount / $rate, 2);
        }

        return round($chargedAmount, 2);
    }

    private function normalizeGatewayUrl(string $raw): ?string
    {
        $trimmed = rtrim(trim($raw), '/');
        if ($trimmed === '') {
            return null;
        }

        $parts = parse_url($trimmed);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($host === '') {
            return null;
        }

        if ($scheme === 'https') {
            return $trimmed;
        }

        if ($scheme === 'http' && app()->environment(['local', 'testing']) && in_array($host, ['127.0.0.1', 'localhost'], true)) {
            return $trimmed;
        }

        return null;
    }
}
