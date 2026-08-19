<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Badge extends Model
{
    use HasTranslations;

    protected $primaryKey = 'code';
    protected $keyType = 'string';
    public $incrementing = false;

    protected array $translatable = ['label', 'description'];

    protected $fillable = ['code', 'label', 'description', 'icon', 'colour'];

    protected function casts(): array
    {
        return ['label' => 'array', 'description' => 'array'];
    }

    public function credentialTypes(): BelongsToMany
    {
        return $this->belongsToMany(
            CredentialType::class,
            'badge_credential_type',
            'badge_code',
            'credential_type_code',
            'code',
            'code'
        );
    }

    public function name(): string
    {
        return $this->translate('label') ?? $this->code;
    }

    /** FR-024 — the disclosure page URL for this badge. */
    public function disclosureUrl(): string
    {
        return route('badges.show', $this->code);
    }
}
