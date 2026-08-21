<?php

namespace App\Observers;

use App\Models\Shipment;
use App\Notifications\ShipmentUpdated;

/**
 * Phase 2 §1 — "shipment updates" for the buyer. Hooked here (not in
 * SellerOrderController nor the admin ShipmentsRelationManager) so neither
 * path can forget to tell the buyer — the same reasoning as ListingObserver.
 */
class ShipmentObserver
{
    public function updated(Shipment $shipment): void
    {
        if (! $shipment->wasChanged('status')) {
            return;
        }

        $buyer = $shipment->order?->buyer;
        $buyer?->notify(new ShipmentUpdated($shipment));
    }
}
