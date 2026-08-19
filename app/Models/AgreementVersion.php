<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgreementVersion extends Model
{
    use HasTranslations, HasUuids;

    protected array $translatable = ['title'];

    protected $fillable = ['type', 'version', 'title', 'body_path', 'effective_from'];

    protected function casts(): array
    {
        return ['title' => 'array', 'effective_from' => 'date'];
    }

    public function acceptances(): HasMany
    {
        return $this->hasMany(AgreementAcceptance::class);
    }

    /** The version currently in force for a given agreement type. */
    public static function current(string $type): ?self
    {
        return static::where('type', $type)
            ->whereDate('effective_from', '<=', now()->toDateString())
            ->orderByDesc('effective_from')
            ->first();
    }
}
