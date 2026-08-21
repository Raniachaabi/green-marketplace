<?php

namespace App\Observers;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Models\RestockAlert;
use App\Models\User;
use App\Notifications\FollowedSellerNewListing;
use App\Notifications\ListingApproved;
use App\Notifications\ListingRejected;
use App\Notifications\ListingSuspended;
use App\Notifications\RestockAvailable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class ListingObserver
{
    public function creating(Listing $listing): void
    {
        if (blank($listing->slug)) {
            $base = Str::slug($listing->translate('title') ?: 'listing');
            $listing->slug = $base.'-'.Str::lower(Str::random(6));
        }

        // Denormalised for the "near me" filter, so browsing does not need a
        // join to the seller's address on every query.
        if (blank($listing->governorate)) {
            $seller = $listing->seller();
            $listing->governorate = $seller?->governorate
                ?? $seller?->addresses()?->where('is_default', true)->value('governorate');
        }
    }

    /**
     * A listing that is edited after going live drops back to pending.
     * The alternative is letting a seller publish a compliant listing and
     * then quietly swap the content, which defeats the whole verification
     * layer.
     */
    public function updating(Listing $listing): void
    {
        if (! $listing->isDirty()) {
            return;
        }

        $sensitive = ['title', 'description', 'price', 'category_id', 'attribute_values', 'lot_number'];

        $touchedSensitive = collect($sensitive)->contains(fn ($f) => $listing->isDirty($f));

        if ($touchedSensitive
            && $listing->getRawOriginal('status') === ListingStatus::Active->value
            && ! $listing->isDirty('status')) {
            $listing->status = ListingStatus::Pending;
            $listing->status_reason = 'edited_after_publication';
        }
    }

    /**
     * Notify the seller on every moderation outcome. Hooked here rather than
     * at each call site (the publishing gate, the admin suspend action, a
     * raw Filament edit) so no path into these statuses can forget to tell
     * the seller what happened.
     */
    public function updated(Listing $listing): void
    {
        $this->notifyRestockSubscribers($listing);

        if (! $listing->wasChanged('status') || ! $listing->sellerUser) {
            return;
        }

        $isFirstPublish = $listing->status === ListingStatus::Active && $listing->getOriginal('published_at') === null;

        match ($listing->status) {
            ListingStatus::Active => $listing->sellerUser->notify(new ListingApproved($listing)),
            ListingStatus::Suspended => $listing->sellerUser->notify(new ListingSuspended($listing)),
            ListingStatus::Rejected => $listing->sellerUser->notify(new ListingRejected($listing)),
            default => null,
        };

        if ($isFirstPublish) {
            $this->notifyFollowers($listing);
        }
    }

    /**
     * Phase 2 §5 — fires once stock goes from "none" to "some" on a listing
     * that is actually live. Every pending subscriber is notified and their
     * row is marked fulfilled in the same pass, so nobody is notified twice.
     */
    private function notifyRestockSubscribers(Listing $listing): void
    {
        if (! $listing->wasChanged('stock')
            || (float) $listing->getOriginal('stock') > 0
            || $listing->stock <= 0
            || $listing->status !== ListingStatus::Active) {
            return;
        }

        $alerts = $listing->restockAlerts()->pending()->with('user')->get();

        if ($alerts->isEmpty()) {
            return;
        }

        Notification::send($alerts->pluck('user')->filter(), new RestockAvailable($listing));

        RestockAlert::whereIn('id', $alerts->pluck('id'))->update(['notified_at' => now()]);
    }

    /**
     * Phase 2 §3 — every follower of this seller hears about a genuinely
     * new listing going live, once, the first time it publishes (not on
     * every later re-approval after an edit).
     */
    private function notifyFollowers(Listing $listing): void
    {
        $followerIds = $listing->sellerUser->followers()->pluck('follower_user_id');

        if ($followerIds->isEmpty()) {
            return;
        }

        Notification::send(User::whereIn('id', $followerIds)->get(), new FollowedSellerNewListing($listing));
    }
}
