<?php

use App\Models\Client;
use App\Models\Setting;
use Illuminate\Support\ViewErrorBag;

/*
 * The sign-in pages (register, forgot-password, reset-password, two-factor and
 * invite) used to be standalone documents carrying their own --card/--border
 * shim. They now extend the WHMCS Twenty-One shell like every other client
 * page, so what they must carry is the theme's stylesheet and the uploaded
 * logo rather than a private set of colour variables.
 */

beforeEach(function () {
    Setting::set('custom_logo_path', '/branding/logo_7.png', 'appearance');
});

test('the client sign-in pages carry the logo and the theme colours', function (string $url) {
    $html = $this->get($url)->assertOk()->getContent();

    expect($html)->toContain('src="/branding/logo_7.png"')
        ->and($html)->toContain('templates/twenty-one/css/theme.min.css')
        ->and($html)->not->toMatch('/(background|color):\s*#(1a4d80|337ab7|405189)/');
})->with([
    'register' => fn () => route('client.register'),
    'forgot password' => fn () => route('client.password.request'),
    'reset password' => fn () => route('client.password.reset', ['token' => 'abc']),
]);

test('the two-factor and invite views use the same head and mark', function (string $view, array $data) {
    $html = view($view, $data + ['errors' => new ViewErrorBag])->render();

    expect($html)->toContain('src="/branding/logo_7.png"')
        ->and($html)->toContain('templates/twenty-one/css/theme.min.css');
})->with([
    'two-factor' => ['client.auth.two-factor', []],
    'invite' => ['client.auth.invite', ['invite' => (object) ['email' => 'new@example.com'], 'account' => new Client(['company_name' => 'Acme', 'first_name' => 'A', 'last_name' => 'B']), 'hasLogin' => false, 'signedInAs' => null, 'token' => 'abc']],
]);
