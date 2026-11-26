<?php

namespace Tests\Feature;

use App\Models\AddonSetting;
use App\Models\Admin;
use App\Models\AdminRole;
use App\Models\Client;
use App\Models\Currency;
use App\Models\Domain;
use App\Models\DomainPricing;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Pricing;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\Server;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Providers\AddonServiceProvider;
use App\Services\AddonManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Modules\Addons\DriveBackup\DriveBackupModule;
use Modules\Addons\DriveBackup\Models\DriveBackupLog;
use Modules\Addons\ForceClientActive\Models\ForcedActiveClient;
use Modules\Addons\ForceClientActive\Models\ForcedActiveLog;
use Modules\Addons\PriceUpdater\Models\PriceUpdaterLog;
use Modules\Addons\ServerIpUnblock\Models\IpUnblockLog;
use Modules\Addons\SupportPin\Services\SupportPinService;
use Tests\TestCase;
use ZipArchive;

class Phase4AdminAndSupportToolsTest extends TestCase
{
    private const ADDONS = [
        'price_updater',
        'invoice_export',
        'gdrive_backup',
        'support_pin',
        'force_client_active',
        'unblock_ip',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Currency::firstOrCreate(
            ['code' => 'BDT'],
            ['prefix' => '৳', 'suffix' => ' BDT', 'rate' => 1.00000, 'is_default' => true]
        );

        foreach (self::ADDONS as $addon) {
            Setting::set("addon_{$addon}_active", '1', 'addons');
        }

        app()->forgetInstance(AddonManager::class);
        (new AddonServiceProvider(app()))->boot();
        app('router')->getRoutes()->refreshNameLookups();

        foreach (['SupportPin', 'ServerIpUnblock', 'ForceClientActive'] as $folder) {
            $file = base_path("modules/Addons/{$folder}/hooks.php");
            if (is_file($file)) {
                require $file;
            }
        }
    }

    private function makeAdmin(): Admin
    {
        $role = AdminRole::firstOrCreate(
            ['name' => 'Tools Admin '.uniqid()],
            ['description' => 'Full admin', 'is_full_admin' => true, 'permissions' => []]
        );

        return Admin::forceCreate([
            'username' => 'toolsadmin_'.uniqid(),
            'email' => 'toolsadmin.'.uniqid().'@softcents.test',
            'password' => Hash::make('AdminSecret123!'),
            'first_name' => 'Tools',
            'last_name' => 'Admin',
            'role_id' => $role->id,
            'is_disabled' => false,
        ]);
    }

