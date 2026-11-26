<?php

namespace Modules\Addons\RechargeRequests\Http\Client;

use App\Http\Controllers\Concerns\ResolvesClient;
use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Addons\RechargeRequests\Models\RechargeRequest;
use Modules\Addons\RechargeRequests\Services\RechargeRequestService;

/**
 * The client-side recharge page.
 *
 * The legacy addon put the form in the sidebar and created the invoice from a
 * GET link (`index.php?fund_invoice=1&amount=…&mobile=…`), which meant any page
 * load could raise an invoice. This is a page of its own with a POST form, and
 * the request that creates the invoice is CSRF-protected.
 */
class RechargeRequestController extends Controller
{
    use ResolvesClient;

    public function show(): View
    {
        $clientId = $this->getClientId();

        $requests = RechargeRequest::with('invoice')
            ->where('client_id', $clientId)
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return view('rechargerequests::client.form', [
            'requests' => $requests,
            'minAmount' => RechargeRequestService::MIN_AMOUNT,
            'maxAmount' => RechargeRequestService::MAX_AMOUNT,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mobile_number' => ['required', 'string', 'max:64', 'regex:/^[0-9A-Za-z._+\-]{3,64}$/'],
            'amount' => ['required', 'numeric', 'min:'.RechargeRequestService::MIN_AMOUNT, 'max:'.RechargeRequestService::MAX_AMOUNT],
        ], [
            'mobile_number.regex' => 'Enter the IP phone number exactly as it appears on your account.',
        ]);

        $client = Client::findOrFail($this->getClientId());

        $result = app(RechargeRequestService::class)->request(
            $client,
            (string) $validated['mobile_number'],
            (float) $validated['amount']
        );

        return redirect()->route('client.invoices.show', $result['invoice'])
            ->with('success', 'Recharge invoice created. Your balance is topped up once it is paid.');
    }
}
