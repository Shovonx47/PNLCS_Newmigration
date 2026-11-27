@extends("admin.layouts.app")

@section("title", __("admin.ssl_module_configuration"))

@section("content")
<h1>{{ __('admin.ssl_modules.title') }}</h1>

<div class="context-btn-container" style="margin-bottom:12px;">
    <a href="{{ route('admin.config.servers') }}" class="btn btn-default btn-sm">&larr; {{ __('common.actions.back') }}</a>
</div>

@foreach($modules as $name => $module)
<div style="margin-bottom:20px;">
    <div style="display:flex;justify-content:space-between;align-items:center;background:#eef2f7;border:1px solid #dcdcdc;border-bottom:none;padding:8px 12px;">
        <strong style="color:#1a4d80;">{{ ucfirst($name) }}</strong>
        <form method="POST" action="{{ route('admin.config.testSslConnection', $name) }}" style="margin:0;display:inline;">
            @csrf
            <button type="submit" class="btn btn-xs btn-default">{{ __('common.actions.test_connection') }}</button>
        </form>
    </div>
    <form method="POST" action="{{ route('admin.config.updateSslModuleSettings', $name) }}">
        @csrf
        <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
            @foreach($module->getConfigFields() as $key => $field)
                <tr>
                    <td class="fieldlabel" width="24%">{{ $field['label'] }}</td>
                    <td class="fieldarea">
                        @if(($field['type'] ?? 'text') === 'checkbox')
                            <label style="font-weight:400;margin:0;cursor:pointer;">
                                <input type="hidden" name="settings[{{ $key }}]" value="0">
                                <input type="checkbox" name="settings[{{ $key }}]" value="1"
                                    {{ ($settings[$name][$key] ?? $field['default'] ?? '0') === '1' ? 'checked' : '' }}>
                                {{ __('common.status.enabled') }}
                            </label>
                        @elseif(($field['type'] ?? 'text') === 'password')
                            {{-- Never echoed back: blank keeps the stored password. --}}
                            <input type="password" name="settings[{{ $key }}]" class="form-control" style="max-width:420px;" value="" autocomplete="new-password"
                                placeholder="{{ ($settings[$name][$key] ?? '') !== '' ? __('admin.settings.smtp_password_keep') : '' }}"
                                {{ !empty($field['required']) && ($settings[$name][$key] ?? '') === '' ? 'required' : '' }}>
                        @else
                            <input type="text" name="settings[{{ $key }}]" class="form-control" style="max-width:420px;"
                                value="{{ $settings[$name][$key] ?? '' }}"
                                {{ !empty($field['required']) ? 'required' : '' }}>
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
        <div class="btn-container">
            <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.ssl_modules.save_settings') }}</button>
        </div>
    </form>
</div>
@endforeach
@endsection
