<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case PendingCod = 'pending_cod';
    case AwaitingConfirmation = 'awaiting_confirmation';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return __('order.payment_status.'.$this->value);
    }

    public function colour(): string
    {
        return match ($this) {
            self::Paid => 'success',
            self::Failed => 'danger',
            self::Pending, self::PendingCod, self::AwaitingConfirmation => 'warning',
            default => 'gray',
        };
    }
}
