<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\TaxRule;
use Illuminate\Support\Str;

class QuoteService
{
    public function createQuote(?Client $client, array $data): Quote
    {
        $quote = Quote::create([
            'client_id' => $client?->id,
            'first_name' => $client ? null : ($data['first_name'] ?? null),
            'last_name' => $client ? null : ($data['last_name'] ?? null),
            'company_name' => $client ? null : ($data['company_name'] ?? null),
            'email' => $client ? null : ($data['email'] ?? null),
            'address1' => $client ? null : ($data['address1'] ?? null),
            'address2' => $client ? null : ($data['address2'] ?? null),
            'city' => $client ? null : ($data['city'] ?? null),
            'state' => $client ? null : ($data['state'] ?? null),
            'postcode' => $client ? null : ($data['postcode'] ?? null),
            'country' => $client ? null : ($data['country'] ?? null),
            'phone_prefix' => $client ? null : ($data['phone_prefix'] ?? null),
            'phone_number' => $client ? null : ($data['phone_number'] ?? null),
            'currency_id' => $client ? $client->currency_id : ($data['currency_id'] ?? null),
            'subject' => $data['subject'],
            'date' => $data['date'] ?? now()->toDateString(),
            'valid_until' => $data['valid_until'],
            'status' => $data['status'] ?? $data['stage'] ?? 'Draft',
            'notes' => $data['notes'] ?? null,
            'customer_notes' => $data['customer_notes'] ?? null,
            'proposal' => $data['proposal'] ?? null,
            'subtotal' => 0,
            'tax' => 0,
            'total' => 0,
        ]);

        if (! empty($data['items'])) {
            foreach ($data['items'] as $i => $itemData) {
                $this->addItem($quote, array_merge($itemData, ['sort_order' => $i]));
            }
        }

        return $quote->fresh('items');
    }

    public function addItem(Quote $quote, array $itemData): QuoteItem
    {
        $quantity = max(0.01, (float) ($itemData['quantity'] ?? 1));
        $unitPrice = max(0, (float) ($itemData['unit_price'] ?? 0));
        $discountPercent = min(100, max(0, (float) ($itemData['discount_percent'] ?? 0)));

        if (array_key_exists('discount', $itemData) && $itemData['discount'] !== null && $itemData['discount'] !== '') {
            $discount = max(0, (float) $itemData['discount']);
        } elseif ($discountPercent > 0) {
            $discount = round(($quantity * $unitPrice) * ($discountPercent / 100), 2);
        } else {
            $discount = 0.0;
        }

        $item = QuoteItem::create([
            'quote_id' => $quote->id,
            'description' => $itemData['description'],
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount' => $discount,
            'discount_percent' => $discountPercent,
            'taxable' => (bool) ($itemData['taxable'] ?? true),
            'sort_order' => (int) ($itemData['sort_order'] ?? 0),
        ]);

        $this->recalculateTotals($quote);

        return $item;
    }

    public function recalculateTotals(Quote $quote): Quote
    {
        $quote->load('items', 'client');

        $subtotal = 0;
        $taxTotal = 0;
        // The customer's own rate, the way the invoice will read it: country
        // and state first, the default rule last, nothing for a customer
        // marked tax exempt. Reading the default rule alone quoted one total
        // and billed another.
        $taxRate = $quote->client
            ? (float) app(InvoiceService::class)->calculateTax(0.0, $quote->client->id)['tax_rate']
            : TaxRule::defaultRate();

        foreach ($quote->items as $item) {
            $lineTotal = ($item->quantity * $item->unit_price) - $item->discount;
            $lineTotal = max(0, $lineTotal);
            $subtotal += $lineTotal;
            if ($item->taxable && $taxRate > 0) {
                $taxTotal += TaxRule::taxShare($lineTotal, $taxRate);
            }
        }

        // As the invoice: with prices that include tax, the subtotal is what is
        // left once the tax is taken out, and the total is what was quoted.
        if (TaxRule::pricesIncludeTax()) {
            $subtotal -= $taxTotal;
        }

        $quote->subtotal = round($subtotal, 2);
        $quote->tax = round($taxTotal, 2);
        $quote->total = round($subtotal + $taxTotal, 2);
        $quote->save();

        return $quote;
    }

