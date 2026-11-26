<?php

use App\Support\Mojibake;

test('it repairs text that was decoded as latin-1 and encoded again', function () {
    // "সার্ভিস" stored the way the legacy WHMCS tables hold it.
    $broken = 'à¦¸à¦¾à¦°à§à¦­à¦¿à¦¸';

    expect(Mojibake::repair($broken))->toBe('সার্ভিস');
});

test('it repairs emoji that were decoded as windows-1252', function () {
    // "⏰ *Reminder*" as WHMCS stored it: UTF-8 bytes for the emoji read as
    // ISO-8859-1 (â + U+008F + °) and then written back as UTF-8.
    $broken = "\u{00E2}\u{008F}\u{00B0} *Reminder*";
    $repaired = Mojibake::repair($broken);

    expect($repaired)->toBe("\u{23F0} *Reminder*")
        ->and(mb_check_encoding($repaired, 'UTF-8'))->toBeTrue();
});

test('ordinary text passes through untouched', function () {
    foreach ([
        'Hello {firstname}, your invoice is ready.',
        'Invoice #INV-202611-000723',
        'café naïve résumé',
        'সার্ভিস বন্ধের পূর্ববর্তী কল',
        "\u{23F0} Reminder",
        '',
    ] as $text) {
        expect(Mojibake::repair($text))->toBe($text);
    }
});

test('repairing twice changes nothing further', function () {
    $broken = "\u{00E2}\u{008F}\u{00B0} *Reminder*";
    $once = Mojibake::repair($broken);

    expect(Mojibake::repair($once))->toBe($once);
});
