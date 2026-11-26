<?php

namespace Modules\Addons\ItalkBalance\Services;

use App\Models\AddonSetting;
use App\Models\Service;
use Illuminate\Support\Facades\View;
use Modules\Addons\ItalkBalance\Models\ItalkBalanceEndpoint;
use Modules\Addons\ItalkBalance\Models\ItalkBalanceService;

/**
 * The one place that answers "does this service show a balance, and what does
 * the customer see" and "what happens when they press refresh".
 *
 * Both the service-page card (a hook) and the two controllers ask this class,
 * so the ownership rules, the product selection and the cooldown are written
 * once and behave the same wherever the balance is shown.
 */
class BalanceRegistry
{
    public const ADDON = 'italk_balance';

    public const DEFAULT_COOLDOWN = 30;

    public function __construct(private readonly BalanceFetcher $fetcher = new BalanceFetcher) {}

    /** @return array<string, mixed> */
    public function settings(): array
    {
        return AddonSetting::getForAddon(self::ADDON);
    }

    /**
     * Product ids whose services may show a balance, for one program.
     *
     * @return array<int, int>
     */
    public function selectedProducts(string $program): array
    {
        $raw = (string) (AddonSetting::getSetting(self::ADDON, "products_{$program}", '') ?? '');

        return array_values(array_unique(array_filter(array_map('intval', explode(',', $raw)))));
    }

    /** @param array<int, int|string> $productIds */
    public function storeSelectedProducts(string $program, array $productIds): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        AddonSetting::setSetting(self::ADDON, "products_{$program}", implode(',', $ids));
    }

    /** Seconds a customer must wait between balance refreshes of the same service. */
    public function cooldown(): int
    {
        $raw = (int) (AddonSetting::getSetting(self::ADDON, 'refresh_cooldown', (string) self::DEFAULT_COOLDOWN) ?? self::DEFAULT_COOLDOWN);

        return max(0, min(3600, $raw));
    }

    public function mappingFor(int $serviceId): ?ItalkBalanceService
    {
        return ItalkBalanceService::with('endpoint')->where('service_id', $serviceId)->first();
    }

    /**
     * The HTML the service page shows, or null when this service has no
     * balance to show. Null keeps the hook silent: no empty panel is added to
     * every unrelated service.
     */
    public function card(Service $service): ?string
    {
        $mapping = $this->revealMapping($service);

        if ($mapping === null) {
            return null;
        }

        return View::make('italkbalance::card', [
            'service' => $service,
            'mapping' => $mapping,
            'endpoint' => $mapping->endpoint,
            'balance' => $mapping->balanceAmount(),
            'updatedAt' => $mapping->last_checked_at,
            'cooldown' => $this->cooldown(),
            'refreshUrl' => route('client.italk-balance.refresh', $service),
            'widgetId' => 'italk-balance-'.$mapping->id,
        ])->render();
    }

    /**
     * A mapping the customer is allowed to see and refresh: it exists, has an
     * IP number, its endpoint is active and its product is one the operator
     * selected. Anything else means the feature is not offered for this
     * service and the page must not pretend otherwise.
     */
    public function revealMapping(Service $service): ?ItalkBalanceService
    {
        $mapping = $this->mappingFor((int) $service->id);

        if ($mapping === null || trim((string) $mapping->ip_number) === '') {
            return null;
        }

        if (! $mapping->endpoint || ! $mapping->endpoint->is_active) {
            return null;
        }

        if (! in_array((int) $service->product_id, $this->selectedProducts((string) $mapping->endpoint->program), true)) {
            return null;
        }

        return $mapping;
    }

    /**
     * Ask the provider for a fresh balance and store what came back.
     *
     * The cooldown is checked against last_checked_at rather than a session
     * counter, so it holds across tabs and devices. $force is for the admin
     * screen, which is not rate limited by the customer's cooldown.
     *
     * @return array{ok: bool, balance: ?float, raw: ?string, message: string, retry_after: int}
     */
    public function refresh(ItalkBalanceService $mapping, bool $force = false): array
    {
        $waited = $mapping->last_checked_at ? (int) $mapping->last_checked_at->diffInSeconds(now()) : null;
        $cooldown = $this->cooldown();

        if (! $force && $waited !== null && $waited < $cooldown) {
            return [
                'ok' => false,
                'balance' => $mapping->balanceAmount(),
                'raw' => $mapping->balance,
                'message' => 'Balance was checked a moment ago. Try again in '.($cooldown - $waited).'s.',
                'retry_after' => $cooldown - $waited,
            ];
        }

        $endpoint = $mapping->endpoint;

        if (! $endpoint || ! $endpoint->is_active) {
            return [
                'ok' => false,
                'balance' => $mapping->balanceAmount(),
                'raw' => $mapping->balance,
                'message' => 'No active balance endpoint is assigned to this service.',
                'retry_after' => 0,
            ];
        }

        $result = $this->fetcher->fetch((string) $endpoint->base_url, (string) $mapping->ip_number);

        $mapping->forceFill(['last_checked_at' => now()]);

        if ($result['ok']) {
            $mapping->forceFill(['balance' => $result['balance'], 'last_error' => null])->save();
        } else {
            // The previous figure stays: a provider hiccup must not look like a
            // zero balance to the customer.
            $mapping->forceFill(['last_error' => mb_substr($result['message'], 0, 250)])->save();
        }

        return [
            'ok' => $result['ok'],
            'balance' => $mapping->balanceAmount(),
            'raw' => $mapping->balance,
            'message' => $result['message'],
            'retry_after' => $cooldown,
        ];
    }

    /** @return array<int, ItalkBalanceEndpoint> */
    public function endpoints(?string $program = null): array
    {
        return ItalkBalanceEndpoint::query()
            ->when($program, fn ($q) => $q->where('program', $program))
            ->orderBy('program')->orderBy('label')
            ->get()->all();
    }
}
