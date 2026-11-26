<?php

namespace Modules\Gateways\UddoktaPay;

use App\Http\Controllers\Controller;
use App\Models\GatewayLog;
use App\Models\Invoice;
use App\Services\Module\ModuleRegistry;
use App\Services\PaymentService;
use App\Support\ClientPermissions;
use Illuminate\Http\Request;

class UddoktaPayController extends Controller
{
    public function __construct(
        private readonly ModuleRegistry $registry,
        private readonly PaymentService $payments,
    ) {}

    public function captureLocal(Request $request, Invoice $invoice)
    {
        return $this->captureFor($request, $invoice, UddoktaPayModule::NAME);
    }

    public function captureInternational(Request $request, Invoice $invoice)
    {
        return $this->captureFor($request, $invoice, 'uddoktapayinternational');
    }

    public function returnLocal(Request $request, Invoice $invoice)
    {
        return $this->handleReturnFor($request, $invoice, UddoktaPayModule::NAME);
    }

    public function returnInternational(Request $request, Invoice $invoice)
    {
        return $this->handleReturnFor($request, $invoice, 'uddoktapayinternational');
    }

    public function webhookLocal(Request $request)
    {
        return $this->webhookFor($request, UddoktaPayModule::NAME);
    }

    public function webhookInternational(Request $request)
    {
        return $this->webhookFor($request, 'uddoktapayinternational');
    }

    private function captureFor(Request $request, Invoice $invoice, string $gatewayKey)
    {
        $user = auth()->user();
        $client = $user?->clients()->whereKey($invoice->client_id)->first();
        abort_if(! $client || ! ClientPermissions::allows($user, $client, 'invoices'), 403);

        $invoice->refreshAddFundsRate();
        $due = $invoice->amountDue();
        if ($due <= 0) {
            return redirect()->route('client.invoices.show', $invoice);
        }

        $module = $this->registry->getGatewayModule($gatewayKey);
        if (! $module instanceof AbstractUddoktaPayModule) {
            return redirect()->route('client.invoices.show', $invoice)
                ->with('error', 'Selected payment method is currently unavailable.');
        }

        $result = $module->capture($invoice, $due);
        if (($result['success'] ?? false) && ! empty($result['checkout_url'])) {
            return redirect()->away((string) $result['checkout_url']);
        }

        return redirect()->route('client.invoices.show', $invoice)
            ->with('error', (string) ($result['message'] ?? 'Unable to start payment session.'));
    }

    private function handleReturnFor(Request $request, Invoice $invoice, string $gatewayKey)
    {
        $upInvoiceId = trim((string) ($request->query('invoice_id') ?? $request->input('invoice_id') ?? ''));

        $module = $this->registry->getGatewayModule($gatewayKey);
        if ($upInvoiceId !== '' && $module instanceof AbstractUddoktaPayModule) {
            $verified = $module->verifyPaymentReference($upInvoiceId);
            if ($verified['success'] ?? false) {
                $verifiedLocalId = (int) ($verified['invoice_id'] ?? 0);
                // Prevent cross-invoice replay: metadata invoice_id MUST match the route invoice!
                if ($verifiedLocalId === (int) $invoice->id) {
                    $settlement = $module->resolveSettlementAmount(
                        $invoice,
                        (float) ($verified['charged_amount'] ?? 0),
                        (array) ($verified['metadata'] ?? [])
                    );

                    if ($settlement !== null && $settlement > 0) {
                        $this->payments->applyPayment(
                            $invoice,
                            $gatewayKey,
                            (string) $verified['transaction_id'],
                            $settlement
                        );

                        GatewayLog::create([
                            'gateway' => $gatewayKey,
                            'data' => json_encode([
                                'invoice_id' => $invoice->id,
                                'transaction_id' => $verified['transaction_id'],
                                'amount' => $settlement,
                            ], JSON_UNESCAPED_SLASHES),
                            'result' => 'paid_on_return',
                        ]);

                        return redirect()->to(route('client.invoices.show', $invoice).'?payment=success');
                    }
                }
            }
        }

        $fresh = $invoice->fresh();
        if ($fresh && strtolower((string) $fresh->status) === 'paid') {
            return redirect()->to(route('client.invoices.show', $invoice).'?payment=success');
        }

        return redirect()->to(route('client.invoices.show', $invoice).'?payment=pending');
    }

    private function webhookFor(Request $request, string $gatewayKey)
    {
        $module = $this->registry->getGatewayModule($gatewayKey);
        if (! $module instanceof AbstractUddoktaPayModule) {
            return response('Gateway not active', 404);
        }

        $payload = $request->json()->all() ?: $request->all();
        $payload['_header_key'] = $request->header('RT-UDDOKTAPAY-API-KEY', '');

        $result = $module->processWebhook($payload);

        if ($result['unauthorized'] ?? false) {
            GatewayLog::create([
                'gateway' => $gatewayKey,
                'data' => json_encode(['ip' => $request->ip()], JSON_UNESCAPED_SLASHES),
                'result' => 'unauthorized_webhook',
            ]);

            return response('Unauthorized Action.', 401);
        }

        if (! ($result['success'] ?? false)) {
            GatewayLog::create([
                'gateway' => $gatewayKey,
                'data' => json_encode(['up_invoice_id' => $payload['invoice_id'] ?? null], JSON_UNESCAPED_SLASHES),
                'result' => 'webhook_not_completed',
            ]);

            return response('Ignored', 200);
        }

        $invoice = Invoice::find((int) $result['invoice_id']);
        if (! $invoice) {
            return response('Invoice not found', 404);
        }

        $this->payments->applyPayment(
            $invoice,
            $gatewayKey,
            (string) $result['transaction_id'],
            (float) $result['amount']
        );

        GatewayLog::create([
            'gateway' => $gatewayKey,
            'data' => json_encode([
                'invoice_id' => $invoice->id,
                'transaction_id' => $result['transaction_id'],
                'amount' => $result['amount'],
            ], JSON_UNESCAPED_SLASHES),
            'result' => 'paid',
        ]);

        return response('Success', 200);
    }
}
