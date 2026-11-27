@extends('client.layouts.twentyone')
@section('no_page_header', '1')
@section('title', __('client.confirm.title'))
@section('content')

<div class="card mw-540 mb-md-4 mt-md-4 mx-auto">
    <div class="card-body px-sm-5 py-5">
        <h3 class="card-title">{{ __('client.confirm.title') }}</h3>
        <p class="text-muted">{{ __('client.confirm.intro', ['email' => $email]) }}</p>

        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        @if($codeSent)
            <form method="POST" action="{{ route('client.confirm.verify') }}">
                @csrf
                <div class="form-group">
                    <label for="code">{{ __('client.confirm.code_label', ['minutes' => $minutes]) }}</label>
                    <input type="text" id="code" name="code" required autofocus autocomplete="one-time-code"
                           inputmode="numeric" maxlength="12" class="form-control form-control-lg text-center">
                </div>
                <button type="submit" class="btn btn-primary btn-block">{{ __('client.confirm.verify') }}</button>
            </form>
            <form method="POST" action="{{ route('client.confirm.send') }}" class="mt-2">
                @csrf
                <button type="submit" class="btn btn-default btn-block">{{ __('client.confirm.resend') }}</button>
            </form>
        @else
            <form method="POST" action="{{ route('client.confirm.send') }}">
                @csrf
                <button type="submit" class="btn btn-primary btn-block">{{ __('client.confirm.send') }}</button>
            </form>
        @endif
    </div>
</div>

@endsection
