<?php

namespace App\Notifications;

use App\Models\Shipment;
use Illuminate\Notifications\Notification;

class ShipmentUpdated extends Notification
{
    public function __construct(private readonly Shipment $shipment) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message_key' => 'notifications.shipment_updated',
            'replace' => [
                'number' => $this->shipment->order->number,
                'status' => $this->shipment->status->label(),
            ],
            'url' => route('orders.show', $this->shipment->order),
        ];
    }
}
