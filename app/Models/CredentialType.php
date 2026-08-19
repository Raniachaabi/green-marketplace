<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CredentialType extends Model
{
    use HasTranslations;

    protected $primaryKey = 'code';
    protected $keyType = 'string';
    public $incrementing = false;

    protected array $translatable = ['label', 'description'];

    protected $fillable = [
        'code', 'label', 'description', 'issuing_body', 'legal_reference',
        'requires_expiry', 'requires_number', 'requires_document',
    ];

    protected function casts(): array
    {
        return [
            'label' => 'array',
            'description' => 'array',
            'requires_expiry' => 'boolean',
            'requires_number' => 'boolean',
            'requires_document' => 'boolean',
        ];
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(Credential::class, 'credential_type_code', 'code');
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(
            Badge::class,
            'badge_credential_type',
            'credential_type_code',
            'badge_code',
            'code',
            'code'
        );
    }

    public function name(): string
    {
        return $this->translate('label') ?? $this->code;
    }
}
