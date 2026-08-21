<?php

namespace Tests\Feature;

use App\Filament\Resources\ListingResource\Pages\ListListings;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\CreatesMarketplace;
use Tests\TestCase;

class OriginAndStorytellingTest extends TestCase
{
    use CreatesMarketplace, RefreshDatabase;

    private function admin(): User
    {
        $admin = User::create(['phone' => '+21670000000', 'full_name' => 'Admin', 'slug' => 'admin']);
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    public function test_origin_is_never_verified_automatically(): void
    {
        $listing = $this->liveListing($this->seller(), $this->category('cosmetics'), ['governorate' => 'nabeul']);

        $this->assertFalse($listing->isOriginVerified());
    }

    public function test_an_admin_can_verify_origin_and_it_is_audited(): void
    {
        $admin = $this->admin();
        $listing = $this->liveListing($this->seller(), $this->category('cosmetics'), ['governorate' => 'nabeul']);

        // The resource's default tab is "Awaiting review" (pending only) —
        // this listing is active, so it needs the "all" tab to be visible.
        Livewire::actingAs($admin)
            ->test(ListListings::class)
            ->set('activeTab', 'all')
            ->callTableAction('verify_origin', $listing);

        $fresh = $listing->fresh();
        $this->assertTrue($fresh->isOriginVerified());
        $this->assertSame($admin->id, $fresh->origin_verified_by_admin_id);

        $log = AuditLog::where('entity_type', 'Listing')->where('action', 'listing.origin_verified')->first();
        $this->assertNotNull($log);
        $this->assertSame('nabeul', $log->context['governorate']);
    }

    public function test_a_seller_can_save_a_product_story_and_it_only_shows_when_filled_in(): void
    {
        $seller = $this->seller();
        $seller->forceFill(['preferred_locale' => 'fr'])->save();
        $listing = $this->liveListing($seller, $this->category('cosmetics'));

        $this->actingAs($seller)->put(route('seller.listings.update', $listing), [
            'title' => ['fr' => 'Savon', 'en' => 'Soap', 'ar' => 'صابون'],
            'price' => 10,
            'unit' => 'piece',
            'availability_model' => 'in_stock',
            'stock' => 5,
            'origin_locality' => 'Nabeul',
            'story' => ['fr' => 'Fabriqué à la main par notre coopérative.'],
            'production_process' => "Récolte\nPressage\nFiltration",
            'ingredients_materials' => ['fr' => "Huile d'olive, eau"],
        ])->assertRedirect();

        $fresh = $listing->fresh();

        $this->assertSame('Nabeul', $fresh->origin_locality);
        $this->assertSame('Fabriqué à la main par notre coopérative.', $fresh->translate('story', 'fr'));
        $this->assertSame(['Récolte', 'Pressage', 'Filtration'], $fresh->productionProcessSteps('fr'));
        $this->assertNull($fresh->translate('packaging_info', 'fr'));
    }

    public function test_a_seller_cannot_edit_another_sellers_story(): void
    {
        $owner = $this->seller('Owner');
        $intruder = $this->seller('Intruder');
        $listing = $this->liveListing($owner, $this->category('cosmetics'));

        $this->actingAs($intruder)->put(route('seller.listings.update', $listing), [
            'title' => ['fr' => 'Hacked'],
            'price' => 10,
            'unit' => 'piece',
            'availability_model' => 'in_stock',
            'story' => ['fr' => 'not mine to write'],
        ])->assertForbidden();

        $this->assertNull($listing->fresh()->translate('story'));
    }
}
