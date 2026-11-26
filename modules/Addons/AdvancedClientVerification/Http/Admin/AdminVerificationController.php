<?php

namespace Modules\Addons\AdvancedClientVerification\Http\Admin;

use App\Http\Controllers\Controller;
use App\Models\AddonSetting;
use App\Models\ProductGroup;
use Illuminate\Http\Request;
use Modules\Addons\AdvancedClientVerification\AdvancedClientVerificationModule;
use Modules\Addons\AdvancedClientVerification\Http\Client\ClientVerificationController;
use Modules\Addons\AdvancedClientVerification\Models\AcvActivityLog;
use Modules\Addons\AdvancedClientVerification\Models\AcvAdminNote;
use Modules\Addons\AdvancedClientVerification\Models\AcvDocument;
use Modules\Addons\AdvancedClientVerification\Models\AcvVerification;
use Modules\Addons\AdvancedClientVerification\Services\KycService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminVerificationController extends Controller
{
    public function index(Request $request, KycService $kyc)
    {
        $status = (string) $request->query('status', '');
        $search = trim((string) $request->query('q', ''));

        $counts = [
            'all' => AcvVerification::count(),
            'verified' => AcvVerification::where('status', 'verified')->count(),
            'pending' => AcvVerification::where('status', 'pending')->count(),
            'failed' => AcvVerification::where('status', 'failed')->count(),
            'not_started' => AcvVerification::where('status', 'not_started')->count(),
        ];

        $verifications = AcvVerification::with('client')
            ->withCount('documents')
            ->when(in_array($status, ['verified', 'pending', 'failed', 'not_started'], true), fn ($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $like = '%'.addcslashes($search, '%_').'%';
                $q->where(function ($sub) use ($like) {
                    $sub->where('document_number', 'like', $like)
                        ->orWhere('session_id', 'like', $like)
                        ->orWhereHas('client', fn ($cq) => $cq
                            ->where('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like)
                            ->orWhere('email', 'like', $like)
                            ->orWhere('company_name', 'like', $like));
                });
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $productGroups = ProductGroup::orderBy('name')->get(['id', 'name']);

        return view('acv::admin.index', [
            'verifications' => $verifications,
            'counts' => $counts,
            'status' => $status,
            'search' => $search,
            'settings' => $kyc->settings(),
            'productGroups' => $productGroups,
        ]);
    }

    public function show(AcvVerification $verification)
    {
        $verification->load(['client', 'documents', 'notes']);
        $logs = AcvActivityLog::where('client_id', $verification->client_id)->latest('id')->limit(50)->get();

        return view('acv::admin.show', compact('verification', 'logs'));
    }

    public function approve(AcvVerification $verification, KycService $kyc)
    {
        $admin = auth('admin')->user();
        $kyc->approve($verification, $admin?->id, $admin?->username ?: 'admin', 'admin');

        return back()->with('success', 'Client verification marked as verified.');
    }

    public function decline(Request $request, AcvVerification $verification, KycService $kyc)
    {
        $validated = $request->validate([
            'decline_reason' => 'required|string|max:1000',
        ]);

        $admin = auth('admin')->user();
        $kyc->decline($verification, $validated['decline_reason'], $admin?->id, $admin?->username ?: 'admin', 'admin');

        return back()->with('success', 'Client verification declined.');
    }

    public function addNote(Request $request, AcvVerification $verification)
    {
        $validated = $request->validate([
            'note' => 'required|string|max:2000',
        ]);

        $admin = auth('admin')->user();
        AcvAdminNote::create([
            'verification_id' => $verification->id,
            'admin_id' => $admin?->id,
            'admin_name' => $admin?->username ?: 'admin',
            'note' => trim($validated['note']),
        ]);

        AcvActivityLog::record(
            $verification->client_id,
            'admin_note_added',
            'Staff note added to verification record.',
            $admin?->id,
            $admin?->username ?: 'admin'
        );

        return back()->with('success', 'Note saved.');
    }

    public function updateSettings(Request $request)
    {
        $v = $request->validate([
            'verification_mode' => 'required|in:api_only,manual_only,hybrid',
            'api_key' => 'nullable|string|max:512',
            'workflow_id' => 'nullable|string|max:128',
            'webhook_secret' => 'nullable|string|max:512',
            'verification_requirement' => 'required|in:disabled,all_clients,restricted_groups',
            'restricted_groups' => 'nullable|string|max:500',
            'max_file_size' => 'required|integer|min:1|max:10',
            'verification_deadline_days' => 'required|integer|min:1|max:90',
            'business_types' => 'nullable|string|max:500',
        ]);

        $addon = AdvancedClientVerificationModule::NAME;
        foreach (['verification_mode', 'workflow_id', 'verification_requirement', 'restricted_groups', 'max_file_size', 'verification_deadline_days', 'business_types'] as $key) {
            AddonSetting::setSetting($addon, $key, (string) ($v[$key] ?? ''));
        }

        foreach (['api_key', 'webhook_secret'] as $secretKey) {
            if ($request->filled($secretKey)) {
                AddonSetting::setSetting($addon, $secretKey, trim((string) $request->input($secretKey)));
            }
        }

        foreach ([
            'require_selfie',
            'require_document_back',
            'require_trade_license',
            'client_access',
            'auto_approve',
            'enable_service_suspension',
            'enable_daily_reminders',
        ] as $boolKey) {
            AddonSetting::setSetting($addon, $boolKey, $request->boolean($boolKey) ? '1' : '0');
        }

        return back()->with('success', 'Verification settings updated.');
    }

    public function downloadDocument(AcvDocument $document)
    {
        return ClientVerificationController::streamPrivateDocument($document);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $status = (string) $request->query('status', '');

        $rows = AcvVerification::with('client')
            ->when(in_array($status, ['verified', 'pending', 'failed', 'not_started'], true), fn ($q) => $q->where('status', $status))
            ->orderBy('id')
            ->get();

        $filename = 'kyc-verifications-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'ID', 'Client ID', 'Client Name', 'Email', 'Method', 'Status',
                'Document Type', 'Document Number', 'Business Type', 'Trade License',
                'Decline Reason', 'Verified At', 'Updated At',
            ]);

            foreach ($rows as $r) {
                $name = $r->client ? trim($r->client->first_name.' '.$r->client->last_name) : '';
                fputcsv($out, [
                    $r->id,
                    $r->client_id,
                    KycService::csvSafe($name),
                    KycService::csvSafe($r->client?->email ?? ''),
                    KycService::csvSafe($r->verification_method),
                    KycService::csvSafe($r->status),
                    KycService::csvSafe($r->document_type),
                    KycService::csvSafe($r->document_number),
                    KycService::csvSafe($r->business_type),
                    KycService::csvSafe($r->trade_license_number),
                    KycService::csvSafe($r->decline_reason),
                    $r->verified_at?->toDateTimeString() ?? '',
                    $r->updated_at?->toDateTimeString() ?? '',
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
