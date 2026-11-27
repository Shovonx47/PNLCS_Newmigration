@extends("admin.layouts.app")
@section("title", __("admin.client_groups"))
@section("content")

<h1>{{ __('admin.client_groups.title') }}</h1>

<div class="infobox">
    {{ __('admin.client_groups.description') }}
</div>

<div class="context-btn-container">
    <span class="textgrey">{{ __('admin.client_groups.title') }}</span>
    <button type="button" onclick="openModal('add-group')" class="btn btn-primary btn-sm">
        <i class="fas fa-plus"></i> {{ __('admin.client_groups.add_group') }}
    </button>
</div>

@if(($groups ?? collect())->isEmpty())
    <div class="contentbox">{{ __('admin.client_groups.no_groups') }}</div>
@else
    <div class="tablebg">
        <table class="datatable">
            <thead>
                <tr>
                    <th class="text-left">{{ __('admin.client_groups.group_name') }}</th>
                    <th>{{ __('admin.client_groups.color') }}</th>
                    <th>{{ __('admin.client_groups.clients') }}</th>
                    <th>{{ __('admin.client_groups.discount_pct') }}</th>
                    <th>{{ 'Separate Invoices' }}</th>
                    <th>{{ __('common.table.actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($groups as $group)
                <tr>
                    <td><strong>{{ $group->name }}</strong></td>
                    <td style="text-align:center;"><span style="display:inline-block;width:24px;height:16px;border-radius:3px;border:1px solid #ccc;background:{{ $group->color ?? '#ccc' }};vertical-align:middle;"></span></td>
                    <td style="text-align:center;">{{ $group->clients_count ?? 0 }}</td>
                    <td style="text-align:center;">{{ $group->discount_percent ?? 0 }}%</td>
                    <td style="text-align:center;"><span class="whmcs-status {{ $group->separate_invoices ? 'active' : 'cancelled' }}">{{ $group->separate_invoices ? __('common.yes') : __('common.no') }}</span></td>
                    <td style="text-align:center;white-space:nowrap;">
                        <button type="button" onclick="openModal('edit-group-{{ $group->id }}')" class="btn btn-default btn-xs">{{ __('common.actions.edit') }}</button>
                        <form method="POST" action="{{ route("admin.config.client-groups.destroy", $group) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js(__('admin.client_groups.confirm_delete')))">@csrf @method("DELETE")<button type="submit" class="btn btn-danger btn-xs">{{ __('common.actions.delete') }}</button></form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif

@foreach($groups as $group)
<x-modal name="edit-group-{{ $group->id }}" title="{{ __('admin.client_groups.edit_group') }}" maxWidth="md">
    <form method="POST" action="{{ route('admin.config.client-groups.update', $group) }}">
        @csrf @method('PUT')
        <table class="form">
            <tr>
                <td class="fieldlabel">{{ __('admin.client_groups.group_name') }}</td>
                <td class="fieldarea"><input type="text" name="name" required class="form-control" value="{{ $group->name }}"></td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ __('admin.client_groups.color') }}</td>
                <td class="fieldarea"><input type="color" name="color" value="{{ $group->color ?: '#405189' }}" style="height:32px;width:64px;padding:2px;"></td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ __('admin.client_groups.discount_pct') }}</td>
                <td class="fieldarea"><input type="number" name="discount_percent" value="{{ $group->discount_percent }}" min="0" max="100" step="0.01" class="form-control input-100"> %</td>
            </tr>
            <tr>
                <td class="fieldlabel label-top">{{ __('common.table.status') }}</td>
                <td class="fieldarea">
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;"><input type="hidden" name="suspend_exempt" value="0"><input type="checkbox" name="suspend_exempt" value="1" @checked($group->suspend_exempt)> {{ __('admin.client_groups.suspend_exempt') }}</label>
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;margin-top:4px;"><input type="hidden" name="terminate_exempt" value="0"><input type="checkbox" name="terminate_exempt" value="1" @checked($group->terminate_exempt)> {{ __('admin.client_groups.terminate_exempt') }}</label>
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;margin-top:4px;"><input type="hidden" name="separate_invoices" value="0"><input type="checkbox" name="separate_invoices" value="1" @checked($group->separate_invoices)> {{ 'Separate Renewal Invoices' }}</label>
                </td>
            </tr>
        </table>
        <div class="btn-container">
            <button type="button" onclick="closeModal('edit-group-{{ $group->id }}')" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
            <button type="submit" class="btn btn-primary btn-sm">{{ __('common.actions.save_changes') }}</button>
        </div>
    </form>
</x-modal>
@endforeach

<x-modal name="add-group" title="{{ __('admin.client_groups.add_group') }}" maxWidth="md">
    <form method="POST" action="{{ route("admin.config.client-groups.store") }}">
        @csrf
        <table class="form">
            <tr>
                <td class="fieldlabel">{{ __('admin.client_groups.group_name') }}</td>
                <td class="fieldarea"><input type="text" name="name" required class="form-control" placeholder="{{ __('admin.client_groups.name_placeholder') }}"></td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ __('admin.client_groups.color') }}</td>
                <td class="fieldarea"><input type="color" name="color" value="#405189" style="height:32px;width:64px;padding:2px;"></td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ __('admin.client_groups.discount_pct') }}</td>
                <td class="fieldarea"><input type="number" name="discount_percent" value="0" min="0" max="100" step="0.01" class="form-control input-100"> %</td>
            </tr>
            <tr>
                <td class="fieldlabel label-top">{{ __('common.table.status') }}</td>
                <td class="fieldarea">
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;"><input type="hidden" name="suspend_exempt" value="0"><input type="checkbox" name="suspend_exempt" value="1"> {{ __('admin.client_groups.suspend_exempt') }}</label>
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;margin-top:4px;"><input type="hidden" name="terminate_exempt" value="0"><input type="checkbox" name="terminate_exempt" value="1"> {{ __('admin.client_groups.terminate_exempt') }}</label>
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;margin-top:4px;"><input type="hidden" name="separate_invoices" value="0"><input type="checkbox" name="separate_invoices" value="1"> {{ 'Separate Renewal Invoices' }}</label>
                </td>
            </tr>
        </table>
        <div class="btn-container">
            <button type="button" onclick="closeModal('add-group')" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
            <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.client_groups.create_group') }}</button>
        </div>
    </form>
</x-modal>

@endsection
