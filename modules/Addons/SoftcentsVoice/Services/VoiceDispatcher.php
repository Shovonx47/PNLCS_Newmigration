<?php

namespace Modules\Addons\SoftcentsVoice\Services;

use App\Models\Client;
use App\Support\BdPhone;
use Carbon\Carbon;
use Modules\Addons\SoftcentsVoice\Models\VoiceEvent;
use Modules\Addons\SoftcentsVoice\Models\VoiceGroupOverride;
use Modules\Addons\SoftcentsVoice\Models\VoiceLog;
use Modules\Addons\SoftcentsVoice\Models\VoiceRetry;
use Modules\Addons\SoftcentsVoice\Models\VoiceSettings;

/**
 * When a call may be placed, to whom, with which recording.
 *
 * Three rules, all from the legacy addon and all worth keeping:
 *  - nothing rings outside the operator's calling window (09:00-21:00 Dhaka by
 *    default) - a payment reminder at 3am costs a customer;
 *  - a product group may use its own recording and caller id;
 *  - a call that the gateway refused, or that was due outside the window, is
 *    tried again on an interval, a fixed number of times.
 *
 * What is new: a call that was skipped is logged with the reason, so "why did
 * nobody call this customer" has an answer on the screen instead of in a cron
 * mail.
 */
class VoiceDispatcher
{
    public function settings(): VoiceSettings
    {
        return VoiceSettings::current();
    }

    public function windowOpen(?Carbon $at = null): bool
    {
        $settings = $this->settings();
        $now = ($at ?? now())->copy()->setTimezone($settings->timezone ?: 'Asia/Dhaka');

        $start = $now->copy()->setTimeFromTimeString($settings->calling_start ?: '09:00');
        $end = $now->copy()->setTimeFromTimeString($settings->calling_end ?: '21:00');

        return $now->betweenIncluded($start, $end);
    }

    /** The next moment the window opens, in the operator's timezone. */
    public function nextOpenAt(?Carbon $at = null): Carbon
    {
        $settings = $this->settings();
        $tz = $settings->timezone ?: 'Asia/Dhaka';
        $now = ($at ?? now())->copy()->setTimezone($tz);
        $start = $now->copy()->setTimeFromTimeString($settings->calling_start ?: '09:00');

        return $now->lessThan($start) ? $start : $start->addDay();
    }

    /**
     * @param  array<string, mixed>  $vars  the hook parameters
     * @param  array<string, mixed>  $meta  extra facts kept on a retry row
     */
    public function dispatch(string $eventKey, array $vars = [], ?Client $client = null, array $meta = []): ?VoiceLog
    {
        $settings = $this->settings();
        $event = VoiceEvent::where('event_key', $eventKey)->first();

        if (! $event || ! $event->is_enabled) {
            return null;
        }

        $client ??= $this->clientFrom($vars);
        $phone = $client ? BdPhone::gateway($client->phone_number, $client->phone_prefix) : '';

        if ($phone === '') {
            return VoiceLog::create([
                'client_id' => $client?->id,
                'phone' => '',
                'event_key' => $eventKey,
                'status' => 'skipped',
                'reason' => 'No phone number on the account.',
            ]);
        }

        [$audioId, $callerId, $groupId] = $this->resolve($event, $vars);

        if ($audioId === '') {
            return VoiceLog::create([
                'client_id' => $client?->id,
                'phone' => $phone,
                'event_key' => $eventKey,
                'caller_id' => $callerId,
                'status' => 'skipped',
                'reason' => 'No recording chosen for this event.',
            ]);
        }

        if ($settings->master_paused) {
            return VoiceLog::create([
                'client_id' => $client?->id,
                'invoice_id' => $vars['invoice']->id ?? null,
                'phone' => $phone,
                'event_key' => $eventKey,
                'audio_id' => $audioId,
                'caller_id' => $callerId,
                'status' => 'skipped',
                'reason' => 'Voice dispatch is paused.',
            ]);
        }

        if (! $this->windowOpen()) {
            return $this->logAndSchedule(
                $eventKey, $client, $phone, $audioId, $callerId, $groupId,
                'skipped', 'Outside the calling window.', $vars, $meta, $this->nextOpenAt()
            );
        }

        return $this->call($eventKey, $client, $phone, $audioId, $callerId, $groupId, 1, $vars, $meta);
    }

