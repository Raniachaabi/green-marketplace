<?php

namespace App\Http\Controllers;

use App\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The seller's own sales — a seller sees only orders that contain at least
 * one of their listings, and within that order only their own lines and
 * shipment. Nothing here ever exposes another seller's prices or an
 * order's grand total, which would leak revenue across sellers sharing a
 * multi-seller order.
 */
class SellerOrderController extends Controller
{
    public function index(Request $request): View
    {
        $sellerId = $request->user()->id;

        $orders = Order::whereHas('lines', fn ($q) => $q->where('seller_user_id', $sellerId))
            ->with([
                'lines' => fn ($q) => $q->where('seller_user_id', $sellerId),
                'shipments' => fn ($q) => $q->where('seller_user_id', $sellerId),
            ])
            ->latest('placed_at')
            ->paginate(15);

        return view('seller.orders.index', ['orders' => $orders]);
    }

    public function show(Request $request, Order $order): View
    {
        $sellerId = $request->user()->id;

        abort_unless($order->lines()->where('seller_user_id', $sellerId)->exists(), 403);

        $order->load([
            'buyer',
            'address',
            'lines' => fn ($q) => $q->where('seller_user_id', $sellerId)->with('listing.media'),
            'shipments' => fn ($q) => $q->where('seller_user_id', $sellerId),
        ]);

        return view('seller.orders.show', ['order' => $order]);
    }

    public function updateShipment(Request $request, Order $order, Shipment $shipment): RedirectResponse
    {
        abort_unless($shipment->seller_user_id === $request->user()->id, 403);
        abort_unless($shipment->order_id === $order->id, 404);

        $data = $request->validate([
            'status' => ['required', Rule::enum(ShipmentStatus::class)],
        ]);

        $status = ShipmentStatus::from($data['status']);

        $shipment->forceFill(array_filter([
            'status' => $status,
            'picked_up_at' => $status === ShipmentStatus::PickedUp ? now() : $shipment->picked_up_at,
            'delivered_at' => $status === ShipmentStatus::Delivered ? now() : $shipment->delivered_at,
        ]))->save();

        return back()->with('status', __('seller.shipment_updated'));
    }
}
