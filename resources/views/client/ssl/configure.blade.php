@extends('client.layouts.twentyone')
@section('title', __('client.configure_ssl_certificate'))
@section('content')

<div class="mb-3">
    <a href="{{ route('client.ssl.show', $order) }}" class="btn btn-sm btn-default">&larr; {{ __('client.actions.back') }}</a>
</div>

<h2 class="h4 mb-4">{{ __('client.ssl.configure_ssl') }}</h2>

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 pl-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('client.ssl.submitConfiguration', $order) }}" x-data="sslConfigForm()">
    @csrf

    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title h5 mb-0">{{ __('client.ssl.csr_title') }}</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label>{{ __('client.ssl.csr') }} <span class="text-danger">*</span></label>
                <textarea name="csr" class="form-control text-monospace" rows="8" required
                    placeholder="{{ "-----BEGIN CERTIFICATE REQUEST-----\n" . __('client.ssl.csr_paste_here') . "\n-----END CERTIFICATE REQUEST-----" }}"
                    x-model="csr" @blur="decodeCsr()">{{ old('csr') }}</textarea>
                <small class="form-text text-muted">{{ __('client.ssl.csr_hint') }}</small>
            </div>

            <div x-show="csrDecoded" x-cloak class="alert alert-info mb-0">
                <strong>{{ __('client.ssl.csr_decoded') }}:</strong>
                <div class="row mt-2">
                    <div class="col-md-6"><small>{{ __('client.ssl.common_name') }}:</small> <strong x-text="csrInfo.cn"></strong></div>
                    <div class="col-md-6"><small>{{ __('client.ssl.organization') }}:</small> <span x-text="csrInfo.org"></span></div>
                    <div class="col-md-6"><small>{{ __('client.ssl.country') }}:</small> <span x-text="csrInfo.country"></span></div>
                    <div class="col-md-6"><small>{{ __('client.ssl.key_size') }}:</small> <span x-text="csrInfo.key_size"></span></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title h5 mb-0">{{ __('client.ssl.server_validation') }}</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ __('client.ssl.web_server_type') }} <span class="text-danger">*</span></label>
                        <select name="webserver_type" class="form-control custom-select" required>
                            <option value="">{{ __('client.ssl.select_server_type') }}</option>
                            @foreach($webServerTypes as $id => $name)
                                <option value="{{ $id }}" {{ old('webserver_type') == $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ __('client.ssl.domain_validation_method') }} <span class="text-danger">*</span></label>
                        <select name="validation_method" class="form-control custom-select" required x-model="validationMethod" @change="onValidationChange()">
                            <option value="EMAIL">{{ __('client.ssl.email_validation') }}</option>
                            <option value="HTTP">{{ __('client.ssl.http_validation') }}</option>
                            <option value="DNS">{{ __('client.ssl.dns_validation') }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <div x-show="validationMethod === 'EMAIL'" class="form-group">
                <label>{{ __('client.ssl.approver_email') }} <span class="text-danger">*</span></label>
                <select name="approver_email" class="form-control custom-select" x-model="approverEmail">
                    <option value="">{{ __('client.ssl.select_approver_email') }}</option>
                    @foreach($approverEmails ?? [] as $email)
                        <option value="{{ $email }}" @selected(old('approver_email') === $email)>{{ $email }}</option>
                    @endforeach
                </select>
                <small class="form-text text-muted">{{ __('client.ssl.approver_email_hint') }}</small>
            </div>

            <div class="form-group mb-0">
                <label>{{ __('client.ssl.additional_san') }}</label>
                <textarea name="domains" class="form-control" rows="3" placeholder="{{ __('client.ssl.san_placeholder') }}">{{ old('domains') }}</textarea>
                <small class="form-text text-muted">{{ __('client.ssl.san_hint') }}</small>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title h5 mb-0">{{ __('client.ssl.admin_contact') }}</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ __('common.form.first_name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="admin_first_name" class="form-control" value="{{ old('admin_first_name', auth()->user()->first_name ?? '') }}" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ __('common.form.last_name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="admin_last_name" class="form-control" value="{{ old('admin_last_name', auth()->user()->last_name ?? '') }}" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ __('common.form.email') }} <span class="text-danger">*</span></label>
                        <input type="email" name="admin_email" class="form-control" value="{{ old('admin_email', auth()->user()->email ?? '') }}" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ __('client.form.phone') }}</label>
                        <input type="text" name="admin_phone" class="form-control" value="{{ old('admin_phone', auth()->user()->phone_number ?? '') }}">
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>{{ __('client.ssl.organization') }}</label>
                        <input type="text" name="admin_org" class="form-control" value="{{ old('admin_org', auth()->user()->company_name ?? '') }}">
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>{{ __('common.form.address') }}</label>
                        <input type="text" name="admin_address" class="form-control" value="{{ old('admin_address', auth()->user()->address1 ?? '') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ __('common.form.city') }}</label>
                        <input type="text" name="admin_city" class="form-control" value="{{ old('admin_city', auth()->user()->city ?? '') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ __('client.ssl.state_province') }}</label>
                        <input type="text" name="admin_state" class="form-control" value="{{ old('admin_state', auth()->user()->state ?? '') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ __('client.ssl.zip_code') }}</label>
                        <input type="text" name="admin_zip" class="form-control" value="{{ old('admin_zip', auth()->user()->postcode ?? '') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>{{ __('client.ssl.country') }}</label>
                        <input type="text" name="admin_country" class="form-control" maxlength="2" placeholder="{{ 'US' }}" value="{{ old('admin_country', auth()->user()->country ?? '') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="text-right">
        <button type="submit" class="btn btn-primary btn-lg">{{ __('client.ssl.submit_configuration') }}</button>
    </div>
</form>

@endsection

@push('scripts')
<script>
function sslConfigForm() {
    return {
        csr: @json(old('csr', '')),
        csrDecoded: false,
        csrInfo: { cn: '', org: '', country: '', key_size: '' },
        validationMethod: @json(old('validation_method', 'EMAIL')),
        approverEmail: @json(old('approver_email', '')),
        approverEmails: [],
        async decodeCsr() {
            if (!this.csr || this.csr.length < 100) return;
            try {
                const lines = this.csr.trim().split('\n');
                if (lines.length > 2) {
                    this.csrDecoded = true;
                    this.csrInfo.cn = 'Parsing...';
                    this.loadApproverEmails();
                }
            } catch (e) { console.error(e); }
        },
        async loadApproverEmails() {
            const domain = this.csrInfo.cn || @json($order->domain ?? '');
            if (!domain || domain === 'Parsing...') {
                const fallbackDomain = @json($order->domain ?? '');
                if (!fallbackDomain) return;
            }
            try {
                const response = await fetch(@json(route('client.ssl.approverEmails', $order)) + '?domain=' + encodeURIComponent(domain || @json($order->domain ?? '')));
                const data = await response.json();
                if (data.emails && data.emails.length) {
                    this.approverEmails = data.emails;
                    if (!this.approverEmail) this.approverEmail = data.emails[0];
                }
            } catch (e) { console.error(e); }
        },
        onValidationChange() {
            if (this.validationMethod === 'EMAIL' && this.approverEmails.length === 0) {
                this.loadApproverEmails();
            }
        },
        init() {
            this.loadApproverEmails();
        }
    }
}
</script>
@endpush
