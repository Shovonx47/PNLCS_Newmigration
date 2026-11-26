@extends('admin.layouts.app')
@section('title', 'Scrolling Notices')

@section('content')
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
    <h1>Scrolling Notices</h1>
</div>

<div class="card" style="margin-bottom:18px;">
    <div class="card-header"><strong>Add Notice</strong></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.extensions.scrolling-notices.store') }}">
            @csrf
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;">
                <div class="form-group">
                    <label class="form-label">Internal Title</label>
                    <input type="text" name="title" class="form-control" value="{{ old('title') }}" placeholder="e.g. IP Number Offer">
                </div>
                <div class="form-group">
                    <label class="form-label">Target URL (optional)</label>
                    <input type="text" name="url_target" class="form-control" value="{{ old('url_target') }}" placeholder="/client/store or https://...">
                </div>
                <div class="form-group">
                    <label class="form-label">Audience</label>
                    <select name="login_status" class="form-control">
                        <option value="all">All Visitors</option>
                        <option value="logged_in">Logged-in Clients Only</option>
                        <option value="logged_out">Guests Only</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Priority</label>
                    <input type="number" name="priority" class="form-control" value="{{ old('priority', 10) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Scroll Speed (1-20)</label>
                    <input type="number" name="scroll_speed" min="1" max="20" class="form-control" value="{{ old('scroll_speed', 5) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Promo Code Badge (optional)</label>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <input type="checkbox" name="coupon_enabled" value="1">
                        <input type="text" name="coupon_code" class="form-control" placeholder="PROMO2026">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Starts At (optional)</label>
                    <input type="datetime-local" name="start_time" class="form-control" value="{{ old('start_time') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Expires At (optional)</label>
                    <input type="datetime-local" name="expiry_time" class="form-control" value="{{ old('expiry_time') }}">
                </div>
            </div>
            <div class="form-group" style="margin-top:10px;">
                <label class="form-label">Notice Text <span style="color:#d9534f;">*</span></label>
                <textarea name="content" rows="2" required class="form-control">{{ old('content') }}</textarea>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:10px;">
                <label style="font-size:13px;display:flex;align-items:center;gap:6px;"><input type="checkbox" name="is_active" value="1" checked> Active immediately</label>
                <button type="submit" class="btn btn-primary btn-sm">Create Notice</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><strong>Existing Notices ({{ $notices->count() }})</strong></div>
    <div class="card-body">
        @if($notices->isEmpty())
            <div style="text-align:center;padding:30px;color:#888;">No scrolling notices configured yet.</div>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Content</th>
                        <th>Audience</th>
                        <th>Priority</th>
                        <th>Speed</th>
                        <th>Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($notices as $notice)
                    <tr>
                        <td style="font-weight:600;">{{ $notice->title ?: '—' }}</td>
                        <td style="max-width:380px;">
                            <div>{{ $notice->content }}</div>
                            @if($notice->url_target)
                                <small style="color:#666;">Link: {{ $notice->url_target }} &rarr; {{ $notice->resolvedUrl() }}</small>
                            @endif
                            @if($notice->coupon_enabled && $notice->coupon_code)
                                <span class="badge badge-active">Coupon: {{ $notice->coupon_code }}</span>
                            @endif
                        </td>
                        <td>{{ ucfirst(str_replace('_', ' ', $notice->login_status)) }}</td>
                        <td>{{ $notice->priority }}</td>
                        <td>{{ $notice->scroll_speed }}</td>
                        <td>
                            <span class="badge {{ $notice->is_active ? 'badge-active' : 'badge-cancelled' }}">
                                {{ $notice->is_active ? 'Active' : 'Disabled' }}
                            </span>
                        </td>
                        <td style="text-align:right;white-space:nowrap;">
                            <button type="button" onclick="openModal('edit-notice-{{ $notice->id }}')" class="btn btn-default btn-xs">Edit</button>
                            <form method="POST" action="{{ route('admin.extensions.scrolling-notices.toggle', $notice) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-default btn-xs">{{ $notice->is_active ? 'Disable' : 'Enable' }}</button>
                            </form>
                            <form method="POST" action="{{ route('admin.extensions.scrolling-notices.destroy', $notice) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js('Delete this scrolling notice?'))">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-xs">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

@foreach($notices as $notice)
<x-modal name="edit-notice-{{ $notice->id }}" title="Edit Notice: {{ $notice->title ?: '#'.$notice->id }}" maxWidth="md">
    <form method="POST" action="{{ route('admin.extensions.scrolling-notices.update', $notice) }}">
        @csrf
        @method('PUT')
        <div class="form-group"><label class="form-label">Title</label><input type="text" name="title" value="{{ $notice->title }}" class="form-control"></div>
        <div class="form-group"><label class="form-label">Content *</label><textarea name="content" rows="3" required class="form-control">{{ $notice->content }}</textarea></div>
        <div class="form-group"><label class="form-label">Target URL</label><input type="text" name="url_target" value="{{ $notice->url_target }}" class="form-control"></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <div class="form-group"><label class="form-label">Priority</label><input type="number" name="priority" value="{{ $notice->priority }}" class="form-control"></div>
            <div class="form-group"><label class="form-label">Scroll Speed (1-20)</label><input type="number" name="scroll_speed" min="1" max="20" value="{{ $notice->scroll_speed }}" class="form-control"></div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <div class="form-group"><label class="form-label">Starts At</label><input type="datetime-local" name="start_time" value="{{ $notice->start_time?->format('Y-m-d\TH:i') }}" class="form-control"></div>
            <div class="form-group"><label class="form-label">Expires At</label><input type="datetime-local" name="expiry_time" value="{{ $notice->expiry_time?->format('Y-m-d\TH:i') }}" class="form-control"></div>
        </div>
        <div class="form-group">
            <label class="form-label">Audience</label>
            <select name="login_status" class="form-control">
                <option value="all" @selected($notice->login_status === 'all')>All Visitors</option>
                <option value="logged_in" @selected($notice->login_status === 'logged_in')>Logged-in Clients Only</option>
                <option value="logged_out" @selected($notice->login_status === 'logged_out')>Guests Only</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Promo Code Badge</label>
            <div style="display:flex;gap:8px;align-items:center;">
                <input type="checkbox" name="coupon_enabled" value="1" @checked($notice->coupon_enabled)>
                <input type="text" name="coupon_code" value="{{ $notice->coupon_code }}" class="form-control" placeholder="Coupon code">
            </div>
        </div>
        <label style="font-size:13px;display:flex;align-items:center;gap:6px;margin-bottom:10px;">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked($notice->is_active)>
            Active
        </label>
        <div style="display:flex;justify-content:flex-end;gap:8px;">
            <button type="button" onclick="closeModal('edit-notice-{{ $notice->id }}')" class="btn btn-default btn-sm">Cancel</button>
            <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
        </div>
    </form>
</x-modal>
@endforeach
@endsection
