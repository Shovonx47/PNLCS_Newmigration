<?php

namespace Modules\Addons\ForceClientActive\Http\Admin;

use App\Http\Controllers\Controller;
use App\Models\AddonSetting;
use Illuminate\Http\Request;
use Modules\Addons\ForceClientActive\ForceClientActiveModule;
use Modules\Addons\ForceClientActive\Models\ForcedActiveClient;
use Modules\Addons\ForceClientActive\Models\ForcedActiveLog;
use Modules\Addons\ForceClientActive\Services\ForceClientActiveService;

class ForceClientActiveAdminController extends Controller
{
    public function index(ForceClientActiveService $service)
    {
        return view('forceclientactive::admin.index', [
            'enforceAll' => $service->enforceAllEnabled(),
            'restoreInactive' => $service->restoreInactiveEnabled(),
            'flagged' => ForcedActiveClient::with('client')->orderByDesc('id')->get(),
            'logs' => ForcedActiveLog::with('client')->latest('id')->limit(25)->get(),
        ]);
    }

    public function store(Request $request, ForceClientActiveService $service)
    {
        $v = $request->validate([
            'client_id' => 'required|integer|exists:clients,id',
            'note' => 'nullable|string|max:255',
        ]);

        ForcedActiveClient::updateOrCreate(
            ['client_id' => (int) $v['client_id']],
            [
                'added_by_admin_id' => auth('admin')->id(),
                'note' => $v['note'] ?? null,
            ]
        );

        $service->run();

        return back()->with('success', 'Client added to protected active list.');
    }

    public function updateMode(Request $request, ForceClientActiveService $service)
    {
        AddonSetting::setSetting(ForceClientActiveModule::NAME, 'restore_inactive_clients', $request->boolean('restore_inactive_clients') ? '1' : '0');
        AddonSetting::setSetting(ForceClientActiveModule::NAME, 'enforce_all_clients', $request->boolean('enforce_all_clients') ? '1' : '0');
        $restored = $service->run();

        return back()->with('success', "Settings saved ({$restored} account(s) restored).");
    }

    public function destroy(ForcedActiveClient $entry)
    {
        $entry->delete();

        return back()->with('success', 'Client removed from protected active list.');
    }
}
