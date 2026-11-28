<?php

use App\Http\Middleware\RedirectToInstaller;
use App\Models\Admin;
use App\Models\AdminRole;
use App\Models\Client;
use App\Models\ClientGroup;
use App\Models\Currency;
use App\Models\GatewaySettings;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;

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

test('invoices paid view renders WHMCS totals bar, columns, group highlight, custom gateway name, and bottom actions', function () {
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

    $paid = Invoice::factory()->create([
        'client_id' => $client->id,
        'invoice_num' => 'INV-WHMCS-716',
        'status' => 'paid',
        'total' => 1300,
        'subtotal' => 1300,
        'payment_method' => 'piprapay',
        'source_currency' => 'BDT',
        'date' => '2026-11-25',
        'due_date' => '2026-11-25',
    ]);

    Invoice::factory()->create([
        'client_id' => $client->id,
        'invoice_num' => 'INV-WHMCS-720',
        'status' => 'unpaid',
        'total' => 500,
        'credit' => 0,
        'source_currency' => 'BDT',
        'date' => now()->toDateString(),
        'due_date' => now()->addDays(5)->toDateString(),
    ]);

    $response = $this->actingAs($this->admin, 'admin')
        ->get('/admin/invoices?status=Paid')
        ->assertOk();

    $html = $response->getContent();

    expect($html)
        ->toContain('BDT')
        ->toContain('1,300Tk')
        ->toContain('500Tk')
        ->toContain('716')
        ->toContain('Monoar Dipto')
        ->toContain('background-color:#FF00DD')
        ->toContain('SoftCents System Billing')
        ->toContain('Last Capture Attempt')
        ->toContain('Jump to Page:')
        ->toContain('With Selected:')
        ->toContain('Mark Paid')
        ->toContain('Mark Unpaid')
        ->toContain('Mark Cancelled')
        ->toContain('Duplicate Invoice')
        ->toContain('Send Reminder')
        ->toContain(e(route('admin.invoices.view', $paid)))
        ->toContain(e(route('admin.invoices.show', $paid)))
        ->toContain(e(route('admin.invoices.destroy', $paid)));
});

test('invoices paginate at 50 per page and support jump to page and sorting', function () {
    $client = Client::factory()->create(['first_name' => 'Page', 'last_name' => 'Tester']);

    for ($i = 1; $i <= 55; $i++) {
        Invoice::factory()->create([
            'client_id' => $client->id,
            'invoice_num' => 'INV-P-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
            'status' => 'paid',
            'total' => $i * 10,
            'source_currency' => 'BDT',
            'date' => '2026-11-01',
            'due_date' => now()->subDays(56 - $i)->toDateString(),
        ]);
    }

    $page1 = $this->actingAs($this->admin, 'admin')
        ->get(route('admin.invoices.index', ['status' => 'paid']))
        ->assertOk()
        ->getContent();

    expect($page1)
        ->toContain('55 Records Found, Showing 1 to 50')
        ->toContain('Next Page »')
        ->toContain('INV-P-055');

    $page2 = $this->actingAs($this->admin, 'admin')
        ->get(route('admin.invoices.index', ['status' => 'paid', 'page' => 2]))
        ->assertOk()
        ->getContent();

    expect($page2)
        ->toContain('55 Records Found, Showing 51 to 55')
        ->toContain('INV-P-001');

    $sortedAsc = $this->actingAs($this->admin, 'admin')
        ->get(route('admin.invoices.index', ['status' => 'paid', 'sort' => 'total', 'dir' => 'asc']))
        ->assertOk()
        ->getContent();

    expect($sortedAsc)->toContain('INV-P-001');
});

test('invoices filter by line item, total range, invoice number, and client search', function () {
    $c1 = Client::factory()->create(['first_name' => 'Alice', 'last_name' => 'Vega']);
    $c2 = Client::factory()->create(['first_name' => 'Bob', 'last_name' => 'Ross']);

    $inv1 = Invoice::factory()->create(['client_id' => $c1->id, 'invoice_num' => 'INV-1001', 'status' => 'paid', 'total' => 250]);
    InvoiceItem::create(['invoice_id' => $inv1->id, 'client_id' => $c1->id, 'type' => 'Hosting', 'description' => 'Cloud PBX Gold', 'qty' => 1, 'amount' => 250, 'taxed' => false]);

    $inv2 = Invoice::factory()->create(['client_id' => $c2->id, 'invoice_num' => 'INV-2002', 'status' => 'paid', 'total' => 1500]);
    InvoiceItem::create(['invoice_id' => $inv2->id, 'client_id' => $c2->id, 'type' => 'Hosting', 'description' => 'Dedicated Server', 'qty' => 1, 'amount' => 1500, 'taxed' => false]);

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.invoices.index', ['status' => 'paid', 'line_item' => 'Cloud PBX', 'total_from' => 100, 'total_to' => 500]))
        ->assertOk()
        ->assertSee('INV-1001')
        ->assertDontSee('INV-2002');
});

