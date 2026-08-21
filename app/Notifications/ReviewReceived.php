<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Notifications\Notification;

class ReviewReceived extends Notification
{
    public function __construct(private readonly Review $review) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message_key' => 'notifications.review_received',
            'replace' => ['rating' => $this->review->rating],
            'url' => route('catalog.show', $this->review->orderLine->listing->slug),
        ];
    }
}
