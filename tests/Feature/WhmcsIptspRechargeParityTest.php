<?php

use App\Http\Middleware\RedirectToInstaller;
use App\Models\Admin;
use App\Models\AdminRole;
use App\Models\Client;
use App\Models\Currency;
use App\Models\Setting;
use App\Providers\AddonServiceProvider;
use App\Services\AddonManager;
use Illuminate\Support\Facades\Http;
use Modules\Addons\ItalkRecharge\Models\ItalkRechargeAccount;
use Modules\Addons\ItalkRecharge\Models\ItalkRechargeLog;
use Modules\Addons\ItalkRecharge\Models\ItalkRechargeProvider;

beforeEach(function () {
    $this->withoutMiddleware(RedirectToInstaller::class);

    foreach (['italk_balance', 'italk_recharge', 'recharge_requests', 'bdpbx_accounting'] as $addon) {
        Setting::set("addon_{$addon}_active", '1', 'addons');
    }
    app()->forgetInstance(AddonManager::class);
    (new AddonServiceProvider(app()))->boot();
    app('router')->getRoutes()->refreshNameLookups();

    $this->role = AdminRole::factory()->fullAdmin()->create();
    $this->admin = Admin::factory()->create(['role_id' => $this->role->id]);

    Currency::where('is_default', true)->update(['is_default' => false]);
    Currency::factory()->create([
        'code' => 'BDT',
        'prefix' => '৳',
        'suffix' => ' Tk',
        'format' => 4,
        'rate' => 1.0,
        'is_default' => true,
    ]);
});

test('iptsp recharge index renders WHMCS Multi-Provider Auto Recharge table, Addons sidebar, badges, and log stats without exposing secrets', function () {
    $this->actingAs($this->admin, 'admin')
        ->post(route('admin.extensions.iptsp-recharge.providers.store'), [
            'name' => 'CODE 09647',
            'base_url' => 'https://billing.webvoice.net',
            'base_path' => '/',
            'api_type' => 'webvoice',
            'username' => 'Ominaz',
            'password' => 'TopSecretRest99!',
            'api_key' => 'ApiSecretRest88!',
            'status' => 'active',
        ])->assertRedirect(route('admin.extensions.iptsp-recharge.index'));

    $this->actingAs($this->admin, 'admin')
        ->post(route('admin.extensions.iptsp-recharge.providers.store'), [
            'name' => 'CODE 09639',
            'base_url' => 'https://icctalk.com',
            'base_path' => '/ICC/',
            'api_type' => 'italk',
            'username' => 'Softcents',
            'password' => 'TopSecretScraper77!',
            'status' => 'inactive',
        ])->assertRedirect(route('admin.extensions.iptsp-recharge.index'));

    $restProvider = ItalkRechargeProvider::where('name', 'CODE 09647')->firstOrFail();
    $client = Client::factory()->create(['first_name' => 'Subrato', 'last_name' => 'Saha']);

    ItalkRechargeLog::create([
        'client_id' => $client->id,
        'provider_id' => $restProvider->id,
        'pin_number' => '09647000111',
        'amount' => 200,
        'status' => 'pending',
        'attempts' => 1,
    ]);
    ItalkRechargeLog::create([
        'client_id' => $client->id,
        'provider_id' => $restProvider->id,
        'pin_number' => '09647000111',
        'amount' => 500,
        'status' => 'success',
        'attempts' => 1,
    ]);
    ItalkRechargeLog::create([
        'client_id' => $client->id,
        'provider_id' => $restProvider->id,
        'pin_number' => '09647000111',
        'amount' => 1500,
        'status' => 'success',
        'attempts' => 1,
    ]);
    ItalkRechargeLog::create([
        'client_id' => $client->id,
        'provider_id' => $restProvider->id,
        'pin_number' => '09647000111',
        'amount' => 100,
        'status' => 'failed',
        'attempts' => 2,
    ]);

    $html = $this->actingAs($this->admin, 'admin')
        ->get(route('admin.extensions.iptsp-recharge.index'))
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain('Multi-Provider Auto Recharge')
        ->toContain('Recharge Providers')
        ->toContain('Add New')
        ->toContain('Manage Pending')
        ->toContain('<th>Logo</th>')
        ->toContain('<th>Base URL</th>')
        ->toContain('<th>Type</th>')
        ->toContain('<th>Username</th>')
        ->toContain('<th>Status</th>')
        ->toContain('Stats (Count &amp; Total BDT)')
        ->toContain('<th>Actions</th>')
        ->toContain('CODE 09647')
        ->toContain('CODE 09639')
        ->toContain('REST API')
        ->toContain('SCRAPER')
        ->toContain('Active')
        ->toContain('Inactive')
        ->toContain('Pending: 1 (৳200.00)')
        ->toContain('Success: 2 (৳2,000.00)')
        ->toContain('Failed: 1 (৳100.00)')
        ->toContain('Addons')
        ->not->toContain('TopSecretRest99!')
        ->not->toContain('ApiSecretRest88!')
        ->not->toContain('TopSecretScraper77!');
});

