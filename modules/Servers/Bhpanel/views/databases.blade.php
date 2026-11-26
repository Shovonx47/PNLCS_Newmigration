@extends("client.layouts.app")
@section("title", "Databases — " . ($service->domain ?: "Hosting"))
@section("content")

@include("bhpanel::partials.nav")

<div class="db-head">
    <div class="db-head-ic"><i class="ri-database-2-line"></i></div>
    <div>
        <h1>Databases ({{ strtoupper($engine) }})</h1>
        <div class="sub">Local host port <code>{{ $port }}</code> &middot; Database prefix <code>{{ $prefix }}</code></div>
    </div>
    <div style="margin-left:auto;display:flex;gap:6px;">
        @foreach($engines as $eng)
            <a href="{{ route('client.services.bhpanel.databases', ['service' => $service, 'engine' => $eng]) }}" class="db-act" style="{{ $engine === $eng ? 'border-color:var(--primary);color:var(--primary);' : '' }}">{{ strtoupper($eng) }}</a>
        @endforeach
    </div>
</div>

@if($missingEngine)
    <div class="db-card"><div class="db-empty">{{ strtoupper($engine) }} is not enabled on this hosting plan.</div></div>
@else
    <div class="db-card">
        <div class="db-ch"><span><i class="ri-add-circle-line"></i> Create {{ strtoupper($engine) }} Database</span></div>
        <form method="POST" action="{{ route('client.services.bhpanel.databases', ['service' => $service, 'engine' => $engine]) }}" class="db-form">
            @csrf
            <input type="hidden" name="bhp_action" value="create">
            <input type="hidden" name="engine" value="{{ $engine }}">
            <div class="db-fld">
                <label class="db-lbl">Database Suffix ({{ $prefix }}...)</label>
                <input type="text" name="name" required maxlength="32" pattern="[A-Za-z0-9_]+" placeholder="appdb" class="db-inp">
            </div>
            <div class="db-fld">
                <label class="db-lbl">Database Password (min 12 chars)</label>
                <input type="password" name="password" required minlength="12" class="db-inp" autocomplete="new-password">
            </div>
            <button type="submit" class="db-btn"><i class="ri-add-line"></i> Create Database</button>
        </form>
    </div>

    <div class="db-card">
        <div class="db-ch"><span><i class="ri-stack-line"></i> Existing Databases ({{ count($dbs) }})</span></div>
        @if(count($dbs) === 0)
            <div class="db-empty">No {{ strtoupper($engine) }} databases created yet.</div>
        @else
            <table class="pn-table">
                <thead>
                    <tr>
                        <th>Database Name</th>
                        <th>Size</th>
                        <th>Assigned Users</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dbs as $db)
                        <tr>
                            <td><code>{{ $db['name'] }}</code></td>
                            <td>{{ $db['size_mb'] !== null ? number_format($db['size_mb'], 2).' MB' : '—' }}</td>
                            <td>{{ count($db['users']) > 0 ? implode(', ', $db['users']) : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    @if(count($logins) > 0 && $engine !== 'mongodb')
    <div class="db-card">
        <div class="db-ch"><span><i class="ri-key-2-line"></i> Change Database User Password</span></div>
        <form method="POST" action="{{ route('client.services.bhpanel.databases', ['service' => $service, 'engine' => $engine]) }}" class="db-form">
            @csrf
            <input type="hidden" name="bhp_action" value="password">
            <input type="hidden" name="engine" value="{{ $engine }}">
            <div class="db-fld">
                <label class="db-lbl">Database User</label>
                <select name="db_user" class="db-inp">
                    @foreach($logins as $u)<option value="{{ $u }}">{{ $u }}</option>@endforeach
                </select>
            </div>
            <div class="db-fld">
                <label class="db-lbl">New Password (min 12 chars)</label>
                <input type="password" name="password" required minlength="12" class="db-inp" autocomplete="new-password">
            </div>
            <button type="submit" class="db-btn"><i class="ri-save-line"></i> Update Password</button>
        </form>
    </div>
    @endif
@endif

@endsection
