<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
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
            'order' => $order->load(['lines.listing', 'shipments', 'payments', 'documents']),
        ]);
    }
}
