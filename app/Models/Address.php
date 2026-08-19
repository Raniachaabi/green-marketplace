<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id', 'organization_id', 'label', 'contact_name', 'contact_phone',
        'governorate', 'delegation', 'locality', 'street', 'postal_code',
        'lat', 'lng', 'is_default',
    ];

    protected function casts(): array
    {
        return ['is_default' => 'boolean', 'lat' => 'float', 'lng' => 'float'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function toSnapshot(): array
    {
        return $this->only([
            'contact_name', 'contact_phone', 'governorate',
            'delegation', 'locality', 'street', 'postal_code',
        ]);
    }

    public function oneLine(): string
    {
        return collect([$this->street, $this->locality, $this->delegation,
            __('governorate.'.$this->governorate)])->filter()->implode(', ');
    }
}
