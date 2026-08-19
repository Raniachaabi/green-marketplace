<?php

namespace Tests\Feature;

use App\Enums\CredentialStatus;
use App\Enums\ListingStatus;
use App\Services\CredentialManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesMarketplace;
use Tests\TestCase;

/**
 * FR-025 / FR-026 — expiry is an enforcement action, not a notification.
 *
 * A live "Bio certifié" listing backed by a certificate that lapsed in March
 * is the exact failure this platform cannot survive, so these tests matter
 * more than their size suggests.
 */
class CredentialExpiryTest extends TestCase
{
    use CreatesMarketplace, RefreshDatabase;

    public function test_expiry_suspends_listings_that_depended_on_the_credential(): void
    {
        $this->credentialType('organic_certificate');
        $category = $this->category('bio');
        $this->requireCredential($category, 'organic_certificate');

        $seller = $this->seller();
        $credential = $this->giveCredential($seller, 'organic_certificate', now()->addYear()->toDateString());

        $listing = $this->listing($seller, $category);
        app(\App\Services\Publishing\PublishingGate::class)->publish($listing);
        $this->assertSame(ListingStatus::Active, $listing->fresh()->status);

        // The certificate lapses.
        $credential->forceFill(['expires_at' => now()->subDay()])->save();

        $this->artisan('credentials:check-expiry')->assertSuccessful();

        $this->assertSame(CredentialStatus::Expired, $credential->fresh()->status);
        $this->assertSame(ListingStatus::Suspended, $listing->fresh()->status);
        $this->assertSame('credential_expired', $listing->fresh()->status_reason);
    }

    /**
     * Suspension must be surgical. A seller who sells both certified seed and
     * unregulated pottery should not lose the pottery listing because their
     * seed authorization lapsed.
     */
    public function test_expiry_does_not_touch_listings_in_unrelated_categories(): void
    {
        $this->credentialType('moa_seed_authorization');

        $regulated = $this->category('semences');
        $this->requireCredential($regulated, 'moa_seed_authorization');

        $unregulated = $this->category('poterie');

        $seller = $this->seller();
        $credential = $this->giveCredential($seller, 'moa_seed_authorization', now()->addYear()->toDateString());

        $gate = app(\App\Services\Publishing\PublishingGate::class);

        $seedListing = $this->listing($seller, $regulated);
        $potteryListing = $this->listing($seller, $unregulated);
        $gate->publish($seedListing);
        $gate->publish($potteryListing);

        $credential->forceFill(['expires_at' => now()->subDay()])->save();
        $this->artisan('credentials:check-expiry');

        $this->assertSame(ListingStatus::Suspended, $seedListing->fresh()->status);
        $this->assertSame(ListingStatus::Active, $potteryListing->fresh()->status);
    }

    /** A renewal uploaded and approved before the old one lapses keeps listings live. */
    public function test_a_valid_renewal_prevents_suspension(): void
    {
        $this->credentialType('organic_certificate');
        $category = $this->category('bio');
        $this->requireCredential($category, 'organic_certificate');

        $seller = $this->seller();
        $old = $this->giveCredential($seller, 'organic_certificate', now()->addDay()->toDateString());
        $this->giveCredential($seller, 'organic_certificate', now()->addYear()->toDateString());

        $listing = $this->listing($seller, $category);
        app(\App\Services\Publishing\PublishingGate::class)->publish($listing);

        $old->forceFill(['expires_at' => now()->subDay()])->save();
        $this->artisan('credentials:check-expiry');

        $this->assertSame(ListingStatus::Active, $listing->fresh()->status);
    }

    public function test_reminder_thresholds_fire_once_each(): void
    {
        $this->credentialType('liability_insurance');
        $seller = $this->seller();

        // 25 days out crosses both the 60 and 30 day thresholds.
        $credential = $this->giveCredential($seller, 'liability_insurance', now()->addDays(25)->toDateString());

        $manager = app(CredentialManager::class);

        $due = $manager->dueReminderThresholds($credential);
        $this->assertEqualsCanonicalizing([60, 30], $due);

        foreach ($due as $threshold) {
            $manager->recordReminderSent($credential, $threshold);
        }

        // Only the 7-day threshold remains, and it is not due yet.
        $this->assertSame([], $manager->dueReminderThresholds($credential->fresh()));
    }

    public function test_rejecting_a_credential_suspends_dependent_listings(): void
    {
        $this->credentialType('sanitary_authorization');
        $category = $this->category('conserves');
        $this->requireCredential($category, 'sanitary_authorization');

        $admin = $this->seller('Admin');
        $seller = $this->seller();
        $credential = $this->giveCredential($seller, 'sanitary_authorization', now()->addYear()->toDateString());

        $listing = $this->listing($seller, $category);
        app(\App\Services\Publishing\PublishingGate::class)->publish($listing);

        app(CredentialManager::class)->reject($credential, $admin, 'Document illisible');

        $this->assertSame(ListingStatus::Suspended, $listing->fresh()->status);
        $this->assertSame('credential_rejected', $listing->fresh()->status_reason);
    }
}
