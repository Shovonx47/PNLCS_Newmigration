@extends('admin.layouts.app')
@section('title', isset($group) ? __('admin.products.edit_group') : __('admin.products.create_group'))
@section('content')
@php $editing = isset($group); @endphp
<h1>{{ $editing ? __('admin.products.edit_group') : __('admin.products.create_group') }}</h1>

<div class="context-btn-container" style="margin-bottom:12px;">
    <a href="{{ route('admin.products.index') }}" class="btn btn-default btn-sm">&larr; {{ __('common.actions.back') }}</a>
</div>

@if($errors->any())
<div class="alert alert-danger" style="margin-bottom:15px;">
    @foreach($errors->all() as $e)<div>&bull; {{ $e }}</div>@endforeach
</div>
@endif

<style>
    .tab-content.admin-tabs > .tab-pane { display: none; }
    .tab-content.admin-tabs > .tab-pane.active { display: block; }
</style>

<ul class="nav nav-tabs admin-tabs" id="group-tabs" role="tablist">
    <li class="nav-item active"><a class="nav-link active" href="#tab-group-details" role="tab" data-group-tab="tab-group-details">{{ __('Details') }}</a></li>
    @if($editing)
    <li class="nav-item"><a class="nav-link" href="#tab-group-links" role="tab" data-group-tab="tab-group-links">{{ __('Links') }}</a></li>
    @endif
</ul>

<div class="tab-content admin-tabs" id="group-panes">
    <div class="tab-pane active" id="tab-group-details">
        <form method="POST" action="{{ $editing ? route('admin.products.groups.update', $group) : route('admin.products.groups.store') }}">
            @csrf
            @if($editing) @method('PUT') @endif
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td class="fieldlabel" width="20%">{{ __('admin.products.group_name') }} *</td>
                    <td class="fieldarea"><input type="text" name="name" required class="form-control" style="max-width:420px;" value="{{ old('name', $group->name ?? '') }}"></td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.products.headline') }}</td>
                    <td class="fieldarea"><input type="text" name="headline" class="form-control" style="max-width:520px;" value="{{ old('headline', $group->headline ?? '') }}"></td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.products.tagline') }}</td>
                    <td class="fieldarea"><input type="text" name="tagline" class="form-control" style="max-width:520px;" value="{{ old('tagline', $group->tagline ?? '') }}"></td>
                </tr>
                @if($editing)
                <tr>
                    <td class="fieldlabel">{{ __('admin.products.hidden') }}</td>
                    <td class="fieldarea">
                        <label style="font-weight:400;margin:0;"><input type="checkbox" name="hidden" value="1" @checked(old('hidden', $group->hidden))> {{ __('admin.products.hidden') }}</label>
                        <div style="color:#777;font-size:12px;margin-top:3px;">{{ __('admin.products.group_hidden_hint') }}</div>
                    </td>
                </tr>
                @endif
            </table>
            <div class="btn-container">
                <button type="submit" class="btn btn-primary">{{ $editing ? __('common.actions.save_changes') : __('admin.products.create_group') }}</button>
                <a href="{{ route('admin.products.index') }}" class="btn btn-default">{{ __('common.actions.cancel') }}</a>
            </div>
        </form>
    </div>

    @if($editing)
    <div class="tab-pane" id="tab-group-links">
        <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
            <tr>
                <td class="fieldlabel" width="20%">{{ __('Direct Shopping Cart Link') }}</td>
                <td class="fieldarea">
                    <input type="text" readonly class="form-control" value="{{ url('/store?gid='.$group->id) }}" onclick="this.select()">
                </td>
            </tr>
        </table>
    </div>
    @endif
</div>

@if($editing)
<script>
(function () {
    var tabs = document.querySelectorAll('#group-tabs [data-group-tab]');
    var panes = document.querySelectorAll('#group-panes > .tab-pane');
    tabs.forEach(function (a) {
        a.addEventListener('click', function (e) {
            e.preventDefault();
            var id = a.getAttribute('data-group-tab');
            tabs.forEach(function (t) {
                var on = t === a;
                t.classList.toggle('active', on);
                if (t.parentElement) t.parentElement.classList.toggle('active', on);
            });
            panes.forEach(function (p) { p.classList.toggle('active', p.id === id); });
        });
    });
})();
</script>
@endif
@endsection
