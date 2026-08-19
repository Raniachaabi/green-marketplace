<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::Confirmed, self::Shipped], true);
    }

    public function label(): string
    {
        return __('order.status.'.$this->value);
    }

    public function colour(): string
    {
        return match ($this) {
            self::Delivered => 'success',
            self::Cancelled, self::Refunded => 'danger',
            self::Shipped => 'info',
            default => 'warning',
        };
    }
}
