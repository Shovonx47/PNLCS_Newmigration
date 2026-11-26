<?php

use App\Models\Client;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Modules\Addons\ResellerCenter\Models\Reseller;
use Modules\Addons\ResellerCenter\Models\ResellerClient;
use Modules\Addons\ResellerCenter\Models\ResellerPayout;

add_hook('AdminAreaClientSummaryOutput', 20, function (array $vars) {
    $client = $vars['client'] ?? null;
    if (! $client instanceof Client || ! auth('admin')->check() || ! Schema::hasTable('resellers')) {
        return null;
    }

    try {
        $reseller = Reseller::with('group')->where('client_id', $client->id)->first();
        if ($reseller) {
            $url = e(route('admin.extensions.reseller-center.resellers.show', $reseller));
            $group = e((string) ($reseller->group?->name ?: 'Standard'));
            $balance = e(money_fmt((float) $reseller->balance));
            $cls = $reseller->isActive() ? 'badge-active' : 'badge-cancelled';
            $statusLabel = e(ucfirst((string) $reseller->status));

            return '<span style="color:#ccc;">|</span><span>Reseller: '
                .'<a href="'.$url.'" style="font-weight:600;text-decoration:none;">'.$group.'</a> '
                .'(<span class="badge '.$cls.'">'.$statusLabel.'</span> · '.$balance.')</span>';
        }

        $link = ResellerClient::with('reseller.client')->where('client_id', $client->id)->first();
        if ($link && $link->reseller) {
            $url = e(route('admin.extensions.reseller-center.resellers.show', $link->reseller));
            $ownerName = e($link->reseller->company_name ?: trim(($link->reseller->client?->first_name ?? '').' '.($link->reseller->client?->last_name ?? '')));

            return '<span style="color:#ccc;">|</span><span>End Client of Reseller: '
                .'<a href="'.$url.'" style="font-weight:600;text-decoration:none;">'.$ownerName.'</a></span>';
        }
    } catch (Throwable $e) {
        report($e);
    }

    return null;
});

add_hook('ClientAreaHomePagePanels', 6, function (array $vars) {
    if (! auth('web')->check() || ! Schema::hasTable('resellers')) {
        return null;
    }

    try {
        $user = auth('web')->user();
        $clientId = session('active_client_id') ?: $user?->clients()->value('clients.id');
        if (! $clientId) {
            return null;
        }

        $reseller = Reseller::with('group')->where('client_id', $clientId)->where('status', 'active')->first();
        if (! $reseller) {
            return null;
        }

        $endClientsCount = ResellerClient::where('reseller_id', $reseller->id)->count();
        $pendingAmount = (float) ResellerPayout::where('reseller_id', $reseller->id)->where('status', 'pending')->sum('amount');

        return View::make('resellercenter::client.banner', [
            'reseller' => $reseller,
            'endClientsCount' => $endClientsCount,
            'pendingAmount' => $pendingAmount,
        ])->render();
    } catch (Throwable $e) {
        report($e);

        return null;
    }
});
