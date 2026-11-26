@extends('admin.layouts.app')
@section('title', 'Softcents Voice')

@section('content')

<div class="page-header">
    <h1>Softcents Voice</h1>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;margin-bottom:15px;">
    <div class="card"><div class="card-body">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;">Voice balance</div>
        <div style="font-size:22px;font-weight:700;color:{{ $balance['success'] ? '#065f46' : '#991b1b' }};">
            {{ $balance['success'] ? $balance['balance'] : '—' }}
        </div>
        @unless($balance['success'])<div style="font-size:11px;color:#991b1b;">{{ $balance['error'] }}</div>@endunless
    </div></div>
    <div class="card"><div class="card-body">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;">Calls today</div>
        <div style="font-size:22px;font-weight:700;">{{ $sentToday }}</div>
    </div></div>
    <div class="card"><div class="card-body">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;">Retries waiting</div>
        <div style="font-size:22px;font-weight:700;">{{ $pendingRetries }}</div>
    </div></div>
    <div class="card"><div class="card-body">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;">Gave up</div>
        <div style="font-size:22px;font-weight:700;color:{{ $failedRetries ? '#991b1b' : '#111827' }};">{{ $failedRetries }}</div>
    </div></div>
    <div class="card"><div class="card-body">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;">Dispatch</div>
        <div style="font-size:16px;font-weight:700;color:{{ $settings->master_paused ? '#991b1b' : '#065f46' }};">
            {{ $settings->master_paused ? 'Paused' : 'Live' }}
        </div>
        <div style="font-size:11px;color:#6b7280;">{{ $settings->calling_start }}–{{ $settings->calling_end }} {{ $settings->timezone }}</div>
    </div></div>
</div>

<div class="card" style="margin-bottom:15px;">
    <div class="card-header"><strong>Gateway &amp; calling window</strong></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.extensions.voice.settings') }}">
            @csrf
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px;">
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;">API token</label>
                    <input type="password" name="api_token" class="form-control" autocomplete="new-password"
                           placeholder="{{ $settings->api_token ? '•••••••• (saved — leave blank to keep)' : 'Not set' }}">
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;">API base URL</label>
                    <input type="url" name="api_base_url" class="form-control" value="{{ $settings->api_base_url }}">
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;">Default caller id</label>
                    <input type="text" name="caller_id" class="form-control" value="{{ $settings->caller_id }}">
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;">Window opens</label>
                    <input type="time" name="calling_start" class="form-control" value="{{ $settings->calling_start }}" required>
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;">Window closes</label>
                    <input type="time" name="calling_end" class="form-control" value="{{ $settings->calling_end }}" required>
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;">Timezone</label>
                    <input type="text" name="timezone" class="form-control" value="{{ $settings->timezone }}" required>
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;">Retry every (minutes)</label>
                    <input type="number" name="retry_interval_minutes" min="1" max="1440" class="form-control" value="{{ $settings->retry_interval_minutes }}">
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;">Attempts</label>
                    <input type="number" name="max_attempts" min="1" max="10" class="form-control" value="{{ $settings->max_attempts }}">
                </div>
            </div>
            <div style="display:flex;gap:18px;flex-wrap:wrap;margin:4px 0 12px;">
                <label style="display:flex;gap:8px;align-items:center;font-size:13px;">
                    <input type="checkbox" name="master_paused" value="1" {{ $settings->master_paused ? 'checked' : '' }}> Pause all automatic calls
                </label>
                <label style="display:flex;gap:8px;align-items:center;font-size:13px;">
                    <input type="checkbox" name="retry_enabled" value="1" {{ $settings->retry_enabled ? 'checked' : '' }}> Retry refused calls
                </label>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Save settings</button>
        </form>

        <hr style="margin:14px 0;border:0;border-top:1px solid #e5e7eb;">

        <form method="POST" action="{{ route('admin.extensions.voice.test') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
            @csrf
            <div class="form-group" style="margin:0;width:190px;">
                <label style="font-size:12px;font-weight:600;">Test number</label>
                <input type="text" name="number" class="form-control" placeholder="01711223344" required>
            </div>
            <div class="form-group" style="margin:0;width:170px;">
                <label style="font-size:12px;font-weight:600;">Audio id</label>
                <input type="text" name="audio_id" class="form-control" required>
            </div>
            <div class="form-group" style="margin:0;width:130px;">
                <label style="font-size:12px;font-weight:600;">Caller id</label>
                <input type="text" name="caller_id" class="form-control" value="{{ $settings->caller_id }}">
            </div>
            <button type="submit" class="btn btn-default btn-sm">Place test call</button>
        </form>
        @if($audios !== [])
        <div style="font-size:11px;color:#6b7280;margin-top:6px;">
            Approved recordings:
            @foreach(array_slice($audios, 0, 8) as $audio)
                <span style="display:inline-block;background:#f1f5f9;border-radius:9px;padding:1px 8px;margin:2px;">#{{ $audio['id'] ?? '?' }} {{ \Illuminate\Support\Str::limit((string) ($audio['title'] ?? $audio['name'] ?? ''), 24) }}</span>
            @endforeach
        </div>
        @endif
    </div>
