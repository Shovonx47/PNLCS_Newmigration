<?php

namespace Modules\Addons\PriceUpdater\Services;

use App\Models\ActivityLog;
use App\Models\Domain;
use App\Models\DomainPricing;
use App\Models\Pricing;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Modules\Addons\PriceUpdater\Models\PriceUpdaterLog;

class PriceUpdaterService
{
    public const CYCLES = ['monthly', 'quarterly', 'semiannually', 'annually', 'biennially', 'triennially'];

    /**
     * Preview or apply bulk product pricing changes (and optional active service recurring cascade).
     *
     * @param  array<string, mixed>  $params
     * @return array{preview: list<array<string, mixed>>, affected_products: int, affected_services: int}
     */
    public function runProductUpdate(array $params, bool $apply = false, ?int $adminId = null, string $adminName = 'admin'): array
    {
        $mode = ($params['mode'] ?? 'percent') === 'fixed' ? 'fixed' : 'percent';
        $delta = (float) ($params['delta'] ?? 0);
        $groupId = ! empty($params['group_id']) ? (int) $params['group_id'] : null;
        $currencyId = ! empty($params['currency_id']) ? (int) $params['currency_id'] : null;
        $cycles = array_values(array_intersect(self::CYCLES, (array) ($params['cycles'] ?? self::CYCLES)));
        if ($cycles === []) {
            $cycles = self::CYCLES;
        }
        $cascadeServices = ! empty($params['cascade_services']);

        $products = Product::when($groupId, fn ($q) => $q->where('group_id', $groupId))
            ->orderBy('id')
            ->get(['id', 'name', 'group_id']);

        if ($products->isEmpty()) {
            return ['preview' => [], 'affected_products' => 0, 'affected_services' => 0];
        }

        $pricingRows = Pricing::where('type', 'product')
            ->whereIn('rel_id', $products->pluck('id')->all())
            ->when($currencyId, fn ($q) => $q->where('currency_id', $currencyId))
            ->get();

        $productNames = $products->pluck('name', 'id');
        $preview = [];
        $affectedProducts = 0;
        $affectedServices = 0;

        $runner = function () use ($pricingRows, $cycles, $mode, $delta, $cascadeServices, $apply, $productNames, &$preview, &$affectedProducts, &$affectedServices, $adminId, $adminName, $groupId, $currencyId) {
            foreach ($pricingRows as $row) {
                $updates = [];
                foreach ($cycles as $cycle) {
                    $old = $row->{$cycle};
                    if ($old === null || (float) $old < 0) {
                        continue;
                    }
                    $new = $this->computeNewPrice((float) $old, $mode, $delta);
                    if (abs($new - (float) $old) >= 0.005) {
                        $updates[$cycle] = $new;
                        if (count($preview) < 100) {
                            $preview[] = [
                                'target' => ($productNames[$row->rel_id] ?? 'Product #'.$row->rel_id).' ('.$cycle.')',
                                'old' => round((float) $old, 2),
                                'new' => $new,
                            ];
                        }
                    }
                }

                if ($updates !== []) {
                    $affectedProducts++;
                    if ($apply) {
                        $row->update($updates);
                    }

                    if ($cascadeServices) {
                        foreach ($updates as $cycle => $newPrice) {
                            $svcQuery = Service::where('product_id', $row->rel_id)
                                ->whereIn('status', ['active', 'suspended'])
                                ->whereRaw('LOWER(billing_cycle) = ?', [strtolower($cycle)]);

                            $cnt = (clone $svcQuery)->count();
                            $affectedServices += $cnt;
                            if ($apply && $cnt > 0) {
                                $svcQuery->update(['amount' => $newPrice]);
                            }
                        }
                    }
                }
            }

            if ($apply && ($affectedProducts > 0 || $affectedServices > 0)) {
                PriceUpdaterLog::create([
                    'admin_id' => $adminId,
                    'target_type' => $cascadeServices ? 'service' : 'product',
                    'mode' => $mode,
                    'delta' => $delta,
                    'filters' => ['group_id' => $groupId, 'currency_id' => $currencyId, 'cycles' => $cycles, 'cascade_services' => $cascadeServices],
                    'affected_count' => $affectedProducts + $affectedServices,
                ]);
                ActivityLog::log("Bulk price update ({$mode} {$delta}) applied to {$affectedProducts} product pricing row(s) and {$affectedServices} service(s).", $adminName);
            }
        };

        if ($apply) {
            DB::transaction($runner);
        } else {
            $runner();
        }

        return [
            'preview' => $preview,
            'affected_products' => $affectedProducts,
            'affected_services' => $affectedServices,
        ];
    }