    private function makeClientWithUser(): array
    {
        $email = 'support.'.uniqid().'@softcents.test';

        $user = User::forceCreate([
            'first_name' => 'Kamal',
            'last_name' => 'Hossain',
            'email' => $email,
            'password' => Hash::make('ClientSecret123!'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $client = Client::forceCreate([
            'first_name' => '=Kamal',
            'last_name' => 'Hossain',
            'email' => $email,
            'phone_number' => '01755667788',
            'address1' => 'Mirpur 10',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'postcode' => '1216',
            'country' => 'BD',
            'status' => 'active',
        ]);

        $user->clients()->syncWithoutDetaching([$client->id => ['owner' => true, 'permissions' => null]]);

        return [$user, $client];
    }

    public function test_price_updater_dry_run_and_transactional_apply_for_products_services_and_tlds(): void
    {
        $admin = $this->makeAdmin();
        $currency = Currency::getDefault();
        $group = ProductGroup::create(['name' => 'BDIX '.uniqid(), 'slug' => 'bdix-'.uniqid()]);
        $product = Product::create(['group_id' => $group->id, 'name' => 'BDIX Starter', 'slug' => 'bdix-s-'.uniqid(), 'type' => 'hosting', 'pay_type' => 'recurring']);

        $pricing = Pricing::create([
            'type' => 'product',
            'currency_id' => $currency->id,
            'rel_id' => $product->id,
            'monthly' => 200,
            'quarterly' => -1, 'semiannually' => -1, 'annually' => 2000, 'biennially' => -1, 'triennially' => -1,
        ]);

        [, $client] = $this->makeClientWithUser();
        $service = Service::create([
            'client_id' => $client->id,
            'product_id' => $product->id,
            'domain' => 'starter.bdix.test',
            'amount' => 200,
            'billing_cycle' => 'monthly',
            'next_due_date' => now()->addMonth()->toDateString(),
            'registration_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        // Preview leaves database unchanged
        $this->actingAs($admin, 'admin')
            ->post(route('admin.extensions.price-updater.products'), [
                'mode' => 'percent',
                'delta' => 10,
                'group_id' => $group->id,
                'currency_id' => $currency->id,
                'cascade_services' => '1',
                'action_mode' => 'preview',
            ])
            ->assertRedirect();

        $this->assertEquals(200.00, (float) $pricing->fresh()->monthly);
        $this->assertEquals(200.00, (float) $service->fresh()->amount);

        // Apply updates pricing + cascades to active service + keeps -1 untouched
        $this->actingAs($admin, 'admin')
            ->post(route('admin.extensions.price-updater.products'), [
                'mode' => 'percent',
                'delta' => 10,
                'group_id' => $group->id,
                'currency_id' => $currency->id,
                'cascade_services' => '1',
                'action_mode' => 'apply',
            ])
            ->assertRedirect();

        $this->assertEquals(220.00, (float) $pricing->fresh()->monthly);
        $this->assertEquals(2200.00, (float) $pricing->fresh()->annually);
        $this->assertEquals(-1.00, (float) $pricing->fresh()->quarterly);
        $this->assertEquals(220.00, (float) $service->fresh()->amount);
        $this->assertTrue(PriceUpdaterLog::where('target_type', 'service')->exists());

        // TLD update + cascade_domains (longest-suffix match)
        $ext = '.p4test'.rand(10, 99);
        $subExt = '.com'.$ext;
        $tld = DomainPricing::create([
            'extension' => $ext,
            'register_price' => 1000,
            'renew_price' => 1200,
            'transfer_price' => 1000,
            'enabled' => true,
        ]);
        DomainPricing::create([
            'extension' => $subExt,
            'register_price' => 800,
            'renew_price' => 900,
            'transfer_price' => 800,
            'enabled' => true,
        ]);

        $domDirect = Domain::create([
            'client_id' => $client->id,
            'domain' => 'alpha'.$ext,
            'status' => 'active',
            'recurring_amount' => 1200,
        ]);
        $domSub = Domain::create([
            'client_id' => $client->id,
            'domain' => 'beta'.$subExt,
            'status' => 'active',
            'recurring_amount' => 900,
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.extensions.price-updater.domains'), [
                'mode' => 'fixed',
                'delta' => 150,
                'extension' => $ext,
                'cascade_domains' => '1',
                'action_mode' => 'apply',
            ])
            ->assertRedirect();

        $this->assertEquals(1150.00, (float) $tld->fresh()->register_price);
        $this->assertEquals(1350.00, (float) $tld->fresh()->renew_price);
        $this->assertEquals(1350.00, (float) $domDirect->fresh()->recurring_amount);
        $this->assertEquals(900.00, (float) $domSub->fresh()->recurring_amount);
    }

    public function test_invoice_export_generates_valid_pdf_zip_with_formula_safe_csv(): void
    {
        $admin = $this->makeAdmin();
        [, $client] = $this->makeClientWithUser();

        $invoice = Invoice::create([
            'invoice_num' => 'INV-ZIP-'.uniqid(),
            'client_id' => $client->id,
            'date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'subtotal' => 750,
            'total' => 750,
            'status' => 'paid',
            'date_paid' => now(),
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'type' => 'Hosting',
            'rel_id' => 0,
            'description' => 'Cloud PBX Monthly',
            'amount' => 750,
        ]);

        $res = $this->actingAs($admin, 'admin')
            ->post(route('admin.extensions.invoice-export.download'), [
                'client_id' => $client->id,
                'status' => 'paid',
                'limit' => 5,
            ])
            ->assertOk();

        $this->assertSame('nosniff', $res->headers->get('X-Content-Type-Options'));
        $file = $res->baseResponse->getFile();
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($file->getPathname()) === true);

        $csv = (string) $zip->getFromName('index.csv');
        $this->assertStringContainsString($invoice->invoice_num, $csv);
        // Client first_name starts with '=' so CSV must escape it
        $this->assertStringContainsString("'=Kamal", $csv);

        $foundPdf = false;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if (str_ends_with((string) $entry, '.pdf')) {
                $bytes = (string) $zip->getFromIndex($i);
                $this->assertStringStartsWith('%PDF-', $bytes);
                $foundPdf = true;
            }
        }
        $zip->close();
        $this->assertTrue($foundPdf);
    }

    public function test_gdrive_backup_encrypts_secrets_uploads_multipart_and_prunes_retention(): void
    {
        $admin = $this->makeAdmin();
        $addon = DriveBackupModule::NAME;
        AddonSetting::setSetting($addon, 'client_id', 'oauth-client-id');
        AddonSetting::setSetting($addon, 'client_secret', 'super-secret-oauth-key');
        AddonSetting::setSetting($addon, 'refresh_token', 'refresh-tok-xyz');
        AddonSetting::setSetting($addon, 'drive_folder_id', 'folder123');
        AddonSetting::setSetting($addon, 'max_backups', '1');
        AddonSetting::setSetting($addon, 'backup_db', '0');
        AddonSetting::setSetting($addon, 'backup_files', '0');
        AddonSetting::setSetting($addon, 'keep_local', '0');

        // Confirm raw DB value is encrypted, never plaintext
        $rawCipher = DB::table('addon_settings')->where('addon', $addon)->where('setting', 'refresh_token')->value('value');
        $this->assertNotSame('refresh-tok-xyz', $rawCipher);

        Http::fake([
            'oauth2.googleapis.com/token' => function ($request) {
                if (($request['grant_type'] ?? '') === 'authorization_code') {
                    return Http::response(['refresh_token' => 'rotated-refresh-tok-999'], 200);
                }

                return Http::response(['access_token' => 'ya29.fake_access_token'], 200);
            },
            'www.googleapis.com/upload/drive/v3/files*' => Http::response(['id' => 'drive_new_file_1'], 200),
            'www.googleapis.com/drive/v3/files*' => function ($request) {
                if ($request->method() === 'DELETE') {
                    return Http::response('', 204);
                }

                return Http::response([
                    'files' => [
                        ['id' => 'drive_new_file_1', 'name' => 'pnlcs-backup-new.zip'],
                        ['id' => 'drive_old_file_2', 'name' => 'pnlcs-backup-old.zip'],
                    ],
                ], 200);
            },
        ]);

        // OAuth2 authorization code exchange helper rotates and encrypts refresh_token
        $this->actingAs($admin, 'admin')
            ->post(route('admin.extensions.gdrive-backup.oauth-exchange'), [
                'auth_code' => '4/0AdQt8qjFakeCode',
            ])
            ->assertRedirect(route('admin.extensions.gdrive-backup.index'));
        $this->assertSame('rotated-refresh-tok-999', AddonSetting::getSetting($addon, 'refresh_token'));

        $code = Artisan::call('pnlcs:gdrive-backup');
        $this->assertSame(0, $code);

        $log = DriveBackupLog::latest('id')->firstOrFail();
        $this->assertSame('success', $log->status);
        $this->assertSame('drive_new_file_1', $log->drive_file_id);
        $this->assertStringContainsString('pruned 1 older backup', (string) $log->message);
    }

    public function test_support_pin_force_client_active_and_server_ip_unblocker(): void
    {
        $admin = $this->makeAdmin();
        [$user, $client] = $this->makeClientWithUser();

        // 1. Support PIN generation, dashboard masking, verification, and rate limit
        $pinModel = app(SupportPinService::class)->getOrGenerate($client->id);
        $plainPin = (string) $pinModel->pin_encrypted;
        $this->assertMatchesRegularExpression('/^\d{6}$/', $plainPin);

        $this->actingAs($user, 'web')
            ->withSession(['active_client_id' => $client->id])
            ->get(route('client.home'))
            ->assertOk()
            ->assertSee('Support Verification PIN');

        // Admin Client Summary displays the client's Support PIN
        $this->actingAs($admin, 'admin')
            ->get(route('admin.clients.show', $client))
            ->assertOk()
            ->assertSee('Support PIN:')
            ->assertSee($plainPin);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.extensions.support-pin.verify'), ['pin' => $plainPin])
            ->assertRedirect()
            ->assertSessionHas('support_pin_client');

        // Rate limit throttle:5,1 -> 5 more attempts hit 429 on the 6th total within 1 minute
        for ($i = 0; $i < 4; $i++) {
            $this->actingAs($admin, 'admin')->post(route('admin.extensions.support-pin.verify'), ['pin' => '000000']);
        }
        $this->actingAs($admin, 'admin')
            ->post(route('admin.extensions.support-pin.verify'), ['pin' => '000000'])
            ->assertStatus(429);

        // 2. ForceClientActive restores inactive non-protected client, leaves closed non-protected closed, and restores closed protected client
        [, $inactiveClient] = $this->makeClientWithUser();
        [, $closedClient] = $this->makeClientWithUser();
        $inactiveClient->update(['status' => 'inactive']);
        $closedClient->update(['status' => 'closed']);
        $client->update(['status' => 'closed']);
        ForcedActiveClient::create(['client_id' => $client->id, 'note' => 'VIP Partner']);

        Artisan::call('pnlcs:force-client-active');
        $this->assertSame('active', strtolower($client->fresh()->status->value ?? (string) $client->fresh()->status));
        $this->assertSame('active', strtolower($inactiveClient->fresh()->status->value ?? (string) $inactiveClient->fresh()->status));
        $this->assertSame('closed', strtolower($closedClient->fresh()->status->value ?? (string) $closedClient->fresh()->status));
        $this->assertTrue(ForcedActiveLog::where('client_id', $client->id)->exists());

        // 3. ServerIpUnblock invokes BdpbxModule::unblockIp() and records audit log
        Http::fake([
            'https://pbx-node.softcents.test/*' => Http::response(['status' => 'success', 'message' => 'IP cleared'], 200),
        ]);

        $server = Server::create([
            'name' => 'PBX Node Test',
            'hostname' => 'pbx-node.softcents.test',
            'ip_address' => '203.0.113.10',
            'type' => 'bdpbx',
            'access_hash' => 'pbx-secret-api-key',
            'active' => true,
        ]);
        $pg = ProductGroup::create(['name' => 'PBX Unblock '.uniqid(), 'slug' => 'pbx-u-'.uniqid()]);
        $prod = Product::create(['group_id' => $pg->id, 'name' => 'Cloud PBX', 'slug' => 'cpbx-'.uniqid(), 'type' => 'other', 'pay_type' => 'recurring', 'server_type' => 'bdpbx']);
        $pbxService = Service::create([
            'client_id' => $client->id,
            'product_id' => $prod->id,
            'server_id' => $server->id,
            'domain' => '49.bdpbx.com',
            'amount' => 1000,
            'billing_cycle' => 'Monthly',
            'next_due_date' => now()->addMonth()->toDateString(),
            'registration_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        // Client navbar exposes Unblock IP Address on non-dashboard screens
        $this->actingAs($user, 'web')
            ->withSession(['active_client_id' => $client->id])
            ->get(route('client.invoices.index'))
            ->assertOk()
            ->assertSee('Unblock IP Address');

        $this->actingAs($user, 'web')
            ->withSession(['active_client_id' => $client->id])
            ->post(route('client.unblock-ip.submit'), [
                'service_id' => $pbxService->id,
                'ip_address' => '103.120.44.55',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        // Admin direct unblock works and logs staff actor
        $this->actingAs($admin, 'admin')
            ->post(route('admin.extensions.unblock-ip.unblock'), [
                'service_id' => $pbxService->id,
                'ip_address' => '103.120.44.56',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue(
            IpUnblockLog::where('client_id', $client->id)
                ->where('service_id', $pbxService->id)
                ->where('ip_address', '103.120.44.56')
                ->where('success', true)
                ->where('message', 'like', '[Staff:%')
                ->exists()
        );
    }
}
