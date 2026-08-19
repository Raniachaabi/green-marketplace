<?php

namespace App\Services;

use App\Models\Listing;
use App\Support\Money;
use Illuminate\Support\Collection;

/**
 * Session-backed cart.
 *
 * Deliberately stores only listing id and quantity — never the price. Prices
 * are re-read from the database on every render, so a cart left open for a
 * week cannot be used to buy at last week's price.
 */
class Cart
{
    private const KEY = 'cart.items';

    public function items(): Collection
    {
        return collect(session(self::KEY, []));
    }

    public function add(Listing $listing, float $qty = 1): void
    {
        $items = $this->items()->all();
        $current = (float) ($items[$listing->id] ?? 0);

        $items[$listing->id] = max($listing->min_order_qty, $current + $qty);

        session([self::KEY => $items]);
    }

    public function set(string $listingId, float $qty): void
    {
        $items = $this->items()->all();

        if ($qty <= 0) {
            unset($items[$listingId]);
        } else {
            $items[$listingId] = $qty;
        }

        session([self::KEY => $items]);
    }

    public function remove(string $listingId): void
    {
        $items = $this->items()->all();
        unset($items[$listingId]);
        session([self::KEY => $items]);
    }

    public function clear(): void
    {
        session()->forget(self::KEY);
    }

    public function isEmpty(): bool
    {
        return $this->items()->isEmpty();
    }

    public function count(): int
    {
        return $this->items()->count();
    }

    /**
     * Hydrated lines with live prices. Listings that have since been
     * suspended, sold out or gone out of season are returned flagged rather
     * than silently dropped — the buyer should be told what changed.
     */
    public function lines(): Collection
    {
        $items = $this->items();

        if ($items->isEmpty()) {
            return collect();
        }

        $listings = Listing::with(['category', 'media', 'sellerUser', 'sellerOrg'])
            ->whereIn('id', $items->keys())
            ->get()
            ->keyBy('id');

        return $items->map(function ($qty, $listingId) use ($listings) {
            $listing = $listings->get($listingId);

            if (! $listing) {
                return null;
            }

            $qty = (float) $qty;
            $available = $listing->isPurchasable() && $listing->isInSeason();

            return [
                'listing' => $listing,
                'qty' => $qty,
                'unit_price' => (int) $listing->price,
                'line_total' => (int) round($listing->price * $qty),
                'available' => $available,
                'reason' => $available ? null : $this->unavailableReason($listing),
            ];
        })->filter()->values();
    }

    public function subtotal(): int
    {
        return (int) $this->lines()
            ->where('available', true)
            ->sum('line_total');
    }

    public function formattedSubtotal(): string
    {
        return Money::format($this->subtotal());
    }

    public function hasUnavailableLines(): bool
    {
        return $this->lines()->contains('available', false);
    }

    private function unavailableReason(Listing $listing): string
    {
        if (! $listing->isInSeason()) {
            return __('cart.out_of_season');
        }

        if ($listing->availability_model->tracksStock() && $listing->stock <= 0) {
            return __('cart.out_of_stock');
        }

        return __('cart.unavailable');
    }
}
