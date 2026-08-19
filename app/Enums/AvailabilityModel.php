<?php

namespace App\Enums;

enum AvailabilityModel: string
{
    case InStock = 'in_stock';
    case MadeToOrder = 'made_to_order';
    case LimitedBatch = 'limited_batch';
    case Seasonal = 'seasonal';

    public function tracksStock(): bool
    {
        return in_array($this, [self::InStock, self::LimitedBatch], true);
    }

    public function label(): string
    {
        return __('listing.availability.'.$this->value);
    }
}
