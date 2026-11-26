<?php

namespace Modules\Addons\ScrollingNotice;

use App\Contracts\AddonModuleInterface;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ScrollingNoticeModule implements AddonModuleInterface
{
    public function getName(): string
    {
        return 'scrolling_notice';
    }

    public function getDisplayName(): string
    {
        return 'Scrolling Notice Manager';
    }

    public function getDescription(): string
    {
        return 'Scheduled top announcement marquee with login-state targeting, priority ordering, link target, and click-to-copy promo code badge.';
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
        if (! Schema::hasTable('scrolling_notices')) {
            return ['success' => false, 'message' => 'The scrolling_notices table is missing. Run `php artisan migrate` first.'];
        }

        return ['success' => true, 'message' => 'Scrolling Notice Manager activated.'];
    }

    public function deactivate(): array
    {
        return ['success' => true, 'message' => 'Scrolling Notice Manager deactivated.'];
    }

    public function config(): array
    {
        return [];
    }

    public function sidebar(): array
    {
        return [
            [
                'label' => 'Scrolling Notices',
                'icon' => 'megaphone',
                'url' => '/admin/extensions/scrolling-notices',
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
            return Setting::get('addon_scrolling_notice_active', '0') === '1';
        } catch (\Throwable) {
            return false;
        }
    }
}
