@extends('admin.layouts.app')
@section('title', __('admin.registrars.title'))
@section('content')

<h1>{{ __('admin.registrars.title') }}</h1>

@if(($registrars ?? collect())->isEmpty())
<div class="infobox">{{ __('admin.registrars.no_registrars') }}</div>
@else
@foreach($registrars as $reg)
    <details class="gw-card" @if($reg->active) open @endif>
        <summary>
            <span class="gw-chevron" style="display:inline-block;transition:transform .2s;color:#1a4d80;">&#9656;</span>
            <span style="flex:1;font-weight:700;color:#1a4d80;display:flex;align-items:center;gap:8px;">
                {{ $reg->label }}
                @if($reg->help)
                <span class="help-badge" tabindex="0">?
                    <span class="help-tooltip"><span class="help-tooltip-inner">{{ inline_markup($reg->help) }}</span></span>
                </span>
                @endif
            </span>
            <span class="whmcs-status {{ $reg->active ? 'active badge-active' : 'terminated' }}">{{ $reg->active ? __('common.status.active') : __('common.status.inactive') }}</span>
        </summary>
        <div style="padding:12px 14px;border-top:1px solid #dcdcdc;">
            <form method="POST" action="{{ route('admin.config.registrars.settings.update', $reg->registrar_name) }}">
                @csrf
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="24%">{{ __('admin.registrars.enable_registrar') }}</td>
                        <td class="fieldarea">
                            <label style="font-weight:400;margin:0;cursor:pointer;">
                                <input type="checkbox" name="visible" value="1" {{ $reg->active ? 'checked' : '' }}> {{ __('admin.registrars.enable_registrar') }}
                            </label>
                        </td>
                    </tr>

                    @forelse($reg->fields as $field)
                        @php
                            $key = $field['name'];
                            $value = $reg->values[$key] ?? ($field['default'] ?? '');
                            $type = $field['type'] ?? 'text';
                        @endphp
                        <tr>
                            <td class="fieldlabel">{{ $field['label'] ?? ucfirst(str_replace('_', ' ', $key)) }}</td>
                            <td class="fieldarea">
                                @if($type === 'password')
                                    <input type="password" name="settings[{{ $key }}]" value="" class="form-control" style="max-width:420px;" autocomplete="new-password" placeholder="{{ (string) $value !== '' ? __('admin.settings.smtp_password_keep') : '' }}">
                                @elseif($type === 'textarea')
                                    <textarea name="settings[{{ $key }}]" rows="3" class="form-control" style="max-width:520px;">{{ $value }}</textarea>
                                @elseif($type === 'yesno')
                                    <select name="settings[{{ $key }}]" class="form-control" style="max-width:180px;">
                                        <option value="0" {{ (string) $value === '0' ? 'selected' : '' }}>{{ __('common.no') }}</option>
                                        <option value="1" {{ (string) $value === '1' ? 'selected' : '' }}>{{ __('common.yes') }}</option>
                                    </select>
                                @elseif($type === 'select')
                                    <select name="settings[{{ $key }}]" class="form-control" style="max-width:320px;">
                                        @foreach(($field['options'] ?? []) as $optValue => $optLabel)
                                        <option value="{{ $optValue }}" {{ (string) $value === (string) $optValue ? 'selected' : '' }}>{{ $optLabel }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="text" name="settings[{{ $key }}]" value="{{ $value }}" class="form-control" style="max-width:420px;">
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="fieldlabel">{{ __('admin.registrars.config_json') }}</td>
                            <td class="fieldarea"><textarea name="settings_json" rows="4" class="form-control" style="max-width:520px;" placeholder='{"key":"value"}'></textarea></td>
                        </tr>
                    @endforelse
                </table>

                <div class="btn-container" style="margin-bottom:0;">
                    <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.registrars.save_settings') }}</button>
                </div>
            </form>
            @if($reg->testable || $reg->syncable || $reg->importable)
            <div style="display:flex;gap:6px;margin-top:8px;padding-top:8px;border-top:1px solid #eee;flex-wrap:wrap;">
                @if($reg->testable)
                <form method="POST" action="{{ route('admin.config.registrars.test', $reg->registrar_name) }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="btn btn-default btn-sm">{{ __('admin.registrars.test') }}</button>
                </form>
                @endif
                @if($reg->importable)
                <form method="POST" action="{{ route('admin.config.registrars.add-tlds', $reg->registrar_name) }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="btn btn-default btn-sm" onclick="return pnConfirm(event, @js(__('admin.registrars.add_tlds_confirm', ['name' => $reg->label])))">{{ __('admin.registrars.add_tlds') }}</button>
                </form>
                @endif
                @if($reg->syncable)
                <form method="POST" action="{{ route('admin.config.registrars.sync-prices', $reg->registrar_name) }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="btn btn-default btn-sm" onclick="return pnConfirm(event, @js(__('admin.registrars.sync_prices_confirm', ['name' => $reg->label])))">{{ __('admin.registrars.sync_prices') }}</button>
                </form>
                @if(!empty($reg->values['last_price_sync']))
                <div style="font-size:12px;color:#777;align-self:center;">{{ __('admin.registrars.last_sync', ['date' => \Carbon\Carbon::parse($reg->values['last_price_sync'])->format('d.m.Y H:i')]) }}</div>
                @endif
                @endif
            </div>
            @endif
        </div>
    </details>
@endforeach
@endif

<style>
    .gw-card { border: 1px solid #dcdcdc; background: #fff; margin-bottom: 10px; border-radius: 3px; overflow: visible !important; }
    .gw-card summary { cursor: pointer; display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: #eef2f7; list-style: none; font-size: 13px; }
    .gw-card summary::-webkit-details-marker { display: none; }
    .gw-card[open] .gw-chevron { transform: rotate(90deg); }
    .help-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #cbd5e1;
        color: #334155;
        font-size: 11px;
        font-weight: 700;
        line-height: 1;
        cursor: help;
        position: relative;
        flex: 0 0 auto;
    }
    .help-tooltip {
        visibility: hidden;
        opacity: 0;
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        padding-top: 8px;
        z-index: 300;
        transition: opacity .12s ease;
    }
    .help-tooltip-inner {
        display: block;
        width: 360px;
        max-width: 80vw;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 4px;
        box-shadow: 0 6px 20px rgba(0,0,0,.15);
        padding: 12px 14px;
        font-size: 12px;
        font-weight: 400;
        line-height: 1.55;
        color: #374151;
        text-align: left;
    }
    .help-badge:hover .help-tooltip,
    .help-badge:focus .help-tooltip {
        visibility: visible;
        opacity: 1;
    }
</style>
@endsection
