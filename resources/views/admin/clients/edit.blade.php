@extends('admin.layouts.app')
@section('title', __('admin.clients.edit_client') . ': ' . $client->full_name)
@section('content')
@include('admin.clients.partials.client-tabs', ['client' => $client, 'activeTab' => 'profile'])

<div class="tab-content client-tabs">
    <div class="tab-pane active">
        <div class="clientsummaryactions">
            <a href="{{ route('admin.clients.show', $client) }}">&larr; {{ __('common.actions.back') }}</a>
        </div>
        <div class="client-summary-name">
            #{{ $client->id }} - {{ $client->full_name }}
        </div>
        <div style="clear:both;margin-bottom:10px;"></div>

        @if($errors->any())
        <div style="padding:10px 15px;background:#f2dede;border:1px solid #ebccd1;border-radius:4px;color:#a94442;margin-bottom:14px;font-size:13px;">
            @foreach($errors->all() as $e)<div>&bull; {{ $e }}</div>@endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('admin.clients.update', $client) }}">
            @csrf @method('PUT')
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td width="15%" class="fieldlabel">{{ __('common.form.first_name') }} <span style="color:#d9534f;">*</span></td>
                    <td width="35%" class="fieldarea">
                        <input type="text" name="first_name" value="{{ old('first_name', $client->first_name) }}" required class="form-control input-250">
                    </td>
                    <td width="15%" class="fieldlabel">{{ __('common.form.address') }}</td>
                    <td width="35%" class="fieldarea">
                        <input type="text" name="address1" value="{{ old('address1', $client->address1) }}" class="form-control input-300">
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('common.form.last_name') }} <span style="color:#d9534f;">*</span></td>
                    <td class="fieldarea">
                        <input type="text" name="last_name" value="{{ old('last_name', $client->last_name) }}" required class="form-control input-250">
                    </td>
                    <td class="fieldlabel">{{ __('common.form.city') }}</td>
                    <td class="fieldarea">
                        <input type="text" name="city" value="{{ old('city', $client->city) }}" class="form-control input-250">
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('common.form.company') }}</td>
                    <td class="fieldarea">
                        <input type="text" name="company_name" value="{{ old('company_name', $client->company_name) }}" class="form-control input-250">
                    </td>
                    <td class="fieldlabel">{{ __('common.form.state') }}</td>
                    <td class="fieldarea">
                        <select id="state-select" class="form-control select-inline" style="display:none !important;">
                            <option value="">{{ __('common.none') }}</option>
                            @foreach(\App\Support\Countries::PL_STATES as $voivodeship)
                            <option value="{{ $voivodeship }}" {{ old('state', $client->state) === $voivodeship ? 'selected' : '' }}>{{ $voivodeship }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="state" id="state-input" value="{{ old('state', $client->state) }}" class="form-control input-250">
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('common.form.email') }} <span style="color:#d9534f;">*</span></td>
                    <td class="fieldarea">
                        <input type="email" name="email" value="{{ old('email', $client->email) }}" required class="form-control input-300">
                    </td>
                    <td class="fieldlabel">{{ __('common.form.postcode') }}</td>
                    <td class="fieldarea">
                        <input type="text" name="postcode" value="{{ old('postcode', $client->postcode) }}" class="form-control input-150">
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('common.form.billing_email') }}</td>
                    <td class="fieldarea">
                        <input type="email" name="billing_email" value="{{ old('billing_email', $client->billing_email) }}" class="form-control input-300">
                        <div style="color:#777;font-size:11px;margin-top:3px;">{{ __('admin.clients.billing_email_hint') }}</div>
                    </td>
                    <td class="fieldlabel">{{ __('common.form.country') }}</td>
                    <td class="fieldarea">
                        <select name="country" id="country" class="form-control select-inline">
                            @foreach(\App\Support\Countries::all() as $code => $name)
                            <option value="{{ $code }}" {{ old('country', $client->country) == $code ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('common.form.new_password') }}</td>
                    <td class="fieldarea">
                        <div style="display:flex;gap:4px;align-items:center;flex-wrap:wrap;">
                            <input type="password" name="password" id="pw-input" class="form-control input-200" style="flex:1;min-width:140px;" autocomplete="new-password" oninput="checkStrength(this.value)">
                            <button type="button" onclick="generatePassword()" class="btn btn-default btn-sm" style="white-space:nowrap;" title="{{ __('admin.clients.generate_password') }}">&#x1F512; {{ __('admin.clients.generate_password') }}</button>
                            <button type="button" onclick="copyPw('pw-input', this)" class="btn btn-default btn-sm" title="{{ __('common.actions.copy') }}">&#128203;</button>
                            <button type="button" onclick="togglePw('pw-input')" class="btn btn-default btn-sm" title="{{ __('common.actions.toggle') }}">&#128065;</button>
                        </div>
                        <div style="font-size:11px;color:#888;margin-top:2px;">({{ __('admin.clients.password_optional_edit') }})</div>
                        <div style="height:4px;border-radius:999px;background:#e5e5e5;overflow:hidden;margin-top:4px;"><div id="pwBar" style="height:100%;border-radius:999px;transition:width 0.3s,background 0.3s;width:0;background:#ef4444;"></div></div>
                        <div id="pwHint" style="font-size:11px;color:#999;margin-top:2px;"></div>
                    </td>
                    <td class="fieldlabel">{{ __('common.form.phone') }}</td>
                    <td class="fieldarea">
                        <div style="display:flex;gap:6px;align-items:center;">
                            <select name="phone_prefix" id="phone_prefix" class="form-control" style="width:110px !important;flex-shrink:0;">
                                @foreach(\App\Support\Countries::PHONE_PREFIXES as $code => $prefix)
                                <option value="{{ $prefix }}" {{ old('phone_prefix', $client->phone_prefix ?? \App\Support\Countries::phonePrefix($client->country)) == $prefix ? 'selected' : '' }}>{{ $code }} {{ $prefix }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="phone_number" value="{{ old('phone_number', $client->phone_number) }}" class="form-control" style="flex:1;min-width:0;">
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('common.form.password_confirm') }}</td>
                    <td class="fieldarea">
                        <input type="password" name="password_confirmation" id="pw-confirm" class="form-control input-250" autocomplete="new-password">
                    </td>
                    <td class="fieldlabel">{{ __('common.form.default_payment_method') }} <span style="color:#d9534f;">*</span></td>
                    <td class="fieldarea">
                        <select name="default_payment_method" class="form-control select-inline">
                            @foreach($paymentMethods as $pm)
                            <option value="{{ $pm }}" {{ old('default_payment_method', $client->default_payment_method) == $pm ? 'selected' : '' }}>{{ \payment_method_label($pm) }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.clients.billing_type') }}</td>
                    <td class="fieldarea">
                        @php $clientType = old('client_type', $client->client_type) ?: 'individual'; @endphp
                        <div style="display:flex;gap:16px;align-items:center;">
                            <label style="display:inline-flex;align-items:center;gap:6px;font-weight:400;cursor:pointer;margin:0;">
                                <input type="radio" name="client_type" value="individual" {{ $clientType === 'individual' ? 'checked' : '' }}> {{ __('admin.clients.billing_type_individual') }}
                            </label>
                            <label style="display:inline-flex;align-items:center;gap:6px;font-weight:400;cursor:pointer;margin:0;">
                                <input type="radio" name="client_type" value="company" {{ $clientType === 'company' ? 'checked' : '' }}> {{ __('admin.clients.billing_type_company') }}
                            </label>
                        </div>
                    </td>
                    <td class="fieldlabel">{{ __('common.form.group') }}</td>
                    <td class="fieldarea">
                        <select name="group_id" class="form-control select-inline">
                            <option value="">{{ __('common.none') }}</option>
                            @foreach($groups as $g)
                            <option value="{{ $g->id }}" @selected((string) old('group_id', $client->group_id) === (string) $g->id)>{{ $g->name }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('common.form.tax_id') }}</td>
                    <td class="fieldarea">
                        <div style="display:flex;gap:6px;align-items:center;">
                            <input type="text" name="tax_id" value="{{ old('tax_id', $client->tax_id) }}" maxlength="20" class="form-control" style="flex:1;min-width:0;">
                            <x-company-lookup />
                        </div>
                    </td>
                    @php $clientCurrencyId = (int) old('currency_id', $client->currency_id ?: $currencies->firstWhere('is_default', true)?->id); @endphp
                    <td class="fieldlabel">{{ __('common.form.currency') }}</td>
                    <td class="fieldarea">
                        <select name="currency_id" class="form-control select-inline">
                            @foreach($currencies as $c)
                            <option value="{{ $c->id }}" @selected($clientCurrencyId === (int) $c->id)>{{ $c->code }}@if($c->prefix || $c->suffix) ({{ trim($c->prefix.' '.$c->suffix) }})@endif</option>
                            @endforeach
                        </select>
                        <div style="font-size:11px;color:#777;margin-top:3px;">{{ \App\Support\CustomerCurrency::enabled() ? __('admin.currencies.client_currency_hint_on') : __('admin.currencies.client_currency_hint_off') }}</div>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.clients.billing_national_id') }}</td>
                    <td class="fieldarea">
                        <input type="text" name="national_id" value="{{ old('national_id', $client->national_id) }}" maxlength="20" class="form-control input-200">
                    </td>
                    <td class="fieldlabel">{{ __('common.form.language') }}</td>
                    <td class="fieldarea">
                        <select name="language" class="form-control select-inline">
                            @foreach($languages ?? [] as $lang)
                            <option value="{{ $lang->code }}" @selected(old('language', $client->language) === $lang->code)>{{ $lang->native_name ?? $lang->name }} ({{ $lang->code }})</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('common.form.status') }}</td>
                    <td class="fieldarea">
                        <select name="status" class="form-control select-inline">
                            <option value="active" {{ old('status', $client->status->value) == 'active' ? 'selected' : '' }}>{{ __('common.status.active') }}</option>
                            <option value="inactive" {{ old('status', $client->status->value) == 'inactive' ? 'selected' : '' }}>{{ __('common.status.inactive') }}</option>
                            <option value="closed" {{ old('status', $client->status->value) == 'closed' ? 'selected' : '' }}>{{ __('common.status.closed') }}</option>
                        </select>
                    </td>
                    <td class="fieldlabel">{{ __('admin.clients.tax_exempt') }}</td>
                    <td class="fieldarea">
                        <div style="display:flex;flex-direction:column;gap:4px;">
                            <label style="font-size:12px;display:inline-flex;align-items:center;gap:6px;cursor:pointer;margin:0;font-weight:400;">
                                <input type="hidden" name="tax_exempt" value="0">
                                <input type="checkbox" name="tax_exempt" value="1" @checked(old('tax_exempt', $client->tax_exempt))>
                                {{ __('admin.clients.tax_exempt') }}
                            </label>
                            <label style="font-size:12px;display:inline-flex;align-items:center;gap:6px;cursor:pointer;margin:0;font-weight:400;">
                                <input type="hidden" name="override_auto_suspend" value="0">
                                <input type="checkbox" name="override_auto_suspend" value="1" @checked(old('override_auto_suspend', $client->override_auto_suspend))>
                                {{ __('admin.clients.never_auto_suspend') }}
                            </label>
                        </div>
                    </td>
                </tr>
                <tr id="tax-office-row" style="{{ strtoupper((string) old('country', $client->country)) === 'TR' ? '' : 'display:none;' }}">
                    <td class="fieldlabel">{{ __('admin.clients.billing_tax_office') }}</td>
                    <td class="fieldarea" colspan="3">
                        <input type="text" name="tax_office" value="{{ old('tax_office', $client->tax_office) }}" maxlength="100" class="form-control input-300">
                    </td>
                </tr>
            </table>

            @if($customFields->isNotEmpty())
            <h2 style="font-size:15px;font-weight:600;color:#1a4d80;margin:16px 0 8px;">{{ __('admin.clients.custom_fields') }}</h2>
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                @foreach($customFields->chunk(2) as $pair)
                <tr>
                    @foreach($pair as $field)
                    @php($value = old("custom_fields.{$field->id}", $field->values->first()?->value))
                    <td width="15%" class="fieldlabel">{{ $field->field_name }}@if($field->required) <span style="color:#d9534f;">*</span>@endif</td>
                    <td width="35%" class="fieldarea" @if($pair->count() === 1) colspan="3" @endif>
                        @if($field->field_type === 'textarea')
                            <textarea name="custom_fields[{{ $field->id }}]" rows="3" class="form-control" @if($field->required) required @endif>{{ $value }}</textarea>
                        @elseif($field->field_type === 'select')
                            <select name="custom_fields[{{ $field->id }}]" class="form-control select-inline" @if($field->required) required @endif>
                                <option value="">{{ __('common.none') }}</option>
                                @foreach($field->options() as $opt)
                                <option value="{{ $opt }}" @if($value === $opt) selected @endif>{{ $opt }}</option>
                                @endforeach
                            </select>
                        @elseif($field->field_type === 'checkbox')
                            <label style="display:inline-flex;align-items:center;gap:6px;font-weight:400;margin:0;">
                                <input type="checkbox" name="custom_fields[{{ $field->id }}]" value="1" @if($value) checked @endif> {{ __('admin.custom_fields.checkbox_yes') }}
                            </label>
                        @elseif($field->field_type === 'number')
                            <input type="number" name="custom_fields[{{ $field->id }}]" value="{{ $value }}" class="form-control input-200" @if($field->required) required @endif>
                        @elseif($field->field_type === 'date')
                            <input type="date" name="custom_fields[{{ $field->id }}]" value="{{ $value }}" class="form-control input-200" @if($field->required) required @endif>
                        @else
                            <input type="text" name="custom_fields[{{ $field->id }}]" value="{{ $value }}" class="form-control input-250" @if($field->regex) pattern="{{ $field->regex }}" @endif @if($field->required) required @endif>
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </table>
            @endif

            <div class="btn-container">
                <button type="submit" class="btn btn-primary">{{ __('admin.clients.update_client') }}</button>
                <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-default">{{ __('common.actions.cancel') }}</a>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var map = {!! json_encode(\App\Support\Countries::PHONE_PREFIXES) !!};
    var country = document.getElementById('country');
    var prefix = document.getElementById('phone_prefix');
    var taxOffice = document.getElementById('tax-office-row');
    var stateSelect = document.getElementById('state-select');
    var stateInput = document.getElementById('state-input');

    function syncState() {
        if (!stateSelect || !stateInput) return;
        var code = (country.value || '').toUpperCase();
        if (code === 'PL') {
            var found = false;
            for (var i = 0; i < stateSelect.options.length; i++) {
                if (stateSelect.options[i].value === stateInput.value) { stateSelect.value = stateInput.value; found = true; break; }
            }
            if (!found) stateSelect.value = '';
            stateSelect.style.setProperty('display', 'block', 'important');
            stateInput.style.setProperty('display', 'none', 'important');
        } else {
            stateSelect.style.setProperty('display', 'none', 'important');
            stateInput.style.setProperty('display', 'block', 'important');
        }
    }

    function sync() {
        var code = (country.value || '').toUpperCase();
        if (map[code] && prefix) prefix.value = map[code];
        if (taxOffice) taxOffice.style.display = (code === 'TR') ? '' : 'none';
        syncState();
    }
    if (stateSelect) stateSelect.addEventListener('change', function () { stateInput.value = stateSelect.value; });
    if (country) country.addEventListener('change', sync);
    sync();
})();
</script>
<script>
function generatePassword() {
    var upper = "ABCDEFGHJKLMNPQRSTUVWXYZ";
    var lower = "abcdefghijkmnopqrstuvwxyz";
    var digits = "23456789";
    var symbols = "!@#$%^&*_-+=?";
    var all = upper + lower + digits + symbols;
    var out = [];
    out.push(upper[Math.floor(Math.random() * upper.length)]);
    out.push(lower[Math.floor(Math.random() * lower.length)]);
    out.push(digits[Math.floor(Math.random() * digits.length)]);
    out.push(symbols[Math.floor(Math.random() * symbols.length)]);
    for (var i = 0; i < 12; i++) {
        out.push(all[Math.floor(Math.random() * all.length)]);
    }
    out.sort(function () { return Math.random() - 0.5; });
    var pw = out.join("");
    var input = document.getElementById("pw-input"), confirmInput = document.getElementById("pw-confirm");
    if (input) { input.type = "text"; input.value = pw; input.focus(); }
    if (confirmInput) { confirmInput.type = "text"; confirmInput.value = pw; }
    checkStrength(pw);
}
function togglePw(id) {
    var el = document.getElementById(id);
    if (!el) return;
    el.type = el.type === "password" ? "text" : "password";
}
function copyPw(id, btn) {
    var el = document.getElementById(id);
    if (!el) return;
    var prev = el.type;
    el.type = "text";
    el.select();
    try { document.execCommand("copy"); } catch (e) {}
    el.type = prev;
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(el.value).catch(function () {});
    }
    if (btn) {
        var orig = btn.textContent;
        btn.textContent = "\u2713";
        setTimeout(function () { btn.textContent = orig; }, 1200);
    }
}
function checkStrength(v) {
    var bar = document.getElementById("pwBar"), hint = document.getElementById("pwHint");
    if (!bar || !hint) return;
    var score = 0;
    if (v.length >= 8) score++;
    if (v.length >= 12) score++;
    if (/[A-Z]/.test(v)) score++;
    if (/[0-9]/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;
    var levels = [
        {w:"0%",c:"#ef4444",t:"{{ __('client.password.too_short') }}"},
        {w:"20%",c:"#ef4444",t:"{{ __('client.password.very_weak') }}"},
        {w:"40%",c:"#f59e0b",t:"{{ __('client.password.weak') }}"},
        {w:"60%",c:"#f59e0b",t:"{{ __('client.password.fair') }}"},
        {w:"80%",c:"#06d6a0",t:"{{ __('client.password.strong') }}"},
        {w:"100%",c:"#10b981",t:"{{ __('client.password.very_strong') }}"}
    ];
    var l = levels[Math.min(score, 5)];
    bar.style.width = l.w; bar.style.background = l.c;
    hint.textContent = l.t; hint.style.color = l.c;
}
</script>
@endsection
