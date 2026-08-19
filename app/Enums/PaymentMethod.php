<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cod = 'cod';
    case Transfer = 'transfer';
    case Konnect = 'konnect';
    case Flouci = 'flouci';
    case Paymee = 'paymee';

    /** Only COD and transfer are wired up at P0. */
    public function isAvailableAtLaunch(): bool
    {
        return in_array($this, [self::Cod, self::Transfer], true);
    }

    public function label(): string
    {
        return __('order.payment.'.$this->value);
    }
}
