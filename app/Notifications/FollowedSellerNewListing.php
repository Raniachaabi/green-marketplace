<?php

namespace App\Notifications;

use App\Models\Listing;
use Illuminate\Notifications\Notification;

class FollowedSellerNewListing extends Notification
{
    public function __construct(private readonly Listing $listing) {}

    public function via(object $notifiable): array
    {
        return ['database'];
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
