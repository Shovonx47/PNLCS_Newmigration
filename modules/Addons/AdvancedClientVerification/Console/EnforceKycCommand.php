<?php

namespace Modules\Addons\AdvancedClientVerification\Console;

use Illuminate\Console\Command;
use Modules\Addons\AdvancedClientVerification\AdvancedClientVerificationModule;
use Modules\Addons\AdvancedClientVerification\Services\KycService;

class EnforceKycCommand extends Command
{
    protected $signature = 'pnlcs:acv-enforce';

    protected $description = 'Enforce KYC verification deadlines (suspend past-deadline services) and send daily reminders';

    public function handle(KycService $kyc): int
    {
        if (! AdvancedClientVerificationModule::isEnabled()) {
            $this->info('Advanced Client Verification addon is inactive; skipping.');

            return self::SUCCESS;
        }

        $result = $kyc->enforceDeadlinesAndReminders();

        $this->info("ACV enforcement complete: {$result['suspended_services']} service(s) suspended, {$result['reminders_sent']} reminder(s) recorded.");

        return self::SUCCESS;
    }
}
