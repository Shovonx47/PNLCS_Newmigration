@extends("client.layouts.app")
@section("title", "Email Accounts — " . ($service->domain ?: "Hosting"))
@section("content")

@include("bhpanel::partials.nav")

<div class="db-head">
    <div class="db-head-ic"><i class="ri-mail-line"></i></div>
    <div>
        <h1>Email Accounts &amp; Forwarders</h1>
        <div class="sub">Create mailboxes, adjust storage quotas, and manage forwarding rules.</div>
    </div>
    <span class="db-cnt">{{ count($mailboxes) }} mailbox(es)</span>
</div>

@if(count($noMailDomains) > 0)
<div class="db-card">
    <div class="db-ch"><span><i class="ri-mail-check-line"></i> Enable Email Service on Domain</span></div>
    <form method="POST" action="{{ route('client.services.bhpanel.email', $service) }}" class="db-form">
        @csrf
        <input type="hidden" name="bhp_action" value="enable">
        <div class="db-fld">
            <label class="db-lbl">Website Domain</label>
            <select name="domain" class="db-inp">
                @foreach($noMailDomains as $nd)<option value="{{ $nd }}">{{ $nd }}</option>@endforeach
            </select>
        </div>
        <button type="submit" class="db-btn"><i class="ri-check-line"></i> Turn On Email</button>
    </form>
</div>
@endif

@if(count($mailDomains) > 0)
<div class="db-card">
    <div class="db-ch"><span><i class="ri-mail-add-line"></i> Create New Mailbox</span></div>
    <form method="POST" action="{{ route('client.services.bhpanel.email', $service) }}" class="db-form">
        @csrf
        <input type="hidden" name="bhp_action" value="create">
        <div class="db-fld">
            <label class="db-lbl">Mailbox Name</label>
            <input type="text" name="local" required placeholder="info" class="db-inp">
        </div>
        <div class="db-fld">
            <label class="db-lbl">Domain</label>
            <select name="domain" class="db-inp">
                @foreach($mailDomains as $md)<option value="{{ $md }}">{{ '@' . $md }}</option>@endforeach
            </select>
        </div>
        <div class="db-fld">
            <label class="db-lbl">Password (min 12 chars)</label>
            <input type="password" name="password" required minlength="12" class="db-inp" autocomplete="new-password">
        </div>
        <div class="db-fld" style="max-width:160px;">
            <label class="db-lbl">Quota (MB)</label>
            <input type="number" name="quota_mb" value="1024" min="50" max="102400" class="db-inp">
        </div>
        <button type="submit" class="db-btn"><i class="ri-add-line"></i> Create Mailbox</button>
    </form>
</div>
@endif

<div class="db-card">
    <div class="db-ch">
        <span><i class="ri-inbox-line"></i> Mailboxes</span>
        <form method="GET" action="{{ route('client.services.bhpanel.email', $service) }}" style="display:flex;gap:6px;margin:0;">
            <input type="text" name="q" value="{{ $q }}" placeholder="Filter address..." class="db-inp" style="height:32px;padding:4px 10px;width:180px;">
            <button type="submit" class="db-act">Search</button>
        </form>
    </div>
    @if(count($mailboxes) === 0)
        <div class="db-empty">No mailboxes found.</div>
    @else
        <div style="overflow-x:auto;">
            <table class="pn-table">
                <thead>
                    <tr>
                        <th>Address</th>
                        <th>Storage Used / Quota</th>
                        <th style="text-align:right;">Manage</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($mailboxes as $mb)
                        @php $addr = (string) ($mb['address'] ?? ''); @endphp
                        <tr>
                            <td><strong>{{ $addr }}</strong></td>
                            <td>{{ number_format((float) ($mb['used_mb'] ?? 0), 1) }} / {{ (int) ($mb['quota_mb'] ?? 0) > 0 ? (int) $mb['quota_mb'].' MB' : '∞' }}</td>
                            <td style="text-align:right;">
                                <details style="display:inline-block;text-align:left;margin-right:6px;">
                                    <summary class="db-act"><i class="ri-key-2-line"></i> Password</summary>
                                    <form method="POST" action="{{ route('client.services.bhpanel.email', $service) }}" style="margin-top:6px;padding:10px;background:var(--bg);border:1px solid var(--border);border-radius:10px;display:flex;gap:6px;">
                                        @csrf
                                        <input type="hidden" name="bhp_action" value="password">
                                        <input type="hidden" name="address" value="{{ $addr }}">
                                        <input type="password" name="password" required minlength="12" placeholder="New password (12+)" class="db-inp" style="padding:6px 10px;font-size:12.5px;">
                                        <button type="submit" class="db-btn" style="padding:6px 12px;font-size:12px;">Save</button>
                                    </form>
                                </details>
                                <details style="display:inline-block;text-align:left;">
                                    <summary class="db-act"><i class="ri-hard-drive-2-line"></i> Quota</summary>
                                    <form method="POST" action="{{ route('client.services.bhpanel.email', $service) }}" style="margin-top:6px;padding:10px;background:var(--bg);border:1px solid var(--border);border-radius:10px;display:flex;gap:6px;">
                                        @csrf
                                        <input type="hidden" name="bhp_action" value="quota">
                                        <input type="hidden" name="address" value="{{ $addr }}">
                                        <input type="number" name="quota_mb" value="{{ (int) ($mb['quota_mb'] ?? 1024) }}" min="50" class="db-inp" style="width:110px;padding:6px 10px;font-size:12.5px;">
                                        <button type="submit" class="db-btn" style="padding:6px 12px;font-size:12px;">Update</button>
                                    </form>
                                </details>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@if(count($mailDomains) > 0)
<div class="db-card">
    <div class="db-ch"><span><i class="ri-share-forward-line"></i> Email Forwarders</span></div>
    <form method="POST" action="{{ route('client.services.bhpanel.email', $service) }}" class="db-form" style="border-bottom:1px solid var(--border);">
        @csrf
        <input type="hidden" name="bhp_action" value="forward">
        <div class="db-fld">
            <label class="db-lbl">Forward From</label>
            <input type="text" name="local" required placeholder="sales" class="db-inp">
        </div>
        <div class="db-fld">
            <label class="db-lbl">Domain</label>
            <select name="domain" class="db-inp">
                @foreach($mailDomains as $md)<option value="{{ $md }}">{{ '@' . $md }}</option>@endforeach
            </select>
        </div>
        <div class="db-fld">
            <label class="db-lbl">Forward To Address</label>
            <input type="email" name="to" required placeholder="destination@example.com" class="db-inp">
        </div>
        <button type="submit" class="db-btn"><i class="ri-add-line"></i> Add Forwarder</button>
    </form>
    @if(count($forwards) > 0)
        <table class="pn-table">
            <tbody>
                @foreach($forwards as $fw)
                    <tr>
                        <td><strong>{{ $fw['address'] }}</strong> &rarr; {{ implode(', ', $fw['to']) }}</td>
                        <td style="text-align:right;">
                            <form method="POST" action="{{ route('client.services.bhpanel.email', $service) }}" style="margin:0;">
                                @csrf
                                <input type="hidden" name="bhp_action" value="unforward">
                                <input type="hidden" name="address" value="{{ $fw['address'] }}">
                                <button type="submit" class="db-act">Remove</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endif

@endsection
