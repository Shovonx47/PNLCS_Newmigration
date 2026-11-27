@extends('client.layouts.twentyone')
@section('title', __('client.security.title'))
@section('content')

<div class="card mb-4">
    <div class="card-body">
        <h3 class="card-title">{{ __('client.security.two_factor') }}</h3>
        <p class="text-muted">{{ __('client.security.2fa_desc') }}</p>

        @if($twoFactorEnabled ?? false)
            <div class="alert alert-success d-flex justify-content-between align-items-center flex-wrap">
                <span class="font-weight-bold mb-2 mb-sm-0">
                    <i class="fas fa-check-circle mr-1"></i>{{ __('client.security.2fa_enabled') }}
                </span>
                <form method="POST" action="{{ route('client.2fa.disable') }}" class="form-inline">
                    @csrf
                    <input type="password" name="password" placeholder="{{ __('client.security.your_password') }}" class="form-control form-control-sm mr-2" required>
                    <button type="submit" class="btn btn-danger btn-sm">{{ __('common.actions.disable') }}</button>
                </form>
            </div>

            @if(is_array(session('backup_codes')) && session('backup_codes'))
                <div class="alert alert-warning mb-0">
                    <h5 class="h6 font-weight-bold mb-1">{{ __('client.security.backup_codes_title') }}</h5>
                    <p class="small mb-2">{{ __('client.security.backup_codes_hint') }}</p>
                    <div class="row">
                        @foreach(session('backup_codes') as $backupCode)
                            <div class="col-6 col-sm-4 col-md-3 mb-1">
                                <code class="text-monospace">{{ $backupCode }}</code>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @else
            <div class="alert alert-warning">{{ __('client.security.2fa_not_enabled') }}</div>
            <a href="{{ route('client.2fa.enable') }}" class="btn btn-success">
                {{ __('client.auth.enable_2fa_btn') }}
            </a>
        @endif
    </div>
</div>

@if($phoneVerifyAvailable ?? false)
    <div class="card mb-4">
        <div class="card-body">
            <h3 class="card-title">{{ __('client.phone_verify.title') }}</h3>
            @if($client?->phone_verified_at)
                <div class="alert alert-success mb-0">
                    <i class="fas fa-check-circle mr-1"></i>{{ __('client.phone_verify.verified_label') }} ({{ $client->full_phone }})
                </div>
            @elseif(! $client?->full_phone)
                <p class="text-muted mb-0">{{ __('client.phone_verify.no_phone') }}</p>
            @elseif(session('phone_code_sent'))
                <p class="text-muted">{{ __('client.phone_verify.enter_code_hint') }}</p>
                <form method="POST" action="{{ route('client.account.phone.verify_check') }}" class="form-inline">
                    @csrf
                    <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code"
                           placeholder="{{ __('client.phone_verify.code_placeholder') }}"
                           class="form-control form-control-sm mr-2" required>
                    <button type="submit" class="btn btn-success btn-sm">{{ __('client.phone_verify.confirm_btn') }}</button>
                </form>
                @error('code')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror
            @else
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <span class="text-muted mb-2 mb-sm-0">{{ __('client.phone_verify.unverified_label') }} ({{ $client->full_phone }})</span>
                    <form method="POST" action="{{ route('client.account.phone.verify') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('client.phone_verify.send_btn') }}</button>
                    </form>
                </div>
                @error('phone')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror
            @endif
        </div>
    </div>
@endif

@if($sessionsSupported ?? false)
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title h5 mb-0">{{ __('client.security.active_sessions') }}</h3>
        </div>
        <div class="table-container clearfix">
            <table class="table table-list mb-0">
                <thead>
                    <tr>
                        <th>{{ __('client.security.device_ip') }}</th>
                        <th>{{ __('client.security.last_activity') }}</th>
                        <th>{{ __('client.security.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sessions ?? [] as $session)
                        <tr>
                            <td>
                                <div class="font-weight-bold">{{ $session->ip_address }}</div>
                                <div class="small text-muted">{{ $session->user_agent ? Str::limit($session->user_agent, 60) : '-' }}</div>
                            </td>
                            <td class="text-muted small">
                                {{ $session->last_activity ? \Carbon\Carbon::createFromTimestamp($session->last_activity)->diffForHumans() : '-' }}
                            </td>
                            <td>
                                @if($session->id !== session()->getId())
                                    <form method="POST" action="{{ route('client.account.security.logout_session', $session->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-danger btn-sm">{{ __('client.security.revoke') }}</button>
                                    </form>
                                @else
                                    <span class="badge badge-success">{{ __('client.security.current') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">{{ __('client.security.no_sessions') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif

<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title h5 mb-0">{{ __('client.security.login_history') }}</h3>
    </div>
    <div class="table-container clearfix">
        <table class="table table-list mb-0">
            <thead>
                <tr>
                    <th>{{ __('client.security.login_when') }}</th>
                    <th>{{ __('client.security.device_ip') }}</th>
                    <th>{{ __('client.security.login_result') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logins ?? [] as $login)
                    <tr>
                        <td class="text-nowrap">{{ $login->created_at?->format(date_fmt().' H:i') }}</td>
                        <td>
                            <div class="font-weight-bold">{{ $login->ip_address ?: '-' }}</div>
                            <div class="small text-muted">
                                {{ \App\Services\LoginRecorder::describe($login->user_agent) }}@if($login->method === 'google') &middot; {{ __('client.security.via_google') }}@endif
                            </div>
                        </td>
                        <td>
                            @if($login->successful)
                                <span class="badge badge-success">{{ __('client.security.login_ok') }}</span>
                            @else
                                <span class="badge badge-danger">{{ __('client.security.login_failed') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted py-4">{{ __('client.security.no_logins') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer text-muted small">
        {{ __('client.security.login_history_hint') }}@if(\App\Services\LoginRecorder::mailEnabled()) {{ __('client.security.login_history_mail') }}@endif
    </div>
</div>

@if($ownsAccount ?? false)
    <div class="card">
        <div class="card-body">
            <h3 class="card-title">{{ __('client.security.your_data') }}</h3>
            <p class="text-muted">{{ __('client.security.your_data_desc') }}</p>
            <form method="POST" action="{{ route('client.account.personal-data') }}">
                @csrf
                <button type="submit" class="btn btn-default btn-sm">{{ __('client.security.download_data') }}</button>
            </form>
        </div>
    </div>
@endif

@endsection
