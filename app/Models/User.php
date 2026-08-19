<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, HasUuids, Notifiable;

    protected $fillable = [
        'phone', 'email', 'full_name', 'password', 'preferred_locale',
        'status', 'bio', 'slug', 'avatar_path',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'phone_verified_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'cin_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
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
}
