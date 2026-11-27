@extends('admin.layouts.app')
@section('title', __('admin.banned_ips.title'))
@section('content')

<h1>{{ __('admin.banned_ips.title') }}</h1>

<div class="context-btn-container">
    <span class="textgrey">{{ __('admin.banned_ips.title') }}</span>
    <button type="button" onclick="document.getElementById('modal-add-ip').style.display='flex'" class="btn btn-primary btn-sm">
        <i class="fas fa-plus"></i> {{ __('admin.banned_ips.ban_ip') }}
    </button>
</div>

@if(($bannedIps ?? collect())->isEmpty())
    <div class="contentbox">{{ __('admin.banned_ips.no_banned') }}</div>
@else
    <div class="tablebg">
        <table class="datatable">
            <thead>
                <tr>
                    <th class="text-left">{{ __('common.table.ip_address') }}</th>
                    <th class="text-left">{{ __('admin.banned_ips.reason') }}</th>
                    <th>{{ __('admin.banned_ips.banned_on') }}</th>
                    <th>{{ __('admin.banned_ips.expires') }}</th>
                    <th>{{ __('common.table.actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($bannedIps as $ban)
                <tr>
                    <td style="font-family:monospace;font-weight:600;">{{ $ban->ip }}</td>
                    <td>{{ $ban->reason ?: '—' }}</td>
                    <td style="text-align:center;">{{ $ban->created_at->timezone(display_tz())->format(datetime_fmt()) }}</td>
                    <td style="text-align:center;">{{ $ban->expires_at?->timezone(display_tz())->format(datetime_fmt()) ?? __('admin.banned_ips.never') }}</td>
                    <td style="text-align:center;">
                        <form method="POST" action="{{ route('admin.config.banned-ips.destroy', $ban) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js($ban->ip), {title: @js(__('admin.banned_ips.confirm_unban'))})">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-xs">{{ __('admin.banned_ips.unban') }}</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif

<div id="modal-add-ip" style="display:none;position:fixed;inset:0;z-index:1050;align-items:center;justify-content:center;">
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);" onclick="document.getElementById('modal-add-ip').style.display='none'"></div>
    <div style="position:relative;background:#fff;border-radius:4px;width:460px;max-width:95%;box-shadow:0 5px 30px rgba(0,0,0,0.3);overflow:hidden;">
        <div style="padding:12px 16px;background:#1a4d80;color:#fff;display:flex;align-items:center;justify-content:space-between;">
            <h4 style="margin:0;font-size:15px;color:#fff;">{{ __('admin.banned_ips.ban_ip') }}</h4>
            <button type="button" onclick="document.getElementById('modal-add-ip').style.display='none'" style="background:none;border:none;font-size:20px;cursor:pointer;color:#fff;">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.config.banned-ips.store') }}">
            @csrf
            <div style="padding:15px;">
                <table class="form" style="margin:0;">
                    <tr>
                        <td class="fieldlabel">{{ __('admin.banned_ips.ip_address') }}</td>
                        <td class="fieldarea"><input type="text" name="ip" required class="form-control" placeholder="{{ '192.168.1.1' }}"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.banned_ips.reason') }}</td>
                        <td class="fieldarea"><input type="text" name="reason" class="form-control"></td>
                    </tr>
                </table>
            </div>
            <div style="padding:10px 15px;background:#f5f5f5;border-top:1px solid #ddd;display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('modal-add-ip').style.display='none'" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
                <button type="submit" class="btn btn-danger btn-sm">{{ __('admin.banned_ips.ban_ip') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
