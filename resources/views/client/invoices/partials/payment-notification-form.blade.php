<div class="card border-primary mt-3">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0">{{ __('client.invoices.payment_notification_title') }}</h5>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">{{ __('client.invoices.payment_notification_intro') }}</p>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 pl-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('client.invoices.payment-notification', $invoice) }}" enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ __('client.invoices.pn_sender_name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="sender_name" class="form-control" value="{{ old('sender_name') }}" required maxlength="255">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ __('client.invoices.pn_bank_name') }}</label>
                        <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name') }}" maxlength="255">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>
                            {{ __('client.invoices.pn_amount') }}
                            @if(has_billing_conversion($invoice)) ({{ strtoupper($invoice->billing_currency) }}) @endif
                            <span class="text-danger">*</span>
                        </label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0.01"
                               value="{{ old('amount', number_format(billing_amount((float) ($balance ?? $invoice->amountDue()), $invoice), 2, '.', '')) }}" required>
                        @if(has_billing_conversion($invoice))
                            <small class="form-text text-muted">{{ __('client.invoices.pn_amount_hint', ['amount' => invoice_money_fmt($balance ?? $invoice->amountDue(), $invoice)]) }}</small>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ __('client.invoices.pn_transfer_date') }} <span class="text-danger">*</span></label>
                        <input type="date" name="transfer_date" class="form-control" value="{{ old('transfer_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ __('client.invoices.pn_reference') }}</label>
                        <input type="text" name="reference" class="form-control" value="{{ old('reference') }}" maxlength="255"
                               placeholder="{{ __('client.invoices.pn_reference_placeholder', ['num' => payment_ref($invoice)]) }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ __('client.invoices.pn_receipt') }}</label>
                        <input type="file" name="receipt" class="form-control-file form-control" accept=".jpg,.jpeg,.png,.pdf">
                        <small class="form-text text-muted">{{ __('client.invoices.pn_receipt_hint') }}</small>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>{{ __('client.invoices.pn_note') }}</label>
                <textarea name="client_note" class="form-control" rows="2" maxlength="2000">{{ old('client_note') }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">
                {{ __('client.invoices.pn_submit') }}
            </button>
        </form>
    </div>
</div>
