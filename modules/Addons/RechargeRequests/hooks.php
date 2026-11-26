<?php

use Illuminate\Support\Facades\View;
use Modules\Addons\RechargeRequests\Services\RechargeRequestService;

/*
 * Phase 2 - the persistent recharge card in the client area.
 *
 * WHMCS rendered this into ClientAreaPrimarySidebar on every client page.
 * PNLCS has no sidebar, so the card goes on the dashboard - the page a customer
 * lands on and the one place a persistent panel belongs - through the footer
 * output hook the client layout fires. The route check keeps it off every other
 * page instead of repeating the form under each invoice and ticket.
 */
add_hook('ClientAreaHomePagePanels', 5, function (array $vars) {
    if (! auth()->check()) {
        return null;
    }

    try {
        return View::make('rechargerequests::card', [
            'minAmount' => RechargeRequestService::MIN_AMOUNT,
            'maxAmount' => RechargeRequestService::MAX_AMOUNT,
        ])->render();
    } catch (Throwable $e) {
        report($e);

        return null;
    }
});
