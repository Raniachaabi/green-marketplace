<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\User;
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
    public function show(User $user): View
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

        return view('seller.storefront', [
            'seller' => $user,
            'listings' => $listings,
            'badges' => $user->activeBadges(),
            'ratingAverage' => round((float) Review::where('target_type', 'listing')->whereIn('target_id', $listingIds)->avg('rating'), 1),
            'ratingCount' => Review::where('target_type', 'listing')->whereIn('target_id', $listingIds)->count(),
        ]);
    }
}
