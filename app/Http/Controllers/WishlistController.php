<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Wishlist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function index(Request $request): View
    {
        return view('wishlist.index', [
            'listings' => $request->user()->wishlist()
                ->with(['listing.category', 'listing.media', 'listing.sellerUser', 'listing.sellerOrg', 'listing.greenAttributes'])
                ->latest()
                ->paginate(12)
                ->through(fn (Wishlist $w) => $w->listing)
                ->withQueryString(),
        ]);
    }

    public function store(Request $request, Listing $listing): RedirectResponse
    {
        $request->user()->wishlist()->firstOrCreate(['listing_id' => $listing->id]);

        return back()->with('status', __('wishlist.added'));
    }

    public function destroy(Request $request, Listing $listing): RedirectResponse
    {
        $request->user()->wishlist()->where('listing_id', $listing->id)->delete();

        return back()->with('status', __('wishlist.removed'));
    }
}
