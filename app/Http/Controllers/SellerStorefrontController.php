<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public seller storefront.
 *
 * Only what a buyer needs to decide whether to trust this seller: name,
 * bio, badges, rating, and their live catalogue. Never credentials
 * (private disk, never linked from here), never contact details the
 * seller hasn't chosen to publish, never draft/pending/rejected/
 * suspended listings — those stay in the seller's own dashboard.
 */
class SellerStorefrontController extends Controller
{
    public function show(Request $request, User $user): View
    {
        // A user with no listings at all has never been a seller — nothing
        // to show, and no reason to expose an arbitrary buyer's profile page.
        abort_unless($user->listings()->exists(), 404);

        $listings = $user->listings()->active()
            ->with(['media', 'category', 'sellerUser', 'sellerOrg', 'greenAttributes'])
            ->withCount('reviews')->withAvg('reviews', 'rating')
            ->latest('published_at')
            ->paginate(12);

        $listingIds = $user->listings()->pluck('id');

        $reviews = Review::where('target_type', 'listing')
            ->whereIn('target_id', $listingIds)
            ->with('author')
            ->latest()
            ->paginate(10, ['*'], 'reviews_page');

        return view('seller.storefront', [
            'seller' => $user,
            'listings' => $listings,
            'reviews' => $reviews,
            'reviewedListings' => $user->listings()->get(['id', 'title'])->keyBy('id'),
            'badges' => $user->activeBadges(),
            'ratingAverage' => round((float) Review::where('target_type', 'listing')->whereIn('target_id', $listingIds)->avg('rating'), 1),
            'ratingCount' => Review::where('target_type', 'listing')->whereIn('target_id', $listingIds)->count(),
            'followerCount' => $user->followerCount(),
            'isVerified' => $user->isVerifiedSeller(),
            'isFollowing' => $request->user()?->isFollowing($user->id) ?? false,
        ]);
    }
}
