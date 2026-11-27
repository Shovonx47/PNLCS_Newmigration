@extends('client.layouts.twentyone')
@section('no_page_header', '1')
@section('title', __('client.auth.2fa_title'))
@section('content')

<div class="card mw-540 mb-md-4 mt-md-4 mx-auto">
    <div class="card-body px-sm-5 py-5">
        <h3 class="card-title">{{ __('client.auth.2fa_heading') }}</h3>
        <p class="text-muted mb-4">{{ __('client.auth.2fa_subtitle') }}</p>

        @if($errors->any())
            <div class="alert alert-danger text-center">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('client.2fa.verify.submit') }}">
            @csrf
            <div class="form-group">
                <label for="inputTwoFactorCode">{{ __('client.auth.verification_code') }}</label>
                <input type="text" id="inputTwoFactorCode" name="code" class="form-control form-control-lg text-center"
                       autofocus autocomplete="one-time-code" inputmode="numeric" maxlength="9" placeholder="{{ '000000' }}">
            </div>
            <button type="submit" class="btn btn-primary btn-lg btn-block">{{ __('client.auth.verify') }}</button>
        </form>
    </div>
    <div class="card-footer px-md-5 d-flex justify-content-between align-items-center flex-wrap">
        <small class="text-muted">{{ __('client.auth.backup_code_hint') }}</small>
        <form method="POST" action="{{ route('client.logout') }}" class="m-0">
            @csrf
            <button type="submit" class="btn btn-link btn-sm p-0">{{ __('client.auth.cancel_logout') }}</button>
        </form>
    </div>
</div>

@endsection
