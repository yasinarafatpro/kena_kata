<?php

namespace App\Payments;

final class PaymentGatewayFactory
{
    public function make(string $name): PaymentGateway
    {
        return match ($name) {
            'stripe' => app(StripeGateway::class),
            'paypal' => app(PayPalGateway::class),
            'cod'    => app(CodGateway::class),
            default  => throw new \InvalidArgumentException("Unsupported gateway [$name]"),
        };
    }
}
