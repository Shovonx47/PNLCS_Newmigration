@extends('admin.layouts.app')
@section('title', __('admin.gateways.title'))
@section('content')

<h1>{{ __('admin.gateways.title') }}</h1>
<div class="infobox">{{ __('admin.gateways.description') }}</div>

<style>
    .gw-card { border: 1px solid #dcdcdc; background: #fff; margin-bottom: 10px; border-radius: 3px; }
    .gw-card summary { cursor: pointer; display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: #eef2f7; list-style: none; font-size: 13px; }
    .gw-card summary::-webkit-details-marker { display: none; }
    .gw-card[open] .gw-chevron { transform: rotate(90deg); }
</style>

@foreach($gateways as $gw)
    <details class="gw-card" @if($gw->active) open @endif>
        <summary>
            <span class="gw-chevron" style="display:inline-block;transition:transform .2s;color:#1a4d80;">&#9656;</span>
            <span style="flex:1;font-weight:700;color:#1a4d80;">{{ $gw->label }}</span>
            @if($gw->active && $gw->missing)
            <span class="whmcs-status suspended" title="{{ __('admin.gateways.missing_settings', ['fields' => implode(', ', $gw->missing)]) }}">{{ __('admin.gateways.not_offered') }}</span>
            @else
            <span class="whmcs-status {{ $gw->active ? 'active badge-active' : 'terminated' }}">{{ $gw->active ? __('common.status.active') : __('common.status.inactive') }}</span>
            @endif
        </summary>
        <div style="padding:12px 14px;border-top:1px solid #dcdcdc;">
            @if($gw->active && $gw->missing)
            <div class="alert alert-warning" style="margin:0 0 12px;font-size:12.5px;">{{ __('admin.gateways.missing_settings', ['fields' => implode(', ', $gw->missing)]) }}</div>
            @endif
            <form method="POST" action="{{ route('admin.config.gateways.settings.update', $gw->name) }}">
                @csrf
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="24%">{{ __('admin.gateways.enable_gateway') }}</td>
                        <td class="fieldarea">
                            <label style="font-weight:400;margin:0;cursor:pointer;">
                                <input type="checkbox" name="active" value="1" {{ $gw->active ? 'checked' : '' }}> {{ __('admin.gateways.enable_gateway') }}
                            </label>
                        </td>
                    </tr>

                    @forelse($gw->fields as $field)
                        @php
                            $key = $field['name'];
                            $value = $gw->values[$key] ?? ($field['default'] ?? '');
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
                                @if(! empty($field['description']))
                                <div style="font-size:11px;color:#666;margin-top:3px;">{{ $field['description'] }}</div>
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
                    <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.gateways.save_settings') }}</button>
                </div>
            </form>

            @if($gw->vaults_cards)
                <div style="margin-top:10px;padding-top:10px;border-top:1px solid #eee;font-size:12px;color:#666;">
                    <span class="whmcs-status {{ $autoChargeOn ? 'active badge-active' : 'terminated' }}">
                        {{ $autoChargeOn ? __('common.status.active') : __('common.status.inactive') }}
                    </span>
                    {{ __('admin.gateways.auto_charge_note') }}
                    <a href="{{ route('admin.settings.general') }}#auto-charge">{{ __('admin.gateways.auto_charge_link') }}</a>
                </div>
            @endif
        </div>
    </details>
@endforeach
@endsection
