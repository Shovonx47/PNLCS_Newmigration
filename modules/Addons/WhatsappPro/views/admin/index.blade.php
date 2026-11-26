@extends('admin.layouts.app')
@section('title', 'WhatsApp Pro')

@section('content')

<div class="page-header">
    <h1>WhatsApp Pro</h1>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;margin-bottom:15px;">
    <div class="card"><div class="card-body">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;">Gateway</div>
        <div style="font-size:16px;font-weight:700;color:{{ $connection['connected'] ? '#065f46' : '#991b1b' }};">
            {{ $connection['connected'] ? 'Connected' : 'Not connected' }}
        </div>
        <div style="font-size:11px;color:#6b7280;">{{ $connection['reason'] }}</div>
        <form method="POST" action="{{ route('admin.extensions.whatsapp.connection') }}" style="margin-top:6px;">
            @csrf
            <button type="submit" class="btn btn-default btn-xs">Check now</button>
        </form>
    </div></div>
    <div class="card"><div class="card-body">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;">Queued</div>
        <div style="font-size:22px;font-weight:700;">{{ $queued }}</div>
    </div></div>
    <div class="card"><div class="card-body">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;">Sent today</div>
        <div style="font-size:22px;font-weight:700;">{{ $sentToday }}</div>
    </div></div>
    <div class="card"><div class="card-body">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;">Failed today</div>
        <div style="font-size:22px;font-weight:700;color:{{ $failedToday ? '#991b1b' : '#111827' }};">{{ $failedToday }}</div>
    </div></div>
    <div class="card"><div class="card-body">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;">Reachable numbers</div>
        <div style="font-size:22px;font-weight:700;">{{ $recipientsAll }}</div>
        <div style="font-size:11px;color:#6b7280;">{{ $recipientsUnpaid }} with an open invoice</div>
    </div></div>
</div>

<div class="card" style="margin-bottom:15px;">
    <div class="card-header"><strong>Gateway</strong></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.extensions.whatsapp.settings') }}">
            @csrf
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;">API key</label>
                    <input type="password" name="api_key" class="form-control" autocomplete="new-password"
                           placeholder="{{ $settings->api_key ? '•••••••• (saved — leave blank to keep)' : 'Not set' }}">
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;">Sender number</label>
                    <input type="text" name="sender" class="form-control" value="{{ $settings->sender }}" placeholder="8801713252753">
                    <div style="font-size:11px;color:#6b7280;margin-top:3px;">The number connected to the gateway.</div>
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;">Gateway domain</label>
                    <input type="url" name="api_domain" class="form-control" value="{{ $settings->api_domain }}">
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;">Footer</label>
                    <input type="text" name="footer" class="form-control" value="{{ $settings->footer }}" placeholder="Softcents Systems">
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;">Admin numbers</label>
                    <input type="text" name="admin_numbers" class="form-control" value="{{ $settings->admin_numbers }}" placeholder="01711223344">
                    <div style="font-size:11px;color:#6b7280;margin-top:3px;">Where operator templates go.</div>
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;">Attempts before giving up</label>
                    <input type="number" name="max_retry" min="1" max="10" class="form-control" value="{{ $settings->max_retry }}">
                </div>
            </div>
            <div style="display:flex;gap:18px;flex-wrap:wrap;margin:4px 0 12px;">
                <label style="display:flex;gap:8px;align-items:center;font-size:13px;">
                    <input type="checkbox" name="enabled" value="1" {{ $settings->enabled ? 'checked' : '' }}> Sending enabled
                </label>
                <label style="display:flex;gap:8px;align-items:center;font-size:13px;">
                    <input type="checkbox" name="client_portal" value="1" {{ $settings->client_portal ? 'checked' : '' }}> Show message history to customers
                </label>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Save settings</button>
        </form>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:15px;margin-bottom:15px;">
    <div class="card"><div class="card-header"><strong>Send one message</strong></div><div class="card-body">
        <form method="POST" action="{{ route('admin.extensions.whatsapp.send') }}">
            @csrf
            <div class="form-group">
                <label style="font-size:12px;font-weight:600;">Number</label>
                <input type="text" name="number" class="form-control" placeholder="01711223344" required>
            </div>
            <div class="form-group">
                <label style="font-size:12px;font-weight:600;">Message</label>
                <textarea name="message" rows="3" class="form-control" required></textarea>
            </div>
            <div style="display:grid;grid-template-columns:2fr 1fr;gap:10px;">
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;">Media URL (optional)</label>
                    <input type="url" name="media_url" class="form-control">
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;">Type</label>
                    <select name="media_type" class="form-control">
                        <option value="document">document</option>
                        <option value="image">image</option>
                        <option value="video">video</option>
                        <option value="audio">audio</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Queue message</button>
        </form>
    </div></div>

    <div class="card"><div class="card-header"><strong>Bulk send</strong></div><div class="card-body">
        <form method="POST" action="{{ route('admin.extensions.whatsapp.bulk') }}" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label style="font-size:12px;font-weight:600;">Audience</label>
                <select name="target" class="form-control">
                    <option value="all">All active customers ({{ $recipientsAll }})</option>
                    <option value="unpaid">Customers with an open invoice ({{ $recipientsUnpaid }})</option>
                    <option value="group">A product group</option>
                    <option value="clients">Selected client ids</option>
                    <option value="csv">Pasted numbers or CSV</option>
                </select>
            </div>
            <div class="form-group">
                <label style="font-size:12px;font-weight:600;">Product group / client ids / numbers</label>
                <select name="group_id" class="form-control" style="margin-bottom:6px;">
                    <option value="">— product group —</option>
                    @foreach($groups as $group)
                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                    @endforeach
                </select>
                <input type="text" name="client_ids" class="form-control" style="margin-bottom:6px;" placeholder="client ids, e.g. 3,7,12">
                <textarea name="csv" rows="2" class="form-control" placeholder="01711223344&#10;01811223344"></textarea>
                <input type="file" name="csv_file" accept=".csv,.txt" class="form-control" style="margin-top:6px;">
            </div>
            <div class="form-group">
                <label style="font-size:12px;font-weight:600;">Message</label>
                <textarea name="message" rows="3" class="form-control" required></textarea>
                <div style="font-size:11px;color:#6b7280;margin-top:3px;">Placeholders: {firstname} {lastname} {email} {companyname} {invoiceid} {amount} {invoiceurl}</div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Queue to audience</button>
        </form>
    </div></div>
