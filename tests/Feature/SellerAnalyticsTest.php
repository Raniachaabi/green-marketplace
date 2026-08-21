<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Address;
use App\Models\AgreementVersion;
use App\Models\Carrier;
use App\Models\Category;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\User;
use App\Services\Cart;
use App\Services\OrderPlacer;
use App\Services\SellerAnalyticsService;
use App\Support\DateRange;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesMarketplace;
use Tests\TestCase;

class SellerAnalyticsTest extends TestCase
{
    use CreatesMarketplace, RefreshDatabase;

    private function order(User $buyer, User $seller, float $priceDinars = 50, int $qty = 1): Order
    {
        $address = Address::firstOrCreate(
            ['user_id' => $buyer->id],
            ['contact_name' => 'Buyer', 'contact_phone' => '1', 'governorate' => 'tunis'],
        );
        Carrier::firstOrCreate(['code' => 'partner'], ['name' => 'Partner']);
        DeliveryZone::firstOrCreate(
            ['carrier_code' => 'partner', 'governorate' => 'tunis'],
            ['base_price' => Money::fromDinars(7), 'lead_time_days' => 1],
        );
        AgreementVersion::firstOrCreate(
            ['type' => 'buyer', 'version' => '1.0'],
            ['title' => ['fr' => 'CGV'], 'effective_from' => now()->subDay()],
        );
        $category = Category::firstOrCreate(
            ['slug' => 'poterie'],
            ['name' => ['fr' => 'poterie'], 'path' => 'poterie', 'is_leaf' => true, 'listing_type' => 'product'],
        );
        $listing = $this->liveListing($seller, $category, ['price' => Money::fromDinars($priceDinars), 'stock' => 20]);
        app(Cart::class)->add($listing, $qty);

        return app(OrderPlacer::class)->place($buyer, $address, PaymentMethod::Cod);
    }

    public function test_revenue_and_orders_reflect_only_this_sellers_lines(): void
    {
        $sellerA = $this->seller('SellerA');
        $sellerB = $this->seller('SellerB');
        $buyer = $this->seller('Buyer');

        $this->order($buyer, $sellerA, 100);
        $this->order($buyer, $sellerB, 500);

        $reportA = app(SellerAnalyticsService::class)->report($sellerA, DateRange::fromKey('30d'));
        $reportB = app(SellerAnalyticsService::class)->report($sellerB, DateRange::fromKey('30d'));

        $this->assertSame(Money::fromDinars(100), $reportA['revenue']);
        $this->assertSame(1, $reportA['ordersCount']);
        $this->assertSame(Money::fromDinars(500), $reportB['revenue']);
    }

    public function test_cancelled_orders_do_not_count_as_revenue(): void
    {
        $seller = $this->seller('Seller');
        $buyer = $this->seller('Buyer');
        $order = $this->order($buyer, $seller, 100);
        $order->update(['status' => OrderStatus::Cancelled]);

        $report = app(SellerAnalyticsService::class)->report($seller, DateRange::fromKey('30d'));

        $this->assertSame(0, $report['revenue']);
        $this->assertSame(0, $report['ordersCount']);
    }

    public function test_orders_outside_the_selected_range_are_excluded(): void
    {
        $seller = $this->seller('Seller');
        $buyer = $this->seller('Buyer');
        $order = $this->order($buyer, $seller, 100);
        $order->update(['placed_at' => now()->subDays(60)]);

        $report = app(SellerAnalyticsService::class)->report($seller, DateRange::fromKey('7d'));

        $this->assertSame(0, $report['revenue']);
    }

    public function test_average_order_value_is_revenue_over_order_count(): void
    {
        $seller = $this->seller('Seller');
        $buyer1 = $this->seller('Buyer1');
        $buyer2 = $this->seller('Buyer2');

        $this->order($buyer1, $seller, 100);
        $this->order($buyer2, $seller, 300);

        $report = app(SellerAnalyticsService::class)->report($seller, DateRange::fromKey('30d'));

        $this->assertSame(Money::fromDinars(200), $report['aov']);
    }

    public function test_inventory_status_reflects_real_stock_levels(): void
    {
        $seller = $this->seller('Seller');
        $category = $this->category('poterie');
        $this->liveListing($seller, $category, ['stock' => 20]);
        $this->liveListing($seller, $category, ['stock' => 2]);
        $this->liveListing($seller, $category, ['stock' => 0]);

        $report = app(SellerAnalyticsService::class)->report($seller, DateRange::fromKey('30d'));

        $this->assertSame(1, $report['inventory']['in_stock']);
        $this->assertSame(1, $report['inventory']['low_stock']);
        $this->assertSame(1, $report['inventory']['out_of_stock']);
    }

    public function test_a_seller_only_sees_their_own_analytics_dashboard(): void
    {
        $sellerA = $this->seller('SellerA');
        $sellerB = $this->seller('SellerB');
        $buyer = $this->seller('Buyer');
        $this->order($buyer, $sellerA, 900);

        $response = $this->actingAs($sellerB)->get(route('seller.analytics.index'));

        $response->assertOk();
        $response->assertDontSee(Money::format(Money::fromDinars(900)));
    }

    public function test_a_guest_cannot_reach_the_analytics_dashboard(): void
    {
        $this->get(route('seller.analytics.index'))->assertRedirect(route('login.show'));
    }
}
