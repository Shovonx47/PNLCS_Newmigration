<?php

namespace Modules\Addons\ResellerCenter\Services;

use App\Enums\ServiceStatus;
use App\Models\AddonSetting;
use App\Models\Client;
use App\Models\Credit;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Service;
use App\Models\ServiceAddon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Addons\ResellerCenter\Models\Reseller;
use Modules\Addons\ResellerCenter\Models\ResellerClient;
use Modules\Addons\ResellerCenter\Models\ResellerInvoice;
use Modules\Addons\ResellerCenter\Models\ResellerLog;
use Modules\Addons\ResellerCenter\Models\ResellerPayout;
use Modules\Addons\ResellerCenter\Models\ResellerPricing;
use Modules\Addons\ResellerCenter\Models\ResellerPromo;

class ResellerService
{
    public const MODULE_NAME = 'reseller_center';

    /**
     * Find the active reseller owning a given end-client, if any.
     */
    public function resellerForEndClient(int $clientId): ?Reseller
    {
        if (! Schema::hasTable('reseller_clients') || ! Schema::hasTable('resellers')) {
            return null;
        }

        $link = ResellerClient::where('client_id', $clientId)->first();
        if (! $link) {
            return null;
        }

        return Reseller::with('group')->where('id', $link->reseller_id)->where('status', 'active')->first();
    }

    /**
     * Idempotently compute and record commission when an end-client invoice is paid.
     */
    public function processPaidInvoice(Invoice $invoice): ?ResellerPayout
    {
        if (! Schema::hasTable('reseller_invoices') || ! Schema::hasTable('reseller_payouts')) {
            return null;
        }

        $reseller = $this->resellerForEndClient((int) $invoice->client_id);
        if (! $reseller || ! $reseller->group) {
            return null;
        }

        return DB::transaction(function () use ($invoice, $reseller) {
            $alreadyProcessed = ResellerInvoice::where('reseller_id', $reseller->id)
                ->where('invoice_id', $invoice->id)
                ->lockForUpdate()
                ->exists();

            if ($alreadyProcessed) {
                return null;
            }

            $invoice->loadMissing('items');
            $profit = $this->calculateInvoiceProfit($reseller, $invoice);

            if ($profit <= 0) {
                return null;
            }

            ResellerInvoice::create([
                'reseller_id' => $reseller->id,
                'invoice_id' => $invoice->id,
                'profit' => $profit,
                'status' => 'pending',
            ]);

            $payout = ResellerPayout::create([
                'reseller_id' => $reseller->id,
                'invoice_id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'amount' => $profit,
                'payout_method' => $reseller->group->payout_method ?: 'credit_balance',
                'status' => 'pending',
                'description' => 'Commission for Invoice #'.($invoice->invoice_num ?: $invoice->id),
            ]);

            $this->log(
                $reseller->id,
                null,
                'commission_earned',
                'Commission '.number_format($profit, 2).' earned from Invoice #'.($invoice->invoice_num ?: $invoice->id),
                request()?->ip()
            );

            return $payout;
        });
    }

    /**
     * Compute commission total across product/addon invoice lines.
     */
    public function calculateInvoiceProfit(Reseller $reseller, Invoice $invoice): float
    {
        $group = $reseller->group;
        if (! $group) {
            return 0.0;
        }

        $defaultPercent = max(0.0, (float) ($group->default_commission_percent ?? 10.0));
        $profit = 0.0;

        foreach ($invoice->items as $item) {
            $lineAmount = max(0.0, (float) $item->amount * max(1, (int) ($item->qty ?? 1)));
            if ($lineAmount <= 0) {
                continue;
            }

            [$ruleType, $relId, $cycle] = $this->resolveItemPricingTarget($item);
            if ($ruleType === null) {
                continue;
            }

            $rule = $this->matchPricingRule($group->id, $ruleType, $relId, $cycle);
            if ($rule) {
                $profit += $this->applyPricingRule($rule, $lineAmount);
            } elseif ($defaultPercent > 0) {
                $profit += round($lineAmount * $defaultPercent / 100, 2);
            }
        }

        return round(max(0.0, $profit), 2);
    }

