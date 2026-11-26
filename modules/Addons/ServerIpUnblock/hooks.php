<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Modules\Addons\ServerIpUnblock\Services\IpUnblockService;

add_hook('ClientAreaHomePagePanels', 18, function (array $vars) {
    if (! auth('web')->check() || ! Schema::hasTable('ip_unblock_logs')) {
        return null;
    }

    try {
        $user = auth('web')->user();
        $clientId = session('active_client_id') ?: $user?->clients()->value('clients.id');
        if (! $clientId) {
            return null;
        }

        $services = app(IpUnblockService::class)->capableServicesForClient((int) $clientId);
        if ($services->isEmpty()) {
            return null;
        }

        return View::make('unblockip::client.shortcut', [
            'count' => $services->count(),
            'visitorIp' => (string) request()->ip(),
        ])->render();
    } catch (Throwable $e) {
        report($e);

        return null;
    }
});
