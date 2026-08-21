<?php

namespace App\Models;

use App\Enums\ShipmentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    use HasUuids;

    protected $fillable = [
        'order_id', 'seller_user_id', 'seller_org_id', 'carrier_code',
        'tracking_ref', 'cod_amount', 'handling_flags', 'method', 'status',
        'picked_up_at', 'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'handling_flags' => 'array',
            'cod_amount' => 'integer',
            'status' => ShipmentStatus::class,
            'picked_up_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function seller(): User|Organization|null
    {
        return $this->sellerOrg ?? $this->sellerUser;
    }

    public function sellerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_user_id');
    }

    public function sellerOrg(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'seller_org_id');
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class, 'carrier_code', 'code');
    }

    public function isCod(): bool
    {
        return $this->cod_amount > 0;
    }
}
