<?php

namespace Modules\Servers\Bdpbx;

use App\Http\Controllers\Concerns\ResolvesClient;
use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\Service;
use App\Services\Module\ModuleRegistry;
use Illuminate\Http\Request;

class BdpbxController extends Controller
{
    use ResolvesClient;

    public function __construct(private readonly ModuleRegistry $registry) {}

    public function updateExtensionPassword(Request $request, Service $service)
    {
        abort_if($service->client_id !== $this->getClientId(), 403);
        abort_unless($service->isLive(), 403);

        $validated = $request->validate([
            'extension' => ['required', 'string', 'regex:/^[0-9]{2,8}$/'],
            'new_password' => ['required', 'string', 'min:6', 'max:64'],
        ]);

        $module = $this->module();
        $result = $module->updateExtensionPassword($service, $validated['extension'], $validated['new_password'], false);

        return redirect()->route('client.services.show', $service)
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function unblockIp(Request $request, Service $service)
    {
        abort_if($service->client_id !== $this->getClientId(), 403);
        abort_unless($service->isLive(), 403);

        $validated = $request->validate([
            'ip_address' => ['required', 'ip'],
        ]);

        $module = $this->module();
        $result = $module->unblockIp($service, $validated['ip_address']);

        return redirect()->route('client.services.show', $service)
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function adminUpdateExtensionPasswords(Request $request, Service $service)
    {
        $passwords = (array) $request->input('extensions', []);
        $module = $this->module();
        $updated = 0;
        $errors = [];

        foreach ($passwords as $ext => $newPass) {
            $newPass = trim((string) $newPass);
            if ($newPass === '') {
                continue;
            }
            $res = $module->updateExtensionPassword($service, (string) $ext, $newPass, true);
            if ($res['success']) {
                $updated++;
            } else {
                $errors[] = "Ext {$ext}: ".$res['message'];
            }
        }

        if ($errors !== []) {
            return back()->with('error', implode(' | ', $errors));
        }

        return back()->with('success', $updated > 0 ? "Updated {$updated} extension password(s)." : 'No password changes submitted.');
    }

    public function adminUnblockIp(Request $request, Service $service)
    {
        $validated = $request->validate(['ip_address' => ['required', 'ip']]);
        $res = $this->module()->unblockIp($service, $validated['ip_address']);

        return back()->with($res['success'] ? 'success' : 'error', $res['message']);
    }

    public function adminCustomerSso(Service $service)
    {
        $res = $this->module()->ssoLogin($service);
        if (($res['success'] ?? false) && ! empty($res['data']['url'])) {
            return redirect()->away((string) $res['data']['url']);
        }

        return back()->with('error', $res['message'] ?? 'Failed to open tenant SSO.');
    }

    public function adminSuperSso(Server $server)
    {
        $res = $this->module()->superAdminSso($server);
        if (($res['success'] ?? false) && ! empty($res['data']['url'])) {
            return redirect()->away((string) $res['data']['url']);
        }

        return back()->with('error', $res['message'] ?? 'Failed to open SuperAdmin SSO.');
    }

    private function module(): BdpbxModule
    {
        /** @var BdpbxModule $module */
        $module = $this->registry->getServerModule(BdpbxModule::NAME) ?? app(BdpbxModule::class);

        return $module;
    }
}
