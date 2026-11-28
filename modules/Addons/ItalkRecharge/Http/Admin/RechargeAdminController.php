<?php

namespace Modules\Addons\ItalkRecharge\Http\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Modules\Addons\ItalkRecharge\Models\ItalkRechargeAccount;
use Modules\Addons\ItalkRecharge\Models\ItalkRechargeLog;
use Modules\Addons\ItalkRecharge\Models\ItalkRechargeProvider;
use Modules\Addons\ItalkRecharge\Services\RechargeService;

/**
 * Extensions -> Multi-Provider Auto Recharge (/admin/extensions/iptsp-recharge).
 *
 * Matches WHMCS italk_recharge UI with Providers summary table (live Pending/Success/Failed
 * stats per provider), Add/Edit provider screens, Manage Pending logs resolver, and
 * per-provider History + Client PIN & Manual Top-Up management.
 */
class RechargeAdminController extends Controller
{
    public function __construct(private readonly RechargeService $recharges) {}

    public function index(Request $request)
    {
        $action = strtolower(trim((string) $request->query('action', 'list')));
        $providers = ItalkRechargeProvider::orderByDesc('id')->get();
        $clients = Client::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'company_name', 'email']);
        $apiTypes = ItalkRechargeProvider::API_TYPES;
        $maxAmount = RechargeService::MAX_AMOUNT;

        if ($action === 'add') {
            return view('italkrecharge::admin.provider-form', [
                'provider' => null,
                'isEdit' => false,
                'apiTypes' => $apiTypes,
            ]);
        }

        if ($action === 'edit' && $request->filled('id')) {
            $provider = ItalkRechargeProvider::findOrFail((int) $request->query('id'));

            return view('italkrecharge::admin.provider-form', [
                'provider' => $provider,
                'isEdit' => true,
                'apiTypes' => $apiTypes,
            ]);
        }

        if ($action === 'pending') {
            $pendingLogs = ItalkRechargeLog::with(['client', 'provider'])
                ->whereRaw('LOWER(status) = ?', ['pending'])
                ->orderBy('id')
                ->get();
            $totalPendingCount = $pendingLogs->count();
            $totalPendingAmount = (float) $pendingLogs->sum('amount');
            $accounts = ItalkRechargeAccount::with(['client', 'provider'])->orderByDesc('id')->limit(200)->get();

            return view('italkrecharge::admin.pending', compact(
                'pendingLogs',
                'totalPendingCount',
                'totalPendingAmount',
                'providers',
                'accounts',
                'clients',
                'maxAmount'
            ));
        }

        if ($action === 'history' && $request->filled('id')) {
            $provider = ItalkRechargeProvider::findOrFail((int) $request->query('id'));
            $logs = ItalkRechargeLog::with(['client'])
                ->where('provider_id', $provider->id)
                ->orderByDesc('id')
                ->limit(150)
                ->get();

            $statRows = ItalkRechargeLog::where('provider_id', $provider->id)
                ->selectRaw('LOWER(status) as st, COUNT(*) as cnt, COALESCE(SUM(amount), 0) as amt')
                ->groupBy('st')
                ->get()
                ->keyBy('st');

            $totalAmount = (float) $statRows->sum('amt');
            $successCount = (int) ($statRows->get('success')->cnt ?? 0);
            $pendingCount = (int) ($statRows->get('pending')->cnt ?? 0);
            $failedCount = (int) ($statRows->get('failed')->cnt ?? 0);

            $accounts = ItalkRechargeAccount::with('client')
                ->where('provider_id', $provider->id)
                ->orderByDesc('id')
                ->limit(200)
                ->get();

            return view('italkrecharge::admin.history', compact(
                'provider',
                'logs',
                'totalAmount',
                'successCount',
                'pendingCount',
                'failedCount',
                'accounts',
                'providers',
                'clients',
                'maxAmount'
            ));
        }

        // Default Provider List view with real per-provider Pending/Success/Failed stats
        $rawStats = ItalkRechargeLog::query()
            ->selectRaw('provider_id, LOWER(status) as st, COUNT(*) as cnt, COALESCE(SUM(amount), 0) as amt')
            ->groupBy('provider_id', 'st')
            ->get();

        $statsByProvider = [];
        foreach ($rawStats as $row) {
            $pid = (int) $row->provider_id;
            $st = (string) $row->st;
            $statsByProvider[$pid][$st] = [
                'count' => (int) $row->cnt,
                'amount' => (float) $row->amt,
            ];
        }

        return view('italkrecharge::admin.index', [
            'providers' => $providers,
            'statsByProvider' => $statsByProvider,
            'apiTypes' => $apiTypes,
        ]);
    }

    public function storeProvider(Request $request): RedirectResponse
    {
        $validated = $this->validateProvider($request);

        if ($request->hasFile('logo_file')) {
            $validated['logo_url'] = $this->storeLogoFile($request);
        }

        ItalkRechargeProvider::create($validated);

        return redirect()
            ->route('admin.extensions.iptsp-recharge.index')
            ->with('success', 'Recharge provider added.');
    }

    public function updateProvider(Request $request, ItalkRechargeProvider $provider): RedirectResponse
    {
        $validated = $this->validateProvider($request, true);

        // An empty secret field means "keep what is stored", never "erase it".
        foreach (['password', 'api_key'] as $secret) {
            if (($validated[$secret] ?? '') === '' || $validated[$secret] === null) {
                unset($validated[$secret]);
            }
        }

        if ($request->hasFile('logo_file')) {
            $validated['logo_url'] = $this->storeLogoFile($request, $provider->id);
        }

        $provider->update($validated);

        return redirect()
            ->route('admin.extensions.iptsp-recharge.index')
            ->with('success', 'Recharge provider updated.');
    }

    public function destroyProvider(ItalkRechargeProvider $provider): RedirectResponse
    {
        ItalkRechargeAccount::where('provider_id', $provider->id)->update(['status' => 'inactive']);
        $provider->delete();

        return redirect()
            ->route('admin.extensions.iptsp-recharge.index')
            ->with('success', 'Provider removed. Its client PIN accounts were disabled and the log is kept.');
    }

    public function resolveLog(Request $request, ItalkRechargeLog $log): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['success', 'failed'])],
        ]);

        if (strtolower((string) $log->status) !== 'pending') {
            return back()->with('error', 'Only pending logs can be updated manually.');
        }

        $newStatus = $validated['status'];

        DB::transaction(function () use ($log, $newStatus) {
            $log->update([
                'status' => $newStatus,
                'last_attempt_at' => now(),
                'api_response' => json_encode([
                    'success' => $newStatus === 'success',
                    'message' => 'Marked as '.strtoupper($newStatus).' manually by administrator',
                ]),
            ]);

            if ($newStatus === 'success') {
                $account = ItalkRechargeAccount::where('client_id', $log->client_id)
                    ->where('provider_id', $log->provider_id)
                    ->first();
                if ($account) {
                    $account->increment('total_recharged', (float) $log->amount);
                    $account->update(['last_recharge' => now()]);
                }
            }
        });

        return back()->with('success', 'Log #'.$log->id.' marked as '.strtoupper($newStatus).'.');
    }

    public function storeAccount(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')],
            'provider_id' => ['required', 'integer', Rule::exists('italk_recharge_providers', 'id')],
            'pin_number' => ['required', 'string', 'max:64', 'regex:/^[0-9A-Za-z._+\-]{3,64}$/'],
        ]);

        ItalkRechargeAccount::updateOrCreate(
            ['client_id' => $validated['client_id'], 'provider_id' => $validated['provider_id']],
            ['pin_number' => trim($validated['pin_number']), 'status' => 'active']
        );

        return back()->with('success', 'Client PIN account saved.');
    }

    public function destroyAccount(ItalkRechargeAccount $account): RedirectResponse
    {
        $account->delete();

        return back()->with('success', 'Client PIN account removed. The audit log is kept.');
    }

    public function rechargeNow(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')],
            'provider_id' => ['required', 'integer', Rule::exists('italk_recharge_providers', 'id')],
            'pin_number' => ['required', 'string', 'max:64', 'regex:/^[0-9A-Za-z._+\-]{3,64}$/'],
            'amount' => ['required', 'numeric', 'min:'.RechargeService::MIN_AMOUNT, 'max:'.RechargeService::MAX_AMOUNT],
        ]);

        $client = Client::findOrFail($validated['client_id']);
        $provider = ItalkRechargeProvider::findOrFail($validated['provider_id']);
        $account = ItalkRechargeAccount::where('client_id', $client->id)->where('provider_id', $provider->id)->first();

        $result = $this->recharges->recharge($client, $provider, $validated['pin_number'], (float) $validated['amount'], [
            'account_id' => $account?->id,
        ]);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function retryLog(ItalkRechargeLog $log): RedirectResponse
    {
        $result = $this->recharges->retry($log);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    /** @return array<string, mixed> */
    private function validateProvider(Request $request, bool $updating = false): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'base_url' => ['required', 'string', 'max:255', 'url:https'],
            'base_path' => ['nullable', 'string', 'max:100'],
            'api_type' => ['required', Rule::in(array_keys(ItalkRechargeProvider::API_TYPES))],
            'username' => ['required', 'string', 'max:190'],
            'password' => [$updating ? 'nullable' : 'required', 'string', 'max:500'],
            'api_key' => ['nullable', 'string', 'max:500'],
            'logo_file' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        unset($validated['logo_file']);
        $validated['base_path'] = $validated['base_path'] ?: ($validated['api_type'] === 'italk' ? '/iconiptsp/' : '/');
        $validated['integration_type'] = $validated['api_type'] === 'italk' ? 'scraper' : 'rest';

        return $validated;
    }

    private function storeLogoFile(Request $request, ?int $providerId = null): string
    {
        $file = $request->file('logo_file');
        $ext = strtolower((string) $file->getClientOriginalExtension()) ?: 'png';
        $filename = 'provider_'.($providerId ?? time()).'_'.time().'.'.$ext;
        $path = $file->storeAs('iptsp-logos', $filename, 'public');

        return 'storage/'.$path;
    }
}
