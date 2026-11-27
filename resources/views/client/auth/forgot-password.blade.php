@extends('client.layouts.twentyone')
@section('no_page_header', '1')
@section('title', __('client.auth.forgot_password_title'))
@section('content')

<div class="card mw-540 mb-md-4 mt-md-4 mx-auto">
    <div class="card-body px-sm-5 py-5">
        <h3 class="card-title">{{ __('client.auth.forgot_password_heading') }}</h3>
        <p class="text-muted mb-4">{{ __('client.auth.forgot_password_subtitle') }}</p>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                @foreach($errors->all() as $e)
                    <div>{{ $e }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('client.password.email') }}">
            @csrf
            <div class="form-group">
                <label for="inputEmail">{{ __('common.form.email_address') }}</label>
                <input type="email" id="inputEmail" name="email" class="form-control" placeholder="{{ __('common.form.email_address') }}" required autofocus>
            </div>
            @include('client.partials.recaptcha', ['form' => 'password'])
            <button type="submit" class="btn btn-primary btn-block mt-3">{{ __('client.auth.send_reset_link') }}</button>
        </form>
    </div>
    <div class="card-footer px-md-5 text-center">
        <a href="{{ route('client.login') }}">{{ __('client.auth.back_to_login') }}</a>
    </div>
</div>

@endsection
