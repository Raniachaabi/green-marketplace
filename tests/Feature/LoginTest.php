<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\CreatesMarketplace;
use Tests\TestCase;

/**
 * The storefront login is separate from Filament's /admin/login, which
 * rejects (and immediately logs out) anyone whose canAccessPanel() is
 * false — i.e. every non-admin account. This is the only door a seller
 * or buyer actually has into the app.
 */
class LoginTest extends TestCase
{
    use CreatesMarketplace, RefreshDatabase;

    public function test_a_non_admin_seller_can_sign_in_through_the_storefront(): void
    {
        $seller = $this->seller();
        $seller->forceFill(['email' => 'seller@example.tn', 'password' => Hash::make('password')])->save();

        $this->post(route('login.store'), [
            'email' => 'seller@example.tn',
            'password' => 'password',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($seller);
    }

    public function test_wrong_credentials_are_rejected(): void
    {
        $seller = $this->seller();
        $seller->forceFill(['email' => 'seller@example.tn', 'password' => Hash::make('password')])->save();

        $this->from(route('login.show'))->post(route('login.store'), [
            'email' => 'seller@example.tn',
            'password' => 'wrong',
        ])->assertRedirect(route('login.show'))->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_a_guest_hitting_a_protected_seller_route_is_sent_to_the_storefront_login(): void
    {
        $this->get(route('seller.listings.index'))->assertRedirect(route('login.show'));
    }

    public function test_logout_ends_the_session(): void
    {
        $seller = $this->seller();

        $this->actingAs($seller)
            ->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }
}
