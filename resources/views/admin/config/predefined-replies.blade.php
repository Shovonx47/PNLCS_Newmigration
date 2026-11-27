@extends('admin.layouts.app')
@section('title', __('admin.predefined.title'))
@section('content')

<h1>{{ __('admin.predefined.title') }}</h1>
<div class="infobox">{{ __('admin.predefined.intro') }}</div>

@if($errors->any())
<div class="errorbox"><ul style="margin:0;padding-left:18px;">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="row">
    <div class="col-md-4">
        <div class="clientssummarybox">
            <div class="title">{{ __('admin.predefined.categories') }}</div>
            <table class="clientssummarystats">
                @forelse($categories as $category)
                <tr class="{{ $loop->even ? 'altrow' : '' }}">
                    <td><strong>{{ $category->name }}</strong> <span class="textgrey">({{ $category->replies->count() }})</span></td>
                    <td style="text-align:right;width:60px;">
                        <form method="POST" action="{{ route('admin.config.predefined-replies.categories.destroy', $category) }}" style="margin:0;" onsubmit="return pnConfirm(event, @js(__('admin.predefined.confirm_delete_category')))">@csrf @method('DELETE')<button type="submit" class="btn btn-danger btn-xs">{{ __('common.actions.delete') }}</button></form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="2" class="textgrey">{{ __('admin.predefined.no_categories') }}</td></tr>
                @endforelse
            </table>
            <form method="POST" action="{{ route('admin.config.predefined-replies.categories.store') }}" style="margin-top:8px;display:flex;gap:6px;">
                @csrf
                <input type="text" name="name" required maxlength="255" class="form-control" placeholder="{{ __('admin.predefined.new_category') }}">
                <button type="submit" class="btn btn-primary btn-sm">{{ __('common.actions.add') }}</button>
            </form>
        </div>
    </div>

    <div class="col-md-8">
        @if($categories->isNotEmpty())
        <ul class="admin-tabs">
            <li class="active"><a href="#">{{ __('admin.predefined.add_reply') }}</a></li>
        </ul>
        <div class="tab-content admin-tabs">
            <div class="tab-pane active">
                <form method="POST" action="{{ route('admin.config.predefined-replies.store') }}">
                    @csrf
                    <table class="form" style="margin:0;">
                        <tr>
                            <td class="fieldlabel">{{ __('admin.knowledge_base.category') }}</td>
                            <td class="fieldarea"><select name="category_id" class="form-control input-250" required>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></td>
                        </tr>
                        <tr>
                            <td class="fieldlabel">{{ __('admin.predefined.reply_name') }}</td>
                            <td class="fieldarea"><input type="text" name="name" required maxlength="255" class="form-control" placeholder="{{ __('admin.predefined.reply_name') }}"></td>
                        </tr>
                        <tr>
                            <td class="fieldlabel label-top">{{ __('admin.predefined.reply_text') }}</td>
                            <td class="fieldarea"><textarea name="reply" rows="5" required class="form-control" placeholder="{{ __('admin.predefined.reply_text') }}"></textarea></td>
                        </tr>
                    </table>
                    <div class="btn-container">
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('common.actions.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        @foreach($categories as $category)
            @foreach($category->replies as $reply)
            <details class="panel" style="margin-bottom:8px;">
                <summary class="panel-heading" style="cursor:pointer;"><strong>{{ $reply->name }}</strong> <span class="textgrey" style="font-size:12px;">&middot; {{ $category->name }}</span></summary>
                <div class="panel-body">
                    <form method="POST" action="{{ route('admin.config.predefined-replies.update', $reply) }}">
                        @csrf @method('PUT')
                        <table class="form">
                            <tr><td class="fieldlabel">{{ __('admin.knowledge_base.category') }}</td><td class="fieldarea"><select name="category_id" class="form-control input-250">@foreach($categories as $c)<option value="{{ $c->id }}" @selected($c->id === $reply->category_id)>{{ $c->name }}</option>@endforeach</select></td></tr>
                            <tr><td class="fieldlabel">{{ __('admin.predefined.reply_name') }}</td><td class="fieldarea"><input type="text" name="name" value="{{ $reply->name }}" required maxlength="255" class="form-control"></td></tr>
                            <tr><td class="fieldlabel label-top">{{ __('admin.predefined.reply_text') }}</td><td class="fieldarea"><textarea name="reply" rows="5" required class="form-control">{{ $reply->reply }}</textarea></td></tr>
                        </table>
                        <div style="display:flex;gap:6px;">
                            <button type="submit" class="btn btn-primary btn-sm">{{ __('common.actions.save') }}</button>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('admin.config.predefined-replies.destroy', $reply) }}" style="margin-top:6px;" onsubmit="return pnConfirm(event, @js(__('admin.predefined.confirm_delete_reply')))">@csrf @method('DELETE')<button type="submit" class="btn btn-danger btn-xs">{{ __('common.actions.delete') }}</button></form>
                </div>
            </details>
            @endforeach
        @endforeach
    </div>
</div>
@endsection
