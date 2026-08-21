<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Enums\PaymentMethod;
use App\Filament\Widgets\MarketplaceHealthStats;
use App\Filament\Widgets\SellerActivityStats;
use App\Models\Address;
use App\Models\AgreementVersion;
use App\Models\Carrier;
use App\Models\DeliveryZone;
use App\Models\Dispute;
use App\Models\Incident;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\User;
use App\Models\UserRole;
use App\Services\Cart;
use App\Services\OrderPlacer;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\CreatesMarketplace;
use Tests\TestCase;

class AdminDashboardWidgetsTest extends TestCase
{
    use CreatesMarketplace, RefreshDatabase;

    private function admin(): User
    {
        $admin = User::create(['phone' => '+21670000000', 'full_name' => 'Admin', 'slug' => 'admin']);
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    public function test_the_dashboard_renders_both_new_widgets_for_an_admin(): void
    {
        $this->actingAs($this->admin())->get('/admin')
            ->assertOk()
            ->assertSeeLivewire(MarketplaceHealthStats::class)
            ->assertSeeLivewire(SellerActivityStats::class);
    }

    private function healthStats(): array
    {
        $widget = new MarketplaceHealthStats;
        $method = new \ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        return collect($method->invoke($widget))
            ->mapWithKeys(fn ($stat) => [$stat->getLabel() => $stat->getValue()])
            ->all();
    }

    public function test_marketplace_health_counts_pending_listings_and_low_stock_from_real_rows(): void
    {
        $seller = $this->seller('Seller');
        $category = $this->category('poterie');

        $this->liveListing($seller, $category, ['stock' => 2]);
        $pending = $this->liveListing($seller, $category);
        $pending->forceFill(['status' => ListingStatus::Pending])->save();

        $stats = $this->healthStats();

        $this->assertSame(1, $stats['Listings awaiting review']);
        $this->assertSame(1, $stats['Low stock listings']);
    }

    public function test_marketplace_health_counts_open_incidents_disputes_and_reported_reviews(): void
    {
        $buyer = $this->seller('Buyer');
        $seller = $this->seller('Seller');
        $listing = $this->liveListing($seller, $this->category('poterie'));

        Incident::create([
            'reporter_user_id' => $buyer->id, 'listing_id' => $listing->id,
            'type' => 'quality', 'status' => 'open', 'body' => 'Broken on arrival',
        ]);

        $order = $this->placeRealOrder($buyer, $seller, $listing);
        Dispute::create([
            'order_id' => $order->id, 'opened_by_user_id' => $buyer->id,
            'reason' => 'not_as_described', 'status' => 'open',
        ]);

        $review = Review::create([
            'author_user_id' => $buyer->id, 'target_type' => 'listing', 'target_id' => $listing->id, 'rating' => 1,
        ]);
        ReviewReport::create(['review_id' => $review->id, 'reporter_user_id' => $seller->id, 'reason' => 'abusive']);

        $stats = $this->healthStats();

        $this->assertSame(1, $stats['Open incidents']);
        $this->assertSame(1, $stats['Open disputes']);
        $this->assertSame(1, $stats['Reported reviews']);
    }

    public function test_seller_activity_shows_new_sellers_most_followed_and_top_revenue_seller(): void
    {
        $seller = $this->seller('Rising Seller');
        UserRole::create(['user_id' => $seller->id, 'role' => 'seller', 'granted_at' => now()]);

        $buyer = $this->seller('Buyer');
        $buyer->following()->create(['seller_user_id' => $seller->id]);

        $listing = $this->liveListing($seller, $this->category('poterie'));
        $this->placeRealOrder($buyer, $seller, $listing);

        Livewire::actingAs($this->admin())->test(SellerActivityStats::class)
            ->assertSee('New sellers (30d)')
            ->assertSee('Rising Seller')
            ->assertSee('1 followers');
    }

    private function placeRealOrder(User $buyer, User $seller, $listing)
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

        app(Cart::class)->add($listing, 1);

        return app(OrderPlacer::class)->place($buyer, $address, PaymentMethod::Cod);
    }
}
