<?php

namespace Modules\Addons\DriveBackup\Console;

use Illuminate\Console\Command;
use Modules\Addons\DriveBackup\DriveBackupModule;
use Modules\Addons\DriveBackup\Services\DriveBackupService;

class RunDriveBackupCommand extends Command
{
    protected $signature = 'pnlcs:gdrive-backup {--type=full : full, db, or files}';

    protected $description = 'Create encrypted-transit database/files archive and upload to Google Drive';

    public function handle(DriveBackupService $service): int
    {
        if (! DriveBackupModule::isEnabled()) {
            $this->info('Google Drive Backup addon is inactive; skipping.');

            return self::SUCCESS;
        }

        $type = in_array($this->option('type'), ['full', 'db', 'files'], true) ? (string) $this->option('type') : 'full';
        $res = $service->runBackup($type, 'cron');

        if (! $res['success']) {
            $this->error($res['message']);

            return self::FAILURE;
        }

        $this->info($res['message']);

        return self::SUCCESS;
    }
}
