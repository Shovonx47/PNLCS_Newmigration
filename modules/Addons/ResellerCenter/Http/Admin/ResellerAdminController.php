<?php

namespace Modules\Addons\ResellerCenter\Http\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Addons\ResellerCenter\Models\Reseller;
use Modules\Addons\ResellerCenter\Models\ResellerClient;
use Modules\Addons\ResellerCenter\Models\ResellerGroup;
use Modules\Addons\ResellerCenter\Models\ResellerLog;
use Modules\Addons\ResellerCenter\Models\ResellerPayout;
use Modules\Addons\ResellerCenter\Models\ResellerPricing;
use Modules\Addons\ResellerCenter\Services\ResellerService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResellerAdminController extends Controller
{
    public function __construct(protected ResellerService $service) {}

    public function dashboard()
    {
        $stats = [
            'total_resellers' => Reseller::count(),
            'active_resellers' => Reseller::where('status', 'active')->count(),
            'end_clients' => ResellerClient::count(),
            'paid_payouts' => (float) ResellerPayout::where('status', 'paid')->sum('amount'),
            'pending_payouts' => (float) ResellerPayout::where('status', 'pending')->sum('amount'),
            'groups_count' => ResellerGroup::count(),
        ];

        $recentResellers = Reseller::with(['client', 'group'])
            ->withCount('endClients')
            ->latest('id')
            ->limit(15)
            ->get();

        return view('resellercenter::admin.dashboard', compact('stats', 'recentResellers'));
    }

    public function resellers()
    {
        $resellers = Reseller::with(['client', 'group'])
            ->withCount('endClients')
            ->orderByDesc('id')
            ->paginate(25);

        $groups = ResellerGroup::orderBy('name')->get();
        $existingIds = Reseller::pluck('client_id')->all();
        $clients = Client::whereNotIn('id', $existingIds)->orderBy('first_name')->limit(300)->get();

        return view('resellercenter::admin.resellers', compact('resellers', 'groups', 'clients'));
    }

    public function storeReseller(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|integer|exists:clients,id|unique:resellers,client_id',
            'group_id' => 'required|integer|exists:reseller_groups,id',
            'company_name' => 'nullable|string|max:190',
            'email_address' => 'nullable|email|max:190',
            'cname' => 'nullable|string|max:190',
            'paypal_email' => 'nullable|email|max:190',
        ]);

        $reseller = Reseller::create([
            'client_id' => (int) $validated['client_id'],
            'group_id' => (int) $validated['group_id'],
            'company_name' => trim((string) ($validated['company_name'] ?? '')) ?: null,
            'email_address' => trim((string) ($validated['email_address'] ?? '')) ?: null,
            'cname' => trim((string) ($validated['cname'] ?? '')) ?: null,
            'paypal_email' => trim((string) ($validated['paypal_email'] ?? '')) ?: null,
            'store_template' => 'standard_cart',
            'branding_settings' => [],
            'smtp_settings' => [],
            'balance' => 0,
            'status' => 'active',
        ]);

        $this->service->log($reseller->id, auth('admin')->id(), 'reseller_created', "Reseller #{$reseller->id} created for client #{$reseller->client_id}", $request->ip());

        return redirect()->route('admin.extensions.reseller-center.resellers.show', $reseller)
            ->with('success', 'Reseller account created.');
    }

    public function showReseller(Reseller $reseller)
    {
        $reseller->load(['client', 'group', 'endClients.client', 'promos', 'gateways']);
        $groups = ResellerGroup::orderBy('name')->get();
        $metrics = $this->service->resellerMetrics($reseller);

        $endClientIds = $reseller->endClients->pluck('client_id')->all();
        $services = $endClientIds === []
            ? collect()
            : Service::with(['product', 'client'])->whereIn('client_id', $endClientIds)->latest('id')->limit(50)->get();

        $assignedClientIds = ResellerClient::pluck('client_id')->merge([$reseller->client_id])->all();
        $availableClients = Client::whereNotIn('id', $assignedClientIds)->orderBy('first_name')->limit(300)->get();

        return view('resellercenter::admin.reseller-show', compact('reseller', 'groups', 'metrics', 'services', 'availableClients'));
    }

    public function updateReseller(Request $request, Reseller $reseller)
    {
        $validated = $request->validate([
            'group_id' => 'required|integer|exists:reseller_groups,id',
            'company_name' => 'nullable|string|max:190',
            'email_address' => 'nullable|email|max:190',
            'cname' => 'nullable|string|max:190',
            'store_template' => 'nullable|string|in:standard_cart,modern,classic',
            'status' => 'required|string|in:active,inactive',
            'paypal_email' => 'nullable|email|max:190',
            'logo_url' => 'nullable|url|max:512',
        ]);

        $reseller->update([
            'group_id' => (int) $validated['group_id'],
            'company_name' => trim((string) ($validated['company_name'] ?? '')) ?: null,
            'email_address' => trim((string) ($validated['email_address'] ?? '')) ?: null,
            'cname' => trim((string) ($validated['cname'] ?? '')) ?: null,
            'store_template' => $validated['store_template'] ?: $reseller->store_template,
            'status' => $validated['status'],
            'paypal_email' => trim((string) ($validated['paypal_email'] ?? '')) ?: null,
            'logo_url' => trim((string) ($validated['logo_url'] ?? '')) ?: null,
        ]);

        $this->service->log($reseller->id, auth('admin')->id(), 'reseller_updated', "Reseller #{$reseller->id} updated", $request->ip());

        return back()->with('success', 'Reseller configuration saved.');
    }

    public function destroyReseller(Request $request, Reseller $reseller)
    {
        $id = $reseller->id;
        ResellerClient::where('reseller_id', $id)->delete();
        $reseller->delete();

        $this->service->log(null, auth('admin')->id(), 'reseller_deleted', "Reseller #{$id} deleted", $request->ip());

        return redirect()->route('admin.extensions.reseller-center.resellers')
            ->with('success', 'Reseller removed.');
    }

    public function assignClient(Request $request, Reseller $reseller)
    {
        $validated = $request->validate([
            'client_id' => 'required|integer|exists:clients,id',
        ]);

        $targetClientId = (int) $validated['client_id'];
        if ($targetClientId === (int) $reseller->client_id) {
            return back()->with('error', 'A reseller cannot be assigned as their own end-client.');
        }

        if (ResellerClient::where('client_id', $targetClientId)->exists()) {
            return back()->with('error', 'This client is already assigned to a reseller.');
        }

        ResellerClient::create([
            'reseller_id' => $reseller->id,
            'client_id' => $targetClientId,
        ]);

        $this->service->log($reseller->id, auth('admin')->id(), 'client_assigned', "Assigned client #{$targetClientId} to reseller #{$reseller->id}", $request->ip());

        return back()->with('success', 'End client assigned to reseller.');
    }

    public function unassignClient(Request $request, Reseller $reseller, ResellerClient $link)
    {
        abort_unless((int) $link->reseller_id === (int) $reseller->id, 404);
        $cid = $link->client_id;
        $link->delete();

        $this->service->log($reseller->id, auth('admin')->id(), 'client_unassigned', "Unassigned client #{$cid} from reseller #{$reseller->id}", $request->ip());

        return back()->with('success', 'Client unlinked from reseller.');
    }

    public function groups()
    {
        $groups = ResellerGroup::withCount('resellers')->with('pricingRules.product')->orderBy('id')->get();
        $products = Product::orderBy('name')->get(['id', 'name']);

        return view('resellercenter::admin.groups', compact('groups', 'products'));
    }

    public function storeGroup(Request $request)
    {
        $data = $this->validateGroup($request);
        ResellerGroup::create($data);

        return back()->with('success', 'Reseller pricing group created.');
    }

    public function updateGroup(Request $request, ResellerGroup $group)
    {
        $group->update($this->validateGroup($request));

        return back()->with('success', 'Reseller pricing group updated.');
    }

    public function destroyGroup(ResellerGroup $group)
    {
        if ($group->resellers()->exists()) {
            return back()->with('error', 'Cannot delete a tier that still has active resellers.');
        }

        $group->pricingRules()->delete();
        $group->delete();

        return back()->with('success', 'Reseller group deleted.');
    }

    public function storePricing(Request $request, ResellerGroup $group)
    {
        $validated = $request->validate([
            'rel_id' => 'required|integer|exists:products,id',
            'billing_cycle' => 'nullable|string|max:32',
            'payout_type' => 'required|string|in:percent,fixed,difference',
            'payout_value' => 'required|numeric|min:0|max:1000000',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
        ]);

        ResellerPricing::create([
            'group_id' => $group->id,
            'type' => 'product',
            'rel_id' => (int) $validated['rel_id'],
            'billing_cycle' => trim((string) ($validated['billing_cycle'] ?? '')) ?: null,
            'payout_type' => $validated['payout_type'],
            'payout_value' => (float) $validated['payout_value'],
            'min_price' => isset($validated['min_price']) ? (float) $validated['min_price'] : null,
            'max_price' => isset($validated['max_price']) ? (float) $validated['max_price'] : null,
        ]);

        return back()->with('success', 'Commission rule added.');
    }

    public function destroyPricing(ResellerPricing $pricing)
    {
        $pricing->delete();

        return back()->with('success', 'Commission rule deleted.');
    }

    public function payouts()
    {
        $payouts = ResellerPayout::with(['reseller.client', 'client', 'invoice'])
            ->latest('id')
            ->paginate(30);

        $resellersWithPending = Reseller::with('client')
            ->withSum(['payouts as pending_sum' => fn ($q) => $q->where('status', 'pending')], 'amount')
            ->get()
            ->filter(fn (Reseller $r) => (float) ($r->pending_sum ?? 0) > 0);

        $totalPending = (float) ResellerPayout::where('status', 'pending')->sum('amount');

        return view('resellercenter::admin.payouts', compact('payouts', 'resellersWithPending', 'totalPending'));
    }

    public function markPayoutPaid(Request $request, ResellerPayout $payout)
    {
        if (! $this->service->markPayoutPaid($payout, auth('admin')->id(), (string) $request->ip())) {
            return back()->with('error', 'Payout is already processed or missing.');
        }

        return back()->with('success', 'Payout marked paid and credited.');
    }

    public function massPayout(Request $request)
    {
        $validated = $request->validate([
            'reseller_ids' => 'required|array|min:1',
            'reseller_ids.*' => 'integer|exists:resellers,id',
        ]);

        $res = $this->service->massPayout($validated['reseller_ids'], auth('admin')->id(), (string) $request->ip());

        return back()->with('success', "Processed {$res['count']} payout(s) totaling ".money_fmt($res['total']).'.');
    }

    public function utilities()
    {
        $logs = ResellerLog::with('reseller')->latest('id')->limit(50)->get();
        $tables = [
            'reseller_groups',
            'resellers',
            'reseller_clients',
            'reseller_pricing',
            'reseller_payouts',
            'reseller_promos',
            'reseller_invoices',
            'reseller_gateways',
            'reseller_email_templates',
            'reseller_logs',
        ];
        $tableStatus = [];
        foreach ($tables as $t) {
            $tableStatus[$t] = Schema::hasTable($t) ? DB::table($t)->count() : null;
        }

        return view('resellercenter::admin.utilities', compact('logs', 'tableStatus'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $type = $request->query('type') === 'payouts' ? 'payouts' : 'resellers';
        $filename = 'resellers-'.$type.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($type) {
            $out = fopen('php://output', 'w');
            if ($type === 'payouts') {
                fputcsv($out, ['ID', 'Reseller Company', 'End Client', 'Amount', 'Method', 'Status', 'Created At']);
                ResellerPayout::with(['reseller.client', 'client'])->orderBy('id')->chunk(200, function ($rows) use ($out) {
                    foreach ($rows as $row) {
                        $resellerName = $row->reseller?->company_name ?: trim(($row->reseller?->client?->first_name ?? '').' '.($row->reseller?->client?->last_name ?? ''));
                        $clientName = $row->client ? trim($row->client->first_name.' '.$row->client->last_name) : '';
                        fputcsv($out, [
                            $row->id,
                            ResellerService::sanitizeCsvCell($resellerName),
                            ResellerService::sanitizeCsvCell($clientName),
                            number_format((float) $row->amount, 2, '.', ''),
                            ResellerService::sanitizeCsvCell($row->payout_method),
                            ResellerService::sanitizeCsvCell($row->status),
                            $row->created_at?->toDateTimeString() ?? '',
                        ]);
                    }
                });
            } else {
                fputcsv($out, ['ID', 'First Name', 'Last Name', 'Email', 'Company', 'Tier', 'Balance', 'Status', 'Created At']);
                Reseller::with(['client', 'group'])->orderBy('id')->chunk(200, function ($rows) use ($out) {
                    foreach ($rows as $row) {
                        fputcsv($out, [
                            $row->id,
                            ResellerService::sanitizeCsvCell($row->client?->first_name ?? ''),
                            ResellerService::sanitizeCsvCell($row->client?->last_name ?? ''),
                            ResellerService::sanitizeCsvCell($row->email_address ?: ($row->client?->email ?? '')),
                            ResellerService::sanitizeCsvCell($row->company_name ?: ($row->client?->company_name ?? '')),
                            ResellerService::sanitizeCsvCell($row->group?->name ?? ''),
                            number_format((float) $row->balance, 2, '.', ''),
                            ResellerService::sanitizeCsvCell($row->status),
                            $row->created_at?->toDateTimeString() ?? '',
                        ]);
                    }
                });
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function clearLogs(Request $request)
    {
        ResellerLog::query()->delete();
        $this->service->log(null, auth('admin')->id(), 'logs_cleared', 'Cleared reseller audit logs', $request->ip());

        return back()->with('success', 'Reseller audit logs cleared.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateGroup(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:1000',
            'payout_method' => 'required|string|in:credit_balance,paypal,manual',
            'invoicing_method' => 'required|string|in:provider,reseller',
            'credit_limit' => 'nullable|numeric|min:0|max:10000000',
            'default_commission_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        return [
            'name' => trim($validated['name']),
            'description' => trim((string) ($validated['description'] ?? '')) ?: null,
            'payout_method' => $validated['payout_method'],
            'invoicing_method' => $validated['invoicing_method'],
            'credit_limit' => (float) ($validated['credit_limit'] ?? 0),
            'default_commission_percent' => (float) ($validated['default_commission_percent'] ?? 10),
            'allow_deferred' => $request->boolean('allow_deferred'),
            'allow_promos' => $request->boolean('allow_promos'),
            'allow_own_smtp' => $request->boolean('allow_own_smtp'),
            'allow_suspend' => $request->boolean('allow_suspend'),
            'allow_global_search' => $request->boolean('allow_global_search', true),
            'permissions' => [
                'can_access_clients' => $request->boolean('p_clients') ? 1 : 0,
                'can_manage_orders' => $request->boolean('p_orders') ? 1 : 0,
                'can_view_billing' => $request->boolean('p_billing') ? 1 : 0,
                'can_use_promos' => $request->boolean('p_promos') ? 1 : 0,
                'can_suspend' => $request->boolean('p_suspend') ? 1 : 0,
            ],
            'settings' => [],
        ];
    }
}
