<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * FR-037 — the species registry.
 *
 * Two jobs: block invasive species from being sold at all, and warn buyers
 * about plants toxic to children and pets. Both are cheap to build and
 * impossible to bolt on convincingly after an incident.
 */
class Species extends Model
{
    use HasTranslations, HasUuids;

    protected $table = 'species';

    protected array $translatable = ['common_name'];

    protected $fillable = [
        'botanical_name', 'common_name', 'is_native', 'is_invasive_blocked',
        'toxicity_level', 'water_need', 'sun_exposure', 'hardiness',
    ];

    protected function casts(): array
    {
        return [
            'common_name' => 'array',
            'is_native' => 'boolean',
            'is_invasive_blocked' => 'boolean',
        ];
    }

    public function listings(): BelongsToMany
    {
        return $this->belongsToMany(Listing::class, 'listing_species');
    }

    public function scopeSellable(Builder $query): Builder
    {
        return $query->where('is_invasive_blocked', false);
    }

    public function isToxic(): bool
    {
        return in_array($this->toxicity_level, ['mild', 'toxic'], true);
    }

    public function name(): string
    {
        return $this->translate('common_name') ?? $this->botanical_name;
    }
}
