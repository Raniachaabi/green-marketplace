<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\ShipmentStatus;
use App\Models\Address;
use App\Models\AgreementVersion;
use App\Models\Carrier;
use App\Models\DeliveryZone;
use App\Services\Cart;
use App\Services\OrderPlacer;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesMarketplace;
use Tests\TestCase;

class SellerOrdersTest extends TestCase
{
    use CreatesMarketplace, RefreshDatabase;

    private function placeMultiSellerOrder(): array
    {
        $buyer = $this->seller('Buyer');

        $address = Address::create([
            'user_id' => $buyer->id, 'contact_name' => 'Buyer', 'contact_phone' => '1', 'governorate' => 'tunis',
        ]);

        $carrier = Carrier::create(['code' => 'partner', 'name' => 'Partner']);
        DeliveryZone::create([
            'carrier_code' => $carrier->code, 'governorate' => 'tunis',
            'base_price' => Money::fromDinars(7), 'lead_time_days' => 1,
        ]);

        AgreementVersion::create([
            'type' => 'buyer', 'version' => '1.0', 'title' => ['fr' => 'CGV'], 'effective_from' => now()->subDay(),
        ]);

        $sellerA = $this->seller('Seller A');
        $sellerB = $this->seller('Seller B');
        $category = $this->category('poterie');

        $listingA = $this->liveListing($sellerA, $category, ['price' => Money::fromDinars(40)]);
        $listingB = $this->liveListing($sellerB, $category, ['price' => Money::fromDinars(60)]);

        app(Cart::class)->add($listingA, 1);
        app(Cart::class)->add($listingB, 1);

        $order = app(OrderPlacer::class)->place($buyer, $address, PaymentMethod::Cod);

        return [$order, $sellerA, $sellerB];
    }

    public function test_a_seller_only_sees_orders_containing_their_own_listings(): void
    {
        [$order, $sellerA, $sellerB] = $this->placeMultiSellerOrder();

        $this->actingAs($sellerA)->get(route('seller.orders.index'))->assertSee($order->number);

        $unrelatedSeller = $this->seller('Unrelated');
        $this->actingAs($unrelatedSeller)->get(route('seller.orders.index'))->assertDontSee($order->number);
    }

    public function test_a_seller_sees_only_their_own_lines_within_a_shared_order(): void
    {
        [$order, $sellerA, $sellerB] = $this->placeMultiSellerOrder();

        $response = $this->actingAs($sellerA)->get(route('seller.orders.show', $order));

        $response->assertOk();
        $response->assertSee(Money::format(Money::fromDinars(40)));
        $response->assertDontSee(Money::format(Money::fromDinars(60)));
    }

    public function test_a_seller_cannot_view_an_order_they_have_no_line_in(): void
    {
        [$order] = $this->placeMultiSellerOrder();
        $unrelatedSeller = $this->seller('Unrelated');

        $this->actingAs($unrelatedSeller)->get(route('seller.orders.show', $order))->assertForbidden();
    }

    public function test_a_seller_can_update_only_their_own_shipment(): void
    {
        [$order, $sellerA, $sellerB] = $this->placeMultiSellerOrder();

        $shipmentA = $order->shipments()->where('seller_user_id', $sellerA->id)->first();
        $shipmentB = $order->shipments()->where('seller_user_id', $sellerB->id)->first();

        $this->actingAs($sellerA)
            ->patch(route('seller.orders.shipment.update', [$order, $shipmentA]), ['status' => 'picked_up'])
            ->assertRedirect();

        $this->assertSame(ShipmentStatus::PickedUp, $shipmentA->fresh()->status);

        $this->actingAs($sellerA)
            ->patch(route('seller.orders.shipment.update', [$order, $shipmentB]), ['status' => 'picked_up'])
            ->assertForbidden();

        $this->assertSame(ShipmentStatus::Pending, $shipmentB->fresh()->status);
    }
}
