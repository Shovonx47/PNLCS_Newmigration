<?php

namespace Modules\Addons\ItalkRecharge\Services;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Addons\ItalkRecharge\Contracts\RechargeProvider;
use Modules\Addons\ItalkRecharge\Drivers\ItalkScraperDriver;
use Modules\Addons\ItalkRecharge\Drivers\RanksittDriver;
use Modules\Addons\ItalkRecharge\Drivers\WebvoiceDriver;
use Modules\Addons\ItalkRecharge\Models\ItalkRechargeAccount;
use Modules\Addons\ItalkRecharge\Models\ItalkRechargeLog;
use Modules\Addons\ItalkRecharge\Models\ItalkRechargeProvider;

/**
 * The one path every IPTSP top-up goes through.
 *
 * A log row is written as `pending` BEFORE the provider is called and updated
 * afterwards, so an attempt that times out, crashes or is refused stays
 * visible and retryable. The WHMCS addon only wrote its log for invoices, and
 * only after the call, which is why a failed top-up there left no trace when
 * the request died.
 */
class RechargeService
{
    /** A single top-up is capped so a mistyped invoice cannot drain the float. */
    public const MAX_AMOUNT = 100000.0;

    public const MIN_AMOUNT = 1.0;

    public function driverFor(ItalkRechargeProvider $provider): ?RechargeProvider
    {
        return match ($provider->api_type) {
            'italk', 'scraper' => new ItalkScraperDriver($provider),
            'ranksitt' => new RanksittDriver($provider),
            'webvoice' => new WebvoiceDriver($provider),
            default => null,
        };
    }

    /**
     * Create a pending log and try it immediately.
     *
     * @param  array{invoice_id?: ?int, account_id?: ?int}  $meta
     * @return array{ok: bool, message: string, log: ItalkRechargeLog}
     */
    public function recharge(Client $client, ItalkRechargeProvider $provider, string $pin, float $amount, array $meta = []): array
    {
        $log = ItalkRechargeLog::create([
            'client_id' => $client->id,
            'provider_id' => $provider->id,
            'account_id' => $meta['account_id'] ?? null,
            'invoice_id' => $meta['invoice_id'] ?? null,
            'pin_number' => trim($pin),
            'amount' => round($amount, 2),
            'status' => 'pending',
        ]);

        return $this->attempt($log);
    }

    /**
     * Run (or re-run) one logged attempt.
     *
     * @return array{ok: bool, message: string, log: ItalkRechargeLog}
     */
    public function attempt(ItalkRechargeLog $log): array
    {
        $provider = $log->provider ?: ItalkRechargeProvider::find($log->provider_id);

        if (! $provider || ! $provider->isActive()) {
            return $this->finish($log, false, 'No active provider is assigned to this top-up.');
        }

        $amount = (float) $log->amount;

        if ($amount < self::MIN_AMOUNT || $amount > self::MAX_AMOUNT) {
            return $this->finish($log, false, 'The amount is outside the allowed range.');
        }

        if (trim((string) $log->pin_number) === '') {
            return $this->finish($log, false, 'No IP number is recorded for this top-up.');
        }

        $driver = $this->driverFor($provider);

        if ($driver === null) {
            return $this->finish($log, false, 'Unsupported provider type: '.$provider->api_type.'.');
        }

        $login = $driver->login();

        if (! $login['ok']) {
            return $this->finish($log, false, $login['message']);
        }

        $account = $driver->findAccount((string) $log->pin_number);

        if (! $account['ok'] || $account['id'] === null) {
            return $this->finish($log, false, $account['message']);
        }

        $result = $driver->recharge($account['id'], (string) $log->pin_number, $amount);

        $outcome = $this->finish($log, (bool) $result['ok'], $result['message'], $result['raw'] ?? null);

        if ($result['ok']) {
            $this->creditLocalAccount($log, $provider, $amount);
            $this->completeLinkedRequest($log);
        }

        return $outcome;
    }

    /**
     * What a paid invoice triggers.
     *
     * Two sources, in this order: top-ups already logged as pending against the
     * invoice (an operator queued them), then open client recharge requests for
     * it (the customer bought a top-up from the client area). Neither table
     * having anything means the invoice was not an IPTSP top-up and nothing
     * happens - no guessing from the invoice total, which is how the legacy
     * addon converted unrelated payments into recharges.
     *
     * @return array<int, array{ok: bool, message: string, log: ItalkRechargeLog}>
     */
    public function handlePaidInvoice(Invoice $invoice): array
    {
        $results = [];

        foreach (ItalkRechargeLog::where('invoice_id', $invoice->id)->where('status', 'pending')->get() as $log) {
            $results[] = $this->attempt($log);
        }

        if ($results !== [] || ! Schema::hasTable('recharge_requests')) {
            return $results;
        }

        $requests = DB::table('recharge_requests')
            ->where('invoice_id', $invoice->id)
            ->where('status', 'Pending')
            ->get();

        foreach ($requests as $request) {
            $client = Client::find($request->client_id);

            if (! $client) {
                continue;
            }

            $account = ItalkRechargeAccount::with('provider')
                ->where('client_id', $client->id)
                ->where('status', 'active')
                ->where('pin_number', trim((string) $request->mobile_number))
                ->first();

            // Without a matching PIN account there is no way to know which
            // reseller account the money belongs to: leave the request for a
            // human instead of topping up the wrong number.
            if (! $account || ! $account->provider) {
                continue;
            }

            $results[] = $this->recharge($client, $account->provider, (string) $request->mobile_number, (float) $request->amount, [
                'invoice_id' => $invoice->id,
                'account_id' => $account->id,
            ]);
        }

        return $results;
    }

    /** Put a failed attempt back in the queue and run it again. */
    public function retry(ItalkRechargeLog $log): array
    {
        if ($log->status === 'success') {
            return ['ok' => false, 'message' => 'This top-up already succeeded; it will not be run twice.', 'log' => $log];
        }

        $log->forceFill(['status' => 'pending'])->save();

        return $this->attempt($log);
    }

    /** @param  mixed  $raw */
    private function finish(ItalkRechargeLog $log, bool $ok, string $message, $raw = null): array
    {
        $log->forceFill([
            'status' => $ok ? 'success' : 'failed',
            'attempts' => (int) $log->attempts + 1,
            'last_attempt_at' => now(),
            'api_response' => mb_substr(json_encode([
                'message' => $message,
                'result' => $raw,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: $message, 0, 4000),
        ])->save();

        if (! $ok) {
            Log::warning('IPTSP recharge failed', [
                'log_id' => $log->id,
                'client_id' => $log->client_id,
                'provider_id' => $log->provider_id,
                'message' => $message,
            ]);
        }

        return ['ok' => $ok, 'message' => $message, 'log' => $log];
    }

    private function creditLocalAccount(ItalkRechargeLog $log, ItalkRechargeProvider $provider, float $amount): void
    {
        $account = $log->account_id
            ? ItalkRechargeAccount::find($log->account_id)
            : ItalkRechargeAccount::where('client_id', $log->client_id)->where('provider_id', $provider->id)->first();

        if (! $account) {
            return;
        }

        $account->forceFill([
            'total_recharged' => (float) $account->total_recharged + $amount,
            'last_recharge' => now(),
        ])->save();
    }

    private function completeLinkedRequest(ItalkRechargeLog $log): void
    {
        if (! $log->invoice_id || ! Schema::hasTable('recharge_requests')) {
            return;
        }

        DB::table('recharge_requests')
            ->where('invoice_id', $log->invoice_id)
            ->where('client_id', $log->client_id)
            ->where('status', 'Pending')
            ->update(['status' => 'Completed', 'updated_at' => now()]);
    }
}
