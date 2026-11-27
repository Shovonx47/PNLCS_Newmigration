@extends('admin.layouts.app')
@section('title', __('admin.settings.title'))
@section('content')

<h1>{{ __('admin.settings.general_settings') }}</h1>

<form method="POST" action="{{ route('admin.settings.general.update') }}" id="general-settings-form">
    @csrf
    @if($errors->any())
        <div class="alert alert-danger"><ul style="margin:0;padding-left:18px;">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <style>
        .tab-content.admin-tabs > .tab-pane[hidden] { display: none !important; }
    </style>

    <ul class="nav nav-tabs admin-tabs" id="general-settings-tabs" role="tablist">
        <li class="nav-item active"><a href="#company" role="tab" class="nav-link active settings-tab" data-tab="company">{{ __('General') }}</a></li>
        <li class="nav-item"><a href="#localisation" role="tab" class="nav-link settings-tab" data-tab="localisation">{{ __('Localisation') }}</a></li>
        <li class="nav-item"><a href="#ordering" role="tab" class="nav-link settings-tab" data-tab="ordering">{{ __('Ordering') }}</a></li>
        <li class="nav-item"><a href="#domains" role="tab" class="nav-link settings-tab" data-tab="domains">{{ __('Domains') }}</a></li>
        <li class="nav-item"><a href="#mail" role="tab" class="nav-link settings-tab" data-tab="mail">{{ __('Mail') }}</a></li>
        <li class="nav-item"><a href="#support" role="tab" class="nav-link settings-tab" data-tab="support">{{ __('Support') }}</a></li>
        <li class="nav-item"><a href="#invoicing" role="tab" class="nav-link settings-tab" data-tab="invoicing">{{ __('Invoices') }}</a></li>
        <li class="nav-item"><a href="#credit-tax" role="tab" class="nav-link settings-tab" data-tab="credit-tax">{{ __('Credit / Tax') }}</a></li>
        <li class="nav-item"><a href="#security" role="tab" class="nav-link settings-tab" data-tab="security">{{ __('Social / Security') }}</a></li>
    </ul>

    <div class="tab-content admin-tabs">
        {{-- TAB 1: GENERAL --}}
        <div class="tab-pane active" data-tab="company" id="tab-company">
            <div id="settings-company_information">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="22%">{{ __('common.form.company_name') }}</td>
                        <td class="fieldarea"><input type="text" name="CompanyName" value="{{ $settings['CompanyName'] ?? '' }}" class="form-control" style="max-width:420px;"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.company_email') }}</td>
                        <td class="fieldarea"><input type="email" name="Email" value="{{ $settings['Email'] ?? '' }}" class="form-control" style="max-width:420px;"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.domain') }}</td>
                        <td class="fieldarea"><input type="text" name="Domain" value="{{ $settings['Domain'] ?? '' }}" class="form-control" style="max-width:420px;" placeholder="https://panel.example.com"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.company_website') }}</td>
                        <td class="fieldarea"><input type="text" name="CompanyWebsite" value="{{ $settings['CompanyWebsite'] ?? '' }}" class="form-control" style="max-width:420px;" placeholder="https://example.com"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.company_address') }}</td>
                        <td class="fieldarea"><textarea name="Address" rows="3" class="form-control" style="max-width:420px;">{{ $settings['Address'] ?? '' }}</textarea></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('common.form.city') }} / {{ __('admin.settings.state') }} / {{ __('admin.settings.postcode') }}</td>
                        <td class="fieldarea">
                            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;max-width:560px;">
                                <input type="text" name="CompanyCity" value="{{ $settings['CompanyCity'] ?? '' }}" class="form-control" placeholder="{{ __('common.form.city') }}">
                                <input type="text" name="State" value="{{ $settings['State'] ?? '' }}" class="form-control" placeholder="{{ __('admin.settings.state') }}">
                                <input type="text" name="Postcode" value="{{ $settings['Postcode'] ?? '' }}" class="form-control" placeholder="{{ __('admin.settings.postcode') }}">
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('common.form.phone_number') }}</td>
                        <td class="fieldarea"><input type="text" name="PhoneNumber" value="{{ $settings['PhoneNumber'] ?? '' }}" class="form-control" style="max-width:300px;"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('common.form.tax_id') }}</td>
                        <td class="fieldarea"><input type="text" name="TaxID" value="{{ $settings['TaxID'] ?? '' }}" class="form-control" style="max-width:300px;"></td>
                    </tr>
                </table>
            </div>

            <div id="settings-legal" style="margin-top:14px;">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="22%">{{ __('admin.settings.tos_url') }}</td>
                        <td class="fieldarea">
                            <input type="url" name="TOSUrl" value="{{ $settings['TOSUrl'] ?? '' }}" class="form-control" style="max-width:460px;" placeholder="https://example.com/terms">
                            <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.settings.legal_hint') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.privacy_url') }}</td>
                        <td class="fieldarea"><input type="url" name="PrivacyUrl" value="{{ $settings['PrivacyUrl'] ?? '' }}" class="form-control" style="max-width:460px;" placeholder="https://example.com/privacy"></td>
                    </tr>
                </table>
            </div>

            <div id="settings-legal_identity" style="margin-top:14px;">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="22%">{{ __('admin.settings.company_legal_name') }}</td>
                        <td class="fieldarea">
                            <input type="text" name="CompanyLegalName" value="{{ $settings['CompanyLegalName'] ?? '' }}" class="form-control" style="max-width:420px;">
                            <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.settings.company_legal_name_hint') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.tax_office') }}</td>
                        <td class="fieldarea"><input type="text" name="TaxOffice" value="{{ $settings['TaxOffice'] ?? '' }}" class="form-control" style="max-width:360px;"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.trade_registry_no') }}</td>
                        <td class="fieldarea"><input type="text" name="TradeRegistryNo" value="{{ $settings['TradeRegistryNo'] ?? '' }}" class="form-control" style="max-width:360px;"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.mersis_no') }}</td>
                        <td class="fieldarea"><input type="text" name="MersisNo" value="{{ $settings['MersisNo'] ?? '' }}" class="form-control" style="max-width:360px;"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.abuse_email') }}</td>
                        <td class="fieldarea"><input type="email" name="AbuseEmail" value="{{ $settings['AbuseEmail'] ?? '' }}" class="form-control" style="max-width:360px;"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.dpo_email') }}</td>
                        <td class="fieldarea"><input type="email" name="DpoEmail" value="{{ $settings['DpoEmail'] ?? '' }}" class="form-control" style="max-width:360px;"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.about_text') }}</td>
                        <td class="fieldarea">
                            <textarea name="AboutText" rows="4" class="form-control">{{ $settings['AboutText'] ?? '' }}</textarea>
                            <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.settings.about_text_hint') }} <a href="{{ route('pages.about') }}" target="_blank" rel="noopener">{{ route('pages.about') }}</a></div>
                        </td>
                    </tr>
                </table>
            </div>

            <div id="settings-system_settings" style="margin-top:14px;">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="22%">{{ __('admin.settings.system_settings') }}</td>
                        <td class="fieldarea">
                            <label style="font-weight:400;margin:0;cursor:pointer;"><input type="hidden" name="MaintenanceMode" value="0"><input type="checkbox" name="MaintenanceMode" value="1" {{ !empty($settings['MaintenanceMode']) ? 'checked' : '' }}> {{ __('admin.settings.maintenance_mode_label') }}</label>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- TAB 2: LOCALISATION --}}
        <div class="tab-pane" data-tab="localisation" id="tab-localisation" hidden>
            <div id="settings-localization">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="22%">{{ __('admin.settings.default_language') }}</td>
                        <td class="fieldarea">
                            <select name="DefaultLanguage" class="form-control" style="max-width:320px;">
                                @foreach($languages as $lang)
                                    <option value="{{ $lang->code }}" {{ ($settings['DefaultLanguage'] ?? '') === $lang->code ? 'selected' : '' }}>{{ $lang->native_name ?: $lang->name }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.default_country') }}</td>
                        <td class="fieldarea">
                            <select name="Country" class="form-control" style="max-width:320px;">
                                <option value="">&mdash;</option>
                                @foreach($countries as $code => $name)
                                    <option value="{{ $code }}" {{ ($settings['Country'] ?? '') === $code ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.date_format') }}</td>
                        <td class="fieldarea"><input type="text" name="DateFormat" value="{{ $settings['DateFormat'] ?? 'd/m/Y' }}" class="form-control" style="max-width:220px;" placeholder="d/m/Y"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.timezone') }}</td>
                        <td class="fieldarea"><input type="text" name="Timezone" value="{{ $settings['Timezone'] ?? 'UTC' }}" class="form-control" style="max-width:260px;"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.turkish_legal_forms') }}</td>
                        <td class="fieldarea">
                            <label style="font-weight:400;margin:0;cursor:pointer;">
                                <input type="hidden" name="TurkishLegalForms" value="0"><input type="checkbox" name="TurkishLegalForms" value="1" {{ !empty($settings['TurkishLegalForms']) ? 'checked' : '' }}>
                                {{ __('admin.settings.turkish_legal_forms') }}
                            </label>
                            <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.settings.turkish_legal_forms_hint') }}</div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- TAB 3: ORDERING --}}
        <div class="tab-pane" data-tab="ordering" id="tab-ordering" hidden>
            <div id="settings-automation">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="22%">{{ __('admin.settings.auto_approve_emails') }}</td>
                        <td class="fieldarea">
                            <input type="text" name="AutoApproveOrderEmails" value="{{ $settings['AutoApproveOrderEmails'] ?? '' }}" class="form-control" placeholder="tester@example.com, qa@example.com">
                            <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.settings.auto_approve_emails_hint') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.auto_suspension_days') }}</td>
                        <td class="fieldarea"><input type="number" min="0" name="AutoSuspensionDays" value="{{ $settings['AutoSuspensionDays'] ?? '3' }}" class="form-control" style="max-width:160px;"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.auto_termination_days') }}</td>
                        <td class="fieldarea"><input type="number" min="1" name="AutoTerminationDays" value="{{ $settings['AutoTerminationDays'] ?? '30' }}" class="form-control" style="max-width:160px;"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.automation') }}</td>
                        <td class="fieldarea">
                            <label style="font-weight:400;display:block;margin-bottom:6px;cursor:pointer;">
                                <input type="hidden" name="AutoSuspendInvoicedOnly" value="0"><input type="checkbox" name="AutoSuspendInvoicedOnly" value="1" {{ !empty($settings['AutoSuspendInvoicedOnly']) ? 'checked' : '' }}>
                                {{ __('admin.settings.auto_suspend_invoiced_only') }}
                            </label>
                            <label style="font-weight:400;display:block;margin:0;cursor:pointer;">
                                <input type="hidden" name="AutoTerminationEnabled" value="0"><input type="checkbox" name="AutoTerminationEnabled" value="1" {{ !empty($settings['AutoTerminationEnabled']) ? 'checked' : '' }}>
                                {{ __('admin.settings.auto_termination_label') }}
                            </label>
                            <div style="font-size:12px;color:#777;margin-top:6px;">{{ __('admin.settings.automation_hint') }}</div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- TAB 4: DOMAINS --}}
        <div class="tab-pane" data-tab="domains" id="tab-domains" hidden>
            <div id="settings-domains_section">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="22%">{{ __('admin.settings.domain_search_log') }}</td>
                        <td class="fieldarea">
                            <label style="font-weight:400;margin:0;cursor:pointer;">
                                <input type="hidden" name="DomainSearchLog" value="0"><input type="checkbox" name="DomainSearchLog" value="1" {{ ($settings['DomainSearchLog'] ?? '1') !== '0' ? 'checked' : '' }}>
                                {{ __('admin.settings.domain_search_log') }}
                            </label>
                            <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.settings.domain_search_log_hint') }}</div>
                        </td>
                    </tr>
                    @for($i = 1; $i <= 5; $i++)
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.default_nameservers') }} {{ 'NS'.$i }}</td>
                        <td class="fieldarea">
                            <input type="text" name="DefaultNameserver{{ $i }}" value="{{ $settings['DefaultNameserver'.$i] ?? '' }}" class="form-control" style="max-width:360px;" placeholder="ns{{ $i }}.yourdomain.com">
                            @if($i === 5)<div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.settings.default_nameservers_hint') }}</div>@endif
                        </td>
                    </tr>
                    @endfor
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.free_subdomains') }}</td>
                        <td class="fieldarea">
                            <textarea name="FreeSubdomains" rows="3" class="form-control" style="font-family:monospace;font-size:12px;">{{ $settings['FreeSubdomains'] ?? '' }}</textarea>
                            <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.settings.free_subdomains_hint') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.disallowed_subdomains') }}</td>
                        <td class="fieldarea">
                            <textarea name="DisallowedSubdomainPrefixes" rows="3" class="form-control" style="font-family:monospace;font-size:12px;">{{ $settings['DisallowedSubdomainPrefixes'] ?? '' }}</textarea>
                            <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.settings.disallowed_subdomains_hint', ['names' => implode(', ', \App\Support\FreeSubdomains::DEFAULT_DISALLOWED)]) }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel"><label for="domain-sync-rdap-max" style="margin:0;">{{ __('admin.settings.domain_sync_rdap_max') }}</label></td>
                        <td class="fieldarea">
                            <input type="number" id="domain-sync-rdap-max" name="DomainSyncRdapMaxPerRun" value="{{ $settings['DomainSyncRdapMaxPerRun'] ?? '100' }}" class="form-control" min="0" max="10000" style="max-width:180px;">
                            <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.settings.domain_sync_rdap_max_hint') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel"><label for="domain-sync-batch" style="margin:0;">{{ __('admin.settings.domain_sync_batch') }}</label></td>
                        <td class="fieldarea">
                            <input type="number" id="domain-sync-batch" name="DomainSyncBatchSize" value="{{ $settings['DomainSyncBatchSize'] ?? '100' }}" class="form-control" min="1" max="1000" style="max-width:180px;">
                            <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.settings.domain_sync_batch_hint') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel"><label for="domain-sync-delay" style="margin:0;">{{ __('admin.settings.domain_sync_delay_ms') }}</label></td>
                        <td class="fieldarea">
                            <input type="number" id="domain-sync-delay" name="DomainSyncDelayMs" value="{{ $settings['DomainSyncDelayMs'] ?? '500' }}" class="form-control" min="0" max="10000" style="max-width:180px;">
                            <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.settings.domain_sync_delay_ms_hint') }}</div>
                        </td>
                    </tr>
                </table>
            </div>

            <div id="settings-domain_suggestions" style="margin-top:14px;">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="22%">{{ __('admin.settings.domain_suggestion_tlds') }}</td>
                        <td class="fieldarea">
                            <input type="text" name="DomainSuggestionTlds" value="{{ $settings['DomainSuggestionTlds'] ?? '' }}" class="form-control" placeholder="{{ \App\Http\Controllers\DomainSearchController::DEFAULT_SUGGESTIONS }}">
                            <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.settings.domain_suggestions_hint') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.domain_suggestion_count') }}</td>
                        <td class="fieldarea"><input type="number" min="1" max="12" name="DomainSuggestionCount" value="{{ $settings['DomainSuggestionCount'] ?? '' }}" class="form-control" style="max-width:140px;" placeholder="6"></td>
                    </tr>
                </table>
            </div>

            <div id="settings-registrar_balance" style="margin-top:14px;">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="22%">{{ __('admin.settings.registrar_watch_module') }}</td>
                        <td class="fieldarea">
                            @php
                                $watchNow = collect($settings)->has('BalanceWatchRegistrar') ? (string) ($settings['BalanceWatchRegistrar'] ?? '') : 'domainnameapi';
                                $watchList = $balanceRegistrars ?? [];
                                if ($watchNow !== '' && ! in_array($watchNow, $watchList, true)) {
                                    $watchList[] = $watchNow;
                                }
                            @endphp
                            <select name="BalanceWatchRegistrar" class="form-control" style="max-width:280px;">
                                <option value="">{{ __('admin.settings.registrar_watch_off') }}</option>
                                @foreach($watchList as $watchName)
                                    <option value="{{ $watchName }}" @selected($watchNow === $watchName)>{{ $watchName }}</option>
                                @endforeach
                            </select>
                            <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.settings.registrar_balance_hint') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.registrar_floor') }} / {{ __('admin.settings.registrar_currency') }}</td>
                        <td class="fieldarea">
                            <div style="display:flex;gap:10px;max-width:320px;">
                                <input type="number" step="0.01" min="0" name="RegistrarBalanceThreshold" value="{{ $settings['RegistrarBalanceThreshold'] ?? '' }}" class="form-control">
                                <input type="text" name="RegistrarBalanceCurrency" value="{{ $settings['RegistrarBalanceCurrency'] ?? '' }}" maxlength="3" class="form-control" style="max-width:100px;" placeholder="USD">
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.registrar_alert_email') }}</td>
                        <td class="fieldarea"><input type="email" name="RegistrarBalanceAlertEmail" value="{{ $settings['RegistrarBalanceAlertEmail'] ?? '' }}" class="form-control" style="max-width:360px;"></td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- TAB 5: MAIL --}}
        <div class="tab-pane" data-tab="mail" id="tab-mail" hidden>
            <div id="settings-mail_configuration">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="22%">{{ __('admin.settings.mail_type') }}</td>
                        <td class="fieldarea">
                            <select name="MailType" id="mail-type" class="form-control" style="max-width:320px;">
                                <option value="" {{ blank($settings['MailType'] ?? null) ? 'selected' : '' }}>{{ __('admin.settings.mail_type_environment') }}</option>
                                <option value="php_mail" {{ ($settings['MailType'] ?? '') === 'php_mail' ? 'selected' : '' }}>{{ __('admin.settings.php_mail') }}</option>
                                <option value="smtp" {{ ($settings['MailType'] ?? '') === 'smtp' ? 'selected' : '' }}>{{ __('admin.settings.smtp') }}</option>
                            </select>
                            <div style="color:#777;font-size:12px;margin-top:4px;">
                                {{ __('admin.settings.sending_via', ['transport' => $mailTransport ?? config('mail.default')]) }}
                                @if(($mailTransport ?? config('mail.default')) === 'log')
                                    <strong style="color:#c00;">{{ __('admin.settings.mail_goes_to_log') }}</strong>
                                @endif
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.enable_email_sending') }}</td>
                        <td class="fieldarea">
                            <label style="font-weight:400;margin:0;cursor:pointer;">
                                <input type="hidden" name="MailEnabled" value="0"><input type="checkbox" name="MailEnabled" value="1" @checked(\App\Listeners\SuppressMailWhenDisabled::isOn($settings['MailEnabled'] ?? null))>
                                {{ __('admin.settings.enable_outgoing_emails') }}
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.system_email_address') }}</td>
                        <td class="fieldarea"><input type="email" name="SystemEmailAddress" value="{{ $settings['SystemEmailAddress'] ?? '' }}" class="form-control" style="max-width:360px;" placeholder="noreply@yourdomain.com"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.email_from_name') }}</td>
                        <td class="fieldarea"><input type="text" name="EmailFromName" value="{{ $settings['EmailFromName'] ?? '' }}" class="form-control" style="max-width:360px;" placeholder="{{ __('admin.settings.from_name_placeholder') }}"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel"><label for="mail-copy-to" style="margin:0;">{{ __('admin.settings.mail_copy_to') }}</label></td>
                        <td class="fieldarea">
                            <input type="text" id="mail-copy-to" name="MailCopyTo" value="{{ $settings['MailCopyTo'] ?? '' }}" class="form-control" style="max-width:460px;">
                            <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.settings.mail_copy_to_hint') }}</div>
                        </td>
                    </tr>
                </table>

                <div id="smtp-fields" style="display:{{ ($settings['MailType'] ?? '') === 'smtp' ? 'block' : 'none' }};margin-top:12px;">
                    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                        <tr>
                            <td class="fieldlabel" width="22%">{{ __('admin.settings.smtp_host') }}</td>
                            <td class="fieldarea"><input type="text" name="SMTPHost" value="{{ $settings['SMTPHost'] ?? '' }}" class="form-control" style="max-width:360px;" placeholder="smtp.yourdomain.com"></td>
                        </tr>
                        <tr>
                            <td class="fieldlabel">{{ __('admin.settings.smtp_port') }}</td>
                            <td class="fieldarea"><input type="number" name="SMTPPort" value="{{ $settings['SMTPPort'] ?? '587' }}" class="form-control" style="max-width:140px;" placeholder="587"></td>
                        </tr>
                        <tr>
                            <td class="fieldlabel">{{ __('admin.settings.smtp_username') }}</td>
                            <td class="fieldarea"><input type="text" name="SMTPUsername" value="{{ $settings['SMTPUsername'] ?? '' }}" class="form-control" style="max-width:360px;" placeholder="user@yourdomain.com"></td>
                        </tr>
                        <tr>
                            <td class="fieldlabel">{{ __('admin.settings.smtp_password') }}</td>
                            <td class="fieldarea"><input type="password" name="SMTPPassword" value="" autocomplete="new-password" class="form-control" style="max-width:360px;" placeholder="{{ ($settings['SMTPPassword'] ?? '') !== '' ? __('admin.settings.smtp_password_keep') : '' }}"></td>
                        </tr>
                        <tr>
                            <td class="fieldlabel">{{ __('admin.settings.smtp_encryption') }}</td>
                            <td class="fieldarea">
                                <select name="SMTPSecurity" class="form-control" style="max-width:220px;">
                                    <option value="tls" {{ ($settings['SMTPSecurity'] ?? 'tls') === 'tls' ? 'selected' : '' }}>{{ 'TLS (STARTTLS)' }}</option>
                                    <option value="ssl" {{ ($settings['SMTPSecurity'] ?? '') === 'ssl' ? 'selected' : '' }}>{{ 'SSL' }}</option>
                                    <option value="none" {{ ($settings['SMTPSecurity'] ?? '') === 'none' ? 'selected' : '' }}>{{ __('admin.settings.smtp_security_none') }}</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>

                <div style="margin-top:12px;display:flex;gap:10px;align-items:center;">
                    <button type="button" id="test-email-btn" class="btn btn-default btn-sm">{{ __('admin.settings.send_test_email') }}</button>
                    <span id="test-email-result" style="font-size:13px;"></span>
                </div>
            </div>
        </div>

        {{-- TAB 6: SUPPORT --}}
        <div class="tab-pane" data-tab="support" id="tab-support" hidden>
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td class="fieldlabel" width="22%">{{ __('admin.settings.knowledge_base_enabled') }}</td>
                    <td class="fieldarea">
                        <label style="font-weight:400;margin:0;cursor:pointer;">
                            <input type="hidden" name="KnowledgeBaseEnabled" value="0"><input type="checkbox" name="KnowledgeBaseEnabled" value="1" {{ ($settings['KnowledgeBaseEnabled'] ?? '1') !== '0' ? 'checked' : '' }}>
                            {{ __('admin.settings.knowledge_base_enabled') }}
                        </label>
                    </td>
                </tr>
            </table>

            <div id="settings-sms_verification" style="margin-top:14px;">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="22%">{{ __('admin.settings.sms_verification') }}</td>
                        <td class="fieldarea">
                            <label style="font-weight:400;margin:0;cursor:pointer;">
                                <input type="hidden" name="TwilioVerifyEnabled" value="0"><input type="checkbox" name="TwilioVerifyEnabled" value="1" {{ !empty($settings['TwilioVerifyEnabled']) ? 'checked' : '' }}>
                                {{ __('admin.settings.twilio_enabled') }}
                            </label>
                            <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.settings.sms_verification_hint') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.twilio_account_sid') }}</td>
                        <td class="fieldarea"><input type="text" name="TwilioAccountSid" value="{{ $settings['TwilioAccountSid'] ?? '' }}" class="form-control" style="max-width:360px;" autocomplete="off"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.twilio_auth_token') }}</td>
                        <td class="fieldarea"><input type="password" name="TwilioAuthToken" value="" class="form-control" style="max-width:360px;" autocomplete="new-password" placeholder="{{ !empty($settings['TwilioAuthToken']) ? '••••••••' : '' }}"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.twilio_verify_service_sid') }}</td>
                        <td class="fieldarea"><input type="text" name="TwilioVerifyServiceSid" value="{{ $settings['TwilioVerifyServiceSid'] ?? '' }}" class="form-control" style="max-width:360px;" autocomplete="off"></td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- TAB 7: INVOICES --}}
        <div class="tab-pane" data-tab="invoicing" id="tab-invoicing" hidden>
            <div id="settings-invoices_section">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="22%">{{ __('admin.settings.default_payment_method') }}</td>
                        <td class="fieldarea">
                            <select name="DefaultPaymentMethod" class="form-control" style="max-width:320px;">
                                @foreach($paymentMethods as $pm)
                                <option value="{{ $pm }}" {{ ($settings['DefaultPaymentMethod'] ?? '') === $pm ? 'selected' : '' }}>{{ \payment_method_label($pm) }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.invoice_due_days') }}</td>
                        <td class="fieldarea"><input type="number" name="InvoiceDueDays" value="{{ $settings['InvoiceDueDays'] ?? '14' }}" class="form-control" style="max-width:140px;" min="0"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel"><label for="invoice-days-before-due" style="margin:0;">{{ __('admin.settings.invoice_days_before_due') }}</label></td>
                        <td class="fieldarea"><input type="number" id="invoice-days-before-due" name="InvoiceDaysBeforeDue" value="{{ $settings['InvoiceDaysBeforeDue'] ?? '14' }}" class="form-control" style="max-width:140px;" min="0" max="365"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel"><label for="consolidated-billing-day" style="margin:0;">{{ __('admin.settings.consolidated_billing_day') }}</label></td>
                        <td class="fieldarea">
                            <select id="consolidated-billing-day" name="ConsolidatedBillingDay" class="form-control" style="max-width:220px;">
                                <option value="0">{{ __('admin.settings.consolidated_billing_off') }}</option>
                                @for($d = 1; $d <= 28; $d++)
                                <option value="{{ $d }}" @selected((int) ($settings['ConsolidatedBillingDay'] ?? 0) === $d)>{{ $d }}</option>
                                @endfor
                            </select>
                            <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.settings.consolidated_billing_hint') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.invoice_renewals_separately') }}</td>
                        <td class="fieldarea">
                            <label style="font-weight:400;margin:0;cursor:pointer;">
                                <input type="hidden" name="InvoiceRenewalsSeparately" value="0"><input type="checkbox" name="InvoiceRenewalsSeparately" value="1" {{ !empty($settings['InvoiceRenewalsSeparately']) ? 'checked' : '' }}>
                                {{ __('admin.settings.invoice_renewals_separately') }}
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel"><label for="invoice-number-format" style="margin:0;">{{ __('admin.settings.invoice_number_format') }}</label></td>
                        <td class="fieldarea">
                            <input type="text" id="invoice-number-format" name="InvoiceNumberFormat" value="{{ $settings['InvoiceNumberFormat'] ?? 'INV-{year}{month}-{num}' }}" class="form-control" style="max-width:320px;" placeholder="INV-{year}{month}-{num}">
                            <label style="font-weight:400;display:block;margin-top:6px;cursor:pointer;">
                                <input type="hidden" name="InvoiceNumberYearlyReset" value="0"><input type="checkbox" name="InvoiceNumberYearlyReset" value="1" {{ !empty($settings['InvoiceNumberYearlyReset']) && $settings['InvoiceNumberYearlyReset'] == '1' ? 'checked' : '' }}>
                                {{ __('admin.settings.invoice_number_reset_year') }}
                            </label>
                            <div style="font-size:12px;color:#777;margin-top:4px;">{{ __('admin.settings.invoice_number_tokens') }}</div>
                            <div style="font-size:12px;color:#555;margin-top:4px;">
                                <span>{{ __('admin.settings.invoice_number_last') }}:</span>
                                <code>{{ $invoiceLast ?? '—' }}</code>
                                <span style="margin-left:12px;">{{ __('admin.settings.invoice_number_preview') }}:</span>
                                <code id="invoice-number-preview" style="font-weight:600;">{{ $invoicePreview }}</code>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel"><label for="invoice-line-period-display" style="margin:0;">{{ __('admin.settings.invoice_line_period_display') }}</label></td>
                        <td class="fieldarea">
                            <select id="invoice-line-period-display" name="InvoiceLinePeriodDisplay" class="form-control" style="max-width:320px;">
                                @foreach(['cycle', 'period', 'cycle_and_period'] as $linePeriodMode)
                                    <option value="{{ $linePeriodMode }}" @selected(($settings['InvoiceLinePeriodDisplay'] ?? 'cycle_and_period') === $linePeriodMode)>
                                        {{ __('admin.settings.invoice_line_period_'.$linePeriodMode) }}
                                    </option>
                                @endforeach
                            </select>
                            <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.settings.invoice_line_period_hint') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.proforma_enabled') }}</td>
                        <td class="fieldarea">
                            <label style="font-weight:600;margin:0;cursor:pointer;">
                                <input type="hidden" name="ProformaEnabled" value="0"><input type="checkbox" id="proforma-enabled" name="ProformaEnabled" value="1" onchange="document.getElementById('proforma-scheme').style.display = this.checked ? '' : 'none';" {{ $proformaEnabled ? 'checked' : '' }}>
                                {{ __('admin.settings.proforma_enabled') }}
                            </label>
                            <div id="proforma-scheme" style="{{ $proformaEnabled ? '' : 'display:none;' }}margin-top:8px;">
                                <label for="proforma-number-format" style="font-size:12px;display:block;margin-bottom:3px;">{{ __('admin.settings.proforma_number_format') }}</label>
                                <input type="text" id="proforma-number-format" name="ProformaNumberFormat" value="{{ $proformaFormat }}" class="form-control" style="max-width:320px;" placeholder="PRO-{year}/{month}-{num}">
                                <div style="font-size:12px;color:#555;margin-top:4px;">
                                    <span>{{ __('admin.settings.proforma_number_last') }}:</span>
                                    <code>{{ $proformaLast ?? '—' }}</code>
                                    <span style="margin-left:12px;">{{ __('admin.settings.invoice_number_preview') }}:</span>
                                    <code style="font-weight:600;">{{ $proformaPreview }}</code>
                                </div>
                                <label style="font-weight:400;display:block;margin-top:6px;cursor:pointer;">
                                    <input type="hidden" name="HidePaidProformas" value="0"><input type="checkbox" name="HidePaidProformas" value="1" {{ ($settings['HidePaidProformas'] ?? '1') == '1' ? 'checked' : '' }}>
                                    {{ __('admin.settings.hide_paid_proformas') }}
                                </label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.invoice_template_languages') }}</td>
                        <td class="fieldarea">
                            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                                <input type="file" name="template" accept=".html,.htm,.txt" class="form-control" style="max-width:320px;" form="invoice-template-upload">
                                <button type="submit" form="invoice-template-upload" class="btn btn-primary btn-sm">{{ __('admin.settings.invoice_template_upload') }}</button>
                            </div>
                            <div style="font-size:12px;color:#777;margin-top:6px;">{{ __('admin.settings.invoice_template_languages_hint') }}</div>
                            <div style="font-size:12px;color:#777;margin-top:6px;">{{ __('admin.settings.invoice_template_hint') }}</div>
                            <div style="font-size:12px;color:#777;font-family:monospace;margin-top:4px;line-height:1.7;">{{ collect($invoiceTemplateVocabulary)->map(fn ($p) => '{'.$p.'}')->join(' ') }}</div>

                            <div style="margin-top:10px;">
                                @forelse($invoiceTemplateLanguages as $languageTemplate)
                                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:5px 0;border-top:1px solid #eee;">
                                    <span style="font-family:monospace;font-size:13px;">{{ $languageTemplate['filename'] }}</span>
                                    <div style="display:flex;gap:6px;">
                                        <a href="{{ route('admin.settings.invoice-template.download', ['file' => $languageTemplate['filename']]) }}" class="btn btn-default btn-xs">{{ __('common.actions.download') }}</a>
                                        <button type="submit" form="invoice-template-remove" formaction="{{ route('admin.settings.invoice-template.remove', ['file' => $languageTemplate['filename']]) }}" class="btn btn-danger btn-xs" onclick="return pnConfirm(event, @js(__('admin.settings.invoice_template_remove_confirm')))">{{ __('admin.settings.invoice_template_remove') }}</button>
                                    </div>
                                </div>
                                @empty
                                <div style="font-size:12px;color:#999;padding:4px 0;">{{ __('admin.settings.invoice_template_languages_none') }}</div>
                                @endforelse
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <div id="auto-charge" style="margin-top:14px;">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="22%">{{ __('admin.settings.auto_charge') }}</td>
                        <td class="fieldarea">
                            <label style="font-weight:400;margin:0;cursor:pointer;">
                                <input type="hidden" name="AutoChargeEnabled" value="0">
                                <input type="checkbox" name="AutoChargeEnabled" value="1" {{ !empty($settings['AutoChargeEnabled']) ? 'checked' : '' }}>
                                {{ __('admin.settings.auto_charge_enabled') }}
                            </label>
                            <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.settings.auto_charge_hint') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.auto_charge_days_before') }}</td>
                        <td class="fieldarea"><input type="number" min="0" name="AutoChargeDaysBefore" value="{{ $settings['AutoChargeDaysBefore'] ?? \App\Support\AutoCharge::DEFAULT_DAYS_BEFORE }}" class="form-control" style="max-width:140px;"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.auto_charge_max_attempts') }}</td>
                        <td class="fieldarea"><input type="number" min="1" name="AutoChargeMaxAttempts" value="{{ $settings['AutoChargeMaxAttempts'] ?? \App\Support\AutoCharge::DEFAULT_MAX_ATTEMPTS }}" class="form-control" style="max-width:140px;"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.auto_charge_retry_days') }}</td>
                        <td class="fieldarea"><input type="number" min="{{ \App\Support\AutoCharge::MINIMUM_RETRY_DAYS }}" name="AutoChargeRetryDays" value="{{ $settings['AutoChargeRetryDays'] ?? \App\Support\AutoCharge::DEFAULT_RETRY_DAYS }}" class="form-control" style="max-width:140px;"></td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- TAB 8: CREDIT / TAX --}}
        <div class="tab-pane" data-tab="credit-tax" id="tab-credit-tax" hidden>
            <div id="settings-late_fees">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="22%">{{ __('admin.settings.late_fee_type') }}</td>
                        <td class="fieldarea">
                            <select name="LateFeeType" class="form-control" style="max-width:240px;">
                                <option value="none" {{ ($settings['LateFeeType'] ?? 'none') === 'none' ? 'selected' : '' }}>{{ __('admin.settings.late_fee_none') }}</option>
                                <option value="flat" {{ ($settings['LateFeeType'] ?? '') === 'flat' ? 'selected' : '' }}>{{ __('admin.settings.late_fee_flat') }}</option>
                                <option value="percent" {{ ($settings['LateFeeType'] ?? '') === 'percent' ? 'selected' : '' }}>{{ __('admin.settings.late_fee_percent') }}</option>
                            </select>
                            <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.settings.late_fee_hint') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.late_fee_amount') }}</td>
                        <td class="fieldarea"><input type="number" step="0.01" min="0" name="LateFeeAmount" value="{{ $settings['LateFeeAmount'] ?? '0' }}" class="form-control" style="max-width:160px;"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.late_fee_min_days') }}</td>
                        <td class="fieldarea"><input type="number" min="0" name="LateFeeMinDays" value="{{ $settings['LateFeeMinDays'] ?? '3' }}" class="form-control" style="max-width:160px;"></td>
                    </tr>
                </table>
            </div>

            <div id="settings-billing_currency_section" style="margin-top:14px;">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="22%">{{ __('admin.settings.billing_currency') }}</td>
                        <td class="fieldarea">
                            <select name="BillingCurrency" class="form-control" style="max-width:220px;">
                                <option value="">{{ __('admin.settings.billing_currency_none') }}</option>
                                @foreach(\App\Models\Currency::orderBy('code')->get() as $cur)
                                    <option value="{{ $cur->code }}" {{ ($settings['BillingCurrency'] ?? '') === $cur->code ? 'selected' : '' }}>{{ $cur->code }}</option>
                                @endforeach
                            </select>
                            <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.settings.billing_currency_hint') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.official_rate_provider') }}</td>
                        <td class="fieldarea">
                            <select name="OfficialRateProvider" class="form-control" style="max-width:340px;">
                                <option value="">{{ __('admin.settings.official_rate_none') }}</option>
                                <option value="tcmb" {{ ($settings['OfficialRateProvider'] ?? '') === 'tcmb' ? 'selected' : '' }}>{{ __('admin.settings.official_rate_tcmb') }}</option>
                                <option value="nbp" {{ ($settings['OfficialRateProvider'] ?? '') === 'nbp' ? 'selected' : '' }}>{{ __('admin.settings.official_rate_nbp') }}</option>
                                @foreach(\App\Services\OfficialRateProviders::all() as $provider)
                                    <option value="{{ $provider['key'] }}" {{ ($settings['OfficialRateProvider'] ?? '') === $provider['key'] ? 'selected' : '' }}>{{ $provider['name'] }} ({{ $provider['base'] }} → {{ $provider['currency'] }})</option>
                                @endforeach
                            </select>
                            <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.settings.official_rate_nbp_hint') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.tcmb_rate_kind') }}</td>
                        <td class="fieldarea">
                            <select name="TcmbRateKind" class="form-control" style="max-width:240px;">
                                <option value="ForexSelling" {{ ($settings['TcmbRateKind'] ?? 'ForexSelling') === 'ForexSelling' ? 'selected' : '' }}>{{ __('pdf.rate_kind_selling') }}</option>
                                <option value="ForexBuying" {{ ($settings['TcmbRateKind'] ?? '') === 'ForexBuying' ? 'selected' : '' }}>{{ __('pdf.rate_kind_buying') }}</option>
                            </select>
                            <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.settings.tcmb_rate_kind_hint') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.payment_reference_prefix') }}</td>
                        <td class="fieldarea">
                            <input type="text" name="PaymentReferencePrefix" value="{{ $settings['PaymentReferencePrefix'] ?? 'INV' }}" maxlength="8" class="form-control" style="max-width:160px;">
                            <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.settings.payment_reference_prefix_hint') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.official_rate_providers') }}</td>
                        <td class="fieldarea">
                            <textarea name="OfficialRateProviders" rows="6" class="form-control" style="font-family:monospace;font-size:12px;" spellcheck="false" placeholder="[]">{{ $settings['OfficialRateProviders'] ?? '' }}</textarea>
                            <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.settings.official_rate_providers_hint') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.official_rate_api_key') }}</td>
                        <td class="fieldarea">
                            <input type="password" name="OfficialRateApiKey" value="" class="form-control" style="max-width:340px;" autocomplete="new-password" placeholder="{{ !empty($settings['OfficialRateApiKey']) ? '••••••••' : '' }}">
                            <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.settings.official_rate_api_key_hint') }}</div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- TAB 9: SOCIAL / SECURITY --}}
        <div class="tab-pane" data-tab="security" id="tab-security" hidden>
            <div id="settings-email_verification">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="22%">{{ __('admin.settings.email_verification') }}</td>
                        <td class="fieldarea">
                            <label style="font-weight:400;margin:0;cursor:pointer;">
                                <input type="hidden" name="EmailVerificationRequired" value="0"><input type="checkbox" name="EmailVerificationRequired" value="1" {{ ($settings['EmailVerificationRequired'] ?? '1') === '1' ? 'checked' : '' }}>
                                {{ __('admin.settings.email_verification_required') }}
                            </label>
                            <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.settings.email_verification_hint') }}</div>
                        </td>
                    </tr>
                </table>
            </div>

            <div id="settings-account_protection" style="margin-top:14px;">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="22%">{{ __('admin.settings.account_protection') }}</td>
                        <td class="fieldarea">
                            <label style="font-weight:400;margin:0;cursor:pointer;">
                                <input type="hidden" name="ConfirmSensitiveActions" value="0"><input type="checkbox" name="ConfirmSensitiveActions" value="1" {{ !empty($settings['ConfirmSensitiveActions']) ? 'checked' : '' }}>
                                {{ __('admin.settings.confirm_sensitive_actions') }}
                            </label>
                            <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.settings.confirm_sensitive_actions_hint') }}</div>
                        </td>
                    </tr>
                </table>
            </div>

            <div id="settings-fraud_screening" style="margin-top:14px;">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="22%">{{ __('admin.settings.maxmind_enabled') }}</td>
                        <td class="fieldarea">
                            <label style="font-weight:400;margin:0;cursor:pointer;">
                                <input type="hidden" name="MaxMindEnabled" value="0"><input type="checkbox" name="MaxMindEnabled" value="1" {{ !empty($settings['MaxMindEnabled']) ? 'checked' : '' }}>
                                {{ __('admin.settings.maxmind_enabled') }}
                            </label>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;max-width:560px;margin-top:6px;">
                                <input type="text" name="MaxMindAccountId" value="{{ $settings['MaxMindAccountId'] ?? '' }}" class="form-control" autocomplete="off" placeholder="{{ __('admin.settings.maxmind_account_id') }}">
                                <input type="password" name="MaxMindLicenseKey" value="" class="form-control" autocomplete="new-password" placeholder="{{ !empty($settings['MaxMindLicenseKey']) ? '••••••••' : __('admin.settings.maxmind_license_key') }}">
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.fraudlabs_enabled') }}</td>
                        <td class="fieldarea">
                            <label style="font-weight:400;margin:0;cursor:pointer;">
                                <input type="hidden" name="FraudLabsEnabled" value="0"><input type="checkbox" name="FraudLabsEnabled" value="1" {{ !empty($settings['FraudLabsEnabled']) ? 'checked' : '' }}>
                                {{ __('admin.settings.fraudlabs_enabled') }}
                            </label>
                            <div style="margin-top:6px;max-width:280px;">
                                <input type="password" name="FraudLabsApiKey" value="" class="form-control" autocomplete="new-password" placeholder="{{ !empty($settings['FraudLabsApiKey']) ? '••••••••' : __('admin.settings.fraudlabs_api_key') }}">
                            </div>
                            <div style="font-size:12px;color:#777;margin-top:4px;">{{ __('admin.settings.fraud_screening_hint') }}</div>
                        </td>
                    </tr>
                </table>
            </div>

            <div id="settings-recaptcha" style="margin-top:14px;">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="22%">{{ __('admin.settings.recaptcha') }}</td>
                        <td class="fieldarea">
                            <label style="font-weight:400;display:block;margin-bottom:4px;cursor:pointer;">
                                <input type="hidden" name="RecaptchaEnabled" value="0"><input type="checkbox" name="RecaptchaEnabled" value="1" {{ !empty($settings['RecaptchaEnabled']) ? 'checked' : '' }}>
                                {{ __('admin.settings.recaptcha_enabled') }}
                            </label>
                            <label style="font-weight:400;display:block;margin-bottom:4px;cursor:pointer;">
                                <input type="hidden" name="RecaptchaTicketsEnabled" value="0"><input type="checkbox" name="RecaptchaTicketsEnabled" value="1" {{ !empty($settings['RecaptchaTicketsEnabled']) ? 'checked' : '' }}>
                                {{ __('admin.settings.recaptcha_tickets_enabled') }}
                            </label>
                            @foreach(['RecaptchaSignupEnabled' => 'recaptcha_signup_enabled', 'RecaptchaLoginEnabled' => 'recaptcha_login_enabled', 'RecaptchaPasswordEnabled' => 'recaptcha_password_enabled'] as $recaptchaSwitch => $recaptchaLabel)
                            <label style="font-weight:400;display:block;margin-bottom:4px;cursor:pointer;">
                                <input type="hidden" name="{{ $recaptchaSwitch }}" value="0"><input type="checkbox" name="{{ $recaptchaSwitch }}" value="1" {{ !empty($settings[$recaptchaSwitch]) ? 'checked' : '' }}>
                                {{ __('admin.settings.'.$recaptchaLabel) }}
                            </label>
                            @endforeach
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;max-width:560px;margin-top:8px;">
                                <div>
                                    <label style="font-size:12px;display:block;margin-bottom:2px;">{{ __('admin.settings.recaptcha_site_key') }}</label>
                                    <input type="text" name="RecaptchaSiteKey" value="{{ $settings['RecaptchaSiteKey'] ?? '' }}" class="form-control" autocomplete="off">
                                </div>
                                <div>
                                    <label style="font-size:12px;display:block;margin-bottom:2px;">{{ __('admin.settings.recaptcha_secret_key') }}</label>
                                    <input type="password" name="RecaptchaSecretKey" value="" class="form-control" autocomplete="new-password" placeholder="{{ !empty($settings['RecaptchaSecretKey']) ? '••••••••' : '' }}">
                                </div>
                            </div>
                            <div style="font-size:12px;color:#777;margin-top:4px;">{{ __('admin.settings.recaptcha_hint') }}</div>
                        </td>
                    </tr>
                </table>
            </div>

            <div id="settings-social_login" style="margin-top:14px;">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="22%">{{ __('admin.settings.google_login_enabled') }}</td>
                        <td class="fieldarea">
                            <label style="font-weight:400;margin:0;cursor:pointer;">
                                <input type="hidden" name="GoogleLoginEnabled" value="0"><input type="checkbox" name="GoogleLoginEnabled" value="1" {{ !empty($settings['GoogleLoginEnabled']) ? 'checked' : '' }}>
                                {{ __('admin.settings.google_login_enabled') }}
                            </label>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;max-width:560px;margin-top:6px;">
                                <div>
                                    <label style="font-size:12px;display:block;margin-bottom:2px;">{{ __('admin.settings.google_client_id') }}</label>
                                    <input type="text" name="GoogleClientId" value="{{ $settings['GoogleClientId'] ?? '' }}" class="form-control" autocomplete="off">
                                </div>
                                <div>
                                    <label style="font-size:12px;display:block;margin-bottom:2px;">{{ __('admin.settings.google_client_secret') }}</label>
                                    <input type="password" name="GoogleClientSecret" value="" class="form-control" autocomplete="new-password" placeholder="{{ !empty($settings['GoogleClientSecret']) ? '••••••••' : '' }}">
                                </div>
                            </div>
                            <div style="font-size:12px;color:#777;margin-top:4px;">{{ __('admin.settings.google_login_hint') }} <code>{{ route('client.social.google.callback') }}</code></div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.github_login_enabled') }}</td>
                        <td class="fieldarea">
                            <label style="font-weight:400;margin:0;cursor:pointer;">
                                <input type="hidden" name="GithubLoginEnabled" value="0"><input type="checkbox" name="GithubLoginEnabled" value="1" {{ !empty($settings['GithubLoginEnabled']) ? 'checked' : '' }}>
                                {{ __('admin.settings.github_login_enabled') }}
                            </label>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;max-width:560px;margin-top:6px;">
                                <div>
                                    <label style="font-size:12px;display:block;margin-bottom:2px;">{{ __('admin.settings.github_client_id') }}</label>
                                    <input type="text" name="GithubClientId" value="{{ $settings['GithubClientId'] ?? '' }}" class="form-control" autocomplete="off">
                                </div>
                                <div>
                                    <label style="font-size:12px;display:block;margin-bottom:2px;">{{ __('admin.settings.github_client_secret') }}</label>
                                    <input type="password" name="GithubClientSecret" value="" class="form-control" autocomplete="new-password" placeholder="{{ !empty($settings['GithubClientSecret']) ? '••••••••' : '' }}">
                                </div>
                            </div>
                            <div style="font-size:12px;color:#777;margin-top:4px;">{{ __('admin.settings.github_login_hint') }} <code>{{ route('client.social.github.callback') }}</code></div>
                        </td>
                    </tr>
                </table>
            </div>

            <div id="settings-seo_section" style="margin-top:14px;">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="22%">{{ __('admin.settings.social_profiles') }}</td>
                        <td class="fieldarea">
                            <div style="font-size:12px;color:#777;margin-bottom:8px;">{{ __('admin.settings.social_profiles_hint') }}</div>
                            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:8px 14px;">
                                @foreach(\App\Support\SocialProfiles::NETWORKS as $network => $key)
                                <div>
                                    <label style="font-size:12px;display:block;margin-bottom:2px;">{{ \App\Support\SocialProfiles::NAMES[$network] }}</label>
                                    <input type="url" name="{{ $key }}" value="{{ $settings[$key] ?? '' }}" class="form-control">
                                </div>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.seo_description') }}</td>
                        <td class="fieldarea">
                            <textarea name="SeoDescription" rows="2" maxlength="300" class="form-control">{{ $settings['SeoDescription'] ?? '' }}</textarea>
                            <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.settings.seo_description_hint') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.seo_share_image') }}</td>
                        <td class="fieldarea">
                            <input type="text" name="SeoShareImage" value="{{ $settings['SeoShareImage'] ?? '' }}" class="form-control" style="max-width:460px;">
                            <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.settings.seo_share_image_hint') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.seo_twitter') }}</td>
                        <td class="fieldarea"><input type="text" name="SeoTwitter" value="{{ $settings['SeoTwitter'] ?? '' }}" class="form-control" style="max-width:240px;" placeholder="@"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ 'robots.txt' }}</td>
                        <td class="fieldarea">
                            <textarea name="RobotsTxt" rows="3" class="form-control" style="font-family:monospace;">{{ $settings['RobotsTxt'] ?? '' }}</textarea>
                            <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.settings.robots_txt_hint', ['sitemap' => route('sitemap')]) }}</div>
                            @if(is_file(public_path('robots.txt')))<div style="font-size:12px;color:#8a6d3b;margin-top:3px;">{{ __('admin.settings.robots_txt_static', ['path' => 'public/robots.txt']) }}</div>@endif
                        </td>
                    </tr>
                </table>
            </div>

            <div id="settings-tracking_section" style="margin-top:14px;">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="22%">{{ __('admin.settings.tracking_gtm_id') }}</td>
                        <td class="fieldarea">
                            <input type="text" name="TrackingGtmId" value="{{ $settings['TrackingGtmId'] ?? '' }}" class="form-control" style="max-width:260px;">
                            <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.settings.tracking_gtm_hint') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.tracking_policy_url') }}</td>
                        <td class="fieldarea"><input type="text" name="TrackingPolicyUrl" value="{{ $settings['TrackingPolicyUrl'] ?? '' }}" class="form-control" style="max-width:460px;"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.tracking_consent') }}</td>
                        <td class="fieldarea">
                            <label style="font-weight:400;margin:0;cursor:pointer;">
                                <input type="hidden" name="TrackingConsent" value="0"><input type="checkbox" name="TrackingConsent" value="1" {{ ($settings['TrackingConsent'] ?? '1') !== '0' ? 'checked' : '' }}>
                                {{ __('admin.settings.tracking_consent') }}
                            </label>
                            <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.settings.tracking_consent_hint') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.tracking_head_code') }}</td>
                        <td class="fieldarea"><textarea name="TrackingHeadCode" rows="3" class="form-control" style="font-family:monospace;">{{ $settings['TrackingHeadCode'] ?? '' }}</textarea></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.settings.tracking_footer_code') }}</td>
                        <td class="fieldarea">
                            <textarea name="TrackingFooterCode" rows="3" class="form-control" style="font-family:monospace;">{{ $settings['TrackingFooterCode'] ?? '' }}</textarea>
                            <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.settings.tracking_code_hint') }}</div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="btn-container">
        <button type="submit" class="btn btn-primary">{{ __('admin.settings.save_settings') }}</button>
    </div>
