<?php

namespace App\Payments;

final class PaymentIntentResult
{
    public function __construct(
        public readonly string $reference,
        public readonly ?string $clientSecret = null,
        public readonly ?string $redirectUrl = null,
    ) {}

    public function toArray(): array
    {
        return ['client_secret' => $this->clientSecret, 'redirect_url' => $this->redirectUrl];
    }
}
