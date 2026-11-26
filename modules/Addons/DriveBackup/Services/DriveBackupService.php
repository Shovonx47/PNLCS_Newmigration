<?php

namespace Modules\Addons\DriveBackup\Services;

use App\Models\AddonSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Addons\DriveBackup\DriveBackupModule;
use Modules\Addons\DriveBackup\Models\DriveBackupLog;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Process\Process;
use ZipArchive;

class DriveBackupService
{
    public const OAUTH_TOKEN_URL = 'https://oauth2.googleapis.com/token';

    public const DRIVE_UPLOAD_URL = 'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart';

    public const DRIVE_FILES_URL = 'https://www.googleapis.com/drive/v3/files';

    private const ALLOWED_MYSQLDUMP_BINARIES = [
        'C:\\xampp\\mysql\\bin\\mysqldump.exe',
        '/usr/bin/mysqldump',
        '/usr/local/bin/mysqldump',
    ];

    /**
     * @return array<string, string>
     */
    public function settings(): array
    {
        $defaults = [
            'client_id' => '',
            'client_secret' => '',
            'refresh_token' => '',
            'drive_folder_id' => '',
            'max_backups' => '10',
            'backup_db' => '1',
            'backup_files' => '1',
            'keep_local' => '0',
        ];

        return array_merge($defaults, AddonSetting::getForAddon(DriveBackupModule::NAME));
    }

    public function flagEnabled(string $key): bool
    {
        return in_array(strtolower(trim((string) ($this->settings()[$key] ?? '0'))), ['1', 'on', 'true', 'yes'], true);
    }

    public function isConfigured(): bool
    {
        $cfg = $this->settings();

        return trim($cfg['client_id']) !== ''
            && trim($cfg['client_secret']) !== ''
            && trim($cfg['refresh_token']) !== '';
    }

