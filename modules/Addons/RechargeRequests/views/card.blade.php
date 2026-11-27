<div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0"><i class="fas fa-bolt fa-fw"></i>&nbsp;{{ 'IP Number Calling Balance Recharge' }}</h5>
        <a href="{{ route('client.recharge') }}" class="btn btn-default btn-sm">{{ 'Open recharge page' }}</a>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-2">
            {{ 'Top up the calling balance of your IP phone number. Minimum ' . money_fmt($minAmount) . '.' }}
        </p>

        <div class="alert alert-danger py-2 px-3 small mb-3">
            <strong>{{ 'ভুল নাম্বার সতর্কতা:' }}</strong> {{ 'দয়া করে নিশ্চিত হয়ে আপনার আইপি ফোন নাম্বারটি দিন। একবার রিচার্জ সম্পন্ন হলে,' }}
            <strong>{{ 'ভুল নাম্বারে যাওয়া টাকার জন্য আমরা দায়ী নই' }}</strong> {{ 'এবং সেক্ষেত্রে কোনো প্রকার ফেরত (Refund) বা সমন্বয় (Adjustment) করা সম্ভব নয়।' }}
        </div>

        <form method="POST" action="{{ route('client.recharge.store') }}">
            @csrf
            <div class="form-row align-items-end">
                <div class="col-md-5 form-group mb-2">
                    <label for="rc-mobile" class="small font-weight-bold mb-1">{{ 'IP phone number' }}</label>
                    <input id="rc-mobile" type="tel" name="mobile_number" class="form-control" placeholder="{{ 'e.g. 096XXXXXXXX' }}" required>
                </div>
                <div class="col-md-4 form-group mb-2">
                    <label for="rc-amount" class="small font-weight-bold mb-1">{{ 'Amount' }}</label>
                    <input id="rc-amount" type="number" step="0.01" min="{{ $minAmount }}" max="{{ $maxAmount }}" name="amount" class="form-control" placeholder="{{ 'Minimum ' . money_fmt($minAmount) }}" required>
                </div>
                <div class="col-md-3 form-group mb-2">
                    <button type="submit" class="btn btn-primary btn-block">{{ 'Recharge now' }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
