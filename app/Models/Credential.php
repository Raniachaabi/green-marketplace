<?php

namespace App\Models;

use App\Enums\CredentialStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Credential extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id', 'organization_id', 'credential_type_code', 'number', 'issuer',
        'issued_at', 'expires_at', 'document_path', 'status',
        'verified_by_admin_id', 'verified_at', 'rejection_reason', 'reminders_sent',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'expires_at' => 'date',
            'verified_at' => 'datetime',
            'status' => CredentialStatus::class,
            'reminders_sent' => 'array',
        ];
    }

    public function credentialType(): BelongsTo
    {
        return $this->belongsTo(CredentialType::class, 'credential_type_code', 'code');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_admin_id');
    }

    // ------------------------------------------------------------- scopes

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', CredentialStatus::Approved);
    }

    public function scopeUnexpired(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('expires_at')->orWhereDate('expires_at', '>=', now()->toDateString());
        });
    }

    public function scopeExpiringWithin(Builder $query, int $days): Builder
    {
        return $query->approved()
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '>=', now()->toDateString())
            ->whereDate('expires_at', '<=', now()->addDays($days)->toDateString());
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', CredentialStatus::Pending);
    }

    // -------------------------------------------------------------- state

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isUsable(): bool
    {
        return $this->status === CredentialStatus::Approved && ! $this->isExpired();
    }

    public function daysUntilExpiry(): ?int
    {
        if (! $this->expires_at) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->expires_at->startOfDay(), false);
    }

    public function expiresAt(): ?CarbonInterface
    {
        return $this->expires_at;
    }

    /** Who this credential belongs to — an individual or a collective. */
    public function holder(): User|Organization|null
    {
        return $this->organization ?? $this->user;
    }
}
