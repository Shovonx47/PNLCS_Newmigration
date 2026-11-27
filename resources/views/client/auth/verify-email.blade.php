@extends('client.layouts.twentyone')
@section('no_page_header', '1')
@section('title', __('client.email_verify.title'))
@section('content')

<div class="card mw-540 mb-md-4 mt-md-4 mx-auto">
    <div class="card-body px-sm-5 py-5">
        <h3 class="card-title">{{ __('client.email_verify.title') }}</h3>
        <p class="text-muted">{{ __('client.email_verify.subtitle') }}</p>

        <p>
            {{ trans_markup('client.email_verify.sent_to', ['email' => '<strong>'.auth()->user()->email.'</strong>']) }}
        </p>

        @if($errors->has('code'))
            <div class="alert alert-danger">{{ $errors->first('code') }}</div>
        @endif

        <form method="POST" action="{{ route('client.verification.code') }}" class="mb-3">
            @csrf
            <div class="form-group">
                <label for="code">{{ __('client.email_verify.code_label') }}</label>
                <input type="text" id="code" name="code" required autofocus autocomplete="one-time-code"
                       inputmode="numeric" maxlength="12" class="form-control form-control-lg text-center">
            </div>
            <button type="submit" class="btn btn-primary btn-block">{{ __('client.email_verify.code_submit') }}</button>
        </form>

        <p class="text-muted small mb-0">
            {{ __('client.email_verify.hint', ['minutes' => \App\Http\Controllers\Client\EmailVerificationController::CODE_MINUTES]) }}
        </p>
    </div>
    <div class="card-footer px-md-5 d-flex justify-content-between align-items-center flex-wrap">
        <form method="POST" action="{{ route('client.verification.send') }}" class="m-0">
            @csrf
            <button type="submit" class="btn btn-default btn-sm">{{ __('client.email_verify.resend') }}</button>
        </form>
        <a href="{{ route('client.account.profile') }}" class="btn btn-default btn-sm">{{ __('client.email_verify.change_email') }}</a>
    </div>
</div>

@endsection
