<?php

namespace App\Notifications;

use App\Models\Listing;
use Illuminate\Notifications\Notification;

class RestockAvailable extends Notification
{
    public function __construct(private readonly Listing $listing) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message_key' => 'notifications.restock_available',
            'replace' => ['title' => $this->listing->name()],
            'url' => route('catalog.show', $this->listing->slug),
        ];
    }
}
