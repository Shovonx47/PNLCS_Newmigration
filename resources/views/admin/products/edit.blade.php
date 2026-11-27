@extends('admin.layouts.app')
@section('title', __('admin.products.edit_product') . ': ' . $product->name)
@section('content')
<h1>{{ __('admin.products.edit_product') }}: {{ $product->name }}</h1>

<div class="context-btn-container" style="margin-bottom:12px;display:flex;gap:6px;flex-wrap:wrap;">
    <a href="{{ route('admin.products.index') }}" class="btn btn-default btn-sm">&larr; {{ __('admin.products.back') }}</a>
    <form method="POST" action="{{ route('admin.products.destroy', $product) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js(__('admin.products.confirm_delete')))">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-danger btn-sm">{{ __('admin.products.delete_product') }}</button>
    </form>
</div>

<style>
    .product-tab-pane { display: none; }
    .product-tab-pane.active { display: block; }
</style>

<ul class="nav nav-tabs admin-tabs" id="product-edit-tabs" role="tablist">
    <li class="nav-item active"><a class="nav-link active" href="#tab-details" role="tab" data-product-tab="tab-details">{{ __('Details') }}</a></li>
    <li class="nav-item"><a class="nav-link" href="#tab-pricing" role="tab" data-product-tab="tab-pricing">{{ __('admin.products.pricing') }}</a></li>
    <li class="nav-item"><a class="nav-link" href="#tab-module" role="tab" data-product-tab="tab-module">{{ __('Module Settings') }}</a></li>
    <li class="nav-item"><a class="nav-link" href="#product-fields" role="tab" data-product-tab="product-fields">{{ __('Custom Fields') }}</a></li>
    <li class="nav-item"><a class="nav-link" href="#tab-upgrades" role="tab" data-product-tab="tab-upgrades">{{ __('admin.products.upgrade_paths') }}</a></li>
    <li class="nav-item"><a class="nav-link" href="#tab-free-domains" role="tab" data-product-tab="tab-free-domains">{{ __('admin.products.domain_title') }}</a></li>
    <li class="nav-item"><a class="nav-link" href="#tab-links" role="tab" data-product-tab="tab-links">{{ __('Links') }}</a></li>
</ul>

