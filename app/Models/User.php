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
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements FilamentUser, HasName
{
    use HasFactory, HasUuids, Notifiable;

    protected $fillable = [
        'phone', 'email', 'full_name', 'password', 'preferred_locale',
        'status', 'bio', 'slug', 'avatar_path', 'notify_social', 'notify_announcements',
        'cover_path', 'story', 'production_method', 'mission', 'founding_year',
    ];

    protected $hidden = ['password', 'remember_token'];

    /** Per-request memoization for wishlistedListingIds() — not a DB column. */
    protected ?Collection $wishlistedIds = null;

    /** Per-request memoization for followedSellerIds() — not a DB column. */
    protected ?Collection $followedSellerIdsCache = null;

    protected function casts(): array
    {
        return [
            'phone_verified_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'cin_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'status' => UserStatus::class,
            'notify_social' => 'boolean',
            'notify_announcements' => 'boolean',
        ];
    }

    public function isSuspended(): bool
    {
        return $this->status === UserStatus::Suspended;
    }

    /**
     * §1 — discretionary notifications only (a followed seller's activity,
     * restock alerts). Transactional notifications (orders, shipments,
     * credentials, moderation) never check this — they always send.
     */
    public function wantsSocialNotifications(): bool
    {
        return (bool) $this->notify_social;
    }

    public function wantsAnnouncements(): bool
    {
        return (bool) $this->notify_announcements;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // A freshly created instance can have is_admin still unset (null) in
        // memory until the model is re-fetched — cast explicitly so a plain
        // buyer visiting /admin gets a clean deny, not a TypeError.
        return (bool) $this->is_admin;
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

    // -------------------------------------------------------------- follows

    /** Sellers this user (as a buyer) follows. */
    public function following(): HasMany
    {
        return $this->hasMany(Follow::class, 'follower_user_id');
    }

    /** Follow rows where this user is the seller being followed. */
    public function followers(): HasMany
    {
        return $this->hasMany(Follow::class, 'seller_user_id');
    }

    /** Memoized per request, same reasoning as wishlistedListingIds(). */
    public function followedSellerIds(): Collection
    {
        return $this->followedSellerIdsCache ??= $this->following()->pluck('seller_user_id');
    }

    public function isFollowing(string $sellerUserId): bool
    {
        return $this->followedSellerIds()->contains($sellerUserId);
    }

    public function followerCount(): int
    {
        return $this->relationLoaded('followers') ? $this->followers->count() : $this->followers()->count();
    }

    /** Same "verified_seller" badge definition the catalog's verified-only filter uses. */
    public function isVerifiedSeller(): bool
    {
        return $this->credentials()
            ->approved()->unexpired()
            ->whereHas('credentialType.badges', fn ($b) => $b->where('badges.code', 'verified_seller'))
            ->exists();
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

    /** §8/§14 — whether there's anything to show in the storefront's "Our Story" tab. */
    public function hasStory(): bool
    {
        return filled($this->story) || filled($this->production_method) || filled($this->mission) || $this->founding_year !== null;
    }

    public function yearsActive(): ?int
    {
        return $this->founding_year ? max(0, now()->year - $this->founding_year) : null;
    }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null;
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
