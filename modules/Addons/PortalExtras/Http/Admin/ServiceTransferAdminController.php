<?php

namespace Modules\Addons\PortalExtras\Http\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Addons\PortalExtras\Models\ServiceTransferRequest;

class ServiceTransferAdminController extends Controller
{
    public function index(Request $request)
    {
        $status = (string) $request->query('status', 'pending');
        $requests = ServiceTransferRequest::with(['service.product', 'fromClient', 'toClient'])
            ->when(in_array($status, ['pending', 'approved', 'rejected'], true), fn ($q) => $q->where('status', $status))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('portalextras::admin.transfers', compact('requests', 'status'));
    }

    public function approve(Request $request, ServiceTransferRequest $transfer)
    {
        if ($transfer->status !== 'pending') {
            return back()->with('error', 'This transfer request has already been processed.');
        }

        $targetClient = $transfer->to_client_id
            ? Client::find($transfer->to_client_id)
            : Client::whereRaw('LOWER(email) = ?', [strtolower($transfer->to_email)])->first();

        if (! $targetClient) {
            return back()->with('error', "No client account exists yet with email {$transfer->to_email}. Ask the recipient to register first.");
        }

        $service = $transfer->service;
        if (! $service) {
            return back()->with('error', 'The associated service no longer exists.');
        }

        $admin = auth('admin')->user();

        DB::transaction(function () use ($transfer, $targetClient, $service, $admin, $request) {
            $service->update(['client_id' => $targetClient->id]);

            $transfer->update([
                'to_client_id' => $targetClient->id,
                'status' => 'approved',
                'processed_by_admin_id' => $admin?->id,
                'processed_at' => now(),
                'admin_note' => trim((string) $request->input('admin_note', '')) ?: null,
            ]);

            ActivityLog::log(
                "Service #{$service->id} transferred from Client #{$transfer->from_client_id} to Client #{$targetClient->id}",
                $admin?->username ?: 'admin'
            );
        });

        return back()->with('success', "Service #{$service->id} transferred to {$targetClient->email}.");
    }

    public function reject(Request $request, ServiceTransferRequest $transfer)
    {
        if ($transfer->status !== 'pending') {
            return back()->with('error', 'This transfer request has already been processed.');
        }

        $admin = auth('admin')->user();
        $transfer->update([
            'status' => 'rejected',
            'processed_by_admin_id' => $admin?->id,
            'processed_at' => now(),
            'admin_note' => trim((string) $request->input('admin_note', '')) ?: 'Declined by staff',
        ]);

        return back()->with('success', 'Transfer request rejected.');
    }
}
