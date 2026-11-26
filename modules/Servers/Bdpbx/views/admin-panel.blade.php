<div class="card" style="margin-bottom:15px;">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
        <strong>BDPBX Cloud PBX Management</strong>
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
            @if($domain !== '')
                <a href="{{ route('admin.bdpbx.customer-sso', $service) }}" target="_blank" rel="noopener" class="btn btn-primary btn-xs">Tenant SSO</a>
            @endif
            @if($server)
                <a href="{{ route('admin.bdpbx.super-sso', $server) }}" target="_blank" rel="noopener" class="btn btn-default btn-xs">Node SuperAdmin SSO</a>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:14px;font-size:13px;">
            <div><span class="text-muted">Tenant Domain:</span><br><code>{{ $domain ?: 'Not provisioned' }}</code></div>
            <div><span class="text-muted">PBX Server Node:</span><br><strong>{{ $server?->name ?? 'Unassigned' }}</strong> @if($server?->hostname)<small class="text-muted">({{ $server->hostname }})</small>@endif</div>
            <div><span class="text-muted">30-Day Recordings:</span><br><strong>{{ number_format($recordingsCount) }}</strong></div>
            <div><span class="text-muted">Termination Lock:</span><br><strong>{{ !empty($moduleData['soft_terminated']) ? 'Soft-Locked (CDRs Preserved)' : 'Normal' }}</strong></div>
        </div>

        @if(count($extensions) > 0)
        <form method="POST" action="{{ route('admin.bdpbx.extensions.update', $service) }}" style="margin-bottom:14px;">
            @csrf
            <table class="data-table" style="margin-bottom:10px;">
                <thead>
                    <tr>
                        <th>Extension</th>
                        <th>Caller ID Name</th>
                        <th>Status</th>
                        <th>Set New SIP Password</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($extensions as $ext)
                        @php $extNum = (string) ($ext['extension'] ?? ''); @endphp
                        <tr>
                            <td><code>{{ $extNum }}</code></td>
                            <td>{{ $ext['effective_caller_id_name'] ?? '—' }}</td>
                            <td>
                                @if(!empty($ext['enabled']))
                                    <span class="badge badge-active">Active</span>
                                @else
                                    <span class="badge badge-cancelled">Disabled</span>
                                @endif
                            </td>
                            <td>
                                <input type="password" name="extensions[{{ $extNum }}]" placeholder="Leave blank to keep" minlength="6" maxlength="64" class="form-control" style="max-width:220px;height:30px;font-size:12px;">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <button type="submit" class="btn btn-primary btn-xs">Save Extension Passwords</button>
        </form>
        @endif

        <form method="POST" action="{{ route('admin.bdpbx.unblock-ip', $service) }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;padding-top:10px;border-top:1px solid #eee;">
            @csrf
            <label class="form-label" style="margin:0;font-size:12px;">Unblock Client IP on PBX Firewall:</label>
            <input type="text" name="ip_address" placeholder="e.g. 103.15.140.151" required class="form-control" style="max-width:220px;height:30px;font-size:12px;">
            <button type="submit" class="btn btn-default btn-xs">Unblock IP</button>
        </form>
    </div>
</div>
