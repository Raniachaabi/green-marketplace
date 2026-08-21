<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserSuspensionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_suspended_user_cannot_log_in(): void
    {
        $user = User::create([
            'phone' => '+21699000000',
            'email' => 'amira@example.tn',
            'full_name' => 'Amira',
            'password' => Hash::make('password123'),
            'slug' => 'amira',
        ]);
        $user->forceFill(['status' => UserStatus::Suspended])->save();

        $this->post(route('login.store'), [
            'email' => 'amira@example.tn',
            'password' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_an_active_user_can_still_log_in(): void
    {
        $user = User::create([
            'phone' => '+21699000000',
            'email' => 'amira@example.tn',
            'full_name' => 'Amira',
            'password' => Hash::make('password123'),
            'slug' => 'amira',
        ]);

        $this->post(route('login.store'), [
            'email' => 'amira@example.tn',
            'password' => 'password123',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }
}
