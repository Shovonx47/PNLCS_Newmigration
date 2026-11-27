@extends('admin.layouts.app')
@section('title', __('admin.downloads.edit_download'))
@section('content')

<h1>{{ __('admin.downloads.edit_download') }}</h1>

<div class="context-btn-container">
    <strong>{{ $download->title }}</strong>
    <a href="{{ route('admin.config.downloads') }}" class="btn btn-default btn-sm">&laquo; {{ __('admin.downloads.title') }}</a>
</div>

@if($errors->any())
<div class="errorbox">
    <ul style="margin:0;padding-left:18px;">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('admin.config.downloads.update', $download) }}" enctype="multipart/form-data">
    @csrf @method('PUT')
    <table class="form">
        <tr>
            <td class="fieldlabel">{{ __('common.form.name') }}</td>
            <td class="fieldarea"><input type="text" name="title" value="{{ old('title', $download->title) }}" required class="form-control input-400"></td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ __('admin.downloads.category') }}</td>
            <td class="fieldarea">
                <select name="category_id" required class="form-control input-300">
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" @selected(old('category_id', $download->category_id) == $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel label-top">{{ __('common.form.description') }}</td>
            <td class="fieldarea"><textarea name="description" rows="3" class="form-control">{{ old('description', $download->description) }}</textarea></td>
        </tr>
        <tr>
            <td class="fieldlabel label-top">{{ $download->isStoredFile() ? __('admin.downloads.replace_file') : __('admin.downloads.upload_file') }}</td>
            <td class="fieldarea">
                @if($download->isStoredFile())
                <div style="font-size:12px;font-family:monospace;margin-bottom:6px;">{{ $download->fileName() }}</div>
                @endif
                <input type="file" name="file" class="form-control input-400">
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ __('admin.downloads.or_link') }}</td>
            <td class="fieldarea">
                <input type="text" name="location" value="{{ old('location', $download->isStoredFile() ? '' : $download->location) }}" class="form-control input-400">
                <div style="font-size:11px;color:#777;margin-top:3px;">{{ __('admin.downloads.file_or_link_hint') }}</div>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel label-top">{{ __('admin.downloads.products') }}</td>
            <td class="fieldarea">
                @php $chosen = (array) old('products', $download->products->pluck('id')->all()); @endphp
                <select name="products[]" multiple class="form-control input-400" style="height:110px;">
                    @foreach($products as $p)
                    <option value="{{ $p->id }}" @selected(in_array($p->id, $chosen))>{{ $p->name }}</option>
                    @endforeach
                </select>
                <div style="font-size:11px;color:#777;margin-top:3px;">{{ __('admin.downloads.products_hint') }}</div>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ __('admin.downloads.published') }}</td>
            <td class="fieldarea">
                <label style="display:inline-flex;align-items:center;gap:6px;"><input type="checkbox" name="published" value="1" @checked(old('published', ! $download->hidden))> {{ __('admin.downloads.published') }}</label>
            </td>
        </tr>
    </table>
    <div class="btn-container">
        <a href="{{ route('admin.config.downloads') }}" class="btn btn-default">{{ __('common.actions.cancel') }}</a>
        <button type="submit" class="btn btn-primary">{{ __('common.actions.save') }}</button>
    </div>
</form>
@endsection