    /**
     * Mark a pending payout as paid, crediting the reseller balance and optional client credit or PayPal payout.
     */
    public function markPayoutPaid(ResellerPayout $payout, ?int $adminId = null, string $ip = ''): bool
    {
        return DB::transaction(function () use ($payout, $adminId, $ip) {
            /** @var ResellerPayout|null $locked */
            $locked = ResellerPayout::whereKey($payout->id)->lockForUpdate()->first();
            if (! $locked || $locked->status !== 'pending') {
                return false;
            }

            $reseller = Reseller::whereKey($locked->reseller_id)->lockForUpdate()->first();
            if (! $reseller) {
                return false;
            }

            $method = strtolower((string) $locked->payout_method);
            $amount = (float) $locked->amount;

            if ($method === 'paypal' && filter_var((string) $reseller->paypal_email, FILTER_VALIDATE_EMAIL)) {
                $dispatched = $this->attemptPaypalPayout($reseller->paypal_email, $amount, $locked);
                if (! $dispatched['success']) {
                    Log::warning('ResellerCenter: PayPal payout fallback to balance credit', [
                        'payout_id' => $locked->id,
                        'reason' => $dispatched['message'] ?? 'unavailable',
                    ]);
                }
            }

            $reseller->increment('balance', $amount);

            if ($method === 'credit_balance' && $reseller->client) {
                $reseller->client->increment('credit', $amount);
                Credit::create([
                    'client_id' => $reseller->client_id,
                    'admin_id' => $adminId,
                    'date' => now()->toDateString(),
                    'description' => 'Reseller Commission Payout #'.$locked->id,
                    'amount' => $amount,
                ]);
            }

            $locked->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            if ($locked->invoice_id) {
                ResellerInvoice::where('reseller_id', $reseller->id)
                    ->where('invoice_id', $locked->invoice_id)
                    ->update(['status' => 'processed']);
            }

            $this->log(
                $reseller->id,
                $adminId,
                'payout_paid',
                'Paid commission payout #'.$locked->id.' amount '.number_format($amount, 2).' via '.$method,
                $ip ?: request()?->ip()
            );

            return true;
        });
    }

    /**
     * Pay all pending payouts across the selected reseller IDs.
     *
     * @param  list<int>  $resellerIds
     * @return array{count: int, total: float}
     */
    public function massPayout(array $resellerIds, ?int $adminId = null, string $ip = ''): array
    {
        $count = 0;
        $total = 0.0;

        $pending = ResellerPayout::whereIn('reseller_id', array_map('intval', $resellerIds))
            ->where('status', 'pending')
            ->orderBy('id')
            ->get();

        foreach ($pending as $payout) {
            if ($this->markPayoutPaid($payout, $adminId, $ip)) {
                $count++;
                $total += (float) $payout->amount;
            }
        }

        return ['count' => $count, 'total' => round($total, 2)];
    }

    /**
     * Suspend or unsuspend an end-client service on behalf of its authorized reseller.
     *
     * @return array{success: bool, message: string}
     */
    public function toggleEndClientServiceStatus(Reseller $reseller, Service $service, string $action): array
    {
        $belongs = ResellerClient::where('reseller_id', $reseller->id)
            ->where('client_id', $service->client_id)
            ->exists();

        if (! $belongs) {
            return ['success' => false, 'message' => 'This service does not belong to your end clients.'];
        }

        if (! $reseller->group || ! $reseller->group->allow_suspend) {
            return ['success' => false, 'message' => 'Service suspension is disabled for your reseller tier.'];
        }

        if ($action === 'suspend' && strtolower((string) $service->status) === ServiceStatus::Active->value) {
            $service->update([
                'status' => ServiceStatus::Suspended->value,
                'suspension_date' => now(),
                'suspension_reason' => 'Suspended by Reseller #'.$reseller->id,
            ]);
            $this->log($reseller->id, null, 'reseller_suspend_service', "Suspended end-client service #{$service->id}", request()?->ip());

            return ['success' => true, 'message' => "Service #{$service->id} has been suspended."];
        }

        if ($action === 'unsuspend' && strtolower((string) $service->status) === ServiceStatus::Suspended->value) {
            $service->update([
                'status' => ServiceStatus::Active->value,
                'suspension_date' => null,
                'suspension_reason' => null,
            ]);
            $this->log($reseller->id, null, 'reseller_unsuspend_service', "Unsuspended end-client service #{$service->id}", request()?->ip());

            return ['success' => true, 'message' => "Service #{$service->id} has been reactivated."];
        }

        return ['success' => false, 'message' => 'Action is not valid for the current service state.'];
    }

