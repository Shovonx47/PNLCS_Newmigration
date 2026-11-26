@php
    $cid = $widgetId;
@endphp

<div class="italk-card" id="{{ $cid }}" style="padding:18px 20px;">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div>
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--muted);">
                {{ $endpoint?->program === 'icc' ? 'ICT Talk' : 'iTalk' }} &middot; IP Number Balance
            </div>
            <div style="display:flex;align-items:baseline;gap:6px;margin-top:6px;">
                <span style="font-size:30px;font-weight:800;letter-spacing:-.5px;color:var(--text);">
                    <span style="font-size:16px;opacity:.6;">৳</span><span data-balance>{{ $balance !== null ? number_format($balance, 2) : '—' }}</span>
                </span>
                <span style="font-size:12px;font-weight:600;color:var(--muted);">BDT</span>
            </div>
            <div style="font-size:12px;color:var(--muted);margin-top:7px;">
                <span data-note>
                    @if($updatedAt)
                        Last updated {{ $updatedAt->format('d M Y, H:i') }}
                    @else
                        Not fetched yet
                    @endif
                </span>
            </div>
        </div>

        <div style="text-align:right;">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--muted);margin-bottom:6px;">IP Number</div>
            <span style="display:inline-flex;align-items:center;gap:7px;font-family:ui-monospace,Menlo,monospace;font-size:13px;font-weight:700;background:var(--primary-light);color:var(--primary);padding:7px 13px;border-radius:9px;">
                {{ $mapping->ip_number }}
            </span>
        </div>
    </div>

    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:16px;padding-top:14px;border-top:1px solid var(--border);">
        <span style="font-size:12px;color:var(--muted);">
            {{ $endpoint?->label ?? 'Balance endpoint' }} &middot; live balance, refreshed on request
        </span>
        <div style="display:flex;align-items:center;gap:10px;">
            <span data-flash style="font-size:12px;font-weight:600;display:none;"></span>
            <button type="button" data-refresh class="btn btn-default btn-xs" data-idle-label="Refresh Balance">
                Refresh Balance
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    var root = document.getElementById(@js($cid));
    if (!root) { return; }

    var button = root.querySelector('[data-refresh]');
    var balance = root.querySelector('[data-balance]');
    var note = root.querySelector('[data-note]');
    var flash = root.querySelector('[data-flash]');
    var endpoint = @js($refreshUrl);
    var cooldown = {{ (int) $cooldown }};

    function show(message, ok) {
        if (!flash) { return; }
        flash.textContent = message;
        flash.style.display = 'inline';
        flash.style.color = ok ? '#059669' : '#b91c1c';
        window.clearTimeout(root._flashTimer);
        root._flashTimer = window.setTimeout(function () {
            flash.style.display = 'none';
        }, 5000);
    }

    function restore() {
        if (!button) { return; }
        button.disabled = false;
        button.textContent = button.getAttribute('data-idle-label') || 'Refresh Balance';
    }

    if (button) {
        button.addEventListener('click', function () {
            var token = document.querySelector('meta[name="csrf-token"]');
            button.disabled = true;
            button.textContent = 'Refreshing…';

            window.fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token ? token.getAttribute('content') : ''
                }
            }).then(function (response) {
                return response.json().then(function (body) {
                    return { ok: response.ok, body: body };
                });
            }).then(function (result) {
                var body = result.body || {};
                if (body.ok) {
                    if (balance && body.balance !== null) { balance.textContent = body.balance; }
                    if (note && body.updated_at) { note.textContent = 'Last updated ' + body.updated_at; }
                    show(body.message || 'Balance updated.', true);
                } else {
                    show(body.message || 'Balance could not be refreshed.', false);
                }
            }).catch(function () {
                show('Network error. Please try again.', false);
            }).then(function () {
                restore();
            });
        });
    }

    if (cooldown > 0) {
        window.setTimeout(function () {
            if (root._flashTimer === undefined) { return; }
        }, 0);
    }
})();
</script>
