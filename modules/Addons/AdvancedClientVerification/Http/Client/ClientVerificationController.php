<?php

namespace Modules\Addons\AdvancedClientVerification\Http\Client;

use App\Http\Controllers\Concerns\ResolvesClient;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Modules\Addons\AdvancedClientVerification\Models\AcvActivityLog;
use Modules\Addons\AdvancedClientVerification\Models\AcvDocument;
use Modules\Addons\AdvancedClientVerification\Models\AcvVerification;
use Modules\Addons\AdvancedClientVerification\Services\KycService;

class ClientVerificationController extends Controller
{
    use ResolvesClient;

    public function index(KycService $kyc)
    {
        abort_unless($kyc->flagEnabled('client_access'), 404);

        $client = $this->currentClient();
        abort_unless($client !== null, 403);

        $verification = AcvVerification::with('documents')->where('client_id', $client->id)->first();

        return view('acv::client.index', [
            'client' => $client,
            'verification' => $verification,
            'allowsApi' => $kyc->allowsApi(),
            'allowsManual' => $kyc->allowsManual(),
            'settings' => $kyc->settings(),
            'businessTypes' => $kyc->businessTypesRequiringLicense(),
        ]);
    }

    public function startSession(KycService $kyc)
    {
        abort_unless($kyc->flagEnabled('client_access') && $kyc->allowsApi(), 403);

        $client = $this->currentClient();
        abort_unless($client !== null, 403);

        if ($kyc->isVerified($client->id)) {
            return redirect()->route('client.kyc.index')->with('info', 'Your account is already verified.');
        }

        $callbackUrl = route('client.kyc.index');
        $res = $kyc->diditClient()->createSession($client->id, $callbackUrl);

        if (! ($res['success'] ?? false)) {
            return back()->with('error', (string) ($res['message'] ?? 'Unable to start verification right now.'));
        }

        AcvVerification::updateOrCreate(
            ['client_id' => $client->id],
            [
                'session_id' => $res['session_id'],
                'verification_url' => $res['url'],
                'status' => 'pending',
                'verification_method' => 'api',
                'decline_reason' => null,
            ]
        );

        AcvActivityLog::record($client->id, 'session_started', 'Started automated verification session.');

        return redirect()->away($res['url']);
    }

    public function refreshSession(KycService $kyc)
    {
        $client = $this->currentClient();
        abort_unless($client !== null, 403);

        $verification = AcvVerification::where('client_id', $client->id)->first();
        if (! $verification || ! $verification->session_id) {
            return back()->with('error', 'No active verification session was found.');
        }

        $res = $kyc->diditClient()->fetchSession($verification->session_id);
        if (! ($res['success'] ?? false)) {
            return back()->with('info', (string) ($res['message'] ?? 'Verification is still being processed.'));
        }

        if ($res['status'] === 'verified' && $kyc->flagEnabled('auto_approve')) {
            $kyc->approve($verification, null, 'Automated Check', 'api_poll');

            return back()->with('success', 'Your identity verification is complete.');
        }

        if ($res['status'] === 'failed') {
            $kyc->decline($verification, 'Verification session was not approved. Please try again or submit documents manually.', null, 'Automated Check', 'api_poll');

            return back()->with('error', 'Verification could not be completed. Please review the details below.');
        }

        return back()->with('info', 'Your verification is currently under review.');
    }

    public function submitManual(Request $request, KycService $kyc)
    {
        abort_unless($kyc->flagEnabled('client_access') && $kyc->allowsManual(), 403);

        $client = $this->currentClient();
        abort_unless($client !== null, 403);

        if ($kyc->isVerified($client->id)) {
            return back()->with('info', 'Your account is already verified.');
        }

        $businessTypes = $kyc->businessTypesRequiringLicense();
        $chosenBusiness = strtolower(trim((string) $request->input('business_type', 'individual')));
        $needsTradeLicense = $kyc->flagEnabled('require_trade_license') && in_array($chosenBusiness, $businessTypes, true);
        $maxKb = max(1, min(10, (int) ($kyc->settings()['max_file_size'] ?? 5))) * 1024;

        $rules = [
            'document_type' => ['required', Rule::in(['national_id', 'passport', 'driving_license'])],
            'document_number' => 'required|string|max:100',
            'business_type' => 'nullable|string|max:64',
            'trade_license_number' => $needsTradeLicense ? 'required|string|max:120' : 'nullable|string|max:120',
            'id_front' => "required|file|max:{$maxKb}|mimes:jpg,jpeg,png,pdf",
            'id_back' => $kyc->flagEnabled('require_document_back') && $request->input('document_type') !== 'passport'
                ? "required|file|max:{$maxKb}|mimes:jpg,jpeg,png,pdf"
                : "nullable|file|max:{$maxKb}|mimes:jpg,jpeg,png,pdf",
            'selfie' => $kyc->flagEnabled('require_selfie')
                ? "required|file|max:{$maxKb}|mimes:jpg,jpeg,png,pdf"
                : "nullable|file|max:{$maxKb}|mimes:jpg,jpeg,png,pdf",
            'trade_license' => $needsTradeLicense
                ? "required|file|max:{$maxKb}|mimes:jpg,jpeg,png,pdf"
                : "nullable|file|max:{$maxKb}|mimes:jpg,jpeg,png,pdf",
        ];

        $validated = $request->validate($rules);

        $files = [];
        foreach (['id_front', 'id_back', 'selfie', 'trade_license'] as $slot) {
            if ($request->hasFile($slot)) {
                $files[$slot] = $request->file($slot);
            }
        }

        $kyc->storeManualSubmission($client, $validated, $files);

        return redirect()->route('client.kyc.index')
            ->with('success', 'Your verification documents have been submitted for review.');
    }

    public function downloadDocument(AcvDocument $document)
    {
        $clientId = $this->getClientId();
        abort_unless((int) $document->client_id === (int) $clientId, 403);

        return self::streamPrivateDocument($document);
    }

    public static function streamPrivateDocument(AcvDocument $document)
    {
        $path = (string) $document->storage_path;
        $expectedPrefix = 'kyc/'.((int) $document->client_id).'/';

        abort_if(
            $path === ''
            || str_contains($path, '..')
            || ! str_starts_with(str_replace('\\', '/', $path), $expectedPrefix)
            || ! Storage::disk('local')->exists($path),
            404
        );

        $safeName = preg_replace('/[^A-Za-z0-9._-]+/', '_', basename((string) $document->original_name)) ?: 'document';

        return Storage::disk('local')->download($path, $safeName, [
            'Content-Type' => $document->mime_type ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
