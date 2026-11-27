@extends('admin.layouts.app')
@section('title', __('admin.auth.enable_2fa_title'))
@section('content')
<h1>{{ __('admin.auth.enable_2fa_title') }}</h1>

<div class="infobox">
    {{ __('admin.auth.scan_qr_desc') }}
</div>

@if($errors->any())
<div class="alert alert-danger" style="margin-bottom:12px;">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('admin.2fa.enable') }}">
    @csrf
    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
        <tr>
            <td width="20%" class="fieldlabel">{{ __('admin.auth.enable_2fa_title') }}</td>
            <td class="fieldarea">
                <img src="{{ $qrImage }}" width="200" height="200" alt="{{ 'QR Code' }}" style="border:3px solid #e5e5e5;border-radius:4px;background:#fff;padding:4px;">
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ __('admin.auth.enter_manually') }}</td>
            <td class="fieldarea">
                <code style="font-size:14px;background:#f5f5f5;padding:5px 10px;border:1px solid #ddd;border-radius:3px;letter-spacing:2px;">{{ $secret }}</code>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ __('admin.auth.verify_code_label') }}</td>
            <td class="fieldarea">
                <input type="text" name="code" class="form-control" autofocus autocomplete="one-time-code" inputmode="numeric" maxlength="6" placeholder="{{ '000000' }}" style="max-width:180px;text-align:center;font-size:16px;letter-spacing:4px;">
            </td>
        </tr>
    </table>

    <div class="btn-container">
        <button type="submit" class="btn btn-primary">{{ __('admin.auth.enable_2fa_button') }}</button>
        <a href="{{ route('admin.my-account') }}" class="btn btn-default">{{ __('common.actions.cancel') }}</a>
    </div>
</form>
@endsection
