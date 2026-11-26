<?php

namespace Modules\Addons\PortalExtras\Http;

use App\Enums\ServiceStatus;
use App\Events\ServiceSuspended;
use App\Events\ServiceTerminated;
use App\Http\Controllers\Concerns\ResolvesClient;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\Setting;
use App\Services\ProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Modules\Addons\PortalExtras\Models\ServiceTransferRequest;

class PortalServiceActionController extends Controller
{
    use ResolvesClient;

    public function selfSuspend(Request $request, Service $service, ProvisioningService $provisioning)
    {
        $clientId = $this->getClientId();
        abort_unless((int) $service->client_id === (int) $clientId, 403);

        $request->validate(['confirm_suspend' => 'accepted']);

        if (strtolower((string) $service->status) !== ServiceStatus::Active->value) {
            return back()->with('error', 'Only an active service can be suspended.');
        }

        $reason = 'Suspended by customer from client portal';

        if ($service->server_id && $provisioning->resolveModule($service)) {
            $res = $provisioning->suspendAccount($service, $reason, queueOnFail: false);
            if (! ($res['success'] ?? false)) {
                return back()->with('error', 'Unable to pause the service right now. Please contact support.');
            }
        } else {
            $service->update([
                'status' => ServiceStatus::Suspended->value,
                'suspension_date' => now(),
                'suspension_reason' => $reason,
            ]);
            event(new ServiceSuspended($service, $reason));
        }

        ActivityLog::log("Client #{$clientId} self-suspended service #{$service->id}", 'client');

        return redirect()->route('client.services.show', $service)
            ->with('success', 'Your service has been suspended.');
    }

    public function selfTerminate(Request $request, Service $service, ProvisioningService $provisioning)
    {
        $clientId = $this->getClientId();
        abort_unless((int) $service->client_id === (int) $clientId, 403);

        $request->validate(['confirm_terminate' => 'accepted']);

        if (strtolower((string) $service->status) !== ServiceStatus::Suspended->value) {
            return back()->with('error', 'A service must be suspended first before permanent termination.');
        }

        // Prevent terminating a service that carries an unpaid invoice to evade billing audit.
        $hasUnpaidDebt = Invoice::where('client_id', $clientId)
            ->whereIn('status', ['unpaid', 'overdue'])
            ->whereHas('items', fn ($q) => $q->where('type', 'Hosting')->where('rel_id', $service->id))
            ->exists();

        if ($hasUnpaidDebt) {
            return back()->with('error', 'Please settle or contact billing regarding the open invoice on this service before terminating.');
        }

        if ($service->server_id && $provisioning->resolveModule($service)) {
            $res = $provisioning->terminateAccount($service, queueOnFail: false);
            if (! ($res['success'] ?? false)) {
                return back()->with('error', 'Unable to complete termination right now. Please open a support ticket.');
            }
        } else {
            $service->update([
                'status' => ServiceStatus::Terminated->value,
                'termination_date' => now(),
            ]);
            event(new ServiceTerminated($service));
        }

        ActivityLog::log("Client #{$clientId} self-terminated suspended service #{$service->id}", 'client');

        return redirect()->route('client.services.show', $service)
            ->with('success', 'Your service has been permanently terminated.');
    }

    public function requestTransfer(Request $request, Service $service)
    {
        $clientId = $this->getClientId();
        abort_unless((int) $service->client_id === (int) $clientId, 403);

        if (strtolower((string) $service->status) !== ServiceStatus::Active->value) {
            return back()->with('error', 'Only active services may be transferred to another account.');
        }

        $validated = $request->validate([
            'to_name' => 'required|string|max:255',
            'to_email' => 'required|email|max:255',
            'to_phone' => 'nullable|string|max:64',
            'reason' => 'required|string|max:1000',
        ]);

        if (ServiceTransferRequest::where('service_id', $service->id)->where('status', 'pending')->exists()) {
            return back()->with('error', 'A transfer request for this service is already pending review.');
        }

        $recipient = Client::whereRaw('LOWER(email) = ?', [strtolower(trim($validated['to_email']))])->first();
        if ($recipient && (int) $recipient->id === (int) $clientId) {
            return back()->with('error', 'You already own this service.');
        }

        ServiceTransferRequest::create([
            'service_id' => $service->id,
            'from_client_id' => $clientId,
            'to_client_id' => $recipient?->id,
            'to_name' => trim($validated['to_name']),
            'to_email' => strtolower(trim($validated['to_email'])),
            'to_phone' => trim((string) ($validated['to_phone'] ?? '')) ?: null,
            'reason' => trim($validated['reason']),
            'status' => 'pending',
        ]);

        ActivityLog::log("Client #{$clientId} requested ownership transfer for service #{$service->id} to {$validated['to_email']}", 'client');

        try {
            $service->loadMissing(['client', 'product']);
            $productLabel = $service->product?->name ?: "Service #{$service->id}";
            if ($service->client && filter_var($service->client->email, FILTER_VALIDATE_EMAIL)) {
                Mail::raw(
                    "Hello {$service->client->first_name},\n\nWe have received your ownership transfer request for {$productLabel} to {$validated['to_email']}.\nOur team will review and confirm the transfer shortly.\n\nThank you,\nSoftCents Support",
                    fn ($msg) => $msg->to($service->client->email)->subject("Service Ownership Transfer Request Received (#{$service->id})")
                );
            }
            $supportEmail = trim((string) Setting::get('CompanyEmail', ''));
            if (filter_var($supportEmail, FILTER_VALIDATE_EMAIL)) {
                Mail::raw(
                    "New service ownership transfer request submitted.\nService: #{$service->id} ({$productLabel})\nFrom Client: #{$clientId}\nRecipient: {$validated['to_name']} <{$validated['to_email']}>\nReason: {$validated['reason']}",
                    fn ($msg) => $msg->to($supportEmail)->subject("[Admin] Service Transfer Request #{$service->id}")
                );
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('client.services.show', $service)
            ->with('success', 'Ownership transfer request submitted. Our team will review and confirm it shortly.');
    }
}
