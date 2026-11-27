@extends('client.layouts.twentyone')
@section('title', __('client.auth.register_title'))
@section('styles')
<style>
.register-addr-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0 16px; }
</style>
@endsection
@section('content')

<div id="registration">
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 pl-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('client.register.submit') }}" id="frmCheckout">
        @csrf

        <div id="containerNewUserSignup">
            <div class="card mb-4">
                <div class="card-body p-4" id="personalInformation">
                    <h3 class="card-title">{{ __('client.auth.create_your_account') }}</h3>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="first_name">{{ __('common.form.first_name') }} <span class="text-danger">*</span></label>
                                <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" required autofocus class="form-control">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="last_name">{{ __('common.form.last_name') }} <span class="text-danger">*</span></label>
                                <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" required class="form-control">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="email">{{ __('common.form.email_address') }} <span class="text-danger">*</span></label>
                                <input type="email" id="email" name="email" value="{{ old('email') }}" required class="form-control">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="phone_number">
                                    {{ __('client.form.phone') }} <span class="text-muted font-weight-normal">({{ __('client.form.optional') }})</span>
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <select name="phone_prefix" id="phone_prefix" class="custom-select">
                                            @foreach(\App\Support\Countries::PHONE_PREFIXES as $code => $prefix)
                                                <option value="{{ $prefix }}" {{ old('phone_prefix') == $prefix ? 'selected' : '' }}>{{ $code }} {{ $prefix }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <input type="text" id="phone_number" name="phone_number" value="{{ old('phone_number') }}" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body p-4">
                    <h3 class="card-title">{{ __('common.form.billing_address') }}</h3>

                    @php $clientType = old('client_type') ?: 'individual'; @endphp
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="d-block">{{ __('client.form.client_type') }}</label>
                                <div class="form-check form-check-inline mt-1">
                                    <input type="radio" class="form-check-input" id="client_type_individual" name="client_type" value="individual" {{ $clientType === 'individual' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="client_type_individual">{{ __('client.form.client_type_individual') }}</label>
                                </div>
                                <div class="form-check form-check-inline mt-1">
                                    <input type="radio" class="form-check-input" id="client_type_company" name="client_type" value="company" {{ $clientType === 'company' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="client_type_company">{{ __('client.form.client_type_company') }}</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="company_name">
                                    {{ __('client.form.company') }} <span class="text-muted font-weight-normal">({{ __('client.form.optional') }})</span>
                                </label>
                                <input type="text" id="company_name" name="company_name" value="{{ old('company_name') }}" class="form-control">
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <div class="form-group">
                                <label for="country">{{ __('common.form.country') }}</label>
                                <select id="country" name="country" class="form-control custom-select">
                                    @foreach($countries as $code => $name)
                                        <option value="{{ $code }}" {{ old('country', $detectedCountry ?? '') === $code ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    @include('client.partials.billing-address-fields', ['gridClass' => 'register-addr-grid', 'showPhone' => false, 'showCountry' => false])
                </div>
            </div>
        </div>

        <div id="containerNewUserSecurity">
            <div class="card mb-4">
                <div class="card-body p-4">
                    <h3 class="card-title">{{ __('common.form.password') }}</h3>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group mb-sm-0">
                                <label for="password">{{ __('common.form.password') }} <span class="text-danger">*</span></label>
                                <input type="password" id="password" name="password" required class="form-control" autocomplete="new-password">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group mb-0">
                                <label for="password_confirmation">{{ __('common.form.confirm_password') }} <span class="text-danger">*</span></label>
                                <input type="password" id="password_confirmation" name="password_confirmation" required class="form-control" autocomplete="new-password">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body p-4">
                @php
                    $legalLink = function (string $url, string $label) {
                        $url = trim($url);

                        return $url === ''
                            ? e($label)
                            : '<a href="'.e($url).'" target="_blank" rel="noopener">'.e($label).'</a>';
                    };
                @endphp
                <div class="form-check mb-2">
                    <input type="checkbox" class="form-check-input" id="tos" name="tos" value="1" {{ old('tos') ? 'checked' : '' }} required>
                    <label class="form-check-label" for="tos">
                        {{ __('client.auth.i_agree_to') }} {!! $legalLink((string) \App\Models\Setting::get('TOSUrl', ''), __('client.auth.terms_of_service')) !!} {{ __('client.auth.and') }} {!! $legalLink((string) \App\Models\Setting::get('PrivacyUrl', ''), __('client.auth.privacy_policy')) !!}.
                    </label>
                </div>
                @error('tos')
                    <div class="text-danger small mb-2">{{ $message }}</div>
                @enderror

                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="marketing_emails" name="marketing_emails" value="1" @checked(old('marketing_emails'))>
                    <label class="form-check-label" for="marketing_emails">{{ __('client.marketing.signup_box') }}</label>
                </div>
            </div>
        </div>

        <div class="text-center mb-3">
            @include('client.partials.recaptcha', ['form' => 'signup'])
            @error(\App\Services\RecaptchaService::FIELD)
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <p class="text-center">
            <button type="submit" class="btn btn-lg btn-primary px-5">{{ __('client.auth.create_account') }}</button>
        </p>
    </form>

    <div class="mw-540 mx-auto text-center">
        @include('client.partials.google-button')
    </div>

    <p class="text-center text-muted mt-3">
        {{ __('client.auth.already_have_account') }} <a href="{{ route('client.login') }}">{{ __('client.auth.sign_in') }}</a>
    </p>
</div>

@endsection

@section('scripts')
<script>
(function () {
    var map = {!! json_encode(\App\Support\Countries::PHONE_PREFIXES) !!};
    var country = document.getElementById('country');
    var prefix = document.getElementById('phone_prefix');
    if (!country || !prefix) return;
    function sync() {
        var code = (country.value || '').toUpperCase();
        if (map[code]) prefix.value = map[code];
    }
    country.addEventListener('change', sync);
    sync();
})();
</script>
@endsection