    /** Place one call, log it, and queue a retry if it was refused. */
    public function call(string $eventKey, ?Client $client, string $phone, string $audioId, string $callerId, ?int $groupId, int $attempt, array $vars = [], array $meta = [], ?int $invoiceId = null): VoiceLog
    {
        $settings = $this->settings();
        $result = (new VoiceClient($settings))->sendCall($phone, $audioId, $callerId);

        $log = VoiceLog::create([
            'client_id' => $client?->id,
            'invoice_id' => $invoiceId ?? ($vars['invoice']->id ?? null),
            'phone' => $phone,
            'event_key' => $eventKey,
            'audio_id' => $audioId,
            'caller_id' => $callerId,
            'call_id' => $result['call_id'] ?: null,
            'status' => $result['success'] ? 'sent' : 'failed',
            'reason' => $result['success'] ? null : mb_substr($result['error'], 0, 280),
            'attempt' => $attempt,
        ]);

        if (! $result['success'] && $settings->retry_enabled) {
            $this->scheduleRetry($eventKey, $client, $phone, $audioId, $callerId, $groupId, $attempt, $result['error'], $meta);
        }

        return $log;
    }

    private function scheduleRetry(string $eventKey, ?Client $client, string $phone, string $audioId, string $callerId, ?int $groupId, int $attempt, string $reason, array $meta): VoiceRetry
    {
        $settings = $this->settings();

        return VoiceRetry::create([
            'client_id' => $client?->id,
            'phone' => $phone,
            'event_key' => $eventKey,
            'group_id' => $groupId,
            'audio_id' => $audioId,
            'caller_id' => $callerId,
            'attempt' => $attempt,
            'max_attempts' => max(1, (int) $settings->max_attempts),
            'interval_minutes' => max(1, (int) $settings->retry_interval_minutes),
            'status' => 'pending',
            'last_status_reason' => mb_substr($reason, 0, 280),
            'next_attempt_at' => now()->addMinutes(max(1, (int) $settings->retry_interval_minutes)),
            'meta' => $meta === [] ? null : json_encode($meta),
        ]);
    }

    private function logAndSchedule(string $eventKey, ?Client $client, string $phone, string $audioId, string $callerId, ?int $groupId, string $status, string $reason, array $vars, array $meta, ?Carbon $next): VoiceLog
    {
        $log = VoiceLog::create([
            'client_id' => $client?->id,
            'invoice_id' => $vars['invoice']->id ?? null,
            'phone' => $phone,
            'event_key' => $eventKey,
            'audio_id' => $audioId,
            'caller_id' => $callerId,
            'status' => $status,
            'reason' => $reason,
        ]);

        if ($this->settings()->retry_enabled) {
            $retry = $this->scheduleRetry($eventKey, $client, $phone, $audioId, $callerId, $groupId, 1, $reason, $meta);

            if ($next) {
                $retry->forceFill(['next_attempt_at' => $next])->save();
            }
        }

        return $log;
    }

    /**
     * A group's own recording wins over the event's default.
     *
     * @return array{0: string, 1: string, 2: ?int}
     */
    private function resolve(VoiceEvent $event, array $vars): array
    {
        $groupId = null;
        $service = $vars['service'] ?? null;

        if (is_object($service) && isset($service->product)) {
            $groupId = $service->product?->group_id;
        }

        $audioId = (string) ($event->audio_id ?? '');
        $callerId = (string) ($event->caller_id ?: $this->settings()->caller_id);

        if ($groupId) {
            $override = VoiceGroupOverride::where('group_id', $groupId)->where('event_key', $event->event_key)->first();

            if ($override && $override->is_enabled) {
                $audioId = (string) ($override->audio_id ?: $audioId);
                $callerId = (string) ($override->caller_id ?: $callerId);
            }
        }

        return [$audioId, $callerId, $groupId];
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
