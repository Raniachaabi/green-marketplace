<?php

namespace App\Models;

use App\Enums\AvailabilityModel;
use App\Enums\ListingStatus;
use App\Models\Concerns\HasTranslations;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Listing extends Model
{
    use HasFactory, HasTranslations, HasUuids, SoftDeletes;

    protected array $translatable = [
        'title', 'description', 'story', 'ingredients_materials', 'packaging_info', 'care_instructions',
    ];

    protected $fillable = [
        'seller_user_id', 'seller_org_id', 'category_id', 'slug', 'title',
        'description', 'price', 'unit', 'stock', 'min_order_qty',
        'availability_model', 'lead_time_days', 'season_start', 'season_end',
        'lot_number', 'attribute_values', 'governorate', 'status', 'status_reason',
        'published_at', 'suspended_at',
        // Origin (§10) and storytelling (§9) — all optional.
        'origin_locality', 'story', 'production_process',
        'ingredients_materials', 'packaging_info', 'care_instructions',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'description' => 'array',
            // Deliberately NOT named `attributes`: that shadows Eloquent's
            // internal $attributes property and breaks reads from inside the
            // model. Costs one rename, saves a very confusing afternoon.
            'attribute_values' => 'array',
            'production_process' => 'array',
            'price' => 'integer',
            'stock' => 'integer',
            'min_order_qty' => 'integer',
            'lead_time_days' => 'integer',
            'season_start' => 'date',
            'season_end' => 'date',
            'published_at' => 'datetime',
            'suspended_at' => 'datetime',
            'origin_verified_at' => 'datetime',
            'status' => ListingStatus::class,
            'availability_model' => AvailabilityModel::class,
        ];
    }

    // -------------------------------------------------------- relations

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function sellerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_user_id');
    }

    public function sellerOrg(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'seller_org_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(ListingMedia::class)->orderBy('position');
    }

    public function greenAttributes(): BelongsToMany
    {
        return $this->belongsToMany(
            GreenAttribute::class,
            'listing_green_attribute',
            'listing_id',
            'green_attribute_code',
            'id',
            'code'
        );
    }

    public function species(): BelongsToMany
    {
        return $this->belongsToMany(Species::class, 'listing_species');
    }

    public function orderLines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'target_id')->where('target_type', 'listing');
    }

    // ----------------------------------------------------------- scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ListingStatus::Active);
    }

    /**
     * FR-033 — a seasonal listing is hidden outside its window. The window is
     * stored as month-day pairs conceptually but as dates here; comparison is
     * on day-of-year so it survives the year rolling over.
     */
    public function scopeInSeason(Builder $query, ?string $on = null): Builder
    {
        $date = $on ? \Carbon\Carbon::parse($on) : now();

        return $query->where(function (Builder $q) use ($date) {
            $q->where('availability_model', '!=', AvailabilityModel::Seasonal->value)
                ->orWhere(function (Builder $inner) use ($date) {
                    $inner->whereNotNull('season_start')
                        ->whereNotNull('season_end')
                        ->whereDate('season_start', '<=', $date->toDateString())
                        ->whereDate('season_end', '>=', $date->toDateString());
                });
        });
    }

    public function scopeInGovernorate(Builder $query, ?string $governorate): Builder
    {
        return $governorate ? $query->where('governorate', $governorate) : $query;
    }

    public function scopeWithGreenAttribute(Builder $query, string|array $codes): Builder
    {
        $codes = (array) $codes;

        return $query->whereHas('greenAttributes', fn ($q) => $q->whereIn('code', $codes));
    }

    // ------------------------------------------------------------ state

    public function seller(): User|Organization|null
    {
        return $this->sellerOrg ?? $this->sellerUser;
    }

    /** FR-102 — buyers must always see who they are actually buying from. */
    public function sellerLabel(): string
    {
        $seller = $this->seller();

        if ($seller instanceof Organization) {
            return $seller->legal_name.' — '.__('organization.type.'.$seller->type);
        }

        return $seller?->full_name ?? __('common.unknown_seller');
    }

    public function name(): string
    {
        return $this->translate('title') ?? $this->slug;
    }

    public function formattedPrice(): string
    {
        return Money::format((int) $this->price).' / '.__('unit.'.$this->unit);
    }

    public function averageRating(): ?float
    {
        if (! $this->relationLoaded('reviews')) {
            return $this->reviews()->avg('rating');
        }

        return $this->reviews->isEmpty() ? null : round($this->reviews->avg('rating'), 1);
    }

    public function reviewCount(): int
    {
        return $this->relationLoaded('reviews') ? $this->reviews->count() : $this->reviews()->count();
    }

    /** Read one category-driven attribute value. */
    public function attr(string $key, mixed $default = null): mixed
    {
        return data_get($this->attribute_values ?? [], $key, $default);
    }

    public function setAttr(string $key, mixed $value): static
    {
        $values = $this->attribute_values ?? [];
        $values[$key] = $value;
        $this->attribute_values = $values;

        return $this;
    }

    public function isPurchasable(): bool
    {
        if ($this->status !== ListingStatus::Active) {
            return false;
        }

        if ($this->availability_model->tracksStock() && $this->stock <= 0) {
            return false;
        }

        return true;
    }

    // ------------------------------------------------------------ origin

    public function originVerifiedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'origin_verified_by_admin_id');
    }

    /** §10 — never automatic. True only once an admin has actually checked it. */
    public function isOriginVerified(): bool
    {
        return $this->origin_verified_at !== null;
    }

    /**
     * Production-process steps, in order, for one locale — empty if the
     * seller never filled this in. Stored as {"ar": [...], "fr": [...], ...},
     * one locale edited at a time (same convention as `description`).
     */
    public function productionProcessSteps(?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $steps = $this->production_process ?? [];

        if (! empty($steps[$locale])) {
            return array_values(array_filter($steps[$locale]));
        }

        foreach ([config('app.fallback_locale'), 'fr', 'ar', 'en'] as $fallback) {
            if (! empty($steps[$fallback])) {
                return array_values(array_filter($steps[$fallback]));
            }
        }

        return [];
    }

    public function isInSeason(): bool
    {
        if ($this->availability_model !== AvailabilityModel::Seasonal) {
            return true;
        }

        if (! $this->season_start || ! $this->season_end) {
            return false;
        }

        return now()->betweenIncluded($this->season_start, $this->season_end);
    }
}