    /**
     * Validate a reseller promo code for an end-client owned by that reseller.
     */
    public function validateResellerPromo(string $code, Client $client): ?ResellerPromo
    {
        $reseller = $this->resellerForEndClient((int) $client->id);
        if (! $reseller || ! $reseller->group?->allow_promos) {
            return null;
        }

        $promo = ResellerPromo::where('reseller_id', $reseller->id)
            ->whereRaw('LOWER(code) = ?', [strtolower(trim($code))])
            ->first();

        return ($promo && $promo->isCurrentlyUsable()) ? $promo : null;
    }

    /**
     * Compute aggregate revenue and profit metrics for one reseller.
     *
     * @return array{end_clients: int, total_sales: float, last_month_sales: float, processed_profit: float, pending_payouts: float, paid_payouts: float}
     */
    public function resellerMetrics(Reseller $reseller): array
    {
        $clientIds = ResellerClient::where('reseller_id', $reseller->id)->pluck('client_id');

        $totalSales = $clientIds->isEmpty()
            ? 0.0
            : (float) Invoice::whereIn('client_id', $clientIds)->where('status', 'paid')->sum('total');

        $lastStart = now()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $lastEnd = now()->subMonthNoOverflow()->endOfMonth()->toDateString();

        $lastMonthSales = $clientIds->isEmpty()
            ? 0.0
            : (float) Invoice::whereIn('client_id', $clientIds)
                ->where('status', 'paid')
                ->whereBetween('date_paid', [$lastStart, $lastEnd.' 23:59:59'])
                ->sum('total');

        $processedProfit = (float) ResellerInvoice::where('reseller_id', $reseller->id)
            ->where('status', 'processed')
            ->sum('profit');

        $pendingPayouts = (float) ResellerPayout::where('reseller_id', $reseller->id)
            ->where('status', 'pending')
            ->sum('amount');

        $paidPayouts = (float) ResellerPayout::where('reseller_id', $reseller->id)
            ->where('status', 'paid')
            ->sum('amount');

        return [
            'end_clients' => $clientIds->count(),
            'total_sales' => round($totalSales, 2),
            'last_month_sales' => round($lastMonthSales, 2),
            'processed_profit' => round($processedProfit, 2),
            'pending_payouts' => round($pendingPayouts, 2),
            'paid_payouts' => round($paidPayouts, 2),
        ];
    }

    public function log(?int $resellerId, ?int $adminId, string $action, string $description, ?string $ip = null): void
    {
        if (! Schema::hasTable('reseller_logs')) {
            return;
        }

        ResellerLog::create([
            'reseller_id' => $resellerId,
            'admin_id' => $adminId,
            'action' => substr($action, 0, 80),
            'description' => $description,
            'ip_address' => $ip ? substr($ip, 0, 45) : null,
        ]);
    }

    /**
     * Neutralize spreadsheet formula prefixes (=, +, -, @, tab, CR) before CSV output.
     */
    public static function sanitizeCsvCell(mixed $value): string
    {
        $text = (string) $value;
        if ($text !== '' && preg_match('/^[=+\-@\t\r]/', $text) === 1) {
            return "'".$text;
        }

        return $text;
    }

