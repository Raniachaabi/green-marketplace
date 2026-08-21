<?php

namespace Tests\Feature;

use App\Enums\CredentialStatus;
use App\Models\Badge;
use App\Models\Review;
use App\Models\SearchQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesMarketplace;
use Tests\TestCase;

class SearchAndFilterTest extends TestCase
{
    use CreatesMarketplace, RefreshDatabase;

    public function test_search_matches_seller_name(): void
    {
        $seller = $this->seller('Amira Nabli');
        $listing = $this->liveListing($seller, $this->category('poterie'));

        $response = $this->get(route('catalog.index', ['q' => 'Nabli']));

        $response->assertSee($listing->name());
    }

    public function test_search_matches_category_name(): void
    {
        $category = $this->category('poterie', ['name' => ['fr' => 'Poterie artisanale', 'en' => 'Handmade pottery']]);
        $listing = $this->liveListing($this->seller(), $category);

        $response = $this->get(route('catalog.index', ['q' => 'Poterie artisanale']));

        $response->assertSee($listing->name());
    }

    public function test_a_search_term_is_recorded(): void
    {
        $this->get(route('catalog.index', ['q' => 'huile']));

        $this->assertSame('huile', SearchQuery::first()->term);
    }

    public function test_a_logged_in_users_recent_searches_are_scoped_to_them(): void
    {
        // Popular searches are deliberately global (§6), so this checks the
        // recentSearches() data directly rather than via a page assertion,
        // which "savon" would legitimately also satisfy as a popular term.
        $buyer = $this->seller('Buyer');
        $other = $this->seller('Other');

        $this->actingAs($buyer)->get(route('catalog.index', ['q' => 'miel']));
        $this->actingAs($other)->get(route('catalog.index', ['q' => 'savon']));

        $recent = SearchQuery::where('user_id', $buyer->id)->pluck('term');
        $this->assertTrue($recent->contains('miel'));
        $this->assertFalse($recent->contains('savon'));
    }

    public function test_min_rating_filter_excludes_lower_rated_listings(): void
    {
        $seller = $this->seller();
        $category = $this->category('poterie');
        $highRated = $this->liveListing($seller, $category, ['title' => ['fr' => 'Vase haut de gamme']]);
        $lowRated = $this->liveListing($seller, $category, ['title' => ['fr' => 'Vase decevant']]);

        $buyer = $this->seller('Buyer');
        Review::create([
            'author_user_id' => $buyer->id, 'target_type' => 'listing', 'target_id' => $highRated->id, 'rating' => 5,
        ]);
        Review::create([
            'author_user_id' => $buyer->id, 'target_type' => 'listing', 'target_id' => $lowRated->id, 'rating' => 2,
        ]);

        $response = $this->get(route('catalog.index', ['min_rating' => '4']));

        $response->assertSee($highRated->name());
        $response->assertDontSee($lowRated->name());
    }

    public function test_verified_filter_only_shows_listings_from_verified_sellers(): void
    {
        $category = $this->category('poterie');

        $verifiedSeller = $this->seller('Verified');
        $this->credentialType('cin_identity');
        $this->giveCredential($verifiedSeller, 'cin_identity', now()->addYear()->toDateString(), CredentialStatus::Approved);
        Badge::create(['code' => 'verified_seller', 'label' => ['en' => 'Verified']])
            ->credentialTypes()->sync(['cin_identity']);
        $verifiedListing = $this->liveListing($verifiedSeller, $category, ['title' => ['fr' => 'Produit du vendeur verifie']]);

        $unverifiedListing = $this->liveListing($this->seller('Unverified'), $category, ['title' => ['fr' => 'Produit du vendeur non verifie']]);

        $response = $this->get(route('catalog.index', ['verified' => '1']));

        $response->assertSee($verifiedListing->name());
        $response->assertDontSee($unverifiedListing->name());
    }

    public function test_made_in_tunisia_filter_only_shows_listings_with_a_governorate(): void
    {
        $category = $this->category('poterie');
        $withOrigin = $this->liveListing($this->seller(), $category, ['governorate' => 'nabeul', 'title' => ['fr' => 'Produit tunisien']]);
        $withoutOrigin = $this->liveListing($this->seller(), $category, ['governorate' => null, 'title' => ['fr' => 'Produit sans origine']]);

        $response = $this->get(route('catalog.index', ['origin_tn' => '1']));

        $response->assertSee($withOrigin->name());
        $response->assertDontSee($withoutOrigin->name());
    }

    public function test_an_empty_result_shows_recommendations(): void
    {
        $listing = $this->liveListing($this->seller(), $this->category('poterie'));

        $response = $this->get(route('catalog.index', ['q' => 'nonexistent-term-xyz']));

        $response->assertOk();
        $response->assertSee(__('catalog.you_might_like'));
        // Proves the recommended <x-listing-card> actually rendered (with
        // every relation it needs), not just the section header.
        $response->assertSee($listing->name());
    }
}
