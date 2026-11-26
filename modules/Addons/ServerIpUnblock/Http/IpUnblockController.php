<?php

namespace Modules\Addons\ServerIpUnblock\Http;

use App\Http\Controllers\Concerns\ResolvesClient;
use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Modules\Addons\ServerIpUnblock\Models\IpUnblockLog;
use Modules\Addons\ServerIpUnblock\Services\IpUnblockService;

class IpUnblockController extends Controller
{
    use ResolvesClient;

    public function clientIndex(Request $request, IpUnblockService $service)
    {
        $clientId = $this->getClientId();
        $services = $service->capableServicesForClient($clientId);
        $recentLogs = IpUnblockLog::with('service.product')
            ->where('client_id', $clientId)
            ->latest('id')
            ->limit(10)
            ->get();

        return view('unblockip::client.index', [
            'services' => $services,
            'visitorIp' => (string) $request->ip(),
            'recentLogs' => $recentLogs,
        ]);
    }

    public function clientSubmit(Request $request, IpUnblockService $service)
    {
        $clientId = $this->getClientId();
        $validated = $request->validate([
            'service_id' => 'required|integer|exists:services,id',
            'ip_address' => 'required|ip',
        ]);

        $svc = Service::findOrFail((int) $validated['service_id']);
        abort_unless((int) $svc->client_id === (int) $clientId, 403);

        if (strtolower((string) $svc->status) !== 'active') {
            return back()->with('error', 'Only active services support IP unblocking.');
        }

        $res = $service->unblock($svc, $validated['ip_address']);

        return back()->with($res['success'] ? 'success' : 'error', $res['message']);
    }

    public function adminIndex(IpUnblockService $service)
    {
        return view('unblockip::admin.index', [
            'capableServices' => $service->capableServicesForAdmin(),
            'logs' => IpUnblockLog::with(['client', 'service.product'])->latest('id')->paginate(30),
        ]);
    }

    public function adminUnblock(Request $request, IpUnblockService $service)
    {
        $validated = $request->validate([
            'service_id' => 'required|integer|exists:services,id',
            'ip_address' => 'required|ip',
        ]);

        $svc = Service::findOrFail((int) $validated['service_id']);
        $actor = auth('admin')->user()?->username ?: 'admin';
        $res = $service->unblock($svc, $validated['ip_address'], $actor);

        return back()->with($res['success'] ? 'success' : 'error', $res['message']);
    }
}
