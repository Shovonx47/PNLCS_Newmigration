<?php

namespace App\Http\Middleware;

use App\Models\Invoice;
use App\Support\DirectPayLink;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps a session that arrived through a signed payment link inside that one
 * invoice.
 *
 * The link deliberately signs somebody in without a password, so the session
 * it opens must be worth no more than the link: one invoice, its PDF, and the
 * ways to pay it. Anything else - the dashboard, services, tickets, another
 * invoice - ends the session and sends the visitor to the login page, exactly
 * as the legacy install tried to do from a ClientAreaPage hook.
 *
 * A middleware rather than a hook because it is an allow-list: a page added to
 * the client area later is refused without anybody having to remember to add
 * it to a list of forbidden ones.
 *
 * Second factor stays in force. A customer who has switched on 2FA is asked for
 * the code before the invoice opens; the routes that ask for it are on the
 * allow-list below, or the guard would block the very page that unlocks it.
 */
class LockToInvoice
{
    /**
     * Client-area routes a payment session may use. Each one is either about
     * the locked invoice (checked separately) or is how the visitor leaves.
     */
    private const ALLOWED = [
        'client.invoices.show',
        'client.invoices.pdf',
        'client.invoices.payment-notification',
        'client.invoices.pay-with-credit',
        'client.invoices.pay-with-card',
        'client.2fa.verify',
        'client.2fa.confirm',
        'client.logout',
        // Opening a second signed link replaces the lock rather than ending
        // the visit; the new link's own signature decides which invoice.
        'pay.invoice',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $invoiceId = DirectPayLink::lockedInvoiceId();

        if ($invoiceId <= 0) {
            return $next($request);
        }

        $route = $request->route();
        $name = (string) ($route?->getName() ?? '');

        // Payment gateways hand the customer to their own pages and back; the
        // callback is the proof the invoice was paid, so it must be reachable.
        if (str_starts_with($name, 'gateway.')) {
            return $next($request);
        }

        if (! in_array($name, self::ALLOWED, true)) {
            return $this->end($request);
        }

        // An invoice route must be about the locked invoice and no other.
        $parameter = $route?->parameter('invoice');
        $target = $parameter instanceof Invoice ? (int) $parameter->id : (int) $parameter;

        if ($target !== 0 && $target !== $invoiceId) {
            return $this->end($request);
        }

        return $next($request);
    }

    /**
     * The visitor went somewhere a payment link does not reach: the temporary
     * session is torn down and they are asked to sign in properly.
     */
    private function end(Request $request): Response
    {
        DirectPayLink::release();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('client.login')
            ->withErrors(['email' => __('client.direct_pay.ended')]);
    }
}
