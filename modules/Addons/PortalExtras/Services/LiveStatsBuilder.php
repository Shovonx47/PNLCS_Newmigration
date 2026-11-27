<?php

namespace Modules\Addons\PortalExtras\Services;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Credit;
use App\Models\Domain;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\ModuleQueue;
use App\Models\Order;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\Transaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LiveStatsBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function adminStats(): array
    {
        return Cache::remember('sc_admin_top_stats_v4', 30, function () {
            $today = today();
            $todayDate = $today->toDateString();

            $newClientsToday = Client::whereDate('created_at', $today)
                ->whereNotExists(function ($q) use ($todayDate) {
                    $q->select(DB::raw(1))
                        ->from('invoices')
                        ->whereColumn('invoices.client_id', 'clients.id')
                        ->whereDate('invoices.date', '<', $todayDate);
                })
                ->whereNotExists(function ($q) use ($todayDate) {
                    $q->select(DB::raw(1))
                        ->from('services')
                        ->whereColumn('services.client_id', 'clients.id')
                        ->whereDate('services.registration_date', '<', $todayDate);
                })
                ->count();
            // Bulk WHMCS migration creates imported clients in a single batch on import day; if >50 rows share today's date with no today orders, treat as 0 new signups today.
            if ($newClientsToday > 50 && Order::whereDate('created_at', $today)->where('status', 'pending')->count() < 10) {
                $newClientsToday = 0;
            }

            $pendingOrders = Order::where('status', 'pending')->count();

            $openTickets = Ticket::whereIn('status', ['open', 'Open', 'customer-reply', 'Customer-Reply'])->count();

            $activityErrors = 0;
            if (Schema::hasTable('activity_logs')) {
                $activityErrors = DB::table('activity_logs')
                    ->whereDate('created_at', $today)
                    ->where(function ($q) {
                        $q->where('description', 'like', '%Error%')
                            ->orWhere('description', 'like', '%Failed%')
                            ->orWhere('description', 'like', '%Fail%');
                    })
                    ->count();
            }

            $queueErrors = Schema::hasTable('module_queue')
                ? ModuleQueue::whereIn('status', ['pending', 'failed'])->whereDate('updated_at', $today)->count()
                : 0;

            $errorsToday = $activityErrors + $queueErrors;

            // In WHMCS, "Due" includes all unpaid + past-due invoices, while "Overdue" is the past-due subset.
            $dueInvoices = Invoice::whereIn('status', ['unpaid', 'overdue']);
            $unpaidTotal = (float) (clone $dueInvoices)->sum('total') - (float) (clone $dueInvoices)->sum('credit');

            $overdueInvoices = Invoice::where(function ($q) use ($todayDate) {
                $q->where('status', 'overdue')
                    ->orWhere(function ($sq) use ($todayDate) {
                        $sq->where('status', 'unpaid')->whereDate('due_date', '<', $todayDate);
                    });
            });
            $overdueTotal = (float) (clone $overdueInvoices)->sum('total') - (float) (clone $overdueInvoices)->sum('credit');

            $paidTodayQuery = Invoice::where('status', 'paid')->whereDate('date_paid', $today);
            $paidTodayCount = Transaction::whereDate('date', $today)->where('amount_in', '>', 0)->count();
            if ($paidTodayCount === 0) {
                $paidTodayCount = (clone $paidTodayQuery)->count();
            }

            $incomeToday = (float) Transaction::whereDate('date', $today)->sum('amount_in');
            if ($incomeToday <= 0 && $paidTodayCount > 0) {
                $incomeToday = (float) (clone $paidTodayQuery)->sum('total');
            }

            // Hosting & Domain renewals paid today (excluding initial order invoices and recharges/add-funds).
            $orderInvoiceIds = Order::whereNotNull('invoice_id')->pluck('invoice_id')->all();
            $renewalInvoiceIds = InvoiceItem::whereIn('type', ['Hosting', 'Domain'])
                ->whereHas('invoice', function ($q) use ($today, $orderInvoiceIds) {
                    $q->where('status', 'paid')
                        ->whereDate('date_paid', $today)
                        ->when($orderInvoiceIds !== [], fn ($sq) => $sq->whereNotIn('id', $orderInvoiceIds));
                })
                ->pluck('invoice_id')
                ->unique()
                ->values()
                ->all();

            $renewalsToday = count($renewalInvoiceIds);
            $renewalsAmountToday = $renewalsToday > 0
                ? (float) Invoice::whereIn('id', $renewalInvoiceIds)->sum('total')
                : 0.0;

            $rechargeStats = [
                'success_count' => 0,
                'success_bdt' => 0.0,
                'pending_count' => 0,
                'pending_bdt' => 0.0,
                'failed_count' => 0,
                'failed_bdt' => 0.0,
            ];

            if (Schema::hasTable('italk_recharge_logs')) {
                $rows = DB::table('italk_recharge_logs')
                    ->whereDate('created_at', $today)
                    ->selectRaw('status, COUNT(*) as cnt, COALESCE(SUM(amount), 0) as amt')
                    ->groupBy('status')
                    ->get()
                    ->keyBy('status');

                $rechargeStats = [
                    'success_count' => (int) ($rows['success']->cnt ?? 0),
                    'success_bdt' => (float) ($rows['success']->amt ?? 0),
                    'pending_count' => (int) ($rows['pending']->cnt ?? 0),
                    'pending_bdt' => (float) ($rows['pending']->amt ?? 0),
                    'failed_count' => (int) ($rows['failed']->cnt ?? 0),
                    'failed_bdt' => (float) ($rows['failed']->amt ?? 0),
                ];
            }

            return [
                'new_clients_today' => $newClientsToday,
                'pending_orders' => $pendingOrders,
                'renewals_today' => $renewalsToday,
                'renewals_amount_today' => max(0.0, $renewalsAmountToday),
                'open_tickets' => $openTickets,
                'errors_today' => $errorsToday,
                'unpaid_total' => max(0.0, $unpaidTotal),
                'overdue_total' => max(0.0, $overdueTotal),
                'paid_today_count' => $paidTodayCount,
                'income_today' => max(0.0, $incomeToday),
                'recharges' => $rechargeStats,
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function clientStats(Client $client): array
    {
        $unpaidInvoices = Invoice::where('client_id', $client->id)
            ->whereIn('status', ['unpaid', 'overdue'])
            ->get(['id', 'total', 'credit']);

        $dueAmount = $unpaidInvoices->sum(fn ($inv) => max(0.0, (float) $inv->total - (float) $inv->credit));

        $statusRaw = $client->status instanceof \BackedEnum ? $client->status->value : (string) $client->status;
        $tenure = '—';
        if ($client->created_at) {
            $diff = $client->created_at->diff(now());
            if ($diff->y > 0) {
                $tenure = $diff->y.' Yr'.($diff->m > 0 ? ' '.$diff->m.' Mo' : '');
            } elseif ($diff->m > 0) {
                $tenure = $diff->m.' Mo'.($diff->d > 0 ? ' '.$diff->d.' Days' : '');
            } elseif ($diff->d > 0) {
                $tenure = $diff->d.' Days';
            } else {
                $tenure = 'Today';
            }
        }

        $clientRecharges = null;
        if (Schema::hasTable('italk_recharge_logs')) {
            $rows = DB::table('italk_recharge_logs')
                ->where('client_id', $client->id)
                ->selectRaw('status, COUNT(*) as cnt, COALESCE(SUM(amount), 0) as amt')
                ->groupBy('status')
                ->get()
                ->keyBy('status');

            $clientRecharges = [
                'success_count' => (int) ($rows['success']->cnt ?? 0),
                'success_bdt' => (float) ($rows['success']->amt ?? 0),
                'pending_count' => (int) ($rows['pending']->cnt ?? 0),
                'pending_bdt' => (float) ($rows['pending']->amt ?? 0),
            ];
        }

        return [
            'active_services' => Service::where('client_id', $client->id)->where('status', 'active')->count(),
            'active_domains' => Domain::where('client_id', $client->id)->where('status', 'active')->count(),
            'unpaid_count' => $unpaidInvoices->count(),
            'unpaid_total' => $dueAmount,
            'open_tickets' => Ticket::where('client_id', $client->id)->whereNotIn('status', ['closed', 'Closed'])->count(),
            'credit_balance' => (float) ($client->credit ?? 0),
            'recent_credits' => Credit::where('client_id', $client->id)->latest('id')->limit(7)->get(),
            'member_since' => $client->created_at,
            'tenure' => $tenure,
            'status' => ucfirst(strtolower($statusRaw ?: 'active')),
            'last_login' => auth('web')->user()?->last_login_at,
            'client_recharges' => $clientRecharges,
        ];
    }
}
