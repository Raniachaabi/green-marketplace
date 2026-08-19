<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Support\Money;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'number', 'buyer_user_id', 'buyer_org_id', 'address_id', 'status',
        'subtotal', 'delivery_total', 'vat_total', 'total', 'payment_method',
        'shipping_snapshot', 'buyer_note', 'placed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_method' => PaymentMethod::class,
            'shipping_snapshot' => 'array',
            'subtotal' => 'integer',
            'delivery_total' => 'integer',
            'vat_total' => 'integer',
            'total' => 'integer',
            'placed_at' => 'datetime',
        ];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    public function buyerOrg(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'buyer_org_id');
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function formattedTotal(): string
    {
        return Money::format((int) $this->total);
    }

    /** Distinct sellers in this order — one shipment each. */
    public function sellerKeys(): array
    {
        return $this->lines
            ->map(fn (OrderLine $l) => $l->seller_org_id ?: $l->seller_user_id)
            ->unique()
            ->values()
            ->all();
    }
}
