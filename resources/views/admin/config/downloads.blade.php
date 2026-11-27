@extends('admin.layouts.app')
@section('title', __('admin.downloads.title'))
@section('content')

<h1>{{ __('admin.downloads.title') }}</h1>

<div class="context-btn-container">
    <span class="textgrey">{{ __('admin.downloads.title') }}</span>
    <div style="display:flex;gap:6px;">
        <button type="button" onclick="document.getElementById('modal-add-dlcat').style.display='flex'" class="btn btn-default btn-sm">+ {{ __('admin.downloads.add_category') }}</button>
        <button type="button" onclick="document.getElementById('modal-add-dl').style.display='flex'" class="btn btn-primary btn-sm">+ {{ __('admin.downloads.add_download') }}</button>
    </div>
</div>

@if($errors->any())
<div class="errorbox">
    <ul style="margin:0;padding-left:18px;">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

@if(($categories ?? collect())->isEmpty())
    <div class="contentbox">{{ __('admin.downloads.no_categories') }}</div>
@else
    @foreach($categories as $category)
    <div class="panel" style="margin-bottom:16px;">
        <div class="panel-heading" style="display:flex;align-items:center;justify-content:space-between;">
            <strong><i class="far fa-folder-open"></i> {{ $category->name }}</strong>
            <div style="display:flex;gap:6px;align-items:flex-start;">
                <details>
                    <summary class="btn btn-default btn-xs" style="list-style:none;">{{ __('common.actions.edit') }}</summary>
                    <form method="POST" action="{{ route('admin.config.downloads.categories.update', $category) }}" style="display:flex;gap:6px;margin-top:6px;">
                        @csrf @method('PUT')
                        <input type="text" name="name" value="{{ $category->name }}" required class="form-control" style="font-size:12px;">
                        <input type="hidden" name="description" value="{{ $category->description }}">
                        <button type="submit" class="btn btn-primary btn-xs">{{ __('common.actions.save') }}</button>
                    </form>
                </details>
                <form method="POST" action="{{ route('admin.config.downloads.categories.destroy', $category) }}" onsubmit="return pnConfirm(event, @js(__('admin.downloads.confirm_delete_category')))">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-xs">{{ __('admin.downloads.delete_category') }}</button>
                </form>
            </div>
        </div>
        @if(($category->downloads ?? collect())->isEmpty())
            <div class="panel-body textgrey">{{ __('admin.downloads.no_downloads') }}</div>
        @else
            <div class="tablebg" style="margin:0;">
                <table class="datatable" style="margin:0;">
                    <thead>
                        <tr>
                            <th class="text-left">{{ __('admin.downloads.filename') }}</th>
                            <th class="text-left">{{ __('common.table.description') }}</th>
                            <th class="text-left">{{ __('admin.downloads.file_url') }}</th>
                            <th>{{ __('admin.downloads.downloads_count') }}</th>
                            <th>{{ __('admin.downloads.published') }}</th>
                            <th>{{ __('common.table.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($category->downloads as $dl)
                        <tr>
                            <td>
                                <strong>{{ $dl->title }}</strong>
                                @if($dl->products->isNotEmpty())
                                    <div style="font-size:11px;color:#777;">{{ __('admin.downloads.only_for', ['products' => $dl->products->pluck('name')->implode(', ')]) }}</div>
                                @endif
                            </td>
                            <td>{{ Str::limit($dl->description, 60) }}</td>
                            <td style="font-family:monospace;font-size:12px;">@if($dl->isStoredFile()){{ $dl->fileName() }} <span style="font-family:inherit;color:#777;">({{ __('admin.downloads.stored_file') }})</span>@else{{ Str::limit($dl->location, 40) }}@endif</td>
                            <td style="text-align:center;">{{ $dl->download_count ?? 0 }}</td>
                            <td style="text-align:center;"><span class="whmcs-status {{ $dl->hidden ? 'draft' : 'active' }}">{{ $dl->hidden ? __('admin.downloads.draft') : __('admin.downloads.published') }}</span></td>
                            <td style="text-align:center;white-space:nowrap;">
                                <a href="{{ route('admin.config.downloads.edit', $dl) }}" class="btn btn-default btn-xs">{{ __('common.actions.edit') }}</a>
                                <form method="POST" action="{{ route('admin.config.downloads.destroy', $dl) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js(__('admin.downloads.confirm_delete_download')))">
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
    </div>
    @endforeach
@endif

<div id="modal-add-dlcat" style="display:none;position:fixed;inset:0;z-index:1050;align-items:center;justify-content:center;">
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);" onclick="document.getElementById('modal-add-dlcat').style.display='none'"></div>
    <div style="position:relative;background:#fff;border-radius:4px;width:440px;max-width:95%;box-shadow:0 5px 30px rgba(0,0,0,0.3);overflow:hidden;">
        <div style="padding:12px 16px;background:#1a4d80;color:#fff;display:flex;align-items:center;justify-content:space-between;">
            <h4 style="margin:0;font-size:15px;color:#fff;">{{ __('admin.downloads.add_category') }}</h4>
            <button type="button" onclick="document.getElementById('modal-add-dlcat').style.display='none'" style="background:none;border:none;font-size:20px;cursor:pointer;color:#fff;">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.config.downloads.categories.store') }}">
            @csrf
            <div style="padding:15px;">
                <table class="form" style="margin:0;">
                    <tr><td class="fieldlabel">{{ __('admin.downloads.category_name') }}</td><td class="fieldarea"><input type="text" name="name" required class="form-control"></td></tr>
                    <tr><td class="fieldlabel label-top">{{ __('common.form.description') }}</td><td class="fieldarea"><textarea name="description" rows="2" class="form-control"></textarea></td></tr>
                </table>
            </div>
            <div style="padding:10px 15px;background:#f5f5f5;border-top:1px solid #ddd;display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('modal-add-dlcat').style.display='none'" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.downloads.add_category') }}</button>
            </div>
        </form>
    </div>
</div>

<div id="modal-add-dl" style="display:none;position:fixed;inset:0;z-index:1050;align-items:center;justify-content:center;">
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);" onclick="document.getElementById('modal-add-dl').style.display='none'"></div>
    <div style="position:relative;background:#fff;border-radius:4px;width:540px;max-width:95%;box-shadow:0 5px 30px rgba(0,0,0,0.3);overflow:hidden;">
        <div style="padding:12px 16px;background:#1a4d80;color:#fff;display:flex;align-items:center;justify-content:space-between;">
            <h4 style="margin:0;font-size:15px;color:#fff;">{{ __('admin.downloads.add_download') }}</h4>
            <button type="button" onclick="document.getElementById('modal-add-dl').style.display='none'" style="background:none;border:none;font-size:20px;cursor:pointer;color:#fff;">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.config.downloads.store') }}" enctype="multipart/form-data">
            @csrf
            <div style="padding:15px;max-height:75vh;overflow-y:auto;">
                <table class="form" style="margin:0;">
                    <tr><td class="fieldlabel">{{ __('common.form.name') }}</td><td class="fieldarea"><input type="text" name="title" value="{{ old('title') }}" required class="form-control"></td></tr>
                    <tr><td class="fieldlabel">{{ __('admin.downloads.category') }}</td><td class="fieldarea">
                        <select name="category_id" required class="form-control">
                            @foreach($categories ?? [] as $cat)<option value="{{ $cat->id }}" @selected(old('category_id') == $cat->id)>{{ $cat->name }}</option>@endforeach
                        </select>
                    </td></tr>
                    <tr><td class="fieldlabel label-top">{{ __('common.form.description') }}</td><td class="fieldarea"><textarea name="description" rows="2" class="form-control">{{ old('description') }}</textarea></td></tr>
                    <tr><td class="fieldlabel">{{ __('admin.downloads.upload_file') }}</td><td class="fieldarea"><input type="file" name="file" class="form-control"></td></tr>
                    <tr><td class="fieldlabel">{{ __('admin.downloads.or_link') }}</td><td class="fieldarea">
                        <input type="text" name="location" value="{{ old('location') }}" class="form-control" placeholder="{{ 'https://...' }}">
                        <div style="font-size:11px;color:#777;margin-top:3px;">{{ __('admin.downloads.file_or_link_hint') }}</div>
                    </td></tr>
                    <tr><td class="fieldlabel label-top">{{ __('admin.downloads.products') }}</td><td class="fieldarea">
                        <select name="products[]" multiple class="form-control" style="height:85px;">
                            @foreach($products ?? [] as $p)<option value="{{ $p->id }}" @selected(in_array($p->id, (array) old('products', [])))>{{ $p->name }}</option>@endforeach
                        </select>
                        <div style="font-size:11px;color:#777;margin-top:3px;">{{ __('admin.downloads.products_hint') }}</div>
                    </td></tr>
                    <tr><td class="fieldlabel">{{ __('admin.downloads.published') }}</td><td class="fieldarea"><label style="display:inline-flex;align-items:center;gap:6px;"><input type="checkbox" name="published" value="1" checked> {{ __('admin.downloads.published') }}</label></td></tr>
                </table>
            </div>
            <div style="padding:10px 15px;background:#f5f5f5;border-top:1px solid #ddd;display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('modal-add-dl').style.display='none'" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.downloads.add_download') }}</button>
            </div>
        </form>
    </div>
</div>

@if($errors->any())
<script>document.getElementById('modal-add-dl').style.display = 'flex';</script>
@endif
@endsection
