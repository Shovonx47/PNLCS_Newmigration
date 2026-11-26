@php
    $primaryNotice = $notices->first();
    $speed = max(1, min(20, (int) ($primaryNotice?->scroll_speed ?? 5)));
    $durationSeconds = max(10, 42 - ($speed * 2));
@endphp
<style>
    .sc-notice-bar{background:var(--primary,#2563eb);color:#fff;font-size:13px;line-height:1.4;border-bottom:1px solid rgba(255,255,255,.15);overflow:hidden;position:relative;z-index:40}
    .sc-notice-inner{max-width:1280px;margin:0 auto;padding:7px 16px;display:flex;align-items:center;gap:12px}
    .sc-notice-tag{background:rgba(255,255,255,.18);padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;letter-spacing:.3px;text-transform:uppercase;flex-shrink:0}
    .sc-notice-viewport{overflow:hidden;flex:1;white-space:nowrap}
    .sc-notice-track{display:inline-flex;align-items:center;gap:48px;padding-left:100%;animation:scNoticeMarquee {{ $durationSeconds }}s linear infinite}
    .sc-notice-viewport:hover .sc-notice-track{animation-play-state:paused}
    .sc-notice-item{display:inline-flex;align-items:center;gap:10px;color:#fff;text-decoration:none}
    .sc-notice-link{color:#fff;text-decoration:underline;font-weight:600}
    .sc-notice-coupon{background:#fff;color:var(--primary,#1d4ed8);border:none;border-radius:4px;padding:2px 8px;font-size:11.5px;font-weight:700;cursor:pointer}
    @keyframes scNoticeMarquee{0%{transform:translateX(0)}100%{transform:translateX(-100%)}}
</style>
<div class="sc-notice-bar" role="region" aria-label="Announcement">
    <div class="sc-notice-inner">
        <span class="sc-notice-tag">Notice</span>
        <div class="sc-notice-viewport">
            <div class="sc-notice-track">
                @foreach($notices as $notice)
                    @php $safeUrl = $notice->resolvedUrl(); @endphp
                    <span class="sc-notice-item">
                        @if($safeUrl)
                            <a href="{{ $safeUrl }}" class="sc-notice-link">{{ $notice->content }}</a>
                        @else
                            <span>{{ $notice->content }}</span>
                        @endif
                        @if($notice->coupon_enabled && filled($notice->coupon_code))
                            <button type="button" class="sc-notice-coupon" data-coupon="{{ $notice->coupon_code }}" onclick="scCopyNoticeCoupon(this)">
                                Code: {{ $notice->coupon_code }}
                            </button>
                        @endif
                    </span>
                @endforeach
            </div>
        </div>
    </div>
</div>
<script>
function scCopyNoticeCoupon(btn) {
    if (!btn || !btn.dataset.coupon) return;
    var code = btn.dataset.coupon;
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(code).then(function () {
            btn.textContent = 'Copied: ' + code;
        });
    }
}
</script>