</form>

{{-- The invoice-template upload/remove run as their own requests, but their
     controls sit inside the settings form above, which must not be nested. --}}
<form id="invoice-template-upload" method="POST" action="{{ route('admin.settings.invoice-template.upload') }}" enctype="multipart/form-data">@csrf</form>
<form id="invoice-template-remove" method="POST" action="{{ route('admin.settings.invoice-template.remove') }}">@csrf @method('DELETE')</form>

<script>
(function () {
    var tabs = document.querySelectorAll('.settings-tab');
    var panes = document.querySelectorAll('.tab-pane[data-tab]');
    var aliases = { general: 'company', notifications: 'mail', automation: 'ordering', seo: 'security' };

    function activate(name) {
        tabs.forEach(function (t) {
            var on = t.getAttribute('data-tab') === name;
            t.classList.toggle('active', on);
            t.classList.toggle('is-active', on);
            if (t.parentElement) t.parentElement.classList.toggle('active', on);
        });
        panes.forEach(function (p) {
            var on = p.getAttribute('data-tab') === name;
            p.hidden = !on;
            p.classList.toggle('active', on);
        });
    }

    tabs.forEach(function (t) {
        t.addEventListener('click', function (e) {
            e.preventDefault();
            var tab = t.getAttribute('data-tab');
            activate(tab);
            history.replaceState(null, '', '#' + tab);
        });
    });

    function tabOf(hash) {
        if (!hash) return null;
        var name = hash.replace('#', '');
        if (aliases[name]) name = aliases[name];
        var byTab = document.querySelector('.settings-tab[data-tab="' + CSS.escape(name) + '"]');
        if (byTab) return name;
        var section = document.getElementById(name) || document.querySelector('[id="' + CSS.escape(name) + '"]');
        var pane = section ? section.closest('.tab-pane[data-tab]') : null;
        return pane ? pane.getAttribute('data-tab') : null;
    }

    function openFromHash() {
        var name = tabOf(window.location.hash) || 'company';
        activate(name);
        var target = document.getElementById(window.location.hash.replace('#', ''));
        if (target && target.closest('.tab-pane[data-tab]')) {
            target.scrollIntoView();
        }
    }

    window.addEventListener('hashchange', openFromHash);
    openFromHash();
})();

