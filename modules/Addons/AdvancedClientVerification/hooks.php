<?php

use App\Models\Cart;
use App\Models\Client;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Modules\Addons\AdvancedClientVerification\Models\AcvVerification;
use Modules\Addons\AdvancedClientVerification\Services\KycService;

add_hook('ClientAreaCheckoutRedirect', 5, function (array $vars) {
    $cart = $vars['cart'] ?? null;
    $clientId = isset($vars['clientId']) ? (int) $vars['clientId'] : null;

    if (! $cart instanceof Cart || ! $clientId) {
        return null;
    }

    $kyc = app(KycService::class);
    if ($kyc->requiresCheckoutVerification($cart, $clientId)) {
        return redirect()->route('client.kyc.index')
            ->with('warning', 'Please complete identity verification before placing this order.');
    }

    return null;
});

add_hook('ShoppingCartValidateCheckout', 5, function (array $vars) {
    $cart = $vars['cart'] ?? null;
    $clientId = isset($vars['clientId']) ? (int) $vars['clientId'] : null;

    if (! $cart instanceof Cart) {
        return null;
    }

    $kyc = app(KycService::class);
    if ($kyc->requiresCheckoutVerification($cart, $clientId)) {
        return 'Identity verification is required before ordering this product. Please complete verification from your account portal first.';
    }

    return null;
});

add_hook('ClientAreaHomePagePanels', 8, function (array $vars) {
    if (! auth('web')->check() || ! Schema::hasTable('acv_verifications')) {
        return null;
    }

    try {
        $kyc = app(KycService::class);
        if (! $kyc->flagEnabled('client_access')) {
            return null;
        }

        $user = auth('web')->user();
        $clientId = session('active_client_id') ?: $user?->clients()->value('clients.id');
        if (! $clientId) {
            return null;
        }

        $verification = AcvVerification::where('client_id', $clientId)->first();
        if ($verification && $verification->status === 'verified') {
            return null;
        }

        $showPanel = $verification !== null
            || ($kyc->settings()['verification_requirement'] ?? 'disabled') !== 'disabled'
            || $kyc->flagEnabled('enable_service_suspension');

        if (! $showPanel) {
            return null;
        }

        return View::make('acv::client.banner', [
            'verification' => $verification,
        ])->render();
    } catch (Throwable $e) {
        report($e);

        return null;
    }
});

add_hook('AdminAreaClientSummaryOutput', 8, function (array $vars) {
    $client = $vars['client'] ?? null;
    if (! $client instanceof Client || ! Schema::hasTable('acv_verifications')) {
        return null;
    }

    try {
        $v = AcvVerification::where('client_id', $client->id)->first();
        $st = $v?->status ?? 'not_started';
        $cls = $st === 'verified' ? 'badge-active' : ($st === 'pending' ? 'badge-pending' : 'badge-cancelled');
        $label = ucfirst(str_replace('_', ' ', $st));
        $href = $v ? e(route('admin.extensions.kyc.show', $v)) : e(route('admin.extensions.kyc.index', ['q' => $client->email]));

        return '<div style="display:flex;align-items:center;gap:6px;"><strong style="font-size:13px;">KYC:</strong>'
            .'<a href="'.$href.'" style="text-decoration:none;"><span class="badge '.$cls.'">'.e($label).'</span></a></div>';
    } catch (Throwable $e) {
        report($e);

        return null;
    }
});
