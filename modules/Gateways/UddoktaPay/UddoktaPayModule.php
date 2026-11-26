<?php

namespace Modules\Gateways\UddoktaPay;

class UddoktaPayModule extends AbstractUddoktaPayModule
{
    public const NAME = 'uddoktapay';

    public function gatewayKey(): string
    {
        return self::NAME;
    }

    public function getModuleName(): string
    {
        return 'UddoktaPay (bKash, Nagad, Rocket & Upay)';
    }

    protected function checkoutEndpoint(): string
    {
        return 'checkout-v2';
    }

    protected function isInternational(): bool
    {
        return false;
    }

    protected function paymentDescription(): string
    {
        return 'Instant Bangladesh payment via bKash, Nagad, Rocket, Upay, or local bank transfer.';
    }
}
