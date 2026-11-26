<?php

namespace Modules\Addons\CancelInvoices\Http\Admin;

use App\Http\Controllers\Controller;
use App\Models\AddonSetting;
use Illuminate\Http\Request;
use Modules\Addons\CancelInvoices\CancelInvoicesModule;
use Modules\Addons\CancelInvoices\Models\CancelInvoiceExclusion;
use Modules\Addons\CancelInvoices\Services\CancelInvoicesService;

class CancelInvoicesAdminController extends Controller
{
    public function index(CancelInvoicesService $service)
    {
        return view('cancelinvoices::admin.index', [
            'settings' => $service->settings(),
            'exclusions' => CancelInvoiceExclusion::orderBy('type')->orderBy('rel_id')->get(),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $v = $request->validate([
            'days' => 'required|integer|min:1|max:365',
            'all_invoice_days' => 'required|integer|min:1|max:365',
            'add_funds_days' => 'required|integer|min:1|max:365',
        ]);

        $addon = CancelInvoicesModule::NAME;
        AddonSetting::setSetting($addon, 'enable_days', $request->boolean('enable_days') ? '1' : '0');
        AddonSetting::setSetting($addon, 'days', (string) $v['days']);
        AddonSetting::setSetting($addon, 'enable_all_invoices', $request->boolean('enable_all_invoices') ? '1' : '0');
        AddonSetting::setSetting($addon, 'all_invoice_days', (string) $v['all_invoice_days']);
        AddonSetting::setSetting($addon, 'enable_add_funds', $request->boolean('enable_add_funds') ? '1' : '0');
        AddonSetting::setSetting($addon, 'add_funds_days', (string) $v['add_funds_days']);

        return back()->with('success', 'Auto-cancel invoice settings saved.');
    }

    public function storeExclusion(Request $request)
    {
        $v = $request->validate([
            'type' => 'required|in:client,product,service',
            'rel_id' => 'required|integer|min:1',
            'note' => 'nullable|string|max:255',
        ]);

        CancelInvoiceExclusion::updateOrCreate(
            ['type' => $v['type'], 'rel_id' => (int) $v['rel_id']],
            ['note' => $v['note'] ?? null]
        );

        return back()->with('success', 'Exclusion saved.');
    }

    public function destroyExclusion(CancelInvoiceExclusion $exclusion)
    {
        $exclusion->delete();

        return back()->with('success', 'Exclusion removed.');
    }

    public function runNow(CancelInvoicesService $service)
    {
        $res = $service->run(false);

        return back()->with(
            'success',
            "Sweep completed: {$res['cancelled_regular']} regular and {$res['cancelled_add_funds']} Add-Funds invoice(s) cancelled ({$res['skipped_excluded']} skipped by exclusions)."
        );
    }
}
