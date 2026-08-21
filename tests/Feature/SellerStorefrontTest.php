<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesMarketplace;
use Tests\TestCase;

class SellerStorefrontTest extends TestCase
{
    use CreatesMarketplace, RefreshDatabase;

    public function test_the_storefront_shows_only_this_sellers_active_listings(): void
    {
        $seller = $this->seller('Potter');
        $category = $this->category('poterie');

        $active = $this->liveListing($seller, $category, ['title' => ['fr' => 'Vase actif']]);
        $draft = $this->listing($seller, $category, ['title' => ['fr' => 'Brouillon caché']]);

        $otherSeller = $this->seller('Other');
        $otherListing = $this->liveListing($otherSeller, $category, ['title' => ['fr' => 'Annonce d\'un autre']]);

        $response = $this->get(route('seller.storefront', $seller->slug));

        $response->assertOk();
        $response->assertSee('Vase actif');
        $response->assertDontSee('Brouillon caché');
        $response->assertDontSee('Annonce d\'un autre');
    }

    public function test_a_user_who_has_never_sold_anything_has_no_storefront(): void
    {
        $buyer = $this->seller('Just a buyer');

        $this->get(route('seller.storefront', $buyer->slug))->assertNotFound();
    }
}
