@php
    $bhpApps = [
        ['k' => 'wordpress', 'name' => 'WordPress', 'desc' => 'Install, update, clone & one-click wp-admin', 'ic' => 'ri-wordpress-line', 'c' => '#2563eb', 'route' => route('client.services.bhpanel.wordpress', $service)],
        ['k' => 'domains', 'name' => 'Domains', 'desc' => 'Addon domains, subdomains, PHP & redirects', 'ic' => 'ri-global-line', 'c' => '#10b981', 'route' => route('client.services.bhpanel.domains', $service)],
        ['k' => 'email', 'name' => 'Email Accounts', 'desc' => 'Mailboxes, quotas, forwarders & mail setup', 'ic' => 'ri-mail-line', 'c' => '#8b5cf6', 'route' => route('client.services.bhpanel.email', $service)],
        ['k' => 'databases', 'name' => 'Databases', 'desc' => 'MySQL, PostgreSQL & MongoDB management', 'ic' => 'ri-database-2-line', 'c' => '#0ea5e9', 'route' => route('client.services.bhpanel.databases', $service)],
        ['k' => 'ssl', 'name' => 'SSL Certificates', 'desc' => 'Free AutoSSL issuance & Force HTTPS', 'ic' => 'ri-shield-keyhole-line', 'c' => '#059669', 'route' => route('client.services.bhpanel.ssl', $service)],
        ['k' => 'security', 'name' => 'Security', 'desc' => 'Two-factor auth, passkeys & IP unblock', 'ic' => 'ri-lock-password-line', 'c' => '#f59e0b', 'route' => route('client.services.bhpanel.security', $service)],
    ];

    $shortcuts = [
        'files' => ['File Manager', 'ri-folder-open-line'],
        'backups' => ['Backups', 'ri-archive-2-line'],
        'dns' => ['DNS Zone Editor', 'ri-route-line'],
        'cron' => ['Cron Jobs', 'ri-time-line'],
        'logs' => ['Access & Error Logs', 'ri-file-list-3-line'],
    ];
@endphp

@if($isActive)
<div class="sv-sec"><i class="ri-apps-2-line"></i> Hosting Control Center</div>
<div class="sv-apps">
    @foreach($bhpApps as $app)
        <a href="{{ $app['route'] }}" class="sv-app live">
            <span class="sv-aic" style="background:{{ $app['c'] }}1f;color:{{ $app['c'] }}"><i class="{{ $app['ic'] }}"></i></span>
            <div>
                <div class="sv-aname">{{ $app['name'] }}</div>
                <div class="sv-adesc">{{ $app['desc'] }}</div>
            </div>
        </a>
    @endforeach
</div>

<div class="sv-panel" style="margin-bottom:22px;">
    <div class="sv-ph"><i class="ri-external-link-line"></i> Go Straight to Control Panel Tool</div>
    <div style="padding:14px 20px;display:flex;gap:8px;flex-wrap:wrap;">
        @foreach($shortcuts as $dest => [$label, $icon])
            <form method="POST" action="{{ route('client.services.bhpanel.signin', $service) }}" target="_blank" style="margin:0;">
                @csrf
                <input type="hidden" name="to" value="{{ $dest }}">
                <button type="submit" class="pn-btn pn-btn-sm">
                    <i class="{{ $icon }}"></i> {{ $label }}
                </button>
            </form>
        @endforeach
    </div>
</div>
@endif

@if(count($nameservers) > 0)
<div class="sv-panel" style="margin-bottom:22px;">
    <div class="sv-ph"><i class="ri-server-line"></i> Assigned Nameservers</div>
    <div style="padding:14px 20px 6px;">
        @foreach($nameservers as $ns)
            <span class="sv-chip"><i class="ri-Compass-3-line ri-global-line"></i> {{ $ns }}</span>
        @endforeach
    </div>
</div>
@endif
