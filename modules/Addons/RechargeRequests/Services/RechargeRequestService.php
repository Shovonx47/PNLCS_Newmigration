<?php

namespace Modules\Addons\RechargeRequests\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Addons\RechargeRequests\Models\RechargeRequest;

/**
 * One place that turns "top up 096XX by 1000" into an invoice plus a queue
 * entry, so the client page and the dashboard card cannot drift apart.
 *
 * The legacy hook created the invoice with a hardcoded `paymentmethod => 'bkash'`
 * and recalculated the totals through localAPI(). Here the invoice goes through
 * PNLCS's own InvoiceService: numbering lock, buyer/seller snapshot, tax,
 * credit and the InvoiceCreated event all behave exactly as they do for every
 * other invoice.
 */
class RechargeRequestService
{
    /** Below this the voucher top-up is not worth the provider's fee. */
    public const MIN_AMOUNT = 500.0;

    /** The same ceiling the recharge addon enforces on a single top-up. */
    public const MAX_AMOUNT = 100000.0;

    public function __construct(private readonly InvoiceService $invoices) {}

    /**
     * @return array{invoice: Invoice, request: RechargeRequest}
     *
     * @throws ValidationException
     */
    public function request(Client $client, string $ipNumber, float $amount): array
    {
        $ipNumber = trim($ipNumber);

        if (! preg_match('/^[0-9A-Za-z._+\-]{3,64}$/', $ipNumber)) {
            throw ValidationException::withMessages([
                'mobile_number' => 'Enter the IP phone number exactly as it appears on your account.',
            ]);
        }

        if ($amount < self::MIN_AMOUNT) {
            throw ValidationException::withMessages([
                'amount' => 'The minimum top-up is '.money_fmt(self::MIN_AMOUNT).'.',
            ]);
        }

        if ($amount > self::MAX_AMOUNT) {
            throw ValidationException::withMessages([
                'amount' => 'A single top-up may not exceed '.money_fmt(self::MAX_AMOUNT).'.',
            ]);
        }

        return DB::transaction(function () use ($client, $ipNumber, $amount) {
            $invoice = $this->invoices->createInvoice($client, [[
                'type' => 'Recharge',
                'description' => 'IP Number Calling Balance Recharge — '.$ipNumber,
                'qty' => 1,
                'amount' => round($amount, 2),
            ]], [
                'notes' => 'IP number balance recharge requested from the client area ('.$ipNumber.').',
            ]);

            $request = RechargeRequest::create([
                'client_id' => $client->id,
                'amount' => round($amount, 2),
                'mobile_number' => $ipNumber,
                'invoice_id' => $invoice->id,
                'status' => 'Pending',
                'requested_at' => now(),
            ]);

            return ['invoice' => $invoice, 'request' => $request];
        });
    }
}
