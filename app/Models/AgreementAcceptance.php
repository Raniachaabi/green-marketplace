<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgreementAcceptance extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'agreement_version_id', 'accepted_at', 'ip'];

    protected function casts(): array
    {
        return ['accepted_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agreementVersion(): BelongsTo
    {
        return $this->belongsTo(AgreementVersion::class);
    }
}
