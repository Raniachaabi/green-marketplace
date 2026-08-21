<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class RegistrationAndPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_visitor_can_register_and_is_logged_in_as_a_buyer(): void
    {
        $this->post(route('register.store'), [
            'full_name' => 'Amira Nouveau',
            'phone' => '+21699000000',
            'email' => 'amira@example.tn',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('home'));

        $user = User::where('phone', '+21699000000')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('buyer'));
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_accepts_a_blank_email_field(): void
    {
        // A real browser submits an empty text input as "", not an absent
        // key — that must not fail the (optional) email rule.
        $this->post(route('register.store'), [
            'full_name' => 'Sami Sans Email',
            'phone' => '+21699111111',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('home'));

        $user = User::where('phone', '+21699111111')->first();

        $this->assertNotNull($user);
        $this->assertNull($user->email);
    }

    public function test_a_buyer_with_no_email_can_log_back_in_with_their_phone(): void
    {
        $this->post(route('register.store'), [
            'full_name' => 'Sami Sans Email',
            'phone' => '+21699111111',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->post(route('logout'));

        $this->post(route('login.store'), [
            'email' => '+21699111111',
            'password' => 'password123',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs(User::where('phone', '+21699111111')->first());
    }

    public function test_registration_rejects_a_duplicate_phone(): void
    {
        User::create(['phone' => '+21699000000', 'full_name' => 'Existing', 'slug' => 'existing']);

        $this->post(route('register.store'), [
            'full_name' => 'Amira Nouveau',
            'phone' => '+21699000000',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('phone');

        $this->assertGuest();
    }

    public function test_a_user_can_request_a_password_reset_link(): void
    {
        Notification::fake();

        $user = User::create([
            'phone' => '+21699000000',
            'email' => 'amira@example.tn',
            'full_name' => 'Amira',
            'password' => Hash::make('old-password'),
            'slug' => 'amira',
        ]);

        $this->post(route('password.email'), ['email' => 'amira@example.tn'])
            ->assertRedirect();

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_a_user_can_reset_their_password_with_a_valid_token(): void
    {
        $user = User::create([
            'phone' => '+21699000000',
            'email' => 'amira@example.tn',
            'full_name' => 'Amira',
            'password' => Hash::make('old-password'),
            'slug' => 'amira',
        ]);

        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'amira@example.tn',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('login.show'));

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }

    public function test_the_storefront_login_locks_out_after_repeated_failures(): void
    {
        $user = User::create([
            'phone' => '+21699000000',
            'email' => 'amira@example.tn',
            'full_name' => 'Amira',
            'password' => Hash::make('correct-password'),
            'slug' => 'amira',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.store'), ['email' => 'amira@example.tn', 'password' => 'wrong']);
        }

        $this->post(route('login.store'), ['email' => 'amira@example.tn', 'password' => 'correct-password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
