<?php

namespace Modules\Addons\ItalkBalance\Http\Admin;

use App\Http\Controllers\Controller;
use App\Models\AddonSetting;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Addons\ItalkBalance\Models\ItalkBalanceEndpoint;
use Modules\Addons\ItalkBalance\Models\ItalkBalanceService;
use Modules\Addons\ItalkBalance\Services\BalanceRegistry;

/**
 * Extensions -> iTalk / ICC Balance.
 *
 * Three jobs on one screen, in the order an operator needs them: which
 * endpoints exist, which products are IPTSP products, and which service holds
 * which IP number. The legacy addon spread this over a URL manager, a product
 * picker and a per-service edit form; here it is one page with the mapping
 * list directly under the form.
 */
class BalanceAdminController extends Controller
{
    public function __construct(private readonly BalanceRegistry $registry) {}

    public function index()
    {
        $mappings = ItalkBalanceService::with(['service.product', 'client', 'endpoint'])
            ->orderByDesc('id')
            ->get();

        return view('italkbalance::admin.index', [
            'endpoints' => ItalkBalanceEndpoint::orderBy('program')->orderBy('label')->get(),
            'mappings' => $mappings,
            'products' => Product::orderBy('name')->get(['id', 'name', 'group_id']),
            'services' => Service::with('client', 'product')->orderBy('id')->get(),
            'selectedItalk' => $this->registry->selectedProducts('italk'),
            'selectedIcc' => $this->registry->selectedProducts('icc'),
            'cooldown' => $this->registry->cooldown(),
            'programs' => ItalkBalanceEndpoint::PROGRAMS,
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'products_italk' => ['array'],
            'products_italk.*' => ['integer', Rule::exists('products', 'id')],
            'products_icc' => ['array'],
            'products_icc.*' => ['integer', Rule::exists('products', 'id')],
            'refresh_cooldown' => ['nullable', 'integer', 'min:0', 'max:3600'],
        ]);

        $this->registry->storeSelectedProducts('italk', $validated['products_italk'] ?? []);
        $this->registry->storeSelectedProducts('icc', $validated['products_icc'] ?? []);

        AddonSetting::setSetting(
            BalanceRegistry::ADDON,
            'refresh_cooldown',
            (string) ($validated['refresh_cooldown'] ?? BalanceRegistry::DEFAULT_COOLDOWN)
        );

        return back()->with('success', 'Balance settings saved.');
    }

    public function storeEndpoint(Request $request): RedirectResponse
    {
        $validated = $this->validateEndpoint($request);
        ItalkBalanceEndpoint::create($validated);

        return back()->with('success', 'Balance endpoint added.');
    }

    public function updateEndpoint(Request $request, ItalkBalanceEndpoint $endpoint): RedirectResponse
    {
        $validated = $this->validateEndpoint($request);
        $endpoint->update($validated);

        return back()->with('success', 'Balance endpoint updated.');
    }

    public function destroyEndpoint(ItalkBalanceEndpoint $endpoint): RedirectResponse
    {
        // Mappings keep their PIN but lose the endpoint: they stop showing a
        // balance instead of pointing at a URL that no longer exists.
        ItalkBalanceService::where('endpoint_id', $endpoint->id)->update(['endpoint_id' => null]);
        $endpoint->delete();

        return back()->with('success', 'Balance endpoint removed. Its mappings now show no balance until reassigned.');
    }

    public function storeMapping(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'service_id' => ['required', 'integer', Rule::exists('services', 'id')],
            'ip_number' => ['required', 'string', 'max:64', 'regex:/^[0-9A-Za-z._+\-]{3,64}$/'],
            'endpoint_id' => ['required', 'integer', Rule::exists('italk_balance_endpoints', 'id')],
        ]);

        $service = Service::findOrFail($validated['service_id']);
        $endpoint = ItalkBalanceEndpoint::findOrFail($validated['endpoint_id']);

        ItalkBalanceService::updateOrCreate(
            ['service_id' => $service->id],
            [
                'client_id' => $service->client_id,
                'product_id' => $service->product_id,
                'ip_number' => trim($validated['ip_number']),
                'endpoint_id' => $endpoint->id,
            ]
        );

        return back()->with('success', "IP number mapped to service #{$service->id} ({$endpoint->label}).");
    }

    public function destroyMapping(ItalkBalanceService $mapping): RedirectResponse
    {
        $mapping->delete();

        return back()->with('success', 'IP number mapping removed.');
    }

    public function refreshMapping(ItalkBalanceService $mapping): RedirectResponse
    {
        $mapping->load('endpoint');
        $result = $this->registry->refresh($mapping, true);

        return back()->with(
            $result['ok'] ? 'success' : 'error',
            $result['ok']
                ? 'Balance refreshed: ৳'.number_format((float) $result['balance'], 2)
                : $result['message']
        );
    }

    /** @return array<string, mixed> */
    private function validateEndpoint(Request $request): array
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:190'],
            'base_url' => ['required', 'string', 'max:500', 'url:https'],
            'program' => ['required', Rule::in(array_keys(ItalkBalanceEndpoint::PROGRAMS))],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
