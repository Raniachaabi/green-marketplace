<?php

namespace Tests\Feature;

use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesMarketplace;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use CreatesMarketplace, RefreshDatabase;

    public function test_a_buyer_can_add_and_remove_a_listing(): void
    {
        $buyer = $this->seller('Buyer');
        $listing = $this->liveListing($this->seller('Seller'), $this->category('poterie'));

        $this->actingAs($buyer)->post(route('wishlist.store', $listing))->assertRedirect();
        $this->assertSame(1, Wishlist::where('user_id', $buyer->id)->where('listing_id', $listing->id)->count());

        $this->actingAs($buyer)->delete(route('wishlist.destroy', $listing))->assertRedirect();
        $this->assertSame(0, Wishlist::where('user_id', $buyer->id)->where('listing_id', $listing->id)->count());
    }

    public function test_adding_the_same_listing_twice_does_not_duplicate(): void
    {
        $buyer = $this->seller('Buyer');
        $listing = $this->liveListing($this->seller('Seller'), $this->category('poterie'));

        $this->actingAs($buyer)->post(route('wishlist.store', $listing));
        $this->actingAs($buyer)->post(route('wishlist.store', $listing));

        $this->assertSame(1, Wishlist::where('user_id', $buyer->id)->where('listing_id', $listing->id)->count());
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $listing = $this->liveListing($this->seller('Seller'), $this->category('poterie'));

        $this->post(route('wishlist.store', $listing))->assertRedirect(route('login.show'));
    }

    public function test_the_wishlist_page_only_shows_the_current_users_items(): void
    {
        $buyer = $this->seller('Buyer');
        $other = $this->seller('Other');
        $category = $this->category('poterie');

        $mine = $this->liveListing($this->seller('Seller'), $category, ['title' => ['fr' => 'Vase en terre cuite']]);
        $theirs = $this->liveListing($this->seller('Seller'), $category, ['title' => ['fr' => 'Panier tressé']]);

        $buyer->wishlist()->create(['listing_id' => $mine->id]);
        $other->wishlist()->create(['listing_id' => $theirs->id]);

        $response = $this->actingAs($buyer)->get(route('wishlist.index'));

        $response->assertSee($mine->name());
        $response->assertDontSee($theirs->name());
    }
}