    public function updateQuote(Quote $quote, array $data): Quote
    {
        $attributes = [
            'subject' => $data['subject'] ?? $quote->subject,
            'date' => $data['date'] ?? $quote->date,
            'valid_until' => $data['valid_until'] ?? $quote->valid_until,
            'status' => $data['status'] ?? $data['stage'] ?? $quote->status,
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $quote->notes,
            'customer_notes' => array_key_exists('customer_notes', $data) ? $data['customer_notes'] : $quote->customer_notes,
            'proposal' => array_key_exists('proposal', $data) ? $data['proposal'] : $quote->proposal,
        ];

        if (($data['client_type'] ?? null) === 'existing' && ! empty($data['client_id'])) {
            $client = Client::find($data['client_id']);
            if ($client) {
                $attributes['client_id'] = $client->id;
                $attributes['currency_id'] = $client->currency_id;
                $attributes['first_name'] = null;
                $attributes['last_name'] = null;
                $attributes['company_name'] = null;
                $attributes['email'] = null;
                $attributes['address1'] = null;
                $attributes['address2'] = null;
                $attributes['city'] = null;
                $attributes['state'] = null;
                $attributes['postcode'] = null;
                $attributes['country'] = null;
                $attributes['phone_prefix'] = null;
                $attributes['phone_number'] = null;
            }
        } elseif (($data['client_type'] ?? null) === 'new') {
            $attributes['client_id'] = null;
            foreach (['first_name', 'last_name', 'company_name', 'email', 'address1', 'address2', 'city', 'state', 'postcode', 'country', 'phone_prefix', 'phone_number', 'currency_id'] as $field) {
                if (array_key_exists($field, $data)) {
                    $attributes[$field] = $data[$field];
                }
            }
        }

        $quote->update($attributes);

        if (isset($data['items'])) {
            $quote->items()->delete();
            foreach ($data['items'] as $i => $itemData) {
                $quantity = max(0.01, (float) ($itemData['quantity'] ?? 1));
                $unitPrice = max(0, (float) ($itemData['unit_price'] ?? 0));
                $discountPercent = min(100, max(0, (float) ($itemData['discount_percent'] ?? 0)));

                if (array_key_exists('discount', $itemData) && $itemData['discount'] !== null && $itemData['discount'] !== '') {
                    $discount = max(0, (float) $itemData['discount']);
                } elseif ($discountPercent > 0) {
                    $discount = round(($quantity * $unitPrice) * ($discountPercent / 100), 2);
                } else {
                    $discount = 0.0;
                }

                QuoteItem::create([
                    'quote_id' => $quote->id,
                    'description' => $itemData['description'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount' => $discount,
                    'discount_percent' => $discountPercent,
                    'taxable' => (bool) ($itemData['taxable'] ?? true),
                    'sort_order' => $i,
                ]);
            }
            $this->recalculateTotals($quote);
        }

        return $quote->fresh('items');
    }

    public function duplicateQuote(Quote $quote): Quote
    {
        $quote->load('items');

        $copy = Quote::create([
            'client_id' => $quote->client_id,
            'first_name' => $quote->first_name,
            'last_name' => $quote->last_name,
            'company_name' => $quote->company_name,
            'email' => $quote->email,
            'address1' => $quote->address1,
            'address2' => $quote->address2,
            'city' => $quote->city,
            'state' => $quote->state,
            'postcode' => $quote->postcode,
            'country' => $quote->country,
            'phone_prefix' => $quote->phone_prefix,
            'phone_number' => $quote->phone_number,
            'currency_id' => $quote->currency_id,
            'subject' => $quote->subject,
            'date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'status' => 'Draft',
            'notes' => $quote->notes,
            'customer_notes' => $quote->customer_notes,
            'proposal' => $quote->proposal,
            'subtotal' => 0,
            'tax' => 0,
            'total' => 0,
        ]);

        foreach ($quote->items as $idx => $item) {
            QuoteItem::create([
                'quote_id' => $copy->id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'discount' => $item->discount,
                'discount_percent' => $item->discount_percent ?? 0,
                'taxable' => (bool) $item->taxable,
                'sort_order' => $idx,
            ]);
        }

        return $this->recalculateTotals($copy)->fresh('items');
    }

    public function sendQuote(Quote $quote): Quote
    {
        $quote->update(['status' => 'Sent']);

        return $quote;
    }

    public function acceptQuote(Quote $quote): Quote
    {
        $quote->update(['status' => 'Accepted']);

        return $quote;
    }

    public function declineQuote(Quote $quote): Quote
    {
        $quote->update(['status' => 'Declined']);

        return $quote;
    }

    public function convertToInvoice(Quote $quote): Invoice
    {
        // r128-once: one quote, one invoice. The customer's accept button
        // refuses to run twice - the quote has to be "Sent" - and the API
        // returns early on an accepted quote. The admin button checked nothing,
        // so a second click raised a second invoice for the same work and the
        // customer was chased for both. An invoice that was cancelled is not
        // standing any more, so that quote can be raised again.
        $existing = Invoice::whereHas('items', fn ($q) => $q
            ->where('type', 'Quote')
            ->where('rel_id', $quote->id))
            ->whereNotIn('status', [InvoiceStatus::Cancelled->value, InvoiceStatus::Refunded->value])
            ->latest('id')
            ->first();

        if ($existing) {
            if ($quote->status !== 'Accepted') {
                $quote->update(['status' => 'Accepted']);
            }

            return $existing;
        }

        $quote->load('items', 'client');

        $client = $quote->client;
        if (! $client) {
            if ($quote->email) {
                $client = Client::where('email', $quote->email)->first();
            }
            if (! $client) {
                $client = Client::create([
                    'first_name' => $quote->first_name ?: 'Client',
                    'last_name' => $quote->last_name ?: ('#' . $quote->id),
                    'company_name' => $quote->company_name,
                    'email' => $quote->email ?: ('quote-' . $quote->id . '-' . Str::lower(Str::random(6)) . '@prospect.local'),
                    'address1' => $quote->address1 ?? '',
                    'address2' => $quote->address2,
                    'city' => $quote->city ?? '',
                    'state' => $quote->state ?? '',
                    'postcode' => $quote->postcode ?? '',
                    'country' => $quote->country ?: 'BD',
                    'phone_prefix' => $quote->phone_prefix,
                    'phone_number' => $quote->phone_number ?? '',
                    'currency_id' => $quote->currency_id,
                    'status' => 'active',
                ]);
            }
            $quote->update(['client_id' => $client->id]);
            $quote->setRelation('client', $client);
        }

        $items = [];

        foreach ($quote->items as $item) {
            $lineTotal = ($item->quantity * $item->unit_price) - $item->discount;

            $items[] = [
                'type' => 'Quote',
                'rel_id' => $quote->id,
                'description' => $item->description,
                // Whether a line is taxable is the quote's decision; how much
                // tax that comes to is the invoice's, using the same rules as
                // every other invoice.
                'taxed' => (bool) $item->taxable,
                'amount' => max(0, $lineTotal),
            ];
        }

        $invoice = app(InvoiceService::class)->createInvoice($client, $items, [
            'due_date' => now()->addDays(30)->toDateString(),
            // Not $quote->notes: those are the admin's notes, and invoice
            // notes are printed on the customer's PDF. A reference to the
            // quote, the way an order's invoice names its order.
            'notes' => "Quote #{$quote->id}",
        ]);

        $quote->update(['status' => 'Accepted']);

        return $invoice;
    }

    public function deleteQuote(Quote $quote): void
    {
        $quote->items()->delete();
        $quote->delete();
    }
}
