<?php

namespace App\Models;

use App\Enums\GreenScoreCheckType;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class GreenScoreRule extends Model
{
    use HasTranslations, HasUuids;

    protected array $translatable = ['name', 'description'];

    protected $fillable = [
        'code', 'name', 'description', 'icon', 'category',
        'check_type', 'check_value', 'points', 'is_active', 'display_order',
    ];

    protected function casts(): array
    {
        return [
            'check_type' => GreenScoreCheckType::class,
            'points' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function name(): string
    {
        return $this->translate('name') ?? $this->code;
    }

    public function description(): ?string
    {
        return $this->translate('description');
    }
}
