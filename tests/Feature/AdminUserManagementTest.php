<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::create(['phone' => '+21670000000', 'full_name' => 'Admin', 'slug' => 'admin']);
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    public function test_an_admin_can_grant_admin_access_to_another_account(): void
    {
        $admin = $this->admin();
        $buyer = User::create(['phone' => '+21699222222', 'full_name' => 'Future Admin', 'slug' => 'future-admin']);

        Livewire::actingAs($admin)
            ->test(EditUser::class, ['record' => $buyer->getRouteKey()])
            ->fillForm(['is_admin' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($buyer->fresh()->is_admin);
    }

    public function test_editing_a_users_name_does_not_reset_their_status(): void
    {
        $admin = $this->admin();
        $buyer = User::create(['phone' => '+21699333333', 'full_name' => 'Renamed Later', 'slug' => 'renamed-later']);
        $buyer->forceFill(['status' => UserStatus::Suspended])->save();

        Livewire::actingAs($admin)
            ->test(EditUser::class, ['record' => $buyer->getRouteKey()])
            ->fillForm(['full_name' => 'New Name'])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $buyer->fresh();
        $this->assertSame('New Name', $fresh->full_name);
        $this->assertTrue($fresh->isSuspended());
    }
}
