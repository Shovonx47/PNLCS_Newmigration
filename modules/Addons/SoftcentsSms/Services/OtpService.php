<?php

namespace Modules\Addons\SoftcentsSms\Services;

use App\Support\BdPhone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Addons\SoftcentsSms\Models\SmsSetting;

/**
 * The registration SMS OTP.
 *
 * The legacy OtpManager stored the code's bcrypt hash and a PHP session id;
 * the session id was never read, so the code belonged to a number and nothing
 * else. That is the right model and it is kept. What is added is the limit the
 * legacy install could not enforce without it: a number gets
 * otp_max_per_hour codes, and a code gives five tries in total.
 *
 * The verified marker lives in the session under SESSION_PHONE; registration
 * checks that it equals the number being submitted, so a customer cannot
 * verify one number and register another.
 */
class OtpService
{
    public const SESSION_PHONE = 'softcents_sms_verified_phone';

    public const MAX_ATTEMPTS = 5;

    public function settings(): SmsSetting
    {
        return SmsSetting::current();
    }

    public function enabled(): bool
    {
        $settings = $this->settings();

        return $settings->otp_enabled && $settings->configured();
    }

    /**
     * @return array{status: string, message: string, cooldown?: int, phone?: string}
     */
    public function start(string $number, string $prefix = ''): array
    {
        $settings = $this->settings();

        if (! $this->enabled()) {
            return ['status' => 'error', 'message' => 'SMS verification is not available right now.'];
        }

        $phone = BdPhone::gateway($number, $prefix);

        if ($phone === '') {
            return ['status' => 'error', 'message' => 'Please enter a valid Bangladeshi mobile number.'];
        }

        $cooldown = max(0, (int) $settings->otp_resend_cooldown);
        $last = DB::table('softcents_sms_otps')->where('phone', $phone)->latest('id')->first();

        if ($last && $cooldown > 0) {
            $wait = $cooldown - now()->diffInSeconds($last->created_at);
            if ($wait > 0) {
                return [
                    'status' => 'cooldown',
                    'message' => 'Please wait '.$wait.' seconds before asking for another code.',
                    'cooldown' => $wait,
                ];
            }
        }

        $perHour = max(1, (int) $settings->otp_max_per_hour);
        $recent = DB::table('softcents_sms_otps')
            ->where('phone', $phone)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recent >= $perHour) {
            return ['status' => 'error', 'message' => 'Too many codes for this number. Try again in an hour.'];
        }

        $length = min(8, max(4, (int) $settings->otp_length));
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= (string) random_int(0, 9);
        }

        $expiry = max(1, (int) $settings->otp_expiry_minutes);

        $template = (string) ($settings->otp_template ?: 'Your {companyname} registration verification OTP is {otp}. Valid for {minutes} minutes.');
        $text = str_replace(
            ['{otp}', '{minutes}', '{companyname}'],
            [$code, (string) $expiry, company_name()],
            $template
        );

        $result = (new SmsClient($settings))->send($phone, $text);

        if (! $result['success']) {
            return ['status' => 'error', 'message' => 'The code could not be sent. '.$result['error']];
        }

        // Old codes for this number stop working the moment a new one is sent.
        DB::table('softcents_sms_otps')->where('phone', $phone)->where('consumed', false)->delete();

        DB::table('softcents_sms_otps')->insert([
            'phone' => $phone,
            'otp_hash' => Hash::make($code),
            'attempts' => 0,
            'verified' => false,
            'consumed' => false,
            'ip_address' => request()->ip(),
            'expires_at' => now()->addMinutes($expiry),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'status' => 'success',
            'message' => 'A '.$length.'-digit code has been sent to your mobile number.',
            'cooldown' => $cooldown,
            'phone' => $phone,
        ];
    }

    /**
     * @return array{status: string, message: string, phone?: string}
     */
    public function verify(string $number, string $code, string $prefix = ''): array
    {
        $phone = BdPhone::gateway($number, $prefix);

        if ($phone === '') {
            return ['status' => 'error', 'message' => 'Please enter a valid mobile number.'];
        }

        $row = DB::table('softcents_sms_otps')
            ->where('phone', $phone)
            ->where('consumed', false)
            ->latest('id')
            ->first();

        if (! $row) {
            return ['status' => 'error', 'message' => 'No code was sent to this number. Ask for a new one.'];
        }

        if (now()->greaterThan($row->expires_at)) {
            return ['status' => 'error', 'message' => 'This code has expired. Ask for a new one.'];
        }

        if ((int) $row->attempts >= self::MAX_ATTEMPTS) {
            return ['status' => 'error', 'message' => 'Too many wrong codes. Ask for a new one.'];
        }

        if (! Hash::check(trim($code), (string) $row->otp_hash)) {
            DB::table('softcents_sms_otps')->where('id', $row->id)->increment('attempts');

            return ['status' => 'error', 'message' => 'That code is not correct.'];
        }

        DB::table('softcents_sms_otps')->where('id', $row->id)->update([
            'verified' => true,
            'verified_at' => now(),
            'attempts' => (int) $row->attempts + 1,
            'updated_at' => now(),
        ]);

        session([self::SESSION_PHONE => $phone]);

        return ['status' => 'success', 'message' => 'Mobile number verified.', 'phone' => $phone];
    }

    /** The number this session has proved, in 880... form, or ''. */
    public function verifiedPhone(): string
    {
        return (string) session(self::SESSION_PHONE, '');
    }

    /** Registration succeeded: the code cannot be used to open another account. */
    public function consume(string $phone): void
    {
        DB::table('softcents_sms_otps')->where('phone', $phone)->where('consumed', false)->update([
            'consumed' => true,
            'updated_at' => now(),
        ]);

        session()->forget(self::SESSION_PHONE);
    }
}
