<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Carrier extends Model
{
    protected $primaryKey = 'code';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['code', 'name', 'supports_cod', 'is_active', 'api_config'];

    protected function casts(): array
    {
        return [
            'supports_cod' => 'boolean',
            'is_active' => 'boolean',
            'api_config' => 'array',
        ];
    }

    public function zones(): HasMany
    {
        return $this->hasMany(DeliveryZone::class, 'carrier_code', 'code');
    }
}
