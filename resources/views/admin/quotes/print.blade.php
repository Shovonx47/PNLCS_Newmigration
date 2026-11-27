<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Printable Quote #{{ $quote->id }} - {{ company_name() }}</title>
    <style>
        body { font-family: "Open Sans", Arial, sans-serif; font-size: 13px; color: #222; background: #f4f6f8; margin: 0; padding: 24px; }
        .print-toolbar { max-width: 840px; margin: 0 auto 14px; display: flex; justify-content: flex-end; gap: 8px; }
        .btn { padding: 6px 14px; border-radius: 4px; border: 1px solid #ccc; background: #fff; color: #333; font-size: 13px; cursor: pointer; text-decoration: none; }
        .btn-primary { background: #1a4d80; border-color: #1a4d80; color: #fff; }
        .sheet { max-width: 840px; margin: 0 auto; background: #fff; border: 1px solid #ddd; border-radius: 4px; padding: 36px 42px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .top-row { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #1a4d80; padding-bottom: 18px; margin-bottom: 22px; }
        .company { font-size: 22px; font-weight: 700; color: #1a4d80; }
        .quote-badge { display: inline-block; padding: 3px 10px; border-radius: 3px; background: #eef2f7; border: 1px solid #cbd5e1; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-top: 6px; }
        .cols { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 22px; }
        .col h4 { margin: 0 0 6px; font-size: 11px; text-transform: uppercase; color: #666; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }
        .proposal { margin-bottom: 20px; padding: 12px 15px; background: #f8fafc; border-left: 3px solid #1a4d80; white-space: pre-wrap; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        table.items th { background: #1a4d80; color: #fff; padding: 8px 10px; font-size: 12px; text-align: left; }
        table.items td { padding: 8px 10px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals { width: 280px; margin-left: auto; border-collapse: collapse; }
        .totals td { padding: 6px 10px; }
        .totals tr.grand td { border-top: 2px solid #1a4d80; font-weight: 700; font-size: 15px; }
        .footer-notes { margin-top: 22px; padding: 12px 15px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; white-space: pre-wrap; }
        @media print {
            body { background: #fff; padding: 0; }
            .print-toolbar { display: none !important; }
            .sheet { border: 0; box-shadow: none; padding: 0; max-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="print-toolbar">
        <button type="button" class="btn btn-primary" onclick="window.print()">Print Quote</button>
        <a href="{{ route('admin.quotes.edit', $quote) }}" class="btn">Back to Quote</a>
    </div>
    <div class="sheet">
        <div class="top-row">
            <div>
                <div class="company">{{ company_name() }}</div>
                @if(\App\Models\Setting::get('Address'))<div>{{ \App\Models\Setting::get('Address') }}</div>@endif
                @if(\App\Models\Setting::get('Email'))<div>{{ \App\Models\Setting::get('Email') }}</div>@endif
            </div>
            <div class="text-right">
                <div style="font-size:24px;font-weight:700;">Quote #{{ $quote->id }}</div>
                <div style="color:#555;margin-top:2px;">{{ $quote->subject }}</div>
                <div class="quote-badge">{{ $quote->status }}</div>
            </div>
        </div>

        <div class="cols">
            <div class="col">
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
            <div class="col">
                <h4>Quote Summary</h4>
                <div><strong>Date Created:</strong> {{ $quote->date?->format(date_fmt()) }}</div>
                <div><strong>Valid Until:</strong> {{ $quote->valid_until?->format(date_fmt()) }}</div>
                <div><strong>Stage:</strong> {{ $quote->status }}</div>
            </div>
        </div>

        @if($quote->proposal)
            <div class="proposal">{{ $quote->proposal }}</div>
        @endif

        <table class="items">
            <thead>
                <tr>
                    <th class="text-center" style="width:65px;">Qty</th>
                    <th>Description</th>
                    <th class="text-right" style="width:115px;">Unit Price</th>
                    <th class="text-right" style="width:100px;">Discount</th>
                    <th class="text-right" style="width:120px;">Total</th>
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
                        <td class="text-right"><strong>{{ $quote->formatMoney($lineTotal) }}</strong></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center" style="color:#888;padding:18px;">No line items.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <table class="totals">
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
            <tr class="grand">
                <td class="text-right">Total Due:</td>
                <td class="text-right">{{ $quote->formatMoney($quote->total) }}</td>
            </tr>
        </table>

        @if($quote->customer_notes)
            <div class="footer-notes"><strong>Customer Notes:</strong><br>{{ $quote->customer_notes }}</div>
        @endif
    </div>
</body>
</html>
