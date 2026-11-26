<?php

namespace Modules\Addons\AdvancedClientVerification\Services;

use App\Enums\ServiceStatus;
use App\Models\AddonSetting;
use App\Models\Cart;
use App\Models\Client;
use App\Models\Product;
use App\Models\Service;
use App\Services\ProvisioningService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Addons\AdvancedClientVerification\AdvancedClientVerificationModule;
use Modules\Addons\AdvancedClientVerification\Models\AcvActivityLog;
use Modules\Addons\AdvancedClientVerification\Models\AcvDocument;
use Modules\Addons\AdvancedClientVerification\Models\AcvReminder;
use Modules\Addons\AdvancedClientVerification\Models\AcvVerification;

class KycService
{
    public const SUSPENSION_REASON = 'Identity Verification Deadline Exceeded';

    public const ALLOWED_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/pdf' => 'pdf',
    ];

    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'pdf'];

    /**
     * @return array<string, string>
     */
    public function settings(): array
    {
        $defaults = [
            'verification_mode' => 'hybrid',
            'api_key' => '',
            'workflow_id' => '',
            'webhook_secret' => '',
            'verification_requirement' => 'disabled',
            'restricted_groups' => '',
            'require_selfie' => '1',
            'require_document_back' => '1',
            'require_trade_license' => '1',
            'business_types' => 'sole_proprietorship,partnership,limited_company,pvt_ltd',
            'max_file_size' => '5',
            'client_access' => '1',
            'auto_approve' => '1',
            'enable_service_suspension' => '1',
            'verification_deadline_days' => '7',
            'enable_daily_reminders' => '1',
        ];

        return array_merge($defaults, AddonSetting::getForAddon(AdvancedClientVerificationModule::NAME));
    }

    public function flagEnabled(string $key): bool
    {
        $val = strtolower(trim((string) ($this->settings()[$key] ?? '0')));

        return in_array($val, ['1', 'on', 'true', 'yes'], true);
    }

    public function diditClient(): DiditClient
    {
        $cfg = $this->settings();

        return new DiditClient(
            (string) ($cfg['api_key'] ?? ''),
            (string) ($cfg['workflow_id'] ?? ''),
            (string) ($cfg['webhook_secret'] ?? ''),
        );
    }

    public function allowsApi(): bool
    {
        $mode = strtolower((string) ($this->settings()['verification_mode'] ?? 'hybrid'));

        return in_array($mode, ['api_only', 'hybrid'], true) && $this->diditClient()->isConfigured();
    }

    public function allowsManual(): bool
    {
        $mode = strtolower((string) ($this->settings()['verification_mode'] ?? 'hybrid'));

        return in_array($mode, ['manual_only', 'hybrid'], true) || ! $this->diditClient()->isConfigured();
    }

    /**
     * @return list<string>
     */
    public function businessTypesRequiringLicense(): array
    {
        $raw = (string) ($this->settings()['business_types'] ?? '');

        return array_values(array_filter(array_map(
            static fn (string $s) => strtolower(trim($s)),
            explode(',', $raw)
        )));
    }

    public function isVerified(int $clientId): bool
    {
        return AcvVerification::where('client_id', $clientId)
            ->where('status', 'verified')
            ->exists();
    }

    public function requiresCheckoutVerification(Cart $cart, ?int $clientId): bool
    {
        $cfg = $this->settings();
        $requirement = strtolower(trim((string) ($cfg['verification_requirement'] ?? 'disabled')));

        if ($requirement === 'disabled') {
            return false;
        }

        if ($clientId && $this->isVerified($clientId)) {
            return false;
        }

        $cartData = is_string($cart->data) ? (json_decode($cart->data, true) ?: []) : [];
        $items = (array) ($cartData['items'] ?? []);

        if ($items === []) {
            return false;
        }

        if ($requirement === 'all_clients') {
            return true;
        }

        if ($requirement === 'restricted_groups') {
            $restrictedIds = array_values(array_filter(array_map(
                'intval',
                preg_split('/[\s,]+/', (string) ($cfg['restricted_groups'] ?? '')) ?: []
            )));

            if ($restrictedIds === []) {
                return false;
            }

            $productIds = [];
            foreach ($items as $item) {
                if (($item['type'] ?? 'product') !== 'domain' && ! empty($item['product_id'])) {
                    $productIds[] = (int) $item['product_id'];
                }
            }

            if ($productIds === []) {
                return false;
            }

            return Product::whereIn('id', array_unique($productIds))
                ->whereIn('group_id', $restrictedIds)
                ->exists();
        }

        return false;
    }

    /**
     * Validate and store manual KYC documents on the private local disk
     * (`storage/app/private/kyc/{client_id}/`).
     *
     * @param  array<string, mixed>  $meta
     * @param  array<string, UploadedFile>  $files
     */
    public function storeManualSubmission(Client $client, array $meta, array $files): AcvVerification
    {
        $maxBytes = max(1, min(10, (int) ($this->settings()['max_file_size'] ?? 5))) * 1024 * 1024;

        $validatedFiles = [];
        foreach ($files as $type => $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                throw ValidationException::withMessages([$type => 'Uploaded file is invalid.']);
            }

            if ($file->getSize() > $maxBytes) {
                throw ValidationException::withMessages([$type => 'File exceeds the maximum allowed upload size.']);
            }

            $clientExt = strtolower((string) $file->getClientOriginalExtension());
            if (! in_array($clientExt, self::ALLOWED_EXTENSIONS, true)) {
                throw ValidationException::withMessages([$type => 'Only JPG, PNG, and PDF files are permitted.']);
            }

            $realPath = $file->getRealPath();
            $detectedMime = $realPath ? (string) mime_content_type($realPath) : '';
            if (! isset(self::ALLOWED_MIMES[$detectedMime]) || ! $this->matchesMagicBytes($realPath, $detectedMime)) {
                throw ValidationException::withMessages([$type => 'File content does not match an allowed document format (JPG, PNG, or PDF).']);
            }

            // TODO(security): Integrate external sandboxed antivirus scanning and PDF Content Disarm & Reconstruction (CDR) before permanent storage when host AV/CDR infrastructure is provisioned.
            $validatedFiles[$type] = [
                'file' => $file,
                'mime' => $detectedMime,
                'ext' => self::ALLOWED_MIMES[$detectedMime],
                'original' => basename((string) $file->getClientOriginalName()),
                'size' => (int) $file->getSize(),
            ];
        }

        return DB::transaction(function () use ($client, $meta, $validatedFiles) {
            $verification = AcvVerification::firstOrNew(['client_id' => $client->id]);
            $verification->fill([
                'status' => 'pending',
                'verification_method' => 'manual',
                'document_type' => (string) ($meta['document_type'] ?? 'national_id'),
                'document_number' => (string) ($meta['document_number'] ?? ''),
                'business_type' => ($meta['business_type'] ?? null) ?: null,
                'trade_license_number' => ($meta['trade_license_number'] ?? null) ?: null,
                'decline_reason' => null,
            ]);
            $verification->save();

            foreach ($validatedFiles as $type => $info) {
                $existing = AcvDocument::where('verification_id', $verification->id)
                    ->where('file_type', $type)
                    ->get();

                foreach ($existing as $oldDoc) {
                    if ($oldDoc->storage_path && Storage::disk('local')->exists($oldDoc->storage_path)) {
                        Storage::disk('local')->delete($oldDoc->storage_path);
                    }
                    $oldDoc->delete();
                }

                $uuidName = Str::uuid()->toString().'.'.$info['ext'];
                $relativeDir = 'kyc/'.$client->id;
                $storedPath = $info['file']->storeAs($relativeDir, $uuidName, 'local');

                AcvDocument::create([
                    'verification_id' => $verification->id,
                    'client_id' => $client->id,
                    'file_type' => $type,
                    'original_name' => Str::limit(preg_replace('/[^\w.\- ]+/u', '_', $info['original']) ?: ('document.'.$info['ext']), 200, ''),
                    'storage_path' => $storedPath,
                    'file_size' => $info['size'],
                    'mime_type' => $info['mime'],
                    'status' => 'pending',
                ]);
            }

            AcvActivityLog::record(
                $client->id,
                'manual_submission',
                'Manual KYC documents submitted ('.implode(', ', array_keys($validatedFiles)).').'
            );

            return $verification->fresh(['documents']);
        });
    }

    public function approve(
        AcvVerification $verification,
        ?int $adminId = null,
        ?string $adminName = null,
        string $source = 'admin'
    ): AcvVerification {
        $suspendedIds = array_values(array_filter(array_map('intval', (array) ($verification->acv_suspended_service_ids ?? []))));

        if ($verification->status === 'verified' && $suspendedIds === []) {
            return $verification;
        }

        DB::transaction(function () use ($verification, $adminId, $adminName, $source) {
            $verification->update([
                'status' => 'verified',
                'decline_reason' => null,
                'verified_at' => $verification->verified_at ?? now(),
                'verified_by_admin_id' => $adminId ?? $verification->verified_by_admin_id,
            ]);

            AcvDocument::where('verification_id', $verification->id)
                ->where('status', 'pending')
                ->update(['status' => 'approved']);

            AcvActivityLog::record(
                $verification->client_id,
                'verified',
                "Verification approved via {$source}.",
                $adminId,
                $adminName
            );
        });

        if ($suspendedIds !== []) {
            $provisioning = app(ProvisioningService::class);
            $remaining = [];

            foreach (Service::whereIn('id', $suspendedIds)->where('client_id', $verification->client_id)->get() as $svc) {
                if (strtolower((string) $svc->status) !== ServiceStatus::Suspended->value) {
                    continue;
                }
                if ($svc->suspension_reason && $svc->suspension_reason !== self::SUSPENSION_REASON) {
                    continue;
                }

                if ($svc->server_id && $provisioning->resolveModule($svc)) {
                    $res = $provisioning->unsuspendAccount($svc);
                    if (! ($res['success'] ?? false)) {
                        $remaining[] = $svc->id;
                    }
                } else {
                    $svc->update([
                        'status' => ServiceStatus::Active->value,
                        'suspension_date' => null,
                        'suspension_reason' => null,
                    ]);
                }
            }

            $verification->update(['acv_suspended_service_ids' => $remaining ?: null]);
        }

        return $verification->fresh();
    }

    public function decline(
        AcvVerification $verification,
        string $reason,
        ?int $adminId = null,
        ?string $adminName = null,
        string $source = 'admin'
    ): AcvVerification {
        $reason = trim($reason) ?: 'Verification could not be approved with the provided details.';

        DB::transaction(function () use ($verification, $reason, $adminId, $adminName, $source) {
            $verification->update([
                'status' => 'failed',
                'decline_reason' => $reason,
                'verified_at' => null,
            ]);

            AcvDocument::where('verification_id', $verification->id)
                ->where('status', 'pending')
                ->update(['status' => 'rejected']);

            AcvActivityLog::record(
                $verification->client_id,
                'declined',
                "Verification declined via {$source}: {$reason}",
                $adminId,
                $adminName
            );
        });

        return $verification->fresh();
    }

    /**
     * Enforce the verification deadline (suspending unverified services after N days)
     * and send daily verification reminders once per day.
     *
     * @return array{suspended_services: int, reminders_sent: int}
     */
    public function enforceDeadlinesAndReminders(): array
    {
        $suspendedServices = 0;
        $remindersSent = 0;
        $deadlineDays = max(1, (int) ($this->settings()['verification_deadline_days'] ?? 7));
        $cutoffDate = now()->subDays($deadlineDays)->startOfDay();
        $provisioning = app(ProvisioningService::class);

        $verifiedClientIds = AcvVerification::where('status', 'verified')->pluck('client_id')->all();

        if ($this->flagEnabled('enable_service_suspension')) {
            $services = Service::with(['client.group'])
                ->where('status', ServiceStatus::Active->value)
                ->when($verifiedClientIds !== [], fn ($q) => $q->whereNotIn('client_id', $verifiedClientIds))
                ->where(function ($q) use ($cutoffDate) {
                    $q->whereDate('registration_date', '<=', $cutoffDate->toDateString())
                        ->orWhere(fn ($sq) => $sq->whereNull('registration_date')->where('created_at', '<=', $cutoffDate));
                })
                ->get();

            foreach ($services as $service) {
                $client = $service->client;
                if (! $client) {
                    continue;
                }
                if (($client->override_auto_suspend ?? false) || ($client->group?->suspend_exempt ?? false)) {
                    continue;
                }

                $ok = false;
                if ($service->server_id && $provisioning->resolveModule($service)) {
                    $res = $provisioning->suspendAccount($service, self::SUSPENSION_REASON);
                    $ok = (bool) ($res['success'] ?? false);
                } else {
                    $service->update([
                        'status' => ServiceStatus::Suspended->value,
                        'suspension_date' => now(),
                        'suspension_reason' => self::SUSPENSION_REASON,
                    ]);
                    $ok = true;
                }

                if ($ok) {
                    $verification = AcvVerification::firstOrCreate(
                        ['client_id' => $client->id],
                        ['status' => 'not_started', 'verification_method' => 'api']
                    );
                    $held = array_values(array_unique([
                        ...array_map('intval', (array) ($verification->acv_suspended_service_ids ?? [])),
                        (int) $service->id,
                    ]));
                    $verification->update(['acv_suspended_service_ids' => $held]);
                    AcvActivityLog::record(
                        $client->id,
                        'deadline_suspended',
                        "Service #{$service->id} suspended after {$deadlineDays}-day verification deadline."
                    );
                    $suspendedServices++;
                }
            }
        }

        if ($this->flagEnabled('enable_daily_reminders')) {
            $today = now()->toDateString();
            $alreadyReminded = AcvReminder::whereDate('sent_on', $today)->pluck('client_id')->all();

            $clientsToRemind = Client::where('status', 'active')
                ->when($verifiedClientIds !== [], fn ($q) => $q->whereNotIn('id', $verifiedClientIds))
                ->when($alreadyReminded !== [], fn ($q) => $q->whereNotIn('id', $alreadyReminded))
                ->whereHas('services', fn ($sq) => $sq->whereIn('status', [ServiceStatus::Active->value, ServiceStatus::Suspended->value]))
                ->get();

            foreach ($clientsToRemind as $client) {
                AcvReminder::firstOrCreate(['client_id' => $client->id, 'sent_on' => $today]);

                if (filled($client->email)) {
                    try {
                        $portalUrl = url('/client/verification');
                        Mail::raw(
                            "Hello {$client->first_name},\n\nPlease complete your account identity verification within {$deadlineDays} days of service activation to avoid service interruption:\n{$portalUrl}\n\nThank you,\n".company_name(),
                            function ($msg) use ($client) {
                                $msg->to($client->email)->subject('Action Required: Complete Account Verification');
                            }
                        );
                    } catch (\Throwable $e) {
                        Log::warning('KYC reminder email dispatch failed', ['client_id' => $client->id, 'error' => $e->getMessage()]);
                    }
                }

                AcvActivityLog::record($client->id, 'reminder_sent', 'Daily verification reminder recorded.');
                $remindersSent++;
            }
        }

        return [
            'suspended_services' => $suspendedServices,
            'reminders_sent' => $remindersSent,
        ];
    }

    /**
     * Escape leading formula-triggering characters (`=`, `+`, `-`, `@`, `\t`, `\r`)
     * before writing untrusted strings into CSV exports.
     */
    public static function csvSafe(?string $value): string
    {
        $val = (string) ($value ?? '');
        if ($val !== '' && in_array($val[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$val;
        }

        return $val;
    }

    private function matchesMagicBytes(?string $path, string $expectedMime): bool
    {
        if (! $path || ! is_file($path)) {
            return false;
        }

        $fh = @fopen($path, 'rb');
        if (! $fh) {
            return false;
        }
        $header = (string) fread($fh, 8);
        fclose($fh);

        return match ($expectedMime) {
            'image/jpeg' => str_starts_with($header, "\xFF\xD8\xFF"),
            'image/png' => str_starts_with($header, "\x89PNG\r\n\x1a\n"),
            'application/pdf' => str_starts_with($header, '%PDF-'),
            default => false,
        };
    }
}
