<?php

namespace App\Support;

/**
 * Repairs text that was decoded as Latin-1 or Windows-1252 and then encoded as
 * UTF-8 again — the "Ã¢â‚¬â„¢"/"ðŸŽ¬" shape.
 *
 * The legacy WHMCS tables contain a lot of this: Bengali descriptions and the
 * emoji in WhatsApp templates were stored double-encoded, and copying them
 * verbatim into the new tables shows customers and staff garbage. The repair
 * is lossless and only runs when the string actually looks double-encoded, so
 * ordinary text and correctly stored Bengali pass through untouched.
 */
final class Mojibake
{
    public static function repair(?string $value): string
    {
        $value = (string) $value;

        if ($value === '' || ! preg_match('//u', $value) || ! preg_match('/[\x80-\xFF]/', $value)) {
            return $value;
        }

        // Each character was one byte that was read as ISO-8859-1 or
        // Windows-1252. Both are handled per character, because a single
        // template usually mixes both (Bengali text plus a typographic
        // apostrophe, an emoji plus a curly quote).
        $windows1252 = [
            0x20AC => 0x80, 0x201A => 0x82, 0x0192 => 0x83, 0x201E => 0x84,
            0x2026 => 0x85, 0x2020 => 0x86, 0x2021 => 0x87, 0x02C6 => 0x88,
            0x2030 => 0x89, 0x0160 => 0x8A, 0x2039 => 0x8B, 0x0152 => 0x8C,
            0x017D => 0x8E, 0x2018 => 0x91, 0x2019 => 0x92, 0x201C => 0x93,
            0x201D => 0x94, 0x2022 => 0x95, 0x2013 => 0x96, 0x2014 => 0x97,
            0x02DC => 0x98, 0x2122 => 0x99, 0x0161 => 0x9A, 0x203A => 0x9B,
            0x0153 => 0x9C, 0x017E => 0x9E, 0x0178 => 0x9F,
        ];

        $bytes = '';

        foreach (preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $char) {
            $code = mb_ord($char, 'UTF-8');

            if ($code <= 0xFF) {
                $bytes .= chr($code);
            } elseif (isset($windows1252[$code])) {
                $bytes .= chr($windows1252[$code]);
            } else {
                // Not one of the two single-byte encodings; leave it alone.
                return $value;
            }
        }

        return ($bytes !== '' && preg_match('//u', $bytes)) ? $bytes : $value;
    }
}
