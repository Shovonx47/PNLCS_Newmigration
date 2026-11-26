<style>
    .db-back{display:inline-flex;align-items:center;gap:6px;color:var(--muted);text-decoration:none;font-size:13px;font-weight:600;margin-bottom:14px}
    .db-back:hover{color:var(--primary)}
    .db-nav{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px}
    .db-tab{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:999px;font-size:12.5px;font-weight:700;text-decoration:none;border:1px solid var(--border);background:var(--card);color:var(--muted);transition:all .15s}
    .db-tab:hover{color:var(--primary);border-color:var(--primary)}
    .db-tab.active{background:var(--primary);color:#fff;border-color:var(--primary)}
    .db-head{display:flex;align-items:center;gap:14px;margin-bottom:18px;flex-wrap:wrap}
    .db-head-ic{width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;display:flex;align-items:center;justify-content:center;font-size:24px}
    .db-head h1{font-size:22px;font-weight:800;margin:0;letter-spacing:-.5px;color:var(--text)}
    .db-head .sub{font-size:13px;color:var(--muted)}
    .db-cnt{margin-left:auto;font-size:12px;font-weight:700;color:var(--muted);background:var(--bg);border:1px solid var(--border);padding:6px 13px;border-radius:999px}
    .db-card{background:var(--card);border:1px solid var(--border);border-radius:14px;box-shadow:var(--shadow);margin-bottom:18px;overflow:hidden}
    .db-ch{padding:14px 18px;border-bottom:1px solid var(--border);font-size:13px;font-weight:800;color:var(--text);display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap}
    .db-form{display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;padding:18px}
    .db-fld{flex:1;min-width:150px}
    .db-lbl{display:block;font-size:12px;font-weight:600;color:var(--muted);margin-bottom:5px}
    .db-inp{width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:9px;background:var(--bg);color:var(--text);font-size:13.5px}
    .db-btn{display:inline-flex;align-items:center;gap:7px;padding:9px 16px;border:none;border-radius:9px;background:var(--primary);color:#fff;font-weight:700;font-size:13px;cursor:pointer;white-space:nowrap;text-decoration:none}
    .db-btn:hover{background:var(--primary-dark)}
    .db-act{display:inline-flex;align-items:center;gap:5px;padding:6px 11px;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;border:1px solid var(--border);background:var(--card);color:var(--text);list-style:none;text-decoration:none}
    .db-act:hover{border-color:var(--primary);color:var(--primary)}
    .db-empty{padding:26px;text-align:center;color:var(--muted);font-size:13.5px}
</style>

<a href="{{ route('client.services.show', $service) }}" class="db-back">
    <i class="ri-arrow-left-line"></i> {{ $service->domain ?: ($service->product?->name ?? __('client.services.title')) }}
</a>

<div class="db-nav">
    @foreach([
        'wordpress' => ['WordPress', 'ri-wordpress-line'],
        'domains' => ['Domains', 'ri-global-line'],
        'email' => ['Email', 'ri-mail-line'],
        'databases' => ['Databases', 'ri-database-2-line'],
        'ssl' => ['SSL', 'ri-shield-keyhole-line'],
        'security' => ['Security', 'ri-lock-password-line'],
    ] as $key => [$label, $icon])
        <a href="{{ route('client.services.bhpanel.' . $key, $service) }}" class="db-tab {{ ($activeTab ?? '') === $key ? 'active' : '' }}">
            <i class="{{ $icon }}"></i> {{ $label }}
        </a>
    @endforeach
</div>

@if(!empty($pageError))
    <div class="pn-alert pn-alert-warning" style="margin-bottom:18px;">{{ $pageError }}</div>
@endif
