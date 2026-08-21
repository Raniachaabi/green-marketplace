<?php

namespace Tests\Feature;

use App\Enums\CredentialStatus;
use App\Models\Badge;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesMarketplace;
use Tests\TestCase;

class StorefrontRedesignTest extends TestCase
{
    use CreatesMarketplace, RefreshDatabase;

    public function test_the_storefront_shows_a_verified_badge_only_for_a_verified_seller(): void
    {
        $category = $this->category('poterie');

        $verified = $this->seller('Verified');
        $this->credentialType('cin_identity');
        $this->giveCredential($verified, 'cin_identity', now()->addYear()->toDateString(), CredentialStatus::Approved);
        Badge::create(['code' => 'verified_seller', 'label' => ['en' => 'Verified']])
            ->credentialTypes()->sync(['cin_identity']);
        $this->liveListing($verified, $category);

        $unverified = $this->seller('Unverified');
        $this->liveListing($unverified, $category);

        $this->get(route('seller.storefront', $verified->slug))->assertSee(__('home.verified_seller'));
        $this->get(route('seller.storefront', $unverified->slug))->assertDontSee(__('home.verified_seller'));
    }

    public function test_the_reviews_tab_shows_real_reviews_from_across_the_sellers_listings(): void
    {
        $seller = $this->seller('Seller');
        $buyer = $this->seller('Buyer');
        $category = $this->category('poterie');
        $listing = $this->liveListing($seller, $category, ['title' => ['fr' => 'Vase artisanal']]);

        Review::create([
            'author_user_id' => $buyer->id, 'target_type' => 'listing', 'target_id' => $listing->id,
            'rating' => 5, 'body' => 'Superbe qualite, tres satisfait.',
        ]);

        $response = $this->get(route('seller.storefront', $seller->slug));

        $response->assertSee('Superbe qualite, tres satisfait.');
        $response->assertSee($buyer->full_name);
        $response->assertSee('Vase artisanal');
    }

    public function test_the_reviews_tab_shows_an_empty_state_when_there_are_no_reviews(): void
    {
        $seller = $this->seller('Seller');
        $this->liveListing($seller, $this->category('poterie'));

        $this->get(route('seller.storefront', $seller->slug))->assertSee(__('seller.no_reviews'));
    }

    public function test_the_certifications_tab_lists_the_sellers_active_badges(): void
    {
        $seller = $this->seller('Seller');
        $this->credentialType('cin_identity');
        $this->giveCredential($seller, 'cin_identity', now()->addYear()->toDateString(), CredentialStatus::Approved);
        Badge::create(['code' => 'verified_seller', 'label' => ['en' => 'Verified Seller']])
            ->credentialTypes()->sync(['cin_identity']);
        $this->liveListing($seller, $this->category('poterie'));

        $this->get(route('seller.storefront', $seller->slug))->assertSee('Verified Seller');
    }

    public function test_the_certifications_tab_shows_an_empty_state_with_no_badges(): void
    {
        $seller = $this->seller('Seller');
        $this->liveListing($seller, $this->category('poterie'));

        $this->get(route('seller.storefront', $seller->slug))->assertSee(__('seller.no_certifications'));
    }
}
