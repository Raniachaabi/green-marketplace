<?php

namespace App\Models;

use App\Enums\ListingType;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

/**
 * The category is the engine of this marketplace.
 *
 * It declares what a seller must prove, what a listing must contain, how the
 * goods travel and what they may cost. Adding "pepiniere" or "atelier
 * scolaire" is configuration — rows in category_requirements, category_fields
 * and category_rules — not a migration and not a deploy.
 */
class Category extends Model
{
    use HasFactory, HasTranslations, HasUuids;

    protected array $translatable = ['name', 'description'];

    protected $fillable = [
        'parent_id', 'slug', 'name', 'description', 'path', 'is_leaf',
        'listing_type', 'icon', 'display_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'is_leaf' => 'boolean',
            'is_active' => 'boolean',
            'listing_type' => ListingType::class,
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('display_order');
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(CategoryRequirement::class);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(CategoryField::class)->orderBy('display_order');
    }

    public function rule(): HasOne
    {
        return $this->hasOne(CategoryRule::class);
    }

    public function priceCaps(): HasMany
    {
        return $this->hasMany(PriceCap::class);
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * A category and every descendant beneath it in the path tree — bounded
     * on a "/" so a sibling whose slug merely starts with the same
     * characters (e.g. "vegetal2" next to "vegetal") is never mistaken for
     * a child.
     */
    public function scopeSelfAndDescendantsOf(Builder $query, self $category): Builder
    {
        return $query->where(function (Builder $q) use ($category) {
            $q->where('path', $category->path)->orWhere('path', 'like', $category->path.'/%');
        });
    }

    // ------------------------------------------------------- inheritance
    //
    // Requirements and rules cascade down the tree. Putting
    // "sanitary authorization" on the "Alimentation" branch means every leaf
    // beneath it inherits it, so a new jam subcategory can never be created
    // accidentally unregulated.

    /** Max depth guard — the tree is a taxonomy, not a linked list. */
    private const MAX_DEPTH = 12;

    /** This category plus all of its ancestors, nearest first, root last. */
    public function lineage(): Collection
    {
        $chain = collect([$this]);
        $node = $this;
        $depth = 0;

        while ($node->parent_id !== null && ++$depth < self::MAX_DEPTH) {
            $parent = self::find($node->parent_id);

            if (! $parent) {
                break;
            }

            $chain->push($parent);
            $node = $parent;
        }

        return $chain;
    }

    /** All requirements applying here, including inherited ones. */
    public function effectiveRequirements(): Collection
    {
        return $this->lineage()
            ->flatMap(fn (self $c) => $c->requirements()->with('credentialType')->get())
            ->unique('credential_type_code')
            ->values();
    }

    public function mandatoryCredentialCodes(): Collection
    {
        return $this->effectiveRequirements()
            ->where('is_mandatory', true)
            ->pluck('credential_type_code')
            ->values();
    }

    /** All dynamic listing fields applying here, including inherited ones. */
    public function effectiveFields(): Collection
    {
        // Nearest-first, so a child's redefinition of a key wins over the
        // ancestor's. Form order then comes from display_order.
        return $this->lineage()
            ->flatMap(fn (self $c) => $c->fields()->get())
            ->unique('key')
            ->sortBy('display_order')
            ->values();
    }

    /** The nearest rule up the tree, since most branches share handling. */
    public function effectiveRule(): ?CategoryRule
    {
        foreach ($this->lineage() as $node) {
            $rule = $node->rule()->first();
            if ($rule) {
                return $rule;
            }
        }

        return null;
    }

    public function activePriceCap(?string $on = null): ?PriceCap
    {
        $date = $on ?: now()->toDateString();

        foreach ($this->lineage() as $node) {
            $cap = $node->priceCaps()
                ->whereDate('effective_from', '<=', $date)
                ->where(function ($q) use ($date) {
                    $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date);
                })
                ->orderByDesc('effective_from')
                ->first();

            if ($cap) {
                return $cap;
            }
        }

        return null;
    }

    public function name(): string
    {
        return $this->translate('name') ?? $this->slug;
    }
}
