<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Services\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private readonly Cart $cart) {}

    public function show(): View
    {
        return view('cart.show', [
            'lines' => $this->cart->lines(),
            'subtotal' => $this->cart->subtotal(),
        ]);
    }

    public function add(Request $request, Listing $listing): RedirectResponse
    {
        $validated = $request->validate([
            'qty' => ['nullable', 'numeric', 'min:0.001'],
        ]);

        abort_unless($listing->isPurchasable() && $listing->isInSeason(), 422);

        // Services and experiences are booked, not carted (FR-015).
        abort_unless($listing->category->listing_type->usesCart(), 422);

        $this->cart->add($listing, (float) ($validated['qty'] ?? $listing->min_order_qty));

        return back()->with('status', __('cart.added'));
    }

    public function update(Request $request, string $listingId): RedirectResponse
    {
        $validated = $request->validate([
            'qty' => ['required', 'numeric', 'min:0'],
        ]);

        $this->cart->set($listingId, (float) $validated['qty']);

        return back()->with('status', __('cart.updated'));
    }

    public function remove(string $listingId): RedirectResponse
    {
        $this->cart->remove($listingId);

        return back()->with('status', __('cart.removed'));
    }
}
