@if(!empty($moduleProductSchemas))
    @foreach($moduleProductSchemas as $modKey => $fields)
        <div class="card" data-custom-module-card="{{ $modKey }}" style="margin-bottom:15px;display:none;">
            <div class="card-header">
                <strong>{{ $serverModules[$modKey] ?? ucfirst($modKey) }} — {{ __('admin.products.module_settings') }}</strong>
            </div>
            <div class="card-body">
                <input type="hidden" name="custom_module_config_section" value="1">
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px;">
                    @foreach($fields as $field)
                        @php
                            $fKey = $field['name'];
                            $fVal = $cfg[$fKey] ?? ($field['default'] ?? '');
                            $fType = $field['type'] ?? 'text';
                        @endphp
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="font-size:12px;">{{ $field['label'] ?? $fKey }}</label>
                            @if($fType === 'select')
                                <select name="module_cfg[{{ $fKey }}]" class="form-control" style="font-size:12.5px;">
                                    @foreach(($field['options'] ?? []) as $optVal => $optLabel)
                                        <option value="{{ $optVal }}" @selected((string) $fVal === (string) $optVal)>{{ $optLabel }}</option>
                                    @endforeach
                                </select>
                            @elseif($fType === 'yesno')
                                <select name="module_cfg[{{ $fKey }}]" class="form-control" style="font-size:12.5px;">
                                    <option value="0" @selected((string) $fVal === '0')>{{ __('common.no') }}</option>
                                    <option value="1" @selected((string) $fVal !== '0')>{{ __('common.yes') }}</option>
                                </select>
                            @elseif($fType === 'password')
                                <input type="password" name="module_cfg[{{ $fKey }}]" value="" placeholder="{{ (string) $fVal !== '' ? '(Saved — leave blank to keep)' : '' }}" class="form-control" style="font-size:12.5px;" autocomplete="new-password">
                            @elseif($fType === 'number')
                                <input type="number" name="module_cfg[{{ $fKey }}]" value="{{ $fVal }}" class="form-control" style="font-size:12.5px;">
                            @else
                                <input type="text" name="module_cfg[{{ $fKey }}]" value="{{ $fVal }}" class="form-control" style="font-size:12.5px;">
                            @endif
                            @if(!empty($field['description']))
                                <small style="display:block;color:#777;margin-top:4px;font-size:11px;">{{ $field['description'] }}</small>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
@endif
