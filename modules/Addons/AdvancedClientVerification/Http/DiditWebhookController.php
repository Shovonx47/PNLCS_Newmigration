<?php

namespace Modules\Addons\AdvancedClientVerification\Http;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Addons\AdvancedClientVerification\Models\AcvActivityLog;
use Modules\Addons\AdvancedClientVerification\Models\AcvVerification;
use Modules\Addons\AdvancedClientVerification\Services\DiditClient;
use Modules\Addons\AdvancedClientVerification\Services\KycService;

class DiditWebhookController extends Controller
{
    public function __invoke(Request $request, KycService $kyc): JsonResponse
    {
        $rawBody = (string) $request->getContent();
        $signature = $request->header('X-Signature')
            ?? $request->header('X-Didit-Signature')
            ?? $request->header('X-ACV-Signature');

        if (! $kyc->diditClient()->verifyWebhookSignature($rawBody, $signature)) {
            return response()->json(['ok' => false, 'error' => 'Invalid signature.'], 401);
        }

        $payload = json_decode($rawBody, true);
        if (! is_array($payload)) {
            return response()->json(['ok' => false, 'error' => 'Invalid JSON payload.'], 400);
        }

        $sessionId = trim((string) ($payload['session_id'] ?? $payload['id'] ?? ''));
        $vendorClientId = (int) ($payload['vendor_data'] ?? $payload['client_id'] ?? 0);
        $rawStatus = (string) ($payload['status'] ?? $payload['decision']['status'] ?? 'pending');
        $normalized = DiditClient::normalizeProviderStatus($rawStatus);

        $verification = null;
        if ($sessionId !== '') {
            $verification = AcvVerification::where('session_id', $sessionId)->first();
        }
        if (! $verification && $vendorClientId > 0 && Client::whereKey($vendorClientId)->exists()) {
            $verification = AcvVerification::firstOrCreate(
                ['client_id' => $vendorClientId],
                [
                    'session_id' => $sessionId ?: null,
                    'status' => 'pending',
                    'verification_method' => 'api',
                ]
            );
        }

        if (! $verification) {
            return response()->json(['ok' => true, 'ignored' => true]);
        }

        if ($sessionId !== '' && ! $verification->session_id) {
            $verification->session_id = $sessionId;
        }
        $verification->webhook_payload = $rawBody;
        $verification->save();

        if ($normalized === 'verified') {
            if ($kyc->flagEnabled('auto_approve')) {
                $kyc->approve($verification, null, 'Didit Webhook', 'didit_webhook');
            } else {
                if ($verification->status !== 'verified') {
                    $verification->update(['status' => 'pending']);
                }
                AcvActivityLog::record($verification->client_id, 'webhook_pending_review', 'Automated verification passed; awaiting staff review.');
            }
        } elseif ($normalized === 'failed') {
            $reason = trim((string) ($payload['reason'] ?? $payload['decision']['decline_reason'] ?? 'Automated identity check was not approved.'));
            if ($verification->status !== 'verified') {
                $kyc->decline($verification, $reason, null, 'Didit Webhook', 'didit_webhook');
            }
        } else {
            if ($verification->status === 'not_started') {
                $verification->update(['status' => 'pending']);
            }
            AcvActivityLog::record($verification->client_id, 'webhook_status', "Webhook status update: {$rawStatus}");
        }

        return response()->json(['ok' => true, 'status' => $verification->fresh()?->status]);
    }
}
