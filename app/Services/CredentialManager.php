<?php

namespace App\Services;

use App\Enums\CredentialStatus;
use App\Enums\ListingStatus;
use App\Models\AuditLog;
use App\Models\Credential;
use App\Models\Listing;
use App\Models\User;
use App\Notifications\CredentialApproved;
use App\Notifications\CredentialExpired;
use App\Notifications\CredentialExpiringSoon;
use App\Notifications\CredentialRejected;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The credential lifecycle (FR-022, FR-025, FR-026).
 *
 * The important method here is suspendListingsDependingOn(). A silently
 * expired certificate on a live "Bio certifié" listing is exactly how a green
 * marketplace produces its first scandal — so expiry is not a notification,
 * it is an enforcement action.
 */
class CredentialManager
{
    public function approve(Credential $credential, User $admin): Credential
    {
        $credential->forceFill([
            'status' => CredentialStatus::Approved,
            'verified_by_admin_id' => $admin->id,
            'verified_at' => now(),
            'rejection_reason' => null,
        ])->save();

        AuditLog::record('credential.approved', $credential, [
            'type' => $credential->credential_type_code,
            'number' => $credential->number,
            'expires_at' => $credential->expires_at?->toDateString(),
        ], $admin);

        if ($credential->holder() instanceof User) {
            $credential->holder()->notify(new CredentialApproved($credential));
        }

        return $credential;
    }

    public function reject(Credential $credential, User $admin, string $reason): Credential
    {
        $credential->forceFill([
            'status' => CredentialStatus::Rejected,
            'verified_by_admin_id' => $admin->id,
            'verified_at' => now(),
            'rejection_reason' => $reason,
        ])->save();

        AuditLog::record('credential.rejected', $credential, ['reason' => $reason], $admin);

        if ($credential->holder() instanceof User) {
            $credential->holder()->notify(new CredentialRejected($credential, $reason));
        }

        $this->suspendListingsDependingOn($credential, 'credential_rejected');

        return $credential;
    }

    /** Credentials that have passed their expiry date but are still marked approved. */
    public function findNewlyExpired(): Collection
    {
        return Credential::query()
            ->where('status', CredentialStatus::Approved)
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<', now()->toDateString())
            ->get();
    }

    public function markExpired(Credential $credential): void
    {
        $credential->forceFill(['status' => CredentialStatus::Expired])->save();

        AuditLog::record('credential.expired', $credential, [
            'type' => $credential->credential_type_code,
            'expired_on' => $credential->expires_at?->toDateString(),
        ]);

        if ($credential->holder() instanceof User) {
            $credential->holder()->notify(new CredentialExpired($credential));
        }

        $this->suspendListingsDependingOn($credential, 'credential_expired');
    }

    /**
     * FR-026 — suspend every live listing that depended on this credential.
     *
     * "Depended on" means: the listing's category (or one of its ancestors)
     * lists this credential type as mandatory, and the seller can no longer
     * prove it. Listings in categories that never needed it are untouched.
     */
    public function suspendListingsDependingOn(Credential $credential, string $reason): int
    {
        $holder = $credential->holder();

        if (! $holder) {
            return 0;
        }

        $stillUsable = $holder->approvedCredentialCodes();

        if ($stillUsable->contains($credential->credential_type_code)) {
            // Another valid credential of the same type covers it — for
            // example a renewal uploaded before the old one lapsed.
            return 0;
        }

        $listings = Listing::query()
            ->where('status', ListingStatus::Active)
            ->when(
                $holder instanceof \App\Models\Organization,
                fn ($q) => $q->where('seller_org_id', $holder->id),
                fn ($q) => $q->where('seller_user_id', $holder->id),
            )
            ->with('category')
            ->get();

        $suspended = 0;

        DB::transaction(function () use ($listings, $credential, $reason, &$suspended) {
            foreach ($listings as $listing) {
                $required = $listing->category?->mandatoryCredentialCodes() ?? collect();

                if (! $required->contains($credential->credential_type_code)) {
                    continue;
                }

                $listing->forceFill([
                    'status' => ListingStatus::Suspended,
                    'status_reason' => $reason,
                    'suspended_at' => now(),
                ])->save();

                AuditLog::record('listing.suspended', $listing, [
                    'reason' => $reason,
                    'credential_type' => $credential->credential_type_code,
                ]);

                $suspended++;
            }
        });

        return $suspended;
    }

    /**
     * FR-025 — which reminder thresholds are due for a credential, skipping
     * any already sent so a seller never gets the same warning twice.
     */
    public function dueReminderThresholds(Credential $credential): array
    {
        $days = $credential->daysUntilExpiry();

        if ($days === null || $days < 0) {
            return [];
        }

        $sent = $credential->reminders_sent ?? [];

        return collect(config('marketplace.credential_reminder_days'))
            ->filter(fn (int $threshold) => $days <= $threshold && ! in_array($threshold, $sent, true))
            ->values()
            ->all();
    }

    public function recordReminderSent(Credential $credential, int $threshold): void
    {
        $sent = $credential->reminders_sent ?? [];
        $sent[] = $threshold;

        $credential->forceFill(['reminders_sent' => array_values(array_unique($sent))])->save();

        if ($credential->holder() instanceof User) {
            $credential->holder()->notify(new CredentialExpiringSoon($credential, $threshold));
        }
    }
}
