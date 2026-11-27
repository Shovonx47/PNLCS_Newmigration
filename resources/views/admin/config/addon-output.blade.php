@extends("admin.layouts.app")
@section("title", $addon->getDisplayName())
@section("content")

<h1>{{ $addon->getDisplayName() }}</h1>
<div class="infobox">
    {{ $addon->getDescription() }} &mdash; {{ __('admin.appearance.theme_by', ['version' => $addon->getVersion(), 'author' => $addon->getAuthor()]) }}
</div>

<div class="context-btn-container" style="margin-bottom:12px;display:flex;gap:6px;flex-wrap:wrap;">
    @if($dashboardUrl)
        <a href="{{ $dashboardUrl }}" class="btn btn-sm btn-primary">{{ __('admin.addon_modules.open') }} {{ $addon->getDisplayName() }}</a>
    @endif
    <a href="{{ route('admin.config.addons.modules') }}" class="btn btn-sm btn-default">&larr; {{ __('admin.nav.back') }}</a>
</div>

@if(trim((string) $output) !== '')
<div style="border:1px solid #dcdcdc;background:#fff;padding:15px;margin-bottom:16px;">
    {!! $output !!}
</div>
@endif

@if(!empty($config))
<h2 style="font-size:15px;margin:16px 0 8px;">{{ __('admin.addon_modules.settings') }}</h2>
<form method="POST" action="{{ route('admin.config.addons.modules.settings', $name) }}">
    @csrf
    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
        @foreach($config as $field)
            @php($key = $field['name'])
            @php($type = $field['type'] ?? 'text')
            <tr>
                <td class="fieldlabel" width="24%">{{ $field['label'] }}</td>
                <td class="fieldarea">
                    @if($type === 'checkbox')
                        <input type="hidden" name="{{ $key }}" value="0">
                        <input type="checkbox" name="{{ $key }}" value="1"
                            {{ ($settings[$key] ?? $field['default'] ?? '0') == '1' ? 'checked' : '' }}>
                    @elseif($type === 'textarea' && ! empty($field['secret']))
                        <textarea name="{{ $key }}" class="form-control" style="max-width:520px;" rows="3" autocomplete="off"
                            placeholder="{{ ($settings[$key] ?? '') ? __('admin.addon_modules.key_is_set') : '' }}"></textarea>
                    @elseif($type === 'textarea')
                        <textarea name="{{ $key }}" class="form-control" style="max-width:520px;" rows="3">{{ $settings[$key] ?? $field['default'] ?? '' }}</textarea>
                    @elseif($type === 'password')
                        <input type="password" name="{{ $key }}" class="form-control" style="max-width:420px;" autocomplete="new-password"
                            placeholder="{{ ($settings[$key] ?? '') ? __('admin.addon_modules.key_is_set') : '' }}">
                    @elseif($type === 'select')
                        <select name="{{ $key }}" class="form-control" style="max-width:320px;">
                            @foreach($field['options'] ?? [] as $optValue => $optLabel)
                                <option value="{{ $optValue }}" {{ ($settings[$key] ?? $field['default'] ?? '') == $optValue ? 'selected' : '' }}>{{ $optLabel }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" name="{{ $key }}" class="form-control" style="max-width:420px;" value="{{ $settings[$key] ?? $field['default'] ?? '' }}">
                    @endif
                    @if(!empty($field['hint']))
                        <div style="font-size:12px;color:#777;margin-top:3px;">{{ $field['hint'] }}</div>
                    @endif
                </td>
            </tr>
        @endforeach
    </table>
    <div class="btn-container">
        <button type="submit" class="btn btn-primary">{{ __('common.actions.save_changes') }}</button>
    </div>
</form>
@endif

@php($changelog = method_exists($addon, 'changelog') ? $addon->changelog() : [])
@if(!empty($changelog))
<h2 style="font-size:15px;margin:20px 0 8px;">{{ __('admin.addon_modules.changelog') }}</h2>
<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th style="width:140px;">{{ __('Version') }}</th>
                <th style="width:140px;">{{ __('common.table.date') }}</th>
                <th>{{ __('common.table.description') }}</th>
            </tr>
        </thead>
        <tbody>
        @foreach($changelog as $entry)
            <tr>
                <td style="font-weight:700;">{{ 'v'.($entry['version'] ?? '') }}</td>
                <td>{{ $entry['date'] ?? '-' }}</td>
                <td>
                    @if(!empty($entry['changes']))
                    <ul style="margin:0;padding-left:18px;">
                        @foreach($entry['changes'] as $change)
                        <li>{{ $change }}</li>
                        @endforeach
                    </ul>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

@endsection
