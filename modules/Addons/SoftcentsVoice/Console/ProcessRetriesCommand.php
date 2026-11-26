<?php

namespace Modules\Addons\SoftcentsVoice\Console;

use Illuminate\Console\Command;
use Modules\Addons\SoftcentsVoice\Models\VoiceLog;
use Modules\Addons\SoftcentsVoice\Models\VoiceRetry;
use Modules\Addons\SoftcentsVoice\Services\VoiceClient;
use Modules\Addons\SoftcentsVoice\Services\VoiceDispatcher;

class ProcessRetriesCommand extends Command
{
    protected $signature = 'pnlcs:voice-retries {--limit=50}';

    protected $description = 'Retry the voice calls that were refused or fell outside the calling window';

    public function handle(VoiceDispatcher $dispatcher, VoiceClient $client): int
    {
        $settings = $dispatcher->settings();

        if (! $settings->retry_enabled) {
            $this->info('Voice retries are switched off.');

            return self::SUCCESS;
        }

        $due = VoiceRetry::where('status', 'pending')
            ->where('next_attempt_at', '<=', now())
            ->orderBy('next_attempt_at')
            ->limit((int) $this->option('limit'))
            ->get();

        $sent = 0;
        $failed = 0;

        foreach ($due as $retry) {
            // Never ring outside the window: the retry waits for the next one.
            if (! $dispatcher->windowOpen()) {
                $retry->forceFill(['next_attempt_at' => $dispatcher->nextOpenAt()])->save();

                continue;
            }

            if ($retry->attempt >= $retry->max_attempts) {
                $retry->forceFill(['status' => 'failed', 'completed_at' => now()])->save();
                $failed++;

                continue;
            }

            $result = $client->sendCall($retry->phone, (string) $retry->audio_id, (string) $retry->caller_id);

            VoiceLog::create([
                'client_id' => $retry->client_id,
                'phone' => $retry->phone,
                'event_key' => $retry->event_key,
                'audio_id' => $retry->audio_id,
                'caller_id' => $retry->caller_id,
                'call_id' => $result['call_id'] ?: null,
                'status' => $result['success'] ? 'sent' : 'failed',
                'reason' => $result['success'] ? 'Retry attempt '.($retry->attempt + 1).'.' : mb_substr($result['error'], 0, 280),
                'attempt' => $retry->attempt + 1,
            ]);

            if ($result['success']) {
                $retry->forceFill(['status' => 'sent', 'completed_at' => now(), 'last_status_reason' => null])->save();
                $sent++;
            } else {
                $attempt = $retry->attempt + 1;
                $retry->forceFill([
                    'attempt' => $attempt,
                    'last_status_reason' => mb_substr($result['error'], 0, 280),
                    'status' => $attempt >= $retry->max_attempts ? 'failed' : 'pending',
                    'next_attempt_at' => now()->addMinutes(max(1, (int) $retry->interval_minutes)),
                    'completed_at' => $attempt >= $retry->max_attempts ? now() : null,
                ])->save();
                $failed += $result['success'] ? 0 : 1;
            }
        }

        $this->info("Voice retries: {$sent} sent, {$failed} still failing.");

        return self::SUCCESS;
    }
}
