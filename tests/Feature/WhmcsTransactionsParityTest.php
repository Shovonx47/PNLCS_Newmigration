<?php

use App\Http\Middleware\RedirectToInstaller;
use App\Models\Admin;
use App\Models\AdminRole;
use App\Models\Client;
use App\Models\ClientGroup;
use App\Models\Currency;
use App\Models\GatewaySettings;
use App\Models\Invoice;
use App\Models\Transaction;

beforeEach(function () {
    $this->withoutMiddleware(RedirectToInstaller::class);
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

test('transactions page renders WHMCS chart, 3 KPI cards, 2 tabs, 8 columns, group tags, trans ID, and compact money', function () {
    $group = ClientGroup::create([
        'name' => 'PBX',
        'color' => '#FF00DD',
        'discount_percent' => 0,
    ]);
    $client = Client::factory()->create([
        'first_name' => 'Monoar',
        'last_name' => 'Dipto',
        'group_id' => $group->id,
    ]);
    GatewaySettings::updateOrCreate(
        ['gateway' => 'piprapay', 'setting' => 'display_name'],
        ['value' => 'SoftCents System Billing']
    );
    $invoice = Invoice::factory()->create([
        'client_id' => $client->id,
        'invoice_num' => 'INV-WHMCS-716',
        'status' => 'paid',
        'total' => 500,
    ]);

    $tx = Transaction::create([
        'client_id' => $client->id,
        'invoice_id' => $invoice->id,
        'date' => now()->toDateString(),
        'gateway' => 'piprapay',
        'description' => 'Invoice Payment',
        'transaction_id' => '182342643060269571893036701',
        'amount_in' => 500,
        'fees' => 10,
        'amount_out' => 0,
    ]);

    // Older than 30 days should be hidden on default Past 30 Days view, visible when within=all
    Transaction::create([
        'client_id' => $client->id,
        'date' => now()->subDays(45)->toDateString(),
        'gateway' => 'piprapay',
        'description' => 'Old Sept Payment',
        'amount_in' => 999,
        'fees' => 0,
        'amount_out' => 0,
    ]);

    $html = $this->actingAs($this->admin, 'admin')
        ->get(route('admin.config.transactions'))
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain('Search/Filter')
        ->toContain('Add Transaction')
        ->toContain('Net Revenue (BDT)')
        ->toContain('Total Income')
        ->toContain('Total Fees')
        ->toContain('Total Expenditure')
        ->toContain('500Tk')
        ->toContain('10Tk')
        ->toContain('0Tk')
        ->toContain('Monoar Dipto')
        ->toContain('background-color:#FF00DD')
        ->toContain('SoftCents System Billing')
        ->toContain('#716')
        ->toContain('Trans ID: 182342643060269571893036701')
        ->toContain('Jump to Page:')
        ->toContain(e(route('admin.config.transactions.update', $tx)))
        ->toContain(e(route('admin.config.transactions.destroy', $tx)))
        ->not->toContain('Old Sept Payment');

    $allHtml = $this->actingAs($this->admin, 'admin')
        ->get(route('admin.config.transactions', ['within' => 'all']))
        ->assertOk()
        ->getContent();

    expect($allHtml)->toContain('Old Sept Payment');
});

test('transactions support search filter and full store, update, delete CRUD', function () {
    $client = Client::factory()->create(['first_name' => 'Rajaul', 'last_name' => 'Rimon']);

    // Store
    $this->actingAs($this->admin, 'admin')
        ->post(route('admin.config.transactions.store'), [
            'client_id' => $client->id,
            'date' => now()->toDateString(),
            'gateway' => 'banktransfer',
            'description' => 'Manual wire deposit',
            'transaction_id' => 'WIRE-9001',
            'amount_in' => 1250,
            'fees' => 25,
            'amount_out' => 0,
        ])->assertRedirect(route('admin.config.transactions'));

    $tx = Transaction::where('transaction_id', 'WIRE-9001')->first();
    expect($tx)->not->toBeNull()
        ->and((float) $tx->amount_in)->toBe(1250.0);

    // Search/Filter by transaction_id & client
    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.config.transactions', ['client' => 'Rajaul', 'transaction_id' => 'WIRE-9001']))
        ->assertOk()
        ->assertSee('Manual wire deposit')
        ->assertSee('1,250Tk');

    // Update
    $this->actingAs($this->admin, 'admin')
        ->put(route('admin.config.transactions.update', $tx), [
            'client_id' => $client->id,
            'date' => now()->toDateString(),
            'gateway' => 'banktransfer',
            'description' => 'Updated wire deposit',
            'transaction_id' => 'WIRE-9001',
            'amount_in' => 1400,
            'fees' => 0,
            'amount_out' => 0,
        ])->assertRedirect(route('admin.config.transactions'));

    expect($tx->fresh()->description)->toBe('Updated wire deposit')
        ->and((float) $tx->fresh()->amount_in)->toBe(1400.0);

    // Delete
    $this->actingAs($this->admin, 'admin')
        ->delete(route('admin.config.transactions.destroy', $tx))
        ->assertRedirect(route('admin.config.transactions'));

    expect(Transaction::find($tx->id))->toBeNull();
});
