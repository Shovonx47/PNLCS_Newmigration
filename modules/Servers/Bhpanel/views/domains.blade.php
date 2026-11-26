@extends("client.layouts.app")
@section("title", "Domains — " . ($service->domain ?: "Hosting"))
@section("content")

@include("bhpanel::partials.nav")

<div class="db-head">
    <div class="db-head-ic"><i class="ri-global-line"></i></div>
    <div>
        <h1>Domains &amp; PHP Settings</h1>
        <div class="sub">Manage addon domains, subdomains, PHP versions, and HTTP redirects.</div>
    </div>
    <span class="db-cnt">{{ count($sites) }} domain(s)</span>
</div>

<div class="db-card">
    <div class="db-ch"><span><i class="ri-add-circle-line"></i> Add Domain or Subdomain</span></div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:0;">
        <form method="POST" action="{{ route('client.services.bhpanel.domains', $service) }}" class="db-form" style="border-right:1px solid var(--border);">
            @csrf
            <input type="hidden" name="bhp_action" value="add">
            <input type="hidden" name="kind" value="addon">
            <div class="db-fld">
                <label class="db-lbl">Addon Domain Name</label>
                <input type="text" name="domain" required placeholder="example.com" class="db-inp">
            </div>
            <button type="submit" class="db-btn"><i class="ri-add-line"></i> Add Domain</button>
        </form>

        @if(count($parents) > 0)
        <form method="POST" action="{{ route('client.services.bhpanel.domains', $service) }}" class="db-form">
            @csrf
            <input type="hidden" name="bhp_action" value="add">
            <input type="hidden" name="kind" value="subdomain">
            <div class="db-fld">
                <label class="db-lbl">Subdomain Prefix</label>
                <input type="text" name="label" required placeholder="app" class="db-inp">
            </div>
            <div class="db-fld">
                <label class="db-lbl">Parent Domain</label>
                <select name="parent" class="db-inp">
                    @foreach($parents as $p)<option value="{{ $p }}">.{{ $p }}</option>@endforeach
                </select>
            </div>
            <button type="submit" class="db-btn"><i class="ri-node-tree"></i> Create Subdomain</button>
        </form>
        @endif
    </div>
</div>

<div class="db-card">
    <div class="db-ch"><span><i class="ri-pages-line"></i> Hosted Websites</span></div>
    @if(count($sites) === 0)
        <div class="db-empty">No websites listed on this account.</div>
    @else
        <div style="overflow-x:auto;">
            <table class="pn-table">
                <thead>
                    <tr>
                        <th>Domain</th>
                        <th>Type</th>
                        <th>SSL</th>
                        <th>PHP Version</th>
                        <th style="text-align:right;">Redirect</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sites as $s)
                        @php
                            $dom = (string) ($s['domain'] ?? '');
                            $cert = $ssl[strtolower($dom)] ?? null;
                        @endphp
                        <tr>
                            <td><strong>{{ $dom }}</strong></td>
                            <td><span class="badge badge-active">{{ ucfirst((string) ($s['kind'] ?? 'addon')) }}</span></td>
                            <td>
                                @if($cert && !empty($cert['has_cert']))
                                    <span class="badge badge-active">SSL Active</span>
                                @else
                                    <a href="{{ route('client.services.bhpanel.ssl', $service) }}" class="db-act">Issue SSL</a>
                                @endif
                            </td>
                            <td>
                                @if(count($phpVersions) > 0)
                                    <form method="POST" action="{{ route('client.services.bhpanel.domains', $service) }}" style="display:inline-flex;gap:6px;align-items:center;margin:0;">
                                        @csrf
                                        <input type="hidden" name="bhp_action" value="php">
                                        <input type="hidden" name="domain" value="{{ $dom }}">
                                        <select name="version" class="db-inp" style="width:auto;padding:4px 8px;font-size:12.5px;">
                                            @foreach($phpVersions as $ver)
                                                <option value="{{ $ver }}" @selected(($s['php'] ?? '') === $ver)>PHP {{ $ver }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="db-act">Apply</button>
                                    </form>
                                @else
                                    <span class="text-muted">{{ $s['php'] ?? 'Default' }}</span>
                                @endif
                            </td>
                            <td style="text-align:right;">
                                @if(!empty($s['redirect_to']))
                                    <form method="POST" action="{{ route('client.services.bhpanel.domains', $service) }}" style="display:inline-flex;gap:6px;align-items:center;margin:0;">
                                        @csrf
                                        <input type="hidden" name="bhp_action" value="unredirect">
                                        <input type="hidden" name="domain" value="{{ $dom }}">
                                        <small class="text-muted">&rarr; {{ $s['redirect_to'] }}</small>
                                        <button type="submit" class="db-act">Remove</button>
                                    </form>
                                @else
                                    <details style="display:inline-block;text-align:left;">
                                        <summary class="db-act">Set Redirect</summary>
                                        <form method="POST" action="{{ route('client.services.bhpanel.domains', $service) }}" style="margin-top:6px;padding:10px;background:var(--bg);border:1px solid var(--border);border-radius:10px;display:flex;gap:6px;flex-wrap:wrap;min-width:260px;">
                                            @csrf
                                            <input type="hidden" name="bhp_action" value="redirect">
                                            <input type="hidden" name="domain" value="{{ $dom }}">
                                            <input type="url" name="to" required placeholder="https://target.example.com" class="db-inp" style="padding:6px 10px;font-size:12.5px;">
                                            <select name="code" class="db-inp" style="width:auto;padding:5px 8px;font-size:12px;">
                                                <option value="301">301 Permanent</option>
                                                <option value="302">302 Temporary</option>
                                            </select>
                                            <button type="submit" class="db-btn" style="padding:6px 12px;font-size:12px;">Save</button>
                                        </form>
                                    </details>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection
