@extends('client.layouts.twentyone')
@section('no_page_header', '1')
@section('title', __('client.auth.enable_2fa_title'))
@section('content')

<div class="card mw-540 mb-md-4 mt-md-4 mx-auto">
    <div class="card-body px-sm-5 py-5">
        <h3 class="card-title">{{ __('client.auth.enable_2fa_title') }}</h3>
        <p class="text-muted">{{ __('client.auth.scan_qr_code') }}</p>

        <div class="text-center my-4">
            <img src="{{ $qrImage }}" width="200" height="200" alt="{{ __('client.auth.qr_code_alt') }}" class="img-thumbnail">
        </div>

        <p class="text-center text-muted small mb-2">{{ __('client.auth.or_enter_manually') }}</p>
        <div class="text-center mb-4">
            <code class="p-2 bg-light border rounded d-inline-block text-monospace">{{ $secret }}</code>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('client.2fa.enable') }}">
            @csrf
            <div class="form-group">
                <label for="inputVerify2faCode">{{ __('client.auth.enter_6_digit_verify') }}</label>
                <input type="text" id="inputVerify2faCode" name="code" class="form-control form-control-lg text-center"
                       autofocus autocomplete="one-time-code" inputmode="numeric" maxlength="6" placeholder="{{ '000000' }}">
            </div>
            <button type="submit" class="btn btn-primary btn-block">{{ __('client.auth.enable_2fa_btn') }}</button>
            <a href="{{ route('client.account.security') }}" class="btn btn-default btn-block mt-2">{{ __('common.actions.cancel') }}</a>
        </form>
    </div>
    <div class="card-footer px-md-5">
        <a href="{{ route('client.account.security') }}" class="small">&larr; {{ __('client.auth.back_to_security') }}</a>
    </div>
</div>

@endsection
