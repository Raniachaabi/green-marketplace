<?php

namespace App\Models;

use App\Enums\UserStatus;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class User extends Authenticatable implements FilamentUser, HasName
{
    use HasFactory, HasUuids, Notifiable;

    protected $fillable = [
        'phone', 'email', 'full_name', 'password', 'preferred_locale',
        'status', 'bio', 'slug', 'avatar_path',
    ];

    protected $hidden = ['password', 'remember_token'];

    /** Per-request memoization for wishlistedListingIds() — not a DB column. */
    protected ?Collection $wishlistedIds = null;

    protected function casts(): array
    {
        return [
            'phone_verified_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'cin_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'status' => UserStatus::class,
        ];
    }

    public function isSuspended(): bool
    {
        return $this->status === UserStatus::Suspended;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }

    // ---------------------------------------------------------------- roles

    public function roles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    public function hasRole(string $role): bool
    {
        return $this->roles->contains('role', $role);
    }

    public function grantRole(string $role): void
    {
        $this->roles()->firstOrCreate(['role' => $role]);
        $this->unsetRelation('roles');
    }

    // --------------------------------------------------------- associations

    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationMember::class);
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class, 'organization_members')
            ->withPivot(['role', 'revenue_share_pct'])
            ->withTimestamps();
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(Credential::class);
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class, 'seller_user_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'buyer_user_id');
    }

    public function wishlist(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * Memoized on the instance so a grid of listing cards — each asking
     * "is this one wishlisted?" — costs one query per request, not one
     * per card. `auth()->user()` returns the same instance throughout a
     * request, which is what makes the memoization actually work.
     */
    public function wishlistedListingIds(): Collection
    {
        return $this->wishlistedIds ??= $this->wishlist()->pluck('listing_id');
    }

    public function hasWishlisted(string $listingId): bool
    {
        return $this->wishlistedListingIds()->contains($listingId);
    }

    public function agreementAcceptances(): HasMany
    {
        return $this->hasMany(AgreementAcceptance::class);
    }

    // ------------------------------------------------------------- badges

    /**
     * Badges the seller currently holds. Deliberately computed from
     * *approved and unexpired* credentials only — never cached on the user
     * row, because a stale badge is the exact failure mode this platform
     * cannot afford.
     */
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

    /** Credential type codes this user can currently prove. */
    public function approvedCredentialCodes(): Collection
    {
        return $this->credentials()
            ->approved()
            ->unexpired()
            ->pluck('credential_type_code')
            ->unique()
            ->values();
    }

    public function displayName(): string
    {
        return $this->full_name;
    }

    public function getFilamentName(): string
    {
        return $this->full_name;
    }
}
