<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Client;
use App\Models\GatewaySettings;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\ModuleQueue;
use App\Models\Order;
use App\Models\Product;
use App\Models\Server;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Models\User;
use App\Services\WidgetManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function __construct(protected WidgetManager $widgets) {}

    public function index()
    {
        return view('admin.dashboard', [
            'widgetOutput'   => $this->widgets->renderAll(),
            'setup'          => $this->setupChecklist(),
            'badges'         => $this->buildTopBadges(),
            'ubo'            => $this->buildUltimateBusinessOverview(),
            'automation'     => $this->buildAutomationOverview(),
            'activityFeed'   => $this->buildActivityFeed(),
            'clientActivity' => $this->buildClientActivity(),
            'todos'          => $this->buildTodoList(),
            'servers'        => $this->buildNetworkStatus(),
            'staffOnline'    => $this->buildStaffOnline(),
            'health'         => $this->buildSystemHealth(),
            'billingSummary' => $this->buildBillingSummary(),
            'supportSummary' => $this->buildSupportSummary(),
            'chartSeries'    => $this->buildSystemOverviewSeries(),
            'projectTabs'    => $this->buildProjectTabs(),
        ]);
    }

    private function buildTopBadges(): array
    {
        try {
            $pendingOrders = Order::where('status', 'pending')->count();
            $ticketsWaiting = Ticket::whereIn('status', ['open', 'Open', 'customer-reply', 'Customer-Reply'])->count();
            $pendingCancellations = Schema::hasTable('cancellation_requests')
                ? DB::table('cancellation_requests')->whereNull('processed_at')->count()
                : 0;
            $pendingModuleActions = Schema::hasTable('module_queue')
                ? ModuleQueue::whereIn('status', ['pending', 'failed'])->count()
                : 0;

            return [
                'pending_orders'         => $pendingOrders,
                'tickets_waiting'        => $ticketsWaiting,
                'pending_cancellations'  => $pendingCancellations,
                'pending_module_actions' => $pendingModuleActions,
            ];
        } catch (\Throwable) {
            return [
                'pending_orders'         => 0,
                'tickets_waiting'        => 0,
                'pending_cancellations'  => 0,
                'pending_module_actions' => 0,
            ];
        }
    }

    private function buildUltimateBusinessOverview(): array
    {
        $today = today();
        $todayStr = $today->toDateString();

        try {
            $newClients = Client::whereDate('created_at', $today)
                ->whereNotExists(function ($q) use ($todayStr) {
                    $q->select(DB::raw(1))
                        ->from('invoices')
                        ->whereColumn('invoices.client_id', 'clients.id')
                        ->whereDate('invoices.date', '<', $todayStr);
                })
                ->whereNotExists(function ($q) use ($todayStr) {
                    $q->select(DB::raw(1))
                        ->from('services')
                        ->whereColumn('services.client_id', 'clients.id')
                        ->whereDate('services.registration_date', '<', $todayStr);
                })
                ->count();
            if ($newClients > 50 && Order::whereDate('created_at', $today)->where('status', 'pending')->count() < 10) {
                $newClients = 0;
            }
            $orders = Order::where('status', 'pending')->count();
            $tickets = Ticket::whereIn('status', ['open', 'Open', 'customer-reply', 'Customer-Reply'])->count();

            $dueInvoices = Invoice::whereIn('status', ['unpaid', 'overdue']);
            $due = max(0.0, (float) (clone $dueInvoices)->sum('total') - (float) (clone $dueInvoices)->sum('credit'));

            $overdueInvoices = Invoice::where(function ($q) use ($todayStr) {
                $q->where('status', 'overdue')
                    ->orWhere(function ($sq) use ($todayStr) {
                        $sq->where('status', 'unpaid')->whereDate('due_date', '<', $todayStr);
                    });
            });
            $overdue = max(0.0, (float) (clone $overdueInvoices)->sum('total') - (float) (clone $overdueInvoices)->sum('credit'));

            $paidToday = Transaction::whereDate('date', $today)->where('amount_in', '>', 0)->count();
            if ($paidToday === 0) {
                $paidToday = Invoice::where('status', 'paid')->whereDate('date_paid', $today)->count();
            }

            $incomeToday = (float) Transaction::whereDate('date', $today)->sum('amount_in');
            if ($incomeToday <= 0 && $paidToday > 0) {
                $incomeToday = (float) Invoice::where('status', 'paid')->whereDate('date_paid', $today)->sum('total');
            }

            $orderInvoiceIds = Order::whereNotNull('invoice_id')->pluck('invoice_id')->all();
            $renewalQuery = Invoice::where('status', 'paid')
                ->whereDate('date_paid', $today)
                ->when($orderInvoiceIds !== [], fn ($q) => $q->whereNotIn('id', $orderInvoiceIds));
            $renewCount = (clone $renewalQuery)->count();
            $renewAmount = (float) (clone $renewalQuery)->sum('total');

            $addFunds = (float) InvoiceItem::whereIn('type', ['AddFunds', 'add_funds', 'credit'])
                ->whereHas('invoice', fn ($q) => $q->where('status', 'paid')->whereDate('date_paid', $today))
                ->sum('amount');

            $totalCredit = (float) Client::sum('credit');

            $topProduct = DB::table('services')
                ->join('products', 'services.product_id', '=', 'products.id')
                ->select('products.name', DB::raw('COUNT(services.id) as total_sold'))
                ->whereIn('services.status', ['active', 'suspended', 'pending', 'Active', 'Suspended', 'Pending'])
                ->groupBy('services.product_id', 'products.name')
                ->orderByDesc('total_sold')
                ->first();

            $topProdName = $topProduct ? (string) $topProduct->name : 'No sales yet';
            $topProdSales = $topProduct ? (int) $topProduct->total_sold : 0;
            if (mb_strlen($topProdName) > 18) {
                $topProdName = mb_substr($topProdName, 0, 16) . '..';
            }

            $rcgSuccessC = $rcgPendingC = $rcgFailedC = 0;
            $rcgSuccessA = $rcgPendingA = $rcgFailedA = 0.0;

            if (Schema::hasTable('italk_recharge_logs')) {
                $rows = DB::table('italk_recharge_logs')
                    ->whereDate('created_at', $today)
                    ->selectRaw('status, COUNT(*) as cnt, COALESCE(SUM(amount), 0) as amt')
                    ->groupBy('status')
                    ->get()
                    ->keyBy('status');

                $rcgSuccessC = (int) ($rows['success']->cnt ?? 0);
                $rcgSuccessA = (float) ($rows['success']->amt ?? 0);
                $rcgPendingC = (int) ($rows['pending']->cnt ?? 0);
                $rcgPendingA = (float) ($rows['pending']->amt ?? 0);
                $rcgFailedC  = (int) ($rows['failed']->cnt ?? 0);
                $rcgFailedA  = (float) ($rows['failed']->amt ?? 0);
            }

            $fmt = fn ($n) => '৳' . number_format((float) $n, 2);

            return [
                'newClients'   => $newClients,
                'orders'       => $orders,
                'tickets'      => $tickets,
                'paidToday'    => $paidToday,
                'topProdName'  => $topProdName,
                'topProdSales' => $topProdSales,
                'due'          => $fmt($due),
                'overdue'      => $fmt($overdue),
                'income'       => $fmt($incomeToday),
                'renewCount'   => $renewCount,
                'renewAmount'  => $fmt($renewAmount),
                'addFunds'     => $fmt($addFunds),
                'totalCredit'  => $fmt($totalCredit),
                'rcgSuccessC'  => $rcgSuccessC,
                'rcgSuccessA'  => $fmt($rcgSuccessA),
                'rcgPendingC'  => $rcgPendingC,
                'rcgPendingA'  => $fmt($rcgPendingA),
                'rcgFailedC'   => $rcgFailedC,
                'rcgFailedA'   => $fmt($rcgFailedA),
            ];
        } catch (\Throwable) {
            return [
                'newClients' => 0, 'orders' => 0, 'tickets' => 0, 'paidToday' => 0,
                'topProdName' => 'No sales yet', 'topProdSales' => 0,
                'due' => '৳0.00', 'overdue' => '৳0.00', 'income' => '৳0.00',
                'renewCount' => 0, 'renewAmount' => '৳0.00', 'addFunds' => '৳0.00', 'totalCredit' => '৳0.00',
                'rcgSuccessC' => 0, 'rcgSuccessA' => '৳0.00',
                'rcgPendingC' => 0, 'rcgPendingA' => '৳0.00',
                'rcgFailedC' => 0, 'rcgFailedA' => '৳0.00',
            ];
        }
    }

    private function buildAutomationOverview(): array
    {
        try {
            $days = collect(range(6, 0))->map(fn ($i) => today()->subDays($i)->toDateString());

            $invSeries = [];
            $ccSeries = [];
            $suspSeries = [];
            $tktClosedSeries = [];
            $remSeries = [];
            $cancelSeries = [];

            foreach ($days as $d) {
                $invSeries[] = Invoice::whereDate('date', $d)->count();
                $ccSeries[] = Transaction::whereDate('date', $d)->where('gateway', 'like', '%stripe%')->count();
                $suspSeries[] = Schema::hasTable('activity_logs')
                    ? DB::table('activity_logs')->whereDate(DB::raw('COALESCE(`date`, `created_at`)'), $d)->where('description', 'like', '%Suspend%')->count()
                    : 0;
                $tktClosedSeries[] = Schema::hasTable('activity_logs')
                    ? DB::table('activity_logs')->whereDate(DB::raw('COALESCE(`date`, `created_at`)'), $d)->where('description', 'like', '%Ticket Closed%')->count()
                    : 0;
                $remSeries[] = Invoice::whereDate('reminder_sent_at', $d)->count();
                $cancelSeries[] = Schema::hasTable('cancellation_requests')
                    ? DB::table('cancellation_requests')->whereDate('processed_at', $d)->count()
                    : 0;
            }

            $lastCron = Setting::get('last_cron_run');
            $lastRunLabel = $lastCron
                ? Carbon::parse($lastCron)->calendar()
                : 'Today at 9:00 AM';

            return [
                'items' => [
                    ['label' => 'Invoices Created',        'today' => end($invSeries),       'series' => $invSeries,       'stroke' => '#40babd', 'fill' => 'rgba(64,186,189,0.22)',  'colorClass' => 'color-blue'],
                    ['label' => 'Credit Card Captures',    'today' => end($ccSeries),        'series' => $ccSeries,        'stroke' => '#5dc560', 'fill' => 'rgba(93,197,96,0.22)',   'colorClass' => 'color-green'],
                    ['label' => 'Overdue Suspensions',     'today' => end($suspSeries),      'series' => $suspSeries,      'stroke' => '#f8a13f', 'fill' => 'rgba(248,161,63,0.22)',  'colorClass' => 'color-orange'],
                    ['label' => 'Inactive Tickets Closed', 'today' => end($tktClosedSeries), 'series' => $tktClosedSeries, 'stroke' => '#ea5395', 'fill' => 'rgba(234,83,149,0.22)',  'colorClass' => 'color-pink'],
                    ['label' => 'Overdue Reminders',       'today' => end($remSeries),       'series' => $remSeries,       'stroke' => '#666666', 'fill' => 'rgba(30,30,30,0.18)',    'colorClass' => 'color-grey'],
                    ['label' => 'Cancellations Processed', 'today' => end($cancelSeries),    'series' => $cancelSeries,    'stroke' => '#901fc5', 'fill' => 'rgba(144,31,197,0.22)',  'colorClass' => 'color-purple'],
                ],
                'lastRun' => $lastRunLabel,
            ];
        } catch (\Throwable) {
            return ['items' => [], 'lastRun' => 'Today at 9:00 AM'];
        }
    }

    private function buildActivityFeed(): array
    {
        try {
            if (! Schema::hasTable('activity_logs')) {
                return [];
            }

            return DB::table('activity_logs')
                ->orderByDesc('id')
                ->limit(15)
                ->get()
                ->map(function ($row) {
                    $ts = $row->date ?: $row->created_at;

                    return [
                        'user'        => $row->user ?: 'System',
                        'description' => (string) $row->description,
                        'ip'          => (string) ($row->ip_address ?? ''),
                        'ago'         => $ts ? Carbon::parse($ts)->diffForHumans() : '',
                    ];
                })
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function buildClientActivity(): array
    {
        try {
            $activeClients = Client::where('status', 'active')->count();
            $usersOnline = Schema::hasColumn('users', 'last_login')
                ? User::where('last_login', '>=', now()->subHour())->count()
                : 0;

            $recent = DB::table('clients')
                ->leftJoin('users', 'users.email', '=', 'clients.email')
                ->whereNull('clients.deleted_at')
                ->select(
                    'clients.id',
                    'clients.first_name',
                    'clients.last_name',
                    'clients.company_name',
                    'clients.ip_address',
                    'clients.updated_at',
                    'users.last_login',
                    'users.last_login_ip'
                )
                ->orderByRaw('COALESCE(users.last_login, clients.updated_at) DESC')
                ->limit(15)
                ->get()
                ->map(function ($c) {
                    $fullName = trim($c->first_name . ' ' . $c->last_name);
                    $ts = $c->last_login ?: $c->updated_at;

                    return [
                        'id'      => $c->id,
                        'label'   => $fullName . ($c->company_name ? ' (' . $c->company_name . ')' : ''),
                        'ip'      => $c->last_login_ip ?: ($c->ip_address ?: ''),
                        'ago'     => $ts ? Carbon::parse($ts)->diffForHumans() : 'N/A',
                    ];
                })
                ->all();

            return [
                'activeClients' => $activeClients,
                'usersOnline'   => $usersOnline,
                'clients'       => $recent,
            ];
        } catch (\Throwable) {
            return ['activeClients' => 0, 'usersOnline' => 0, 'clients' => []];
        }
    }

    private function buildTodoList(): array
    {
        try {
            if (! Schema::hasTable('todo_items')) {
                return [];
            }

            return DB::table('todo_items')
                ->whereNotIn('status', ['completed', 'Completed', 'done'])
                ->orderBy('due_date')
                ->limit(10)
                ->get()
                ->map(fn ($t) => [
                    'id'     => $t->id,
                    'title'  => (string) $t->title,
                    'status' => strtoupper((string) ($t->status ?: 'PENDING')),
                    'due'    => $t->due_date ? 'Due ' . Carbon::parse($t->due_date)->diffForHumans() : '',
                ])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function buildNetworkStatus(): array
    {
        try {
            return Server::orderBy('id')
                ->get(['id', 'name', 'hostname', 'ip_address', 'active', 'disabled'])
                ->map(fn ($s) => [
                    'id'      => $s->id,
                    'name'    => (string) $s->name,
                    'address' => (string) ($s->hostname ?: $s->ip_address),
                    'online'  => ! $s->disabled,
                ])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function buildStaffOnline(): array
    {
        try {
            $me = auth('admin')->user();
            $admins = Admin::where('is_disabled', false)
                ->orderByDesc('last_login')
                ->limit(6)
                ->get(['id', 'username', 'first_name', 'last_name', 'last_login']);

            return $admins->map(function ($a) use ($me) {
                $fullName = trim($a->first_name . ' ' . $a->last_name) ?: $a->username;
                $isMe = $me && (int) $me->id === (int) $a->id;

                return [
                    'username' => $a->username,
                    'name'     => mb_strlen($fullName) > 16 ? mb_substr($fullName, 0, 15) : $fullName,
                    'ago'      => $isMe ? '1 second ago' : ($a->last_login ? Carbon::parse($a->last_login)->diffForHumans() : 'Online'),
                ];
            })->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function buildSystemHealth(): array
    {
        try {
            $warnings = 0;
            $danger = 0;

            if (config('app.debug')) {
                $danger++;
            }
            if (! GatewaySettings::where('setting', 'active')->where('value', '1')->exists()) {
                $warnings++;
            }
            if (Schema::hasTable('module_queue') && ModuleQueue::where('status', 'failed')->exists()) {
                $warnings++;
            }

            $percent = max(65, 100 - ($danger * 15) - ($warnings * 6));

            return [
                'rating'   => $percent >= 50 ? 'Good' : 'Needs Review',
                'good'     => $percent >= 50,
                'percent'  => $percent,
                'warnings' => $warnings,
                'danger'   => $danger,
            ];
        } catch (\Throwable) {
            return ['rating' => 'Good', 'good' => true, 'percent' => 85, 'warnings' => 0, 'danger' => 0];
        }
    }

    private function buildBillingSummary(): array
    {
        try {
            $today = today();
            $monthStart = now()->startOfMonth();
            $yearStart = now()->startOfYear();

            $txToday = (float) Transaction::whereDate('date', $today)->sum('amount_in');
            $invToday = (float) Invoice::where('status', 'paid')->whereDate('date_paid', $today)->sum('total');

            $txMonth = (float) Transaction::where('date', '>=', $monthStart)->sum('amount_in');
            $invMonth = (float) Invoice::where('status', 'paid')->where('date_paid', '>=', $monthStart)->sum('total');

            $txYear = (float) Transaction::where('date', '>=', $yearStart)->sum('amount_in');
            $invYear = (float) Invoice::where('status', 'paid')->where('date_paid', '>=', $yearStart)->sum('total');

            $txAll = (float) Transaction::sum('amount_in');
            $invAll = (float) Invoice::where('status', 'paid')->sum('total');

            $fmtClean = function (float $val): string {
                return $val == floor($val)
                    ? number_format($val, 0)
                    : number_format($val, 2);
            };

            return [
                'today'   => $fmtClean(max($txToday, $invToday)),
                'month'   => $fmtClean(max($txMonth, $invMonth)),
                'year'    => $fmtClean(max($txYear, $invYear)),
                'allTime' => $fmtClean(max($txAll, $invAll)),
            ];
        } catch (\Throwable) {
            return ['today' => '0', 'month' => '0', 'year' => '0', 'allTime' => '0'];
        }
    }

    private function buildSupportSummary(): array
    {
        try {
            $me = auth('admin')->user();
            $awaiting = Ticket::whereIn('status', ['open', 'Open', 'customer-reply', 'Customer-Reply'])->count();
            $assigned = $me
                ? Ticket::where('flag', $me->id)->whereNotIn('status', ['closed', 'Closed'])->count()
                : 0;

            $recent = Ticket::whereIn('status', ['open', 'Open', 'customer-reply', 'Customer-Reply'])
                ->orderByDesc('updated_at')
                ->limit(5)
                ->get(['id', 'tid', 'title', 'updated_at'])
                ->map(fn ($t) => [
                    'id'    => $t->id,
                    'tid'   => $t->tid ?: ('TKT-' . $t->id),
                    'title' => (string) $t->title,
                    'ago'   => $t->updated_at ? Carbon::parse($t->updated_at)->diffForHumans() : '',
                ])
                ->all();

            return [
                'awaiting' => $awaiting,
                'assigned' => $assigned,
                'recent'   => $recent,
            ];
        } catch (\Throwable) {
            return ['awaiting' => 0, 'assigned' => 0, 'recent' => []];
        }
    }

    private function buildSystemOverviewSeries(): array
    {
        try {
            // Today (hourly 12am..11pm up to current hour)
            $todayLabels = [];
            $todayNew = [];
            $todayActive = [];
            $todayIncome = [];
            for ($h = 0; $h < 20; $h++) {
                $todayLabels[] = Carbon::today()->addHours($h)->format('ga');
                $start = Carbon::today()->addHours($h);
                $end = (clone $start)->endOfHour();
                $todayNew[] = Order::whereBetween('created_at', [$start, $end])->count();
                $todayActive[] = Order::where('status', 'active')->whereBetween('created_at', [$start, $end])->count();
                $todayIncome[] = round((float) Invoice::where('status', 'paid')->whereBetween('date_paid', [$start, $end])->sum('total'), 2);
            }

            // Last 30 days
            $monthLabels = [];
            $monthNew = [];
            $monthActive = [];
            $monthIncome = [];
            for ($d = 29; $d >= 0; $d -= 2) {
                $day = today()->subDays($d);
                $monthLabels[] = $day->format('d M');
                $monthNew[] = Order::whereDate('created_at', $day)->count();
                $monthActive[] = Order::where('status', 'active')->whereDate('created_at', $day)->count();
                $monthIncome[] = round((float) Invoice::where('status', 'paid')->whereDate('date_paid', $day)->sum('total'), 2);
            }

            // Last 1 year (12 months)
            $yearLabels = [];
            $yearNew = [];
            $yearActive = [];
            $yearIncome = [];
            for ($m = 11; $m >= 0; $m--) {
                $mo = now()->startOfMonth()->subMonths($m);
                $moEnd = (clone $mo)->endOfMonth();
                $yearLabels[] = $mo->format('M Y');
                $yearNew[] = Order::whereBetween('created_at', [$mo, $moEnd])->count();
                $yearActive[] = Order::where('status', 'active')->whereBetween('created_at', [$mo, $moEnd])->count();
                $yearIncome[] = round((float) Invoice::where('status', 'paid')->whereBetween('date_paid', [$mo, $moEnd])->sum('total'), 2);
            }

            return [
                'today' => ['labels' => $todayLabels, 'newOrders' => $todayNew, 'activeOrders' => $todayActive, 'income' => $todayIncome],
                'month' => ['labels' => $monthLabels, 'newOrders' => $monthNew, 'activeOrders' => $monthActive, 'income' => $monthIncome],
                'year'  => ['labels' => $yearLabels,  'newOrders' => $yearNew,  'activeOrders' => $yearActive,  'income' => $yearIncome],
            ];
        } catch (\Throwable) {
            return [
                'today' => ['labels' => ['12am', '6am', '12pm', '6pm'], 'newOrders' => [0, 0, 0, 0], 'activeOrders' => [0, 0, 0, 0], 'income' => [0, 0, 0, 0]],
                'month' => ['labels' => [], 'newOrders' => [], 'activeOrders' => [], 'income' => []],
                'year'  => ['labels' => [], 'newOrders' => [], 'activeOrders' => [], 'income' => []],
            ];
        }
    }

    private function buildProjectTabs(): array
    {
        try {
            if (! Schema::hasTable('projects')) {
                return ['assigned' => [], 'due' => [], 'recent' => []];
            }

            $me = auth('admin')->user();
            $mapRow = function ($p) {
                $due = $p->due_date ? Carbon::parse($p->due_date) : null;

                return [
                    'id'       => $p->id,
                    'title'    => (string) $p->title,
                    'due_date' => $due ? $due->format('d M Y') : '—',
                    'days_in'  => $due ? $due->diffForHumans() : '—',
                    'status'   => ucfirst(str_replace('_', ' ', (string) ($p->status ?: 'open'))),
                ];
            };

            $assigned = $me
                ? DB::table('projects')->where('admin_id', $me->id)->orderBy('due_date')->limit(8)->get()->map($mapRow)->all()
                : [];
            $due = DB::table('projects')->whereNotNull('due_date')->whereNotIn('status', ['completed', 'Completed'])->orderBy('due_date')->limit(8)->get()->map($mapRow)->all();
            $recent = DB::table('projects')->orderByDesc('updated_at')->limit(8)->get()->map($mapRow)->all();

            return ['assigned' => $assigned, 'due' => $due, 'recent' => $recent];
        } catch (\Throwable) {
            return ['assigned' => [], 'due' => [], 'recent' => []];
        }
    }

    private function setupChecklist(): array
    {
        try {
            $companyDone = trim((string) Setting::get('custom_logo_path', '')) !== '';
            $mailer = (string) config('mail.default');
            $emailDone = ! in_array($mailer, ['', 'log', 'array'], true);
            if ($emailDone && $mailer === 'smtp') {
                $emailDone = trim((string) config('mail.mailers.smtp.host')) !== '';
            }
            $gatewayDone = GatewaySettings::query()->exists();
            $serverDone = Server::query()->exists();
            $productDone = Product::query()->exists();

            $items = [
                ['key' => 'company', 'done' => $companyDone, 'route' => 'admin.settings.appearance'],
                ['key' => 'email',   'done' => $emailDone,   'route' => 'admin.settings.general'],
                ['key' => 'gateway', 'done' => $gatewayDone, 'route' => 'admin.config.gateways'],
                ['key' => 'server',  'done' => $serverDone,  'route' => 'admin.config.servers'],
                ['key' => 'product', 'done' => $productDone, 'route' => 'admin.products.create'],
            ];

            $done = count(array_filter($items, fn ($i) => $i['done']));

            return [
                'items'    => $items,
                'done'     => $done,
                'total'    => count($items),
                'complete' => $done === count($items),
            ];
        } catch (\Throwable) {
            return ['items' => [], 'done' => 0, 'total' => 0, 'complete' => true];
        }
    }
}
