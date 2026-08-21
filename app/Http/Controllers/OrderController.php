<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private readonly Cart $cart) {}

    public function index(Request $request): View
    {
        return view('orders.index', [
            'orders' => $request->user()->orders()
                ->with(['lines', 'shipments'])
                ->latest('placed_at')
                ->paginate(15),
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        abort_unless($order->buyer_user_id === $request->user()->id, 403);

        return view('orders.show', [
            'order' => $order->load(['lines.listing.media', 'lines.review', 'shipments.carrier', 'payments', 'documents', 'address']),
        ]);
    }

    /** Re-add every line from a past order to the cart, skipping what no longer sells. */
    public function reorder(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->buyer_user_id === $request->user()->id, 403);

        $order->load('lines.listing');

        $added = 0;

        foreach ($order->lines as $line) {
            $listing = $line->listing;

            if ($listing && $listing->isPurchasable() && $listing->isInSeason()) {
                $this->cart->add($listing, (float) $line->qty);
                $added++;
            }
        }

        if ($added === 0) {
            return back()->withErrors(['reorder' => __('order.reorder_none_available')]);
        }

        return redirect()->route('cart.show')->with('status', __('order.reorder_added'));
    }
}
