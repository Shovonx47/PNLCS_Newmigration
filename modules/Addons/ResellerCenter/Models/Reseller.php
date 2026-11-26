<?php

namespace Modules\Addons\ResellerCenter\Models;

use App\Models\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reseller extends Model
{
    protected $table = 'resellers';

    protected $fillable = [
        'client_id',
        'group_id',
        'cname',
        'company_name',
        'email_address',
        'store_template',
        'logo_url',
        'invoice_logo_url',
        'branding_settings',
        'smtp_settings',
        'balance',
        'status',
        'paypal_email',
    ];

    protected $hidden = ['smtp_settings'];

    protected function casts(): array
    {
        return [
            'branding_settings' => 'array',
            'smtp_settings' => 'encrypted:array',
            'balance' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ResellerGroup::class, 'group_id');
    }

    public function endClients(): HasMany
    {
        return $this->hasMany(ResellerClient::class, 'reseller_id');
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(ResellerPayout::class, 'reseller_id');
    }

    public function promos(): HasMany
    {
        return $this->hasMany(ResellerPromo::class, 'reseller_id');
    }

    public function gateways(): HasMany
    {
        return $this->hasMany(ResellerGateway::class, 'reseller_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(ResellerInvoice::class, 'reseller_id');
    }

    public function isActive(): bool
    {
        return strtolower((string) $this->status) === 'active';
    }
}
