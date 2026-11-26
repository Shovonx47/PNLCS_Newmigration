<?php

namespace Modules\Addons\SoftcentsSms\Services;

use App\Contracts\SmsSender;

/**
 * Lets the rest of the panel send an SMS through the same account this addon
 * is configured with - the phone-verification code, for one - instead of
 * needing a second provider set up elsewhere.
 */
class SoftcentsSmsSender implements SmsSender
{
    public function __construct(private ?SmsClient $client = null)
    {
        $this->client ??= new SmsClient;
    }

    public function send(string $to, string $message): bool
    {
        return $this->client->send($to, $message)['success'];
    }
}
