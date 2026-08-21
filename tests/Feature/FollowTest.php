<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Follow;
use App\Notifications\FollowedSellerNewListing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\CreatesMarketplace;
use Tests\TestCase;

class FollowTest extends TestCase
{
    use CreatesMarketplace, RefreshDatabase;

    public function test_a_buyer_can_follow_and_unfollow_a_seller(): void
    {
        $buyer = $this->seller('Buyer');
        $seller = $this->seller('Seller');
        $this->liveListing($seller, $this->category('cosmetics'));

        $this->actingAs($buyer)->post(route('seller.follow', $seller->slug))->assertRedirect();
        $this->assertSame(1, Follow::count());
        $this->assertTrue($buyer->isFollowing($seller->id));

        $this->actingAs($buyer)->delete(route('seller.unfollow', $seller->slug))->assertRedirect();
        $this->assertSame(0, Follow::count());
    }

    public function test_following_the_same_seller_twice_does_not_duplicate(): void
    {
        $buyer = $this->seller('Buyer');
        $seller = $this->seller('Seller');
        $this->liveListing($seller, $this->category('cosmetics'));

        $this->actingAs($buyer)->post(route('seller.follow', $seller->slug));
        $this->actingAs($buyer)->post(route('seller.follow', $seller->slug));

        $this->assertSame(1, Follow::count());
    }

    public function test_a_seller_cannot_follow_themselves(): void
    {
        $seller = $this->seller('Seller');
        $this->liveListing($seller, $this->category('cosmetics'));

        $this->actingAs($seller)->post(route('seller.follow', $seller->slug))->assertStatus(422);
        $this->assertSame(0, Follow::count());
    }

    public function test_a_guest_cannot_follow_a_seller(): void
    {
        $seller = $this->seller('Seller');
        $this->liveListing($seller, $this->category('cosmetics'));

        $this->post(route('seller.follow', $seller->slug))->assertRedirect(route('login.show'));
    }

    public function test_the_following_page_only_shows_the_current_users_follows(): void
    {
        $buyer = $this->seller('Buyer');
        $other = $this->seller('Other');
        $sellerA = $this->seller('SellerA');
        $sellerB = $this->seller('SellerB');
        $category = $this->category('cosmetics');
        $this->liveListing($sellerA, $category);
        $this->liveListing($sellerB, $category);

        $buyer->following()->create(['seller_user_id' => $sellerA->id]);
        $other->following()->create(['seller_user_id' => $sellerB->id]);

        $response = $this->actingAs($buyer)->get(route('following.index'));

        $response->assertSee($sellerA->full_name);
        $response->assertDontSee($sellerB->full_name);
    }

    public function test_followers_are_notified_when_a_followed_seller_publishes_a_new_listing(): void
    {
        Notification::fake();

        $buyer = $this->seller('Buyer');
        $seller = $this->seller('Seller');
        $buyer->following()->create(['seller_user_id' => $seller->id]);

        $listing = $this->listing($seller, $this->category('cosmetics'));
        $listing->forceFill(['status' => ListingStatus::Active, 'published_at' => now()])->save();

        Notification::assertSentTo($buyer, FollowedSellerNewListing::class);
    }

    public function test_followers_are_not_re_notified_when_an_already_published_listing_is_edited_and_reapproved(): void
    {
        $buyer = $this->seller('Buyer');
        $seller = $this->seller('Seller');
        $buyer->following()->create(['seller_user_id' => $seller->id]);

        $listing = $this->liveListing($seller, $this->category('cosmetics'));

        Notification::fake();

        // Simulate the observer's own transition: pending after edit, then re-approved.
        $listing->forceFill(['status' => ListingStatus::Pending])->save();
        $listing->forceFill(['status' => ListingStatus::Active])->save();

        Notification::assertNotSentTo($buyer, FollowedSellerNewListing::class);
    }
}
