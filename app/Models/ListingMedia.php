<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ListingMedia extends Model
{
    use HasTranslations, HasUuids;

    protected $table = 'listing_media';

    protected array $translatable = ['alt'];

    protected $fillable = ['listing_id', 'path', 'type', 'alt', 'position'];

    protected function casts(): array
    {
        return ['alt' => 'array'];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
