<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryRule extends Model
{
    use HasUuids;

    protected $fillable = [
        'category_id', 'is_fragile', 'is_perishable', 'is_live', 'is_heavy',
        'needs_cold_chain', 'max_delivery_days', 'min_lead_time_days',
        'allows_group_booking', 'requires_lot_number',
    ];

    protected function casts(): array
    {
        return [
            'is_fragile' => 'boolean',
            'is_perishable' => 'boolean',
            'is_live' => 'boolean',
            'is_heavy' => 'boolean',
            'needs_cold_chain' => 'boolean',
            'allows_group_booking' => 'boolean',
            'requires_lot_number' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** Flags handed to the carrier so a parcel is handled correctly. */
    public function handlingFlags(): array
    {
        return collect([
            'fragile' => $this->is_fragile,
            'perishable' => $this->is_perishable,
            'live' => $this->is_live,
            'heavy' => $this->is_heavy,
            'cold_chain' => $this->needs_cold_chain,
        ])->filter()->keys()->all();
    }
}
