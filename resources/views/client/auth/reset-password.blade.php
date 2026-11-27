@extends('client.layouts.twentyone')
@section('no_page_header', '1')
@section('title', __('client.auth.reset_password_title'))
@section('content')

<div class="card mw-540 mb-md-4 mt-md-4 mx-auto">
    <div class="card-body px-sm-5 py-5">
        <h3 class="card-title mb-4">{{ __('client.auth.reset_password') }}</h3>

        @if($errors->any())
            <div class="alert alert-danger">
                @foreach($errors->all() as $e)
                    <div>{{ $e }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('client.password.update.reset') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="form-group">
                <label for="inputEmail">{{ __('common.form.email') }}</label>
                <input type="email" id="inputEmail" name="email" class="form-control" value="{{ $email ?? old('email') }}" required>
            </div>
            <div class="form-group">
                <label for="inputPassword">{{ __('common.form.new_password') }}</label>
                <input type="password" id="inputPassword" name="password" class="form-control" minlength="8" required autocomplete="new-password">
            </div>
            <div class="form-group">
                <label for="inputPasswordConfirm">{{ __('common.form.confirm_password') }}</label>
                <input type="password" id="inputPasswordConfirm" name="password_confirmation" class="form-control" required autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-primary btn-block">{{ __('client.auth.reset_password') }}</button>
        </form>
    </div>
</div>

@endsection
