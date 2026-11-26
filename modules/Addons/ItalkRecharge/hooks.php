<?php

use App\Models\Invoice;
use Modules\Addons\ItalkRecharge\ItalkRechargeModule;
use Modules\Addons\ItalkRecharge\Services\RechargeService;

/*
 * Phase 2 - an invoice being paid is what tops up an IPTSP wallet.
 *
 * The legacy addon hung this off InvoicePaid too, but then derived the amount
 * and provider from the invoice notes or, failing that, multiplied the invoice
 * total by a fixed rate - so paying any invoice for a client who happened to
 * have a PIN looked like a top-up. Here the invoice must already have a pending
 * top-up log or an open recharge request against it; anything else is left
 * alone.
 */
add_hook('InvoicePaid', 1, function (array $vars) {
    $invoice = $vars['invoice'] ?? null;

    if (! $invoice instanceof Invoice || ! ItalkRechargeModule::autoRechargeEnabled()) {
        return null;
    }

    try {
        app(RechargeService::class)->handlePaidInvoice($invoice);
    } catch (Throwable $e) {
        // A provider outage must never roll back a payment that succeeded.
        report($e);
    }

    return null;
});
