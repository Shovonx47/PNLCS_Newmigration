<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Quote #{{ $quote->id }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a1a; line-height: 1.5; }
        .container { padding: 36px 40px; }
        .header { display: table; width: 100%; margin-bottom: 26px; }
        .header-left { display: table-cell; width: 55%; vertical-align: top; }
        .header-right { display: table-cell; width: 45%; vertical-align: top; text-align: right; }
        .company-name { font-size: 20px; font-weight: bold; color: #1a4d80; margin-bottom: 4px; }
        .quote-title { font-size: 24px; font-weight: bold; color: #111; letter-spacing: 0.5px; }
        .quote-number { font-size: 13px; color: #555; margin-top: 3px; }
        .status-badge { display: inline-block; margin-top: 8px; padding: 3px 10px; border: 1px solid #999; border-radius: 3px; font-size: 11px; font-weight: bold; text-transform: uppercase; color: #333; }
        .addresses { display: table; width: 100%; margin-bottom: 24px; }
        .address-box { display: table-cell; width: 50%; vertical-align: top; padding-right: 16px; }
        .address-box h4 { font-size: 11px; text-transform: uppercase; color: #555; margin-bottom: 6px; letter-spacing: 0.8px; border-bottom: 1px solid #ccc; padding-bottom: 3px; }
        .proposal-box { margin-bottom: 20px; padding: 12px 14px; background: #f8fafc; border-left: 3px solid #1a4d80; font-size: 12px; white-space: pre-wrap; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table thead th { background: #1a4d80; color: #fff; padding: 8px 10px; text-align: left; font-size: 11px; text-transform: uppercase; }
        .items-table tbody td { padding: 8px 10px; border-bottom: 1px solid #ddd; vertical-align: top; }
        .items-table tbody tr:last-child td { border-bottom: 2px solid #1a4d80; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals { width: 280px; margin-left: auto; }
        .totals table { width: 100%; border-collapse: collapse; }
        .totals td { padding: 5px 10px; }
        .totals .total-row td { font-size: 14px; font-weight: bold; color: #111; border-top: 2px solid #1a4d80; }
        .notes { margin-top: 22px; padding: 12px 14px; background: #f4f4f5; border-radius: 4px; font-size: 11px; white-space: pre-wrap; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="header-left">
            <div class="company-name">{{ company_name() }}</div>
            @if(\App\Models\Setting::get('Address'))<div>{{ \App\Models\Setting::get('Address') }}</div>@endif
            @if(\App\Models\Setting::get('Email'))<div>{{ \App\Models\Setting::get('Email') }}</div>@endif
        </div>
        <div class="header-right">
            <div class="quote-title">QUOTE #{{ $quote->id }}</div>
            <div class="quote-number">{{ $quote->subject }}</div>
            <div><span class="status-badge">{{ $quote->status }}</span></div>
        </div>
    </div>

    <div class="addresses">
        <div class="address-box">
            <h4>Prepared For</h4>
            @php
                $c = $quote->client;
                $companyLine = $c?->company_name ?: $quote->company_name;
                $addr1 = $c?->address1 ?: $quote->address1;
                $addr2 = $c?->address2 ?: $quote->address2;
                $city = $c?->city ?: $quote->city;
                $state = $c?->state ?: $quote->state;
                $postcode = $c?->postcode ?: $quote->postcode;
                $country = $c?->country ?: $quote->country;
            @endphp
            <div><strong>{{ $quote->recipientName() }}</strong></div>
            @if($companyLine)<div>{{ $companyLine }}</div>@endif
            @if($addr1)<div>{{ $addr1 }}</div>@endif
            @if($addr2)<div>{{ $addr2 }}</div>@endif
            @if($city || $state || $postcode)
                <div>{{ trim(implode(', ', array_filter([$city, $state, $postcode]))) }}</div>
            @endif
            @if($country)<div>{{ \App\Support\Countries::ALL[$country] ?? $country }}</div>@endif
            @if($quote->recipientEmail())<div>{{ $quote->recipientEmail() }}</div>@endif
        </div>
        <div class="address-box" style="padding-right:0;">
            <h4>Quote Details</h4>
            <div><strong>Date Created:</strong> {{ $quote->date?->format(date_fmt()) }}</div>
            <div><strong>Valid Until:</strong> {{ $quote->valid_until?->format(date_fmt()) }}</div>
            <div><strong>Stage:</strong> {{ $quote->status }}</div>
        </div>
    </div>

    @if($quote->proposal)
        <div class="proposal-box">{{ $quote->proposal }}</div>
    @endif

    <table class="items-table">
        <thead>
            <tr>
                <th class="text-center" style="width:60px;">Qty</th>
                <th>Description</th>
                <th class="text-right" style="width:105px;">Unit Price</th>
                <th class="text-right" style="width:95px;">Discount</th>
                <th class="text-right" style="width:110px;">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($quote->items as $item)
                @php
                    $lineTotal = max(0, ($item->quantity * $item->unit_price) - $item->discount);
                @endphp
                <tr>
                    <td class="text-center">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                    <td style="white-space:pre-wrap;">{{ $item->description }}</td>
                    <td class="text-right">{{ $quote->formatMoney($item->unit_price) }}</td>
                    <td class="text-right">
                        @if((float) ($item->discount_percent ?? 0) > 0)
                            {{ rtrim(rtrim(number_format((float) $item->discount_percent, 2), '0'), '.') }}%
                        @elseif((float) $item->discount > 0)
                            {{ $quote->formatMoney($item->discount) }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-right">{{ $quote->formatMoney($lineTotal) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">No line items.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <td class="text-right"><strong>Sub Total:</strong></td>
                <td class="text-right">{{ $quote->formatMoney($quote->subtotal) }}</td>
            </tr>
            @if((float) $quote->tax > 0)
                <tr>
                    <td class="text-right"><strong>Tax:</strong></td>
                    <td class="text-right">{{ $quote->formatMoney($quote->tax) }}</td>
                </tr>
            @endif
            <tr class="total-row">
                <td class="text-right">Total Due:</td>
                <td class="text-right">{{ $quote->formatMoney($quote->total) }}</td>
            </tr>
        </table>
    </div>

    @if($quote->customer_notes)
        <div class="notes"><strong>Notes:</strong><br>{{ $quote->customer_notes }}</div>
    @endif
</div>
</body>
</html>
