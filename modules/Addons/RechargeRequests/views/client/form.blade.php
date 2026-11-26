@extends('client.layouts.app')
@section('title', 'IP Number Balance Recharge')
@section('content')

<div class="pn-card mb-24" style="padding:24px;">
    <h2 style="margin:0 0 6px;">IP Number Calling Balance Recharge</h2>
    <p style="color:var(--muted);font-size:13.5px;margin:0 0 16px;">
        Enter the IP phone number exactly as it appears on your account and the amount you want to add.
        An invoice is raised immediately; the balance is topped up once it is paid.
    </p>

    <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:8px;padding:12px 14px;font-size:13px;line-height:1.6;margin-bottom:12px;">
        <strong>ভুল নাম্বার সতর্কতা:</strong> দয়া করে নিশ্চিত হয়ে আপনার আইপি ফোন নাম্বারটি দিন। একবার রিচার্জ সম্পন্ন হলে,
        <strong>ভুল নাম্বারে যাওয়া টাকার জন্য আমরা দায়ী নই</strong> এবং সেক্ষেত্রে কোনো প্রকার ফেরত (Refund) বা সমন্বয় (Adjustment) করা সম্ভব নয়।
    </div>

    <div style="background:#fffbeb;border:1px solid #fde68a;color:#92400e;border-radius:8px;padding:12px 14px;font-size:13px;line-height:1.6;margin-bottom:18px;">
        <strong>Important:</strong> this form is for customers who receive an <strong>IP Number</strong> service from us.
        Minimum top-up: {{ money_fmt($minAmount) }}, maximum per request: {{ money_fmt($maxAmount) }}.
    </div>

    @if($errors->any())
    <div class="pn-alert pn-alert-danger" role="alert" style="margin-bottom:14px;">
        {{ $errors->first() }}
    </div>
    @endif

    <form method="POST" action="{{ route('client.recharge.store') }}">
        @csrf
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;">
            <div class="form-group" style="margin:0;">
                <label for="mobile_number" style="font-size:12.5px;font-weight:600;">IP phone number</label>
                <input id="mobile_number" type="tel" name="mobile_number" value="{{ old('mobile_number') }}" class="form-control" placeholder="e.g. 096XXXXXXXX" required>
            </div>
            <div class="form-group" style="margin:0;">
                <label for="amount" style="font-size:12.5px;font-weight:600;">Recharge amount</label>
                <input id="amount" type="number" step="0.01" min="{{ $minAmount }}" max="{{ $maxAmount }}" name="amount" value="{{ old('amount') }}" class="form-control" placeholder="Minimum {{ money_fmt($minAmount) }}" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:16px;">
            Create recharge invoice
        </button>
    </form>
</div>

<div class="pn-card" style="padding:24px;">
    <div class="pn-section-title">Your recent recharge requests</div>
    @if($requests->isEmpty())
    <p style="color:var(--muted);font-size:13.5px;margin:10px 0 0;">You have not requested a recharge yet.</p>
    @else
    <div style="overflow-x:auto;">
        <table class="pn-table" style="margin-top:10px;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>IP number</th>
                    <th>Amount</th>
                    <th>Invoice</th>
                    <th>Requested</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($requests as $row)
                <tr>
                    <td>{{ $row->id }}</td>
                    <td style="font-family:ui-monospace,Menlo,monospace;">{{ $row->mobile_number }}</td>
                    <td>{{ money_fmt((float) $row->amount) }}</td>
                    <td>
                        @if($row->invoice)
                        <a href="{{ route('client.invoices.show', $row->invoice) }}">{{ $row->invoice->invoice_num }}</a>
                        @else
                        —
                        @endif
                    </td>
                    <td style="font-size:12.5px;color:var(--muted);">{{ $row->requested_at?->format(date_fmt().' H:i') }}</td>
                    <td><span class="badge badge-{{ strtolower($row->status) }}">{{ $row->status }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@endsection
