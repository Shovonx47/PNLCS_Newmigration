@extends("admin.layouts.app")
@section("title", __("admin.configurable_options"))
@section("content")

<h1>{{ __('admin.config_options.title') }}</h1>

<div class="context-btn-container" style="margin-bottom:12px;">
    <button type="button" onclick="document.getElementById('modal-add-group').style.display='flex'" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> {{ __('admin.config_options.add_group') }}</button>
</div>

@if(($groups ?? collect())->isEmpty())
<div class="infobox">{{ __('admin.config_options.no_groups_message') }}</div>
@else
@foreach($groups as $group)
<div style="border:1px solid #dcdcdc;background:#fff;margin-bottom:16px;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:9px 14px;background:#eef2f7;cursor:pointer;" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'none' ? 'block' : 'none'">
        <div>
            <strong style="color:#1a4d80;">{{ $group->name }}</strong>
            @if($group->description)
            <span style="color:#666;font-size:12px;margin-left:8px;">{{ $group->description }}</span>
            @endif
            <span class="whmcs-status active" style="margin-left:8px;">{{ $group->options->count() }} {{ __('admin.config_options.options_count') }}</span>
        </div>
        <div style="display:flex;gap:6px;">
            <button type="button" onclick="event.stopPropagation(); document.getElementById('modal-edit-group-{{ $group->id }}').style.display='flex'" class="btn btn-default btn-xs">{{ __('common.actions.edit') }}</button>
            <form method="POST" action="{{ route('admin.config.config-option-groups.destroy', $group->id) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js(__('admin.config_options.delete_group_cascade', ['name' => $group->name])), {title: @js(__('admin.config_options.confirm_delete_group')), danger: true})">
                @csrf @method("DELETE")
                <button type="submit" class="btn btn-danger btn-xs" onclick="event.stopPropagation();">{{ __('common.actions.delete') }}</button>
            </form>
        </div>
    </div>
    <div style="padding:12px;">
        <form method="POST" action="{{ route('admin.config.config-option-groups.link', $group->id) }}" style="margin-bottom:12px;">
            @csrf
            @php $linked = $group->productLinks->pluck('product_id')->all(); @endphp
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td class="fieldlabel" width="22%">{{ __('admin.config_options.applies_to_products') }}</td>
                    <td class="fieldarea">
                        <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
                            <select name="product_ids[]" multiple size="4" class="form-control" style="max-width:420px;font-size:12px;">
                                @foreach($products ?? [] as $p)
                                <option value="{{ $p->id }}" @selected(in_array($p->id, $linked, true))>{{ $p->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-default btn-sm">{{ __('common.actions.save') }}</button>
                        </div>
                    </td>
                </tr>
            </table>
        </form>

        <div class="tablebg" style="margin-bottom:12px;">
            <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
                <thead>
                    <tr>
                        <th>{{ __('admin.config_options.option_name') }}</th>
                        <th>{{ __('common.table.type') }}</th>
                        <th>{{ __('admin.config_options.sub_options') }}</th>
                        <th style="text-align:right;width:180px;">{{ __('common.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($group->options as $option)
                    <tr>
                        <td style="font-weight:600;">{{ $option->option_name }}</td>
                        <td style="text-transform:capitalize;">{{ $option->option_type }}</td>
                        <td>
                            @if($option->subs->isEmpty())
                            <span style="color:#777;font-size:12px;">{{ __('admin.config_options.none') }}</span>
                            @else
                            @foreach($option->subs as $sub)
                            <span style="display:inline-flex;align-items:center;gap:4px;background:#eef2f7;padding:2px 8px;border-radius:3px;font-size:12px;margin:1px;">
                                {{ $sub->option_name }}
                                <form method="POST" action="{{ route('admin.config.config-option-subs.destroy', $sub->id) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js(__('admin.config_options.confirm_delete_sub')))">
                                    @csrf @method("DELETE")
                                    <button type="submit" style="background:none;border:none;color:#d9534f;cursor:pointer;font-size:11px;padding:0;">&times;</button>
                                </form>
                            </span>
                            @endforeach
                            @endif
                        </td>
                        <td style="text-align:right;white-space:nowrap;">
                            <button type="button" onclick="document.getElementById('modal-add-sub-{{ $option->id }}').style.display='flex'" class="btn btn-success btn-xs">+ {{ __('admin.config_options.add_sub') }}</button>
                            <form method="POST" action="{{ route('admin.config.config-options.destroy', $option->id) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js($option->option_name), {title: @js(__('admin.config_options.confirm_delete_option')), danger: true})">
                                @csrf @method("DELETE")
                                <button type="submit" class="btn btn-danger btn-xs">{{ __('common.actions.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align:center;color:#777;padding:15px;">{{ __('admin.config_options.no_options') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('admin.config.config-options.store') }}">
            @csrf
            <input type="hidden" name="group_id" value="{{ $group->id }}">
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td class="fieldlabel" width="22%">{{ __('admin.config_options.add_option') }}</td>
                    <td class="fieldarea">
                        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                            <input type="text" name="option_name" required class="form-control" style="max-width:260px;" placeholder="{{ __('admin.config_options.option_name') }}">
                            <select name="option_type" class="form-control" style="max-width:180px;">
                                <option value="dropdown">{{ __('admin.config_options.type_dropdown') }}</option>
                                <option value="radio">{{ __('admin.config_options.type_radio') }}</option>
                                <option value="checkbox">{{ __('admin.config_options.type_checkbox') }}</option>
                                <option value="quantity">{{ __('admin.config_options.type_quantity') }}</option>
                                <option value="text">{{ __('admin.config_options.type_text') }}</option>
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.config_options.add_option') }}</button>
                        </div>
                    </td>
                </tr>
            </table>
        </form>
    </div>
</div>

{{-- Edit Group Modal --}}
<div id="modal-edit-group-{{ $group->id }}" style="display:none;position:fixed;inset:0;z-index:1050;align-items:center;justify-content:center;">
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);" onclick="this.parentElement.style.display='none'"></div>
    <div style="position:relative;background:#fff;border-radius:4px;width:480px;max-width:95%;box-shadow:0 5px 30px rgba(0,0,0,0.3);">
        <div style="padding:12px 16px;border-bottom:1px solid #e5e5e5;display:flex;align-items:center;justify-content:space-between;">
            <h4 style="margin:0;font-size:15px;">{{ __('admin.config_options.edit_group') }}</h4>
            <button type="button" onclick="this.closest('[id^=modal]').style.display='none'" style="background:none;border:none;font-size:22px;cursor:pointer;color:#777;">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.config.config-option-groups.update', $group->id) }}">
            @csrf @method("PUT")
            <div style="padding:15px;">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="32%">{{ __('admin.config_options.group_name') }}</td>
                        <td class="fieldarea"><input type="text" name="name" value="{{ $group->name }}" required class="form-control"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('common.form.description') }}</td>
                        <td class="fieldarea"><textarea name="description" class="form-control" rows="2">{{ $group->description }}</textarea></td>
                    </tr>
                </table>
            </div>
            <div class="btn-container" style="margin:0;padding:10px 15px;border-top:1px solid #e5e5e5;text-align:right;">
                <button type="button" onclick="this.closest('[id^=modal]').style.display='none'" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.config_options.update_group') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Add Sub-Option Modals --}}
@foreach($group->options as $option)
<div id="modal-add-sub-{{ $option->id }}" style="display:none;position:fixed;inset:0;z-index:1050;align-items:center;justify-content:center;">
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);" onclick="this.parentElement.style.display='none'"></div>
    <div style="position:relative;background:#fff;border-radius:4px;width:520px;max-width:95%;box-shadow:0 5px 30px rgba(0,0,0,0.3);">
        <div style="padding:12px 16px;border-bottom:1px solid #e5e5e5;display:flex;align-items:center;justify-content:space-between;">
            <h4 style="margin:0;font-size:15px;">{{ __('admin.config_options.add_sub_option_to') }} "{{ $option->option_name }}"</h4>
            <button type="button" onclick="this.closest('[id^=modal]').style.display='none'" style="background:none;border:none;font-size:22px;cursor:pointer;color:#777;">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.config.config-option-subs.store') }}">
            @csrf
            <input type="hidden" name="config_id" value="{{ $option->id }}">
            <div style="padding:15px;">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="32%">{{ __('admin.config_options.sub_option_name') }}</td>
                        <td class="fieldarea"><input type="text" name="option_name" required class="form-control" placeholder="{{ __('admin.config_options.sub_option_placeholder') }}"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.config_options.pricing') }}</td>
                        <td class="fieldarea">
                            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;">
                                <div><label style="font-size:11px;display:block;">{{ __('admin.config_options.price_monthly') }}</label><input type="number" step="0.01" min="0" name="monthly" value="0" class="form-control"></div>
                                <div><label style="font-size:11px;display:block;">{{ __('admin.config_options.price_quarterly') }}</label><input type="number" step="0.01" min="0" name="quarterly" value="0" class="form-control"></div>
                                <div><label style="font-size:11px;display:block;">{{ __('admin.config_options.price_semiannually') }}</label><input type="number" step="0.01" min="0" name="semiannually" value="0" class="form-control"></div>
                                <div><label style="font-size:11px;display:block;">{{ __('admin.config_options.price_annually') }}</label><input type="number" step="0.01" min="0" name="annually" value="0" class="form-control"></div>
                                <div><label style="font-size:11px;display:block;">{{ __('admin.config_options.price_biennially') }}</label><input type="number" step="0.01" min="0" name="biennially" value="0" class="form-control"></div>
                                <div><label style="font-size:11px;display:block;">{{ __('admin.config_options.price_triennially') }}</label><input type="number" step="0.01" min="0" name="triennially" value="0" class="form-control"></div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.config_options.sort_order') }}</td>
                        <td class="fieldarea"><input type="number" name="sort_order" value="0" class="form-control" style="max-width:120px;"></td>
                    </tr>
                </table>
            </div>
            <div class="btn-container" style="margin:0;padding:10px 15px;border-top:1px solid #e5e5e5;text-align:right;">
                <button type="button" onclick="this.closest('[id^=modal]').style.display='none'" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.config_options.add_sub_option') }}</button>
            </div>
        </form>
    </div>
</div>
@endforeach

@endforeach
@endif

{{-- Add Group Modal --}}
<div id="modal-add-group" style="display:none;position:fixed;inset:0;z-index:1050;align-items:center;justify-content:center;">
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);" onclick="document.getElementById('modal-add-group').style.display='none'"></div>
    <div style="position:relative;background:#fff;border-radius:4px;width:480px;max-width:95%;box-shadow:0 5px 30px rgba(0,0,0,0.3);">
        <div style="padding:12px 16px;border-bottom:1px solid #e5e5e5;display:flex;align-items:center;justify-content:space-between;">
            <h4 style="margin:0;font-size:15px;">{{ __('admin.config_options.add_config_option_group') }}</h4>
            <button type="button" onclick="document.getElementById('modal-add-group').style.display='none'" style="background:none;border:none;font-size:22px;cursor:pointer;color:#777;">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.config.config-option-groups.store') }}">
            @csrf
            <div style="padding:15px;">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="32%">{{ __('admin.config_options.group_name') }}</td>
                        <td class="fieldarea"><input type="text" name="name" required class="form-control" placeholder="{{ __('admin.config_options.group_name_placeholder') }}"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.config_options.description_optional') }}</td>
                        <td class="fieldarea"><textarea name="description" class="form-control" rows="2" placeholder="{{ __('admin.config_options.description_placeholder') }}"></textarea></td>
                    </tr>
                </table>
            </div>
            <div class="btn-container" style="margin:0;padding:10px 15px;border-top:1px solid #e5e5e5;text-align:right;">
                <button type="button" onclick="document.getElementById('modal-add-group').style.display='none'" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.config_options.create_group') }}</button>
            </div>
        </form>
    </div>
</div>

@endsection
