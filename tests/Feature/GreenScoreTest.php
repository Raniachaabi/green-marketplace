<?php

namespace Tests\Feature;

use App\Filament\Resources\GreenScoreRuleResource\Pages\EditGreenScoreRule;
use App\Models\AuditLog;
use App\Models\GreenScoreRule;
use App\Models\Listing;
use App\Models\Organization;
use App\Models\User;
use App\Services\GreenScoreCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\CreatesMarketplace;
use Tests\TestCase;

class GreenScoreTest extends TestCase
{
    use CreatesMarketplace, RefreshDatabase;

    private function admin(): User
    {
        $admin = User::create(['phone' => '+21670000000', 'full_name' => 'Admin', 'slug' => 'admin']);
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    public function test_score_only_counts_rules_matching_real_listing_data(): void
    {
        GreenScoreRule::create([
            'code' => 'local_production', 'name' => ['en' => 'Local production'], 'icon' => '🇹🇳',
            'category' => 'origin', 'check_type' => 'origin_governorate', 'points' => 25,
        ]);
        GreenScoreRule::create([
            'code' => 'organic', 'name' => ['en' => 'Organic'], 'icon' => '🌱',
            'category' => 'certification', 'check_type' => 'green_attribute', 'check_value' => 'organic_certified', 'points' => 15,
        ]);

        $seller = $this->seller();
        $category = $this->category('cosmetics');

        $withGovernorate = $this->liveListing($seller, $category, ['governorate' => 'nabeul']);
        $withoutGovernorate = $this->liveListing($seller, $category);

        $result = app(GreenScoreCalculator::class)->scoreFor($withGovernorate);
        $this->assertSame(25, $result->total);
        $this->assertSame('local_production', $result->breakdown->first()['rule']->code);

        $result = app(GreenScoreCalculator::class)->scoreFor($withoutGovernorate);
        $this->assertSame(0, $result->total);
        $this->assertTrue($result->isEmpty());
    }

    public function test_an_inactive_rule_does_not_contribute_to_the_score(): void
    {
        GreenScoreRule::create([
            'code' => 'local_production', 'name' => ['en' => 'Local production'], 'icon' => '🇹🇳',
            'category' => 'origin', 'check_type' => 'origin_governorate', 'points' => 25, 'is_active' => false,
        ]);

        $listing = $this->liveListing($this->seller(), $this->category('cosmetics'), ['governorate' => 'nabeul']);

        $result = app(GreenScoreCalculator::class)->scoreFor($listing);

        $this->assertSame(0, $result->total);
    }

    public function test_small_producer_rule_distinguishes_individuals_from_large_companies(): void
    {
        GreenScoreRule::create([
            'code' => 'small_producer', 'name' => ['en' => 'Small producer'], 'icon' => '🤝',
            'category' => 'producer', 'check_type' => 'small_producer', 'points' => 10,
        ]);

        $company = Organization::create(['type' => 'company', 'legal_name' => 'Big Co', 'slug' => 'big-co']);
        $category = $this->category('cosmetics');

        $listing = $this->liveListing($this->seller(), $category);
        $companyListing = Listing::create([
            'seller_org_id' => $company->id,
            'category_id' => $category->id,
            'title' => ['en' => 'Company item'],
            'price' => 100000, 'unit' => 'piece', 'stock' => 5,
            'availability_model' => 'in_stock', 'status' => 'active', 'published_at' => now(),
        ]);

        $this->assertSame(10, app(GreenScoreCalculator::class)->scoreFor($listing)->total);
        $this->assertSame(0, app(GreenScoreCalculator::class)->scoreFor($companyListing)->total);
    }

    public function test_an_admin_can_toggle_a_rule_and_the_change_is_audited(): void
    {
        $admin = $this->admin();
        $rule = GreenScoreRule::create([
            'code' => 'local_production', 'name' => ['en' => 'Local production'], 'icon' => '🇹🇳',
            'category' => 'origin', 'check_type' => 'origin_governorate', 'points' => 25,
        ]);

        Livewire::actingAs($admin)
            ->test(EditGreenScoreRule::class, ['record' => $rule->getRouteKey()])
            ->fillForm(['points' => 30, 'is_active' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $rule->fresh();
        $this->assertSame(30, $fresh->points);
        $this->assertFalse($fresh->is_active);

        $log = AuditLog::where('entity_type', 'GreenScoreRule')->where('entity_id', $rule->id)->first();
        $this->assertNotNull($log);
        $this->assertSame(25, $log->context['before']['points']);
        $this->assertSame(30, $log->context['after']['points']);
    }

    public function test_a_non_admin_cannot_reach_the_green_score_rules_admin_page(): void
    {
        $buyer = $this->seller('Buyer');

        $this->actingAs($buyer)->get('/admin/green-score-rules')->assertForbidden();
    }
}
