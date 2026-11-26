<?php

namespace Modules\Addons\RechargeRequests\Http\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\Addons\RechargeRequests\Models\RechargeRequest;

/**
 * Extensions -> Recharge Requests: what customers asked for, which invoice it
 * raised, and whether it has been topped up.
 *
 * The legacy screen only listed requests whose invoice was already Paid, so a
 * request waiting on an unpaid invoice was invisible and looked like it had
 * been lost.
 */
class RechargeRequestAdminController extends Controller
{
    public function index(Request $request)
    {
        $status = in_array($request->query('status'), RechargeRequest::STATUSES, true)
            ? (string) $request->query('status')
            : 'Pending';

        $requests = RechargeRequest::with(['client', 'invoice'])
            ->where('status', $status)
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        $counts = DB::table('recharge_requests')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('rechargerequests::admin.index', [
            'requests' => $requests,
            'status' => $status,
            'counts' => $counts,
            'statuses' => RechargeRequest::STATUSES,
        ]);
    }

    public function update(Request $request, RechargeRequest $rechargeRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['Completed', 'Cancelled'])],
        ]);

        $rechargeRequest->update(['status' => $validated['status']]);

        return back()->with('success', "Request #{$rechargeRequest->id} marked {$validated['status']}.");
    }
}
