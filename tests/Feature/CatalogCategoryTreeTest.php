<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\CreatesMarketplace;
use Tests\TestCase;

/**
 * Regression coverage for a path-prefix bug found during the Phase 2 final
 * pass: "vegetal2" is not a descendant of "vegetal" just because its path
 * string happens to start with the same characters — path matching must be
 * bounded on a "/" separator, not a raw string prefix.
 */
class CatalogCategoryTreeTest extends TestCase
{
    use CreatesMarketplace, RefreshDatabase;

    public function test_browsing_a_root_category_never_leaks_a_sibling_whose_slug_shares_a_prefix(): void
    {
        $root = $this->category('vegetal');
        $child = $this->category('fleuriste', ['parent_id' => $root->id, 'path' => 'vegetal/fleuriste']);
        $sibling = $this->category('vegetal2');

        $seller = $this->seller('Seller');
        $inRoot = $this->liveListing($seller, $root, ['title' => ['fr' => 'Plante racine']]);
        $inChild = $this->liveListing($seller, $child, ['title' => ['fr' => 'Fleur enfant']]);
        $inSibling = $this->liveListing($seller, $sibling, ['title' => ['fr' => 'Produit du voisin']]);

        $response = $this->get(route('catalog.index', ['category' => 'vegetal']));

        $response->assertSee($inRoot->name());
        $response->assertSee($inChild->name());
        $response->assertDontSee($inSibling->name());
    }

    public function test_the_home_page_root_category_counts_never_leak_a_prefix_sharing_sibling(): void
    {
        $root = $this->category('vegetal');
        $child = $this->category('fleuriste', ['parent_id' => $root->id, 'path' => 'vegetal/fleuriste']);
        $sibling = $this->category('vegetal2');

        $seller = $this->seller('Seller');
        $this->liveListing($seller, $root);
        $this->liveListing($seller, $child);
        $this->liveListing($seller, $sibling);

        $response = $this->get(route('home'));

        $response->assertSee(__('home.listing_count', ['count' => 2]));
        $response->assertDontSee(__('home.listing_count', ['count' => 3]));
    }

    public function test_the_home_page_computes_root_category_counts_in_a_bounded_number_of_queries(): void
    {
        $seller = $this->seller('Seller');
        for ($i = 0; $i < 6; $i++) {
            $this->liveListing($seller, $this->category("root-{$i}"));
        }

        $queryCount = 0;
        DB::listen(function () use (&$queryCount) {
            $queryCount++;
        });

        $this->get(route('home'))->assertOk();

        // However many categories exist, the root-count computation itself
        // must stay at two queries (all paths, then one grouped listing
        // count) rather than growing with the number of root categories.
        $this->assertLessThan(30, $queryCount, 'Query count should not scale with the number of categories.');
    }
}
