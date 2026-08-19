<?php

namespace Tests;

use App\Enums\CredentialStatus;
use App\Enums\ListingStatus;
use App\Models\Category;
use App\Models\CategoryField;
use App\Models\CategoryRequirement;
use App\Models\CategoryRule;
use App\Models\Credential;
use App\Models\CredentialType;
use App\Models\Listing;
use App\Models\ListingMedia;
use App\Models\PriceCap;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Str;

/**
 * Small builders so each test reads as the rule it is checking, rather than
 * ten lines of fixture setup.
 */
trait CreatesMarketplace
{
    protected function credentialType(string $code, bool $requiresExpiry = true): CredentialType
    {
        return CredentialType::create([
            'code' => $code,
            'label' => ['fr' => $code, 'en' => $code, 'ar' => $code],
            'requires_expiry' => $requiresExpiry,
        ]);
    }

    protected function category(string $slug, array $overrides = []): Category
    {
        return Category::create(array_merge([
            'slug' => $slug,
            'name' => ['fr' => $slug, 'en' => $slug, 'ar' => $slug],
            'path' => $slug,
            'is_leaf' => true,
            'listing_type' => 'product',
        ], $overrides));
    }

    protected function requireCredential(Category $category, string $code): CategoryRequirement
    {
        return CategoryRequirement::create([
            'category_id' => $category->id,
            'credential_type_code' => $code,
            'is_mandatory' => true,
        ]);
    }

    protected function requiredField(Category $category, string $key, string $type = 'string'): CategoryField
    {
        return CategoryField::create([
            'category_id' => $category->id,
            'key' => $key,
            'label' => ['fr' => $key, 'en' => $key, 'ar' => $key],
            'data_type' => $type,
            'required' => true,
        ]);
    }

    protected function rule(Category $category, array $flags): CategoryRule
    {
        return CategoryRule::create(array_merge(['category_id' => $category->id], $flags));
    }

    protected function priceCap(Category $category, float $dinars, string $unit = 'quintal', float $surcharge = 0): PriceCap
    {
        return PriceCap::create([
            'category_id' => $category->id,
            'season_label' => '2026/2027',
            'unit' => $unit,
            'max_price' => Money::fromDinars($dinars),
            'contract_surcharge_pct' => $surcharge,
            'effective_from' => now()->subDay()->toDateString(),
        ]);
    }

    protected function seller(string $name = 'Seller'): User
    {
        return User::create([
            'phone' => '+216'.random_int(10_000_000, 99_999_999),
            'full_name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(4),
        ]);
    }

    protected function giveCredential(
        User $user,
        string $code,
        ?string $expires = null,
        CredentialStatus $status = CredentialStatus::Approved,
    ): Credential {
        return Credential::create([
            'user_id' => $user->id,
            'credential_type_code' => $code,
            'number' => 'NUM-'.Str::random(5),
            'expires_at' => $expires,
            'status' => $status,
            'verified_at' => now(),
        ]);
    }

    protected function listing(User $seller, Category $category, array $overrides = []): Listing
    {
        $skipMedia = (bool) ($overrides['skip_media'] ?? false);
        unset($overrides['skip_media']);

        $listing = Listing::create(array_merge([
            'seller_user_id' => $seller->id,
            'category_id' => $category->id,
            'title' => ['fr' => 'Article de test', 'en' => 'Test item', 'ar' => 'منتج تجريبي'],
            'price' => Money::fromDinars(100),
            'unit' => 'piece',
            'stock' => 10,
            'availability_model' => 'in_stock',
        ], $overrides));

        // The gate requires at least one image, so every builder-made listing
        // gets one unless a test is specifically about missing media.
        if (! $skipMedia) {
            ListingMedia::create([
                'listing_id' => $listing->id,
                'path' => 'test/placeholder.jpg',
                'position' => 0,
            ]);
        }

        return $listing->fresh();
    }

    /**
     * A listing that is actually on sale.
     *
     * Checkout tests need this: the cart deliberately refuses to sell a draft,
     * so a builder that only ever produces drafts makes every checkout test
     * fail for the wrong reason — or worse, pass for the wrong reason.
     */
    protected function liveListing(User $seller, Category $category, array $overrides = []): Listing
    {
        return $this->listing($seller, $category, array_merge([
            'status' => ListingStatus::Active,
            'published_at' => now(),
        ], $overrides));
    }
}
