<?php

namespace App\Notifications;

use App\Models\Listing;
use Illuminate\Notifications\Notification;

/**
 * In-app only for now (Phase 1) — the notifications table is the shared
 * shape every one of these classes writes to, rendered lazily in the
 * recipient's current locale rather than frozen at send time.
 */
class ListingApproved extends Notification
{
    public function __construct(private readonly Listing $listing) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message_key' => 'notifications.listing_approved',
            'replace' => ['title' => $this->listing->name()],
            'url' => route('seller.listings.edit', $this->listing),
        ];
    }
}
