<?php

namespace Modules\Addons\ItalkBalance\Http;

use App\Http\Controllers\Concerns\ResolvesClient;
use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Modules\Addons\ItalkBalance\Services\BalanceRegistry;

/**
 * The customer's "Refresh balance" button.
 *
 * POST (not the legacy GET), CSRF-protected, ownership checked on the service
 * itself, and rate limited twice: a per-minute throttle on the route and the
 * addon's own cooldown per service.
 */
class BalanceRefreshController extends Controller
{
    use ResolvesClient;

    public function __construct(private readonly BalanceRegistry $registry) {}

    public function refresh(Service $service): JsonResponse
    {
        abort_if($service->client_id !== $this->getClientId(), 403);

        $mapping = $this->registry->revealMapping($service);

        if ($mapping === null) {
            return response()->json([
                'ok' => false,
                'message' => 'Live balance is not available for this service.',
            ], 404);
        }

        $result = $this->registry->refresh($mapping);

        return response()->json([
            'ok' => $result['ok'],
            'balance' => $result['balance'] !== null ? number_format($result['balance'], 2) : null,
            'updated_at' => $mapping->last_checked_at?->format('d M Y, H:i'),
            'message' => $result['message'],
            'retry_after' => $result['retry_after'],
        ], $result['ok'] ? 200 : 429);
    }
}