    /**
     * @return array{0: string|null, 1: int, 2: string}
     */
    private function resolveItemPricingTarget(InvoiceItem $item): array
    {
        $type = strtolower(trim((string) $item->type));

        if (in_array($type, ['hosting', 'service'], true)) {
            $service = $item->rel_id > 0 ? Service::find((int) $item->rel_id) : null;

            return ['product', (int) ($service?->product_id ?? 0), strtolower((string) ($service?->billing_cycle ?? ''))];
        }

        if ($type === 'addon') {
            $addon = $item->rel_id > 0 ? ServiceAddon::find((int) $item->rel_id) : null;

            return ['addon', (int) ($addon?->addon_id ?? $item->rel_id), strtolower((string) ($addon?->billing_cycle ?? ''))];
        }

        if (in_array($type, ['item', 'setup', ''], true)) {
            return ['product', (int) $item->rel_id, ''];
        }

        return [null, 0, ''];
    }

    private function matchPricingRule(int $groupId, string $type, int $relId, string $cycle): ?ResellerPricing
    {
        if ($relId <= 0) {
            return null;
        }

        $rules = ResellerPricing::where('group_id', $groupId)
            ->where('type', $type)
            ->where('rel_id', $relId)
            ->get();

        if ($rules->isEmpty()) {
            return null;
        }

        return $rules->first(fn (ResellerPricing $r) => strtolower((string) $r->billing_cycle) === $cycle)
            ?? $rules->first(fn (ResellerPricing $r) => trim((string) $r->billing_cycle) === '');
    }

    private function applyPricingRule(ResellerPricing $rule, float $lineAmount): float
    {
        $val = (float) $rule->payout_value;

        return match (strtolower((string) $rule->payout_type)) {
            'fixed' => max(0.0, round($val, 2)),
            'difference' => max(0.0, round($lineAmount - (float) ($rule->min_price ?? 0), 2)),
            'percent_difference' => max(0.0, round(($lineAmount - (float) ($rule->min_price ?? 0)) * $val / 100, 2)),
            default => max(0.0, round($lineAmount * $val / 100, 2)),
        };
    }

    /**
     * Dispatch PayPal Payouts API v1 if operator configured credentials; returns cleanly on local/missing keys.
     *
     * @return array{success: bool, message?: string}
     */
    private function attemptPaypalPayout(string $email, float $amount, ResellerPayout $payout): array
    {
        $clientId = trim((string) AddonSetting::getSetting(self::MODULE_NAME, 'paypal_client_id', ''));
        $secret = trim((string) AddonSetting::getSetting(self::MODULE_NAME, 'paypal_secret', ''));

        if ($clientId === '' || $secret === '') {
            return ['success' => false, 'message' => 'PayPal payout credentials not configured.'];
        }

        try {
            $tokenResp = Http::asForm()
                ->withBasicAuth($clientId, $secret)
                ->timeout(15)
                ->post('https://api-m.paypal.com/v1/oauth2/token', ['grant_type' => 'client_credentials']);

            if (! $tokenResp->successful() || ! $tokenResp->json('access_token')) {
                return ['success' => false, 'message' => 'PayPal auth failed (HTTP '.$tokenResp->status().')'];
            }

            $resp = Http::withToken((string) $tokenResp->json('access_token'))
                ->timeout(20)
                ->post('https://api-m.paypal.com/v1/payments/payouts', [
                    'sender_batch_header' => [
                        'sender_batch_id' => 'pnlcs-rc-'.$payout->id.'-'.time(),
                        'email_subject' => 'Reseller Commission Payout',
                    ],
                    'items' => [[
                        'recipient_type' => 'EMAIL',
                        'amount' => [
                            'value' => number_format($amount, 2, '.', ''),
                            'currency' => shop_currency_code(),
                        ],
                        'receiver' => $email,
                        'note' => (string) ($payout->description ?: 'Reseller Payout'),
                        'sender_item_id' => 'payout-'.$payout->id,
                    ]],
                ]);

            return $resp->successful()
                ? ['success' => true]
                : ['success' => false, 'message' => 'PayPal payout HTTP '.$resp->status()];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
