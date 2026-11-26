<?php

namespace App\Support;

/**
 * Bangladeshi phone numbers, in the shapes the three gateways want them.
 *
 * The legacy addons each wrote their own version of this and disagreed: one
 * produced 8801..., another 88001..., and the WhatsApp gateway was sent
 * formatted numbers with the spaces still in. One place, one answer.
 */
final class BdPhone
{
    /**
     * Digits only, with the country code: 8801711223344.
     *
     * Accepts 01711223344, 8801711223344, +880 1711-223344 and the
     * 88001711223344 shape the SMS form used to produce.
     */
    public static function digits(?string $number, ?string $prefix = ''): string
    {
        $raw = preg_replace('/\D+/', '', (string) $prefix.(string) $number) ?? '';

        if ($raw === '') {
            return '';
        }

        // 8800 17... — one zero too many, from a prefix that already had 88.
        if (str_starts_with($raw, '8800')) {
            $raw = '880'.substr($raw, 4);
        }

        if (str_starts_with($raw, '880')) {
            $rest = substr($raw, 3);

            // 880 017... — country code plus the local leading zero.
            if (str_starts_with($rest, '0')) {
                $rest = substr($rest, 1);
            }

            return strlen($rest) === 10 ? '880'.$rest : '';
        }

        if (str_starts_with($raw, '0')) {
            $rest = substr($raw, 1);

            return strlen($rest) === 10 ? '880'.$rest : '';
        }

        // A bare 1XXXXXXXXX.
        return strlen($raw) === 10 ? '880'.$raw : '';
    }

    /** E.164, +8801711223344, or '' when the number is not Bangladeshi. */
    public static function e164(?string $number, ?string $prefix = ''): string
    {
        $digits = self::digits($number, $prefix);

        return $digits === '' ? '' : '+'.$digits;
    }

    /** The 8801... shape the SMS and WhatsApp gateways expect. */
    public static function gateway(?string $number, ?string $prefix = ''): string
    {
        return self::digits($number, $prefix);
    }

    /** 01711223344, what a customer recognises on a page. */
    public static function local(?string $number, ?string $prefix = ''): string
    {
        $digits = self::digits($number, $prefix);

        return $digits === '' ? '' : '0'.substr($digits, 3);
    }

    public static function looksValid(?string $number, ?string $prefix = ''): bool
    {
        return self::digits($number, $prefix) !== '';
    }
}
