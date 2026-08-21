<?php

namespace Tests\Feature;

use App\Models\RestockAlert;
use App\Notifications\RestockAvailable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\CreatesMarketplace;
use Tests\TestCase;

class RestockAlertTest extends TestCase
{
    use CreatesMarketplace, RefreshDatabase;

    public function test_a_buyer_can_subscribe_to_an_out_of_stock_listing(): void
    {
        $buyer = $this->seller('Buyer');
        $listing = $this->liveListing($this->seller('Seller'), $this->category('cosmetics'), ['stock' => 0]);

        $this->actingAs($buyer)->post(route('restock_alerts.store', $listing))->assertRedirect();

        $this->assertSame(1, RestockAlert::count());
        $this->assertNull(RestockAlert::first()->notified_at);
    }

    public function test_cannot_subscribe_to_a_listing_that_has_stock(): void
    {
        $buyer = $this->seller('Buyer');
        $listing = $this->liveListing($this->seller('Seller'), $this->category('cosmetics'), ['stock' => 5]);

        $this->actingAs($buyer)->post(route('restock_alerts.store', $listing))->assertStatus(422);
        $this->assertSame(0, RestockAlert::count());
    }

    public function test_subscribing_twice_does_not_duplicate(): void
    {
        $buyer = $this->seller('Buyer');
        $listing = $this->liveListing($this->seller('Seller'), $this->category('cosmetics'), ['stock' => 0]);

        $this->actingAs($buyer)->post(route('restock_alerts.store', $listing));
        $this->actingAs($buyer)->post(route('restock_alerts.store', $listing));

        $this->assertSame(1, RestockAlert::count());
    }

    public function test_subscribers_are_notified_and_marked_sent_once_stock_is_restored(): void
    {
        Notification::fake();

        $buyer = $this->seller('Buyer');
        $listing = $this->liveListing($this->seller('Seller'), $this->category('cosmetics'), ['stock' => 0]);
        $this->actingAs($buyer)->post(route('restock_alerts.store', $listing));

        $listing->update(['stock' => 10]);

        Notification::assertSentTo($buyer, RestockAvailable::class);
        $this->assertNotNull(RestockAlert::first()->notified_at);
    }

    public function test_a_subscriber_is_not_notified_twice_for_a_further_restock(): void
    {
        Notification::fake();

        $buyer = $this->seller('Buyer');
        $listing = $this->liveListing($this->seller('Seller'), $this->category('cosmetics'), ['stock' => 0]);
        $this->actingAs($buyer)->post(route('restock_alerts.store', $listing));

        $listing->update(['stock' => 10]);
        $listing->update(['stock' => 0]);
        $listing->update(['stock' => 10]);

        Notification::assertSentToTimes($buyer, RestockAvailable::class, 1);
    }

    public function test_a_new_subscription_is_possible_after_a_previous_one_was_fulfilled(): void
    {
        $buyer = $this->seller('Buyer');
        $listing = $this->liveListing($this->seller('Seller'), $this->category('cosmetics'), ['stock' => 0]);
        $this->actingAs($buyer)->post(route('restock_alerts.store', $listing));

        $listing->update(['stock' => 10]);
        $listing->update(['stock' => 0]);

        $this->actingAs($buyer)->post(route('restock_alerts.store', $listing))->assertRedirect();

        $this->assertSame(2, RestockAlert::count());
        $this->assertSame(1, RestockAlert::pending()->count());
    }

    public function test_deleting_a_listing_removes_its_restock_alerts(): void
    {
        $buyer = $this->seller('Buyer');
        $listing = $this->listing($this->seller('Seller'), $this->category('cosmetics'), ['stock' => 0]);
        $this->actingAs($buyer)->post(route('restock_alerts.store', $listing));

        $listing->forceDelete();

        $this->assertSame(0, RestockAlert::count());
    }

    public function test_a_guest_cannot_subscribe(): void
    {
        $listing = $this->liveListing($this->seller('Seller'), $this->category('cosmetics'), ['stock' => 0]);

        $this->post(route('restock_alerts.store', $listing))->assertRedirect(route('login.show'));
    }
}
