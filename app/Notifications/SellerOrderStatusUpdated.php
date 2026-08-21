<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Notification;

/** The seller side of OrderStatusUpdated — same event, seller-facing wording and route. */
class SellerOrderStatusUpdated extends Notification
{
    public function __construct(private readonly Order $order) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message_key' => 'notifications.seller_order_status_changed',
            'replace' => ['number' => $this->order->number, 'status' => $this->order->status->label()],
            'url' => route('seller.orders.show', $this->order),
        ];
    }
}
