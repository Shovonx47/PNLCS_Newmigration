@extends('admin.layouts.app')
@section('title', __('admin.billable_items.title'))
@section('content')

<h1>{{ __('admin.billable_items.title') }}</h1>

@if($errors->any())
<div class="errorbox">
    <ul style="margin:0;padding-left:18px;">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<div class="context-btn-container">
    <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
        <a href="{{ route('admin.config.billable-items') }}" class="btn {{ empty($currentFilter) ? 'btn-primary' : 'btn-default' }} btn-xs">{{ 'List All' }}</a>
        <a href="{{ route('admin.config.billable-items', ['status' => 'uninvoiced']) }}" class="btn {{ ($currentFilter ?? '') === 'uninvoiced' ? 'btn-primary' : 'btn-default' }} btn-xs">{{ 'Uninvoiced Items' }}</a>
        <a href="{{ route('admin.config.billable-items', ['status' => 'recurring']) }}" class="btn {{ ($currentFilter ?? '') === 'recurring' ? 'btn-primary' : 'btn-default' }} btn-xs">{{ 'Recurring Items' }}</a>
    </div>
    <button type="button" onclick="document.getElementById('modal-add-bi').style.display='flex'" class="btn btn-primary btn-sm">
        <i class="fas fa-plus"></i> {{ __('admin.billable_items.add_item') }}
    </button>
</div>

@if(($billableItems ?? collect())->isEmpty())
    <div class="contentbox">{{ __('admin.billable_items.no_items') }}</div>
@else
    <div class="tablebg">
        <table class="datatable">
            <thead>
                <tr>
                    <th class="text-left">{{ __('common.table.description') }}</th>
                    <th>{{ __('common.table.amount') }}</th>
                    <th>{{ __('common.table.type') }}</th>
                    <th>{{ __('common.table.actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($billableItems as $item)
                <tr>
                    <td><strong>{{ $item->description }}</strong></td>
                    <td style="text-align:center;">{{ money_fmt($item->amount) }}</td>
                    <td style="text-align:center;text-transform:capitalize;">{{ $item->type ?? 'standard' }}</td>
                    <td style="text-align:center;">
                        <form method="POST" action="{{ route('admin.config.billable-items.destroy', $item) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js(__('admin.billable_items.confirm_delete')))">
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

<div id="modal-add-bi" style="display:{{ request('action') === 'add' ? 'flex' : 'none' }};position:fixed;inset:0;z-index:1050;align-items:center;justify-content:center;">
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);" onclick="document.getElementById('modal-add-bi').style.display='none'"></div>
    <div style="position:relative;background:#fff;border-radius:4px;width:480px;max-width:95%;box-shadow:0 5px 30px rgba(0,0,0,0.3);overflow:hidden;">
        <div style="padding:12px 16px;background:#1a4d80;color:#fff;display:flex;align-items:center;justify-content:space-between;">
            <h4 style="margin:0;font-size:15px;color:#fff;">{{ __('admin.billable_items.add_billable_item') }}</h4>
            <button type="button" onclick="document.getElementById('modal-add-bi').style.display='none'" style="background:none;border:none;font-size:20px;cursor:pointer;color:#fff;">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.config.billable-items.store') }}">
            @csrf
            <div style="padding:15px;">
                <table class="form" style="margin:0;">
                    <tr>
                        <td class="fieldlabel">{{ __('common.table.client') }}</td>
                        <td class="fieldarea">
                            <select name="client_id" required class="form-control">
                                @foreach($clients ?? [] as $c)
                                <option value="{{ $c->id }}" @selected(old('client_id') == $c->id)>{{ trim($c->first_name.' '.$c->last_name) }}{{ $c->company_name ? ' — '.$c->company_name : '' }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('common.form.description') }}</td>
                        <td class="fieldarea"><input type="text" name="description" required class="form-control"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.billable_items.amount') }}</td>
                        <td class="fieldarea"><input type="number" name="amount" step="0.01" required class="form-control input-150"></td>
                    </tr>
                </table>
            </div>
            <div style="padding:10px 15px;background:#f5f5f5;border-top:1px solid #ddd;display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('modal-add-bi').style.display='none'" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.billable_items.add_item') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
