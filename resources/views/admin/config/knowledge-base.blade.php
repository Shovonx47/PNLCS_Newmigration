@extends('admin.layouts.app')
@section('title', __('admin.knowledge_base.title'))
@section('content')

<h1>{{ __('admin.knowledge_base.title') }}</h1>

<div class="context-btn-container">
    <span class="textgrey">{{ __('admin.knowledge_base.title') }}</span>
    <div style="display:flex;gap:6px;">
        <button type="button" onclick="openModal('add-kb-cat')" class="btn btn-default btn-sm">+ {{ __('admin.knowledge_base.add_category_btn') }}</button>
        <button type="button" onclick="openModal('add-kb-article')" class="btn btn-primary btn-sm">+ {{ __('admin.knowledge_base.new_article') }}</button>
    </div>
</div>

@if($errors->any())
<div class="errorbox">
    <ul style="margin:0;padding-left:18px;">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

@if(($articles ?? collect())->isEmpty())
    <div class="contentbox">{{ __('admin.knowledge_base.no_articles') }}</div>
@else
    <div class="tablebg">
        <table class="datatable">
            <thead>
                <tr>
                    <th class="text-left">{{ __('common.form.title') }}</th>
                    <th>{{ __('admin.knowledge_base.category') }}</th>
                    <th>{{ __('admin.knowledge_base.views') }}</th>
                    <th>{{ __('admin.knowledge_base.published') }}</th>
                    <th>{{ __('common.table.actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($articles as $art)
                <tr>
                    <td><strong>{{ $art->title }}</strong></td>
                    <td style="text-align:center;">{{ $art->category->name ?? __('admin.knowledge_base.uncategorized') }}</td>
                    <td style="text-align:center;">{{ $art->views ?? 0 }}</td>
                    <td style="text-align:center;"><span class="whmcs-status {{ $art->private ? 'draft' : 'active' }}">{{ $art->private ? __('admin.knowledge_base.draft') : __('admin.knowledge_base.published') }}</span></td>
                    <td style="text-align:center;white-space:nowrap;">
                        <button type="button" onclick="openModal('edit-art-{{ $loop->index }}')" class="btn btn-default btn-xs">{{ __('common.actions.edit') }}</button>
                        <form method="POST" action="{{ route('admin.config.knowledge-base.articles.destroy', $art) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js(__('admin.knowledge_base.confirm_delete_article')))">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-xs">{{ __('common.actions.delete') }}</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @if(method_exists($articles, 'links'))
        <div style="margin-top:10px;">{{ $articles->links() }}</div>
    @endif
@endif

@if(($categories ?? collect())->isNotEmpty())
<h2 style="font-size:16px;font-weight:600;color:#1a4d80;margin:20px 0 8px;">{{ __('admin.knowledge_base.categories') }}</h2>
<div class="tablebg">
    <table class="datatable">
        <thead>
            <tr>
                <th class="text-left">{{ __('admin.knowledge_base.category_name') }}</th>
                <th>{{ __('admin.knowledge_base.translations') }}</th>
                <th>{{ __('common.table.actions') }}</th>
            </tr>
        </thead>
        <tbody>
        @foreach($categories as $cat)
            <tr>
                <td><strong>{{ $cat->name }}</strong></td>
                <td style="text-align:center;">{{ $cat->translations->pluck('language')->implode(', ') ?: '—' }}</td>
                <td style="text-align:center;"><button type="button" onclick="openModal('edit-cat-{{ $loop->index }}')" class="btn btn-default btn-xs">{{ __('common.actions.edit') }}</button></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

<x-modal name="add-kb-cat" title="{{ __('admin.knowledge_base.add_kb_category') }}" maxWidth="md">
    <form method="POST" action="{{ route('admin.config.knowledge-base.categories.store') }}">
        @csrf
        <table class="form">
            <tr><td class="fieldlabel">{{ __('admin.knowledge_base.category_name') }}</td><td class="fieldarea"><input type="text" name="name" required class="form-control"></td></tr>
            <tr><td class="fieldlabel label-top">{{ __('common.form.description') }}</td><td class="fieldarea"><textarea name="description" rows="2" class="form-control"></textarea></td></tr>
        </table>
        <div class="btn-container">
            <button type="button" onclick="closeModal('add-kb-cat')" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
            <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.knowledge_base.add_category') }}</button>
        </div>
    </form>
</x-modal>

<x-modal name="add-kb-article" title="{{ __('admin.knowledge_base.new_kb_article') }}" maxWidth="xl">
    <form method="POST" action="{{ route('admin.config.knowledge-base.articles.store') }}">
        @csrf
        <table class="form">
            <tr><td class="fieldlabel">{{ __('common.form.title') }}</td><td class="fieldarea"><input type="text" name="title" required class="form-control"></td></tr>
            <tr><td class="fieldlabel">{{ __('admin.knowledge_base.category') }}</td><td class="fieldarea">
                <select name="category_id" class="form-control" required>
                    @foreach($categories ?? [] as $cat)<option value="{{ $cat->id }}">{{ $cat->name }}</option>@endforeach
                </select>
            </td></tr>
            <tr><td class="fieldlabel label-top">{{ __('admin.knowledge_base.content') }}</td><td class="fieldarea"><textarea name="article" rows="8" class="form-control" required></textarea></td></tr>
            <tr><td class="fieldlabel">{{ __('admin.knowledge_base.published') }}</td><td class="fieldarea"><label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;"><input type="checkbox" name="published" value="1" checked> <span>{{ __('admin.knowledge_base.publish_immediately') }}</span></label></td></tr>
        </table>
        @include('admin.partials.translation-fields', ['languages' => $translationLanguages ?? collect(), 'model' => null,
            'fields' => ['title' => [__('common.form.title'), 'input'], 'article' => [__('admin.knowledge_base.content'), 'textarea']]])
        <div class="btn-container">
            <button type="button" onclick="closeModal('add-kb-article')" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
            <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.knowledge_base.create_article') }}</button>
        </div>
    </form>
</x-modal>

@foreach($articles ?? [] as $art)
<x-modal :name="'edit-art-' . $loop->index" title="{{ __('admin.knowledge_base.edit_article') }}" maxWidth="xl">
    <form method="POST" action="{{ route('admin.config.knowledge-base.articles.update', $art) }}">
        @csrf @method('PUT')
        <table class="form">
            <tr><td class="fieldlabel">{{ __('common.form.title') }}</td><td class="fieldarea"><input type="text" name="title" value="{{ $art->title }}" required class="form-control"></td></tr>
            <tr><td class="fieldlabel">{{ __('admin.knowledge_base.category') }}</td><td class="fieldarea">
                <select name="category_id" class="form-control" required>
                    @foreach($categories ?? [] as $cat)<option value="{{ $cat->id }}" @selected($art->category_id == $cat->id)>{{ $cat->name }}</option>@endforeach
                </select>
            </td></tr>
            <tr><td class="fieldlabel label-top">{{ __('admin.knowledge_base.content') }}</td><td class="fieldarea"><textarea name="article" rows="8" class="form-control" required>{{ $art->article }}</textarea></td></tr>
            <tr><td class="fieldlabel">{{ __('admin.knowledge_base.published') }}</td><td class="fieldarea"><label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;"><input type="hidden" name="published" value="0"><input type="checkbox" name="published" value="1" @checked(! $art->private)> <span>{{ __('admin.knowledge_base.published') }}</span></label></td></tr>
        </table>
        @include('admin.partials.translation-fields', ['languages' => $translationLanguages ?? collect(), 'model' => $art,
            'fields' => ['title' => [__('common.form.title'), 'input'], 'article' => [__('admin.knowledge_base.content'), 'textarea']]])
        <div class="btn-container">
            <button type="button" onclick="closeModal('edit-art-{{ $loop->index }}')" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
            <button type="submit" class="btn btn-primary btn-sm">{{ __('common.actions.save_changes') }}</button>
        </div>
    </form>
</x-modal>
@endforeach

@foreach($categories ?? [] as $cat)
<x-modal name="edit-cat-{{ $loop->index }}" title="{{ __('admin.knowledge_base.edit_category') }}" maxWidth="lg">
    <form method="POST" action="{{ route('admin.config.knowledge-base.categories.update', $cat) }}">
        @csrf @method('PUT')
        <table class="form">
            <tr><td class="fieldlabel">{{ __('admin.knowledge_base.category_name') }}</td><td class="fieldarea"><input type="text" name="name" value="{{ $cat->name }}" required class="form-control"></td></tr>
            <tr><td class="fieldlabel label-top">{{ __('common.form.description') }}</td><td class="fieldarea"><textarea name="description" rows="2" class="form-control">{{ $cat->description }}</textarea></td></tr>
        </table>
        @include('admin.partials.translation-fields', ['languages' => $translationLanguages ?? collect(), 'model' => $cat,
            'fields' => ['name' => [__('admin.knowledge_base.category_name'), 'input'], 'description' => [__('common.form.description'), 'textarea']]])
        <div class="btn-container">
            <button type="button" onclick="closeModal('edit-cat-{{ $loop->index }}')" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
            <button type="submit" class="btn btn-primary btn-sm">{{ __('common.actions.save_changes') }}</button>
        </div>
    </form>
</x-modal>
@endforeach
@endsection
