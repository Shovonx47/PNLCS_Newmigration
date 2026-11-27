<?php

use App\Mail\TicketOpenedMail;
use App\Models\Admin;
use App\Models\Client;
use App\Models\Currency;
use App\Models\Quote;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\TicketDepartment;
use Illuminate\Support\Facades\Mail;

test('admin can create a quote for a new prospective client with percentage discount, duplicate, print, pdf, and convert to invoice', function () {
    $admin = Admin::factory()->create();
    $currency = Currency::firstOrCreate(
        ['code' => 'BDT'],
        ['prefix' => '৳', 'suffix' => ' BDT', 'format' => 1, 'rate' => 1, 'is_default' => true]
    );

    $response = $this->actingAs($admin, 'admin')
        ->post(route('admin.quotes.store'), [
            'client_type' => 'new',
            'first_name' => 'Tanvir',
            'last_name' => 'Rahman',
            'company_name' => 'SoftCents Demo Ltd',
            'email' => 'tanvir.prospect@example.test',
            'address1' => 'House 12, Road 5',
            'city' => 'Dhaka',
            'postcode' => '1212',
            'country' => 'BD',
            'phone_prefix' => '+880',
            'phone_number' => '1812345678',
            'currency_id' => $currency->id,
            'subject' => 'Enterprise PBX Setup Proposal',
            'stage' => 'Delivered',
            'date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'proposal' => str_repeat('Detailed scope of work. ', 20),
            'customer_notes' => '50% upfront payment.',
            'notes' => 'Internal sales note.',
            'items' => [
                [
                    'quantity' => 2,
                    'description' => "Cloud PBX Server\nIncludes SIP Trunk configuration",
                    'unit_price' => 1000,
                    'discount_percent' => 10,
                    'taxable' => 0,
                ],
                [
                    'quantity' => 1,
                    'description' => '',
                    'unit_price' => 0,
                    'discount_percent' => 0,
                    'taxable' => 0,
                ],
            ],
        ]);

    $quote = Quote::where('subject', 'Enterprise PBX Setup Proposal')->firstOrFail();
    $response->assertRedirect(route('admin.quotes.edit', $quote));

    expect($quote->client_id)->toBeNull()
        ->and($quote->status)->toBe('Delivered')
        ->and($quote->recipientName())->toBe('Tanvir Rahman')
        ->and($quote->items)->toHaveCount(1)
        ->and((float) $quote->items->first()->discount_percent)->toBe(10.0)
        ->and((float) $quote->items->first()->discount)->toBe(200.0)
        ->and((float) $quote->total)->toBe(1800.0);

    // Printable & PDF views
    $this->actingAs($admin, 'admin')->get(route('admin.quotes.print', $quote))->assertOk()->assertSee('Tanvir Rahman');
    $this->actingAs($admin, 'admin')->get(route('admin.quotes.pdf.view', $quote))->assertOk();

    // Duplicate quote
    $this->actingAs($admin, 'admin')->post(route('admin.quotes.duplicate', $quote))->assertRedirect();
    expect(Quote::where('subject', 'Enterprise PBX Setup Proposal')->count())->toBe(2);

    // Convert prospective client quote to invoice creates client & invoice automatically
    $this->actingAs($admin, 'admin')->post(route('admin.quotes.convert', $quote))->assertRedirect();
    $quote->refresh();
    expect($quote->client_id)->not->toBeNull()
        ->and($quote->status)->toBe('Accepted')
        ->and(Client::where('email', 'tanvir.prospect@example.test')->exists())->toBeTrue();
});

