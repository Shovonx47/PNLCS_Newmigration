<?php

namespace Tests\Feature;

use App\Models\AddonSetting;
use App\Models\Admin;
use App\Models\AdminRole;
use App\Models\Client;
use App\Models\ClientGroup;
use App\Models\Currency;
use App\Models\Pricing;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Providers\AddonServiceProvider;
use App\Services\AddonManager;
use App\Services\CartService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Modules\Addons\AdvancedClientVerification\AdvancedClientVerificationModule;
use Modules\Addons\AdvancedClientVerification\Models\AcvDocument;
use Modules\Addons\AdvancedClientVerification\Models\AcvReminder;
use Modules\Addons\AdvancedClientVerification\Models\AcvVerification;
use Tests\TestCase;

class Phase4KycVerificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Currency::firstOrCreate(
            ['code' => 'BDT'],
            ['prefix' => '৳', 'suffix' => ' BDT', 'rate' => 1.00000, 'is_default' => true]
        );

        Setting::set('addon_advanced_client_verification_active', '1', 'addons');

        $addon = AdvancedClientVerificationModule::NAME;
        AddonSetting::setSetting($addon, 'verification_mode', 'hybrid');
        AddonSetting::setSetting($addon, 'api_key', 'test-didit-api-key');
        AddonSetting::setSetting($addon, 'workflow_id', '2ac4f3a2-1065-49dd-afcc-76cd25d6eb59');
        AddonSetting::setSetting($addon, 'webhook_secret', 'test-webhook-hmac-secret');
        AddonSetting::setSetting($addon, 'client_access', '1');
        AddonSetting::setSetting($addon, 'auto_approve', '1');
        AddonSetting::setSetting($addon, 'require_selfie', '1');
        AddonSetting::setSetting($addon, 'require_document_back', '1');
        AddonSetting::setSetting($addon, 'require_trade_license', '1');
        AddonSetting::setSetting($addon, 'business_types', 'sole_proprietorship,partnership,limited_company,pvt_ltd');
        AddonSetting::setSetting($addon, 'verification_deadline_days', '7');
        AddonSetting::setSetting($addon, 'enable_service_suspension', '1');
        AddonSetting::setSetting($addon, 'enable_daily_reminders', '1');
        AddonSetting::setSetting($addon, 'verification_requirement', 'disabled');

        app()->forgetInstance(AddonManager::class);
        (new AddonServiceProvider(app()))->boot();
        app('router')->getRoutes()->refreshNameLookups();

        require base_path('modules/Addons/AdvancedClientVerification/hooks.php');
    }

    private function makeAdmin(): Admin
    {
        $role = AdminRole::firstOrCreate(
            ['name' => 'KYC Admin '.uniqid()],
            ['description' => 'Full admin', 'is_full_admin' => true, 'permissions' => []]
        );

        return Admin::forceCreate([
            'username' => 'kycadmin_'.uniqid(),
            'email' => 'kycadmin.'.uniqid().'@softcents.test',
            'password' => Hash::make('AdminSecret123!'),
            'first_name' => 'KYC',
            'last_name' => 'Officer',
            'role_id' => $role->id,
            'is_disabled' => false,
        ]);
    }

    private function makeClientWithUser(?ClientGroup $group = null): array
    {
        $email = 'kyc.'.uniqid().'@softcents.test';

        $user = User::forceCreate([
            'first_name' => 'Nusrat',
            'last_name' => 'Jahan',
            'email' => $email,
            'password' => Hash::make('ClientSecret123!'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $client = Client::forceCreate([
            'first_name' => 'Nusrat',
            'last_name' => 'Jahan',
            'email' => $email,
            'phone_number' => '01722334455',
            'address1' => 'Dhanmondi 27',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'postcode' => '1209',
            'country' => 'BD',
            'status' => 'active',
            'group_id' => $group?->id,
        ]);

        $user->clients()->syncWithoutDetaching([$client->id => ['owner' => true, 'permissions' => null]]);

        return [$user, $client];
    }

    private function realPngUpload(string $filename): UploadedFile
    {
        // Minimal valid 1x1 PNG binary with authentic magic bytes
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');

        return UploadedFile::fake()->createWithContent($filename, $png);
    }

    private function realPdfUpload(string $filename): UploadedFile
    {
        $pdf = "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n";

        return UploadedFile::fake()->createWithContent($filename, $pdf);
    }

    public function test_didit_session_creation_and_hmac_webhook_signature_enforcement(): void
    {
        Http::fake([
            'verification.didit.me/v2/session/' => Http::response([
                'session_id' => 'sess_live_987654',
                'url' => 'https://verify.didit.me/session/sess_live_987654',
            ], 201),
        ]);

        [$user, $client] = $this->makeClientWithUser();

        $this->actingAs($user)
            ->withSession(['active_client_id' => $client->id])
            ->post(route('client.kyc.session'))
            ->assertRedirect('https://verify.didit.me/session/sess_live_987654');

        $verification = AcvVerification::where('client_id', $client->id)->firstOrFail();
        $this->assertSame('pending', $verification->status);
        $this->assertSame('sess_live_987654', $verification->session_id);

        $payload = json_encode([
            'session_id' => 'sess_live_987654',
            'vendor_data' => (string) $client->id,
            'status' => 'Approved',
        ]);

        // Forged signature must fail closed with 401
        $this->call('POST', route('kyc.didit.webhook'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SIGNATURE' => 'deadbeef',
        ], $payload)->assertStatus(401);
        $this->assertSame('pending', $verification->fresh()->status);

        // Authentic HMAC-SHA256 signature succeeds and marks client verified
        $validSig = hash_hmac('sha256', $payload, 'test-webhook-hmac-secret');
        $this->call('POST', route('kyc.didit.webhook'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SIGNATURE' => $validSig,
        ], $payload)->assertOk()->assertJson(['ok' => true, 'status' => 'verified']);

        $this->assertSame('verified', $verification->fresh()->status);
    }

    public function test_manual_kyc_enforces_magic_bytes_trade_license_private_storage_and_authorized_downloads(): void
    {
        Storage::fake('local');
        $admin = $this->makeAdmin();
        [$userA, $clientA] = $this->makeClientWithUser();
        [$userB, $clientB] = $this->makeClientWithUser();

        // Disguised script renamed .png must be rejected
        $fakeScript = UploadedFile::fake()->createWithContent('id.png', '<?php echo 1; ?>');
        $this->actingAs($userA)
            ->withSession(['active_client_id' => $clientA->id])
            ->post(route('client.kyc.manual'), [
                'business_type' => 'individual',
                'document_type' => 'passport',
                'document_number' => 'BD998877',
                'id_front' => $fakeScript,
                'selfie' => $this->realPngUpload('selfie.png'),
            ])
            ->assertSessionHasErrors();

        // Business type limited_company requires trade_license + trade_license_number
        $this->actingAs($userA)
            ->withSession(['active_client_id' => $clientA->id])
            ->post(route('client.kyc.manual'), [
                'business_type' => 'limited_company',
                'document_type' => 'national_id',
                'document_number' => '1990123456789',
                'id_front' => $this->realPngUpload('front.png'),
                'id_back' => $this->realPngUpload('back.png'),
                'selfie' => $this->realPngUpload('selfie.png'),
            ])
            ->assertSessionHasErrors(['trade_license_number', 'trade_license']);

        // Complete valid submission
        $this->actingAs($userA)
            ->withSession(['active_client_id' => $clientA->id])
            ->post(route('client.kyc.manual'), [
                'business_type' => 'limited_company',
                'trade_license_number' => '=TL-2026-8899',
                'document_type' => 'national_id',
                'document_number' => '1990123456789',
                'id_front' => $this->realPngUpload('front.png'),
                'id_back' => $this->realPngUpload('back.png'),
                'selfie' => $this->realPngUpload('selfie.png'),
                'trade_license' => $this->realPdfUpload('license.pdf'),
            ])
            ->assertRedirect(route('client.kyc.index'));

        $doc = AcvDocument::where('client_id', $clientA->id)->where('file_type', 'trade_license')->firstOrFail();
        $this->assertStringStartsWith('kyc/'.$clientA->id.'/', $doc->storage_path);
        $this->assertStringNotContainsString('license.pdf', $doc->storage_path);
        Storage::disk('local')->assertExists($doc->storage_path);

        // Cross-tenant client B is strictly forbidden
        $this->actingAs($userB)
            ->withSession(['active_client_id' => $clientB->id])
            ->get(route('client.kyc.documents.download', $doc))
            ->assertForbidden();

        // Owner download forces attachment + nosniff
        $dl = $this->actingAs($userA)
            ->withSession(['active_client_id' => $clientA->id])
            ->get(route('client.kyc.documents.download', $doc))
            ->assertOk();
        $this->assertSame('nosniff', $dl->headers->get('X-Content-Type-Options'));
        $this->assertStringContainsString('attachment', (string) $dl->headers->get('Content-Disposition'));

        // CSV export neutralizes formula injection on '=TL-2026-8899'
        $csv = $this->actingAs($admin, 'admin')
            ->get(route('admin.extensions.kyc.export'))
            ->assertOk()
            ->streamedContent();
        $this->assertStringContainsString("'=TL-2026-8899", $csv);
    }

    public function test_checkout_gate_and_7_day_deadline_suspension_and_auto_unsuspend_on_approve(): void
    {
        Mail::fake();
        $admin = $this->makeAdmin();
        [$user, $client] = $this->makeClientWithUser();

        $exemptGroup = ClientGroup::create([
            'name' => 'Exempt KYC '.uniqid(),
            'color' => '#00aa00',
            'discount_percent' => 0,
            'suspend_exempt' => true,
            'terminate_exempt' => true,
        ]);
        [, $exemptClient] = $this->makeClientWithUser($exemptGroup);

        $currency = Currency::getDefault();
        $restrictedGroup = ProductGroup::create(['name' => 'IPTSP Restricted '.uniqid(), 'slug' => 'iptsp-r-'.uniqid()]);
        $product = Product::create([
            'group_id' => $restrictedGroup->id,
            'name' => 'IPTSP DID Line',
            'slug' => 'iptsp-did-'.uniqid(),
            'type' => 'other',
            'pay_type' => 'recurring',
        ]);
        Pricing::create([
            'type' => 'product',
            'currency_id' => $currency->id,
            'rel_id' => $product->id,
            'monthly' => 500,
            'quarterly' => -1, 'semiannually' => -1, 'annually' => -1, 'biennially' => -1, 'triennially' => -1,
        ]);

        AddonSetting::setSetting(AdvancedClientVerificationModule::NAME, 'verification_requirement', 'restricted_groups');
        AddonSetting::setSetting(AdvancedClientVerificationModule::NAME, 'restricted_groups', (string) $restrictedGroup->id);

        $cart = app(CartService::class)->getOrCreateCart($client->id);
        app(CartService::class)->addProduct($cart, $product, 'monthly');

        // Unverified client attempting restricted checkout is redirected to /client/verification
        $this->actingAs($user)
            ->withSession(['active_client_id' => $client->id])
            ->get(route('client.cart.checkout'))
            ->assertRedirect(route('client.kyc.index'));

        // Active service older than 7 days for unverified client gets suspended by pnlcs:acv-enforce
        $oldService = Service::create([
            'client_id' => $client->id,
            'product_id' => $product->id,
            'domain' => '09644001122',
            'amount' => 500,
            'billing_cycle' => 'Monthly',
            'next_due_date' => now()->addDays(20)->toDateString(),
            'registration_date' => now()->subDays(9)->toDateString(),
            'status' => 'active',
        ]);

        $exemptService = Service::create([
            'client_id' => $exemptClient->id,
            'product_id' => $product->id,
            'domain' => '09644009999',
            'amount' => 500,
            'billing_cycle' => 'Monthly',
            'next_due_date' => now()->addDays(20)->toDateString(),
            'registration_date' => now()->subDays(9)->toDateString(),
            'status' => 'active',
        ]);

        Artisan::call('pnlcs:acv-enforce');

        $this->assertSame('suspended', strtolower((string) $oldService->fresh()->status));
        $this->assertSame('active', strtolower((string) $exemptService->fresh()->status));
        $this->assertSame(1, AcvReminder::where('client_id', $client->id)->count());

        // Second run same day does not duplicate reminder
        Artisan::call('pnlcs:acv-enforce');
        $this->assertSame(1, AcvReminder::where('client_id', $client->id)->count());

        // Approving KYC automatically unsuspends the service ACV suspended and unlocks checkout
        $verification = AcvVerification::where('client_id', $client->id)->firstOrFail();
        $this->actingAs($admin, 'admin')
            ->post(route('admin.extensions.kyc.approve', $verification))
            ->assertRedirect();

        $this->assertSame('active', strtolower((string) $oldService->fresh()->status));

        $this->actingAs($user)
            ->withSession(['active_client_id' => $client->id])
            ->get(route('client.cart.checkout'))
            ->assertOk();

        // Verified client still sees Identity Verification in navbar on non-dashboard pages
        $this->actingAs($user, 'web')
            ->withSession(['active_client_id' => $client->id])
            ->get(route('client.invoices.index'))
            ->assertOk()
            ->assertSee('Identity Verification');

        // Admin Client Summary displays KYC badge linking to verification queue
        $this->actingAs($admin, 'admin')
            ->get(route('admin.clients.show', $client))
            ->assertOk()
            ->assertSee('KYC:')
            ->assertSee('Verified');
    }
}
