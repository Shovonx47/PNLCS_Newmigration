<?php

namespace Modules\Addons\SupportPin\Services;

use App\Models\Client;
use Illuminate\Support\Facades\Hash;
use Modules\Addons\SupportPin\Models\SupportPin;
use Modules\Addons\SupportPin\Models\SupportPinLog;

class SupportPinService
{
    /**
     * Return active 6-digit Support PIN for a client, generating a fresh 24h PIN when absent/expired/forced.
     */
    public function getOrGenerate(int $clientId, bool $forceNew = false): SupportPin
    {
        $existing = SupportPin::where('client_id', $clientId)->first();

        if (! $forceNew && $existing && $existing->expires_at && $existing->expires_at->isFuture() && filled($existing->pin_encrypted)) {
            return $existing;
        }

        $pin = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        return SupportPin::updateOrCreate(
            ['client_id' => $clientId],
            [
                'pin_hash' => Hash::make($pin),
                'pin_encrypted' => $pin,
                'expires_at' => now()->addHours(24),
            ]
        );
    }

    /**
     * Verify a 6-digit PIN submitted by staff (optionally scoped to a client ID or email).
     */
    public function verify(string $submittedPin, ?int $clientId = null, ?int $adminId = null): ?Client
    {
        $cleanPin = preg_replace('/\D+/', '', $submittedPin) ?: '';
        if (strlen($cleanPin) !== 6) {
            SupportPinLog::create([
                'client_id' => $clientId,
                'admin_id' => $adminId,
                'result' => 'invalid_format',
                'ip_address' => request()?->ip(),
            ]);

            return null;
        }

        $candidates = SupportPin::with('client')
            ->when($clientId, fn ($q) => $q->where('client_id', $clientId))
            ->where('expires_at', '>', now())
            ->get();

        foreach ($candidates as $record) {
            $storedPlain = (string) ($record->pin_encrypted ?? '');
            $matched = ($storedPlain !== '' && hash_equals($storedPlain, $cleanPin))
                || Hash::check($cleanPin, (string) $record->pin_hash);

            if ($matched && $record->client) {
                SupportPinLog::create([
                    'client_id' => $record->client_id,
                    'admin_id' => $adminId,
                    'result' => 'verified',
                    'ip_address' => request()?->ip(),
                ]);

                return $record->client;
            }
        }

        SupportPinLog::create([
            'client_id' => $clientId,
            'admin_id' => $adminId,
            'result' => 'mismatch_or_expired',
            'ip_address' => request()?->ip(),
        ]);

        return null;
    }
}