test('admin open ticket supports client context lookup, cc recipients, send_email toggle, and non-client tickets', function () {
    Mail::fake();
    $admin = Admin::factory()->create();
    $client = Client::factory()->create(['first_name' => 'Karim', 'last_name' => 'Hossain', 'email' => 'karim@example.test']);
    $service = Service::factory()->create(['client_id' => $client->id, 'domain' => 'pbx.karim.test']);
    $dept = TicketDepartment::factory()->create();

    // Live client context JSON endpoint for Related Services table
    $this->actingAs($admin, 'admin')
        ->getJson(route('admin.tickets.client-context', $client))
        ->assertOk()
        ->assertJsonFragment(['domain' => 'pbx.karim.test']);

    // Open ticket with Send Email = 0 (suppressed)
    $this->actingAs($admin, 'admin')
        ->post(route('admin.tickets.store'), [
            'client' => (string) $client->id,
            'name' => 'Karim Hossain',
            'email' => 'karim@example.test',
            'send_email' => '0',
            'cc' => 'ops@example.test, billing@example.test',
            'department_id' => $dept->id,
            'priority' => 'Medium',
            'related_service' => $service->id,
            'subject' => 'Silent Ticket',
            'message' => 'Opened without sending email.',
        ])
        ->assertRedirect();

    Mail::assertNothingQueued();
    $silentTicket = Ticket::where('title', 'Silent Ticket')->firstOrFail();
    expect($silentTicket->cc)->toBe('ops@example.test, billing@example.test');

    // Open ticket for a non-registered lead (Name + Email without client ID) with Send Email = 1
    $this->actingAs($admin, 'admin')
        ->post(route('admin.tickets.store'), [
            'client' => '',
            'name' => 'Walk-in Lead',
            'email' => 'lead@example.test',
            'send_email' => '1',
            'cc' => 'manager@example.test',
            'department_id' => $dept->id,
            'priority' => 'High',
            'subject' => 'Pre-sales Inquiry',
            'message' => '### Quote Follow-up' . "\n" . 'Please review your hosting options.',
        ])
        ->assertRedirect();

    $leadTicket = Ticket::where('title', 'Pre-sales Inquiry')->firstOrFail();
    expect($leadTicket->client_id)->toBeNull()
        ->and($leadTicket->name)->toBe('Walk-in Lead')
        ->and($leadTicket->email)->toBe('lead@example.test');

    Mail::assertQueued(TicketOpenedMail::class, fn ($m) => $m->hasTo('lead@example.test') && $m->hasCc('manager@example.test'));
});

test('all billing, quotes, and support sidebar navigation targets respond 200 ok', function () {
    $admin = Admin::factory()->create();

    // Verify Quotes create page renders with every WHMCS Billing/Quotes sidebar option
    $quoteCreateHtml = $this->actingAs($admin, 'admin')
        ->get(route('admin.quotes.create'))
        ->assertOk()
        ->assertSee('Transactions List')
        ->assertSee('Gateway Log')
        ->assertSee('Offline CC Processing')
        ->assertSee('Disputes')
        ->assertSee('List All Invoices')
        ->assertSee('List All Billable Items')
        ->assertSee('List All Quotes')
        ->assertSee('Create New Quote')
        ->assertSee('Add a Predefined Product')
        ->getContent();

    expect($quoteCreateHtml)->toContain('Quote for existing client:')
        ->and($quoteCreateHtml)->toContain('Quote for new client');

    // Verify Tickets create page renders with every WHMCS Support sidebar option
    $this->actingAs($admin, 'admin')
        ->get(route('admin.tickets.create'))
        ->assertOk()
        ->assertSee('Announcements')
        ->assertSee('Downloads')
        ->assertSee('Knowledgebase')
        ->assertSee('Support Tickets')
        ->assertSee('Open New Ticket')
        ->assertSee('Predefined Replies')
        ->assertSee('Awaiting Reply (')
        ->assertSee('Flagged Tickets (')
        ->assertSee('All Active Tickets (')
        ->assertSee('Network Issues')
        ->assertSee('- Open')
        ->assertSee('- Scheduled')
        ->assertSee('- Resolved')
        ->assertSee('Create New')
        ->assertSee('Please select a client to view the related services')
        ->assertSee('View All Services');

    // Hit every single sidebar link target to ensure zero 404/500 errors
    $urls = [
        route('admin.config.transactions'),
        route('admin.logs.gateway'),
        route('admin.billing.offline-cc'),
        route('admin.billing.disputes'),
        route('admin.invoices.index', ['status' => 'all']),
        route('admin.invoices.index', ['status' => 'paid']),
        route('admin.invoices.index', ['status' => 'draft']),
        route('admin.invoices.index', ['status' => 'unpaid']),
        route('admin.invoices.index', ['status' => 'overdue']),
        route('admin.invoices.index', ['status' => 'cancelled']),
        route('admin.invoices.index', ['status' => 'refunded']),
        route('admin.invoices.index', ['status' => 'collections']),
        route('admin.invoices.index', ['status' => 'payment-pending']),
        route('admin.config.billable-items'),
        route('admin.config.billable-items', ['status' => 'uninvoiced']),
        route('admin.config.billable-items', ['status' => 'recurring']),
        route('admin.config.billable-items', ['action' => 'add']),
        route('admin.quotes.index'),
        route('admin.quotes.index', ['status' => 'valid']),
        route('admin.quotes.index', ['status' => 'expired']),
        route('admin.config.announcements'),
        route('admin.config.downloads'),
        route('admin.config.knowledge-base'),
        route('admin.tickets.index'),
        route('admin.tickets.index', ['status' => 'any']),
        route('admin.tickets.index', ['status' => 'awaiting', 'search' => 'SSL', 'email' => 'test@example.com']),
        route('admin.tickets.index', ['status' => 'flagged']),
        route('admin.tickets.index', ['status' => 'active']),
        route('admin.orders.index', ['search' => '100']),
        route('admin.services.index', ['search' => 'example.com']),
        route('admin.domains.index', ['search' => 'example.com']),
        route('admin.config.predefined-replies'),
        route('admin.config.network-issues', ['status' => 'open']),
        route('admin.config.network-issues', ['status' => 'scheduled']),
        route('admin.config.network-issues', ['status' => 'resolved']),
        route('admin.config.network-issues', ['action' => 'create']),
    ];

    foreach ($urls as $url) {
        $this->actingAs($admin, 'admin')->get($url)->assertOk();
    }
});

