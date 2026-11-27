<?php

use App\Models\ProductGroup;

/*
 * The client area on a phone. The shell is now the shop's WHMCS Twenty-One
 * theme, so what has to survive at 375px is that theme's arrangement: the main
 * menu collapses behind a toggle, the Store menu opens the order forms, and a
 * visitor still finds Sign In and Register inside the Account menu. The widths
 * themselves were measured in a browser, as before.
 */

it('keeps product groups in the store itself, not as one bar item each', function () {
    foreach (['Shared Hosting', 'VPS Servers', 'Dedicated Servers'] as $i => $name) {
        ProductGroup::create(['name' => $name, 'slug' => Str::slug($name), 'hidden' => 0, 'sort_order' => $i]);
    }

    $html = $this->get(route('client.contact'))->assertOk()->getContent();

    // One Store item in the bar, opening the order forms.
    expect(preg_match_all('/>\s*Store\s*</', $html))->toBeGreaterThanOrEqual(1)
        ->and($html)->toContain('id="mainNavbar"');

    // The groups are not repeated as items of the bar.
    foreach (['Shared Hosting', 'VPS Servers', 'Dedicated Servers'] as $name) {
        expect(preg_match_all('/>\s*'.preg_quote($name, '/').'\s*</', $html))->toBe(0);
    }
});

it('offers a visitor the login and sign-up links inside the account menu', function () {
    $html = $this->get(route('client.contact'))->assertOk()->getContent();

    // Bootstrap's collapse holds the menu on a phone; the toggle is in the
    // toolbar, and the Account menu carries both links for a visitor.
    expect($html)->toContain('data-target="#mainNavbar"')
        ->and($html)->toContain(route('client.login'))
        ->and($html)->toContain(route('client.register'));
});

it('lets the contact page stack into one column instead of a fixed side column', function () {
    $html = $this->get(route('client.contact'))->assertOk()->getContent();

    // The form is a full-width card; the company details sit beneath it in a
    // Bootstrap row that wraps, so nothing is pinned to a 380px side column.
    expect($html)->toContain('card-body extra-padding')
        ->and($html)->not->toContain('grid-template-columns:1fr 380px');
});

it('keeps the contact page readable under a theme that brings its own client layout', function () {
    // flavor (and custom themes, such as a hosting company's own) replace the
    // client layout. The page's own markup carries no column widths of its
    // own, so it stays readable under another shell too.
    $finder = app('view')->getFinder();
    app('view')->prependLocation(base_path('themes/flavor/views'));
    $finder->flush();

    $html = $this->get(route('client.contact'))->assertOk()->getContent();

    expect($html)->toContain('card-body extra-padding')
        ->not->toContain('pn-aside-grid');
});
