<?php

namespace Modules\Addons\WhatsappPro\Services;

use App\Models\Client;
use App\Support\BdPhone;
use App\Support\NotificationVariables;
use Illuminate\Support\Facades\Log;
use Modules\Addons\WhatsappPro\Models\WaLog;
use Modules\Addons\WhatsappPro\Models\WaQueueItem;
use Modules\Addons\WhatsappPro\Models\WaSettings;
use Modules\Addons\WhatsappPro\Models\WaTemplate;

/**
 * Queue first, send later.
 *
 * The hook path only writes a row: a customer's order must not wait on a
 * WhatsApp gateway any more than it waits on an SMS gateway. The scheduled
 * command does the calls, records what happened, and retries what failed up to
 * the configured limit.
 */
class WaDispatcher
{
    public function enabled(): bool
    {
        $settings = WaSettings::current();

        return $settings->enabled && $settings->configured();
    }

    /**
     * @param  array<string, mixed>  $vars  the hook parameters
     */
    public function queueTemplate(string $name, array $vars = [], ?Client $client = null): ?WaQueueItem
    {
        $template = WaTemplate::where('name', $name)->where('active', true)->first();

        if (! $template || ! $this->enabled()) {
            return null;
        }

        $client ??= $this->clientFrom($vars);

        if ($template->type === 'admin') {
            // Operator templates go to the alert numbers, never to a customer.
            $numbers = WaSettings::current()->adminNumberList();
            $number = $numbers[0] ?? '';
        } else {
            $number = $client ? BdPhone::gateway($client->phone_number, $client->phone_prefix) : '';
        }

        if ($number === '') {
            return null;
        }

        $message = NotificationVariables::render(
            (string) $template->template,
            NotificationVariables::build($vars)
        );

        if (trim($message) === '') {
            return null;
        }

        return WaQueueItem::create([
            'client_id' => $client?->id,
            'number' => $number,
            'message' => $message,
            'type' => 'text',
            'template_name' => $template->name,
            'status' => 'pending',
        ]);
    }

    /** Put one free-form message on the queue (manual, bulk, test). */
    public function queueMessage(string $number, string $message, ?int $clientId = null, string $source = 'manual', ?string $mediaUrl = null, string $mediaType = 'document'): ?WaQueueItem
    {
        $to = BdPhone::gateway($number);

        if ($to === '') {
            return null;
        }

        return WaQueueItem::create([
            'client_id' => $clientId,
            'number' => $to,
            'message' => $message,
            'type' => $mediaUrl ? 'media' : 'text',
            'media_url' => $mediaUrl,
            'media_type' => $mediaUrl ? $mediaType : null,
            'template_name' => $source === 'manual' ? '' : null,
            'status' => 'pending',
        ]);
    }

    /**
     * Send what is waiting. Called by the scheduled command and by nothing
     * else, so a request never waits on the gateway.
     *
     * @return array{sent: int, failed: int}
     */
    public function processQueue(int $limit = 100): array
    {
        $settings = WaSettings::current();

        if (! $settings->enabled || ! $settings->configured()) {
            return ['sent' => 0, 'failed' => 0];
        }

        $client = new WhatsappClient($settings);
        $sent = 0;
        $failed = 0;

        $items = WaQueueItem::where('status', 'pending')->orderBy('id')->limit($limit)->get();

        foreach ($items as $item) {
            $result = $item->type === 'media' && $item->media_url
                ? $client->sendMedia($item->number, (string) $item->media_url, (string) ($item->media_type ?: 'document'), (string) $item->message, (bool) $item->view_once)
                : $client->sendText($item->number, (string) $item->message);

            $item->attempts = (int) $item->attempts + 1;
            $item->error = $result['success'] ? null : mb_substr($result['error'], 0, 480);

            if ($result['success']) {
                $item->status = 'sent';
                $item->processed_at = now();
                $sent++;
            } elseif ($item->attempts >= max(1, (int) $settings->max_retry)) {
                $item->status = 'failed';
                $item->processed_at = now();
                $failed++;
            }

            $item->save();

            WaLog::create([
                'client_id' => $item->client_id,
                'number' => $item->number,
                'message' => $item->message,
                'status' => $result['success'] ? 'Sent' : 'Failed',
                'type' => $item->type,
                'media_url' => $item->media_url,
                'source' => 'automatic',
                'template_name' => $item->template_name,
                'retry_count' => $item->attempts,
                'error' => $item->error,
                'last_retry_at' => now(),
            ]);
        }

        if ($failed > 0) {
            Log::warning("WhatsApp queue: {$failed} message(s) gave up after their attempts.");
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    /** Put a failed log back on the queue, for the admin screen's Retry. */
    public function retry(WaLog $log): WaQueueItem
    {
        $log->forceFill(['status' => 'pending', 'last_retry_at' => now()])->save();

        return WaQueueItem::create([
            'client_id' => $log->client_id,
            'number' => $log->number,
            'message' => $log->message,
            'type' => $log->type,
            'media_url' => $log->media_url,
            'template_name' => $log->template_name,
            'status' => 'pending',
        ]);
    }

    private function clientFrom(array $vars): ?Client
    {
        if (($vars['client'] ?? null) instanceof Client) {
            return $vars['client'];
        }

        foreach (['invoice', 'service', 'ticket', 'order'] as $key) {
            $model = $vars[$key] ?? null;
            $client = is_object($model) ? ($model->client ?? null) : null;

            if ($client instanceof Client) {
                return $client;
            }
        }

        return ($vars['user'] ?? null)?->clients()->first();
    }
}
