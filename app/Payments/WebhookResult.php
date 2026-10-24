<?php

namespace App\Payments;

final class WebhookResult
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $outcome,          // paid | failed | ignored
        public readonly ?int $orderId = null,
        public readonly ?string $reference = null,
        public readonly ?int $amountMinor = null,
    ) {}

    public static function ignored(string $eventId): self
    {
        return new self($eventId, 'ignored');
    }
}
