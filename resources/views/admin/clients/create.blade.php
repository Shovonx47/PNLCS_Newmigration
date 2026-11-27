@extends('admin.layouts.app')
@section('title', 'Add New Client')
@section('content')
<h1>Add New Client</h1>

<div class="context-btn-container" style="margin-bottom:10px;">
    <a href="{{ route('admin.clients.index') }}" class="btn btn-default btn-sm">{{ 'Back' }}</a>
</div>

@if($errors->any())
<div class="infobox" style="border-left:4px solid #c43c35;background:#f2dede;color:#a94442;margin-bottom:14px;">
    <ul style="margin:0;padding-left:18px;">
        @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
        @endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('admin.clients.store') }}">
    @csrf
    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
        <tr>
            <td width="15%" class="fieldlabel">{{ 'First Name' }} <span style="color:#d9534f;">*</span></td>
            <td width="35%" class="fieldarea">
                <input type="text" name="first_name" value="{{ old('first_name') }}" required class="form-control input-250">
            </td>
            <td width="15%" class="fieldlabel">{{ 'Address 1' }}</td>
            <td width="35%" class="fieldarea">
                <input type="text" name="address1" value="{{ old('address1') }}" class="form-control input-300">
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ 'Last Name' }} <span style="color:#d9534f;">*</span></td>
            <td class="fieldarea">
                <input type="text" name="last_name" value="{{ old('last_name') }}" required class="form-control input-250">
            </td>
            <td class="fieldlabel">{{ 'Address 2' }} <span style="color:#999;font-weight:400;">({{ 'Optional' }})</span></td>
            <td class="fieldarea">
                <input type="text" name="address2" value="{{ old('address2') }}" class="form-control input-300" placeholder="{{ 'Optional' }}">
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ 'Company Name' }} <span style="color:#999;font-weight:400;">({{ 'Optional' }})</span></td>
            <td class="fieldarea">
                <input type="text" name="company_name" value="{{ old('company_name') }}" class="form-control input-250" placeholder="{{ 'Optional' }}">
            </td>
            <td class="fieldlabel">{{ 'City' }}</td>
            <td class="fieldarea">
                <input type="text" name="city" value="{{ old('city') }}" class="form-control input-250">
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ 'Email Address' }} <span style="color:#d9534f;">*</span></td>
            <td class="fieldarea">
                <input type="email" name="email" value="{{ old('email') }}" required class="form-control input-300">
            </td>
            <td class="fieldlabel">{{ 'State/Region' }}</td>
            <td class="fieldarea">
                <select id="state-select" class="form-control select-inline" style="display:none !important;">
                    <option value="">{{ 'None' }}</option>
                    @foreach(\App\Support\Countries::PL_STATES as $voivodeship)
                    <option value="{{ $voivodeship }}" {{ old('state') === $voivodeship ? 'selected' : '' }}>{{ $voivodeship }}</option>
                    @endforeach
                </select>
                <input type="text" name="state" id="state-input" value="{{ old('state') }}" class="form-control input-250">
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ 'Password' }}</td>
            <td class="fieldarea">
                <div style="display:flex;gap:4px;align-items:center;">
                    <input type="password" name="password" id="pw-input" class="form-control" style="flex:1;min-width:120px;" autocomplete="new-password">
                    <button type="button" onclick="generatePassword()" class="btn btn-default btn-sm" style="white-space:nowrap;">{{ 'Generate Password' }}</button>
                </div>
            </td>
            <td class="fieldlabel">{{ 'Postcode' }}</td>
            <td class="fieldarea">
                <input type="text" name="postcode" value="{{ old('postcode') }}" class="form-control input-150">
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ 'Confirm Password' }}</td>
            <td class="fieldarea">
                <input type="password" name="password_confirmation" id="pw-confirm" class="form-control input-250" autocomplete="new-password">
            </td>
            <td class="fieldlabel">{{ 'Country' }}</td>
            <td class="fieldarea">
                <select name="country" id="country" class="form-control select-inline">
                    @foreach(\App\Support\Countries::all() as $code => $name)
                        <option value="{{ $code }}" {{ old('country', $defaultCountry ?? '') == $code ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ 'Language' }}</td>
            <td class="fieldarea">
                <select name="language" class="form-control select-inline">
                    @foreach($languages ?? [] as $lang)
                        <option value="{{ $lang->code }}" {{ old('language', $defaultLanguage ?? '') == $lang->code ? 'selected' : '' }}>{{ $lang->native_name ?? $lang->name }} ({{ $lang->code }})</option>
                    @endforeach
                </select>
            </td>
            <td class="fieldlabel">{{ 'Phone Number' }}</td>
            <td class="fieldarea">
                <div style="display:flex;gap:6px;align-items:center;">
                    <select name="phone_prefix" id="phone_prefix" class="form-control" style="width:110px !important;flex-shrink:0;">
                        @foreach(\App\Support\Countries::PHONE_PREFIXES as $code => $prefix)
                        <option value="{{ $prefix }}" {{ old('phone_prefix', $defaultPhonePrefix ?? '') == $prefix ? 'selected' : '' }}>{{ $code }} {{ $prefix }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="phone_number" value="{{ old('phone_number') }}" class="form-control" style="flex:1;min-width:0;">
                </div>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ 'Status' }}</td>
            <td class="fieldarea">
                <select name="status" class="form-control select-inline">
                    <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>{{ 'Active' }}</option>
                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>{{ 'Inactive' }}</option>
                    <option value="closed" {{ old('status') == 'closed' ? 'selected' : '' }}>{{ 'Closed' }}</option>
                </select>
            </td>
            <td class="fieldlabel">{{ 'Payment Method' }}</td>
            <td class="fieldarea">
                <select name="default_payment_method" class="form-control select-inline">
                    @foreach($paymentMethods as $pm)
                    <option value="{{ $pm }}" {{ old('default_payment_method', $defaultPaymentMethod ?? '') == $pm ? 'selected' : '' }}>{{ \payment_method_label($pm) }}</option>
                    @endforeach
                </select>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ 'Client Group' }}</td>
            <td class="fieldarea">
                <select name="group_id" class="form-control select-inline">
                    <option value="">{{ 'None' }}</option>
                    @foreach($groups as $g)
                    <option value="{{ $g->id }}" @selected((string) old('group_id') === (string) $g->id)>{{ $g->name }}</option>
                    @endforeach
                </select>
            </td>
            <td class="fieldlabel">{{ 'Currency' }}</td>
            <td class="fieldarea">
                <select name="currency_id" class="form-control select-inline">
                    @foreach($currencies as $c)
                    <option value="{{ $c->id }}" {{ old('currency_id') ? (old('currency_id') == $c->id ? 'selected' : '') : ($c->is_default ? 'selected' : '') }}>{{ $c->code }} ({{ $c->prefix }})</option>
                    @endforeach
                </select>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ 'Billing Email (Accounting)' }}</td>
            <td class="fieldarea" colspan="3">
                <input type="email" name="billing_email" value="{{ old('billing_email') }}" class="form-control input-300">
                <div style="color:#777;font-size:11px;margin-top:3px;">{{ 'Optional address for invoices and payment reminders. When empty, the account email is used.' }}</div>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ 'Tax ID / VAT Number' }}</td>
            <td class="fieldarea">
                <div style="display:flex;gap:6px;align-items:center;">
                    <input type="text" name="tax_id" value="{{ old('tax_id') }}" maxlength="20" class="form-control" style="flex:1;min-width:0;">
                    <x-company-lookup />
                </div>
            </td>
            <td class="fieldlabel">{{ 'NID Number' }}</td>
            <td class="fieldarea">
                <input type="text" name="national_id" value="{{ old('national_id') }}" maxlength="20" class="form-control input-200">
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ 'Customer type' }}</td>
            <td class="fieldarea">
                @php $clientType = old('client_type') ?: 'individual'; @endphp
                <div style="display:flex;gap:16px;align-items:center;">
                    <label style="display:inline-flex;align-items:center;gap:6px;font-weight:400;cursor:pointer;margin:0;">
                        <input type="radio" name="client_type" value="individual" {{ $clientType === 'individual' ? 'checked' : '' }}> {{ 'Individual' }}
                    </label>
                    <label style="display:inline-flex;align-items:center;gap:6px;font-weight:400;cursor:pointer;margin:0;">
                        <input type="radio" name="client_type" value="company" {{ $clientType === 'company' ? 'checked' : '' }}> {{ 'Company' }}
                    </label>
                </div>
            </td>
            <td class="fieldlabel">{{ 'Identity Document Type' }}</td>
            <td class="fieldarea">
                <div class="form-control-static" style="padding-top:6px;">{{ 'National ID' }}</div>
            </td>
        </tr>
        <tr id="tax-office-row" style="{{ strtoupper((string) old('country')) === 'TR' ? '' : 'display:none;' }}">
            <td class="fieldlabel">{{ 'Tax Office' }}</td>
            <td class="fieldarea" colspan="3">
                <input type="text" name="tax_office" value="{{ old('tax_office') }}" maxlength="100" class="form-control input-300">
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ 'Settings' }}</td>
            <td class="fieldarea" colspan="3">
                <div style="display:flex;gap:26px;flex-wrap:wrap;align-items:center;">
                    <label style="display:inline-flex;align-items:center;gap:7px;font-weight:400;cursor:pointer;margin:0;">
                        <input type="hidden" name="tax_exempt" value="0">
                        <input type="checkbox" name="tax_exempt" value="1" {{ old('tax_exempt') ? 'checked' : '' }}> {{ 'Tax Exempt' }}
                    </label>
                    <label style="display:inline-flex;align-items:center;gap:7px;font-weight:400;cursor:pointer;margin:0;">
                        <input type="hidden" name="auto_charge" value="0">
                        <input type="checkbox" name="auto_charge" value="1" {{ old('auto_charge', true) ? 'checked' : '' }}> {{ 'Card on File Charging' }}
                    </label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ 'Admin Notes' }}</td>
            <td class="fieldarea" colspan="3">
                <textarea name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
            </td>
        </tr>
    </table>

    @if($customFields->isNotEmpty())
    <h2 style="font-size:15px;font-weight:600;color:#1a4d80;margin:16px 0 8px;">{{ 'Additional Information' }}</h2>
    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
        @foreach($customFields->chunk(2) as $pair)
        <tr>
            @foreach($pair as $field)
            <td width="15%" class="fieldlabel">{{ $field->field_name }}@if($field->required) <span style="color:#d9534f;">*</span>@endif</td>
            <td width="35%" class="fieldarea" @if($pair->count() === 1) colspan="3" @endif>
                @if($field->field_type === 'textarea')
                    <textarea name="custom_fields[{{ $field->id }}]" rows="3" class="form-control" @if($field->required) required @endif>{{ old("custom_fields.{$field->id}") }}</textarea>
                @elseif($field->field_type === 'select')
                    <select name="custom_fields[{{ $field->id }}]" class="form-control select-inline" @if($field->required) required @endif>
                        <option value="">{{ 'None' }}</option>
                        @foreach($field->options() as $opt)
                        <option value="{{ $opt }}" @if(old("custom_fields.{$field->id}") === $opt) selected @endif>{{ $opt }}</option>
                        @endforeach
                    </select>
                @elseif($field->field_type === 'checkbox')
                    <label style="display:inline-flex;align-items:center;gap:6px;font-weight:400;margin:0;">
                        <input type="checkbox" name="custom_fields[{{ $field->id }}]" value="1" @if(old("custom_fields.{$field->id}")) checked @endif> {{ 'Yes' }}
                    </label>
                @elseif($field->field_type === 'number')
                    <input type="number" name="custom_fields[{{ $field->id }}]" value="{{ old("custom_fields.{$field->id}") }}" class="form-control input-200" @if($field->required) required @endif>
                @elseif($field->field_type === 'date')
                    <input type="date" name="custom_fields[{{ $field->id }}]" value="{{ old("custom_fields.{$field->id}") }}" class="form-control input-200" @if($field->required) required @endif>
                @else
                    <input type="text" name="custom_fields[{{ $field->id }}]" value="{{ old("custom_fields.{$field->id}") }}" class="form-control input-250" @if($field->regex) pattern="{{ $field->regex }}" @endif @if($field->required) required @endif>
                @endif
            </td>
            @endforeach
        </tr>
        @endforeach
    </table>
    @endif

    <div class="btn-container">
        <button type="submit" class="btn btn-primary">{{ 'Add Client' }}</button>
        <a href="{{ route('admin.clients.index') }}" class="btn btn-default">{{ 'Cancel' }}</a>
    </div>
</form>

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
}
</script>
@endsection
