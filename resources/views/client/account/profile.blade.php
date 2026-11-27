@extends('client.layouts.twentyone')
@section('title', __('client.edit_profile'))

@section('page_subheading')
    {{ __('client.profile.edit_subtitle') }}
@endsection

@section('content')

@if(($accounts ?? collect())->count() > 1)
<div class="card">
    <div class="card-body">
        <h3 class="card-title">{{ __('client.account.accounts') }}</h3>
        <p class="text-muted">{{ __('client.account.accounts_hint') }}</p>
        @foreach($accounts as $account)
            <form method="POST" action="{{ route('client.account.switch', $account) }}" class="d-inline-block mr-2 mb-2">
                @csrf
                <button type="submit" class="btn {{ $client && $client->id === $account->id ? 'btn-primary' : 'btn-default' }} btn-sm" @disabled($client && $client->id === $account->id)>
                    {{ trim($account->first_name.' '.$account->last_name) ?: $account->email }}
                </button>
            </form>
        @endforeach
    </div>
</div>
@endif

@if(session('fatura_sonrasi_odeme'))
<div class="alert alert-info" role="alert">{{ __('client.cart.billing_identity_required') }}</div>
@endif

<div class="card">
    <div class="card-body">
        <h3 class="card-title">{{ __('client.profile.personal_info') }}</h3>

        @if($errors->any())
        <div class="alert alert-danger" role="alert">
            @foreach($errors->all() as $validationError)
                <div>{{ $validationError }}</div>
            @endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('client.account.update') }}">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="form-group col-md-6">
                    <label for="first_name">{{ __('common.form.first_name') }}<span class="text-danger">*</span></label>
                    <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" required class="form-control">
                </div>
                <div class="form-group col-md-6">
                    <label for="last_name">{{ __('common.form.last_name') }}<span class="text-danger">*</span></label>
                    <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" required class="form-control">
                </div>
            </div>

            <div class="row">
                <div class="form-group col-md-6">
                    <label for="email">{{ __('common.form.email_address') }}<span class="text-danger">*</span></label>
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required class="form-control"
                           data-giris-epostasi="{{ $user->email }}">
                </div>
                {{-- Asked only when the login email changes. --}}
                <div class="form-group col-md-6" data-eposta-onay @unless($errors->has('current_password')) hidden @endunless>
                    <label for="current_password">{{ __('client.account.current_password') }}<span class="text-danger">*</span></label>
                    <input type="password" id="current_password" name="current_password" value="" autocomplete="off" readonly data-oto-doldurma-yok class="form-control">
                    <small class="text-muted">{{ __('client.account.email_change_needs_password') }}</small>
                    @error('current_password')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
            </div>

            <div data-musteri-tipi>
                <div class="form-group">
                    <label>{{ __('client.form.client_type') }}<span class="text-danger">*</span></label>
                    <div class="d-flex gap-8 mb-2">
                        <label class="mr-3"><input type="radio" name="client_type" value="individual" @checked(old('client_type', $client?->client_type ?: 'individual') === 'individual') required> {{ __('client.form.client_type_individual') }}</label>
                        <label><input type="radio" name="client_type" value="company" @checked(old('client_type', $client?->client_type) === 'company')> {{ __('client.form.client_type_company') }}</label>
                    </div>
                    @error('client_type') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="row" data-mt="individual">
                    <div class="form-group col-md-6">
                        <label for="national_id">{{ __('client.form.national_id') }}<span class="text-danger">*</span></label>
                        <input type="text" id="national_id" name="national_id" value="{{ old('national_id', $client?->national_id) }}" inputmode="numeric" maxlength="20" class="form-control">
                        @error('national_id') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div data-mt="company">
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="company_name">{{ __('client.form.company_title') }}<span class="text-danger">*</span></label>
                            <input type="text" id="company_name" name="company_name" value="{{ old('company_name', $client?->company_name) }}" class="form-control">
                            @error('company_name') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-group col-md-6" id="tax-office-row" style="{{ strtoupper((string) old('country', $client->country)) === 'TR' ? '' : 'display:none;' }}">
                            <label for="tax_office">{{ __('client.form.tax_office') }}</label>
                            <input type="text" id="tax_office" name="tax_office" value="{{ old('tax_office', $client?->tax_office) }}" class="form-control">
                            @error('tax_office') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="form-group col-md-6">
                    <label for="billing_email">{{ __('common.form.billing_email') }}</label>
                    <input type="email" id="billing_email" name="billing_email" value="{{ old('billing_email', $client?->billing_email) }}" class="form-control">
                    <small class="text-muted">{{ __('client.account.billing_email_hint') }}</small>
                </div>
                <div class="form-group col-md-6">
                    <label for="phone_prefix">{{ __('common.form.phone_number') }}</label>
                    <div class="input-group">
                        <select name="phone_prefix" id="phone_prefix" class="form-control" style="max-width:130px">
                            @foreach(\App\Support\Countries::PHONE_PREFIXES as $code => $prefix)
                            <option value="{{ $prefix }}" {{ old('phone_prefix', $client?->phone_prefix) == $prefix ? 'selected' : '' }}>{{ $code }} {{ $prefix }}</option>
                            @endforeach
                        </select>
                        <input type="text" id="phone_number" name="phone_number" value="{{ old('phone_number', $client?->phone_number) }}" class="form-control">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="form-group col-md-6">
                    <label for="country">{{ __('common.form.country') }}</label>
                    <select id="country" name="country" class="form-control">
                        <option value="">{{ __('common.form.select_country') }}</option>
                        @if(isset($countries))
                            @foreach($countries as $code => $name)
                            <option value="{{ $code }}" {{ old('country', $client?->country) == $code ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                @if(isset($languages) && $languages->isNotEmpty())
                <div class="form-group col-md-6">
                    <label for="language">{{ __('common.form.language') }}</label>
                    <select id="language" name="language" class="form-control">
                        @foreach($languages as $lang)
                        <option value="{{ $lang->code }}" {{ old('language', $client?->language) == $lang->code ? 'selected' : '' }}>{{ $lang->native_name ?? $lang->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>

            <div class="form-group">
                <label for="address1">{{ __('common.form.street_address') }}</label>
                <input type="text" id="address1" name="address1" value="{{ old('address1', $client?->address1) }}" class="form-control" placeholder="{{ __('common.form.street_address_placeholder') }}">
            </div>

            <div class="row">
                <div class="form-group col-md-6">
                    <label for="city">{{ __('common.form.city') }}</label>
                    <input type="text" id="city" name="city" value="{{ old('city', $client?->city) }}" class="form-control">
                </div>
                <div class="form-group col-md-6">
                    <label for="postcode">{{ __('common.form.postcode') }}</label>
                    <input type="text" id="postcode" name="postcode" value="{{ old('postcode', $client?->postcode) }}" class="form-control">
                </div>
            </div>

            <div class="row">
                <div class="form-group col-md-6">
                    <label for="state">{{ __('common.form.state') }}</label>
                    <input type="text" id="state" name="state" value="{{ old('state', $client?->state) }}" class="form-control">
                </div>
                <div class="form-group col-md-6">
                    <label for="tax_id">{{ __('common.form.tax_id') }}</label>
                    <input type="text" id="tax_id" name="tax_id" value="{{ old('tax_id', $client?->tax_id) }}" class="form-control">
                    <small class="text-muted">{{ __('common.form.tax_id_hint') }}</small>
                </div>
            </div>

            @if(isset($customFields) && $customFields->isNotEmpty())
            <hr>
            <h4 class="mb-3">{{ __('client.profile.custom_fields') }}</h4>
            @include('client.partials.custom-fields', ['fields' => $customFields, 'clientId' => $client?->id])
            @endif

            @if($client)
            <hr>
            <div class="form-group">
                <input type="hidden" name="marketing_section" value="1">
                <label class="d-flex align-items-start gap-8">
                    <input type="checkbox" name="marketing_emails" value="1" @checked(old('marketing_emails', \App\Models\MarketingConsent::optedIn($client)))>
                    <span>{{ __('client.marketing.profile_box') }}<br><small class="text-muted">{{ __('client.marketing.profile_hint') }}</small></span>
                </label>
            </div>
            @endif

            <button type="submit" class="btn btn-primary">{{ session('fatura_sonrasi_odeme') ? __('client.cart.save_and_continue') : __('common.actions.save_changes') }}</button>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
(function () {
    function baglaMusteriTipi(kok) {
        var sahis  = kok.querySelector('[data-mt="individual"]');
        var sirket = kok.querySelector('[data-mt="company"]');
        var radios = kok.querySelectorAll('input[name="client_type"]');

        if (!sahis || !sirket || !radios.length) { return; }

        function uygula() {
            var secili = kok.querySelector('input[name="client_type"]:checked');
            var deger = secili ? secili.value : 'individual';
            sahis.hidden  = deger !== 'individual';
            sirket.hidden = deger !== 'company';
        }

        Array.prototype.forEach.call(radios, function (r) {
            r.addEventListener('change', uygula);
        });

        uygula();
    }

    function baslat() {
        Array.prototype.forEach.call(
            document.querySelectorAll('[data-musteri-tipi]'),
            baglaMusteriTipi
        );
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', baslat);
    } else {
        baslat();
    }
})();
</script>

<script>
(function () {
    var map = {!! json_encode(\App\Support\Countries::PHONE_PREFIXES) !!};
    var country = document.getElementById('country');
    var row = document.getElementById('tax-office-row');
    var prefix = document.getElementById('phone_prefix');
    function sync() {
        var code = (country.value || '').toUpperCase();
        if (row) row.style.display = (code === 'TR') ? '' : 'none';
        if (prefix && map[code]) prefix.value = map[code];
    }
    if (country) country.addEventListener('change', sync);
    sync();
})();
</script>

<script>
(function () {
    var eposta = document.querySelector('[data-giris-epostasi]');
    var kutu   = document.querySelector('[data-eposta-onay]');

    if (!eposta || !kutu) { return; }

    var asil = (eposta.getAttribute('data-giris-epostasi') || '').trim().toLowerCase();

    function uygula() {
        var degisti = eposta.value.trim().toLowerCase() !== asil;
        kutu.hidden = !degisti;
        if (!degisti) {
            var alan = kutu.querySelector('input');
            if (alan) { alan.value = ''; }
        }
    }

    eposta.addEventListener('input', uygula);
    eposta.addEventListener('change', uygula);
    uygula();
})();
</script>

<script>
(function () {
    function serbestBirak(e) {
        e.target.removeAttribute('readonly');
    }

    Array.prototype.forEach.call(
        document.querySelectorAll('[data-oto-doldurma-yok]'),
        function (alan) {
            alan.value = '';
            alan.addEventListener('focus', serbestBirak, { once: true });
            alan.addEventListener('mousedown', serbestBirak, { once: true });
        }
    );
})();
</script>
@endsection
