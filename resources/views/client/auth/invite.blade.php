@extends('client.layouts.twentyone')
@section('no_page_header', '1')
@section('title', __('client.invite.title'))
@section('content')

<div class="card mw-540 mb-md-4 mt-md-4 mx-auto">
    <div class="card-body px-sm-5 py-5">
        <h3 class="card-title">{{ __('client.invite.title') }}</h3>
        <p class="text-muted">
            {{ __('client.invite.intro', ['company' => company_name(), 'account' => trim(($account->company_name ?: $account->first_name.' '.$account->last_name)), 'email' => $invite->email]) }}
        </p>

        @if($errors->any())
            <div class="alert alert-danger">
                @foreach($errors->all() as $e)
                    <div>{{ $e }}</div>
                @endforeach
            </div>
        @endif

        @if($hasLogin)
            @if($signedInAs && $signedInAs->email === $invite->email)
                <form method="POST" action="{{ route('client.invite.accept', $token) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-block">{{ __('client.invite.accept') }}</button>
                </form>
            @else
                <p class="text-muted">{{ __('client.invite.sign_in_first', ['email' => $invite->email]) }}</p>
                <a class="btn btn-primary btn-block" href="{{ route('client.login') }}">{{ __('client.invite.sign_in') }}</a>
            @endif
        @else
            <form method="POST" action="{{ route('client.invite.accept', $token) }}">
                @csrf
                <div class="form-group">
                    <label>{{ __('common.form.email') }}</label>
                    <input type="email" class="form-control" value="{{ $invite->email }}" disabled>
                </div>
                <div class="form-group">
                    <label for="invite-first-name">{{ __('common.form.first_name') }}</label>
                    <input type="text" id="invite-first-name" name="first_name" class="form-control" value="{{ old('first_name') }}" required maxlength="255">
                </div>
                <div class="form-group">
                    <label for="invite-last-name">{{ __('common.form.last_name') }}</label>
                    <input type="text" id="invite-last-name" name="last_name" class="form-control" value="{{ old('last_name') }}" required maxlength="255">
                </div>
                <div class="form-group">
                    <label for="invite-password">{{ __('common.form.new_password') }}</label>
                    <input type="password" id="invite-password" name="password" class="form-control" minlength="8" required autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="invite-password-confirm">{{ __('common.form.confirm_password') }}</label>
                    <input type="password" id="invite-password-confirm" name="password_confirmation" class="form-control" required autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primary btn-block">{{ __('client.invite.create_and_accept') }}</button>
            </form>
        @endif
    </div>
</div>

@endsection
