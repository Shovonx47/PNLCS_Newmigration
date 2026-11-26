<?php

namespace Modules\Addons\ScrollingNotice\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ScrollingNotice extends Model
{
    protected $table = 'scrolling_notices';

    protected $fillable = [
        'title',
        'content',
        'priority',
        'url_target',
        'start_time',
        'expiry_time',
        'is_active',
        'login_status',
        'coupon_enabled',
        'coupon_code',
        'scroll_speed',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'start_time' => 'datetime',
            'expiry_time' => 'datetime',
            'is_active' => 'boolean',
            'coupon_enabled' => 'boolean',
            'scroll_speed' => 'integer',
        ];
    }

    public function scopeVisibleNow(Builder $query, bool $isAuthenticated): Builder
    {
        $now = now();
        $allowedStates = $isAuthenticated ? ['all', 'logged_in'] : ['all', 'logged_out'];

        return $query->where('is_active', true)
            ->whereIn('login_status', $allowedStates)
            ->where(fn (Builder $q) => $q->whereNull('start_time')->orWhere('start_time', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('expiry_time')->orWhere('expiry_time', '>=', $now))
            ->orderByDesc('priority')
            ->orderByDesc('id');
    }

    /**
     * Resolve a safe target URL for the notice (preventing javascript:/data: schemes
     * and translating legacy WHMCS /index.php?rp=/store/... links cleanly).
     */
    public function resolvedUrl(): ?string
    {
        $raw = trim((string) $this->url_target);
        if ($raw === '') {
            return null;
        }

        if (preg_match('#^/index\.php\?rp=/store(?:/([a-z0-9_-]+))?#i', $raw)) {
            return '/client/store';
        }

        if (str_starts_with($raw, '/') && ! str_starts_with($raw, '//')) {
            return $raw;
        }

        $scheme = strtolower((string) parse_url($raw, PHP_URL_SCHEME));
        if (in_array($scheme, ['https', 'http'], true)) {
            return $raw;
        }

        return null;
    }
}