test('iptsp recharge supports add, edit with password preservation, and remove provider actions', function () {
    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.extensions.iptsp-recharge.index', ['action' => 'add']))
        ->assertOk()
        ->assertSee('Add Provider')
        ->assertSee('Driver / API Type')
        ->assertSee('Save');

    $this->actingAs($this->admin, 'admin')
        ->post(route('admin.extensions.iptsp-recharge.providers.store'), [
            'name' => 'CODE 09617',
            'base_url' => 'https://sip.ranksitt.net',
            'base_path' => '/vsm4/',
            'api_type' => 'ranksitt',
            'username' => 'Ominaz_Online_L1',
            'password' => 'KeepMeSecret!',
            'status' => 'active',
        ])->assertRedirect(route('admin.extensions.iptsp-recharge.index'));

    $provider = ItalkRechargeProvider::where('name', 'CODE 09617')->firstOrFail();

    $editHtml = $this->actingAs($this->admin, 'admin')
        ->get(route('admin.extensions.iptsp-recharge.index', ['action' => 'edit', 'id' => $provider->id]))
        ->assertOk()
        ->getContent();

    expect($editHtml)
        ->toContain('Edit Provider')
        ->toContain('Remove Provider')
        ->toContain('CODE 09617')
        ->not->toContain('KeepMeSecret!');

    // Updating without a password keeps the encrypted password intact
    $this->actingAs($this->admin, 'admin')
        ->put(route('admin.extensions.iptsp-recharge.providers.update', $provider), [
            'name' => 'CODE 09617 Updated',
            'base_url' => 'https://sip.ranksitt.net',
            'base_path' => '/vsm4/',
            'api_type' => 'ranksitt',
            'username' => 'Ominaz_Online_L1',
            'password' => '',
            'status' => 'active',
        ])->assertRedirect(route('admin.extensions.iptsp-recharge.index'));

    $provider->refresh();
    expect($provider->name)->toBe('CODE 09617 Updated')
        ->and($provider->password)->toBe('KeepMeSecret!');

    $client = Client::factory()->create();
    $account = ItalkRechargeAccount::create([
        'client_id' => $client->id,
        'provider_id' => $provider->id,
        'pin_number' => '09617510250',
        'total_recharged' => 0,
        'status' => 'active',
    ]);

    $this->actingAs($this->admin, 'admin')
        ->delete(route('admin.extensions.iptsp-recharge.providers.destroy', $provider))
        ->assertRedirect(route('admin.extensions.iptsp-recharge.index'));

    expect(ItalkRechargeProvider::find($provider->id))->toBeNull()
        ->and($account->fresh()->status)->toBe('inactive');
});

