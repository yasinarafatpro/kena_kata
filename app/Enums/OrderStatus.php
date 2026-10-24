<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, match ($this) {
            self::Pending => [self::Paid, self::Cancelled],
            self::Paid    => [self::Shipped, self::Cancelled],
            self::Shipped => [self::Delivered],
            default       => [],
        }, true);
    }
}
