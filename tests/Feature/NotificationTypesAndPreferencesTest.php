<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\ShipmentStatus;
use App\Filament\Pages\SendAnnouncement;
use App\Models\Address;
use App\Models\AgreementVersion;
use App\Models\Carrier;
use App\Models\CredentialType;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\User;
use App\Notifications\CredentialExpired;
use App\Notifications\CredentialExpiringSoon;
use App\Notifications\CredentialSubmittedForReview;
use App\Notifications\LowInventory;
use App\Notifications\OrderConfirmed;
use App\Notifications\ReviewReminder;
use App\Notifications\SellerOrderStatusUpdated;
use App\Notifications\ShipmentUpdated;
use App\Services\Cart;
use App\Services\CredentialManager;
use App\Services\OrderPlacer;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\CreatesMarketplace;
use Tests\TestCase;

class NotificationTypesAndPreferencesTest extends TestCase
{
    use CreatesMarketplace, RefreshDatabase;

    private function admin(): User
    {
        $admin = User::create(['phone' => '+21670000000', 'full_name' => 'Admin', 'slug' => 'admin']);
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    private function placeOrder(User $buyer): Order
    {
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
        $listing = $this->liveListing($this->seller('Seller'), $this->category('poterie'));
        app(Cart::class)->add($listing, 1);

        return app(OrderPlacer::class)->place($buyer, $address, PaymentMethod::Cod);
    }

    public function test_a_buyer_is_notified_when_their_order_is_confirmed(): void
    {
        Notification::fake();

        $buyer = $this->seller('Buyer');
        $this->placeOrder($buyer);

        Notification::assertSentTo($buyer, OrderConfirmed::class);
    }

    public function test_a_buyer_is_notified_when_a_shipment_status_changes(): void
    {
        $buyer = $this->seller('Buyer');
        $order = $this->placeOrder($buyer);
        $shipment = $order->shipments()->first();

        Notification::fake();
        $shipment->update(['status' => ShipmentStatus::InTransit]);

        Notification::assertSentTo($buyer, ShipmentUpdated::class);
    }

    public function test_a_seller_is_notified_when_the_order_status_changes(): void
    {
        $buyer = $this->seller('Buyer');
        $order = $this->placeOrder($buyer);
        $sellerId = $order->lines()->value('seller_user_id');
        $seller = User::find($sellerId);

        Notification::fake();
        $order->update(['status' => OrderStatus::Confirmed]);

        Notification::assertSentTo($seller, SellerOrderStatusUpdated::class);
    }

    public function test_a_seller_is_notified_when_stock_crosses_below_the_low_stock_threshold(): void
    {
        $seller = $this->seller('Seller');
        $listing = $this->liveListing($seller, $this->category('cosmetics'), ['stock' => 10]);

        Notification::fake();
        $listing->update(['stock' => 3]);

        Notification::assertSentTo($seller, LowInventory::class);
    }

    public function test_low_inventory_does_not_fire_again_while_already_low(): void
    {
        $seller = $this->seller('Seller');
        $listing = $this->liveListing($seller, $this->category('cosmetics'), ['stock' => 10]);
        $listing->update(['stock' => 3]);

        Notification::fake();
        $listing->update(['stock' => 2]);

        Notification::assertNotSentTo($seller, LowInventory::class);
    }

    public function test_admins_are_notified_when_a_seller_submits_a_credential(): void
    {
        $admin = $this->admin();
        $seller = $this->seller('Seller');
        CredentialType::create(['code' => 'onat_artisan', 'label' => ['en' => 'ONAT'], 'requires_expiry' => false, 'requires_document' => false]);

        Notification::fake();

        $this->actingAs($seller)->post(route('seller.credentials.store'), [
            'credential_type_code' => 'onat_artisan',
            'number' => '12345',
        ]);

        Notification::assertSentTo($admin, CredentialSubmittedForReview::class);
    }

    public function test_a_seller_is_notified_when_their_credential_expires(): void
    {
        $seller = $this->seller('Seller');
        CredentialType::create(['code' => 'organic_certificate', 'label' => ['en' => 'Organic'], 'requires_expiry' => true]);
        $credential = $this->giveCredential($seller, 'organic_certificate', now()->subDay()->toDateString());

        Notification::fake();
        app(CredentialManager::class)->markExpired($credential);

        Notification::assertSentTo($seller, CredentialExpired::class);
    }

    public function test_a_seller_is_notified_of_an_upcoming_credential_expiry(): void
    {
        $seller = $this->seller('Seller');
        CredentialType::create(['code' => 'organic_certificate', 'label' => ['en' => 'Organic'], 'requires_expiry' => true]);
        $credential = $this->giveCredential($seller, 'organic_certificate', now()->addDays(5)->toDateString());

        Notification::fake();
        app(CredentialManager::class)->recordReminderSent($credential, 7);

        Notification::assertSentTo($seller, CredentialExpiringSoon::class);
    }

    public function test_a_buyer_is_reminded_to_review_a_delivered_unreviewed_order(): void
    {
        $buyer = $this->seller('Buyer');
        $order = $this->placeOrder($buyer);
        $order->forceFill(['status' => OrderStatus::Delivered, 'updated_at' => now()->subDays(10)])->saveQuietly();
        OrderLine::where('order_id', $order->id)->update(['updated_at' => now()->subDays(10)]);

        Notification::fake();
        Artisan::call('reviews:send-reminders');

        Notification::assertSentTo($buyer, ReviewReminder::class);
        $this->assertNotNull(OrderLine::where('order_id', $order->id)->first()->review_reminder_sent_at);
    }

    public function test_the_review_reminder_command_does_not_run_twice(): void
    {
        $buyer = $this->seller('Buyer');
        $order = $this->placeOrder($buyer);
        $order->forceFill(['status' => OrderStatus::Delivered, 'updated_at' => now()->subDays(10)])->saveQuietly();

        Artisan::call('reviews:send-reminders');

        Notification::fake();
        Artisan::call('reviews:send-reminders');

        Notification::assertNothingSent();
    }

    public function test_a_buyer_can_opt_out_of_social_notifications(): void
    {
        $buyer = $this->seller('Buyer');
        $seller = $this->seller('Seller');
        $buyer->following()->create(['seller_user_id' => $seller->id]);
        $buyer->update(['notify_social' => false]);

        $listing = $this->listing($seller, $this->category('cosmetics'));
        $listing->forceFill(['status' => 'active', 'published_at' => now()])->save();

        $this->assertSame(0, $buyer->notifications()->count());
    }

    public function test_an_admin_can_broadcast_an_announcement_respecting_preferences(): void
    {
        $admin = $this->admin();
        $optedIn = $this->seller('OptedIn');
        $optedOut = $this->seller('OptedOut');
        $optedOut->update(['notify_announcements' => false]);

        // Real database-channel delivery (not faked) — this is what actually
        // proves the opted-out recipient's row count stays at zero.
        Livewire::actingAs($admin)
            ->test(SendAnnouncement::class)
            ->fillForm(['title' => 'Maintenance', 'body' => 'We will be down tonight.'])
            ->call('send');

        $this->assertSame(1, $optedIn->notifications()->count());
        $this->assertSame(0, $optedOut->notifications()->count());
    }

    public function test_settings_page_saves_notification_preferences(): void
    {
        $buyer = $this->seller('Buyer');

        $this->actingAs($buyer)->put(route('settings.update'), [
            'notify_social' => '0',
        ])->assertRedirect();

        $fresh = $buyer->fresh();
        $this->assertFalse($fresh->notify_social);
        $this->assertFalse($fresh->notify_announcements);
    }
}
