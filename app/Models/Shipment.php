<?php

namespace App\Models;

use App\Enums\ShipmentStatus;
use Carbon\Carbon;
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

    /**
     * Phase 2 §17 — a step tracker built only from timestamps this app
     * actually records: the order's placed_at, and this shipment's own
     * picked_up_at/delivered_at. No GPS, no invented "confirmed at" — a
     * step without a real timestamp column just doesn't show one.
     *
     * @return array<int, array{key: string, label: string, done: bool, active: bool, timestamp: ?Carbon}>
     */
    public function trackerSteps(Order $order): array
    {
        if (in_array($this->status, [ShipmentStatus::Failed, ShipmentStatus::Returned], true)) {
            return [];
        }

        $order_ = [ShipmentStatus::Pending, ShipmentStatus::PickedUp, ShipmentStatus::InTransit, ShipmentStatus::Delivered];
        $currentIndex = array_search($this->status, $order_, true);

        $steps = [
            ['key' => 'placed', 'label' => __('order.tracker.placed'), 'timestamp' => $order->placed_at],
            ['key' => 'preparing', 'label' => __('order.tracker.preparing'), 'timestamp' => null],
            ['key' => 'picked_up', 'label' => __('order.tracker.picked_up'), 'timestamp' => $this->picked_up_at],
            ['key' => 'in_transit', 'label' => __('order.tracker.in_transit'), 'timestamp' => null],
            ['key' => 'delivered', 'label' => __('order.tracker.delivered'), 'timestamp' => $this->delivered_at],
        ];

        // Step 0 ("placed") is always done; steps 1-4 map to the shipment
        // status index (0=pending..3=delivered) shifted by one.
        return collect($steps)->values()->map(function (array $step, int $i) use ($currentIndex) {
            $step['done'] = $i === 0 || $i <= $currentIndex + 1;
            $step['active'] = $i === $currentIndex + 1;

            return $step;
        })->all();
    }
}