</div>

<div class="card" style="margin-bottom:15px;">
    <div class="card-header">
        <strong>Templates</strong>
        <span style="font-size:12px;color:#666;">{{ $templates->where('active', true)->count() }} active of {{ $templates->count() }}</span>
    </div>
    <div class="card-body" style="padding:0;max-height:520px;overflow:auto;">
        @foreach($templates as $template)
        <form method="POST" action="{{ route('admin.extensions.whatsapp.templates.update', $template) }}"
              style="border-bottom:1px solid #f0f0f0;padding:9px 14px;">
            @csrf
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:5px;">
                <strong style="font-size:13px;min-width:200px;">{{ $template->name }}</strong>
                <span style="font-size:11px;padding:1px 8px;border-radius:10px;background:{{ $template->type === 'admin' ? '#fef3c7' : '#d1fae5' }};color:{{ $template->type === 'admin' ? '#92400e' : '#065f46' }};">{{ $template->type }}</span>
                <label style="display:flex;gap:6px;align-items:center;font-size:12px;color:#374151;">
                    <input type="checkbox" name="active" value="1" {{ $template->active ? 'checked' : '' }}> active
                </label>
                <span style="font-size:11px;color:#9ca3af;">{{ $template->description }}</span>
                <button type="submit" class="btn btn-default btn-xs" style="margin-left:auto;">Save</button>
            </div>
            <textarea name="template" class="form-control" rows="2" style="font-size:12px;">{{ $template->template }}</textarea>
        </form>
        @endforeach
    </div>
</div>

<div class="card">
    <div class="card-header"><strong>Recent messages</strong></div>
    <div class="card-body" style="padding:0;overflow-x:auto;">
        <table class="table table-striped" style="margin:0;font-size:12px;">
            <thead><tr><th>When</th><th>To</th><th>Template</th><th>Status</th><th>Detail</th><th></th></tr></thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td style="white-space:nowrap;">{{ $log->created_at?->format('d M H:i') }}</td>
                    <td>{{ $log->number }}</td>
                    <td>{{ $log->template_name ?: ucfirst($log->source) }}</td>
                    <td style="color:{{ $log->status === 'Sent' ? '#065f46' : '#991b1b' }};font-weight:600;">{{ $log->status }}</td>
                    <td style="max-width:420px;color:#6b7280;">{{ $log->error ?: \Illuminate\Support\Str::limit($log->message, 80) }}</td>
                    <td>
                        @if($log->status !== 'Sent')
                        <form method="POST" action="{{ route('admin.extensions.whatsapp.logs.retry', $log) }}">
                            @csrf
                            <button class="btn btn-default btn-xs" type="submit">Retry</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" style="padding:14px;color:#9ca3af;">No messages yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body">{{ $logs->links() }}</div>
</div>

@endsection
