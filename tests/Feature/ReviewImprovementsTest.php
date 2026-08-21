<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Address;
use App\Models\AgreementVersion;
use App\Models\Carrier;
use App\Models\DeliveryZone;
use App\Models\Listing;
use App\Models\OrderLine;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\User;
use App\Notifications\ReviewReported;
use App\Services\Cart;
use App\Services\OrderPlacer;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\CreatesMarketplace;
use Tests\TestCase;

class ReviewImprovementsTest extends TestCase
{
    use CreatesMarketplace, RefreshDatabase;

    private function admin(): User
    {
        $admin = User::create(['phone' => '+21670000000', 'full_name' => 'Admin', 'slug' => 'admin']);
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    /** Places a real delivered order so a review is possible, and returns the reviewed listing's order line. */
    private function deliveredOrderLine(User $buyer, User $seller, ?Listing $listing = null): OrderLine
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
        $listing ??= $this->liveListing($seller, $this->category('poterie'));
        app(Cart::class)->add($listing, 1);
        $order = app(OrderPlacer::class)->place($buyer, $address, PaymentMethod::Cod);
        $order->update(['status' => OrderStatus::Delivered]);

        return $order->lines()->first();
    }

    public function test_a_seller_can_respond_to_a_review_on_their_own_listing(): void
    {
        $seller = $this->seller('Seller');
        $buyer = $this->seller('Buyer');
        $line = $this->deliveredOrderLine($buyer, $seller);

        $this->actingAs($buyer)->post(route('reviews.store', $line), ['rating' => 4, 'body' => 'Good.']);
        $review = Review::first();

        $this->actingAs($seller)->post(route('reviews.respond', $review), [
            'seller_response' => 'Thank you!',
        ])->assertRedirect();

        $this->assertSame('Thank you!', $review->fresh()->seller_response);
    }

    public function test_a_seller_cannot_respond_to_a_review_on_someone_elses_listing(): void
    {
        $seller = $this->seller('Seller');
        $intruder = $this->seller('Intruder');
        $buyer = $this->seller('Buyer');
        $line = $this->deliveredOrderLine($buyer, $seller);

        $this->actingAs($buyer)->post(route('reviews.store', $line), ['rating' => 4]);
        $review = Review::first();

        $this->actingAs($intruder)->post(route('reviews.respond', $review), [
            'seller_response' => 'Not yours to answer.',
        ])->assertForbidden();

        $this->assertNull($review->fresh()->seller_response);
    }

    public function test_a_buyer_can_report_a_review_and_admins_are_notified(): void
    {
        Notification::fake();

        $admin = $this->admin();
        $seller = $this->seller('Seller');
        $buyer = $this->seller('Buyer');
        $reporter = $this->seller('Reporter');
        $line = $this->deliveredOrderLine($buyer, $seller);
        $this->actingAs($buyer)->post(route('reviews.store', $line), ['rating' => 1, 'body' => 'Bad.']);
        $review = Review::first();

        $this->actingAs($reporter)->post(route('reviews.report', $review), ['reason' => 'fake'])->assertRedirect();

        $this->assertSame(1, ReviewReport::count());
        Notification::assertSentTo($admin, ReviewReported::class);
    }

    public function test_reporting_the_same_review_twice_does_not_duplicate(): void
    {
        $seller = $this->seller('Seller');
        $buyer = $this->seller('Buyer');
        $reporter = $this->seller('Reporter');
        $line = $this->deliveredOrderLine($buyer, $seller);
        $this->actingAs($buyer)->post(route('reviews.store', $line), ['rating' => 1]);
        $review = Review::first();

        $this->actingAs($reporter)->post(route('reviews.report', $review), ['reason' => 'spam']);
        $this->actingAs($reporter)->post(route('reviews.report', $review), ['reason' => 'spam']);

        $this->assertSame(1, ReviewReport::count());
    }

    public function test_rating_distribution_reflects_real_reviews(): void
    {
        $seller = $this->seller('Seller');
        $listing = $this->liveListing($seller, $this->category('poterie'));

        foreach ([5, 5, 3] as $i => $rating) {
            $buyer = $this->seller("Buyer{$i}");
            $line = $this->deliveredOrderLine($buyer, $seller, $listing);
            $this->actingAs($buyer)->post(route('reviews.store', $line), ['rating' => $rating]);
        }

        $distribution = $listing->fresh()->ratingDistribution();

        $this->assertSame(2, $distribution[5]);
        $this->assertSame(1, $distribution[3]);
        $this->assertSame(0, $distribution[4]);
    }
}
