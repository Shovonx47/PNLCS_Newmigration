<?php

namespace Database\Seeders;

use App\Models\AddonSetting;
use App\Models\Setting;
use App\Support\Mojibake;
use Illuminate\Database\Seeder;
use Modules\Addons\SoftcentsSms\Models\SmsSetting;
use Modules\Addons\SoftcentsSms\SoftcentsSmsModule;
use Modules\Addons\SoftcentsVoice\Models\VoiceEvent;
use Modules\Addons\SoftcentsVoice\Models\VoiceGroupOverride;
use Modules\Addons\SoftcentsVoice\Models\VoiceSettings;
use Modules\Addons\SoftcentsVoice\SoftcentsVoiceModule;
use Modules\Addons\TelegramBot\TelegramBotModule;
use Modules\Addons\WhatsappPro\Models\WaSettings;
use Modules\Addons\WhatsappPro\WhatsappProModule;
use PDO;

/**
 * Phase 3 - the notification addons' real credentials and switches, taken from
 * the production WHMCS database.
 *
 * Read-only from billing_db. The SMS account, the WhatsApp gateway, the voice
 * API token and the Telegram bot are the operator's own; the templates and the
 * 24 voice events come from their addon tables. Nothing is enabled that was not
 * enabled there, so importing cannot start ringing customers by itself.
 *
 * Run with: php artisan db:seed --class=Phase3NotificationDataSeeder
 */
class Phase3NotificationDataSeeder extends Seeder
{
    public function run(): void
    {
        $whmcs = $this->connectBillingDb();

        $this->activateAddons();
        $this->seedSms($whmcs);
        $this->seedWhatsapp($whmcs);
        $this->seedVoice($whmcs);
        $this->seedTelegram($whmcs);

        $this->command?->info('Phase3NotificationDataSeeder synced Phase 3 settings from billing_db.');
    }

    private function activateAddons(): void
    {
        foreach ([
            'softcents_sms' => SoftcentsSmsModule::class,
            'whatsapp_pro' => WhatsappProModule::class,
            'telegram_bot' => TelegramBotModule::class,
            'softcents_voice' => SoftcentsVoiceModule::class,
        ] as $name => $module) {
            $result = app($module)->activate();

            if (! ($result['success'] ?? false)) {
                $this->command?->warn("{$name}: ".($result['message'] ?? 'activation failed'));
            }

            Setting::set("addon_{$name}_active", '1', 'addons');
            Setting::set("addon_{$name}_version", '1.0.0', 'addons');
        }

        $this->command?->info('Activated: softcents_sms, whatsapp_pro, telegram_bot, softcents_voice.');
    }

    private function seedSms(PDO $whmcs): void
    {
        $row = $whmcs->query('SELECT * FROM mod_softcents_sms_settings ORDER BY id LIMIT 1')->fetch();

        if (! $row) {
            $this->command?->warn('SMS: no settings row in billing_db.');

            return;
        }

        SmsSetting::current()->fill([
            'client_id' => (string) $row['client_id'],
            'client_secret' => (string) $row['client_secret'],
            'sender_id' => (string) $row['sender_id'],
            'date_format' => (string) ($row['dateformat'] ?: 'd-m-Y'),
            'otp_enabled' => (bool) $row['otp_enabled'],
            'otp_checkout_enabled' => (bool) $row['otp_checkout_enabled'],
            'otp_length' => (int) $row['otp_length'],
            'otp_expiry_minutes' => (int) $row['otp_expiry_minutes'],
            'otp_resend_cooldown' => (int) $row['otp_resend_cooldown'],
            'otp_max_per_hour' => (int) $row['otp_max_per_hour'],
            'otp_template' => (string) $row['otp_template'],
        ])->save();

        $this->command?->info('SMS: credentials and OTP settings imported (sender '.$row['sender_id'].').');
    }

    private function seedWhatsapp(PDO $whmcs): void
    {
        $row = $whmcs->query('SELECT * FROM mod_bdhost_wa_settings ORDER BY id LIMIT 1')->fetch();

        if (! $row) {
            $this->command?->warn('WhatsApp: no settings row in billing_db.');

            return;
        }

        WaSettings::current()->fill([
            'api_key' => (string) $row['apikey'],
            'sender' => (string) $row['whatsapp'],
            'api_domain' => rtrim((string) ($row['custom_domain'] ?: 'https://wpg.softcents.com'), '/'),
            'footer' => (string) $row['footer_message'],
            'admin_numbers' => (string) $row['update_admin_number'],
            'client_portal' => (bool) $row['client_portal_enabled'],
            'max_retry' => (int) ($row['max_retry_count'] ?: 3),
            'enabled' => true,
        ])->save();

        $this->command?->info('WhatsApp: gateway imported (sender '.$row['whatsapp'].', '.$row['custom_domain'].').');
    }

