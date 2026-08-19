<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * State-fixed prices (FR-014 / LC-02).
 *
 * The Ministry of Agriculture publishes ceiling prices for selected seed each
 * season. The platform enforces them at listing time and says so publicly —
 * turning a constraint into a trust signal rather than hiding it.
 */
class PriceCap extends Model
{
    use HasUuids;

    protected $fillable = [
        'category_id', 'season_label', 'unit', 'max_price',
        'contract_surcharge_pct', 'effective_from', 'effective_to', 'source_ref',
    ];

    protected function casts(): array
    {
        return [
            'max_price' => 'integer',
            'contract_surcharge_pct' => 'decimal:2',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Ceiling in millimes. Varieties under a commercial exploitation contract
     * carry an additional percentage covering plant variety rights.
     */
    public function ceilingFor(bool $underContract = false): int
    {
        if (! $underContract) {
            return (int) $this->max_price;
        }

        return (int) $this->max_price
            + Money::percent((int) $this->max_price, (float) $this->contract_surcharge_pct);
    }

    public function formattedCeiling(bool $underContract = false): string
    {
        return Money::format($this->ceilingFor($underContract)).' / '.$this->unit;
    }
}
