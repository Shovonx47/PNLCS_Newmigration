<?php

use App\Models\AddonSetting;
use App\Models\Client;
use App\Models\Service;
use Illuminate\Support\Facades\View;
use Modules\Addons\PortalExtras\Models\ServiceTransferRequest;
use Modules\Addons\PortalExtras\PortalExtrasModule;
use Modules\Addons\PortalExtras\Services\LiveStatsBuilder;

add_hook('AdminAreaTopOutput', 5, function (array $vars) {
    if (! auth('admin')->check()) {
        return null;
    }

    try {
        $enabled = AddonSetting::getSetting(PortalExtrasModule::NAME, 'enable_admin_top_stats', '1');
        if (in_array(strtolower((string) $enabled), ['0', 'off', 'false'], true)) {
            return null;
        }

        $stats = app(LiveStatsBuilder::class)->adminStats();

        return View::make('portalextras::admin.top-stats', ['stats' => $stats])->render();
    } catch (Throwable $e) {
        report($e);

        return null;
    }
});

add_hook('ClientAreaTopOutput', 10, function (array $vars) {
    if (! auth('web')->check() || ! request()->is('client*') || request()->is('client/login', 'client/register')) {
        return null;
    }

    try {
        $enabled = AddonSetting::getSetting(PortalExtrasModule::NAME, 'enable_client_top_stats', '1');
        if (in_array(strtolower((string) $enabled), ['0', 'off', 'false'], true)) {
            return null;
        }

        $user = auth('web')->user();
        $clientId = session('active_client_id') ?: $user?->clients()->value('clients.id');
        $client = $clientId ? Client::find($clientId) : null;
        if (! $client) {
            return null;
        }

        $stats = app(LiveStatsBuilder::class)->clientStats($client);

        return View::make('portalextras::client.top-stats', [
            'client' => $client,
            'stats' => $stats,
        ])->render();
    } catch (Throwable $e) {
        report($e);

        return null;
    }
});

add_hook('ClientAreaHomePagePanels', 15, function (array $vars) {
    if (! auth('web')->check()) {
        return null;
    }

    try {
        $user = auth('web')->user();
        $clientId = session('active_client_id') ?: $user?->clients()->value('clients.id');
        $client = $clientId ? Client::find($clientId) : null;
        if (! $client) {
            return null;
        }

        $stats = app(LiveStatsBuilder::class)->clientStats($client);

        return View::make('portalextras::client.dashboard-panels', [
            'client' => $client,
            'stats' => $stats,
        ])->render();
    } catch (Throwable $e) {
        report($e);

        return null;
    }
});

add_hook('ClientAreaProductDetailsOutput', 15, function (array $vars) {
    $service = $vars['service'] ?? null;
    if (! $service instanceof Service || ! auth('web')->check()) {
        return null;
    }

    $status = strtolower((string) $service->status);
    if (! in_array($status, ['active', 'suspended'], true)) {
        return null;
    }

    try {
        $allowSuspend = ! in_array(strtolower((string) AddonSetting::getSetting(PortalExtrasModule::NAME, 'enable_self_suspend', '1')), ['0', 'off', 'false'], true);
        $allowTransfer = ! in_array(strtolower((string) AddonSetting::getSetting(PortalExtrasModule::NAME, 'enable_service_transfer', '1')), ['0', 'off', 'false'], true);

        if (! $allowSuspend && ! $allowTransfer) {
            return null;
        }

        $pendingTransfer = ServiceTransferRequest::where('service_id', $service->id)
            ->where('status', 'pending')
            ->first();

        $waDigits = preg_replace('/\D+/', '', (string) AddonSetting::getSetting(PortalExtrasModule::NAME, 'whatsapp_support_number', '8801329646688'));
        $whatsappUrl = $waDigits !== ''
            ? 'https://wa.me/'.$waDigits.'?text='.rawurlencode("Hello SoftCents Support, please assist with reactivating my suspended service #{$service->id}.")
            : null;

        return View::make('portalextras::client.service-controls', [
            'service' => $service,
            'status' => $status,
            'allowSuspend' => $allowSuspend,
            'allowTransfer' => $allowTransfer,
            'pendingTransfer' => $pendingTransfer,
            'whatsappUrl' => $whatsappUrl,
        ])->render();
    } catch (Throwable $e) {
        report($e);

        return null;
    }
});
