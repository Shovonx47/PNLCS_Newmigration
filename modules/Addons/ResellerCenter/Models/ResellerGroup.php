<?php

namespace Modules\Addons\ResellerCenter\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResellerGroup extends Model
{
    protected $table = 'reseller_groups';

    protected $fillable = [
        'name',
        'description',
        'permissions',
        'payout_method',
        'invoicing_method',
        'allow_deferred',
        'credit_limit',
        'allow_promos',
        'allow_own_smtp',
        'allow_suspend',
        'allow_global_search',
        'default_commission_percent',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'settings' => 'array',
            'allow_deferred' => 'boolean',
            'allow_promos' => 'boolean',
            'allow_own_smtp' => 'boolean',
            'allow_suspend' => 'boolean',
            'allow_global_search' => 'boolean',
            'credit_limit' => 'decimal:2',
            'default_commission_percent' => 'decimal:2',
        ];
    }

    public function resellers(): HasMany
    {
        return $this->hasMany(Reseller::class, 'group_id');
    }

    public function pricingRules(): HasMany
    {
        return $this->hasMany(ResellerPricing::class, 'group_id');
    }

    public function hasPermission(string $key): bool
    {
        $perms = is_array($this->permissions) ? $this->permissions : [];

        return ! empty($perms[$key]);
    }
}
