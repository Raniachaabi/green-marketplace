<?php

namespace App\Enums;

enum CredentialStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Expired = 'expired';

    public function label(): string
    {
        return __('credential.status.'.$this->value);
    }

    public function colour(): string
    {
        return match ($this) {
            self::Approved => 'success',
            self::Pending => 'warning',
            self::Rejected, self::Expired => 'danger',
        };
    }
}
