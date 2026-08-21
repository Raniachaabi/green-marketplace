<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderStatusUpdated;
use App\Notifications\SellerOrderStatusUpdated;
use Illuminate\Support\Facades\Notification;

class OrderObserver
{
    public function updated(Order $order): void
    {
        if (! $order->wasChanged('status')) {
            return;
        }

        $order->buyer?->notify(new OrderStatusUpdated($order));

        $sellerIds = $order->lines()->whereNotNull('seller_user_id')->distinct()->pluck('seller_user_id');

        if ($sellerIds->isNotEmpty()) {
            Notification::send(User::whereIn('id', $sellerIds)->get(), new SellerOrderStatusUpdated($order));
        }
    }
}
