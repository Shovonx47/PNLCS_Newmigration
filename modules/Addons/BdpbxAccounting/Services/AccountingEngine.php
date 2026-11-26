<?php

namespace Modules\Addons\BdpbxAccounting\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Server;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Addons\BdpbxAccounting\Models\BdpbxDailyStat;

/**
 * The daily BDPBX accounting figures, one row per server plus a combined row.
 *
 * Read from PNLCS's own tables: services whose product runs on the bdpbx
 * module, the servers those services sit on, and the invoices whose items
 * point at those services.
 *
 * Two decisions worth knowing when reading the numbers:
 *  - Combined revenue counts each paid invoice once (its total). Per-server
 *    revenue is the pre-tax amount of that server's own invoice items, so an
 *    invoice covering services on two servers is split instead of counted
 *    twice - the legacy engine added the whole invoice total to every server.
 *  - Paid revenue is attributed by the invoice's services, not by the client,
 *    so a customer with hosting and PBX does not inflate PBX revenue.
 */
class AccountingEngine
{
    /**
     * Recalculate one day and upsert its snapshots.
     *
     * @return array<int, array<string, mixed>> keyed by server id (0 = combined)
     */
    public function run(?string $date = null): array
    {
        $date = $this->normaliseDate($date);
        $month = substr($date, 0, 7);

        $productIds = Product::where('server_type', 'bdpbx')->pluck('id');

        if ($productIds->isEmpty()) {
            return [];
        }

        /** @var Collection<int, Service> $services */
        $services = Service::whereIn('product_id', $productIds->all())
            ->get(['id', 'client_id', 'product_id', 'server_id', 'domain', 'amount', 'status', 'billing_cycle', 'next_due_date']);

        $servers = Server::where('type', 'bdpbx')->get(['id', 'name', 'hostname'])->keyBy('id');

        // Server ids to report on: every bdpbx server, plus any id a service
        // actually points at (a server whose type was changed still holds them).
        $serverIds = $servers->keys()->map(fn ($id) => (int) $id)->all();

        foreach ($services as $service) {
            $id = (int) $service->server_id;
            if ($id !== 0 && ! in_array($id, $serverIds, true)) {
                $serverIds[] = $id;
            }
        }

        sort($serverIds);

        $serviceIds = $services->pluck('id')->all();

        $items = $serviceIds === []
            ? collect()
            : InvoiceItem::whereIn('rel_id', $serviceIds)
                ->whereIn('type', ['Hosting', 'Setup', 'Upgrade', 'Addon'])
                ->get(['invoice_id', 'rel_id', 'amount']);

        $serviceServer = $services->mapWithKeys(fn ($s) => [(int) $s->id => (int) $s->server_id]);

        // invoice id => ['services' => [ids], 'amounts' => [server_id => pre-tax sum]]
        $invoiceServices = [];
        $invoiceServerAmount = [];

        foreach ($items as $item) {
            $invoiceId = (int) $item->invoice_id;
            $serverId = (int) ($serviceServer[(int) $item->rel_id] ?? 0);

            $invoiceServices[$invoiceId][(int) $item->rel_id] = true;
            $invoiceServerAmount[$invoiceId][$serverId] = ($invoiceServerAmount[$invoiceId][$serverId] ?? 0) + (float) $item->amount;
        }

        $paidInvoices = $invoiceServices === []
            ? collect()
            : Invoice::whereIn('id', array_keys($invoiceServices))
                ->where('status', 'Paid')
                ->where('date_paid', 'like', $month.'%')
                ->get(['id', 'client_id', 'total', 'date_paid']);

        $dueInvoices = $invoiceServices === []
            ? collect()
            : Invoice::whereIn('id', array_keys($invoiceServices))
                ->where('status', 'Unpaid')
                ->get(['id', 'client_id', 'total', 'due_date']);

        $results = [];

        // The combined row first, then each server.
        foreach (array_merge([0], $serverIds) as $serverId) {
            $own = $services->filter(fn ($s) => $serverId === 0 || (int) $s->server_id === $serverId);

            $active = $own->filter(fn ($s) => strtolower((string) $s->status) === 'active');
            $mrr = (float) $active->sum(fn ($s) => (float) $s->amount);

            $paidClients = [];
            $paidInvoiceIds = [];
            $paidAmount = 0.0;
            $paidToday = 0.0;

            foreach ($paidInvoices as $invoice) {
                $share = $serverId === 0
                    ? (float) $invoice->total
                    : (float) ($invoiceServerAmount[(int) $invoice->id][$serverId] ?? 0);

                if ($serverId !== 0 && $share <= 0) {
                    continue;
                }

                $paidInvoiceIds[(int) $invoice->id] = true;
                $paidClients[(int) $invoice->client_id] = true;
                $paidAmount += $share;

                if ($invoice->date_paid && str_starts_with((string) $invoice->date_paid, $date)) {
                    $paidToday += $share;
                }
            }

            $dueClients = [];
            $dueAmount = 0.0;
            $dueList = [];

            foreach ($dueInvoices as $invoice) {
                $share = $serverId === 0
                    ? (float) $invoice->total
                    : (float) ($invoiceServerAmount[(int) $invoice->id][$serverId] ?? 0);

                if ($serverId !== 0 && $share <= 0) {
                    continue;
                }

                $dueClients[(int) $invoice->client_id] = true;
                $dueAmount += $share;
                $dueList[] = [
                    'invoice_id' => (int) $invoice->id,
                    'client_id' => (int) $invoice->client_id,
                    'amount' => round($share, 2),
                    'due_date' => (string) $invoice->due_date,
                ];
            }

            $server = $servers->get($serverId);

            $record = [
                'snapshot_date' => $date,
                'server_id' => $serverId,
                'server_name' => $serverId === 0 ? 'All PBX Servers (Combined)' : ($server->name ?? 'Server #'.$serverId),
                'server_hostname' => $serverId === 0 ? 'All Servers' : (string) ($server->hostname ?? ''),
                'total_services' => $own->count(),
                'active_services' => $active->count(),
                'suspended_services' => $own->filter(fn ($s) => strtolower((string) $s->status) === 'suspended')->count(),
                'pending_services' => $own->filter(fn ($s) => strtolower((string) $s->status) === 'pending')->count(),
                'cancelled_services' => $own->filter(fn ($s) => in_array(strtolower((string) $s->status), ['cancelled', 'terminated'], true))->count(),
                'active_mrr' => round($mrr, 2),
                'paid_clients_month' => count($paidClients),
                'paid_invoices_month' => count($paidInvoiceIds),
                'paid_amount_month' => round($paidAmount, 2),
                'paid_today_amount' => round($paidToday, 2),
                'due_clients' => count($dueClients),
                'due_invoices' => count($dueList),
                'due_amount' => round($dueAmount, 2),
                'due_details' => array_slice($dueList, 0, 200),
                'services_details' => $own->map(fn ($s) => [
                    'service_id' => (int) $s->id,
                    'client_id' => (int) $s->client_id,
                    'domain' => (string) $s->domain,
                    'status' => (string) $s->status,
                    'amount' => (float) $s->amount,
                    'billing_cycle' => (string) $s->billing_cycle,
                    'next_due_date' => (string) $s->next_due_date,
                ])->values()->all(),
                'last_run_at' => now(),
            ];

            BdpbxDailyStat::updateOrCreate(
                ['snapshot_date' => $date, 'server_id' => $serverId],
                $record
            );

            $results[$serverId] = $record;
        }

        return $results;
    }

    public function normaliseDate(?string $date): string
    {
        try {
            return Carbon::parse($date ?: 'now')->toDateString();
        } catch (\Throwable) {
            return now()->toDateString();
        }
    }

    /** Snapshot rows for a date, combined first then by server name. */
    public function forDate(?string $date = null): Collection
    {
        $date = $this->normaliseDate($date);

        return BdpbxDailyStat::where('snapshot_date', $date)
            ->orderBy('server_id')
            ->get();
    }

    /** Distinct snapshot dates as Y-m-d strings, newest first. */
    public function dates(int $limit = 30): Collection
    {
        return BdpbxDailyStat::query()
            ->select('snapshot_date')
            ->distinct()
            ->orderByDesc('snapshot_date')
            ->limit($limit)
            ->pluck('snapshot_date')
            ->map(fn ($date) => $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : substr((string) $date, 0, 10))
            ->values();
    }
}
