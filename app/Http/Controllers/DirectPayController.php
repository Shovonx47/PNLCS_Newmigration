<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Support\DirectPayLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * GET /pay/{invoice}?sig=...
 *
 * The link in an invoice email. It proves the holder was sent the invoice -
 * the signature covers the invoice and its owner - and opens a session that is
 * good for that invoice alone.
 *
 * A draft has not been issued, so its link is not honoured; a cancelled
 * invoice is shown (the customer may be looking for why), and the page itself
 * decides what can be paid.
 */
class DirectPayController extends Controller
{
    public function __invoke(Request $request, Invoice $invoice)
    {
        abort_unless(
            DirectPayLink::verify($invoice, (string) $request->query('sig', '')),
            403,
            __('client.direct_pay.invalid')
        );

        abort_if($invoice->status === InvoiceStatus::Draft->value, 404);

        $client = $invoice->client()->with('users')->first();
        abort_if($client === null, 404);

        $user = $client->users()->first();
        abort_if($user === null, 403, __('client.direct_pay.no_login'));

        // A different person signed in on this browser must not be left with
        // the old session's data once the link signs the invoice's owner in.
        if (Auth::id() !== $user->id) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            Auth::login($user);
        }

        // regenerate() keeps the data but issues a new id, so a link clicked
        // on a shared computer does not hand over a session id that was in the
        // address bar or a proxy log before the customer arrived.
        $request->session()->regenerate();

        session(['active_client_id' => $client->id]);
        DirectPayLink::lockSession($invoice);

        return redirect()->route('client.invoices.show', $invoice);
    }
}
