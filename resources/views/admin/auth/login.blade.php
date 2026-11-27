<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $textDirection ?? 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('admin.auth.admin_login_title') }} - {{ company_name() }}</title>
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
            padding: 26px 30px 18px;
            text-align: center;
            border-bottom: 1px solid #e6ebf1;
        }
        .login-header img {
            max-height: 48px;
            max-width: 230px;
            width: auto;
            height: auto;
            display: inline-block;
        }
        .login-header-sub {
            margin: 8px 0 0;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #5b6b7c;
        }
        .login-body {
            padding: 24px 30px 26px;
        }
        .login-body .form-group {
            margin-bottom: 15px;
        }
        .login-body .form-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #333333;
            margin-bottom: 5px;
        }
        .login-body .form-control {
            width: 100%;
            height: 36px;
            padding: 6px 11px;
            font-size: 13px;
            font-family: 'Open Sans', 'Segoe UI', Arial, sans-serif;
            color: #333333;
            background: #ffffff;
            border: 1px solid #cccccc;
            border-radius: 3px;
            box-sizing: border-box;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
        }
        .login-body .form-control:focus {
            border-color: #66afe9;
            outline: none;
            box-shadow: inset 0 1px 1px rgba(0,0,0,0.05), 0 0 5px rgba(102, 175, 233, 0.55);
        }
        .login-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 18px;
        }
        .login-remember {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 12px;
            color: #555555;
            cursor: pointer;
            margin: 0;
        }
        .login-btn {
            background: #337ab7;
            border: 1px solid #2e6da4;
            color: #ffffff;
            padding: 7px 22px;
            font-size: 13px;
            font-weight: 600;
            font-family: 'Open Sans', 'Segoe UI', Arial, sans-serif;
            border-radius: 3px;
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
            margin-top: 16px;
        }
    </style>
</head>
<body class="whmcs-login-body">

<div class="login-container">
    <div class="login-box">
        <div class="login-header">
            <img src="{{ !empty($customLogo) ? $customLogo : asset('assets/img/logo.png') }}" alt="{{ company_name() }}">
            <p class="login-header-sub">{{ __('admin.auth.administration_area') }}</p>
        </div>

        <div class="login-body">
            @if ($errors->any())
            <div style="background:#f2dede;border:1px solid #ebccd1;color:#a94442;padding:9px 12px;border-radius:3px;font-size:12px;margin-bottom:15px;">
                {{ $errors->first() }}
            </div>
            @endif

            @if (session('status'))
            <div style="background:#dff0d8;border:1px solid #d6e9c6;color:#3c763d;padding:9px 12px;border-radius:3px;font-size:12px;margin-bottom:15px;">
                {{ session('status') }}
            </div>
            @endif

            <form method="POST" action="{{ route('admin.login.submit') }}">
                @csrf

                <div class="form-group">
                    <label for="username" class="form-label">{{ __('common.form.username') }}</label>
                    <input type="text" name="username" id="username" value="{{ old('username') }}"
                        autocomplete="username" required autofocus
                        class="form-control"
                        placeholder="{{ __('admin.auth.enter_username') }}">
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">{{ __('common.form.password') }}</label>
                    <input type="password" name="password" id="password"
                        autocomplete="current-password" required
                        class="form-control"
                        placeholder="{{ '••••••••' }}">
                </div>

                <div class="login-actions">
                    <label for="remember" class="login-remember">
                        <input type="checkbox" name="remember" id="remember" style="width:14px;height:14px;accent-color:#337ab7;cursor:pointer;margin:0;">
                        <span>{{ __('admin.auth.remember_me') }}</span>
                    </label>

                    <button type="submit" class="login-btn">
                        {{ __('admin.auth.sign_in') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="login-footer">
        &copy; {{ date('Y') }} {{ company_name() }}. {{ __('admin.auth.all_rights_reserved') }}
    </div>
</div>

</body>
</html>
