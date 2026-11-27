<div class="card mt-3">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-sliders-h fa-fw"></i>&nbsp;{{ 'Self-Service Lifecycle & Ownership' }}</h5>
    </div>
    <div class="card-body">
        @if($status === 'active')
            @if($allowSuspend)
            <div class="mb-3 pb-3 border-bottom">
                <h6 class="font-weight-bold mb-2">{{ 'Pause / Suspend Service Immediately' }}</h6>
                <form method="POST" action="{{ route('client.services.self-suspend', $service) }}">
                    @csrf
                    <p class="text-muted small mb-2">
                        {{ 'Suspending pauses active service access immediately. Billing schedules remain unchanged unless you submit a cancellation request.' }}
                    </p>
                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" id="confirmSuspendChk" name="confirm_suspend" value="1" required>
                        <label class="form-check-label small" for="confirmSuspendChk">{{ 'I confirm that I want to suspend this service right now.' }}</label>
                    </div>
                    <button type="submit" class="btn btn-danger btn-sm">{{ 'Suspend Service' }}</button>
                </form>
            </div>
            @endif

            @if($allowTransfer)
                @if($pendingTransfer)
                    <div class="alert alert-warning mb-0">
                        {{ 'Ownership transfer to ' }}<strong>{{ $pendingTransfer->to_email }}</strong>{{ ' is currently awaiting review.' }}
                    </div>
                @else
                    <div>
                        <h6 class="font-weight-bold mb-2">{{ 'Transfer Service Ownership' }}</h6>
                        <form method="POST" action="{{ route('client.services.transfer-request', $service) }}">
                            @csrf
                            <div class="row">
                                <div class="col-md-4 form-group mb-2">
                                    <label class="small">{{ 'Recipient Full Name *' }}</label>
                                    <input type="text" name="to_name" required class="form-control form-control-sm">
                                </div>
                                <div class="col-md-4 form-group mb-2">
                                    <label class="small">{{ 'Recipient Account Email *' }}</label>
                                    <input type="email" name="to_email" required class="form-control form-control-sm">
                                </div>
                                <div class="col-md-4 form-group mb-2">
                                    <label class="small">{{ 'Recipient Phone' }}</label>
                                    <input type="text" name="to_phone" class="form-control form-control-sm">
                                </div>
                            </div>
                            <div class="form-group mb-2">
                                <label class="small">{{ 'Reason for Transfer *' }}</label>
                                <input type="text" name="reason" required class="form-control form-control-sm" placeholder="{{ 'Briefly describe the transfer request' }}">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">{{ 'Submit Transfer Request' }}</button>
                        </form>
                    </div>
                @endif
            @endif
        @elseif($status === 'suspended' && $allowSuspend)
            <div class="d-flex align-items-center justify-content-between flex-wrap mb-3 pb-3 border-bottom">
                <span class="text-muted small">{{ 'Need this suspended service reactivated? Reach out to our team via ticket or WhatsApp.' }}</span>
                <div class="mt-2 mt-sm-0">
                    <a href="{{ route('client.tickets.create') }}" class="btn btn-default btn-sm">{{ 'Request Reactivation' }}</a>
                    @if(! empty($whatsappUrl))
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-success btn-sm">{{ 'WhatsApp Support' }}</a>
                    @endif
                </div>
            </div>
            <div>
                <h6 class="font-weight-bold text-danger mb-2">{{ 'Terminate Suspended Service Permanently' }}</h6>
                <form method="POST" action="{{ route('client.services.self-terminate', $service) }}">
                    @csrf
                    <p class="text-muted small mb-2">{{ 'Termination permanently closes this service and cannot be undone.' }}</p>
                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" id="confirmTermChk" name="confirm_terminate" value="1" required>
                        <label class="form-check-label small" for="confirmTermChk">{{ 'I understand this permanently terminates my service.' }}</label>
                    </div>
                    <button type="submit" class="btn btn-danger btn-sm">{{ 'Terminate Permanently' }}</button>
                </form>
            </div>
        @endif
    </div>
</div>
