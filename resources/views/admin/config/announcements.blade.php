@extends('admin.layouts.app')
@section('title', __('admin.announcements.title'))
@section('content')

<h1>{{ __('admin.announcements.title') }}</h1>

<div class="context-btn-container">
    <span class="textgrey">{{ __('admin.announcements.title') }}</span>
    <button type="button" onclick="openModal('add-announcement')" class="btn btn-primary btn-sm">
        <i class="fas fa-plus"></i> {{ __('admin.announcements.new_announcement') }}
    </button>
</div>

@if($errors->any())
<div class="errorbox">
    <ul style="margin:0;padding-left:18px;">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

@if(($announcements ?? collect())->isEmpty())
    <div class="contentbox">{{ __('admin.announcements.no_announcements_posted') }}</div>
@else
    <div class="tablebg">
        <table class="datatable">
            <thead>
                <tr>
                    <th>{{ __('common.table.date') }}</th>
                    <th class="text-left">{{ __('admin.announcements.title_column') }}</th>
                    <th>{{ __('admin.announcements.published') }}</th>
                    <th>{{ __('common.table.actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($announcements as $ann)
                <tr>
                    <td style="text-align:center;white-space:nowrap;">{{ $ann->date?->format(date_fmt()) ?? $ann->created_at->format(date_fmt()) }}</td>
                    <td><strong>{{ $ann->title }}</strong></td>
                    <td style="text-align:center;">
                        <span class="whmcs-status {{ $ann->published ? 'active' : 'draft' }}">{{ $ann->published ? __('admin.announcements.status_published') : __('admin.announcements.status_draft') }}</span>
                    </td>
                    <td style="text-align:center;white-space:nowrap;">
                        <button type="button" onclick="openModal('edit-ann-{{ $loop->index }}')" class="btn btn-default btn-xs">{{ __('common.actions.edit') }}</button>
                        <form method="POST" action="{{ route('admin.config.announcements.destroy', $ann) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js(__('admin.announcements.confirm_delete')))">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-xs">{{ __('common.actions.delete') }}</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @if(method_exists($announcements, 'links'))
        <div style="margin-top:10px;">{{ $announcements->links() }}</div>
    @endif
@endif

<datalist id="announcement-categories">@foreach($categories ?? [] as $category)<option value="{{ $category }}">@endforeach</datalist>

<x-modal name="add-announcement" title="{{ __('admin.announcements.new_announcement') }}" maxWidth="lg">
    <form method="POST" action="{{ route('admin.config.announcements.store') }}">
        @csrf
        <table class="form">
            <tr>
                <td class="fieldlabel">{{ __('common.form.title') }}</td>
                <td class="fieldarea"><input type="text" name="title" required class="form-control"></td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ __('admin.announcements.category') }}</td>
                <td class="fieldarea"><input type="text" name="category" maxlength="60" list="announcement-categories" class="form-control" placeholder="{{ __('admin.announcements.category_hint') }}"></td>
            </tr>
            <tr>
                <td class="fieldlabel label-top">{{ __('admin.announcements.content') }}</td>
                <td class="fieldarea"><textarea name="body" rows="6" class="form-control" required></textarea></td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ __('admin.announcements.published') }}</td>
                <td class="fieldarea">
                    <label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;margin:0;">
                        <input type="checkbox" name="published" value="1" checked>
                        <span>{{ __('admin.announcements.publish_immediately') }}</span>
                    </label>
                </td>
            </tr>
        </table>
        @include('admin.partials.translation-fields', ['languages' => $translationLanguages ?? collect(), 'model' => null,
            'fields' => ['title' => [__('common.form.title'), 'input'], 'announcement' => [__('admin.announcements.content'), 'textarea']]])
        <div class="btn-container">
            <button type="button" onclick="closeModal('add-announcement')" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
            <button type="submit" class="btn btn-primary btn-sm">{{ __('common.actions.create') }}</button>
        </div>
    </form>
</x-modal>

@foreach($announcements ?? [] as $ann)
<x-modal :name="'edit-ann-' . $loop->index" title="{{ __('admin.announcements.edit_announcement') }}" maxWidth="lg">
    <form method="POST" action="{{ route('admin.config.announcements.update', $ann) }}">
        @csrf @method('PUT')
        <table class="form">
            <tr>
                <td class="fieldlabel">{{ __('common.form.title') }}</td>
                <td class="fieldarea"><input type="text" name="title" value="{{ $ann->title }}" required class="form-control"></td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ __('admin.announcements.category') }}</td>
                <td class="fieldarea"><input type="text" name="category" value="{{ $ann->category }}" maxlength="60" list="announcement-categories" class="form-control"></td>
            </tr>
            <tr>
                <td class="fieldlabel label-top">{{ __('admin.announcements.content') }}</td>
                <td class="fieldarea"><textarea name="body" rows="6" class="form-control" required>{{ $ann->announcement }}</textarea></td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ __('admin.announcements.published') }}</td>
                <td class="fieldarea">
                    <label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;margin:0;">
                        <input type="hidden" name="published" value="0">
                        <input type="checkbox" name="published" value="1" @checked($ann->published)>
                        <span>{{ __('admin.announcements.published') }}</span>
                    </label>
                </td>
            </tr>
        </table>
        @include('admin.partials.translation-fields', ['languages' => $translationLanguages ?? collect(), 'model' => $ann,
            'fields' => ['title' => [__('common.form.title'), 'input'], 'announcement' => [__('admin.announcements.content'), 'textarea']]])
        <div class="btn-container">
            <button type="button" onclick="closeModal('edit-ann-{{ $loop->index }}')" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
            <button type="submit" class="btn btn-primary btn-sm">{{ __('common.actions.save_changes') }}</button>
        </div>
    </form>
</x-modal>
@endforeach
@endsection
