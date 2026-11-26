<?php

namespace Modules\Addons\ItalkRecharge\Http\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Addons\ItalkRecharge\Models\ItalkRechargeAccount;
use Modules\Addons\ItalkRecharge\Models\ItalkRechargeLog;
use Modules\Addons\ItalkRecharge\Models\ItalkRechargeProvider;
use Modules\Addons\ItalkRecharge\Services\RechargeService;

/**
 * Extensions -> IPTSP Recharge.
 *
 * Providers, PIN accounts, a manual top-up and the audit log on one screen.
 * Stored credentials are never rendered back: the form shows whether a
 * password/key is set, and an empty field on save keeps the stored value.
 */
class RechargeAdminController extends Controller
{
    public function __construct(private readonly RechargeService $recharges) {}

    public function index()
    {
        return view('italkrecharge::admin.index', [
            'providers' => ItalkRechargeProvider::orderBy('sort_order')->orderBy('name')->get(),
            'accounts' => ItalkRechargeAccount::with('client', 'provider')->orderByDesc('id')->limit(200)->get(),
            'logs' => ItalkRechargeLog::with('client', 'provider')->orderByDesc('id')->limit(100)->get(),
            'clients' => Client::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'company_name', 'email']),
            'apiTypes' => ItalkRechargeProvider::API_TYPES,
            'maxAmount' => RechargeService::MAX_AMOUNT,
        ]);
    }

    public function storeProvider(Request $request): RedirectResponse
    {
        $validated = $this->validateProvider($request);
        ItalkRechargeProvider::create($validated);

        return back()->with('success', 'Recharge provider added.');
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

        $provider->update($validated);

        return back()->with('success', 'Recharge provider updated.');
    }

    public function destroyProvider(ItalkRechargeProvider $provider): RedirectResponse
    {
        ItalkRechargeAccount::where('provider_id', $provider->id)->update(['status' => 'inactive']);
        $provider->delete();

        return back()->with('success', 'Provider removed. Its client PIN accounts were disabled and the log is kept.');
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
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $validated['base_path'] = $validated['base_path'] ?: '/iconiptsp/';
        $validated['integration_type'] = $validated['api_type'] === 'italk' ? 'scraper' : 'rest';

        return $validated;
    }
}
