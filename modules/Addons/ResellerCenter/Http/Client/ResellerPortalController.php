<?php

namespace Modules\Addons\ResellerCenter\Http\Client;

use App\Http\Controllers\Concerns\ResolvesClient;
use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Modules\Addons\ResellerCenter\Models\Reseller;
use Modules\Addons\ResellerCenter\Models\ResellerGateway;
use Modules\Addons\ResellerCenter\Models\ResellerPayout;
use Modules\Addons\ResellerCenter\Models\ResellerPromo;
use Modules\Addons\ResellerCenter\Services\ResellerService;

class ResellerPortalController extends Controller
{
    use ResolvesClient;

    public function __construct(protected ResellerService $service) {}

    public function index()
    {
        $clientId = $this->getClientId();
        $reseller = Reseller::with(['group', 'endClients.client', 'promos', 'gateways'])
            ->where('client_id', $clientId)
            ->first();

        if (! $reseller || ! $reseller->isActive()) {
            return view('resellercenter::client.not-reseller');
        }

        $metrics = $this->service->resellerMetrics($reseller);
        $endClientIds = $reseller->endClients->pluck('client_id')->all();

        $services = $endClientIds === []
            ? collect()
            : Service::with(['product', 'client'])->whereIn('client_id', $endClientIds)->latest('id')->limit(30)->get();

        $payouts = ResellerPayout::with('client')->where('reseller_id', $reseller->id)->latest('id')->limit(20)->get();

        return view('resellercenter::client.index', compact('reseller', 'metrics', 'services', 'payouts'));
    }

    public function updateBranding(Request $request)
    {
        $reseller = $this->requireActiveReseller();

        $validated = $request->validate([
            'company_name' => 'required|string|max:190',
            'email_address' => 'required|email|max:190',
            'cname' => 'nullable|string|max:190',
            'paypal_email' => 'nullable|email|max:190',
            'logo_url' => 'nullable|url|max:512',
            'email_signature' => 'nullable|string|max:1000',
            'theme_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'smtp_host' => 'nullable|string|max:190',
            'smtp_port' => 'nullable|integer|min:1|max:65535',
            'smtp_username' => 'nullable|string|max:190',
            'smtp_password' => 'nullable|string|max:255',
        ]);

        $branding = is_array($reseller->branding_settings) ? $reseller->branding_settings : [];
        if (! empty($validated['theme_color'])) {
            $branding['theme_color'] = $validated['theme_color'];
        }
        $branding['email_signature'] = trim((string) ($validated['email_signature'] ?? ''));

        $smtp = is_array($reseller->smtp_settings) ? $reseller->smtp_settings : [];
        if ($reseller->group?->allow_own_smtp) {
            $smtp['host'] = trim((string) ($validated['smtp_host'] ?? ''));
            $smtp['port'] = (int) ($validated['smtp_port'] ?? 587);
            $smtp['username'] = trim((string) ($validated['smtp_username'] ?? ''));
            if (filled($validated['smtp_password'] ?? '')) {
                $smtp['password'] = $validated['smtp_password'];
            }
        }

        $reseller->update([
            'company_name' => trim($validated['company_name']),
            'email_address' => trim($validated['email_address']),
            'cname' => trim((string) ($validated['cname'] ?? '')) ?: null,
            'paypal_email' => trim((string) ($validated['paypal_email'] ?? '')) ?: null,
            'logo_url' => trim((string) ($validated['logo_url'] ?? '')) ?: null,
            'branding_settings' => $branding,
            'smtp_settings' => $smtp,
        ]);

        $this->service->log($reseller->id, null, 'branding_updated', 'Reseller updated portal branding', $request->ip());

        return back()->with('success', 'Store branding settings updated.');
    }