test('iptsp pending logs screen resolves pending requests to success or failed and updates account totals', function () {
    $provider = ItalkRechargeProvider::create([
        'name' => 'CODE 09644',
        'base_url' => 'https://sip.italk.com.bd',
        'base_path' => '/iconiptsp/',
        'api_type' => 'italk',
        'username' => 'Softcents_Systems',
        'password' => 'Pass123!',
        'status' => 'active',
    ]);
    $client = Client::factory()->create(['first_name' => 'Monoar', 'last_name' => 'Dipto']);
    $account = ItalkRechargeAccount::create([
        'client_id' => $client->id,
        'provider_id' => $provider->id,
        'pin_number' => '09644881127',
        'total_recharged' => 100.00,
        'status' => 'active',
    ]);

    $logSuccess = ItalkRechargeLog::create([
        'client_id' => $client->id,
        'provider_id' => $provider->id,
        'pin_number' => '09644881127',
        'amount' => 250.00,
        'status' => 'pending',
        'attempts' => 1,
    ]);
    $logFail = ItalkRechargeLog::create([
        'client_id' => $client->id,
        'provider_id' => $provider->id,
        'pin_number' => '09644881127',
        'amount' => 300.00,
        'status' => 'pending',
        'attempts' => 1,
    ]);

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.extensions.iptsp-recharge.index', ['action' => 'pending']))
        ->assertOk()
        ->assertSee('Pending Recharge Logs')
        ->assertSee('Total Pending Requests')
        ->assertSee('৳550.00')
        ->assertSee('Manual Top-Up')
        ->assertSee('Client PIN Accounts');

    $this->actingAs($this->admin, 'admin')
        ->post(route('admin.extensions.iptsp-recharge.logs.resolve', $logSuccess), ['status' => 'success'])
        ->assertRedirect(route('admin.extensions.iptsp-recharge.index', ['action' => 'pending']));

    $account->refresh();
    expect($logSuccess->fresh()->status)->toBe('success')
        ->and((float) $account->total_recharged)->toBe(350.0)
        ->and($account->last_recharge)->not->toBeNull();

    $this->actingAs($this->admin, 'admin')
        ->post(route('admin.extensions.iptsp-recharge.logs.resolve', $logFail), ['status' => 'failed'])
        ->assertRedirect(route('admin.extensions.iptsp-recharge.index', ['action' => 'pending']));

    expect($logFail->fresh()->status)->toBe('failed');

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.extensions.iptsp-recharge.index', ['action' => 'pending']))
        ->assertOk()
        ->assertSee('No pending logs. Everything is clean!');
});

test('iptsp provider history view displays summary cards, PIN account CRUD, and manual top-up', function () {
    Http::fake(['*' => Http::response(['status' => 'ok'], 200)]);

    $provider = ItalkRechargeProvider::create([
        'name' => 'CODE 09617',
        'base_url' => 'https://sip.ranksitt.net',
        'base_path' => '/vsm4/',
        'api_type' => 'ranksitt',
        'username' => 'Ominaz_Online_L1',
        'password' => 'Pass123!',
        'status' => 'active',
    ]);
    $client = Client::factory()->create(['first_name' => 'Asif', 'last_name' => 'Rafi']);

    $this->actingAs($this->admin, 'admin')
        ->post(route('admin.extensions.iptsp-recharge.accounts.store'), [
            'client_id' => $client->id,
            'provider_id' => $provider->id,
            'pin_number' => '09617510244',
        ])->assertRedirect();

    $account = ItalkRechargeAccount::where('pin_number', '09617510244')->firstOrFail();

    ItalkRechargeLog::create([
        'client_id' => $client->id,
        'provider_id' => $provider->id,
        'pin_number' => '09617510244',
        'amount' => 1200.00,
        'status' => 'success',
        'attempts' => 1,
    ]);

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.extensions.iptsp-recharge.index', ['action' => 'history', 'id' => $provider->id]))
        ->assertOk()
        ->assertSee('History:')
        ->assertSee('CODE 09617')
        ->assertSee('Total Recharged')
        ->assertSee('৳1,200.00')
        ->assertSee('09617510244')
        ->assertSee('Asif Rafi');

    $this->actingAs($this->admin, 'admin')
        ->delete(route('admin.extensions.iptsp-recharge.accounts.destroy', $account))
        ->assertRedirect();

    expect(ItalkRechargeAccount::find($account->id))->toBeNull();
});
