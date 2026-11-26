@extends("client.layouts.app")
@section("title", "WordPress — " . ($service->domain ?: "Hosting"))
@section("content")

@include("bhpanel::partials.nav")

<div class="db-head">
    <div class="db-head-ic"><i class="ri-wordpress-line"></i></div>
    <div>
        <h1>WordPress Manager</h1>
        <div class="sub">Install, update, back up, and sign in to your WordPress sites in one click.</div>
    </div>
    <span class="db-cnt">{{ count($installs) }} site(s)</span>
</div>

@if($wpLogin)
<div class="db-card">
    <div class="db-ch"><span><i class="ri-login-circle-line"></i> One-Click WordPress Admin Sign-In</span></div>
    <div style="padding:18px;">
        <p style="margin:0 0 12px;font-size:13.5px;color:var(--muted);">
            Your temporary single-use WordPress session for <strong>{{ $wpLogin['user'] ?: 'admin' }}</strong> is ready.
        </p>
        <form method="POST" action="{{ $wpLogin['url'] }}" target="_blank" rel="noopener">
            <input type="hidden" name="{{ $wpLogin['field'] }}" value="{{ $wpLogin['token'] }}">
            <button type="submit" class="db-btn"><i class="ri-external-link-line"></i> Continue to wp-admin</button>
        </form>
    </div>
</div>
@endif

@if($job)
<div class="pn-alert {{ ($job['state'] ?? '') === 'failed' ? 'pn-alert-danger' : (($job['state'] ?? '') === 'done' ? 'pn-alert-success' : 'pn-alert-info') }}" style="margin-bottom:18px;">
    <strong>WordPress Task Status: {{ ucfirst((string) ($job['state'] ?? 'running')) }}</strong>
    @if(!empty($job['message'])) <div>{{ $job['message'] }}</div> @endif
    @if(!empty($job['result']['admin_password']))
        <div style="margin-top:8px;">Generated Admin Password (shown briefly): <code>{{ $job['result']['admin_password'] }}</code></div>
    @endif
</div>
@if(in_array($job['state'] ?? '', ['running', 'unreadable'], true))
<script>setTimeout(function(){ window.location.reload(); }, 4000);</script>
@endif
@endif

@if(count($sites) > 0)
<div class="db-card">
    <div class="db-ch"><span><i class="ri-add-circle-line"></i> Install New WordPress Site</span></div>
    <form method="POST" action="{{ route('client.services.bhpanel.wordpress', $service) }}" class="db-form">
        @csrf
        <input type="hidden" name="bhp_action" value="install">
        <div class="db-fld">
            <label class="db-lbl">Domain</label>
            <select name="domain" required class="db-inp">
                @foreach($sites as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
            </select>
        </div>
        <div class="db-fld">
            <label class="db-lbl">Subfolder (optional)</label>
            <input type="text" name="subdir" placeholder="blog (leave empty for root)" class="db-inp">
        </div>
        <div class="db-fld">
            <label class="db-lbl">Site Title</label>
            <input type="text" name="title" value="My Website" required class="db-inp">
        </div>
        <div class="db-fld">
            <label class="db-lbl">Admin Username</label>
            <input type="text" name="admin_user" value="admin" required class="db-inp">
        </div>
        <div class="db-fld">
            <label class="db-lbl">Admin Email</label>
            <input type="email" name="admin_email" value="{{ $clientEmail }}" required class="db-inp">
        </div>
        <button type="submit" class="db-btn"><i class="ri-download-cloud-2-line"></i> Install WordPress</button>
    </form>
</div>
@endif

<div class="db-card">
    <div class="db-ch"><span><i class="ri-stack-line"></i> Installed WordPress Sites</span></div>
    @if(count($installs) === 0)
        <div class="db-empty">No WordPress installations found on this hosting account yet.</div>
    @else
        <div style="overflow-x:auto;">
            <table class="pn-table">
                <thead>
                    <tr>
                        <th>Site URL / Path</th>
                        <th>Version</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($installs as $in)
                        @php $inRel = (string) ($in['rel'] ?? ''); @endphp
                        <tr>
                            <td>
                                <strong>{{ $in['url'] ?? ($in['domain'] ?? $inRel) }}</strong>
                                @if($inRel !== '')<br><small class="text-muted">/{{ $inRel }}</small>@endif
                            </td>
                            <td>
                                <span class="badge {{ !empty($in['update']) ? 'badge-pending' : 'badge-active' }}">
                                    v{{ $in['version'] ?? '?' }} {{ !empty($in['update']) ? '(Update Available)' : '' }}
                                </span>
                            </td>
                            <td style="text-align:right;display:flex;gap:6px;justify-content:flex-end;flex-wrap:wrap;">
                                <form method="POST" action="{{ route('client.services.bhpanel.wordpress', $service) }}" style="margin:0;">
                                    @csrf
                                    <input type="hidden" name="bhp_action" value="login">
                                    <input type="hidden" name="rel" value="{{ $inRel }}">
                                    <button type="submit" class="db-act"><i class="ri-login-box-line"></i> wp-admin</button>
                                </form>
                                <form method="POST" action="{{ route('client.services.bhpanel.wordpress', $service) }}" style="margin:0;">
                                    @csrf
                                    <input type="hidden" name="bhp_action" value="update">
                                    <input type="hidden" name="rel" value="{{ $inRel }}">
                                    <button type="submit" class="db-act"><i class="ri-refresh-line"></i> Update All</button>
                                </form>
                                <form method="POST" action="{{ route('client.services.bhpanel.wordpress', $service) }}" style="margin:0;">
                                    @csrf
                                    <input type="hidden" name="bhp_action" value="backup">
                                    <input type="hidden" name="rel" value="{{ $inRel }}">
                                    <button type="submit" class="db-act"><i class="ri-archive-line"></i> Backup</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection
