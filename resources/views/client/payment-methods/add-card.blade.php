@extends('client.layouts.twentyone')
@section('title', __('client.payment_methods.add_card_title'))
@section('content')

{{-- The card never touches this server. The gateway's own script holds the
     number, the browser sends it straight to them, and what comes back here is
     an id. --}}

@if($publishableKey && $clientSecret && $gateway === 'stripe')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                <h3 class="card-title mb-0">{{ __('client.payment_methods.add_card_title') }}</h3>
                <a href="{{ route('client.payment-methods.index') }}" class="btn btn-default btn-sm">{{ __('client.actions.back') }}</a>
            </div>
            <p class="text-muted mb-4">{{ __('client.payment_methods.add_card_subtitle') }}</p>

            <div class="form-group row">
                <label class="col-md-3 col-form-label">{{ __('client.payment_methods.card_details') }}</label>
                <div class="col-md-9">
                    <div id="card-element" class="form-control h-auto py-3"></div>
                    <div id="card-error" class="text-danger small mt-2"></div>
                </div>
            </div>

            <div class="form-group row mb-0">
                <div class="col-md-9 offset-md-3">
                    <div class="alert alert-light border mb-3">
                        <p class="text-muted small mb-2">{{ __('client.payment_methods.consent_terms') }}</p>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="card-consent">
                            <label class="form-check-label small" for="card-consent">
                                {{ __('client.payment_methods.consent_label', ['company' => company_name()]) }}
                            </label>
                        </div>
                    </div>

                    <button type="button" id="card-submit" class="btn btn-primary" disabled>
                        {{ __('client.payment_methods.store_card_button') }}
                    </button>
                    <a href="{{ route('client.payment-methods.index') }}" class="btn btn-default ml-2">
                        {{ __('client.actions.back') }}
                    </a>
                </div>
            </div>

            <form method="POST" action="{{ route('client.payment-methods.store-card') }}" id="card-form" class="d-none">
                @csrf
                <input type="hidden" name="session_id" id="card-session">
                <input type="hidden" name="gateway" value="{{ $gateway }}">
                <input type="hidden" name="consent" id="card-consent-value" value="0">
            </form>
        </div>
    </div>
@else
    <div class="alert alert-warning">{{ __('client.payment_methods.card_unavailable') }}</div>
@endif

@endsection

@section('scripts')
@if($publishableKey && $clientSecret && $gateway === 'stripe')
<script src="https://js.stripe.com/v3/"></script>
<script>
(function () {
    var stripe = Stripe(@json($publishableKey));
    var elements = stripe.elements();
    var card = elements.create("card", { hidePostalCode: false });
    card.mount("#card-element");

    var button = document.getElementById("card-submit");
    var consent = document.getElementById("card-consent");
    var errorBox = document.getElementById("card-error");
    var form = document.getElementById("card-form");
    var busy = false;

    function sync() { button.disabled = busy || !consent.checked; }
    consent.addEventListener("change", sync);
    card.addEventListener("change", function (e) {
        errorBox.textContent = e.error ? e.error.message : "";
    });

    button.addEventListener("click", function () {
        if (!consent.checked || busy) { return; }

        busy = true;
        sync();
        errorBox.textContent = "";
        button.textContent = @json(__('client.payment_methods.storing'));

        stripe.confirmCardSetup(@json($clientSecret), { payment_method: { card: card } })
            .then(function (result) {
                if (result.error) {
                    errorBox.textContent = result.error.message || @json(__('client.payment_methods.card_not_stored'));
                    busy = false;
                    button.textContent = @json(__('client.payment_methods.store_card_button'));
                    sync();
                    return;
                }

                document.getElementById("card-session").value = result.setupIntent.id;
                document.getElementById("card-consent-value").value = "1";
                form.submit();
            })
            .catch(function () {
                errorBox.textContent = @json(__('client.payment_methods.card_not_stored'));
                busy = false;
                button.textContent = @json(__('client.payment_methods.store_card_button'));
                sync();
            });
    });
})();
</script>
@endif
@endsection
