@extends("client.layouts.app")
@section("title", "SSL Certificates — " . ($service->domain ?: "Hosting"))
@section("content")

@include("bhpanel::partials.nav")

<div class="db-head">
    <div class="db-head-ic"><i class="ri-shield-keyhole-line"></i></div>
    <div>
        <h1>SSL Certificates &amp; HTTPS</h1>
        <div class="sub">Issue automated SSL certificates and manage Force HTTPS redirects for your domains.</div>
    </div>
    <span class="db-cnt">{{ count($sites) }} domain(s)</span>
</div>

<div class="db-card">
    <div class="db-ch"><span><i class="ri-lock-line"></i> Domain SSL Coverage</span></div>
    @if(count($sites) === 0)
        <div class="db-empty">No domains found on this hosting account.</div>
    @else
        <div style="overflow-x:auto;">
            <table class="pn-table">
                <thead>
                    <tr>
                        <th>Domain</th>
                        <th>Certificate Status</th>
                        <th>Expires</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sites as $s)
                        @php
                            $dom = (string) ($s['domain'] ?? '');
                            $row = $ssl[strtolower($dom)] ?? [];
                            $hasCert = !empty($row['has_cert']);
                            $forceHttps = !empty($row['force_https']);
                        @endphp
                        <tr>
                            <td><strong>{{ $dom }}</strong></td>
                            <td>
                                @if($hasCert)
                                    <span class="badge badge-active">Valid SSL @if(!empty($row['issuer']))({{ $row['issuer'] }})@endif</span>
                                @else
                                    <span class="badge badge-cancelled">No Certificate</span>
                                @endif
                            </td>
                            <td>{{ $row['expires'] ?? '—' }}</td>
                            <td style="text-align:right;display:flex;gap:8px;justify-content:flex-end;">
                                @if(!$hasCert)
                                    <form method="POST" action="{{ route('client.services.bhpanel.ssl', $service) }}" style="margin:0;">
                                        @csrf
                                        <input type="hidden" name="bhp_action" value="issue">
                                        <input type="hidden" name="domain" value="{{ $dom }}">
                                        <button type="submit" class="db-btn" style="padding:6px 12px;font-size:12px;"><i class="ri-shield-check-line"></i> Issue Free SSL</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('client.services.bhpanel.ssl', $service) }}" style="margin:0;">
                                        @csrf
                                        <input type="hidden" name="bhp_action" value="https">
                                        <input type="hidden" name="domain" value="{{ $dom }}">
                                        <input type="hidden" name="enable" value="{{ $forceHttps ? 'no' : 'yes' }}">
                                        <button type="submit" class="db-act">
                                            <i class="ri-lock-2-line"></i> {{ $forceHttps ? 'Disable Force HTTPS' : 'Enable Force HTTPS' }}
                                        </button>
                                    </form>
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
