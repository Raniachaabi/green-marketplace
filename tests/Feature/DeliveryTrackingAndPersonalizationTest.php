<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\ShipmentStatus;
use App\Models\Address;
use App\Models\AgreementVersion;
use App\Models\Carrier;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\RecentlyViewedListing;
use App\Models\User;
use App\Services\Cart;
use App\Services\OrderPlacer;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesMarketplace;
use Tests\TestCase;

class DeliveryTrackingAndPersonalizationTest extends TestCase
{
    use CreatesMarketplace, RefreshDatabase;

    private function placeOrder(User $buyer, User $seller): Order
    {
        $address = Address::create([
            'user_id' => $buyer->id, 'contact_name' => 'Buyer', 'contact_phone' => '1', 'governorate' => 'tunis',
        ]);
        Carrier::firstOrCreate(['code' => 'partner'], ['name' => 'Partner']);
        DeliveryZone::firstOrCreate(
            ['carrier_code' => 'partner', 'governorate' => 'tunis'],
            ['base_price' => Money::fromDinars(7), 'lead_time_days' => 1],
        );
        AgreementVersion::firstOrCreate(
            ['type' => 'buyer', 'version' => '1.0'],
            ['title' => ['fr' => 'CGV'], 'effective_from' => now()->subDay()],
        );
        $listing = $this->liveListing($seller, $this->category('poterie'));
        app(Cart::class)->add($listing, 1);

        return app(OrderPlacer::class)->place($buyer, $address, PaymentMethod::Cod);
    }

    public function test_the_tracker_marks_only_real_completed_steps(): void
    {
        $buyer = $this->seller('Buyer');
        $order = $this->placeOrder($buyer, $this->seller('Seller'));
        $shipment = $order->shipments()->first();

        $steps = $shipment->trackerSteps($order);
        $byKey = collect($steps)->keyBy('key');

        $this->assertTrue($byKey['placed']['done']);
        $this->assertTrue($byKey['preparing']['done']);
        $this->assertFalse($byKey['picked_up']['done']);
        $this->assertFalse($byKey['delivered']['done']);
        $this->assertNotNull($byKey['placed']['timestamp']);
        $this->assertNull($byKey['picked_up']['timestamp']);
    }

    public function test_the_tracker_reflects_a_real_pickup_timestamp(): void
    {
        $buyer = $this->seller('Buyer');
        $order = $this->placeOrder($buyer, $this->seller('Seller'));
        $shipment = $order->shipments()->first();
        $shipment->update(['status' => ShipmentStatus::PickedUp, 'picked_up_at' => now()]);

        $steps = collect($shipment->fresh()->trackerSteps($order))->keyBy('key');

        $this->assertTrue($steps['picked_up']['done']);
        $this->assertTrue($steps['picked_up']['active']);
        $this->assertNotNull($steps['picked_up']['timestamp']);
        $this->assertFalse($steps['in_transit']['done']);
    }

    public function test_a_failed_shipment_shows_no_fabricated_progress(): void
    {
        $buyer = $this->seller('Buyer');
        $order = $this->placeOrder($buyer, $this->seller('Seller'));
        $shipment = $order->shipments()->first();
        $shipment->update(['status' => ShipmentStatus::Failed]);

        $this->assertSame([], $shipment->fresh()->trackerSteps($order));
    }

    public function test_viewing_a_listing_records_it_for_a_logged_in_buyer(): void
    {
        $buyer = $this->seller('Buyer');
        $listing = $this->liveListing($this->seller('Seller'), $this->category('poterie'));

        $this->actingAs($buyer)->get(route('catalog.show', $listing->slug));

        $this->assertSame(1, RecentlyViewedListing::where('user_id', $buyer->id)->where('listing_id', $listing->id)->count());
    }

    public function test_a_guest_view_is_not_recorded(): void
    {
        $listing = $this->liveListing($this->seller('Seller'), $this->category('poterie'));

        $this->get(route('catalog.show', $listing->slug));

        $this->assertSame(0, RecentlyViewedListing::count());
    }

    public function test_the_home_page_shows_recently_viewed_only_for_the_current_buyer(): void
    {
        $buyerA = $this->seller('BuyerA');
        $buyerB = $this->seller('BuyerB');
        $listing = $this->liveListing($this->seller('Seller'), $this->category('poterie'));

        $this->actingAs($buyerA)->get(route('catalog.show', $listing->slug));

        $responseA = $this->actingAs($buyerA)->get(route('home'));
        $responseA->assertSee(__('home.recently_viewed'));
        $responseA->assertSee($listing->name());

        $responseB = $this->actingAs($buyerB)->get(route('home'));
        $responseB->assertDontSee(__('home.recently_viewed'));
    }

    public function test_the_home_page_recommends_from_the_same_category_as_the_last_viewed_listing(): void
    {
        $buyer = $this->seller('Buyer');
        $category = $this->category('poterie');
        $viewed = $this->liveListing($this->seller('Seller'), $category, ['title' => ['fr' => 'Vase vu']]);
        $related = $this->liveListing($this->seller('Seller2'), $category, ['title' => ['fr' => 'Vase similaire']]);

        $this->actingAs($buyer)->get(route('catalog.show', $viewed->slug));

        $response = $this->actingAs($buyer)->get(route('home'));

        $response->assertSee(__('home.because_you_viewed', ['title' => $viewed->name()]));
        $response->assertSee($related->name());
    }

    public function test_the_home_page_shows_new_listings_from_followed_sellers(): void
    {
        $buyer = $this->seller('Buyer');
        $followedSeller = $this->seller('Followed');
        $buyer->following()->create(['seller_user_id' => $followedSeller->id]);
        $listing = $this->liveListing($followedSeller, $this->category('poterie'));

        $response = $this->actingAs($buyer)->get(route('home'));

        $response->assertSee(__('home.from_followed_sellers'));
        $response->assertSee($listing->name());
    }
}
