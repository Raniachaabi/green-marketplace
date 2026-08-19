<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryRequirement extends Model
{
    use HasTranslations, HasUuids;

    protected array $translatable = ['note'];

    protected $fillable = ['category_id', 'credential_type_code', 'is_mandatory', 'note'];

    protected function casts(): array
    {
        return ['is_mandatory' => 'boolean', 'note' => 'array'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function credentialType(): BelongsTo
    {
        return $this->belongsTo(CredentialType::class, 'credential_type_code', 'code');
    }
}
