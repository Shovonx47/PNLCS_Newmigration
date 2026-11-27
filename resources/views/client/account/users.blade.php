@extends('client.layouts.twentyone')
@section('title', __('client.account_users.title'))
@section('content')

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 pl-3">
            @foreach($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card mb-4">
    <div class="card-body">
        <h3 class="card-title">{{ __('client.account_users.logins') }}</h3>
        <p class="text-muted">{{ __('client.account_users.intro') }}</p>

        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>{{ __('common.form.email') }}</th>
                        <th>{{ __('client.account_users.permissions') }}</th>
                        <th class="text-right"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logins as $login)
                        @php($granted = \App\Support\ClientPermissions::granted($login, $client) ?? [])
                        <tr>
                            <td>
                                <div class="font-weight-bold">{{ trim($login->first_name.' '.$login->last_name) ?: $login->email }}</div>
                                <small class="text-muted">{{ $login->email }}</small>
                            </td>
                            <td>
                                @if($login->pivot->owner)
                                    <span class="badge badge-info">{{ __('client.account_users.owner') }}</span>
                                @else
                                    <form method="POST" action="{{ route('client.account.users.update', $login) }}">
                                        @csrf
                                        @method('PUT')
                                        <div>
                                            @foreach($permissionNames as $perm)
                                                <label class="form-check form-check-inline mr-3 mb-1">
                                                    <input type="checkbox" class="form-check-input" name="permissions[]" value="{{ $perm }}" @checked(in_array($perm, $granted, true))>
                                                    <span class="form-check-label small">{{ __('client.account_users.perm_'.$perm) }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                        <button type="submit" class="btn btn-default btn-sm mt-2">{{ __('common.actions.save_changes') }}</button>
                                    </form>
                                @endif
                            </td>
                            <td class="text-right">
                                @if(! $login->pivot->owner && $login->id !== auth()->id())
                                    <form method="POST" action="{{ route('client.account.users.destroy', $login) }}"
                                          onsubmit="return pnConfirm(event, @js(__('client.account_users.confirm_remove')))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">{{ __('client.account_users.remove') }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($invites->isNotEmpty())
    <div class="card mb-4">
        <div class="card-body">
            <h3 class="card-title">{{ __('client.account_users.pending') }}</h3>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <tbody>
                        @foreach($invites as $invite)
                            <tr>
                                <td>{{ $invite->email }}</td>
                                <td class="text-muted small">{{ __('client.account_users.sent_on', ['date' => $invite->created_at->format(date_fmt())]) }}</td>
                                <td class="text-right">
                                    <form method="POST" action="{{ route('client.account.users.invites.destroy', $invite) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-default btn-sm">{{ __('client.account_users.cancel_invite') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

<div class="card">
    <div class="card-body">
        <h3 class="card-title">{{ __('client.account_users.invite') }}</h3>
        <form method="POST" action="{{ route('client.account.users.invite') }}">
            @csrf
            <div class="form-group">
                <label for="invite-user-email">{{ __('common.form.email') }} <span class="text-danger">*</span></label>
                <input type="email" id="invite-user-email" name="email" value="{{ old('email') }}" required class="form-control">
            </div>
            <div class="form-group">
                <label class="d-block">{{ __('client.account_users.permissions') }}</label>
                <div class="card bg-light">
                    <div class="card-body py-2 px-3">
                        @foreach($permissionNames as $perm)
                            <label class="form-check form-check-inline mr-3 my-1">
                                <input type="checkbox" class="form-check-input" name="permissions[]" value="{{ $perm }}" @checked(in_array($perm, old('permissions', $permissionNames), true))>
                                <span class="form-check-label">{{ __('client.account_users.perm_'.$perm) }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">{{ __('client.account_users.send_invite') }}</button>
            <small class="form-text text-muted mt-2">{{ __('client.account_users.invite_hint', ['days' => \App\Models\UserInvite::VALID_DAYS]) }}</small>
        </form>
    </div>
</div>

@endsection
