<div class="sv-ph">Self-Service Lifecycle &amp; Ownership</div>
<div style="padding:16px 20px;display:flex;flex-direction:column;gap:14px;">
    @if($status === 'active')
        @if($allowSuspend)
        <details style="border:1px solid var(--border);border-radius:10px;padding:12px 14px;">
            <summary style="cursor:pointer;font-weight:700;font-size:13.5px;">Pause / Suspend Service Immediately</summary>
            <form method="POST" action="{{ route('client.services.self-suspend', $service) }}" style="margin-top:10px;">
                @csrf
                <p style="font-size:12.5px;color:var(--muted);margin:0 0 10px;">
                    Suspending pauses active service access immediately. Billing schedules remain unchanged unless you submit a cancellation request.
                </p>
                <label style="display:flex;align-items:center;gap:8px;font-size:12.5px;margin-bottom:10px;cursor:pointer;">
                    <input type="checkbox" name="confirm_suspend" value="1" required>
                    I confirm that I want to suspend this service right now.
                </label>
                <button type="submit" class="pn-btn pn-btn-sm pn-btn-danger">Suspend Service</button>
            </form>
        </details>
        @endif

        @if($allowTransfer)
            @if($pendingTransfer)
                <div class="pn-alert pn-alert-warning" style="margin:0;">
                    Ownership transfer to <strong>{{ $pendingTransfer->to_email }}</strong> is currently awaiting review.
                </div>
            @else
                <details style="border:1px solid var(--border);border-radius:10px;padding:12px 14px;">
                    <summary style="cursor:pointer;font-weight:700;font-size:13.5px;">Transfer Service Ownership</summary>
                    <form method="POST" action="{{ route('client.services.transfer-request', $service) }}" style="margin-top:12px;">
                        @csrf
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:10px;">
                            <div class="form-group" style="margin:0;">
                                <label class="form-label" style="font-size:12px;">Recipient Full Name *</label>
                                <input type="text" name="to_name" required class="form-control">
                            </div>
                            <div class="form-group" style="margin:0;">
                                <label class="form-label" style="font-size:12px;">Recipient Account Email *</label>
                                <input type="email" name="to_email" required class="form-control">
                            </div>
                            <div class="form-group" style="margin:0;">
                                <label class="form-label" style="font-size:12px;">Recipient Phone</label>
                                <input type="text" name="to_phone" class="form-control">
                            </div>
                        </div>
                        <div class="form-group" style="margin:10px 0;">
                            <label class="form-label" style="font-size:12px;">Reason for Transfer *</label>
                            <input type="text" name="reason" required class="form-control" placeholder="Briefly describe the transfer request">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Submit Transfer Request</button>
                    </form>
                </details>
            @endif
        @endif
    @elseif($status === 'suspended' && $allowSuspend)
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <span style="font-size:13px;color:var(--muted);">Need this suspended service reactivated? Reach out to our team via ticket or WhatsApp.</span>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <a href="{{ route('client.tickets.create') }}" class="btn btn-outline btn-sm">Request Reactivation</a>
                @if(! empty($whatsappUrl))
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm">WhatsApp Support</a>
                @endif
            </div>
        </div>
        <details style="border:1px solid var(--border);border-radius:10px;padding:12px 14px;">
            <summary style="cursor:pointer;font-weight:700;font-size:13.5px;color:#b91c1c;">Terminate Suspended Service Permanently</summary>
            <form method="POST" action="{{ route('client.services.self-terminate', $service) }}" style="margin-top:10px;">
                @csrf
                <p style="font-size:12.5px;color:var(--muted);margin:0 0 10px;">
                    Termination permanently closes this service and cannot be undone.
                </p>
                <label style="display:flex;align-items:center;gap:8px;font-size:12.5px;margin-bottom:10px;cursor:pointer;">
                    <input type="checkbox" name="confirm_terminate" value="1" required>
                    I understand this permanently terminates my service.
                </label>
                <button type="submit" class="pn-btn pn-btn-sm pn-btn-danger">Terminate Permanently</button>
            </form>
        </details>
    @endif
</div>
