<?php

use App\Models\Client;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Modules\Addons\SupportPin\Services\SupportPinService;

add_hook('ClientAreaHomePagePanels', 12, function (array $vars) {
    if (! auth('web')->check() || ! Schema::hasTable('support_pins')) {
        return null;
    }

    try {
        $user = auth('web')->user();
        $clientId = session('active_client_id') ?: $user?->clients()->value('clients.id');
        if (! $clientId) {
            return null;
        }

        $record = app(SupportPinService::class)->getOrGenerate((int) $clientId);

        return View::make('supportpin::client.card', [
            'pin' => (string) $record->pin_encrypted,
            'expiresAt' => $record->expires_at,
        ])->render();
    } catch (Throwable $e) {
        report($e);

        return null;
    }
});

add_hook('AdminAreaClientSummaryOutput', 15, function (array $vars) {
    $client = $vars['client'] ?? null;
    if (! $client instanceof Client || ! auth('admin')->check() || ! Schema::hasTable('support_pins')) {
        return null;
    }

    try {
        $record = app(SupportPinService::class)->getOrGenerate((int) $client->id);
        $pin = e((string) $record->pin_encrypted);
        $url = e(url('/admin/extensions/support-pin'));

        return '<span style="color:#ccc;">|</span><span>Support PIN: <a href="'.$url.'" style="text-decoration:none;"><code style="font-weight:700;">'.$pin.'</code></a></span>';
    } catch (Throwable $e) {
        report($e);

        return null;
    }
});