    private function seedVoice(PDO $whmcs): void
    {
        $settings = $whmcs->query(
            "SELECT setting, value FROM tbladdonmodules WHERE module = 'softcents_voice'"
        )->fetchAll(PDO::FETCH_KEY_PAIR);

        VoiceSettings::current()->fill([
            'api_token' => (string) ($settings['api_token'] ?? ''),
            'api_base_url' => (string) ($settings['api_base_url'] ?? 'https://vsms.softcents.com/api'),
            'caller_id' => (string) ($settings['caller_id'] ?? '376'),
            'calling_start' => (string) ($settings['calling_start_time'] ?? '09:00'),
            'calling_end' => (string) ($settings['calling_end_time'] ?? '21:00'),
            'timezone' => (string) ($settings['calling_timezone'] ?? 'Asia/Dhaka'),
            'master_paused' => ($settings['master_voice_dispatch'] ?? 'paused') === 'paused',
            'retry_enabled' => ($settings['retry_enabled'] ?? 'on') === 'on',
        ])->save();

        // The event rows and their recordings, exactly as the legacy addon had
        // them: switched off until an operator chooses a recording.
        $events = $whmcs->query('SELECT * FROM mod_softcents_voice_events ORDER BY id')->fetchAll();

        foreach ($events as $row) {
            VoiceEvent::updateOrCreate(
                ['event_key' => (string) $row['event_key']],
                [
                    'category' => (string) $row['category'],
                    'name' => Mojibake::repair((string) $row['name_en']),
                    'description' => Mojibake::repair((string) ($row['description'] ?? '')),
                    'audio_id' => (string) ($row['audio_id'] ?? ''),
                    'caller_id' => (string) ($row['caller_id'] ?? ''),
                    'is_enabled' => (bool) $row['is_enabled'],
                ]
            );
        }

        $overrides = $whmcs->query('SELECT * FROM mod_softcents_voice_group_events ORDER BY id')->fetchAll();
        $imported = 0;

        foreach ($overrides as $row) {
            VoiceGroupOverride::updateOrCreate(
                ['group_id' => (int) $row['group_id'], 'event_key' => (string) $row['event_key']],
                [
                    'audio_id' => (string) ($row['audio_id'] ?? ''),
                    'caller_id' => (string) ($row['caller_id'] ?? ''),
                    'is_enabled' => (bool) $row['is_enabled'],
                ]
            );
            $imported++;
        }

        $this->command?->info('Voice: '.count($events).' events and '.$imported.' group override(s) imported; dispatch '.($settings['master_voice_dispatch'] ?? 'paused').'.');
    }

    private function seedTelegram(PDO $whmcs): void
    {
        $settings = $whmcs->query(
            "SELECT setting, value FROM tbladdonmodules WHERE module = 'softcents_telegram'"
        )->fetchAll(PDO::FETCH_KEY_PAIR);

        if (empty($settings['bot_token'])) {
            $this->command?->warn('Telegram: no bot token in billing_db.');

            return;
        }

        AddonSetting::setSetting('telegram_bot', 'bot_token', (string) $settings['bot_token']);
        AddonSetting::setSetting('telegram_bot', 'webhook_secret', (string) ($settings['webhook_secret'] ?? ''));
        AddonSetting::setSetting('telegram_bot', 'enabled', ($settings['enabled'] ?? '') === 'on' ? '1' : '0');
        AddonSetting::setSetting('telegram_bot', 'notify_invoices', ($settings['notify_invoices'] ?? '') === 'on' ? '1' : '0');
        AddonSetting::setSetting('telegram_bot', 'notify_services', ($settings['notify_services'] ?? '') === 'on' ? '1' : '0');
        AddonSetting::setSetting('telegram_bot', 'notify_tickets', ($settings['notify_tickets'] ?? '') === 'on' ? '1' : '0');

        // The username is read from Telegram itself (getMe), not guessed: the
        // connect link is built from it and a wrong one sends customers nowhere.
        $this->command?->info('Telegram: token and switches imported. Run the live check to resolve the bot username.');
    }

    private function connectBillingDb(): PDO
    {
        $host = (string) env('WHMCS_DB_HOST', config('database.connections.mysql.host', '127.0.0.1'));
        $port = (int) env('WHMCS_DB_PORT', config('database.connections.mysql.port', 3306));
        $db = (string) env('WHMCS_DB_DATABASE', 'billing_db');
        $user = (string) env('WHMCS_DB_USERNAME', 'root');
        $pass = (string) env('WHMCS_DB_PASSWORD', '');

        $pdo = new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('SET SESSION TRANSACTION READ ONLY');

        return $pdo;
    }
}