test('whois lookup and all 17 utilities sidebar destinations render 200 ok with utilities sidebar intact', function () {
    $admin = Admin::factory()->create();

    $whoisHtml = $this->actingAs($admin, 'admin')
        ->get(route('admin.whois.index'))
        ->assertOk()
        ->assertSee('Domain WHOIS Lookup')
        ->assertSee('domaintolookup.com')
        ->assertSee('Lookup Domain')
        ->getContent();

    $expectedSidebarItems = [
        'Update WHMCS',
        'WHMCS Connect',
        'Automation Status',
        'Module Queue',
        'Registrar TLD Sync',
        'Email Campaigns',
        'Email Marketer',
        'Link Tracking',
        'Calendar',
        'To-Do List',
        'WHOIS Lookup',
        'Domain Resolver',
        'Integration Code',
        'Database Status',
        'System Cleanup',
        'PHP Info',
        'PHP Version Compatibility',
    ];

    foreach ($expectedSidebarItems as $label) {
        expect($whoisHtml)->toContain($label);
    }

    $utilityUrls = [
        route('admin.config.updates'),
        route('admin.utilities.connect'),
        route('admin.config.automation'),
        route('admin.utilities.module-queue'),
        route('admin.utilities.tld-sync'),
        route('admin.bulk.mass-email'),
        route('admin.utilities.email-marketer'),
        route('admin.utilities.link-tracking'),
        route('admin.calendar'),
        route('admin.config.todo'),
        route('admin.whois.index'),
        route('admin.utilities.domain-resolver'),
        route('admin.utilities.domain-resolver', ['check' => 1]),
        route('admin.utilities.integration-code'),
        route('admin.config.system-database'),
        route('admin.settings.log-retention'),
        route('admin.config.system-phpinfo'),
        route('admin.utilities.php-compat'),
    ];

    foreach ($utilityUrls as $url) {
        $this->actingAs($admin, 'admin')
            ->get($url)
            ->assertOk()
            ->assertSee('PHP Version Compatibility');
    }

    // Verify interactive CRUD on Email Marketer & Link Tracking
    $this->actingAs($admin, 'admin')
        ->post(route('admin.utilities.email-marketer.store'), [
            'name' => 'Welcome Follow-Up',
            'type' => 'client',
            'days' => 7,
            'criteria' => 'days_after_registration',
            'active' => 1,
        ])
        ->assertRedirect();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.utilities.link-tracking.store'), [
            'name' => 'Homepage Promo',
            'url' => 'https://example.com',
        ])
        ->assertRedirect();
});