    public function storePromo(Request $request)
    {
        $reseller = $this->requireActiveReseller();
        abort_unless((bool) ($reseller->group?->allow_promos), 403);

        $validated = $request->validate([
            'code' => 'required|string|max:64|alpha_dash|unique:reseller_promos,code',
            'type' => 'required|string|in:percent,fixed',
            'value' => 'required|numeric|min:0.01|max:100000',
            'max_uses' => 'nullable|integer|min:0|max:100000',
            'expires_at' => 'nullable|date',
        ]);

        if ($validated['type'] === 'percent' && (float) $validated['value'] > 100) {
            return back()->withErrors(['value' => 'Percentage discount cannot exceed 100%.']);
        }

        ResellerPromo::create([
            'reseller_id' => $reseller->id,
            'code' => strtoupper(trim($validated['code'])),
            'type' => $validated['type'],
            'value' => (float) $validated['value'],
            'max_uses' => (int) ($validated['max_uses'] ?? 0),
            'uses' => 0,
            'expires_at' => ($validated['expires_at'] ?? null) ?: null,
            'active' => true,
        ]);

        $this->service->log($reseller->id, null, 'promo_created', 'Created reseller promo '.$validated['code'], $request->ip());

        return back()->with('success', 'Reseller promo code created.');
    }

    public function destroyPromo(Request $request, ResellerPromo $promo)
    {
        $reseller = $this->requireActiveReseller();
        abort_unless((int) $promo->reseller_id === (int) $reseller->id, 403);

        $code = $promo->code;
        $promo->delete();
        $this->service->log($reseller->id, null, 'promo_deleted', 'Deleted reseller promo '.$code, $request->ip());

        return back()->with('success', 'Promo code deleted.');
    }

    public function saveGateway(Request $request)
    {
        $reseller = $this->requireActiveReseller();

        $validated = $request->validate([
            'gateway' => 'required|string|in:banktransfer,piprapay,paypal',
            'enabled' => 'nullable|boolean',
            'display_name' => 'nullable|string|max:120',
            'instructions' => 'nullable|string|max:2000',
            'base_url' => 'nullable|url|max:255',
            'api_key' => 'nullable|string|max:512',
            'paypal_email' => 'nullable|email|max:190',
        ]);

        $existing = ResellerGateway::where('reseller_id', $reseller->id)
            ->where('gateway', $validated['gateway'])
            ->first();

        $settings = is_array($existing?->settings) ? $existing->settings : [];
        $settings['display_name'] = trim((string) ($validated['display_name'] ?? '')) ?: ucfirst($validated['gateway']);
        if (isset($validated['instructions'])) {
            $settings['instructions'] = trim((string) $validated['instructions']);
        }
        if (isset($validated['base_url'])) {
            $settings['base_url'] = rtrim(trim((string) $validated['base_url']), '/');
        }
        if (filled($validated['api_key'] ?? '')) {
            $settings['api_key'] = trim((string) $validated['api_key']);
        }
        if (isset($validated['paypal_email'])) {
            $settings['paypal_email'] = trim((string) $validated['paypal_email']);
        }

        ResellerGateway::updateOrCreate(
            ['reseller_id' => $reseller->id, 'gateway' => $validated['gateway']],
            [
                'settings' => $settings,
                'enabled' => $request->boolean('enabled'),
            ]
        );

        $this->service->log($reseller->id, null, 'gateway_updated', 'Updated reseller gateway '.$validated['gateway'], $request->ip());

        return back()->with('success', 'Reseller payment gateway saved.');
    }

    public function suspendService(Service $service)
    {
        $reseller = $this->requireActiveReseller();
        $result = $this->service->toggleEndClientServiceStatus($reseller, $service, 'suspend');

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function unsuspendService(Service $service)
    {
        $reseller = $this->requireActiveReseller();
        $result = $this->service->toggleEndClientServiceStatus($reseller, $service, 'unsuspend');

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    private function requireActiveReseller(): Reseller
    {
        $reseller = Reseller::with('group')
            ->where('client_id', $this->getClientId())
            ->where('status', 'active')
            ->first();

        abort_unless($reseller !== null, 403, 'Active reseller account required.');

        return $reseller;
    }
}