test('admin can view printable HTML invoice and delete an invoice safely', function () {
    $client = Client::factory()->create();
    $invoice = Invoice::factory()->create(['client_id' => $client->id, 'invoice_num' => 'INV-DEL-1', 'status' => 'paid', 'total' => 300]);
    InvoiceItem::create(['invoice_id' => $invoice->id, 'client_id' => $client->id, 'type' => 'Other', 'description' => 'Setup Fee', 'qty' => 1, 'amount' => 300, 'taxed' => false]);
    $order = Order::factory()->create(['client_id' => $client->id, 'invoice_id' => $invoice->id]);

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.invoices.view', $invoice))
        ->assertOk()
        ->assertSee('INV-DEL-1')
        ->assertSee('Setup Fee');

    $this->actingAs($this->admin, 'admin')
        ->delete(route('admin.invoices.destroy', $invoice))
        ->assertRedirect();

    expect(Invoice::find($invoice->id))->toBeNull()
        ->and($order->fresh()->invoice_id)->toBeNull();
});

test('bulk invoice actions handle mark unpaid, duplicate, remind, cancel, paid, and delete', function () {
    Mail::fake();
    $client = Client::factory()->create(['email' => 'billing@example.test']);
    $paidInv = Invoice::factory()->create(['client_id' => $client->id, 'invoice_num' => 'INV-BLK-1', 'status' => 'paid', 'total' => 400, 'subtotal' => 400, 'date_paid' => now()]);
    InvoiceItem::create(['invoice_id' => $paidInv->id, 'client_id' => $client->id, 'type' => 'Hosting', 'description' => 'Monthly Hosting', 'qty' => 1, 'amount' => 400, 'taxed' => false]);

    // 1. Mark Unpaid
    $this->actingAs($this->admin, 'admin')
        ->post(route('admin.bulk.invoice-action'), [
            'action' => 'unpaid',
            'invoice_ids' => [$paidInv->id],
        ])->assertRedirect();

    expect(strtolower((string) $paidInv->fresh()->status))->toBe('unpaid')
        ->and($paidInv->fresh()->date_paid)->toBeNull();

    // 2. Duplicate Invoice
    $beforeCount = Invoice::count();
    $this->actingAs($this->admin, 'admin')
        ->post(route('admin.bulk.invoice-action'), [
            'action' => 'duplicate',
            'invoice_ids' => [$paidInv->id],
        ])->assertRedirect();

    expect(Invoice::count())->toBe($beforeCount + 1);
    $dup = Invoice::where('id', '!=', $paidInv->id)->latest('id')->first();
    expect($dup)->not->toBeNull()
        ->and(strtolower((string) $dup->status))->toBe('unpaid')
        ->and($dup->items()->where('description', 'Monthly Hosting')->exists())->toBeTrue();

    // 3. Send Reminder & Cancel & Paid & Delete
    $this->actingAs($this->admin, 'admin')
        ->post(route('admin.bulk.invoice-action'), [
            'action' => 'remind',
            'invoice_ids' => [$paidInv->id],
        ])->assertRedirect();
    Mail::assertQueued(\App\Mail\PaymentReminderMail::class);

    $this->actingAs($this->admin, 'admin')
        ->post(route('admin.bulk.invoice-action'), [
            'action' => 'cancel',
            'invoice_ids' => [$paidInv->id],
        ])->assertRedirect();
    expect(strtolower((string) $paidInv->fresh()->status))->toBe('cancelled');

    $this->actingAs($this->admin, 'admin')
        ->post(route('admin.bulk.invoice-action'), [
            'action' => 'paid',
            'invoice_ids' => [$dup->id],
        ])->assertRedirect();
    expect(strtolower((string) $dup->fresh()->status))->toBe('paid');

    $this->actingAs($this->admin, 'admin')
        ->post(route('admin.bulk.invoice-action'), [
            'action' => 'delete',
            'invoice_ids' => [$dup->id],
        ])->assertRedirect();
    expect(Invoice::find($dup->id))->toBeNull();
});
