<?php

use App\Models\Service;
use Modules\Addons\ItalkBalance\Services\BalanceRegistry;

/*
 * Phase 2 - the live balance card on the customer's service page.
 *
 * WHMCS's ClientAreaPageProductDetails appended to a template nothing here
 * renders; this panel fires ClientAreaProductDetailsOutput inside the service
 * page, next to the module's own section, which is the same idea done through
 * the hook the panel actually runs.
 *
 * Returns null (the hook contract for "nothing to add") for every service that
 * has no IP number, so unrelated services are untouched.
 */
add_hook('ClientAreaProductDetailsOutput', 1, function (array $vars) {
    $service = $vars['service'] ?? null;

    if (! $service instanceof Service) {
        return null;
    }

    try {
        return app(BalanceRegistry::class)->card($service);
    } catch (Throwable $e) {
        report($e);

        return null;
    }
});
