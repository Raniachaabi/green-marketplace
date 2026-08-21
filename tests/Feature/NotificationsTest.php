<?php

namespace Tests\Feature;

use App\Enums\CredentialStatus;
use App\Enums\ListingStatus;
use App\Enums\PaymentMethod;
use App\Models\Address;
use App\Models\AgreementVersion;
use App\Models\Carrier;
use App\Models\DeliveryZone;
use App\Services\Cart;
use App\Services\CredentialManager;
use App\Services\OrderPlacer;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesMarketplace;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use CreatesMarketplace, RefreshDatabase;

    public function test_a_seller_is_notified_when_their_listing_is_approved(): void
    {
        $seller = $this->seller();
        $listing = $this->listing($seller, $this->category('poterie'));

        $listing->forceFill(['status' => ListingStatus::Active])->save();

        $this->assertSame(1, $seller->notifications()->count());
        $this->assertSame('notifications.listing_approved', $seller->notifications()->first()->data['message_key']);
    }

    public function test_a_seller_is_notified_when_a_credential_is_rejected(): void
    {
        $type = $this->credentialType('cin_identity');
        $seller = $this->seller();
        $credential = $this->giveCredential($seller, 'cin_identity', null, CredentialStatus::Pending);

        app(CredentialManager::class)->reject($credential, $this->seller('Admin'), 'Illegible scan');

        $notification = $seller->notifications()->first();
        $this->assertSame('notifications.credential_rejected', $notification->data['message_key']);
        $this->assertSame('Illegible scan', $notification->data['replace']['reason']);
    }

    public function test_a_seller_is_notified_of_a_new_order(): void
    {
        $seller = $this->seller('Seller');
        $buyer = $this->seller('Buyer');
        $category = $this->category('poterie');

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

        $listing = $this->liveListing($seller, $category);
        app(Cart::class)->add($listing, 1);
        app(OrderPlacer::class)->place($buyer, $address, PaymentMethod::Cod);

        $this->assertSame(1, $seller->notifications()->count());
        $this->assertSame('notifications.new_order', $seller->notifications()->first()->data['message_key']);
    }

    public function test_a_buyer_can_mark_a_notification_read_and_is_sent_to_its_target(): void
    {
        $seller = $this->seller();
        $listing = $this->listing($seller, $this->category('poterie'));
        $listing->forceFill(['status' => ListingStatus::Active])->save();

        $notification = $seller->notifications()->first();

        $this->actingAs($seller)
            ->post(route('notifications.read', $notification->id))
            ->assertRedirect(route('seller.listings.edit', $listing));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_a_user_cannot_mark_someone_elses_notification_read(): void
    {
        $seller = $this->seller();
        $listing = $this->listing($seller, $this->category('poterie'));
        $listing->forceFill(['status' => ListingStatus::Active])->save();

        $notification = $seller->notifications()->first();
        $intruder = $this->seller('Intruder');

        $this->actingAs($intruder)
            ->post(route('notifications.read', $notification->id))
            ->assertNotFound();
    }
}
