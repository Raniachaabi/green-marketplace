<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\Address;
use App\Models\AgreementVersion;
use App\Models\Carrier;
use App\Models\DeliveryZone;
use App\Models\User;
use App\Services\Cart;
use App\Services\OrderPlacer;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesMarketplace;
use Tests\TestCase;

/**
 * Not a full Filament/Livewire test — just enough to catch a fatal PHP
 * error in a resource or relation manager (a bad ->form() callback, a
 * missing relation, a typo in a class reference) before it reaches
 * production. If a page 500s, this fails; that is the whole point.
 */
class AdminPanelSmokeTest extends TestCase
{
    use CreatesMarketplace, RefreshDatabase;

    private function admin(): User
    {
        $admin = User::create(['phone' => '+21670000000', 'full_name' => 'Admin', 'slug' => 'admin']);
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    public function test_the_core_admin_resource_pages_render(): void
    {
        $admin = $this->admin();

        foreach (['/admin', '/admin/orders', '/admin/credentials', '/admin/payments', '/admin/categories', '/admin/listings', '/admin/users', '/admin/reviews'] as $path) {
            $this->actingAs($admin)->get($path)->assertOk();
        }
    }

    public function test_the_order_edit_page_with_relation_managers_renders(): void
    {
        $admin = $this->admin();
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

        $listing = $this->liveListing($this->seller('Seller'), $this->category('poterie'));
        app(Cart::class)->add($listing, 1);
        $order = app(OrderPlacer::class)->place($buyer, $address, PaymentMethod::Cod);

        $this->actingAs($admin)->get("/admin/orders/{$order->id}/edit")->assertOk();
    }
}
