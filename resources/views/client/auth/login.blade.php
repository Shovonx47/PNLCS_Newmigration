@section('no_page_header')@endsection
@extends('client.layouts.twentyone')
@section('title', __('client.auth.login_title'))

@section('no_breadcrumb')@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6 col-xl-5">
        <form method="POST" action="{{ route('client.login.submit') }}" class="login-form" role="form">
            @csrf
            <div class="card mb-md-4 mt-md-4">
                <div class="card-body px-sm-5 py-5">
                    <div class="mb-4">
                        <h6 class="h3">Login</h6>
                        <p class="text-muted mb-0">Sign in to your account to continue.</p>
                    </div>

                    @if($errors->any())
                        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
                    @endif

                    <div class="form-group">
                        <label for="email" class="form-control-label">{{ __('common.form.email_address') }}</label>
                        <div class="input-group input-group-merge">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                            </div>
                            <input type="email" class="form-control" name="email" id="email" value="{{ old('email') }}" placeholder="name@example.com" autofocus required>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <label for="password" class="form-control-label">{{ __('common.form.password') }}</label>
                            <div class="mb-2">
                                <a href="{{ route('client.password.request') }}" class="small text-muted" tabindex="-1">{{ __('client.auth.forgot_password') }}</a>
                            </div>
                        </div>
                        <div class="input-group input-group-merge">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-key"></i></span>
                            </div>
                            <input type="password" class="form-control pw-input" name="password" id="password" placeholder="{{ __('common.form.password') }}" autocomplete="off" required>
                            <div class="input-group-append">
                                <button class="btn btn-default btn-reveal-pw" type="button" tabindex="-1">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    @include('client.partials.recaptcha', ['form' => 'login'])

                    <div class="float-left">
                        <button id="login" type="submit" class="btn btn-primary">{{ __('client.auth.sign_in') }}</button>
                    </div>
                    <div class="text-right">
                        <label>
                            <input type="checkbox" class="form-check-input" name="remember" />
                            {{ __('client.auth.remember_me') }}
                        </label>
                    </div>
                </div>
                <div class="card-footer px-md-5">
                    <small>{{ __('client.auth.no_account') }}</small>
                    <a href="{{ route('client.register') }}" class="small font-weight-bold">{{ __('common.actions.register') }}</a>
                </div>
            </div>
        </form>
        @include('client.partials.google-button')
    </div>
</div>
@endsection
