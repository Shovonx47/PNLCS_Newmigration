@extends('admin.layouts.app')
@section('title', __('messages.ksef.settings_title'))
@section('content')

<h1>{{ __('messages.ksef.settings_title') }}</h1>

@php
    $ksefFilterUrl = \Illuminate\Support\Facades\Route::has('admin.ksef.index')
        ? route('admin.ksef.index')
        : url()->current();
    $ksefStatusOptions = [
        ''          => __('common.form.all'),
        'pending'   => __('messages.ksef.status_pending'),
        'sent'      => __('messages.ksef.status_sent'),
        'accepted'  => __('messages.ksef.status_accepted'),
        'rejected'  => __('messages.ksef.status_rejected'),
        'error'     => __('messages.ksef.status_error'),
        'corrected' => __('messages.ksef.status_corrected'),
    ];
    $currentKsefStatus = (string) request('status', '');
@endphp

<div class="infobox" style="margin-bottom:12px;">
    {{ __('messages.ksef.addon_output_hint') }}
</div>

<ul class="admin-tabs" role="tablist">
    <li class="active">
        <a href="#ksef-search-tab" class="active" onclick="var el=document.getElementById('ksef-search-pane');if(el){el.style.display=el.style.display==='none'?'block':'none';}return false;">
            {{ __('Search/Filter') }}
        </a>
    </li>
    @foreach($ksefStatusOptions as $val => $label)
        @php $isKsefActive = ($currentKsefStatus === $val); @endphp
        <li class="{{ $isKsefActive ? 'active' : '' }}">
            <a href="{{ $ksefFilterUrl . ($val !== '' ? '?status=' . urlencode($val) : '') }}" class="{{ $isKsefActive ? 'active' : '' }}">{{ $label }}</a>
        </li>
    @endforeach
</ul>

<div class="tab-content admin-tabs" id="ksef-search-pane">
    <div class="tab-pane active">
        <form method="GET" action="{{ $ksefFilterUrl }}">
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td width="15%" class="fieldlabel">{{ __('messages.ksef.status') }}</td>
                    <td width="35%" class="fieldarea">
                        <select name="status" class="form-control select-inline">
                            @foreach($ksefStatusOptions as $val => $label)
                                <option value="{{ $val }}" @selected($currentKsefStatus === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td width="15%" class="fieldlabel">{{ __('messages.ksef.ksef_number') }}</td>
                    <td width="35%" class="fieldarea">
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control input-250" placeholder="{{ __('messages.ksef.ksef_number') }}">
                    </td>
                </tr>
            </table>
            <div class="btn-container">
                <button type="submit" class="btn btn-default">{{ __('common.actions.search') }}</button>
            </div>
        </form>
    </div>
</div>

<div class="context-btn-container" style="display:flex;align-items:center;justify-content:flex-end;margin:10px 0;">
    <form method="POST" action="{{ route('admin.ksef.test') }}" style="margin:0;">
        @csrf
        <button type="submit" class="btn btn-success btn-sm">{{ __('messages.ksef.test') }}</button>
    </form>
</div>

<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th>{{ __('messages.ksef.invoice') }}</th>
                <th>{{ __('messages.ksef.client') }}</th>
                <th style="text-align:right;">{{ __('messages.ksef.total') }}</th>
                <th>{{ __('messages.ksef.status') }}</th>
                <th>{{ __('messages.ksef.ksef_number') }}</th>
                <th>{{ __('messages.ksef.sent_at') }}</th>
                <th style="text-align:right;">{{ __('messages.ksef.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $r)
                @php
                    $statusMap = [
                        'pending'   => ['label' => __('messages.ksef.status_pending'),   'color' => '#f59e0b'],
                        'sent'      => ['label' => __('messages.ksef.status_sent'),      'color' => '#337ab7'],
                        'accepted'  => ['label' => __('messages.ksef.status_accepted'),  'color' => '#46a546'],
                        'rejected'  => ['label' => __('messages.ksef.status_rejected'),  'color' => '#c43c35'],
                        'error'     => ['label' => __('messages.ksef.status_error'),     'color' => '#c43c35'],
                        'corrected' => ['label' => __('messages.ksef.status_corrected'), 'color' => '#6c757d'],
                    ];
                    $status = $statusMap[$r->status] ?? ['label' => $r->status, 'color' => '#6c757d'];
                @endphp
                <tr>
                    <td>
                        @if($r->invoice)
                            <a href="{{ route('admin.invoices.show', $r->invoice) }}" style="font-family:monospace;">#{{ $r->invoice->invoice_num ?? $r->invoice->id }}</a>
                        @else
                            {{ '—' }}
                        @endif
                    </td>
                    <td>{{ $r->invoice?->client?->display_name ?? '—' }}</td>
                    <td style="text-align:right;font-weight:600;">{{ $r->invoice ? money_fmt($r->invoice->total) : '—' }}</td>
                    <td>
                        <span class="whmcs-status {{ strtolower($r->status) }}" style="display:inline-block;padding:2px 8px;border-radius:999px;background:{{ $status['color'] }}1a;color:{{ $status['color'] }};font-weight:600;font-size:12px;">
                            {{ $status['label'] }}
                        </span>
                    </td>
                    <td style="font-family:monospace;">{{ $r->ksef_number ?? '—' }}</td>
                    <td>{{ $r->sent_at ? $r->sent_at->format(date_fmt() . ' H:i') : '—' }}</td>
                    <td style="text-align:right;white-space:nowrap;">
                        @if(in_array($r->status, ['pending', 'error', 'rejected'], true))
                        <form method="POST" action="{{ route('admin.ksef.resend', $r) }}" style="display:inline;margin:0;">
                            @csrf
                            <button type="submit" class="btn btn-xs btn-default">{{ __('messages.ksef.resend') }}</button>
                        </form>
                        @endif
                        @if(in_array($r->status, ['sent', 'accepted'], true))
                        <form method="POST" action="{{ route('admin.ksef.mark-corrected', $r) }}" style="display:inline;margin:0;" onsubmit="return pnConfirm(event, @js(__('messages.ksef.confirm_corrected')));">
                            @csrf
                            <button type="submit" class="btn btn-xs btn-default">{{ __('messages.ksef.mark_corrected') }}</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @if($r->error_message)
                <tr>
                    <td colspan="7" style="color:#c43c35;font-size:12px;background:#fff5f5;">{{ $r->error_message }}</td>
                </tr>
                @endif
            @empty
                <tr>
                    <td colspan="7" style="text-align:center;color:#888;padding:20px;">{{ __('messages.ksef.none_yet') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($records->hasPages())
<div style="margin-top:12px;">
    {{ $records->withQueryString()->links() }}
</div>
@endif

@endsection
