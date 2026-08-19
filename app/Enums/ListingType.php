<?php

namespace App\Enums;

enum ListingType: string
{
    case Product = 'product';
    case Service = 'service';
    case Experience = 'experience';
    case Rental = 'rental';

    /** Products go through the cart; everything else is booked. */
    public function usesCart(): bool
    {
        return $this === self::Product;
    }

    public function label(): string
    {
        return __('listing.type.'.$this->value);
    }
}