<div class="tab-content admin-tabs" id="product-edit-panes">
    <form method="POST" action="{{ route('admin.products.update', $product) }}" id="product-edit-form" x-data="{ type: '{{ $product->type }}' }">
        @csrf @method('PUT')

        <div class="tab-pane product-tab-pane active" id="tab-details">
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td class="fieldlabel" width="20%">{{ __('common.form.type') }}</td>
                    <td class="fieldarea">
                        <select name="type" x-model="type" class="form-control" style="max-width:320px;">
                            <option value="hosting" {{ $product->type=='hosting'?'selected':'' }}>{{ __('admin.products.type_hosting') }}</option>
                            <option value="reseller" {{ $product->type=='reseller'?'selected':'' }}>{{ __('admin.products.type_reseller_label') }}</option>
                            <option value="vps" {{ $product->type=='vps'?'selected':'' }}>{{ __('admin.products.type_vps') }}</option>
                            <option value="ssl" {{ $product->type=='ssl'?'selected':'' }}>{{ __('admin.products.type_ssl') }}</option>
                            <option value="other" {{ $product->type=='other'?'selected':'' }}>{{ __('admin.products.type_other') }}</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.products.product_group') }}</td>
                    <td class="fieldarea">
                        <select name="group_id" class="form-control" style="max-width:320px;">
                            @foreach($groups as $g)<option value="{{ $g->id }}" {{ $product->group_id == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>@endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.products.product_name') }} *</td>
                    <td class="fieldarea">
                        <input type="text" name="name" value="{{ $product->name }}" required class="form-control" style="max-width:420px;">
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.products.product_details') }}</td>
                    <td class="fieldarea">
                        @include('admin.products.partials.description-tabs', ['product' => $product, 'value' => old('description', $product->description), 'translationLanguages' => $translationLanguages ?? collect(), 'rows' => 5])
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.products.stock_control') }}</td>
                    <td class="fieldarea">
                        <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                            <label style="font-weight:400;margin:0;"><input type="checkbox" name="stock_control" value="1" {{ $product->stock_control ? 'checked' : '' }}> {{ __('admin.products.stock_control_hint') }}</label>
                            <input type="number" min="0" name="stock_qty" value="{{ $product->stock_qty ?? 0 }}" class="form-control" style="max-width:120px;">
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.products.suspend_after_days') }}</td>
                    <td class="fieldarea">
                        <input type="number" name="suspend_after_days" min="0" max="365" value="{{ $product->suspend_after_days }}" placeholder="{{ \App\Console\Commands\SuspensionCommand::graceDays() }}" class="form-control" style="max-width:140px;">
                        <div style="color:#777;font-size:12px;margin-top:3px;">{{ __('admin.products.suspend_after_days_hint', ['days' => \App\Console\Commands\SuspensionCommand::graceDays()]) }}</div>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.products.terminate_after_days') }}</td>
                    <td class="fieldarea">
                        <input type="number" name="terminate_after_days" min="1" max="3650" value="{{ $product->terminate_after_days }}" class="form-control" style="max-width:140px;">
                        <div style="color:#777;font-size:12px;margin-top:3px;">{{ __('admin.products.terminate_after_days_hint') }}</div>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('common.table.status') }}</td>
                    <td class="fieldarea">
                        <div style="display:flex;gap:20px;flex-wrap:wrap;">
                            <label style="font-weight:400;margin:0;cursor:pointer;"><input type="checkbox" name="hidden" value="1" {{ $product->hidden?'checked':'' }}> {{ __('admin.products.hidden') }}</label>
                            <label style="font-weight:400;margin:0;cursor:pointer;"><input type="checkbox" name="retired" value="1" {{ $product->retired?'checked':'' }}> {{ __('admin.products.retired') }}</label>
                            <label style="font-weight:400;margin:0;cursor:pointer;"><input type="checkbox" name="is_featured" value="1" {{ $product->is_featured?'checked':'' }}> {{ __('admin.products.featured') }}</label>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="tab-pane product-tab-pane" id="tab-pricing">
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3" style="margin-bottom:12px;">
                <tr>
                    <td class="fieldlabel" width="20%">{{ __('admin.products.payment') }}</td>
                    <td class="fieldarea">
                        <select name="pay_type" class="form-control" style="max-width:260px;">
                            <option value="recurring" {{ $product->pay_type=='recurring'?'selected':'' }}>{{ __('admin.products.pay_recurring') }}</option>
                            <option value="onetime" {{ $product->pay_type=='onetime'?'selected':'' }}>{{ __('admin.products.pay_onetime') }}</option>
                            <option value="free" {{ $product->pay_type=='free'?'selected':'' }}>{{ __('admin.products.pay_free') }}</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ 'Free Trial Period' }}</td>
                    <td class="fieldarea">
                        @php $trialChoices = [0, 1, 3, 7, 15, 30, 90]; $currentTrial = (int) ($product->trial_days ?? 0); @endphp
                        <select name="trial_days" class="form-control" style="max-width:260px;">
                            @foreach($trialChoices as $td)
                            <option value="{{ $td }}" @selected($currentTrial === $td)>{{ $td === 0 ? 'Disabled (0 days)' : $td.' '.($td === 1 ? 'day' : 'days') }}</option>
                            @endforeach
                            @if(! in_array($currentTrial, $trialChoices, true))
                            <option value="{{ $currentTrial }}" selected>{{ $currentTrial.' days' }}</option>
                            @endif
                        </select>
                        <div style="color:#777;font-size:12px;margin-top:3px;">{{ 'Shifts the initial due date on activation when > 0.' }}</div>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ 'Compare / Regular Price' }}</td>
                    <td class="fieldarea">
                        <input type="number" step="0.01" min="0" name="compare_price" value="{{ $product->compare_price !== null && (float) $product->compare_price > 0 ? $product->compare_price : '' }}" class="form-control" style="max-width:200px;">
                        <div style="color:#777;font-size:12px;margin-top:3px;">{{ 'Optional crossed-out regular price shown above the sale price in the store.' }}</div>
                    </td>
                </tr>
            </table>

            {{-- One price, in the shop currency. A customer who chose another
                 currency sees it converted at the daily rate; a price typed
                 against each currency would not be the one charged. --}}
            @php
                $currency = $currencies->firstWhere('is_default', true) ?? $currencies->first();
            @endphp
            @if($currency)
            @php
                $p = $pricing[$currency->id] ?? null;
            @endphp
            <div class="tablebg">
                <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
                    <thead>
                        <tr>
                            <th>{{ __('common.table.currency') }}</th>
                            <th></th>
                            @foreach(['monthly','quarterly','semiannually','annually','biennially','triennially'] as $cycle)
                            <th style="text-transform:capitalize;">{{ $cycle }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td rowspan="2" style="font-weight:600;vertical-align:middle;white-space:nowrap;">{{ $currency->code }} ({{ $currency->prefix }})</td>
                            <td style="font-size:11px;color:#666;white-space:nowrap;">{{ __('Setup Fee') }}</td>
                            @foreach(['monthly_setup','quarterly_setup','semiannually_setup','annually_setup','biennially_setup','triennially_setup'] as $setup)
                            <td><input type="number" step="0.01" name="pricing[{{ $currency->id }}][{{ $setup }}]" value="{{ $p ? $p->$setup : 0 }}" class="form-control" style="font-size:12px;"></td>
                            @endforeach
                        </tr>
                        <tr>
                            <td style="font-size:11px;color:#666;white-space:nowrap;">{{ __('admin.invoices.price') }}</td>
                            @foreach(['monthly','quarterly','semiannually','annually','biennially','triennially'] as $cycle)
                            <td><input type="number" step="0.01" name="pricing[{{ $currency->id }}][{{ $cycle }}]" value="{{ $p ? $p->$cycle : -1 }}" class="form-control" style="font-size:12px;"></td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
            <p style="font-size:11px;color:#777;margin:6px 0 0;">{{ __('admin.products.disable_cycle_hint') }} {{ __('admin.products.pricing_shop_currency_hint') }}</p>
            @endif
        </div>

        <div class="tab-pane product-tab-pane" id="tab-module">
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3" style="margin-bottom:15px;">
                <tr x-show="type !== 'ssl'">
                    <td class="fieldlabel" width="20%">{{ __('admin.products.server_module') }}</td>
                    <td class="fieldarea">
                        <select name="server_type" class="form-control" style="max-width:320px;">
                            <option value="">{{ __('admin.products.server_module_none') }}</option>
                            @foreach($serverModules as $key => $label)
                            <option value="{{ $key }}" @selected(strtolower((string) $product->server_type) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div style="color:#777;font-size:12px;margin-top:3px;">{{ __('admin.products.server_module_hint') }}</div>
                    </td>
                </tr>
                <tr x-show="type === 'ssl'" x-cloak>
                    <td class="fieldlabel" width="20%">{{ __('admin.products.ssl_module') }}</td>
                    <td class="fieldarea">
                        <select name="ssl_module" class="form-control" style="max-width:320px;">
                            <option value="">{{ __('admin.products.ssl_none') }}</option>
                            @foreach($sslModules + array_filter([(string) $product->ssl_module => (string) $product->ssl_module]) as $sslKey => $sslLabel)
                            <option value="{{ $sslKey }}" @selected($product->ssl_module === $sslKey)>{{ $sslLabel }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.products.server_group') }}</td>
                    <td class="fieldarea">
                        <select name="server_group_id" class="form-control" style="max-width:320px;">
                            <option value="">{{ __('admin.products.server_group_any') }}</option>
                            @foreach($serverGroups as $sg)
                            <option value="{{ $sg->id }}" @selected((int) $product->server_group_id === (int) $sg->id)>{{ $sg->name }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr id="package-group">
                    <td class="fieldlabel">{{ __('admin.products.package') }}</td>
                    <td class="fieldarea">
                        <select name="package_name" id="package-select" class="form-control" style="max-width:320px;">
                            <option value="">{{ __('admin.products.package_default') }}</option>
                            @foreach(($packageList['packages'] ?? []) as $pkg)
                            <option value="{{ $pkg['id'] }}" @selected(($selectedPackage ?? '') === $pkg['id'])>{{ $pkg['name'] }}</option>
                            @endforeach
                            {{-- The saved package stays selectable when the panel did not list it
                                 (unreachable, or the plan was renamed there): without it the form
                                 posted "Default" and saving the product wiped the plan. --}}
                            @if(($selectedPackage ?? '') !== '' && ! collect($packageList['packages'] ?? [])->contains('id', $selectedPackage))
                            <option value="{{ $selectedPackage }}" selected>{{ $selectedPackage }}</option>
                            @endif
                        </select>
                        <div id="package-note" style="color:#777;font-size:12px;margin-top:3px;">
                            {{ $packageList['error'] ?? __('admin.products.package_hint') }}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.products.auto_setup') }}</td>
                    <td class="fieldarea">
                        <select name="auto_setup" class="form-control" style="max-width:360px;">
                            <option value="order" {{ $product->auto_setup=='order'?'selected':'' }}>{{ __('admin.products.auto_setup_order') }}</option>
                            <option value="payment" {{ $product->auto_setup=='payment'?'selected':'' }}>{{ __('admin.products.auto_setup_payment') }}</option>
                            <option value="manual" {{ $product->auto_setup=='manual'?'selected':'' }}>{{ __('admin.products.auto_setup_manual') }}</option>
                        </select>
                    </td>
                </tr>
            </table>

            @php $cfg = is_string($product->config_options) ? (json_decode($product->config_options, true) ?: []) : ($product->config_options ?? []); @endphp
            @include('admin.products.partials.proxmox')
            @include('admin.products.partials.module-config')

            <div data-module-card="panelica" style="margin-top:15px;">
                <div class="infobox"><strong>{{ __('admin.products.panelica_resources') }}</strong> &mdash; {{ __('admin.products.panelica_resources_hint') }}</div>
                <input type="hidden" name="res_section" value="1">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="20%">{{ __('admin.products.managed_mode') }}</td>
                        <td class="fieldarea">
                            <label style="font-weight:400;margin:0;"><input type="checkbox" name="res_managed" value="1" {{ !empty($cfg['res_managed']) ? 'checked' : '' }}> {{ __('admin.products.managed_mode_hint') }}</label>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.products.existing_plan') }}</td>
                        <td class="fieldarea">
                            @if(!empty($panelicaPlans))
                            <select name="panelica_plan_id" class="form-control" style="max-width:360px;">
                                <option value="">&mdash; {{ __('admin.products.no_plan') }} &mdash;</option>
                                @foreach($panelicaPlans as $pl)
                                <option value="{{ $pl['id'] ?? '' }}" {{ (string)($cfg['panelica_plan_id'] ?? '') === (string)($pl['id'] ?? '') ? 'selected' : '' }}>{{ $pl['name'] ?? ($pl['id'] ?? 'plan') }}</option>
                                @endforeach
                            </select>
                            @else
                            <input type="text" name="panelica_plan_id" value="{{ $cfg['panelica_plan_id'] ?? '' }}" class="form-control" style="max-width:360px;" placeholder="{{ 'd6875821-...' }}">
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.products.app_hosting') }}</td>
                        <td class="fieldarea">
                            @if(!empty($panelicaTemplates))
                            <select name="panelica_app_template" class="form-control" style="max-width:360px;">
                                <option value="">&mdash; {{ __('admin.products.no_app') }} &mdash;</option>
                                @foreach($panelicaTemplates as $tpl)
                                <option value="{{ $tpl['slug'] }}" {{ (string)($cfg['panelica_app_template'] ?? '') === $tpl['slug'] ? 'selected' : '' }}>{{ $tpl['name'] }}</option>
                                @endforeach
                            </select>
                            @else
                            <input type="text" name="panelica_app_template" value="{{ $cfg['panelica_app_template'] ?? '' }}" class="form-control" style="max-width:360px;" placeholder="{{ 'wordpress' }}">
                            @endif
                            <div style="font-size:11px;color:#777;margin-top:3px;">{{ __('admin.products.app_hosting_hint') }} &mdash; {{ __('admin.products.app_needs_containers') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.products.customer_picks_app') }}</td>
                        <td class="fieldarea">
                            <label style="font-weight:400;margin:0;"><input type="checkbox" name="panelica_app_choose" value="1" {{ !empty($cfg['panelica_app_choose']) ? 'checked' : '' }}> {{ __('admin.products.customer_picks_app_hint') }}</label>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.products.container_plan') }}</td>
                        <td class="fieldarea">
                            <label style="font-weight:400;margin:0;"><input type="checkbox" name="panelica_container_plan" value="1" {{ !empty($cfg['panelica_container_plan']) ? 'checked' : '' }}> {{ __('admin.products.container_plan_hint') }}</label>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.products.panelica_resources') }}</td>
                        <td class="fieldarea">
                            @php $numFields = [
                                'res_cpu_percent'=>['CPU Limit (%) &mdash; 100 = 1 core',100],'res_memory_mb'=>['RAM (MB)',1024],
                                'res_inode_quota'=>['Inode Quota (-1 = unlimited)',-1],'res_iops'=>['IOPS (0 = unlimited)',0],
                                'res_io_mbs'=>['Disk I/O (MB/s)',0],'res_disk_mb'=>['Disk (MB)',5120],
                                'res_bandwidth_mb'=>['Bandwidth (MB)',51200],'res_process_limit'=>['Max Processes',100],
                                'res_max_domains'=>['Max Websites',1],'res_max_subdomains'=>['Max Subdomains',10],
                                'res_max_email'=>['Max Email',10],'res_max_db'=>['Max Databases',5],
                                'res_max_ftp'=>['Max FTP',5],'res_max_cron'=>['Max Cron',5],
                                'res_max_containers'=>['Max Containers',0],'res_network_mbit'=>['Network (Mbit/s)',0],
                                'res_php_memory_mb'=>['PHP Memory (MB)',256],'res_php_exec'=>['PHP Exec (s)',30],
                                'res_php_upload'=>['PHP Upload (MB)',64],
                            ];
                            $resDefaults = strtolower((string) $product->server_type) === 'panelica'; @endphp
                            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;">
                                @foreach($numFields as $k => $meta)
                                <div>
                                    <label class="form-label" style="font-size:11px;">{!! $meta[0] !!}</label>
                                    <input type="number" name="{{ $k }}" value="{{ $cfg[$k] ?? ($resDefaults ? $meta[1] : '') }}" placeholder="{{ $meta[1] }}" class="form-control" style="font-size:12px;">
                                </div>
                                @endforeach
                                <div>
                                    <label class="form-label" style="font-size:11px;">{{ __('admin.products.ssh_access') }}</label>
                                    <select name="res_ssh_level" class="form-control" style="font-size:12px;">
                                        @foreach(['none','jailed','full'] as $o)<option value="{{ $o }}" {{ ($cfg['res_ssh_level'] ?? 'none')===$o?'selected':'' }}>{{ ucfirst($o) }}</option>@endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label" style="font-size:11px;">{{ __('admin.products.quota_mode') }}</label>
                                    <select name="res_quota_mode" class="form-control" style="font-size:12px;">
                                        @foreach(['strict','monitor','oversell'] as $o)<option value="{{ $o }}" {{ ($cfg['res_quota_mode'] ?? 'strict')===$o?'selected':'' }}>{{ ucfirst($o) }}</option>@endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label" style="font-size:11px;">{{ 'ModSecurity' }}</label>
                                    <select name="res_modsec" class="form-control" style="font-size:12px;">
                                        <option value="on" {{ ($cfg['res_modsec'] ?? 'on')!=='off'?'selected':'' }}>{{ __('admin.products.on') }}</option>
                                        <option value="off" {{ ($cfg['res_modsec'] ?? 'on')==='off'?'selected':'' }}>{{ __('admin.products.off') }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label" style="font-size:11px;">{{ __('admin.products.backups') }}</label>
                                    <select name="res_backup" class="form-control" style="font-size:12px;">
                                        <option value="on" {{ ($cfg['res_backup'] ?? 'on')!=='off'?'selected':'' }}>{{ __('admin.products.on') }}</option>
                                        <option value="off" {{ ($cfg['res_backup'] ?? 'on')==='off'?'selected':'' }}>{{ __('admin.products.off') }}</option>
                                    </select>
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Whether customers on this package may move to another, and to which. --}}
        <div class="tab-pane product-tab-pane" id="tab-upgrades">
            <input type="hidden" name="upgrade_paths_section" value="1">
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td class="fieldlabel" width="20%">{{ __('admin.products.allow_upgrades') }}</td>
                    <td class="fieldarea">
                        <input type="hidden" name="allow_upgrades" value="0">
                        <label style="font-weight:400;margin:0;"><input type="checkbox" name="allow_upgrades" value="1" id="allow-upgrades" @checked(old('allow_upgrades', $product->allow_upgrades ?? true))> {{ __('admin.products.allow_upgrades') }}</label>
                        <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.products.allow_upgrades_hint') }}</div>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.products.upgrade_paths') }}</td>
                    <td class="fieldarea">
                        @if(($upgradeCandidates ?? collect())->isEmpty())
                        <div style="font-size:12px;color:#777;">{{ __('admin.products.upgrade_paths_none') }}</div>
                        @else
                        @php $ticked = old('upgrade_paths_section') ? array_map('intval', (array) old('upgrade_paths', [])) : ($selectedUpgrades ?? []); @endphp
                        <div id="upgrade-paths" style="max-height:300px;overflow-y:auto;border:1px solid #ddd;background:#f9f9f9;padding:8px 12px;">
                            @foreach($upgradeCandidates->groupBy('group_id') as $candidates)
                            @if($candidates->first()->group?->name)
                            <div style="font-size:11px;font-weight:600;color:#666;text-transform:uppercase;margin:{{ $loop->first ? '2px' : '10px' }} 0 4px;">{{ $candidates->first()->group->name }}</div>
                            @endif
                            @foreach($candidates as $candidate)
                            <label style="font-weight:400;display:flex;gap:8px;align-items:flex-start;margin:0;padding:2px 0;"><input type="checkbox" name="upgrade_paths[]" value="{{ $candidate->id }}" style="margin-top:3px;" @checked(in_array((int) $candidate->id, $ticked, true))> <span>{{ $candidate->name }}@if($candidate->hidden) ({{ __('admin.products.hidden') }})@endif @if($candidate->retired) ({{ __('admin.products.retired') }})@endif</span></label>
                            @endforeach
                            @endforeach
                        </div>
                        <div style="font-size:12px;color:#777;margin-top:4px;">{{ __('admin.products.upgrade_paths_hint') }}</div>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        {{-- The domain on the order form, and a free first year with it. --}}
        <div class="tab-pane product-tab-pane" id="tab-free-domains">
            <input type="hidden" name="domain_section" value="1">
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td class="fieldlabel" width="20%">{{ __('admin.products.show_domain_options') }}</td>
                    <td class="fieldarea">
                        <label style="font-weight:400;margin:0;"><input type="checkbox" name="show_domain_options" value="1" @checked(old('show_domain_options', $product->show_domain_options))> {{ __('admin.products.show_domain_options') }}</label>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.products.free_domain') }}</td>
                    <td class="fieldarea">
                        <label style="font-weight:400;margin:0;"><input type="checkbox" name="free_domain" value="1" @checked(old('free_domain', $product->free_domain))> {{ __('admin.products.free_domain') }}</label>
                        <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.products.free_domain_hint') }}</div>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel"><label for="fd-tlds" style="margin:0;">{{ __('admin.products.free_domain_tlds') }}</label></td>
                    <td class="fieldarea">
                        <input type="text" id="fd-tlds" name="free_domain_tlds" value="{{ old('free_domain_tlds', implode(', ', $product->freeDomainTlds())) }}" class="form-control" style="max-width:420px;">
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.products.free_domain_cycles') }}</td>
                    <td class="fieldarea">
                        @php($fdChosen = (array) old('free_domain_cycles', $product->freeDomainCycles()))
                        <div style="display:flex;flex-wrap:wrap;gap:12px;">
                            @foreach(['monthly', 'quarterly', 'semiannually', 'annually', 'biennially', 'triennially'] as $fdc)
                            <label style="font-weight:400;margin:0;"><input type="checkbox" name="free_domain_cycles[]" value="{{ $fdc }}" @checked(in_array($fdc, $fdChosen, true))> {{ __('common.billing.'.str_replace('semiannually', 'semi_annually', $fdc)) }}</label>
                            @endforeach
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="btn-container" id="product-save-bar">
            <button type="submit" class="btn btn-primary">{{ __('common.actions.save_changes') }}</button>
            <a href="{{ route('admin.products.index') }}" class="btn btn-default">{{ __('common.actions.cancel') }}</a>
        </div>
    </form>

    {{-- The product's own questions, asked on its order form. Their own forms,
         outside the product form above. --}}
    @php($productFields = \App\Models\CustomField::productFields($product->id)->get())
    <div class="tab-pane product-tab-pane" id="product-fields">
        <div class="infobox">{{ __('admin.products.fields_hint') }}</div>
        @if($productFields->isNotEmpty())
        <div class="tablebg" style="margin-bottom:15px;">
            <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
                <thead><tr><th>{{ __('common.form.name') }}</th><th>{{ __('common.table.type') }}</th><th style="text-align:right;width:120px;">{{ __('common.table.actions') }}</th></tr></thead>
                <tbody>
                @foreach($productFields as $field)
                <tr>
                    <td>{{ $field->field_name }}@if($field->required) *@endif @if($field->admin_only) <span style="color:#777;font-size:12px;">({{ __('admin.products.fields_admin_only') }})</span>@endif</td>
                    <td>{{ $field->field_type }}</td>
                    <td style="text-align:right;">
                        <form method="POST" action="{{ route('admin.products.fields.destroy', [$product, $field]) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js(__('admin.products.fields_confirm_delete')))">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-xs">{{ __('common.actions.delete') }}</button>
                        </form>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <form method="POST" action="{{ route('admin.products.fields.store', $product) }}">
            @csrf
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td class="fieldlabel" width="20%"><label for="pf-name" style="margin:0;">{{ __('common.form.name') }}</label></td>
                    <td class="fieldarea"><input type="text" id="pf-name" name="field_name" required maxlength="255" class="form-control" style="max-width:320px;"></td>
                </tr>
                <tr>
                    <td class="fieldlabel"><label for="pf-type" style="margin:0;">{{ __('common.table.type') }}</label></td>
                    <td class="fieldarea">
                        <select id="pf-type" name="field_type" class="form-control" style="max-width:240px;">
                            @foreach(['text', 'textarea', 'select', 'checkbox', 'number', 'date'] as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel"><label for="pf-options" style="margin:0;">{{ __('admin.products.fields_options') }}</label></td>
                    <td class="fieldarea"><textarea id="pf-options" name="field_options" rows="2" class="form-control" style="max-width:420px;"></textarea></td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('Options') }}</td>
                    <td class="fieldarea">
                        <label style="font-weight:400;margin-right:16px;"><input type="checkbox" name="required" value="1"> {{ __('admin.products.fields_required') }}</label>
                        <label style="font-weight:400;"><input type="checkbox" name="admin_only" value="1"> {{ __('admin.products.fields_admin_only') }}</label>
                    </td>
                </tr>
            </table>
            <div class="btn-container">
                <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.products.fields_add') }}</button>
            </div>
        </form>
    </div>

    <div class="tab-pane product-tab-pane" id="tab-links">
        <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
            <tr>
                <td class="fieldlabel" width="20%">{{ __('Direct Shopping Cart Link') }}</td>
                <td class="fieldarea">
                    <input type="text" readonly class="form-control" value="{{ Route::has('client.store.configure') ? route('client.store.configure', $product) : url('/cart?pid='.$product->id) }}" onclick="this.select()">
                </td>
            </tr>
            @if($product->group_id)
            <tr>
                <td class="fieldlabel">{{ __('Product Group Cart Link') }}</td>
                <td class="fieldarea">
                    <input type="text" readonly class="form-control" value="{{ url('/store?gid='.$product->group_id) }}" onclick="this.select()">
                </td>
            </tr>
            @endif
        </table>
    </div>
</div>

<script>
(function () {
    var tabs = document.querySelectorAll('#product-edit-tabs [data-product-tab]');
    var panes = document.querySelectorAll('#product-edit-panes .product-tab-pane');
    var saveBar = document.getElementById('product-save-bar');
    function activate(id) {
        var exists = document.getElementById(id);
        if (!exists || !exists.classList.contains('product-tab-pane')) id = 'tab-details';
        tabs.forEach(function (a) {
            var on = a.getAttribute('data-product-tab') === id;
            a.classList.toggle('active', on);
            if (a.parentElement) a.parentElement.classList.toggle('active', on);
        });
        panes.forEach(function (p) { p.classList.toggle('active', p.id === id); });
        if (saveBar) saveBar.style.display = (id === 'product-fields' || id === 'tab-links') ? 'none' : '';
    }
    tabs.forEach(function (a) {
        a.addEventListener('click', function (e) {
            e.preventDefault();
            var id = a.getAttribute('data-product-tab');
            activate(id);
            if (history.replaceState) history.replaceState(null, '', '#' + id);
        });
    });
    var form = document.getElementById('product-edit-form');
    if (form) {
        form.addEventListener('invalid', function (e) {
            var pane = e.target.closest('.product-tab-pane');
            if (pane && !pane.classList.contains('active')) activate(pane.id);
        }, true);
    }
    if (window.location.hash) activate(window.location.hash.slice(1));
})();
</script>
@endsection

@push('scripts')
<script>
// Each module's settings card shows only for that module. The Panelica card
// used to be on every product, so a Proxmox product was asked for PHP limits
// and mailbox counts it would never use.
(function () {
    var moduleSelect = document.querySelector('select[name="server_type"]');
    var typeSelect = document.querySelector('select[name="type"]');
    var panelica = document.querySelector('[data-module-card="panelica"]');
    function showCards() {
        var m = moduleSelect ? moduleSelect.value : '';
        // The server module select is hidden for an SSL certificate, but it
        // still carries its old value: the Panelica card must not appear there.
        var isSsl = typeSelect ? typeSelect.value === 'ssl' : false;
        if (panelica) {
            var on = ! isSsl && (m === '' || m === 'custom' || m === 'panelica');
            panelica.style.display = on ? '' : 'none';
            panelica.querySelectorAll('input,select,textarea').forEach(function (f) { f.disabled = !on; });
        }
        if (window.pnlcsProxmoxCard) { window.pnlcsProxmoxCard.show(m === 'proxmox'); }
        document.querySelectorAll('[data-custom-module-card]').forEach(function (card) {
            var active = ! isSsl && card.getAttribute('data-custom-module-card') === m;
            card.style.display = active ? '' : 'none';
            card.querySelectorAll('input,select,textarea').forEach(function (f) { f.disabled = !active; });
        });
        // Proxmox and BDPBX configure resources in their own cards rather than a remote plan dropdown.
        var pkg = document.getElementById('package-group');
        if (pkg) { pkg.style.display = (m === 'proxmox' || m === 'bdpbx') ? 'none' : ''; }
    }
    if (moduleSelect) { moduleSelect.addEventListener('change', showCards); }
    if (typeSelect) { typeSelect.addEventListener('change', showCards); }
    document.addEventListener('DOMContentLoaded', showCards);
})();
(function () {
    var moduleSelect = document.querySelector('select[name="server_type"]');
    var packageSelect = document.getElementById('package-select');
    var note = document.getElementById('package-note');
    if (! moduleSelect || ! packageSelect) { return; }

    moduleSelect.addEventListener('change', function () {
        var chosen = packageSelect.value;
        packageSelect.innerHTML = '<option value="">@lang('admin.products.package_loading')</option>';

        fetch('{{ route('admin.products.packages') }}?module=' + encodeURIComponent(moduleSelect.value), {
            headers: { 'Accept': 'application/json' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            packageSelect.innerHTML = '<option value="">@lang('admin.products.package_default')</option>';
            (data.packages || []).forEach(function (p) {
                var o = document.createElement('option');
                o.value = p.id;
                o.textContent = p.name;
                if (p.id === chosen) { o.selected = true; }
                packageSelect.appendChild(o);
            });
            note.textContent = data.error || '@lang('admin.products.package_hint')';
        })
        .catch(function () {
            packageSelect.innerHTML = '<option value="">@lang('admin.products.package_default')</option>';
            note.textContent = '@lang('admin.products.package_list_unreachable')';
        });
    });
})();
</script>
@endpush
