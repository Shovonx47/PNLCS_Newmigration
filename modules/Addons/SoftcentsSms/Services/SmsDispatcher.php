<?php

namespace Modules\Addons\SoftcentsSms\Services;

use App\Models\Client;
use App\Support\BdPhone;
use App\Support\NotificationVariables;
use Modules\Addons\SoftcentsSms\Models\SmsLog;
use Modules\Addons\SoftcentsSms\Models\SmsSetting;
use Modules\Addons\SoftcentsSms\Models\SmsTemplate;

/**
 * Turns a hook event into one SMS.
 *
 * The template is looked up by the name the legacy addon used (InvoiceCreated,
 * InvoicePaid, AfterModuleSuspend...), so the operator's existing wording and
 * the switches they already set keep working. A template that is switched off
 * sends nothing; a missing template sends nothing.
 */
class SmsDispatcher
{
    public function __construct(private ?SmsClient $client = null)
    {
        $this->client ??= new SmsClient;
    }

    public function enabled(): bool
    {
        return (bool) SmsSetting::current()->configured();
    }

    /**
     * @param  array<string, mixed>  $vars  the hook parameters
     */
    public function sendTemplate(string $name, array $vars = [], ?Client $client = null, ?string $to = null): ?SmsLog
    {
        $settings = SmsSetting::current();
        $template = SmsTemplate::where('name', $name)->where('active', true)->first();

        if (! $template || ! $settings->configured()) {
            return null;
        }

        // Most hooks name a model, not a customer: the invoice, service or
        // ticket it is about is what leads back to the number to text.
        if (! $client instanceof Client) {
            foreach (['invoice', 'service', 'ticket', 'order'] as $key) {
                $model = $vars[$key] ?? null;
                $candidate = is_object($model) ? ($model->client ?? null) : null;

                if ($candidate instanceof Client) {
                    $client = $candidate;
                    break;
                }
            }
        }

        $variables = NotificationVariables::build($vars);
        $message = NotificationVariables::render((string) $template->template, $variables);

        if (trim($message) === '') {
            return null;
        }

        $number = $to;

        if ($number === null) {
            // An admin template goes to the operator's alert numbers, not to a
            // customer's phone. When no numbers are set it sends nothing rather
            // than falling back to the customer.
            if ($template->type === 'admin') {
                if (! $settings->admin_alerts) {
                    return null;
                }

                $numbers = $settings->adminNumberList();

                if ($numbers === []) {
                    return null;
                }

                $number = $numbers[0];
            } else {
                $number = $client
                    ? BdPhone::gateway($client->phone_number, $client->phone_prefix)
                    : '';
            }
        }

        if ($number === '') {
            return null;
        }

        $log = SmsLog::create([
            'client_id' => $client?->id ?? ($vars['client']->id ?? null),
            'to_number' => $number,
            'template_name' => $template->name,
            'message' => $message,
            'status' => 'pending',
        ]);

        $this->deliver($log);

        return $log;
    }

    /** Send one free-form message (the admin screen's "send a test"). */
    public function sendNow(string $number, string $message, ?int $clientId = null, string $templateName = ''): SmsLog
    {
        $log = SmsLog::create([
            'client_id' => $clientId,
            'to_number' => BdPhone::gateway($number) ?: $number,
            'template_name' => $templateName,
            'message' => $message,
            'status' => 'pending',
        ]);

        $this->deliver($log);

        return $log->refresh();
    }

    private function deliver(SmsLog $log): void
    {
        $result = $this->client->send($log->to_number, (string) $log->message);

        $log->forceFill([
            'status' => $result['success'] ? 'sent' : 'failed',
            'error' => $result['success'] ? null : mb_substr($result['error'], 0, 480),
            'http_code' => $result['http_code'] ?: null,
        ])->save();
    }
}
