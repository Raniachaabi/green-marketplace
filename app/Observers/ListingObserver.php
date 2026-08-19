<?php

namespace App\Observers;

use App\Enums\ListingStatus;
use App\Models\Listing;
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
}
