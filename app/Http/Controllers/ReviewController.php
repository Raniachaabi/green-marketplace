<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Listing;
use App\Models\OrderLine;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\User;
use App\Notifications\ReviewReceived;
use App\Notifications\ReviewReported;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

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

        $review = Review::create([
            'author_user_id' => $request->user()->id,
            'order_line_id' => $orderLine->id,
            'target_type' => 'listing',
            'target_id' => $orderLine->listing_id,
            'rating' => $data['rating'],
            'body' => $data['body'] ?? null,
        ]);

        $orderLine->listing?->sellerUser?->notify(new ReviewReceived($review->fresh('orderLine.listing')));

        return back()->with('status', __('order.review_submitted'));
    }

    /** Phase 2 §16 — a seller may respond, once, to a review on their own listing. */
    public function respond(Request $request, Review $review): RedirectResponse
    {
        abort_unless($review->target_type === 'listing', 404);

        $listing = Listing::findOrFail($review->target_id);

        abort_unless($this->ownsListing($request->user(), $listing), 403);

        $data = $request->validate(['seller_response' => ['required', 'string', 'max:2000']]);

        $review->forceFill([
            'seller_response' => $data['seller_response'],
            'seller_response_at' => now(),
        ])->save();

        return back()->with('status', __('order.review_response_saved'));
    }

    /** Any signed-in buyer may flag a review once. */
    public function report(Request $request, Review $review): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'in:spam,offensive,fake,other'],
        ]);

        $report = ReviewReport::firstOrCreate(
            ['review_id' => $review->id, 'reporter_user_id' => $request->user()->id],
            ['reason' => $data['reason']],
        );

        if ($report->wasRecentlyCreated) {
            Notification::send(User::where('is_admin', true)->get(), new ReviewReported($report));
        }

        return back()->with('status', __('order.review_reported'));
    }

    private function ownsListing(User $user, Listing $listing): bool
    {
        if ($listing->seller_user_id === $user->id) {
            return true;
        }

        return $listing->seller_org_id
            && $user->organizations()->where('organizations.id', $listing->seller_org_id)->exists();
    }
}
