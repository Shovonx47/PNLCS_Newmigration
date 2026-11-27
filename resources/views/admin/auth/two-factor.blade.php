<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $textDirection ?? 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('admin.auth.two_factor_title') }} - {{ company_name() }}</title>
    @vite(["resources/css/app.css"])
    @include('admin.auth.partials.head')
    <style>
        body.whmcs-login-body {
            margin: 0;
            padding: 24px 16px;
            min-height: 100vh;
            background-color: #163a63;
            background-image: linear-gradient(180deg, #163a63 0%, #202f60 100%);
            font-family: 'Open Sans', 'Segoe UI', Arial, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
        }
        .login-container {
            width: 100%;
            max-width: 410px;
        }
        .login-box {
            background: #ffffff;
            border-radius: 5px;
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.35);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        .login-header {
            background: #ffffff;
            padding: 24px 30px 16px;
            text-align: center;
            border-bottom: 1px solid #e6ebf1;
        }
        .login-header img {
            max-height: 46px;
            max-width: 220px;
            width: auto;
            height: auto;
            display: inline-block;
            margin-bottom: 10px;
        }
        .login-header h2 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: #163a63;
        }
        .login-header p {
            margin: 5px 0 0;
            color: #666666;
            font-size: 12px;
        }
        .login-body {
            padding: 24px 30px 26px;
        }
        .login-code-input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cccccc;
            border-radius: 3px;
            font-size: 20px;
            font-family: 'Open Sans', 'Segoe UI', Arial, sans-serif;
            text-align: center;
            letter-spacing: 6px;
            box-sizing: border-box;
            color: #333333;
        }
        .login-code-input:focus {
            border-color: #66afe9;
            outline: none;
            box-shadow: 0 0 5px rgba(102, 175, 233, 0.55);
        }
        .login-btn {
            width: 100%;
            padding: 9px 18px;
            background: #337ab7;
            border: 1px solid #2e6da4;
            color: #ffffff;
            border-radius: 3px;
            font-size: 13px;
            font-weight: 600;
            font-family: 'Open Sans', 'Segoe UI', Arial, sans-serif;
            cursor: pointer;
        }
        .login-btn:hover {
            background: #286090;
            border-color: #204d74;
        }
        .login-footer {
            text-align: center;
            font-size: 11px;
            color: rgba(255, 255, 255, 0.68);
            margin-top: 14px;
        }
    </style>
</head>
<body class="whmcs-login-body">

<div class="login-container">
    <div class="login-box">
        <div class="login-header">
            <img src="{{ !empty($customLogo) ? $customLogo : asset('assets/img/logo.png') }}" alt="{{ company_name() }}">
            <h2>{{ __('admin.auth.two_factor_title') }}</h2>
            <p>{{ __('admin.auth.two_factor_desc') }}</p>
        </div>

        <div class="login-body">
            @if($errors->any())
            <div style="background:#f2dede;border:1px solid #ebccd1;color:#a94442;padding:9px 12px;border-radius:3px;margin-bottom:15px;font-size:12px;">
                {{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ route('admin.2fa.verify.submit') }}">
                @csrf
                <div style="margin-bottom:16px;">
                    <label for="code" style="display:block;font-size:12px;font-weight:600;color:#333;margin-bottom:6px;">{{ __('admin.auth.verification_code') }}</label>
                    <input type="text" name="code" id="code" autofocus autocomplete="one-time-code" inputmode="numeric" maxlength="9"
                           class="login-code-input" placeholder="{{ '000000' }}">
                </div>
                <button type="submit" class="login-btn">{{ __('admin.auth.verify') }}</button>
            </form>

            <p style="text-align:center;margin:14px 0 0;font-size:12px;color:#777;">{{ __('admin.auth.backup_code_hint') }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.logout') }}" style="text-align:center;margin-top:14px;">
        @csrf
        <button type="submit" style="background:none;border:none;color:rgba(255,255,255,0.85);cursor:pointer;font-size:12px;text-decoration:underline;font-family:'Open Sans','Segoe UI',Arial,sans-serif;">
            {{ __('admin.auth.cancel_logout') }}
        </button>
    </form>

    <div class="login-footer">
        &copy; {{ date('Y') }} {{ company_name() }}. {{ __('admin.auth.all_rights_reserved') }}
    </div>
</div>

</body>
</html>
