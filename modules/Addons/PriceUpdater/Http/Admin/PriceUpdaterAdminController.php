<?php

namespace Modules\Addons\PriceUpdater\Http\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\ProductGroup;
use Illuminate\Http\Request;
use Modules\Addons\PriceUpdater\Models\PriceUpdaterLog;
use Modules\Addons\PriceUpdater\Services\PriceUpdaterService;

class PriceUpdaterAdminController extends Controller
{
    public function index()
    {
        return view('priceupdater::admin.index', [
            'groups' => ProductGroup::orderBy('name')->get(['id', 'name']),
            'currencies' => Currency::orderByDesc('is_default')->get(['id', 'code']),
            'logs' => PriceUpdaterLog::latest('id')->limit(20)->get(),
            'previewResult' => session('price_preview'),
        ]);
    }

    public function handleProduct(Request $request, PriceUpdaterService $service)
    {
        $v = $request->validate([
            'mode' => 'required|in:percent,fixed',
            'delta' => 'required|numeric|min:-999999|max:999999',
            'group_id' => 'nullable|integer|exists:product_groups,id',
            'currency_id' => 'nullable|integer|exists:currencies,id',
            'cycles' => 'nullable|array',
            'cycles.*' => 'in:monthly,quarterly,semiannually,annually,biennially,triennially',
            'action_mode' => 'required|in:preview,apply',
        ]);

        $v['cascade_services'] = $request->boolean('cascade_services');
        $apply = $v['action_mode'] === 'apply';
        $admin = auth('admin')->user();

        $res = $service->runProductUpdate($v, $apply, $admin?->id, $admin?->username ?: 'admin');

        if (! $apply) {
            return back()->withInput()->with('price_preview', [
                'kind' => 'Products',
                'rows' => $res['preview'],
                'summary' => "{$res['affected_products']} product pricing row(s) and {$res['affected_services']} active service(s) would change.",
            ]);
        }

        return redirect()->route('admin.extensions.price-updater.index')
            ->with('success', "Updated {$res['affected_products']} product pricing row(s) and {$res['affected_services']} service(s).");
    }

    public function handleDomain(Request $request, PriceUpdaterService $service)
    {
        $v = $request->validate([
            'mode' => 'required|in:percent,fixed',
            'delta' => 'required|numeric|min:-999999|max:999999',
            'extension' => 'nullable|string|max:32',
            'fields' => 'nullable|array',
            'fields.*' => 'in:register_price,renew_price,transfer_price',
            'action_mode' => 'required|in:preview,apply',
        ]);

        $v['cascade_domains'] = $request->boolean('cascade_domains');
        $apply = $v['action_mode'] === 'apply';
        $admin = auth('admin')->user();

        $res = $service->runDomainUpdate($v, $apply, $admin?->id, $admin?->username ?: 'admin');

        if (! $apply) {
            return back()->withInput()->with('price_preview', [
                'kind' => 'Domain TLDs',
                'rows' => $res['preview'],
                'summary' => "{$res['affected_tlds']} TLD row(s) and {$res['affected_domains']} active domain(s) would change.",
            ]);
        }

        return redirect()->route('admin.extensions.price-updater.index')
            ->with('success', "Updated pricing on {$res['affected_tlds']} domain extension(s) and {$res['affected_domains']} active domain(s).");
    }
}
