<?php

namespace Modules\Addons\CancelInvoices\Services;

use App\Enums\InvoiceStatus;
use App\Models\AddonSetting;
use App\Models\Invoice;
use App\Models\Service;
use App\Services\InvoiceService;
use Modules\Addons\CancelInvoices\CancelInvoicesModule;
use Modules\Addons\CancelInvoices\Models\CancelInvoiceExclusion;

class CancelInvoicesService
{
    public function __construct(private InvoiceService $invoiceService) {}

    /**
     * @return array<string, string>
     */
    public function settings(): array
    {
        $defaults = [
            'enable_days' => '1',
            'days' => '7',
            'enable_all_invoices' => '0',
            'all_invoice_days' => '7',
            'enable_add_funds' => '1',
            'add_funds_days' => '7',
        ];

        return array_merge($defaults, AddonSetting::getForAddon(CancelInvoicesModule::NAME));
    }

    public function flagEnabled(string $key): bool
    {
        return in_array(strtolower(trim((string) ($this->settings()[$key] ?? '0'))), ['1', 'on', 'true', 'yes'], true);
    }

    /**
     * Cancel eligible unpaid/overdue renewal and Add-Funds invoices while respecting exclusions.
     *
     * @return array{cancelled_regular: int, cancelled_add_funds: int, skipped_excluded: int, invoice_ids: list<int>}
     */
    public function run(bool $dryRun = false): array
    {
        $cfg = $this->settings();
        $excludedClients = CancelInvoiceExclusion::where('type', 'client')->pluck('rel_id')->map(fn ($v) => (int) $v)->all();
        $excludedServices = CancelInvoiceExclusion::where('type', 'service')->pluck('rel_id')->map(fn ($v) => (int) $v)->all();
        $excludedProducts = CancelInvoiceExclusion::where('type', 'product')->pluck('rel_id')->map(fn ($v) => (int) $v)->all();

        $cancelledRegular = 0;
        $cancelledAddFunds = 0;
        $skippedExcluded = 0;
        $invoiceIds = [];

        $candidates = Invoice::with('items')
            ->whereIn('status', [InvoiceStatus::Unpaid->value, InvoiceStatus::Overdue->value])
            ->get();

        foreach ($candidates as $invoice) {
            if (in_array((int) $invoice->client_id, $excludedClients, true)) {
                $skippedExcluded++;

                continue;
            }

            $isAddFunds = $invoice->items->contains(fn ($i) => strtolower((string) $i->type) === 'addfunds');

            if ($isAddFunds) {
                if (! $this->flagEnabled('enable_add_funds')) {
                    continue;
                }
                $afDays = max(1, (int) ($cfg['add_funds_days'] ?? 7));
                $refDate = $invoice->due_date ?? $invoice->date ?? $invoice->created_at;
                if (! $refDate || $refDate->gt(now()->subDays($afDays)->endOfDay())) {
                    continue;
                }

                if (! $dryRun) {
                    $this->invoiceService->cancelInvoice($invoice);
                }
                $cancelledAddFunds++;
                $invoiceIds[] = (int) $invoice->id;

                continue;
            }

            if ($this->hasExcludedItem($invoice, $excludedServices, $excludedProducts)) {
                $skippedExcluded++;

                continue;
            }

            $shouldCancel = false;
            $hasHostingOrDomain = $invoice->items->contains(fn ($i) => in_array(strtolower((string) $i->type), ['hosting', 'domain', 'addon', 'setup'], true));

            if ($this->flagEnabled('enable_days') && $hasHostingOrDomain) {
                $days = max(1, (int) ($cfg['days'] ?? 7));
                $due = $invoice->due_date ?? $invoice->date;
                if ($due && $due->lte(now()->subDays($days)->endOfDay())) {
                    $shouldCancel = true;
                }
            }

            if (! $shouldCancel && $this->flagEnabled('enable_all_invoices')) {
                $allDays = max(1, (int) ($cfg['all_invoice_days'] ?? 7));
                $due = $invoice->due_date ?? $invoice->date;
                if ($due && $due->lte(now()->subDays($allDays)->endOfDay())) {
                    $shouldCancel = true;
                }
            }

            if ($shouldCancel) {
                if (! $dryRun) {
                    $this->invoiceService->cancelInvoice($invoice);
                }
                $cancelledRegular++;
                $invoiceIds[] = (int) $invoice->id;
            }
        }

        return [
            'cancelled_regular' => $cancelledRegular,
            'cancelled_add_funds' => $cancelledAddFunds,
            'skipped_excluded' => $skippedExcluded,
            'invoice_ids' => $invoiceIds,
        ];
    }

    /**
     * @param  list<int>  $excludedServices
     * @param  list<int>  $excludedProducts
     */
    private function hasExcludedItem(Invoice $invoice, array $excludedServices, array $excludedProducts): bool
    {
        if ($excludedServices === [] && $excludedProducts === []) {
            return false;
        }

        $serviceIds = $invoice->items
            ->filter(fn ($i) => strtolower((string) $i->type) === 'hosting' && (int) $i->rel_id > 0)
            ->pluck('rel_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        if ($serviceIds === []) {
            return false;
        }

        if ($excludedServices !== [] && array_intersect($serviceIds, $excludedServices) !== []) {
            return true;
        }

        if ($excludedProducts !== []) {
            return Service::whereIn('id', $serviceIds)->whereIn('product_id', $excludedProducts)->exists();
        }

        return false;
    }
}
