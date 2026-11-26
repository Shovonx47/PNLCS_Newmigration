<?php

namespace Modules\Addons\DriveBackup\Http\Admin;

use App\Http\Controllers\Controller;
use App\Models\AddonSetting;
use Illuminate\Http\Request;
use Modules\Addons\DriveBackup\DriveBackupModule;
use Modules\Addons\DriveBackup\Models\DriveBackupLog;
use Modules\Addons\DriveBackup\Services\DriveBackupService;

class DriveBackupAdminController extends Controller
{
    public function index(Request $request, DriveBackupService $service)
    {
        $redirectUri = route('admin.extensions.gdrive-backup.index');

        return view('gdrivebackup::admin.index', [
            'settings' => $service->settings(),
            'isConfigured' => $service->isConfigured(),
            'oauthRedirectUri' => $redirectUri,
            'oauthConsentUrl' => $service->buildConsentUrl($redirectUri),
            'prefilledCode' => trim((string) $request->query('code', '')),
            'logs' => DriveBackupLog::latest('id')->limit(25)->get(),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $v = $request->validate([
            'client_id' => 'nullable|string|max:512',
            'client_secret' => 'nullable|string|max:512',
            'refresh_token' => 'nullable|string|max:2048',
            'drive_folder_id' => 'nullable|string|max:255',
            'max_backups' => 'required|integer|min:1|max:365',
        ]);

        $addon = DriveBackupModule::NAME;
        AddonSetting::setSetting($addon, 'client_id', trim((string) ($v['client_id'] ?? '')));
        AddonSetting::setSetting($addon, 'drive_folder_id', trim((string) ($v['drive_folder_id'] ?? '')));
        AddonSetting::setSetting($addon, 'max_backups', (string) $v['max_backups']);

        foreach (['client_secret', 'refresh_token'] as $secret) {
            if ($request->filled($secret)) {
                AddonSetting::setSetting($addon, $secret, trim((string) $request->input($secret)));
            }
        }

        foreach (['backup_db', 'backup_files', 'keep_local'] as $flag) {
            AddonSetting::setSetting($addon, $flag, $request->boolean($flag) ? '1' : '0');
        }

        return back()->with('success', 'Google Drive backup settings updated.');
    }

    public function exchangeOAuthCode(Request $request, DriveBackupService $service)
    {
        $v = $request->validate([
            'auth_code' => 'required|string|max:2048',
            'redirect_uri' => 'nullable|url|max:512',
        ]);

        $redirectUri = trim((string) ($v['redirect_uri'] ?? '')) ?: route('admin.extensions.gdrive-backup.index');

        try {
            $service->exchangeAuthorizationCode($v['auth_code'], $redirectUri);

            return redirect()->route('admin.extensions.gdrive-backup.index')
                ->with('success', 'OAuth2 authorization code exchanged and encrypted refresh token saved.');
        } catch (\Throwable $e) {
            return redirect()->route('admin.extensions.gdrive-backup.index')
                ->with('error', $e->getMessage());
        }
    }

    public function runNow(Request $request, DriveBackupService $service)
    {
        $admin = auth('admin')->user()?->username ?: 'admin';
        $res = $service->runBackup('full', $admin);

        return back()->with($res['success'] ? 'success' : 'error', $res['message']);
    }
}
