<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Address;
use App\Models\AgreementVersion;
use App\Models\Carrier;
use App\Models\DeliveryZone;
use App\Models\Review;
use App\Services\Cart;
use App\Services\OrderPlacer;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesMarketplace;
use Tests\TestCase;

/**
 * The buyer-facing pieces that had no way in at all before: an address
 * book (checkout was a dead end without one), reviews tied to a delivered
 * order line, and one-click reorder.
 */
class BuyerAccountTest extends TestCase
{
    use CreatesMarketplace, RefreshDatabase;

    private function buyerWithDeliveredOrder(): array
    {
        $buyer = $this->seller('Buyer');

        $address = Address::create([
            'user_id' => $buyer->id,
            'contact_name' => 'Buyer',
            'contact_phone' => '+21698000000',
            'governorate' => 'tunis',
            'is_default' => true,
        ]);

        $carrier = Carrier::create(['code' => 'partner', 'name' => 'Partner']);
        DeliveryZone::create([
            'carrier_code' => $carrier->code,
            'governorate' => 'tunis',
            'base_price' => Money::fromDinars(7),
            'lead_time_days' => 1,
        ]);

        AgreementVersion::create([
            'type' => 'buyer',
            'version' => '1.0',
            'title' => ['fr' => 'CGV'],
            'effective_from' => now()->subDay(),
        ]);

        $listing = $this->liveListing($this->seller('Seller'), $this->category('poterie'), [
            'price' => Money::fromDinars(40),
            'stock' => 5,
        ]);

        app(Cart::class)->add($listing, 1);
        $order = app(OrderPlacer::class)->place($buyer, $address, PaymentMethod::Cod);
        $order->update(['status' => OrderStatus::Delivered]);

        return [$buyer, $order, $listing];
    }

    public function test_a_buyer_can_add_an_address_and_it_becomes_default_when_first(): void
    {
        $buyer = $this->seller('Buyer');

        $this->actingAs($buyer)->post(route('addresses.store'), [
            'contact_name' => 'Buyer',
            'contact_phone' => '+21698000000',
            'governorate' => 'tunis',
        ])->assertRedirect();

        $address = $buyer->addresses()->first();

        $this->assertNotNull($address);
        $this->assertTrue($address->is_default);
    }

    public function test_setting_a_new_default_address_unsets_the_old_one(): void
    {
        $buyer = $this->seller('Buyer');
        $first = Address::create([
            'user_id' => $buyer->id, 'contact_name' => 'A', 'contact_phone' => '1',
            'governorate' => 'tunis', 'is_default' => true,
        ]);
        $second = Address::create([
            'user_id' => $buyer->id, 'contact_name' => 'B', 'contact_phone' => '2',
            'governorate' => 'sfax', 'is_default' => false,
        ]);

        $this->actingAs($buyer)->post(route('addresses.default', $second))->assertRedirect();

        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->fresh()->is_default);
    }

    public function test_a_buyer_cannot_edit_someone_elses_address(): void
    {
        $owner = $this->seller('Owner');
        $intruder = $this->seller('Intruder');
        $address = Address::create([
            'user_id' => $owner->id, 'contact_name' => 'Owner', 'contact_phone' => '1', 'governorate' => 'tunis',
        ]);

        $this->actingAs($intruder)->put(route('addresses.update', $address), [
            'contact_name' => 'Hijacked', 'contact_phone' => '2', 'governorate' => 'sfax',
        ])->assertForbidden();
    }

    public function test_a_buyer_can_review_a_delivered_line_once(): void
    {
        [$buyer, $order] = $this->buyerWithDeliveredOrder();
        $line = $order->lines->first();

        $this->actingAs($buyer)->post(route('reviews.store', $line), [
            'rating' => 5,
            'body' => 'Great quality.',
        ])->assertRedirect();

        $this->assertSame(1, Review::where('order_line_id', $line->id)->count());

        // A second attempt is rejected, not silently duplicated.
        $this->actingAs($buyer)->post(route('reviews.store', $line), [
            'rating' => 3,
        ])->assertSessionHasErrors('review');

        $this->assertSame(1, Review::where('order_line_id', $line->id)->count());
    }

    public function test_a_buyer_cannot_review_before_delivery(): void
    {
        $buyer = $this->seller('Buyer');
        $listing = $this->liveListing($this->seller('Seller'), $this->category('poterie'));

        $address = Address::create([
            'user_id' => $buyer->id, 'contact_name' => 'Buyer', 'contact_phone' => '1', 'governorate' => 'tunis',
        ]);

        AgreementVersion::create([
            'type' => 'buyer', 'version' => '1.0', 'title' => ['fr' => 'CGV'], 'effective_from' => now()->subDay(),
        ]);

        app(Cart::class)->add($listing, 1);
        $order = app(OrderPlacer::class)->place($buyer, $address, PaymentMethod::Cod);

        $this->actingAs($buyer)
            ->post(route('reviews.store', $order->lines->first()), ['rating' => 5])
            ->assertStatus(422);
    }

    public function test_reorder_adds_still_available_items_to_the_cart(): void
    {
        [$buyer, $order] = $this->buyerWithDeliveredOrder();

        $this->actingAs($buyer)
            ->post(route('orders.reorder', $order))
            ->assertRedirect(route('cart.show'));

        $this->assertFalse(app(Cart::class)->isEmpty());
    }

    public function test_reorder_reports_when_nothing_is_available_any_more(): void
    {
        [$buyer, $order, $listing] = $this->buyerWithDeliveredOrder();
        $listing->update(['status' => ListingStatus::Suspended]);

        $this->actingAs($buyer)
            ->post(route('orders.reorder', $order))
            ->assertSessionHasErrors('reorder');

        $this->assertTrue(app(Cart::class)->isEmpty());
    }
}
