<?php

use App\Http\Middleware\RedirectToInstaller;
use App\Models\ActivityLog;
use App\Models\Admin;
use App\Models\AdminLog;
use App\Models\AdminRole;
use App\Models\Client;
use App\Models\TicketMailLog;

beforeEach(function () {
    $this->withoutMiddleware(RedirectToInstaller::class);
    $this->role = AdminRole::factory()->fullAdmin()->create();
    $this->admin = Admin::factory()->create(['role_id' => $this->role->id]);
});

test('system activity log renders WHMCS sidebar, current time, collapsible search, 5 columns, client link, and None fallback', function () {
    $client = Client::factory()->create(['first_name' => 'Zane', 'last_name' => 'Porter']);

    ActivityLog::create([
        'date' => now()->subMinutes(5),
        'client_id' => null,
        'description' => 'ACV: Frequent cron check completed',
        'user' => null,
        'ip_address' => '10.0.0.15',
    ]);

    ActivityLog::create([
        'date' => now()->subMinutes(2),
        'client_id' => $client->id,
        'description' => 'Updated client billing details',
        'user' => 'raju',
        'ip_address' => '192.168.1.50',
    ]);

    $html = $this->actingAs($this->admin, 'admin')
        ->get(route('admin.config.activity-log'))
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain('System Activity Log')
        ->toContain('Current Time')
        ->toContain('Activity Log')
        ->toContain('Admin Log')
        ->toContain('Module Log')
        ->toContain('Email Message Log')
        ->toContain('Ticket Mail Import Log')
        ->toContain('WHOIS Lookup Log')
        ->toContain('Search/Filter')
        ->toContain('Records Found')
        ->toContain('Jump to Page:')
        ->toContain('Log Entry')
        ->toContain('ACV: Frequent cron check completed')
        ->toContain('System/Automated')
        ->toContain('None')
        ->toContain('Zane Porter')
        ->toContain(e(route('admin.clients.show', $client)))
        ->toContain('192.168.1.50');
});

test('system activity log paginates at 50 per page and filters by description, user, ip, and date', function () {
    for ($i = 1; $i <= 55; $i++) {
        ActivityLog::create([
            'date' => now()->subMinutes(60 - $i),
            'description' => "Log entry number {$i}",
            'user' => $i % 2 === 0 ? 'raju' : null,
            'ip_address' => $i === 42 ? '203.0.113.42' : '127.0.0.1',
        ]);
    }

    $page1 = $this->actingAs($this->admin, 'admin')
        ->get(route('admin.config.activity-log'))
        ->assertOk()
        ->getContent();

    expect($page1)
        ->toContain('Showing 1 to 50')
        ->toContain('Next Page »')
        ->toContain('Log entry number 55');

    $page2 = $this->actingAs($this->admin, 'admin')
        ->get(route('admin.config.activity-log', ['page' => 2]))
        ->assertOk()
        ->getContent();

    expect($page2)
        ->toContain('Showing 51 to')
        ->toContain('« Previous Page')
        ->toContain('Log entry number 1');

    $filtered = $this->actingAs($this->admin, 'admin')
        ->get(route('admin.config.activity-log', [
            'search' => 'number 42',
            'user' => 'raju',
            'ip' => '203.0.113.42',
            'date' => now()->toDateString(),
        ]))
        ->assertOk()
        ->getContent();

    expect($filtered)
        ->toContain('Log entry number 42')
        ->not->toContain('Log entry number 55');
});

test('all logs sidebar routes render 200 OK cleanly and support filtering', function () {
    AdminLog::create([
        'admin_username' => 'raju',
        'login_time' => now(),
        'ip_address' => '127.0.0.1',
        'session_id' => 'sess-xyz-123',
    ]);

    TicketMailLog::create([
        'date' => now(),
        'to' => 'support@softcents.com',
        'name' => 'Client Demo',
        'email' => 'client@example.test',
        'subject' => 'Billing question',
        'message' => 'Please check my invoice',
        'status' => 'Imported',
    ]);

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.logs.admin', ['username' => 'raju']))
        ->assertOk()
        ->assertSee('raju')
        ->assertSee('sess-xyz-123');

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.logs.ticket-mail', ['search' => 'Billing']))
        ->assertOk()
        ->assertSee('Billing question')
        ->assertSee('Imported');

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.logs.whois'))
        ->assertOk()
        ->assertSee('WHOIS Lookup Log');

    $this->actingAs($this->admin, 'admin')->get(route('admin.logs.module'))->assertOk();
    $this->actingAs($this->admin, 'admin')->get(route('admin.logs.email'))->assertOk();
});
