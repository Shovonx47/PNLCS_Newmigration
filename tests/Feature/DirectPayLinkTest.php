<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use App\Services\EmailTemplateService;
use App\Support\DirectPayLink;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Phase 3 - the login-free signed invoice link.
 *
 * The link is a bearer token for one invoice: the signature covers the invoice
 * and its owner, and the session it opens is checked against an allow-list of
 * invoice routes by LockToInvoice. These tests are about that boundary, not
 * about the invoice page itself.
 */
class DirectPayLinkTest extends TestCase
{
    private function createClientWithUser(string $email = ''): array
    {
        $email = $email ?: 'payer.'.uniqid().'@example.com';

        $user = User::forceCreate([
            'first_name' => 'Payer',
            'last_name' => 'One',
            'email' => $email,
            'password' => Hash::make('SecretPass123!'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $client = Client::forceCreate([
            'first_name' => 'Payer',
            'last_name' => 'One',
            'email' => $email,
            'phone_number' => '01711223344',
            'address1' => 'Banani Road 11',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'postcode' => '1213',
            'country' => 'BD',
            'status' => 'active',
        ]);

        $user->clients()->syncWithoutDetaching([$client->id => ['owner' => true, 'permissions' => null]]);

        return [$user, $client];
    }

    private function makeInvoice(Client $client, float $total = 500, string $status = 'unpaid'): Invoice
    {
        return Invoice::forceCreate([
            'client_id' => $client->id,
            'invoice_num' => 'INV-T'.random_int(100000, 999999),
            'date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'subtotal' => $total,
            'tax' => 0,
            'total' => $total,
            'status' => $status,
            'type' => 'vat',
        ]);
    }

    public function test_a_signed_link_opens_the_invoice_and_nothing_else(): void
    {
        [$user, $client] = $this->createClientWithUser();
        $invoice = $this->makeInvoice($client);

        $this->assertTrue(Route::has('pay.invoice'));

        $response = $this->get(DirectPayLink::url($invoice));

        $response->assertRedirect(route('client.invoices.show', $invoice));
        $this->assertAuthenticatedAs($user);
        $this->assertSame($invoice->id, DirectPayLink::lockedInvoiceId());

        // The invoice itself is reachable, with the invoice number on it.
        $this->get(route('client.invoices.show', $invoice))
            ->assertOk()
            ->assertSee($invoice->invoice_num);

        // Any other client page ends the temporary session.
        $this->get(route('client.home'))->assertRedirect(route('client.login'));
        $this->assertGuest();
        $this->assertSame(0, DirectPayLink::lockedInvoiceId());
    }

    public function test_another_invoice_is_refused_inside_a_locked_session(): void
    {
        [, $client] = $this->createClientWithUser();
        $invoice = $this->makeInvoice($client);
        $other = $this->makeInvoice($client, 700);

        $this->get(DirectPayLink::url($invoice))->assertRedirect();
        $this->assertSame($invoice->id, DirectPayLink::lockedInvoiceId());

        $this->get(route('client.invoices.show', $other))->assertRedirect(route('client.login'));
        $this->assertGuest();
    }

    public function test_a_tampered_or_foreign_signature_is_rejected(): void
    {
        [, $client] = $this->createClientWithUser();
        $invoice = $this->makeInvoice($client);
        $other = $this->makeInvoice($client, 900);

        // Wrong signature for this invoice.
        $this->get(route('pay.invoice', ['invoice' => $invoice, 'sig' => str_repeat('a', 32)]))
            ->assertForbidden();

        // A real signature, but for a different invoice.
        $this->get(route('pay.invoice', ['invoice' => $invoice, 'sig' => DirectPayLink::signature($other)]))
            ->assertForbidden();

        // No signature at all.
        $this->get(route('pay.invoice', $invoice))->assertForbidden();

        $this->assertGuest();
    }

    public function test_a_draft_invoice_link_is_not_honoured(): void
    {
        [, $client] = $this->createClientWithUser();
        $draft = $this->makeInvoice($client, 300, 'draft');

        $this->get(DirectPayLink::url($draft))->assertNotFound();
        $this->assertGuest();
    }

    public function test_an_expired_lock_is_a_normal_signed_out_visit(): void
    {
        [$user] = $this->createClientWithUser();

        $this->actingAs($user);
        $this->withSession([
            DirectPayLink::SESSION_INVOICE => 123,
            DirectPayLink::SESSION_UNTIL => now()->subMinute(),
        ]);

        $this->assertSame(0, DirectPayLink::lockedInvoiceId());

        // Nothing is confined any more: the dashboard opens.
        $this->get(route('client.home'))->assertOk();
    }

    public function test_a_normal_login_is_never_locked(): void
    {
        [$user, $client] = $this->createClientWithUser();
        $this->makeInvoice($client);

        $this->actingAs($user)->get(route('client.home'))->assertOk();
        $this->assertSame(0, DirectPayLink::lockedInvoiceId());
    }

    public function test_invoice_email_variables_carry_the_signed_link(): void
    {
        [, $client] = $this->createClientWithUser();
        $invoice = $this->makeInvoice($client);

        $vars = app(EmailTemplateService::class)->varsFor(['invoice' => $invoice]);

        $this->assertSame(DirectPayLink::url($invoice), $vars['direct_pay_url']);
        $this->assertSame(DirectPayLink::url($invoice), $vars['invoice_link']);
        $this->assertStringContainsString('/pay/'.$invoice->id.'?sig=', $vars['invoice_link']);
    }
}
