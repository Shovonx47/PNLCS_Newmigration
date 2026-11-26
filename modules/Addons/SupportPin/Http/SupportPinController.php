<?php

namespace Modules\Addons\SupportPin\Http;

use App\Http\Controllers\Concerns\ResolvesClient;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Addons\SupportPin\Models\SupportPinLog;
use Modules\Addons\SupportPin\Services\SupportPinService;

class SupportPinController extends Controller
{
    use ResolvesClient;

    public function regenerate(SupportPinService $service)
    {
        $clientId = $this->getClientId();
        $service->getOrGenerate($clientId, forceNew: true);

        return back()->with('success', 'A new 24-hour Support PIN has been generated.');
    }

    public function adminIndex()
    {
        return view('supportpin::admin.index', [
            'matchedClient' => session('support_pin_client'),
            'logs' => SupportPinLog::with('client')->latest('id')->limit(25)->get(),
        ]);
    }

    public function adminVerify(Request $request, SupportPinService $service)
    {
        $v = $request->validate([
            'pin' => 'required|digits:6',
            'client_id' => 'nullable|integer|min:1',
        ]);

        $adminId = auth('admin')->id();
        $client = $service->verify($v['pin'], ! empty($v['client_id']) ? (int) $v['client_id'] : null, $adminId);

        if (! $client) {
            return back()->with('error', 'No active client matched that Support PIN (or the PIN has expired).');
        }

        return back()
            ->with('success', 'Support PIN verified.')
            ->with('support_pin_client', [
                'id' => $client->id,
                'name' => trim($client->first_name.' '.$client->last_name),
                'email' => $client->email,
                'company' => $client->company_name,
            ]);
    }
}
