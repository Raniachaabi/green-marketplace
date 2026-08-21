<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Organization extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'type', 'legal_name', 'slug', 'matricule_fiscal', 'rne_number',
        'statutes_doc_path', 'story', 'logo_path', 'governorate', 'verified_at',
    ];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime'];
    }

    public function members(): HasMany
    {
        return $this->hasMany(OrganizationMember::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'organization_members')
            ->withPivot(['role', 'revenue_share_pct'])
            ->withTimestamps();
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(Credential::class);
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class, 'seller_org_id');
    }

    public function approvedCredentialCodes(): Collection
    {
        return $this->credentials()
            ->approved()
            ->unexpired()
            ->pluck('credential_type_code')
            ->unique()
            ->values();
    }

    /** Mirrors User::activeBadges() — an organization-held credential earns the same badges. */
    public function activeBadges(): Collection
    {
        return $this->credentials()
            ->with('credentialType.badges')
            ->approved()
            ->unexpired()
            ->get()
            ->flatMap(fn (Credential $c) => $c->credentialType?->badges ?? collect())
            ->unique('code')
            ->values();
    }

    /**
     * FR-005 — member shares must total 100% before a payout can be split.
     * Checked at payout time rather than on save, because a collective is
     * usually assembled over several sittings.
     */
    public function revenueSharesBalance(): float
    {
        return (float) $this->members()->sum('revenue_share_pct');
    }

    public function hasBalancedShares(): bool
    {
        return abs($this->revenueSharesBalance() - 100.0) < 0.01;
    }

    public function displayName(): string
    {
        return $this->legal_name;
    }
}