    /**
     * Preview or apply bulk Domain TLD pricing updates (and optional active domain recurring_amount cascade).
     *
     * @param  array<string, mixed>  $params
     * @return array{preview: list<array<string, mixed>>, affected_tlds: int, affected_domains: int}
     */
    public function runDomainUpdate(array $params, bool $apply = false, ?int $adminId = null, string $adminName = 'admin'): array
    {
        $mode = ($params['mode'] ?? 'percent') === 'fixed' ? 'fixed' : 'percent';
        $delta = (float) ($params['delta'] ?? 0);
        $tldFilter = strtolower(trim((string) ($params['extension'] ?? '')));
        $fields = array_values(array_intersect(['register_price', 'renew_price', 'transfer_price'], (array) ($params['fields'] ?? ['register_price', 'renew_price', 'transfer_price'])));
        if ($fields === []) {
            $fields = ['register_price', 'renew_price', 'transfer_price'];
        }
        $cascadeDomains = ! empty($params['cascade_domains']);

        $tlds = DomainPricing::when($tldFilter !== '', function ($q) use ($tldFilter) {
            $norm = '.'.ltrim($tldFilter, '.');
            $q->where('extension', $norm);
        })->orderBy('extension')->get();

        $allKnownExtensions = DomainPricing::pluck('extension')
            ->map(fn ($e) => '.'.ltrim(strtolower((string) $e), '.'))
            ->sortByDesc(fn ($e) => strlen($e))
            ->values()
            ->all();

        $preview = [];
        $affected = 0;
        $affectedDomains = 0;

        $runner = function () use ($tlds, $fields, $mode, $delta, $cascadeDomains, $allKnownExtensions, $apply, &$preview, &$affected, &$affectedDomains, $adminId, $adminName, $tldFilter) {
            foreach ($tlds as $tld) {
                $updates = [];
                foreach ($fields as $col) {
                    if ($tld->{$col} === null) {
                        continue;
                    }
                    $old = (float) $tld->{$col};
                    $new = $this->computeNewPrice($old, $mode, $delta);
                    if (abs($new - $old) >= 0.005) {
                        $updates[$col] = $new;
                        if (count($preview) < 100) {
                            $preview[] = [
                                'target' => $tld->extension.' ('.str_replace('_price', '', $col).')',
                                'old' => round($old, 2),
                                'new' => $new,
                            ];
                        }
                    }
                }
                if ($updates !== []) {
                    $affected++;
                    if ($apply) {
                        $tld->update($updates);
                    }

                    if ($cascadeDomains && array_key_exists('renew_price', $updates)) {
                        $extNorm = '.'.ltrim(strtolower((string) $tld->extension), '.');
                        $candidates = Domain::whereIn('status', ['active', 'grace', 'redemption'])
                            ->whereRaw('LOWER(domain) LIKE ?', ['%'.$extNorm])
                            ->get(['id', 'domain']);

                        $matchingIds = [];
                        foreach ($candidates as $dom) {
                            $d = strtolower(trim((string) $dom->domain));
                            $matchedExt = null;
                            foreach ($allKnownExtensions as $knownExt) {
                                if (str_ends_with($d, $knownExt)) {
                                    $matchedExt = $knownExt;
                                    break;
                                }
                            }
                            if ($matchedExt === $extNorm) {
                                $matchingIds[] = $dom->id;
                            }
                        }

                        if ($matchingIds !== []) {
                            $affectedDomains += count($matchingIds);
                            if ($apply) {
                                Domain::whereIn('id', $matchingIds)->update(['recurring_amount' => $updates['renew_price']]);
                            }
                        }
                    }
                }
            }

            if ($apply && ($affected > 0 || $affectedDomains > 0)) {
                PriceUpdaterLog::create([
                    'admin_id' => $adminId,
                    'target_type' => 'domain',
                    'mode' => $mode,
                    'delta' => $delta,
                    'filters' => ['extension' => $tldFilter, 'fields' => $fields, 'cascade_domains' => $cascadeDomains],
                    'affected_count' => $affected + $affectedDomains,
                ]);
                ActivityLog::log("Bulk TLD price update ({$mode} {$delta}) applied to {$affected} extension(s) and {$affectedDomains} active domain(s).", $adminName);
            }
        };

        if ($apply) {
            DB::transaction($runner);
        } else {
            $runner();
        }

        return ['preview' => $preview, 'affected_tlds' => $affected, 'affected_domains' => $affectedDomains];
    }

    private function computeNewPrice(float $old, string $mode, float $delta): float
    {
        $raw = $mode === 'percent'
            ? $old * (1 + ($delta / 100))
            : $old + $delta;

        return round(max(0.0, $raw), 2);
    }
}
