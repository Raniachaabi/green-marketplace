<?php

namespace App\Observers;

use App\Models\Order;
use App\Notifications\OrderStatusUpdated;

class OrderObserver
{
    public function updated(Order $order): void
    {
        if (! $order->wasChanged('status') || ! $order->buyer) {
            return;
        }

        $order->buyer->notify(new OrderStatusUpdated($order));
    }
}
