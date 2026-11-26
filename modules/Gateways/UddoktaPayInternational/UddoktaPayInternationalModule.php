<?php

namespace Modules\Gateways\UddoktaPayInternational;

use Modules\Gateways\UddoktaPay\AbstractUddoktaPayModule;

class UddoktaPayInternationalModule extends AbstractUddoktaPayModule
{
    public const NAME = 'uddoktapayinternational';

    public function gatewayKey(): string
    {
        return self::NAME;
    }

    public function getModuleName(): string
    {
        return 'UddoktaPay International (Visa / Mastercard / Global)';
    }

    protected function checkoutEndpoint(): string
    {
        return 'checkout-v2/global';
    }

    protected function isInternational(): bool
    {
        return true;
    }

    protected function paymentDescription(): string
    {
        return 'Pay securely with international Visa, Mastercard, American Express, or global wallets.';
    }
}
