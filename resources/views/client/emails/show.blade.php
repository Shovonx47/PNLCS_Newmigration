<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $textDirection ?? 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('client.emails.title') }} - {{ company_name() }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('templates/twenty-one/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('templates/twenty-one/css/theme.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/pnlcs-theme-compat.css') }}">
</head>
<body id="popup-backdrop">
    <div class="card bg-default">
        <div class="card-header">
            <h2 class="popup-header-padding"><i class="far fa-envelope">&nbsp;</i>{{ $email->subject }}</h2>
        </div>
        <div class="card-body">
            <iframe width="100%" height="380" frameborder="0" srcdoc="{{ $email->message }}"></iframe>
        </div>
        <div class="card-footer text-center">
            <button type="button" class="btn btn-primary" onclick="window.close()">
                {{ 'Close Window' }}
            </button>
        </div>
    </div>
</body>
</html>
