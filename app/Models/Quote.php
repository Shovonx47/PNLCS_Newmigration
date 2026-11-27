<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Quote extends Model {
    use HasFactory;

    protected $table = "quotes";
    protected $fillable = [
        "client_id",
        "first_name",
        "last_name",
        "company_name",
        "email",
        "address1",
        "address2",
        "city",
        "state",
        "postcode",
        "country",
        "phone_prefix",
        "phone_number",
        "currency_id",
        "subject",
        "date",
        "valid_until",
        "subtotal",
        "tax",
        "total",
        "status",
        "notes",
        "customer_notes",
        "proposal",
    ];

    // Both are DATE columns; without a cast Eloquent hands back a raw string, and
    // the quotes list calls ->format() straight on it (a null-safe call does not
    // guard a string), throwing "member function format() on string".
    protected function casts(): array
    {
        return ['date' => 'date', 'valid_until' => 'date'];
    }

    public function client() { return $this->belongsTo(Client::class); }
    public function currency() { return $this->belongsTo(Currency::class); }
    public function items() { return $this->hasMany(QuoteItem::class)->orderBy('sort_order')->orderBy('id'); }

    public function recipientName(): string
    {
        if ($this->client) {
            return (string) $this->client->full_name;
        }
        $name = trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
        if ($name !== '') {
            return $name;
        }

        return (string) ($this->company_name ?: ($this->email ?: 'N/A'));
    }

    public function recipientEmail(): ?string
    {
        return $this->client?->email ?: ($this->email ?: null);
    }

    public function resolvedCurrency(): ?Currency
    {
        if ($this->relationLoaded('currency') && $this->currency) {
            return $this->currency;
        }
        if ($this->currency_id) {
            $found = Currency::find($this->currency_id);
            if ($found) {
                return $found;
            }
        }
        if ($this->client?->currency_id) {
            $clientCurrency = Currency::find($this->client->currency_id);
            if ($clientCurrency) {
                return $clientCurrency;
            }
        }

        return Currency::where('is_default', true)->first();
    }

    public function formatMoney(float|int|string $amount): string
    {
        $cur = $this->resolvedCurrency();
        if (! $cur) {
            return money_fmt((float) $amount);
        }

        return ($cur->prefix ?? '') . number_format((float) $amount, 2) . ($cur->suffix ?? '');
    }
}
