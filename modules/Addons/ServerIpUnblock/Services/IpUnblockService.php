<?php

namespace Modules\Addons\ServerIpUnblock\Services;

use App\Models\Service;
use App\Services\ProvisioningService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Addons\ServerIpUnblock\Models\IpUnblockLog;

class IpUnblockService
{
    public function __construct(private ProvisioningService $provisioning) {}

    /**
     * Active services owned by the client whose server module exposes `unblockIp()`.
     *
     * @return Collection<int, Service>
     */
    public function capableServicesForClient(int $clientId): Collection
    {
        return Service::with(['product', 'server'])
            ->where('client_id', $clientId)
            ->where('status', 'active')
            ->get()
            ->filter(function (Service $service) {
                $module = $this->provisioning->resolveModule($service);

                return $module !== null && method_exists($module, 'unblockIp');
            })
            ->values();
    }

    /**
     * Active services across all clients whose server module exposes `unblockIp()`, for staff direct action.
     *
     * @return Collection<int, Service>
     */
    public function capableServicesForAdmin(): Collection
    {
        return Service::with(['client', 'product', 'server'])
            ->where('status', 'active')
            ->whereNotNull('server_id')
            ->latest('id')
            ->limit(250)
            ->get()
            ->filter(function (Service $service) {
                $module = $this->provisioning->resolveModule($service);

                return $module !== null && method_exists($module, 'unblockIp');
            })
            ->values();
    }

    /**
     * Request IP unblock on a service and record an audit log.
     *
     * @return array{success: bool, message: string}
     */
    public function unblock(Service $service, string $ipAddress, ?string $adminActor = null): array
    {
        $ipAddress = trim($ipAddress);
        if (! filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            return ['success' => false, 'message' => 'Please enter a valid IPv4 or IPv6 address.'];
        }

        $module = $this->provisioning->resolveModule($service);
        if (! $module || ! method_exists($module, 'unblockIp')) {
            return ['success' => false, 'message' => 'Self-service IP unblocking is not available for this service.'];
        }

        try {
            $res = (array) $module->unblockIp($service, $ipAddress);
            $ok = (bool) ($res['success'] ?? false);
            $msg = $ok
                ? "Firewall check completed for {$ipAddress}. You may reconnect your devices now."
                : 'Unable to clear the IP address right now. Please verify the IP or contact support.';
        } catch (\Throwable $e) {
            $ok = false;
            $msg = 'Unable to reach the service firewall right now. Please try again shortly.';
        }

        $auditMsg = $adminActor ? "[Staff: {$adminActor}] {$msg}" : $msg;

        IpUnblockLog::create([
            'client_id' => $service->client_id,
            'service_id' => $service->id,
            'ip_address' => $ipAddress,
            'success' => $ok,
            'message' => Str::limit($auditMsg, 240, ''),
        ]);

        return ['success' => $ok, 'message' => $msg];
    }
}
