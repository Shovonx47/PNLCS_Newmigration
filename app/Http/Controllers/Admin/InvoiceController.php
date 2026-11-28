<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Traits\CsvExportable;
use App\Mail\InvoiceCreatedMail;
use App\Mail\PaymentReminderMail;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\InvoiceChargeAttempt;
use App\Models\InvoiceItem;
use App\Models\InvoiceProduct;
use App\Models\Product;
use App\Models\Setting;
use App\Models\TaxRule;
use App\Services\InvoicePdfService;
use App\Services\InvoiceService;
use App\Services\Module\ModuleRegistry;
use App\Services\PaymentService;
use App\Support\AutoCharge;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    use CsvExportable;

    /** The status tab that is not a status: invoices whose card charge needs a person. */
    public const CHARGE_REVIEW_FILTER = 'charge_review';

    public function __construct(
        protected InvoiceService $invoiceService,
        protected PaymentService $paymentService
    ) {}

    public function index(Request $request): View
    {
        $query = Invoice::query()
            ->with(['client.group', 'client.currency', 'chargeAttempt'])
            ->select('invoices.*')
            ->excludeSettledProformas();

        // The list opens on the Unpaid view (which includes overdue invoices).
        $rawStatus = trim((string) $request->input('status', 'unpaid'));
        $status = strtolower(str_replace(' ', '-', $rawStatus));
        $activeStatus = $status === '' ? 'all' : $status;
        $todayDate = now()->toDateString();

        // "All" is asked for by name. An empty status cannot carry it: the
        // URL generator leaves it out, so a link to it opened on Unpaid.
        if ($status === 'all') {
            $status = '';
        }

        if ($status === 'unpaid') {
            $query->whereIn(DB::raw('LOWER(invoices.status)'), ['unpaid', 'overdue']);
        } elseif ($status === 'overdue') {
            $query->where(function ($q) use ($todayDate) {
                $q->whereRaw('LOWER(invoices.status) = ?', ['overdue'])
                    ->orWhere(function ($sq) use ($todayDate) {
                        $sq->whereRaw('LOWER(invoices.status) = ?', ['unpaid'])
                            ->whereDate('invoices.due_date', '<', $todayDate);
                    });
            });
        } elseif (in_array($status, ['payment-pending', 'payment_pending'], true)) {
            $pendingIds = \App\Models\PaymentNotification::where('status', 'pending')->pluck('invoice_id');
            $query->where(function ($q) use ($pendingIds) {
                $q->whereIn(DB::raw('LOWER(invoices.status)'), ['payment_pending', 'payment-pending'])
                    ->orWhereIn('invoices.id', $pendingIds);
            });
        } elseif ($status === self::CHARGE_REVIEW_FILTER) {
            // Not an invoice status: the invoices whose automatic card payment
            // was sent and whose outcome could not be established. They are
            // listed here rather than on a screen of their own because this is
            // the screen an operator already has open, and each one's answer is
            // on its own invoice page. The dashboard count links straight to
            // this view.
            $query->whereHas('chargeAttempt', fn ($q) => $q->needsReview());
        } elseif ($status !== '') {
            $query->whereRaw('LOWER(invoices.status) = ?', [strtolower($status)]);
        }

        $invoiceNumFilter = trim((string) $request->input('invoice_num', ''));
        if ($invoiceNumFilter !== '') {
            $query->where(function ($q) use ($invoiceNumFilter) {
                $q->where('invoices.invoice_num', 'like', "%{$invoiceNumFilter}%");
                if (ctype_digit($invoiceNumFilter)) {
                    $q->orWhere('invoices.id', (int) $invoiceNumFilter)
                        ->orWhere('invoices.invoice_num', 'INV-WHMCS-'.$invoiceNumFilter);
                }
            });
        }

        $searchTerm = trim((string) $request->input('search', $request->input('client_name', '')));
        $searchField = trim((string) $request->input('search_field', ''));
        if ($searchTerm !== '') {
            if ($searchField === 'Invoice #') {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('invoices.invoice_num', 'like', "%{$searchTerm}%");
                    if (ctype_digit($searchTerm)) {
                        $q->orWhere('invoices.id', (int) $searchTerm)
                            ->orWhere('invoices.invoice_num', 'INV-WHMCS-'.$searchTerm);
                    }
                });
            } elseif ($searchField === 'Client Name') {
                $this->applyClientSearch($query, $searchTerm);
            } else {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('invoices.invoice_num', 'like', "%{$searchTerm}%");
                    if (ctype_digit($searchTerm)) {
                        $q->orWhere('invoices.id', (int) $searchTerm);
                    }
                    $q->orWhereHas('client', fn ($c) => $this->applyClientTextWhere($c, $searchTerm));
                });
            }
        }

        if ($request->filled('payment_method')) {
            $query->where('invoices.payment_method', $request->input('payment_method'));
        }

        if ($request->filled('line_item')) {
            $lineItem = trim((string) $request->input('line_item'));
            $query->whereHas('items', fn ($q) => $q->where('description', 'like', "%{$lineItem}%"));
        }

        if (($invDate = $this->parseFilterDate($request->input('invoice_date'))) !== null) {
            $query->whereDate('invoices.date', $invDate);
        }
        if (($dueDate = $this->parseFilterDate($request->input('due_date'))) !== null) {
            $query->whereDate('invoices.due_date', $dueDate);
        }
        if (($paidDate = $this->parseFilterDate($request->input('date_paid'))) !== null) {
            $query->whereDate('invoices.date_paid', $paidDate);
        }

        if ($request->filled('total_from') && is_numeric($request->input('total_from'))) {
            $query->where('invoices.total', '>=', (float) $request->input('total_from'));
        }
        if ($request->filled('total_to') && is_numeric($request->input('total_to'))) {
            $query->where('invoices.total', '<=', (float) $request->input('total_to'));
        }

        // Allowlisted column sorting (defaults to Due Date descending, matching WHMCS)
        $sort = strtolower((string) $request->input('sort', 'due_date'));
        $dir = strtolower((string) $request->input('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        match ($sort) {
            'id', 'invoice_num' => $query->orderBy('invoices.invoice_num', $dir)->orderBy('invoices.id', $dir),
            'client_name' => $query->leftJoin('clients as inv_sort_client', 'inv_sort_client.id', '=', 'invoices.client_id')
                ->orderBy('inv_sort_client.first_name', $dir)
                ->orderBy('inv_sort_client.last_name', $dir)
                ->orderBy('invoices.id', 'desc'),
            'date' => $query->orderBy('invoices.date', $dir)->orderBy('invoices.id', 'desc'),
            'last_capture' => $query->leftJoin('invoice_charge_attempts as inv_sort_attempt', 'inv_sort_attempt.invoice_id', '=', 'invoices.id')
                ->orderBy('inv_sort_attempt.updated_at', $dir)
                ->orderBy('invoices.id', 'desc'),
            'total' => $query->orderBy('invoices.total', $dir)->orderBy('invoices.id', 'desc'),
            'payment_method' => $query->orderBy('invoices.payment_method', $dir)->orderBy('invoices.id', 'desc'),
            'status' => $query->orderBy('invoices.status', $dir)->orderBy('invoices.id', 'desc'),
            default => $query->orderBy('invoices.due_date', $dir)->orderBy('invoices.id', $dir),
        };

        $allowedSorts = ['id', 'invoice_num', 'client_name', 'date', 'due_date', 'last_capture', 'total', 'payment_method', 'status'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'due_date';
        }

        $invoices = $query->paginate(50)->withQueryString();
        $currencyTotals = $this->computeCurrencyTotals();
        $paymentMethods = $this->availablePaymentMethods();

        $hasActiveFilters = $request->hasAny([
            'invoice_num', 'search', 'client_name', 'payment_method',
            'line_item', 'invoice_date', 'due_date', 'date_paid',
            'total_from', 'total_to',
        ]) && collect($request->only([
            'invoice_num', 'search', 'client_name', 'payment_method',
            'line_item', 'invoice_date', 'due_date', 'date_paid',
            'total_from', 'total_to',
        ]))->contains(fn ($v) => trim((string) $v) !== '');

        return view('admin.invoices.index', compact(
            'invoices',
            'activeStatus',
            'currencyTotals',
            'paymentMethods',
            'sort',
            'dir',
            'hasActiveFilters'
        ));
    }

    /**
     * Format money cleanly in WHMCS invoices table style (comma grouping, suffix).
     */
    public static function formatWhmcsMoney(float $amount, ?string $currencyCode = null, bool $roundWhole = false): string
    {
        static $currencyCache = [];

        $default = Currency::getDefault();
        $code = strtoupper(trim((string) ($currencyCode ?: ($default->code ?? 'USD'))));

        if (! array_key_exists($code, $currencyCache)) {
            $currencyCache[$code] = Currency::where('code', $code)->first() ?? $default;
        }
        $currency = $currencyCache[$code];

        if ($code === 'BDT') {
            $val = $roundWhole ? round($amount) : $amount;
            $hasDecimals = ! $roundWhole && abs($val - round($val)) >= 0.005;

            return ($hasDecimals ? number_format($val, 2, '.', ',') : number_format($val, 0, '.', ',')).'Tk';
        }

        $val = $roundWhole ? round($amount) : $amount;
        if ($currency) {
            return $currency->formatAmount((float) $val);
        }

        return number_format((float) $val, 2).' '.$code;
    }

    private function applyClientSearch($query, string $term): void
    {
        $query->whereHas('client', fn ($c) => $this->applyClientTextWhere($c, $term));
    }

    private function applyClientTextWhere($clientQuery, string $term): void
    {
        $clientQuery->where(function ($c) use ($term) {
            $c->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('company_name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?", ["%{$term}%"]);
        });
    }

    private function parseFilterDate(mixed $raw): ?string
    {
        $value = trim((string) $raw);
        if ($value === '') {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y'] as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $value)->toDateString();
            } catch (\Throwable) {
            }
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Summarize Paid, Unpaid (not yet past due), and Overdue totals per currency.
     *
     * @return list<array{code: string, paid: float, unpaid: float, overdue: float}>
     */
    private function computeCurrencyTotals(): array
    {
        $defaultCode = strtoupper((string) (Currency::getDefault()?->code ?? 'USD'));
        $todayDate = now()->toDateString();

        $rows = Invoice::query()
            ->excludeSettledProformas()
            ->selectRaw("UPPER(COALESCE(NULLIF(TRIM(source_currency), ''), ?)) as curr_code", [$defaultCode])
            ->selectRaw("SUM(CASE WHEN LOWER(status) = 'paid' THEN total ELSE 0 END) as paid_sum")
            ->selectRaw("SUM(CASE WHEN LOWER(status) = 'unpaid' AND (due_date IS NULL OR DATE(due_date) >= ?) THEN (CASE WHEN total - credit > 0 THEN total - credit ELSE 0 END) ELSE 0 END) as unpaid_sum", [$todayDate])
            ->selectRaw("SUM(CASE WHEN LOWER(status) = 'overdue' OR (LOWER(status) = 'unpaid' AND DATE(due_date) < ?) THEN (CASE WHEN total - credit > 0 THEN total - credit ELSE 0 END) ELSE 0 END) as overdue_sum", [$todayDate])
            ->groupBy('curr_code')
            ->get();

        $list = $rows->map(fn ($r) => [
            'code' => (string) $r->curr_code,
            'paid' => (float) ($r->paid_sum ?? 0),
            'unpaid' => (float) ($r->unpaid_sum ?? 0),
            'overdue' => (float) ($r->overdue_sum ?? 0),
        ])->sortBy(fn ($r) => $r['code'] === $defaultCode ? '0' : '1_'.$r['code'])->values()->all();

        if ($list === []) {
            $list[] = [
                'code' => $defaultCode,
                'paid' => 0.0,
                'unpaid' => 0.0,
                'overdue' => 0.0,
            ];
        }

        return $list;
    }

    /**
     * @return array<string, string>
     */
    private function availablePaymentMethods(): array
    {
        $methods = [];

        foreach (app(ModuleRegistry::class)->usableGateways() as $gw) {
            $methods[$gw] = payment_method_label((string) $gw);
        }

        $used = Invoice::query()
            ->whereNotNull('payment_method')
            ->where('payment_method', '!=', '')
            ->distinct()
            ->pluck('payment_method');

        foreach ($used as $gw) {
            $gw = trim((string) $gw);
            if ($gw !== '' && ! isset($methods[$gw])) {
                $methods[$gw] = payment_method_label($gw);
            }
        }

        asort($methods);

        return $methods;
    }

    public function offlineCc(Request $request): View
    {
        $query = Invoice::with(['client', 'chargeAttempt'])
            ->excludeSettledProformas()
            ->whereIn('status', ['unpaid', 'overdue']);

        if ($request->filled('search')) {
            $s = trim((string) $request->input('search'));
            $query->where(function ($q) use ($s) {
                $q->where('invoice_num', 'like', "%{$s}%")
                    ->orWhereHas('client', fn ($c) => $c
                        ->where('first_name', 'like', "%{$s}%")
                        ->orWhere('last_name', 'like', "%{$s}%")
                        ->orWhere('company_name', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%"));
            });
        }

        $invoices = $query->orderBy('due_date', 'asc')->paginate(25);
        $storedCardClients = \App\Models\PaymentMethod::where('status', \App\Models\PaymentMethod::STATUS_ACTIVE)
            ->pluck('client_id')
            ->unique()
            ->flip();

        return view('admin.invoices.offline-cc', compact('invoices', 'storedCardClients'));
    }

    public function disputes(Request $request): View
    {
        $filter = (string) $request->query('filter', 'all');

        $query = \App\Models\Transaction::with(['client', 'invoice'])
            ->where(function ($q) {
                $q->where('amount_out', '>', 0)
                    ->orWhere('description', 'like', '%refund%')
                    ->orWhere('description', 'like', '%dispute%')
                    ->orWhere('description', 'like', '%chargeback%');
            });

        if ($filter === 'refunded') {
            $query->where('amount_out', '>', 0);
        } elseif ($filter === 'open') {
            $query->where('description', 'like', '%dispute%');
        }

        $disputes = $query->orderByDesc('id')->paginate(25);

        return view('admin.invoices.disputes', compact('disputes', 'filter'));
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load('client', 'items', 'transactions');

        // The tax rules the operator may assign to a line: those configured for
        // the customer's country/state, with the default first.
        $taxRules = $invoice->client
            ? $this->invoiceService->taxRatesFor($invoice->client)
                ->map(fn (TaxRule $r) => ['id' => $r->id, 'name' => $r->name, 'rate' => (float) $r->tax_rate])
                ->values()
            : collect();

        // A line keeps the rule it was written under, even if the customer's
        // country changed since. Offer any such rule so the edit box never
        // silently drops a line's tax to 0% just because it is off the list.
        $referencedIds = $invoice->items->pluck('tax_rule_id')->filter()->map(fn ($id) => (int) $id);
        $missingIds = $referencedIds->diff($taxRules->pluck('id'));
        if ($missingIds->isNotEmpty()) {
            $taxRules = $taxRules->merge(
                TaxRule::whereIn('id', $missingIds)->get()
                    ->map(fn (TaxRule $r) => ['id' => $r->id, 'name' => $r->name, 'rate' => (float) $r->tax_rate])
            )->values();
        }

        $activityLog = ActivityLog::forInvoice($invoice)->limit(50)->get();

        // Only asked where it can mean something. An installation that does not
        // collect by card runs exactly the queries it ran before this feature
        // existed, on this page as everywhere else.
        $chargeReview = AutoCharge::enabled()
            ? InvoiceChargeAttempt::query()->needsReview()->where('invoice_id', $invoice->id)->first()
            : null;

        // "Charge card now": offered where the charger could act on it - the
        // switch is on, the invoice is owed, and the customer has a card a
        // gateway can charge. chargeNow() asks the rest again, under its claim.
        $canChargeNow = AutoCharge::enabled()
            && in_array(strtolower((string) $invoice->status), [InvoiceStatus::Unpaid->value, InvoiceStatus::Overdue->value, InvoiceStatus::PartiallyPaid->value], true)
            && \App\Models\PaymentMethod::query()
                ->where('client_id', $invoice->client_id)
                ->where('status', \App\Models\PaymentMethod::STATUS_ACTIVE)
                ->whereIn('gateway_name', array_keys(app(ModuleRegistry::class)->tokenisedGateways()))
                ->exists();

        return view('admin.invoices.show', compact('invoice', 'taxRules', 'activityLog', 'chargeReview', 'canChargeNow'));
    }

    /**
     * A person has looked at the gateway; let the charger try this one again.
     *
     * needs_review is the state that says "a charge was sent and we cannot tell
     * what became of it". Nothing automatic clears it, on purpose — but until
     * now nothing at all did, so an invoice that landed there was out of
     * automatic collection for ever even after the operator had established
     * that no money had moved. That is what this button is: the human end of
     * the state, and the only thing in the system that writes it off.
     *
     * The row is deleted rather than rewritten. One row per invoice is the
     * table's whole design, and what it holds after a review is answered is a
     * card's attempt count that the review has just made meaningless — the next
     * claim starts clean, which is exactly what "try this again" means. The
     * trail is kept where the rest of an invoice's history is kept.
     *
     * It refuses anything that is not in review. A scheduled retry, an
     * exhausted card or an attempt in flight are all live state, and deleting
     * one of those would hand the invoice straight back to the charger with its
     * dunning count reset — or, for an in-flight row, remove the very thing
     * that stops a second charge.
     */
    /**
     * Charge the customer's stored card for this invoice, now.
     *
     * Through AutoChargeService::chargeNow, so through the same claim as the
     * scheduled run: pressing the button twice, or while the 06:45 run has the
     * invoice, charges once.
     */
    public function chargeNow(Invoice $invoice, \App\Services\AutoChargeService $charger): RedirectResponse
    {
        $outcome = $charger->chargeNow($invoice);

        ActivityLog::log(
            __('admin.invoices.charge_now_log', ['outcome' => $outcome]),
            auth('admin')->user()?->email,
            $invoice->client_id,
            $invoice->id,
        );

        $key = 'admin.invoices.charge_now_'.$outcome;
        $message = \Illuminate\Support\Facades\Lang::has($key) ? __($key) : __('admin.invoices.charge_now_refused', ['reason' => $outcome]);

        return back()->with($outcome === 'charged' ? 'success' : 'error', $message);
    }

    public function releaseChargeReview(Invoice $invoice): RedirectResponse
    {
        $row = InvoiceChargeAttempt::query()->needsReview()->where('invoice_id', $invoice->id)->first();

        if ($row === null) {
            return back()->with('info', __('admin.invoices.charge_review_nothing'));
        }

        $row->delete();

        ActivityLog::log(
            __('admin.invoices.charge_review_released_log'),
            auth('admin')->user()?->email,
            $invoice->client_id,
            $invoice->id,
        );

        return back()->with('success', __('admin.invoices.charge_review_released'));
    }

    /**
     * Update a single line item and recalculate the invoice totals.
     */
    public function updateItem(Request $request, Invoice $invoice, InvoiceItem $item): RedirectResponse
    {
        abort_if($item->invoice_id !== $invoice->id, 404);

        if ($this->itemsLocked($invoice)) {
            return back()->with('error', __('admin.invoices.items_locked'));
        }

        $validated = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'qty' => ['required', 'integer', 'min:1', 'max:999999'],
            'amount' => ['required', 'numeric', 'min:0'],
            'tax_rule_id' => ['nullable', 'string', 'max:20'],
        ]);

        $ruleId = $validated['tax_rule_id'] ?? '';

        // A legacy line with a rate but no matching rule keeps its tax as it
        // was; "__legacy__" is the marker the edit form sends for it. Nothing
        // is typed by hand, so a line never drifts from the rule it was issued
        // under, and the rule id is always validated.
        $rule = null;
        if ($ruleId !== '__legacy__') {
            $rule = $ruleId !== '' ? TaxRule::find((int) $ruleId) : null;
            abort_if($ruleId !== '' && $rule === null, 422);
            $rule = $invoice->buyer_tax_exempt && (float) $rule?->tax_rate > 0 ? null : $rule;
        }

        $fields = [
            'description' => $validated['description'],
            'qty' => (int) $validated['qty'],
            'amount' => (float) $validated['amount'],
        ];

        if ($ruleId !== '__legacy__') {
            $taxRate = $rule !== null ? (float) $rule->tax_rate : 0.0;

            // Give every line an explicit rate so a legacy invoice edited once
            // stays consistent: lines without a rate would otherwise stop being
            // taxed the moment any other line carries its own rate. On an
            // exempt buyer's invoice that rate is zero: handing the untouched
            // lines the invoice's rate turned the document into a per-line one,
            // which is the path that does not read the exemption, and left it
            // half taxed - the edited line at zero, the rest at 20% (#389).
            $fallback = $invoice->buyer_tax_exempt ? 0.0 : (float) $invoice->tax_rate;
            $invoice->items()->whereNull('tax_rate')->get()->each(function (InvoiceItem $legacy) use ($fallback) {
                $legacy->update(['tax_rate' => $legacy->taxed ? $fallback : 0.0]);
            });

            $fields += [
                'tax_rate' => $taxRate,
                'tax_label' => $rule?->name,
                'tax_rule_id' => $rule?->id,
                'taxed' => $taxRate > 0,
            ];
        }

        $item->update($fields);

        $this->invoiceService->recalculateTotals($invoice);

        ActivityLog::log(
            "Invoice #{$invoice->invoice_num} line item updated",
            $this->adminName(),
            $invoice->client_id,
            $invoice->id
        );

        return back()->with('success', __('admin.invoices.item_updated'));
    }

    /**
     * Add a blank line item to the invoice.
     */
    public function storeItem(Request $request, Invoice $invoice): RedirectResponse
    {
        if ($this->itemsLocked($invoice)) {
            return back()->with('error', __('admin.invoices.items_locked'));
        }

        $this->invoiceService->addLineItem($invoice, [
            'type' => 'Other',
            'description' => (string) $request->input('description', ''),
            'qty' => 1,
            'amount' => 0,
            'tax_rate' => 0,
        ]);

        ActivityLog::log(
            "Invoice #{$invoice->invoice_num} line item added",
            $this->adminName(),
            $invoice->client_id,
            $invoice->id
        );

        return back()->with('success', __('admin.invoices.item_added'));
    }

    /**
     * Remove a line item and recalculate the invoice totals.
     */
    public function destroyItem(Request $request, Invoice $invoice, InvoiceItem $item): RedirectResponse
    {
        abort_if($item->invoice_id !== $invoice->id, 404);

        if ($this->itemsLocked($invoice)) {
            return back()->with('error', __('admin.invoices.items_locked'));
        }

        $item->delete();
        $this->invoiceService->recalculateTotals($invoice);

        ActivityLog::log(
            "Invoice #{$invoice->invoice_num} line item removed",
            $this->adminName(),
            $invoice->client_id,
            $invoice->id
        );

        return back()->with('success', __('admin.invoices.item_removed'));
    }

    /**
     * A paid, cancelled or refunded invoice is closed: changing its lines
     * recalculated the total under payments and refunds already recorded
     * against it, and the customer's copy no longer matched what was paid.
     */
    private function itemsLocked(Invoice $invoice): bool
    {
        return in_array(strtolower((string) $invoice->status), [
            InvoiceStatus::Paid->value, InvoiceStatus::Cancelled->value, InvoiceStatus::Refunded->value,
        ], true);
    }

    /**
     * Email the invoice to the client.
     */
    public function sendInvoice(Invoice $invoice): RedirectResponse
    {
        abort_if(! $invoice->client?->billingEmail(), 422, 'Client has no email address.');

        Mail::to($invoice->client->billingEmail())->queue(new InvoiceCreatedMail($invoice));

        ActivityLog::log(
            "Invoice #{$invoice->invoice_num} sent by email",
            $this->adminName(),
            $invoice->client_id,
            $invoice->id
        );

        return back()->with('success', __('admin.invoices.email_sent'));
    }

    /**
     * Send a payment reminder for this invoice (positive = due in N days,
     * negative = N days overdue).
     */
    public function sendReminder(Invoice $invoice): RedirectResponse
    {
        abort_if(! $invoice->client?->billingEmail(), 422, 'Client has no email address.');

        $daysOffset = $invoice->due_date
            ? (int) now()->startOfDay()->diffInDays($invoice->due_date->startOfDay(), false)
            : 0;

        Mail::to($invoice->client->billingEmail())->queue(new PaymentReminderMail($invoice, $daysOffset));

        ActivityLog::log(
            "Invoice #{$invoice->invoice_num} payment reminder sent",
            $this->adminName(),
            $invoice->client_id,
            $invoice->id
        );

        return back()->with('success', __('admin.invoices.reminder_sent'));
    }

    private function adminName(): string
    {
        return auth('admin')->user()?->full_name ?? 'Admin';
    }

    /**
     * Show the invoice creation form.
     */
    public function create(Request $request): View
    {
        $clients = Client::orderBy('first_name')->get();
        // r139-gateways: this box picks the gateway the invoice is paid
        // through, and it was filled from the payment_methods table - every
        // customer's stored method, unscoped, labelled with that customer's own
        // description. An operator raising an invoice for one customer was
        // shown what other customers had named their bank accounts, and the
        // list was wrong besides: three customers with a bank account gave
        // three identical options, and an installation where nobody had saved
        // one offered no gateway at all.
        $gateways = app(ModuleRegistry::class)->usableGateways();

        $selectedClient = $request->filled('client_id')
            ? Client::find($request->client_id)
            : null;

        $defaultPaymentMethod = $selectedClient?->default_payment_method;

        $dueDays = (int) Setting::get('InvoiceDueDays', 14);

        // Cheapest sold cycle stands in for the product's money value, so the
        // builder can pre-fill the amount when a product is picked.
        $products = Product::active()->with('pricing')->get()->map(function (Product $p) {
            $cycles = $p->pricedCycles();

            return [
                'name' => $p->name,
                'amount' => $cycles ? min($cycles) : null,
                'cycle' => $cycles ? array_search(min($cycles), $cycles, true) : null,
                'taxed' => (bool) $p->tax,
            ];
        })->values();

        // Catalog products/services (goods) are offered in the same box. They
        // carry their own price, unit and VAT rate.
        $catalogProducts = InvoiceProduct::orderBy('name')->get()->map(fn (InvoiceProduct $p) => [
            'name' => $p->name,
            'amount' => (float) $p->price,
            'unit' => $p->unit,
            'tax_rate' => (float) $p->tax_rate,
            'tax_label' => $p->tax_label,
            'tax_rule_id' => $p->tax_rule_id,
        ])->values();

        $products = $products->merge($catalogProducts)->values();

        $defaultCurrency = Currency::getDefault();

        // Per-client applicable tax rate (shown in the summary while building
        // the invoice) plus every rate configured for the client's country so
        // the operator can pick one per line. Matches the engine: country+state.
        $invoiceService = app(InvoiceService::class);
        $clients = $clients->map(function (Client $client) use ($invoiceService) {
            $rule = $invoiceService->taxRuleFor($client);
            $rates = $invoiceService->taxRatesFor($client)
                ->map(fn (TaxRule $r) => [
                    'id' => $r->id,
                    'name' => $r->name,
                    'rate' => (float) $r->tax_rate,
                    'is_default' => (bool) $r->is_default,
                ])
                ->values();

            return clone $client
                ->setAttribute('billing_tax_rate', (float) ($rule?->tax_rate ?? 0))
                ->setAttribute('billing_tax_label', $rule?->name ?? '')
                ->setAttribute('billing_tax_rates', $rates);
        });

        return view('admin.invoices.create', compact(
            'clients',
            'gateways',
            'selectedClient',
            'products',
            'defaultCurrency',
            'defaultPaymentMethod',
            'dueDays'
        ));
    }

    /**
     * Store a new invoice created from the admin form.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'date' => ['required', 'date'],
            'due_date' => ['required', 'date'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.qty' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'items.*.amount' => ['required', 'numeric', 'min:0'],
            'items.*.tax_rule_id' => ['nullable', 'integer', 'exists:tax_rules,id'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.tax_label' => ['nullable', 'string', 'max:255'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
        ]);

        $client = Client::findOrFail($validated['client_id']);

        $items = array_map(fn ($item) => [
            'type' => 'Other',
            'description' => $item['description'],
            'qty' => (int) ($item['qty'] ?? 1),
            'amount' => (float) $item['amount'],
            'tax_rule_id' => ($item['tax_rule_id'] ?? null) ?: null,
            // Per-item VAT percentage; an empty field means untaxed.
            'tax_rate' => isset($item['tax_rate']) && $item['tax_rate'] !== '' ? (float) $item['tax_rate'] : 0.0,
            'tax_label' => $item['tax_label'] ?? null,
            'unit' => $item['unit'] ?? null,
        ], $validated['items']);

        $invoice = $this->invoiceService->createInvoice($client, $items, [
            'date' => $validated['date'],
            'due_date' => $validated['due_date'],
            'payment_method' => $validated['payment_method'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('admin.invoices.show', $invoice)
            ->with('success', __('admin.messages.invoice_created', ['num' => $invoice->invoice_num]));
    }

    /**
     * Mark an invoice as paid.
     */
    public function markPaid(Request $request, Invoice $invoice): RedirectResponse
    {
        if (strtolower((string) $invoice->status) === InvoiceStatus::Paid->value) {
            return back()->with('info', __('admin.messages.invoice_already_paid'));
        }

        $validated = $request->validate([
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'gateway' => ['nullable', 'string', 'max:100'],
            'amount' => ['nullable', 'numeric', 'min:0.01'],
        ]);

        // "Credit" pays from the client's credit balance. Recorded as an
        // ordinary payment it marked the invoice paid and left the balance
        // untouched, so the client kept the money and the invoice was settled.
        if (($validated['gateway'] ?? null) === 'credit') {
            return $this->payFromCredit($invoice, isset($validated['amount']) ? (float) $validated['amount'] : null);
        }

        // The form's amount field used to be silently ignored, marking the
        // invoice fully paid regardless of what the admin entered. Routing
        // through PaymentService keeps partial payments, overpayment-to-credit
        // and the InvoicePaid event chain consistent.
        $result = $this->paymentService->applyPayment(
            $invoice,
            $validated['gateway'] ?? 'manual',
            $validated['transaction_id'] ?? null,
            isset($validated['amount']) ? (float) $validated['amount'] : null
        );

        if (($result['balance'] ?? 0) > 0.009) {
            return back()->with('success', __('admin.messages.invoice_partially_paid', [
                'num' => $invoice->invoice_num,
                'balance' => number_format($result['balance'], 2),
            ]));
        }

        return back()->with('success', __('admin.messages.invoice_marked_paid', ['num' => $invoice->invoice_num]));
    }

    private function payFromCredit(Invoice $invoice, ?float $amount): RedirectResponse
    {
        $due = $invoice->amountDue();
        $credit = (float) $invoice->client?->credit;

        if ($credit < 0.01 || $due < 0.01) {
            return back()->with('error', __('admin.messages.no_credit_available'));
        }

        // applyCredit caps at the balance and at what is due, under locks.
        $invoice = $this->invoiceService->applyCredit($invoice, min($amount ?? $due, $due));
        $remaining = $invoice->amountDue();

        if ($remaining > 0.009) {
            return back()->with('success', __('admin.messages.invoice_partially_paid', [
                'num' => $invoice->invoice_num,
                'balance' => number_format($remaining, 2),
            ]));
        }

        return back()->with('success', __('admin.messages.invoice_marked_paid', ['num' => $invoice->invoice_num]));
    }

    /**
     * Refund an invoice (full or partial) through the original gateway,
     * or offline for bank transfer / manual payments.
     */
    public function refund(Request $request, Invoice $invoice): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:500'],
            'gateway_refund' => ['nullable', 'boolean'],
        ]);

        $result = app(PaymentService::class)->refundInvoice(
            $invoice,
            $validated['amount'] ?? null,
            [
                'reason' => $validated['reason'] ?? null,
                'gateway_refund' => $request->boolean('gateway_refund', true),
            ]
        );

        return $result['success']
            ? back()->with('success', $result['message'])
            : back()->with('error', $result['message']);
    }

    /**
     * Cancel an invoice.
     */
    public function cancel(Invoice $invoice): RedirectResponse
    {
        if (strtolower((string) $invoice->status) === InvoiceStatus::Paid->value) {
            return back()->with('error', __('messages.error.paid_invoices_cannot_be_cancelled'));
        }

        $this->invoiceService->cancelInvoice($invoice);

        return back()->with('success', __('admin.messages.invoice_cancelled', ['num' => $invoice->invoice_num]));
    }

    /**
     * Export invoices as CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $query = Invoice::with('client')->excludeSettledProformas();

        if ($request->filled('status')) {
            $status = (string) $request->status;

            if ($status === 'unpaid') {
                $query->whereIn('status', ['unpaid', 'overdue']);
            } else {
                $query->where('status', $status);
            }
        }

        $invoices = $query->orderBy('id', 'asc')->get();

        $rows = $invoices->map(fn ($inv) => [
            $inv->id,
            $inv->invoice_num ?? '',
            $inv->client?->full_name ?? '',
            $inv->client?->email ?? '',
            $inv->status,
            $inv->subtotal ?? '0.00',
            $inv->tax_amount ?? '0.00',
            $inv->total ?? '0.00',
            $inv->payment_method ?? '',
            $inv->date?->format('Y-m-d') ?? '',
            $inv->due_date?->format('Y-m-d') ?? '',
            $inv->date_paid?->format('Y-m-d H:i:s') ?? '',
        ]);

        return $this->streamCsvDownload(
            'invoices-'.now()->format('Y-m-d').'.csv',
            ['ID', 'Invoice #', 'Client', 'Email', 'Status', 'Subtotal', 'Tax', 'Total', 'Payment Method', 'Date', 'Due Date', 'Date Paid'],
            $rows
        );
    }

    /**
     * Render a printable HTML view of the invoice for admins ("View" button).
     */
    public function preview(Invoice $invoice, InvoicePdfService $pdfService): Response
    {
        return response($pdfService->renderHtml($invoice));
    }

    /**
     * Download invoice as PDF.
     */
    public function downloadPdf(Invoice $invoice, InvoicePdfService $pdfService)
    {
        return $pdfService->download($invoice);
    }

    /**
     * Permanently delete an invoice.
     */
    public function destroy(Invoice $invoice): RedirectResponse
    {
        $num = $invoice->invoice_num ?? $invoice->id;
        $clientId = $invoice->client_id;
        $invoiceId = $invoice->id;

        $this->invoiceService->deleteInvoice($invoice);

        ActivityLog::log(
            "Invoice #{$num} deleted",
            $this->adminName(),
            $clientId,
            $invoiceId
        );

        return back()->with('success', __('admin.messages.invoice_deleted', ['num' => $num]));
    }
}
