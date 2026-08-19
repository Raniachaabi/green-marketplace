<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryZone extends Model
{
    use HasUuids;

    protected $fillable = [
        'carrier_code', 'governorate', 'base_price', 'per_kg',
        'lead_time_days', 'cod_supported',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'integer',
            'per_kg' => 'integer',
            'lead_time_days' => 'integer',
            'cod_supported' => 'boolean',
        ];
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class, 'carrier_code', 'code');
    }

    public function formattedPrice(): string
    {
        return Money::format((int) $this->base_price);
    }
}