test('all clients, products/services, service addons, cancellation requests, and affiliates sidebar navigation options render 200 ok', function () {
    $admin = Admin::factory()->create();

    $billingIdentityHtml = $this->actingAs($admin, 'admin')
        ->get(route('admin.clients.billing'))
        ->assertOk()
        ->getContent();

    $expectedClientSidebarItems = [
        'View/Search Clients',
        'Manage Users / Billing Identity',
        'Add New Client',
        'Mass Mail Tool',
        'List All Products/Services',
        '- Shared Hosting',
        '- Reseller Accounts',
        '- VPS/Servers',
        '- Other Services',
        'Service Addons',
        'Domain Registrations',
        'SSL Certificates',
        'Cancellation Requests',
        'Manage Affiliates',
        'WHOIS Lookup',
        'Domain Resolver',
        'Module Queue',
    ];

    foreach ($expectedClientSidebarItems as $label) {
        expect($billingIdentityHtml)->toContain($label);
    }

    $clientSectionUrls = [
        route('admin.clients.index'),
        route('admin.clients.billing'),
        route('admin.clients.create'),
        route('admin.services.index'),
        route('admin.services.index', ['type' => 'hostingaccount']),
        route('admin.services.index', ['type' => 'reselleraccount']),
        route('admin.services.index', ['type' => 'server']),
        route('admin.services.index', ['type' => 'other']),
        route('admin.services.index', ['hide_inactive' => 1]),
        route('admin.services.addons'),
        route('admin.domains.index'),
        route('admin.ssl.index'),
        route('admin.cancellation-requests.index'),
        route('admin.cancellation-requests.index', ['completed' => 1]),
        route('admin.affiliates.index'),
    ];

    foreach ($clientSectionUrls as $url) {
        $this->actingAs($admin, 'admin')
            ->get($url)
            ->assertOk()
            ->assertSee('List All Products/Services');
    }
});

test('whmcs system settings hub renders full-width layout and all 38 setup cards navigate to 200 ok pages', function () {
    $admin = Admin::factory()->create();

    $hubHtml = $this->actingAs($admin, 'admin')
        ->get(route('admin.config.system-settings'))
        ->assertOk()
        ->assertSee('System Settings')
        ->assertSee('Set up and configure your WHMCS installation.')
        ->assertSee('Click here to view the setup tasks')
        ->assertSee('All Settings')
        ->assertSee('Popularity')
        ->assertSee('Alphabetical')
        ->assertSee('Apps & Integrations')
        ->assertSee('User Management')
        ->assertSee('Products & Services')
        ->assertSee('API & Security')
        ->assertSee('MarketConnect')
        ->assertSee('NEW SERVICE')
        ->assertSee('Order Statuses')
        ->assertSee('Sign-In Integrations')
        ->assertSee('Fraud Protection')
        ->assertSee('Two-Factor Authentication')
        ->assertSee('Security Questions')
        ->assertSee('Application Links')
        ->assertSee('OpenID Connect')
        ->assertSee('Storage Settings')
        ->assertSee('Database Backups')
        ->getContent();

    // Global left sidebar must be suppressed on the System Settings hub page
    expect($hubHtml)->not->toContain('id="sidebar"');

    // Extract all 38 setup card URLs and assert every single destination returns 200 OK
    preg_match_all('/<a href="([^"]+)"\s+class="whmcs-ss-card"/', $hubHtml, $matches);
    $cardUrls = $matches[1] ?? [];
    expect(count($cardUrls))->toBe(38);

    foreach (array_unique($cardUrls) as $cardUrl) {
        $this->actingAs($admin, 'admin')
            ->get(html_entity_decode($cardUrl, ENT_QUOTES))
            ->assertOk();
    }

    // Exercise POST actions on new setup screens to ensure zero 500s on mutations
    $this->actingAs($admin, 'admin')
        ->post(route('admin.config.order-statuses.store'), [
            'title' => 'Awaiting KYC',
            'color' => '#2563EB',
            'show_pending' => 1,
            'sort_order' => 5,
        ])
        ->assertRedirect();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.config.security-questions.store'), [
            'question' => 'What was the name of your first pet?',
        ])
        ->assertRedirect();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.config.openid-connect.store'), [
            'name' => 'Panelica SSO',
            'description' => 'Internal panel login',
            'redirect_uris' => 'https://panel.softcents.test/oidc/callback',
        ])
        ->assertRedirect()
        ->assertSessionHas('generated_oidc_secret');

    $this->actingAs($admin, 'admin')
        ->post(route('admin.config.storage-settings.save'), [
            'downloads' => 'local',
            'ticket_attachments' => 'local',
            'invoices' => 'local',
            'email_attachments' => 'local',
            'backups' => 'local',
        ])
        ->assertRedirect();
});

