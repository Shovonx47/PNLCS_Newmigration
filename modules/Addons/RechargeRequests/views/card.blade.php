<div class="pn-card" style="padding:20px 24px;margin-bottom:24px;">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px;">
        <div>
            <div class="pn-section-title" style="margin:0;">IP Number Calling Balance Recharge</div>
            <div style="font-size:12.5px;color:var(--muted);margin-top:4px;">
                Top up the calling balance of your IP phone number. Minimum {{ money_fmt($minAmount) }}.
            </div>
        </div>
        <a href="{{ route('client.recharge') }}" class="btn btn-outline">Open recharge page</a>
    </div>

    <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:8px;padding:10px 12px;font-size:12.5px;line-height:1.5;margin-bottom:12px;">
        <strong>ভুল নাম্বার সতর্কতা:</strong> দয়া করে নিশ্চিত হয়ে আপনার আইপি ফোন নাম্বারটি দিন। একবার রিচার্জ সম্পন্ন হলে,
        <strong>ভুল নাম্বারে যাওয়া টাকার জন্য আমরা দায়ী নই</strong> এবং সেক্ষেত্রে কোনো প্রকার ফেরত (Refund) বা সমন্বয় (Adjustment) করা সম্ভব নয়।
    </div>

    <form method="POST" action="{{ route('client.recharge.store') }}" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
        @csrf
        <div class="form-group" style="margin:0;flex:1;min-width:180px;">
            <label for="rc-mobile" style="font-size:12px;font-weight:600;">IP phone number</label>
            <input id="rc-mobile" type="tel" name="mobile_number" class="form-control" placeholder="e.g. 096XXXXXXXX" required>
        </div>
        <div class="form-group" style="margin:0;flex:1;min-width:160px;">
            <label for="rc-amount" style="font-size:12px;font-weight:600;">Amount</label>
            <input id="rc-amount" type="number" step="0.01" min="{{ $minAmount }}" max="{{ $maxAmount }}" name="amount" class="form-control" placeholder="Minimum {{ money_fmt($minAmount) }}" required>
        </div>
        <button type="submit" class="btn btn-primary">Recharge now</button>
    </form>
</div>
