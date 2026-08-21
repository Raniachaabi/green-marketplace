<?php

namespace App\Notifications;

use App\Models\OrderLine;
use Illuminate\Notifications\Notification;

class ReviewReminder extends Notification
{
    public function __construct(private readonly OrderLine $orderLine) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message_key' => 'notifications.review_reminder',
            'replace' => ['title' => $this->orderLine->title()],
            'url' => route('orders.show', $this->orderLine->order_id),
        ];
    }
}