test('manage admins enforces whmcs password confirmation gate before showing active and disabled administrators', function () {
    $role = \App\Models\AdminRole::factory()->fullAdmin()->create(['name' => 'Full Administrator']);
    $admin = Admin::factory()->create([
        'role_id' => $role->id,
        'username' => 'raju_staff_test',
        'first_name' => 'Raju',
        'last_name' => 'Admin',
        'password' => 'SuperSecretPass!99',
    ]);
    $colleague = Admin::factory()->create([
        'role_id' => $role->id,
        'username' => 'colleague101',
        'first_name' => 'Saiful',
        'last_name' => 'Shovon',
        'email' => 'saiful.colleague@example.test',
    ]);

    // 1. Unconfirmed visit renders WHMCS password confirmation gate + full 6-section Configuration sidebar, hiding admin rows
    $gateHtml = $this->actingAs($admin, 'admin')
        ->get(route('admin.config.admins'))
        ->assertOk()
        ->assertSee('Administrators')
        ->assertSee('Confirm password to continue')
        ->assertSee('You are entering an administrative area of WHMCS and must confirm your password to continue.')
        ->assertSee('Confirm Password')
        ->assertSee('Configuration')
        ->assertSee('Staff Management')
        ->assertSee('Administrator Users')
        ->assertSee('Two-Factor Authentication')
        ->assertSee('Order Statuses')
        ->assertSee('Database Backups')
        ->assertDontSee('saiful.colleague@example.test')
        ->getContent();

    expect($gateHtml)->toContain('name="password"');

    // 2. Unconfirmed direct POST to create admin is rejected by middleware
    $this->actingAs($admin, 'admin')
        ->post(route('admin.config.admins.store'), [
            'first_name' => 'Blocked',
            'last_name' => 'User',
            'username' => 'blocked_user',
            'email' => 'blocked@example.test',
            'password' => 'SecretPass123',
            'role_id' => $role->id,
        ])
        ->assertRedirect(route('admin.config.admins'));
    expect(Admin::where('username', 'blocked_user')->exists())->toBeFalse();

    // 3. Wrong password keeps gate locked and shows error
    $this->actingAs($admin, 'admin')
        ->post(route('admin.confirm-password'), [
            'password' => 'WrongPassword123',
            'intended' => route('admin.config.admins'),
        ])
        ->assertRedirect(route('admin.config.admins'))
        ->assertSessionHasErrors('password');

    // 4. Correct password confirms session and unlocks WHMCS Administrators management view
    $this->actingAs($admin, 'admin')
        ->post(route('admin.confirm-password'), [
            'password' => 'SuperSecretPass!99',
            'intended' => route('admin.config.admins'),
        ])
        ->assertRedirect(route('admin.config.admins'));

    $this->actingAs($admin, 'admin')
        ->withSession(['admin_password_confirmed_at' => time()])
        ->get(route('admin.config.admins'))
        ->assertOk()
        ->assertSee('Active Administrators')
        ->assertSee('Disabled Administrators')
        ->assertSee('Add New Administrator')
        ->assertSee('colleague101')
        ->assertSee('saiful.colleague@example.test');

    // 5. Add/Edit full-page WHMCS form renders cleanly and creates a new admin when confirmed
    $this->actingAs($admin, 'admin')
        ->withSession(['admin_password_confirmed_at' => time()])
        ->get(route('admin.config.admins', ['action' => 'manage']))
        ->assertOk()
        ->assertSee('Add New Administrator')
        ->assertSee('Please confirm your admin password to add or make changes to administrator account details.');

    $this->actingAs($admin, 'admin')
        ->withSession(['admin_password_confirmed_at' => time()])
        ->post(route('admin.config.admins.store'), [
            'role_id' => $role->id,
            'first_name' => 'Nadia',
            'last_name' => 'Islam',
            'email' => 'nadia.staff@example.test',
            'username' => 'nadia_staff',
            'password' => 'NadiaStrong!22',
            'password_confirmation' => 'NadiaStrong!22',
            'verify_current_admin_password' => '1',
            'current_admin_password' => 'SuperSecretPass!99',
        ])
        ->assertRedirect(route('admin.config.admins'));

    expect(Admin::where('username', 'nadia_staff')->exists())->toBeTrue();
});
