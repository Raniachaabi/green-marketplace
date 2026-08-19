<?php

namespace App\Enums;

enum ListingStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Rejected = 'rejected';
    case Archived = 'archived';

    public function isPubliclyVisible(): bool
    {
        return $this === self::Active;
    }

    public function label(): string
    {
        return __('listing.status.'.$this->value);
    }

    public function colour(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Pending => 'warning',
            self::Suspended, self::Rejected => 'danger',
            default => 'gray',
        };
    }
}