document.getElementById('invoice-number-format').addEventListener('input', renderInvoicePreview);
document.addEventListener('DOMContentLoaded', renderInvoicePreview);

function renderInvoicePreview() {
    var fmt = document.getElementById('invoice-number-format').value || '';
    var now = new Date();
    var y = String(now.getFullYear());
    var yy = y.slice(-2);
    var m = ('0' + (now.getMonth() + 1)).slice(-2);
    var d = ('0' + now.getDate()).slice(-2);
    var seq = String({{ $invoiceNextSeq }});
    var out = fmt
        .replace(/\{year\}/g, y)
        .replace(/\{yy\}/g, yy)
        .replace(/\{month\}/g, m)
        .replace(/\{day\}/g, d)
        .replace(/\{num(?::([1-9]))?\}/g, function (all, digits) {
            return seq.padStart(digits ? Number(digits) : {{ \App\Services\InvoiceService::NUM_DIGITS }}, '0');
        });
    document.getElementById('invoice-number-preview').textContent = out;
}

document.getElementById('mail-type').addEventListener('change', function() {
    document.getElementById('smtp-fields').style.display = this.value === 'smtp' ? 'block' : 'none';
});

document.getElementById('test-email-btn').addEventListener('click', function() {
    var btn = this;
    var result = document.getElementById('test-email-result');
    btn.disabled = true;
    btn.textContent = '{{ __("admin.settings.sending") }}';
    result.textContent = '';
    result.style.color = '';

    fetch('{{ route('admin.settings.test-email') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({})
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        btn.disabled = false;
        btn.textContent = '{{ __("admin.settings.send_test_email") }}';
        if (data.success) {
            result.textContent = '✓ ' + data.message;
            result.style.color = '#3c763d';
        } else {
            result.textContent = '✗ ' + data.message;
            result.style.color = '#a94442';
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.textContent = '{{ __("admin.settings.send_test_email") }}';
        result.textContent = '✗ {{ __("admin.settings.request_failed") }}';
        result.style.color = '#a94442';
    });
});
</script>
@endsection
