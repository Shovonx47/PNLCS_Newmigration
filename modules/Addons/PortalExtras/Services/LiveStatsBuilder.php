<?php

namespace Modules\Addons\PortalExtras\Services;

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
        return Cache::remember('sc_admin_top_stats_v2', 30, function () {
            $today = today();

            $newClientsToday = Client::whereDate('created_at', $today)->count();
            $pendingOrders = Order::where('status', 'pending')->count();

            $openTickets = Ticket::whereNotIn('status', ['closed', 'Closed', 'answered', 'Answered'])->count();

            $errorsToday = ModuleQueue::whereIn('status', ['pending', 'failed'])
                ->whereDate('updated_at', $today)
                ->count();

            $unpaidTotal = (float) Invoice::where('status', 'unpaid')->sum('total') - (float) Invoice::where('status', 'unpaid')->sum('credit');
            $overdueTotal = (float) Invoice::where('status', 'overdue')->sum('total') - (float) Invoice::where('status', 'overdue')->sum('credit');

            $paidTodayQuery = Invoice::where('status', 'paid')->whereDate('date_paid', $today);
            $paidTodayCount = (clone $paidTodayQuery)->count();
            $incomeToday = (float) Transaction::whereDate('created_at', $today)->sum('amount_in');
            if ($incomeToday <= 0 && $paidTodayCount > 0) {
                $incomeToday = (float) (clone $paidTodayQuery)->sum('total');
            }

            // Hosting & Domain renewals paid today (excluding initial order invoices and recharges/add-funds).
            $orderInvoiceIds = Order::whereNotNull('invoice_id')->pluck('invoice_id')->all();
            $renewalsQuery = InvoiceItem::whereIn('type', ['Hosting', 'Domain'])
                ->whereHas('invoice', function ($q) use ($today, $orderInvoiceIds) {
                    $q->where('status', 'paid')
                        ->whereDate('date_paid', $today)
                        ->when($orderInvoiceIds !== [], fn ($sq) => $sq->whereNotIn('id', $orderInvoiceIds));
                });

            $renewalsToday = (clone $renewalsQuery)->count();
            $renewalsAmountToday = (float) (clone $renewalsQuery)->sum('amount');

            $rechargeStats = null;
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
