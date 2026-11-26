<?php

namespace App\Support;

use App\Models\Invoice;

/**
 * A payment link that needs no login.
 *
 * The legacy installation did this with a hand-made HMAC in includes/hooks and
 * a pay.php that minted an SSO token for the invoice's owner, then wrote a
 * signed cookie the client area checked by file name. Two things were wrong
 * with it beyond the plumbing: the signature was computed from WHMCS's
 * configuration hash (which doubles as the encryption key for stored gateway
 * credentials), and the "locked" session was enforced by a hook that could be
 * skipped by any script the hook did not name.
 *
 * Here the signature is an HMAC over the invoice id and its owner, keyed with
 * the application key. A link therefore works for exactly one invoice of
 * exactly one customer, and editing either number in the address breaks it.
 * The session lock is a route middleware (LockToInvoice) with an allow-list,
 * so a page it does not name is refused rather than allowed by default.
 *
 * Links carry no expiry of their own: an invoice email that sits unread for a
 * month should still be payable. What does expire is the temporary session the
 * link opens, which lasts two hours and is ended by any other page.
 */
final class DirectPayLink
{
    /** The invoice a direct-pay session may look at. */
    public const SESSION_INVOICE = 'direct_pay.invoice';

    /** When that session stops being valid (a timestamp). */
    public const SESSION_UNTIL = 'direct_pay.until';

    public const TTL_HOURS = 2;

    private const SIGNATURE_LENGTH = 32;

    public static function url(Invoice $invoice, ?string $baseUrl = null): string
    {
        $base = rtrim($baseUrl ?? url('/'), '/');

        return $base.'/pay/'.$invoice->id.'?sig='.self::signature($invoice);
    }

    public static function signature(Invoice $invoice): string
    {
        return substr(hash_hmac('sha256', self::payload($invoice), self::secret()), 0, self::SIGNATURE_LENGTH);
    }

    /**
     * Constant-time check, and only for the invoice the signature was made for.
     */
    public static function verify(Invoice $invoice, string $signature): bool
    {
        $signature = trim($signature);

        if ($signature === '' || ! ctype_xdigit($signature)) {
            return false;
        }

        return hash_equals(self::signature($invoice), $signature);
    }

    /** Open the two-hour, invoice-only session for this invoice. */
    public static function lockSession(Invoice $invoice): void
    {
        session([
            self::SESSION_INVOICE => (int) $invoice->id,
            self::SESSION_UNTIL => now()->addHours(self::TTL_HOURS),
        ]);
    }

    /**
     * The invoice a locked session may look at, or 0 when it is not locked.
     *
     * An expired lock is dropped here, so a tab left open overnight is a
     * normal signed-out visit rather than a still-open door.
     */
    public static function lockedInvoiceId(): int
    {
        $id = (int) session(self::SESSION_INVOICE, 0);

        if ($id <= 0) {
            return 0;
        }

        $until = session(self::SESSION_UNTIL);

        if (! $until || now()->greaterThan($until)) {
            self::release();

            return 0;
        }

        return $id;
    }

    public static function release(): void
    {
        session()->forget([self::SESSION_INVOICE, self::SESSION_UNTIL]);
    }

    /**
     * The key the signature is made with.
     *
     * app.key is the application's own secret. Unlike WHMCS's
     * $cc_encryption_hash it is not also the key that decrypts stored gateway
     * passwords, so a leaked payment link cannot be turned into a way to
     * recover them.
     */
    private static function secret(): string
    {
        return (string) config('app.key');
    }

    private static function payload(Invoice $invoice): string
    {
        return 'pay_inv:'.$invoice->id.':'.$invoice->client_id;
    }
}
