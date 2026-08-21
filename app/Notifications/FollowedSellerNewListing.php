<?php

namespace App\Notifications;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Notifications\Notification;

class FollowedSellerNewListing extends Notification
{
    public function __construct(private readonly Listing $listing) {}

    /** §1 — discretionary; skipped for a buyer who opted out. */
    public function via(object $notifiable): array
    {
        return ($notifiable instanceof User && ! $notifiable->wantsSocialNotifications()) ? [] : ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message_key' => 'notifications.followed_seller_new_listing',
            'replace' => ['seller' => $this->listing->sellerLabel(), 'title' => $this->listing->name()],
            'url' => route('catalog.show', $this->listing->slug),
        ];
    }
}
