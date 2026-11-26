<?php

namespace Modules\Addons\AdvancedClientVerification;

use App\Contracts\AddonModuleInterface;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AdvancedClientVerificationModule implements AddonModuleInterface
{
    public const NAME = 'advanced_client_verification';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDisplayName(): string
    {
        return 'Advanced Client Verification (KYC)';
    }

    public function getDescription(): string
    {
        return 'Didit.me API v2 identity verification and manual KYC document review with private storage, checkout gating, 7-day deadline suspension, and audit logs.';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getAuthor(): string
    {
        return 'SoftCents Systems';
    }

    public function activate(): array
    {
        if (! Schema::hasTable('acv_verifications')) {
            return ['success' => false, 'message' => 'The acv_verifications table is missing. Run `php artisan migrate` first.'];
        }

        return ['success' => true, 'message' => 'Advanced Client Verification activated.'];
    }

    public function deactivate(): array
    {
        return ['success' => true, 'message' => 'Advanced Client Verification deactivated. Existing verification records remain intact.'];
    }

    public function config(): array
    {
        return [
            [
                'name' => 'verification_mode',
                'label' => 'Verification Mode (api_only / manual_only / hybrid)',
                'type' => 'select',
                'options' => [
                    'hybrid' => 'Hybrid (Automated + Manual Upload)',
                    'api_only' => 'Automated API Only (Didit)',
                    'manual_only' => 'Manual Document Upload Only',
                ],
                'default' => 'hybrid',
            ],
            [
                'name' => 'api_key',
                'label' => 'Didit API Key',
                'type' => 'password',
                'default' => '',
            ],
            [
                'name' => 'workflow_id',
                'label' => 'Didit Workflow ID',
                'type' => 'text',
                'default' => '',
            ],
            [
                'name' => 'webhook_secret',
                'label' => 'Didit Webhook Secret',
                'type' => 'password',
                'default' => '',
            ],
            [
                'name' => 'verification_requirement',
                'label' => 'Checkout Requirement',
                'type' => 'select',
                'options' => [
                    'disabled' => 'Disabled (No Checkout Block)',
                    'all_clients' => 'Require for All Orders',
                    'restricted_groups' => 'Require for Restricted Product Groups Only',
                ],
                'default' => 'disabled',
            ],
            [
                'name' => 'restricted_groups',
                'label' => 'Restricted Product Group IDs (comma-separated)',
                'type' => 'text',
                'default' => '',
            ],
            [
                'name' => 'require_selfie',
                'label' => 'Require Selfie Upload (Manual Mode)',
                'type' => 'yesno',
                'default' => '1',
            ],
            [
                'name' => 'require_document_back',
                'label' => 'Require Document Back Side (Manual Mode)',
                'type' => 'yesno',
                'default' => '1',
            ],
            [
                'name' => 'require_trade_license',
                'label' => 'Require Trade License for Business Accounts',
                'type' => 'yesno',
                'default' => '1',
            ],
            [
                'name' => 'business_types',
                'label' => 'Business Entity Types Requiring Trade License',
                'type' => 'text',
                'default' => 'sole_proprietorship,partnership,limited_company,pvt_ltd',
            ],
            [
                'name' => 'max_file_size',
                'label' => 'Maximum Upload Size (MB)',
                'type' => 'text',
                'default' => '5',
            ],
            [
                'name' => 'client_access',
                'label' => 'Enable Client Verification Portal',
                'type' => 'yesno',
                'default' => '1',
            ],
            [
                'name' => 'auto_approve',
                'label' => 'Auto-Approve Verified Webhook Decisions',
                'type' => 'yesno',
                'default' => '1',
            ],
            [
                'name' => 'enable_service_suspension',
                'label' => 'Suspend Unverified Services Past Deadline',
                'type' => 'yesno',
                'default' => '1',
            ],
            [
                'name' => 'verification_deadline_days',
                'label' => 'Verification Deadline (Days)',
                'type' => 'text',
                'default' => '7',
            ],
            [
                'name' => 'enable_daily_reminders',
                'label' => 'Send Daily Verification Reminders',
                'type' => 'yesno',
                'default' => '1',
            ],
        ];
    }

    public function sidebar(): array
    {
        return [
            [
                'label' => 'Client Verification (KYC)',
                'icon' => 'shield-check',
                'url' => '/admin/extensions/client-verification',
            ],
        ];
    }

    public function upgrade(string $fromVersion): array
    {
        return ['success' => true, 'message' => "Upgraded from {$fromVersion}"];
    }

    public function output(Request $request): string
    {
        return '';
    }

    public static function isEnabled(): bool
    {
        try {
            return Setting::get('addon_'.self::NAME.'_active', '0') === '1';
        } catch (\Throwable) {
            return false;
        }
    }
}