    public function buildConsentUrl(string $redirectUri): ?string
    {
        $clientId = trim((string) ($this->settings()['client_id'] ?? ''));
        if ($clientId === '') {
            return null;
        }

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/drive.file',
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);
    }

    public function exchangeAuthorizationCode(string $code, string $redirectUri): string
    {
        $cfg = $this->settings();
        if (trim($cfg['client_id']) === '' || trim($cfg['client_secret']) === '') {
            throw new \RuntimeException('Save OAuth2 Client ID and Client Secret first before exchanging an authorization code.');
        }

        $res = Http::asForm()->timeout(20)->post(self::OAUTH_TOKEN_URL, [
            'code' => trim($code),
            'client_id' => trim($cfg['client_id']),
            'client_secret' => trim($cfg['client_secret']),
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]);

        if (! $res->successful()) {
            throw new \RuntimeException('Google OAuth2 code exchange failed with HTTP '.$res->status().'.');
        }

        $refreshToken = trim((string) ($res->json('refresh_token') ?? ''));
        if ($refreshToken === '') {
            throw new \RuntimeException('Google did not return a refresh_token. Re-authorize using prompt=consent.');
        }

        AddonSetting::setSetting(DriveBackupModule::NAME, 'refresh_token', $refreshToken);

        return $refreshToken;
    }

    /**
     * Build backup archive, upload to Google Drive over strict TLS, and enforce retention.
     *
     * @return array{success: bool, log: DriveBackupLog, message: string}
     */
    public function runBackup(string $type = 'full', string $triggeredBy = 'cron'): array
    {
        $log = DriveBackupLog::create([
            'type' => $type,
            'status' => 'running',
            'triggered_by' => $triggeredBy,
        ]);

        if (! $this->isConfigured()) {
            $msg = 'Google Drive OAuth2 credentials are not configured.';
            $log->update(['status' => 'failed', 'message' => $msg]);

            return ['success' => false, 'log' => $log->fresh(), 'message' => $msg];
        }

        $backupDir = storage_path('app/private/backups');
        File::ensureDirectoryExists($backupDir);
        $archiveName = 'pnlcs-backup-'.now()->format('Y-m-d-His').'-'.Str::lower(Str::random(6)).'.zip';
        $zipPath = $backupDir.DIRECTORY_SEPARATOR.$archiveName;
        $sqlTempPath = $backupDir.DIRECTORY_SEPARATOR.'db-'.Str::uuid()->toString().'.sql';

        try {
            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Failed to create local backup archive.');
            }

            if ($type !== 'files' && $this->flagEnabled('backup_db')) {
                $this->writeDatabaseDump($sqlTempPath);
                $zip->addFile($sqlTempPath, 'database.sql');
            }

            if ($type !== 'db' && $this->flagEnabled('backup_files')) {
                $kycDir = storage_path('app/private/kyc');
                if (is_dir($kycDir)) {
                    $this->addDirectoryToZip($zip, $kycDir, 'private/kyc');
                }
            }

            $zip->addFromString('manifest.json', json_encode([
                'generated_at' => now()->toIso8601String(),
                'type' => $type,
                'app' => config('app.name', 'PNLCS'),
            ], JSON_PRETTY_PRINT));

            $zip->close();

            if (is_file($sqlTempPath)) {
                @unlink($sqlTempPath);
            }

            $sizeBytes = is_file($zipPath) ? (int) filesize($zipPath) : 0;
            $accessToken = $this->fetchAccessToken();
            $driveFileId = $this->uploadZipToDrive($accessToken, $zipPath, $archiveName);
            $pruned = $this->pruneOldBackups($accessToken);

            if (! $this->flagEnabled('keep_local') && is_file($zipPath)) {
                @unlink($zipPath);
            }

            $msg = "Uploaded {$archiveName} ({$sizeBytes} bytes) to Google Drive".($pruned > 0 ? "; pruned {$pruned} older backup(s)." : '.');
            $log->update([
                'status' => 'success',
                'file_name' => $archiveName,
                'drive_file_id' => $driveFileId,
                'size_bytes' => $sizeBytes,
                'message' => $msg,
            ]);

            return ['success' => true, 'log' => $log->fresh(), 'message' => $msg];
        } catch (\Throwable $e) {
            if (is_file($sqlTempPath)) {
                @unlink($sqlTempPath);
            }
            if (! $this->flagEnabled('keep_local') && is_file($zipPath)) {
                @unlink($zipPath);
            }

            Log::error('DriveBackup failed', ['error' => $e->getMessage()]);
            $log->update([
                'status' => 'failed',
                'file_name' => $archiveName,
                'message' => Str::limit($e->getMessage(), 500),
            ]);

            return ['success' => false, 'log' => $log->fresh(), 'message' => 'Backup failed: '.$e->getMessage()];
        }
    }

    private function fetchAccessToken(): string
    {
        $cfg = $this->settings();
        $res = Http::asForm()->timeout(20)->post(self::OAUTH_TOKEN_URL, [
            'client_id' => $cfg['client_id'],
            'client_secret' => $cfg['client_secret'],
            'refresh_token' => $cfg['refresh_token'],
            'grant_type' => 'refresh_token',
        ]);

        if (! $res->successful()) {
            throw new \RuntimeException('Google OAuth2 token refresh returned HTTP '.$res->status());
        }

        $token = trim((string) ($res->json('access_token') ?? ''));
        if ($token === '') {
            throw new \RuntimeException('Google OAuth2 response contained no access_token.');
        }

        return $token;
    }

    private function uploadZipToDrive(string $accessToken, string $zipPath, string $archiveName): string
    {
        $folderId = trim((string) ($this->settings()['drive_folder_id'] ?? ''));
        $metadata = ['name' => $archiveName, 'mimeType' => 'application/zip'];
        if ($folderId !== '') {
            $metadata['parents'] = [$folderId];
        }

        $boundary = 'pnlcs_boundary_'.Str::random(24);
        $zipContents = (string) file_get_contents($zipPath);

        $multipartBody = "--{$boundary}\r\n"
            ."Content-Type: application/json; charset=UTF-8\r\n\r\n"
            .json_encode($metadata)."\r\n"
            ."--{$boundary}\r\n"
            ."Content-Type: application/zip\r\n\r\n"
            .$zipContents."\r\n"
            ."--{$boundary}--";

        $res = Http::withToken($accessToken)
            ->timeout(120)
            ->withBody($multipartBody, "multipart/related; boundary={$boundary}")
            ->post(self::DRIVE_UPLOAD_URL);

        if (! $res->successful()) {
            throw new \RuntimeException('Google Drive upload failed with HTTP '.$res->status());
        }

        return (string) ($res->json('id') ?? '');
    }

    private function pruneOldBackups(string $accessToken): int
    {
        $cfg = $this->settings();
        $maxBackups = max(1, (int) ($cfg['max_backups'] ?? 10));
        $folderId = trim((string) ($cfg['drive_folder_id'] ?? ''));

        $query = "trashed = false and mimeType = 'application/zip' and name contains 'pnlcs-backup-'";
        if ($folderId !== '' && preg_match('/^[A-Za-z0-9_-]+$/', $folderId)) {
            $query .= " and '{$folderId}' in parents";
        }

        $res = Http::withToken($accessToken)->timeout(20)->get(self::DRIVE_FILES_URL, [
            'q' => $query,
            'orderBy' => 'createdTime desc',
            'pageSize' => 100,
            'fields' => 'files(id,name,createdTime)',
        ]);

        if (! $res->successful()) {
            return 0;
        }

        $files = (array) ($res->json('files') ?? []);
        if (count($files) <= $maxBackups) {
            return 0;
        }

        $pruned = 0;
        foreach (array_slice($files, $maxBackups) as $old) {
            $id = (string) ($old['id'] ?? '');
            if ($id !== '' && preg_match('/^[A-Za-z0-9_-]+$/', $id)) {
                $del = Http::withToken($accessToken)->timeout(15)->delete(self::DRIVE_FILES_URL.'/'.rawurlencode($id));
                if ($del->successful() || $del->status() === 204) {
                    $pruned++;
                }
            }
        }

        return $pruned;
    }

    private function writeDatabaseDump(string $targetPath): void
    {
        $binary = null;
        foreach (self::ALLOWED_MYSQLDUMP_BINARIES as $candidate) {
            if (is_file($candidate)) {
                $binary = $candidate;
                break;
            }
        }

        $conn = config('database.default');
        $dbCfg = (array) config("database.connections.{$conn}", []);
        $driver = (string) ($dbCfg['driver'] ?? '');

        if ($binary !== null && in_array($driver, ['mysql', 'mariadb'], true)) {
            $cnfPath = dirname($targetPath).DIRECTORY_SEPARATOR.'my-'.Str::uuid()->toString().'.cnf';
            $host = (string) ($dbCfg['host'] ?? '127.0.0.1');
            $port = (int) ($dbCfg['port'] ?? 3306);
            $user = (string) ($dbCfg['username'] ?? '');
            $pass = str_replace('"', '\\"', (string) ($dbCfg['password'] ?? ''));
            $database = (string) ($dbCfg['database'] ?? '');

            file_put_contents($cnfPath, "[client]\nhost=\"{$host}\"\nport={$port}\nuser=\"{$user}\"\npassword=\"{$pass}\"\n");
            @chmod($cnfPath, 0600);

            try {
                $process = new Process([
                    $binary,
                    '--defaults-extra-file='.$cnfPath,
                    '--single-transaction',
                    '--quick',
                    '--skip-lock-tables',
                    '--result-file='.$targetPath,
                    $database,
                ]);
                $process->setTimeout(120);
                $process->run();

                if ($process->isSuccessful() && is_file($targetPath) && filesize($targetPath) > 0) {
                    return;
                }
            } finally {
                if (is_file($cnfPath)) {
                    @unlink($cnfPath);
                }
            }
        }

        // Fallback PDO schema summary dump (used when mysqldump is unavailable or DB is SQLite in tests).
        $pdo = DB::connection()->getPdo();
        $fh = fopen($targetPath, 'wb');
        fwrite($fh, '-- PNLCS Database Backup generated at '.now()->toIso8601String()."\n\n");
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
            foreach ($tables as $table) {
                $safeTable = str_replace('`', '``', (string) $table);
                $create = $pdo->query("SHOW CREATE TABLE `{$safeTable}`")->fetch(\PDO::FETCH_NUM);
                if (! empty($create[1])) {
                    fwrite($fh, "DROP TABLE IF EXISTS `{$safeTable}`;\n".$create[1].";\n\n");
                }
            }
        }
        fclose($fh);
    }

    private function addDirectoryToZip(ZipArchive $zip, string $dir, string $zipPrefix): void
    {
        $realBase = realpath($dir);
        if ($realBase === false) {
            return;
        }
        $prefixWithSep = rtrim($realBase, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($realBase, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($it as $file) {
            if (! $file->isFile()) {
                continue;
            }
            $realFile = $file->getRealPath();
            if ($realFile === false || ! str_starts_with($realFile, $prefixWithSep)) {
                continue;
            }
            $relative = str_replace('\\', '/', substr($realFile, strlen($prefixWithSep)));
            $zip->addFile($realFile, $zipPrefix.'/'.$relative);
        }
    }
}