</div>

<div class="card" style="margin-bottom:15px;">
    <div class="card-header">
        <strong>Events</strong>
        <span style="font-size:12px;color:#666;">{{ $events->where('is_enabled', true)->count() }} of {{ $events->count() }} switched on</span>
    </div>
    <div class="card-body" style="padding:0;max-height:460px;overflow:auto;">
        @foreach($events as $event)
        <form method="POST" action="{{ route('admin.extensions.voice.events.update', $event) }}"
              style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;border-bottom:1px solid #f0f0f0;padding:8px 14px;">
            @csrf
            <span style="font-size:10px;text-transform:uppercase;letter-spacing:.4px;padding:1px 7px;border-radius:9px;background:#eef2ff;color:#3730a3;">{{ $event->category }}</span>
            <strong style="font-size:12.5px;min-width:190px;">{{ $event->name }}</strong>
            <label style="display:flex;gap:6px;align-items:center;font-size:12px;">
                <input type="checkbox" name="is_enabled" value="1" {{ $event->is_enabled ? 'checked' : '' }}> on
            </label>
            {{-- The theme forces .form-control to 100% width, so the column is
                 sized here instead of on the input. --}}
            <span style="flex:0 0 110px;"><input type="text" name="audio_id" class="form-control input-sm" style="width:100%;" value="{{ $event->audio_id }}" placeholder="audio id"></span>
            <span style="flex:0 0 90px;"><input type="text" name="caller_id" class="form-control input-sm" style="width:100%;" value="{{ $event->caller_id }}" placeholder="caller"></span>
            <button type="submit" class="btn btn-default btn-xs">Save</button>
            <span style="font-size:11px;color:#9ca3af;flex:1;min-width:160px;">{{ $event->description }}</span>
        </form>
        @endforeach
    </div>
</div>

<div class="card" style="margin-bottom:15px;">
    <div class="card-header"><strong>Per-group recordings</strong></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.extensions.voice.overrides.store') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
            @csrf
            <div class="form-group" style="margin:0;width:190px;">
                <label style="font-size:12px;font-weight:600;">Product group</label>
                <select name="group_id" class="form-control" required>
                    @foreach($groups as $group)
                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin:0;width:220px;">
                <label style="font-size:12px;font-weight:600;">Event</label>
                <select name="event_key" class="form-control" required>
                    @foreach($events as $event)
                    <option value="{{ $event->event_key }}">{{ $event->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin:0;width:130px;">
                <label style="font-size:12px;font-weight:600;">Audio id</label>
                <input type="text" name="audio_id" class="form-control" required>
            </div>
            <div class="form-group" style="margin:0;width:110px;">
                <label style="font-size:12px;font-weight:600;">Caller id</label>
                <input type="text" name="caller_id" class="form-control" placeholder="optional">
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Add override</button>
        </form>

        @if($overrides->isNotEmpty())
        <table class="table table-striped" style="margin-top:12px;font-size:12px;">
            <thead><tr><th>Group</th><th>Event</th><th>Audio</th><th>Caller</th><th></th></tr></thead>
            <tbody>
                @foreach($overrides as $override)
                <tr>
                    <td>{{ $groups->firstWhere('id', $override->group_id)?->name ?? '#'.$override->group_id }}</td>
                    <td>{{ $override->event_key }}</td>
                    <td>{{ $override->audio_id }}</td>
                    <td>{{ $override->caller_id ?: '—' }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.extensions.voice.overrides.destroy', $override) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-xs">Remove</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header"><strong>Recent calls</strong></div>
    <div class="card-body" style="padding:0;overflow-x:auto;">
        <table class="table table-striped" style="margin:0;font-size:12px;">
            <thead><tr><th>When</th><th>Number</th><th>Event</th><th>Audio</th><th>Status</th><th>Detail</th></tr></thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td style="white-space:nowrap;">{{ $log->created_at?->format('d M H:i') }}</td>
                    <td>{{ $log->phone }}</td>
                    <td>{{ $log->event_key }}</td>
                    <td>{{ $log->audio_id ?: '—' }}</td>
                    <td style="font-weight:600;color:{{ $log->status === 'sent' ? '#065f46' : ($log->status === 'skipped' ? '#92400e' : '#991b1b') }};">{{ $log->status }}</td>
                    <td style="max-width:420px;color:#6b7280;">{{ $log->reason }}</td>
                </tr>
                @empty
                <tr><td colspan="6" style="padding:14px;color:#9ca3af;">No calls yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
