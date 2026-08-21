<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\OrderLine;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Buyer reviews, tied to a specific delivered order line.
 *
 * Requiring an order_line_id (rather than letting anyone review any
 * listing) is deliberate: every review on the site is from a verified
 * purchase, which is worth more to a buyer than an open comment box.
 */
class ReviewController extends Controller
{
    public function store(Request $request, OrderLine $orderLine): RedirectResponse
    {
        $order = $orderLine->order;

        abort_unless($order->buyer_user_id === $request->user()->id, 403);
        abort_unless($order->status === OrderStatus::Delivered, 422);

        if (Review::where('order_line_id', $orderLine->id)->exists()) {
            return back()->withErrors(['review' => __('order.review_already_submitted')]);
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['nullable', 'string', 'max:2000'],
        ]);

        Review::create([
            'author_user_id' => $request->user()->id,
            'order_line_id' => $orderLine->id,
            'target_type' => 'listing',
            'target_id' => $orderLine->listing_id,
            'rating' => $data['rating'],
            'body' => $data['body'] ?? null,
        ]);

        return back()->with('status', __('order.review_submitted'));
    }
}
