<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GreenAttribute extends Model
{
    use HasTranslations;

    protected $primaryKey = 'code';
    protected $keyType = 'string';
    public $incrementing = false;

    protected array $translatable = ['label'];

    protected $fillable = ['code', 'label', 'icon', 'display_order'];

    protected function casts(): array
    {
        return ['label' => 'array'];
    }

    public function listings(): BelongsToMany
    {
        return $this->belongsToMany(
            Listing::class,
            'listing_green_attribute',
            'green_attribute_code',
            'listing_id',
            'code'
        );
    }

    public function name(): string
    {
        return $this->translate('label') ?? $this->code;
    }
}
