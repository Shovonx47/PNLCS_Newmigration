<?php

namespace Modules\Addons\DriveBackup;

use App\Contracts\AddonModuleInterface;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DriveBackupModule implements AddonModuleInterface
{
    public const NAME = 'gdrive_backup';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDisplayName(): string
    {
        return 'Google Drive Automated Backup';
    }

    public function getDescription(): string
    {
        return 'Automated daily database and private files backup to Google Drive via OAuth2 with encrypted credentials and retention pruning.';
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
        if (! Schema::hasTable('gdrive_backup_logs')) {
            return ['success' => false, 'message' => 'The gdrive_backup_logs table is missing. Run `php artisan migrate` first.'];
        }

        return ['success' => true, 'message' => 'Google Drive Automated Backup activated.'];
    }

    public function deactivate(): array
    {
        return ['success' => true, 'message' => 'Google Drive Automated Backup deactivated.'];
    }

    public function config(): array
    {
        return [
            ['name' => 'client_id', 'label' => 'Google OAuth2 Client ID', 'type' => 'text', 'default' => ''],
            ['name' => 'client_secret', 'label' => 'Google OAuth2 Client Secret', 'type' => 'password', 'default' => ''],
            ['name' => 'refresh_token', 'label' => 'Google OAuth2 Refresh Token', 'type' => 'password', 'default' => ''],
            ['name' => 'drive_folder_id', 'label' => 'Google Drive Folder ID', 'type' => 'text', 'default' => ''],
            ['name' => 'max_backups', 'label' => 'Retention Limit (Max Backups Kept)', 'type' => 'text', 'default' => '10'],
            ['name' => 'backup_db', 'label' => 'Include Database Dump', 'type' => 'yesno', 'default' => '1'],
            ['name' => 'backup_files', 'label' => 'Include Private Uploads', 'type' => 'yesno', 'default' => '1'],
            ['name' => 'keep_local', 'label' => 'Keep Local Copy After Upload', 'type' => 'yesno', 'default' => '0'],
        ];
    }

    public function sidebar(): array
    {
        return [
            [
                'label' => 'Google Drive Backup',
                'icon' => 'cloud-upload',
                'url' => '/admin/extensions/gdrive-backup',
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
