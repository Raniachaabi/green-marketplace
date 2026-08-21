<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\CreatesMarketplace;
use Tests\TestCase;

class SellerStoryTest extends TestCase
{
    use CreatesMarketplace, RefreshDatabase;

    public function test_a_seller_can_save_their_story(): void
    {
        $seller = $this->seller('Seller');

        $this->actingAs($seller)->put(route('seller.story.update'), [
            'bio' => 'Small family farm.',
            'story' => 'We have been growing olives for three generations.',
            'production_method' => 'Cold-pressed, traditional stone mill',
            'mission' => 'Keep traditional methods alive.',
            'founding_year' => 1995,
        ])->assertRedirect();

        $fresh = $seller->fresh();
        $this->assertSame('Small family farm.', $fresh->bio);
        $this->assertSame('We have been growing olives for three generations.', $fresh->story);
        $this->assertTrue($fresh->hasStory());
        $this->assertSame(now()->year - 1995, $fresh->yearsActive());
    }

    public function test_a_seller_can_upload_a_cover_photo(): void
    {
        Storage::fake('public');
        $seller = $this->seller('Seller');

        $this->actingAs($seller)->put(route('seller.story.update'), [
            'cover' => UploadedFile::fake()->image('cover.jpg'),
        ])->assertRedirect();

        $fresh = $seller->fresh();
        $this->assertNotNull($fresh->cover_path);
        Storage::disk('public')->assertExists($fresh->cover_path);
    }

    public function test_the_storefront_only_shows_the_story_section_when_filled_in(): void
    {
        $seller = $this->seller('Seller');
        $listing = $this->liveListing($seller, $this->category('poterie'));

        $response = $this->get(route('seller.storefront', $seller->slug));
        $response->assertDontSee(__('seller.meet_the_producer'));

        $seller->update(['story' => 'Our story.']);

        $response = $this->get(route('seller.storefront', $seller->slug));
        $response->assertSee(__('seller.meet_the_producer'));
        $response->assertSee('Our story.');
    }

    public function test_a_guest_cannot_reach_the_story_editor(): void
    {
        $this->put(route('seller.story.update'), ['bio' => 'x'])->assertRedirect(route('login.show'));
    }
}
