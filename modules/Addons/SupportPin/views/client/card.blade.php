<div class="card mb-4">
    <div class="card-body py-3 d-flex align-items-center justify-content-between flex-wrap">
        <div class="mr-3">
            <strong><i class="fas fa-key fa-fw"></i> {{ 'Support Verification PIN' }}</strong>
            <div class="text-muted small mt-1">
                {{ 'Share this 6-digit PIN only with official support staff when verifying your account. Expires ' . ($expiresAt?->diffForHumans() ?? '') . '.' }}
            </div>
        </div>
        <div class="d-flex align-items-center mt-2 mt-sm-0">
            <code id="scSupportPinDisplay" data-pin="{{ $pin }}" class="px-3 py-1 mr-2 border rounded bg-light font-weight-bold" style="font-size:17px;letter-spacing:3px;">••••••</code>
            <button type="button" class="btn btn-default btn-sm mr-2" onclick="(function(btn){ var el=document.getElementById('scSupportPinDisplay'); if(!el) return; var hidden = el.textContent === '••••••'; el.textContent = hidden ? (el.getAttribute('data-pin') || '') : '••••••'; btn.textContent = hidden ? 'Hide' : 'Show PIN'; })(this)">{{ 'Show PIN' }}</button>
            <form method="POST" action="{{ route('client.support-pin.regenerate') }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-default btn-sm">{{ 'Regenerate' }}</button>
            </form>
        </div>
    </div>
</div>
