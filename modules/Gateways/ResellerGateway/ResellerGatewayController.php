<?php

namespace Modules\Gateways\ResellerGateway;

use App\Http\Controllers\Controller;
use App\Models\GatewayLog;
use App\Models\Invoice;
use App\Services\Module\ModuleRegistry;
use App\Services\PaymentService;
use App\Support\ClientPermissions;
use Illuminate\Http\Request;

class ResellerGatewayController extends Controller
{
    public function __construct(
        private readonly ModuleRegistry $registry,
        private readonly PaymentService $payments,
    ) {}

    public function capture(Request $request, Invoice $invoice)
    {
        $user = auth()->user();
        $client = $user?->clients()->whereKey($invoice->client_id)->first();
        abort_if(! $client || ! ClientPermissions::allows($user, $client, 'invoices'), 403);

        $invoice->refreshAddFundsRate();
        $due = $invoice->amountDue();
        if ($due <= 0) {
            return redirect()->route('client.invoices.show', $invoice);
        }

        $module = $this->registry->getGatewayModule(ResellerGatewayModule::NAME);
        if (! $module instanceof ResellerGatewayModule) {
            return redirect()->route('client.invoices.show', $invoice)
                ->with('error', 'Reseller Managed Gateway is currently unavailable.');
        }

        $result = $module->capture($invoice, $due);
        if (($result['success'] ?? false) && ! empty($result['checkout_url'])) {
            return redirect()->away((string) $result['checkout_url']);
        }

        return redirect()->route('client.invoices.show', $invoice)
            ->with('error', (string) ($result['message'] ?? 'Unable to start payment session.'));
    }

    public function handleReturn(Request $request, Invoice $invoice)
    {
        $ppId = trim((string) ($request->input('pp_id') ?? $request->query('pp_id') ?? ''));
        $module = $this->registry->getGatewayModule(ResellerGatewayModule::NAME);

        if ($ppId !== '' && $module instanceof ResellerGatewayModule) {
            $resolved = $module->resolveActiveResellerGateway($invoice);
            if ($resolved && $resolved['gateway']->gateway === 'piprapay') {
                $verified = $module->verifyPipraPayCharge($resolved['gateway'], $ppId);
                if (($verified['success'] ?? false)
                    && ((int) ($verified['invoice_id'] ?? 0) === 0 || (int) $verified['invoice_id'] === (int) $invoice->id)) {
                    $amount = $module->resolveSettlementAmount(
                        $invoice,
                        (float) ($verified['charged_amount'] ?? 0),
                        (array) ($verified['metadata'] ?? [])
                    );
                    if ($amount > 0) {
                        $this->payments->applyPayment(
                            $invoice,
                            ResellerGatewayModule::NAME,
                            (string) $verified['transaction_id'],
                            $amount
                        );
                    }

                    return redirect()->to(route('client.invoices.show', $invoice).'?payment=success');
                }
            }
        }

        $fresh = $invoice->fresh();
        if ($fresh && strtolower((string) $fresh->status) === 'paid') {
            return redirect()->to(route('client.invoices.show', $invoice).'?payment=success');
        }

        return redirect()->to(route('client.invoices.show', $invoice).'?payment=pending');
    }

    public function webhook(Request $request)
    {
        $module = $this->registry->getGatewayModule(ResellerGatewayModule::NAME);
        if (! $module instanceof ResellerGatewayModule) {
            return response('Module not active', 404);
        }

        $headerKey = $request->header('MHS-PIPRAPAY-API-KEY')
            ?? $request->header('mh-piprapay-api-key')
            ?? '';

        $payload = $request->json()->all() ?: $request->all();
        $payload['_header_key'] = $headerKey;
        $payload['_reseller_id'] = (int) ($request->query('reseller') ?? ($payload['metadata']['reseller_id'] ?? 0));

        $result = $module->processWebhook($payload);

        if ($result['unauthorized'] ?? false) {
            GatewayLog::create([
                'gateway' => ResellerGatewayModule::NAME,
                'data' => json_encode(['ip' => $request->ip()], JSON_UNESCAPED_SLASHES),
                'result' => 'unauthorized_webhook',
            ]);

            return response('Unauthorized', 401);
        }

        if (! ($result['success'] ?? false)) {
            return response('Verification failed', 400);
        }

        $invoice = Invoice::find((int) $result['invoice_id']);
        if (! $invoice) {
            return response('Invoice not found', 404);
        }

        $this->payments->applyPayment(
            $invoice,
            ResellerGatewayModule::NAME,
            (string) $result['transaction_id'],
            (float) $result['amount']
        );

        GatewayLog::create([
            'gateway' => ResellerGatewayModule::NAME,
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
