<?php

namespace App\Enums;

enum ShipmentStatus: string
{
    case Pending = 'pending';
    case PickedUp = 'picked_up';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Returned = 'returned';

    public function label(): string
    {
        return __('order.shipment_status.'.$this->value);
    }

    public function colour(): string
    {
        return match ($this) {
            self::Delivered => 'success',
            self::Failed, self::Returned => 'danger',
            self::InTransit, self::PickedUp => 'info',
            self::Pending => 'warning',
        };
    }
}
