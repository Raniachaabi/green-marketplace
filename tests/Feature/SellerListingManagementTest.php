<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\CreatesMarketplace;
use Tests\TestCase;

class SellerListingManagementTest extends TestCase
{
    use CreatesMarketplace, RefreshDatabase;

    public function test_a_seller_can_update_their_own_draft_listing(): void
    {
        $seller = $this->seller();
        $listing = $this->listing($seller, $this->category('poterie'));

        $this->actingAs($seller)->put(route('seller.listings.update', $listing), [
            'title' => ['fr' => 'Vase en terre cuite'],
            'price' => 55,
            'unit' => 'piece',
            'availability_model' => 'in_stock',
            'stock' => 3,
            'min_order_qty' => 1,
        ])->assertRedirect(route('seller.listings.edit', $listing));

        $listing->refresh();

        $this->assertSame('Vase en terre cuite', $listing->translate('title', 'fr'));
        $this->assertSame(55000, (int) $listing->price);
        $this->assertSame(3, $listing->stock);
    }

    public function test_a_seller_cannot_update_someone_elses_listing(): void
    {
        $owner = $this->seller('Owner');
        $intruder = $this->seller('Intruder');
        $listing = $this->listing($owner, $this->category('poterie'));

        $this->actingAs($intruder)->put(route('seller.listings.update', $listing), [
            'title' => ['fr' => 'Hijack'],
            'price' => 1,
            'unit' => 'piece',
            'availability_model' => 'in_stock',
        ])->assertForbidden();
    }

    /**
     * FR — a live listing that gets edited must go back for review rather
     * than silently keep selling under changed terms.
     */
    public function test_editing_a_live_listing_sends_it_back_to_pending(): void
    {
        $seller = $this->seller();
        $listing = $this->liveListing($seller, $this->category('poterie'));

        $this->actingAs($seller)->put(route('seller.listings.update', $listing), [
            'title' => ['fr' => 'Nouveau titre'],
            'price' => 60,
            'unit' => 'piece',
            'availability_model' => 'in_stock',
            'stock' => $listing->stock,
        ])->assertSessionHas('status', __('seller.listing_resubmitted'));

        $this->assertSame(ListingStatus::Pending, $listing->fresh()->status);
    }

    public function test_a_seller_can_upload_and_manage_photos(): void
    {
        Storage::fake('public');

        $seller = $this->seller();
        $listing = $this->listing($seller, $this->category('poterie'), ['skip_media' => true]);

        $this->actingAs($seller)->post(route('seller.listings.media.store', $listing), [
            'photos' => [
                UploadedFile::fake()->image('front.jpg'),
                UploadedFile::fake()->image('back.jpg'),
            ],
        ])->assertRedirect();

        $this->assertCount(2, $listing->media()->get());

        $first = $listing->media()->orderBy('position')->first();
        Storage::disk('public')->assertExists($first->path);

        $second = $listing->media()->orderBy('position')->get()->last();

        $this->actingAs($seller)
            ->post(route('seller.listings.media.cover', [$listing, $second]))
            ->assertRedirect();

        $this->assertSame(0, $second->fresh()->position);

        $this->actingAs($seller)
            ->delete(route('seller.listings.media.destroy', [$listing, $first]))
            ->assertRedirect();

        $this->assertCount(1, $listing->media()->get());
        Storage::disk('public')->assertMissing($first->path);
    }

    public function test_a_seller_can_unpublish_a_live_listing(): void
    {
        $seller = $this->seller();
        $listing = $this->liveListing($seller, $this->category('poterie'));

        $this->actingAs($seller)
            ->post(route('seller.listings.unpublish', $listing))
            ->assertRedirect();

        $this->assertSame(ListingStatus::Draft, $listing->fresh()->status);
    }

    public function test_a_seller_can_delete_a_draft_but_not_a_live_listing(): void
    {
        $seller = $this->seller();
        $draft = $this->listing($seller, $this->category('poterie'));
        $live = $this->liveListing($seller, $this->category('savon'));

        $this->actingAs($seller)
            ->delete(route('seller.listings.destroy', $draft))
            ->assertRedirect(route('seller.listings.index'));

        $this->assertTrue(Listing::withTrashed()->findOrFail($draft->id)->trashed());

        $this->actingAs($seller)
            ->delete(route('seller.listings.destroy', $live))
            ->assertForbidden();

        $this->assertFalse($live->fresh()->trashed());
    }
}
