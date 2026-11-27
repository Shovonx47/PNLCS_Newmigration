@extends('client.layouts.twentyone')
@section('title', __('client.funds.title'))
@section('page_heading', __('client.nav.add_funds'))
@section('page_subheading', __('client.funds.subtitle'))

@section('content')

@if($errors->any())
    <div class="alert alert-danger text-center" role="alert">
        @foreach($errors->all() as $validationError)
            <div>{{ $validationError }}</div>
        @endforeach
    </div>
@endif

<div class="row">

    @php
        $converted = ($exchangeRate ?? null) !== null;
        $symbol = function ($c) {
            if (! $c) return '$';
            if (trim((string) $c->prefix) !== '') return $c->prefix;
            if (trim((string) $c->suffix) !== '') return trim((string) $c->suffix);
            return $c->code;
        };
        $shopSymbol = $symbol($shopCurrency);
        $sign       = $converted ? $symbol($billingCurrency) : $shopSymbol;
        $signCurrency = $converted ? $billingCurrency : $shopCurrency;
        $signIsPrefix = $signCurrency && trim((string) $signCurrency->prefix) !== '';
        $presets    = collect([10, 25, 50, 100, 250, 500])->map(fn ($v) => $converted ? funds_round_preset($v * $exchangeRate) : $v)->unique()->values();
        $minimum    = $converted ? funds_round_preset(5 * $exchangeRate) : 5;
        $maximum    = $converted ? funds_round_preset(10000 * $exchangeRate) : 10000;
    @endphp

    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-body text-center">
                <p class="mb-1 text-muted">{{ __('client.funds.current_credit') }}</p>
                <h2 class="mb-0">{{ money_fmt($accountCredit ?? 0) }}</h2>
            </div>
            <table class="table table-striped mb-0">
                <tbody>
                    <tr>
                        <td class="text-right"><strong>{{ 'Minimum Amount' }}</strong></td>
                        <td>{{ number_format($minimum, 2, ',', '.') }} {{ $sign }}</td>
                    </tr>
                    <tr>
                        <td class="text-right"><strong>{{ 'Maximum Amount' }}</strong></td>
                        <td>{{ number_format($maximum, 2, ',', '.') }} {{ $sign }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('client.funds.store') }}">
                    @csrf
                    <fieldset>

                        <div class="form-group">
                            <label class="col-form-label">{{ __('client.funds.quick_amounts') }}</label>
                            <div class="btn-group btn-group-sm d-flex" role="group">
                                @foreach($presets as $preset)
                                <button type="button" class="btn btn-default" onclick="setAmount({{ $preset }}, this)">
                                    {{ number_format($preset, 0, ',', '.') }} {{ $sign }}
                                </button>
                                @endforeach
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="amount" class="col-form-label">{{ __('client.funds.custom_amount') }}</label>
                            <div class="input-group">
                                @if($signIsPrefix)
                                <div class="input-group-prepend"><span class="input-group-text">{{ $sign }}</span></div>
                                @endif
                                <input type="number" id="amount" name="amount" value="{{ old('amount') }}"
                                       min="{{ $minimum }}" max="{{ $maximum }}" step="0.01" required
                                       class="form-control" placeholder="0.00"
                                       @if($converted) data-rate="{{ $exchangeRate }}" @endif>
                                @if(! $signIsPrefix)
                                <div class="input-group-append"><span class="input-group-text">{{ $sign }}</span></div>
                                @endif
                            </div>
                        </div>

                        @if($converted)
                        {{-- The customer pays in one currency and the services are
                             priced in another; how much balance they get and at
                             which rate has to be visible before they pay. --}}
                        <div class="form-group">
                            <div class="border rounded p-3 bg-light">
                                <div class="d-flex justify-content-between align-items-baseline gap-8">
                                    <span class="text-muted small">{{ __('client.funds.you_will_receive') }}</span>
                                    <strong id="convertedAmount">{{ $shopSymbol }}0.00</strong>
                                </div>
                                <div class="small text-muted mt-2">
                                    1 {{ $shopCurrency->code }} = {{ number_format($exchangeRate, 4, ',', '.') }} {{ $billingCurrency->code }}
                                    @if($rateSource)
                                        · {{ $rateSource }}@if($rateDate) {{ $rateDate }}@endif
                                        @if($rateBulletin) ({{ __('client.funds.bulletin') }} {{ $rateBulletin }})@endif
                                    @endif
                                </div>
                                <div class="small text-muted mt-1">{{ __('client.funds.rate_notice') }}</div>
                            </div>
                        </div>
                        @endif

                        <div class="form-group">
                            <label for="payment_method" class="col-form-label">{{ __('client.checkout.payment_method') }}</label>
                            <select id="payment_method" name="payment_method" required class="form-control">
                                <option value="">-- {{ __('client.funds.select_payment_method') }} --</option>
                                @if(isset($gateways) && $gateways->isNotEmpty())
                                    @foreach($gateways as $gateway)
                                    <option value="{{ $gateway }}" {{ old('payment_method') === $gateway ? 'selected' : '' }}>
                                        {{ payment_method_label((string) $gateway) }}
                                    </option>
                                    @endforeach
                                @else
                                    <option value="" disabled>{{ __('client.invoices.no_payment_methods') }}</option>
                                @endif
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            {{ __('client.nav.add_funds') }}
                        </button>
                    </fieldset>
                </form>
            </div>
            <div class="card-footer">
                <small>{{ 'Funds added are non-refundable and can only be used towards future invoices.' }}</small>
            </div>
        </div>
    </div>

</div>

@endsection

@section('scripts')
<script>
function setAmount(v, btn) {
    document.getElementById('amount').value = v;
    if (window.updateConversion) { window.updateConversion(); }
    btn.parentNode.querySelectorAll('.btn').forEach(function (b) { b.classList.remove('active'); });
    btn.classList.add('active');
}
</script>

<script>
(function () {
    var input = document.getElementById('amount');
    var target = document.getElementById('convertedAmount');

    if (!input || !target) { return; }

    var rate = parseFloat(input.getAttribute('data-rate') || '0');
    var sign = @json($shopSymbol);

    window.updateConversion = function () {
        var paid = parseFloat(input.value);

        if (!rate || !isFinite(paid) || paid <= 0) {
            target.textContent = sign + '0.00';
            return;
        }

        target.textContent = sign + (paid / rate).toFixed(2);
    };

    input.addEventListener('input', window.updateConversion);
    window.updateConversion();
})();
</script>
@endsection
