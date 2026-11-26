<?php

namespace Modules\Addons\SoftcentsSms\Models;

use App\Support\BdPhone;
use Illuminate\Database\Eloquent\Model;

/**
 * One row. client_secret is encrypted at rest - the legacy table stored it in
 * plain text, where any database dump or SQL injection handed over the SMS
 * account.
 */
class SmsSetting extends Model
{
    protected $table = 'softcents_sms_settings';

    protected $fillable = [
        'client_id', 'client_secret', 'sender_id', 'date_format',
        'otp_enabled', 'otp_checkout_enabled', 'otp_length', 'otp_expiry_minutes',
        'otp_resend_cooldown', 'otp_max_per_hour', 'otp_template',
        'admin_numbers', 'admin_alerts',
    ];

    protected function casts(): array
    {
        return [
            'client_secret' => 'encrypted',
            'otp_enabled' => 'boolean',
            'otp_checkout_enabled' => 'boolean',
            'admin_alerts' => 'boolean',
        ];
    }

    /**
     * The one row. Deliberately not firstOrCreate(['id' => 1]): id is not
     * fillable, so that call quietly inserted a new empty row with an
     * auto-increment id every time it ran.
     */
    public static function current(): self
    {
        $row = static::query()->orderBy('id')->first();

        if ($row) {
            return $row;
        }

        return static::create([
            'client_id' => '',
            'client_secret' => '',
            'sender_id' => '',
            'date_format' => 'd-m-Y',
            'otp_enabled' => false,
            'otp_checkout_enabled' => false,
            'otp_length' => 6,
            'otp_expiry_minutes' => 5,
            'otp_resend_cooldown' => 60,
            'otp_max_per_hour' => 5,
            'otp_template' => 'Your {companyname} registration verification OTP is {otp}. Valid for {minutes} minutes.',
            'admin_alerts' => false,
        ]);
    }

    public function configured(): bool
    {
        return trim((string) $this->client_id) !== ''
            && trim((string) $this->client_secret) !== ''
            && trim((string) $this->sender_id) !== '';
    }

    /** @return array<int, string> */
    public function adminNumberList(): array
    {
        return array_values(array_filter(array_map(
            fn ($n) => BdPhone::gateway(trim($n)),
            explode(',', (string) $this->admin_numbers)
        )));
    }
}
