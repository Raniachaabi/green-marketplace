<?php

namespace Tests\Feature;

use App\Enums\CredentialStatus;
use App\Enums\ListingStatus;
use App\Services\Publishing\PublishingGate;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesMarketplace;
use Tests\TestCase;

/**
 * The publishing gate is the single place where every promise this
 * marketplace makes to a buyer is enforced. If these tests pass, an
 * uncertified seller cannot list regulated goods, food cannot go live without
 * a lot number, and nobody can price above a state ceiling.
 */
class PublishingGateTest extends TestCase
{
    use CreatesMarketplace, RefreshDatabase;

    private function gate(): PublishingGate
    {
        return app(PublishingGate::class);
    }

    public function test_it_publishes_a_compliant_listing(): void
    {
        $this->credentialType('moa_seed_authorization');
        $category = $this->category('semences');
        $this->requireCredential($category, 'moa_seed_authorization');

        $seller = $this->seller();
        $this->giveCredential($seller, 'moa_seed_authorization', now()->addYear()->toDateString());

        $listing = $this->listing($seller, $category);

        $result = $this->gate()->publish($listing);

        $this->assertTrue($result->passes(), implode('; ', $result->messages()));
        $this->assertSame(ListingStatus::Active, $listing->fresh()->status);
    }

    public function test_it_blocks_a_seller_without_the_required_credential(): void
    {
        $this->credentialType('moa_seed_authorization');
        $category = $this->category('semences');
        $this->requireCredential($category, 'moa_seed_authorization');

        $listing = $this->listing($this->seller(), $category);

        $result = $this->gate()->publish($listing);

        $this->assertTrue($result->fails());
        $this->assertContains('missing_credential', $result->codes());
        $this->assertSame(ListingStatus::Draft, $listing->fresh()->status);
    }

    /**
     * The distinction matters: a seller who never applied needs to apply, a
     * seller whose document lapsed needs to renew. Same block, different
     * message, very different action.
     */
    public function test_it_distinguishes_an_expired_credential_from_a_missing_one(): void
    {
        $this->credentialType('sanitary_authorization');
        $category = $this->category('conserves');
        $this->requireCredential($category, 'sanitary_authorization');

        $seller = $this->seller();
        $this->giveCredential($seller, 'sanitary_authorization', now()->subDay()->toDateString());

        $result = $this->gate()->check($this->listing($seller, $category));

        $this->assertContains('expired_credential', $result->codes());
        $this->assertNotContains('missing_credential', $result->codes());
    }

    public function test_a_pending_credential_does_not_count(): void
    {
        $this->credentialType('sanitary_authorization');
        $category = $this->category('conserves');
        $this->requireCredential($category, 'sanitary_authorization');

        $seller = $this->seller();
        $this->giveCredential(
            $seller,
            'sanitary_authorization',
            now()->addYear()->toDateString(),
            CredentialStatus::Pending,
        );

        $this->assertTrue($this->gate()->check($this->listing($seller, $category))->fails());
    }

    /** LC-02 — the ministry ceiling is a hard limit, not a suggestion. */
    public function test_it_blocks_a_price_above_the_state_ceiling(): void
    {
        $category = $this->category('ble-dur');
        $this->priceCap($category, 170);

        $listing = $this->listing($this->seller(), $category, ['price' => Money::fromDinars(180)]);

        $result = $this->gate()->check($listing);

        $this->assertContains('price_above_cap', $result->codes());
    }

    public function test_it_allows_the_contract_surcharge_on_protected_varieties(): void
    {
        $category = $this->category('ble-dur');
        $this->priceCap($category, 170, surcharge: 3);

        // 170 + 3% = 175.1 TND — allowed only when the variety is flagged as
        // being under a commercial exploitation contract.
        $listing = $this->listing($this->seller(), $category, [
            'price' => Money::fromDinars(175),
            'attribute_values' => ['under_exploitation_contract' => true],
        ]);

        $this->assertTrue($this->gate()->check($listing)->passes());

        $without = $this->listing($this->seller(), $category, [
            'price' => Money::fromDinars(175),
            'attribute_values' => ['under_exploitation_contract' => false],
        ]);

        $this->assertContains('price_above_cap', $this->gate()->check($without)->codes());
    }

    /** FR-028 / FR-036 — nothing edible goes live untraceable. */
    public function test_it_requires_a_lot_number_where_the_category_rule_demands_one(): void
    {
        $category = $this->category('conserves');
        $this->rule($category, ['requires_lot_number' => true]);

        $without = $this->listing($this->seller(), $category, ['lot_number' => null]);
        $this->assertContains('missing_lot_number', $this->gate()->check($without)->codes());

        $with = $this->listing($this->seller(), $category, ['lot_number' => 'LOT-1']);
        $this->assertNotContains('missing_lot_number', $this->gate()->check($with)->codes());
    }

    public function test_it_requires_category_fields_marked_required(): void
    {
        $category = $this->category('semences');
        $this->requiredField($category, 'variety');

        $listing = $this->listing($this->seller(), $category, ['attribute_values' => []]);
        $this->assertContains('missing_required_field', $this->gate()->check($listing)->codes());

        $listing->update(['attribute_values' => ['variety' => 'Karim']]);
        $this->assertNotContains('missing_required_field', $this->gate()->check($listing->fresh())->codes());
    }

    /**
     * Requirements set on a parent category apply to every child. This is
     * what stops someone adding a jam subcategory that quietly skips the
     * sanitary authorization.
     */
    public function test_requirements_cascade_from_parent_categories(): void
    {
        $this->credentialType('sanitary_authorization');

        $parent = $this->category('terroir', ['is_leaf' => false]);
        $this->requireCredential($parent, 'sanitary_authorization');

        $child = $this->category('confitures', ['parent_id' => $parent->id, 'path' => 'terroir/confitures']);

        $result = $this->gate()->check($this->listing($this->seller(), $child));

        $this->assertContains('missing_credential', $result->codes());
    }

    public function test_it_blocks_an_invasive_species(): void
    {
        $category = $this->category('pepiniere');
        $listing = $this->listing($this->seller(), $category);

        $species = \App\Models\Species::create([
            'botanical_name' => 'Acacia saligna',
            'common_name' => ['fr' => 'Acacia'],
            'is_invasive_blocked' => true,
        ]);

        $listing->species()->attach($species->id);

        $this->assertContains('invasive_species', $this->gate()->check($listing->fresh())->codes());
    }

    public function test_a_seasonal_listing_needs_a_season_window(): void
    {
        $category = $this->category('eau-de-rose');

        $listing = $this->listing($this->seller(), $category, [
            'availability_model' => 'seasonal',
            'season_start' => null,
            'season_end' => null,
        ]);

        $this->assertContains('missing_season_window', $this->gate()->check($listing)->codes());
    }
}
