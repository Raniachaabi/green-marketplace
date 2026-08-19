<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\Address;
use App\Models\AgreementVersion;
use App\Models\Carrier;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Services\Cart;
use App\Services\OrderPlacer;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\CreatesMarketplace;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use CreatesMarketplace, RefreshDatabase;

    private function buyerWithAddress(): array
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

        return [$buyer, $address];
    }

    public function test_it_places_an_order_with_snapshots_and_an_invoice(): void
    {
        [$buyer, $address] = $this->buyerWithAddress();

        $listing = $this->liveListing($this->seller(), $this->category('poterie'), [
            'price' => Money::fromDinars(40),
            'stock' => 5,
        ]);

        app(Cart::class)->add($listing, 2);

        $order = app(OrderPlacer::class)->place($buyer, $address, PaymentMethod::Cod);

        $this->assertSame(Money::fromDinars(80), (int) $order->subtotal);
        $this->assertSame(Money::fromDinars(7), (int) $order->delivery_total);
        $this->assertCount(1, $order->lines);
        $this->assertCount(1, $order->shipments);
        $this->assertCount(1, $order->documents);
        $this->assertStringStartsWith('FAC-', $order->documents->first()->number);

        // Stock decremented, cart emptied.
        $this->assertSame(3, $listing->fresh()->stock);
        $this->assertTrue(app(Cart::class)->isEmpty());
    }

    /**
     * A draft listing must never be sellable. This is the rule my own test
     * fixtures originally tripped over — worth pinning down explicitly rather
     * than relying on it implicitly.
     */
    public function test_a_draft_listing_cannot_be_bought(): void
    {
        [$buyer, $address] = $this->buyerWithAddress();

        // Note: listing(), not liveListing() — this one stays a draft.
        $draft = $this->listing($this->seller(), $this->category('poterie'), ['stock' => 5]);

        app(Cart::class)->add($draft, 1);

        $this->assertSame(0, app(Cart::class)->subtotal());
        $this->assertTrue(app(Cart::class)->lines()->contains('available', false));

        $this->expectException(RuntimeException::class);
        app(OrderPlacer::class)->place($buyer, $address);
    }

    /**
     * A seller editing their listing tomorrow must not change what a buyer
     * bought today. This is why title and price are snapshot onto the line.
     */
    public function test_order_lines_snapshot_price_and_title(): void
    {
        [$buyer, $address] = $this->buyerWithAddress();

        $listing = $this->liveListing($this->seller(), $this->category('poterie'), [
            'price' => Money::fromDinars(40),
            'stock' => 5,
        ]);

        app(Cart::class)->add($listing, 1);
        $order = app(OrderPlacer::class)->place($buyer, $address);

        $listing->update(['price' => Money::fromDinars(999), 'title' => ['fr' => 'Renommé']]);

        $line = $order->fresh()->lines->first();

        $this->assertSame(Money::fromDinars(40), (int) $line->unit_price);
        $this->assertSame('Article de test', $line->title('fr'));
    }

    /**
     * The real oversell guard: the cart still shows the item as available
     * (stock 1 > 0), but the buyer wants 2. The conditional UPDATE in
     * OrderPlacer is what has to catch this — its WHERE clause is the lock.
     */
    public function test_it_refuses_to_oversell(): void
    {
        [$buyer, $address] = $this->buyerWithAddress();

        $listing = $this->liveListing($this->seller(), $this->category('poterie'), ['stock' => 1]);

        app(Cart::class)->add($listing, 2);

        // Sanity: the line IS considered available, so we are genuinely
        // testing the stock guard and not the empty-cart shortcut.
        $this->assertTrue(app(Cart::class)->lines()->firstWhere('available', true) !== null);

        try {
            app(OrderPlacer::class)->place($buyer, $address);
            $this->fail('Expected the oversell guard to reject this order.');
        } catch (RuntimeException $e) {
            // Expected.
        }

        // And nothing was half-written: no order, stock untouched.
        $this->assertSame(0, Order::count());
        $this->assertSame(1, $listing->fresh()->stock);
    }

    /** FR-092 — one order across two sellers produces two shipments. */
    public function test_a_multi_seller_order_splits_into_one_shipment_per_seller(): void
    {
        [$buyer, $address] = $this->buyerWithAddress();
        $category = $this->category('poterie');

        $a = $this->liveListing($this->seller('A'), $category, ['stock' => 5]);
        $b = $this->liveListing($this->seller('B'), $category, ['stock' => 5]);

        $cart = app(Cart::class);
        $cart->add($a, 1);
        $cart->add($b, 1);

        $order = app(OrderPlacer::class)->place($buyer, $address);

        $this->assertCount(2, $order->shipments);
        $this->assertCount(2, $order->lines);
    }

    /** FR-088 — invoice numbers must be sequential with no gaps. */
    public function test_invoice_numbers_are_sequential(): void
    {
        [$buyer, $address] = $this->buyerWithAddress();
        $category = $this->category('poterie');

        $numbers = [];

        for ($i = 0; $i < 3; $i++) {
            $listing = $this->liveListing($this->seller("S{$i}"), $category, ['stock' => 5]);
            app(Cart::class)->add($listing, 1);
            $numbers[] = app(OrderPlacer::class)->place($buyer, $address)->documents->first()->sequence;
        }

        $this->assertSame([1, 2, 3], $numbers);
    }

    public function test_the_cart_never_trusts_a_stale_price(): void
    {
        $listing = $this->liveListing($this->seller(), $this->category('poterie'), [
            'price' => Money::fromDinars(10),
            'stock' => 5,
        ]);

        $cart = app(Cart::class);
        $cart->add($listing, 1);

        $this->assertSame(Money::fromDinars(10), $cart->subtotal());

        $listing->update(['price' => Money::fromDinars(20)]);

        $line = $cart->lines()->first();

        // The price came from the database on re-read, never from the session.
        // A cart left open for a week cannot buy at last week's price.
        $this->assertSame(Money::fromDinars(20), $line['unit_price']);

        // And because editing a live listing sends it back for review
        // (ListingObserver), it is no longer sellable until re-approved.
        $this->assertFalse($line['available']);
        $this->assertSame(0, $cart->subtotal());
    }
}
