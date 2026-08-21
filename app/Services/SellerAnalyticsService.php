<?php

namespace App\Services;

use App\Enums\ListingStatus;
use App\Enums\OrderStatus;
use App\Models\Listing;
use App\Models\OrderLine;
use App\Models\Review;
use App\Models\User;
use App\Support\DateRange;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Phase 2 §2 — every number here traces to a real query against this
 * seller's own rows. Nothing is estimated or invented; a metric that would
 * need tracking data this app doesn't have (e.g. a true session-based
 * conversion rate) is either omitted or explicitly computed from what
 * views/orders data actually exists, never guessed.
 */
class SellerAnalyticsService
{
    /** Orders in a state that represents real, counted revenue. */
    private const REVENUE_STATUSES = [
        OrderStatus::Pending, OrderStatus::Confirmed, OrderStatus::Shipped, OrderStatus::Delivered,
    ];

    public function report(User $seller, DateRange $range): array
    {
        $lines = OrderLine::query()
            ->where('seller_user_id', $seller->id)
            ->whereHas('order', fn ($q) => $q->whereBetween('placed_at', [$range->from, $range->to])
                ->whereIn('status', self::REVENUE_STATUSES))
            ->get(['id', 'order_id', 'listing_id', 'title_snapshot', 'qty', 'line_total']);

        $revenue = (int) $lines->sum('line_total');
        $ordersCount = $lines->pluck('order_id')->unique()->count();

        $listings = Listing::where('seller_user_id', $seller->id)->get(['id', 'title', 'status', 'stock', 'availability_model', 'view_count']);
        $totalViews = (int) $listings->sum('view_count');

        $bySeller = $lines->groupBy('listing_id');

        $bestSelling = $bySeller
            ->map(fn (Collection $group, string $listingId) => [
                'listing' => $listings->firstWhere('id', $listingId),
                'qty' => (float) $group->sum('qty'),
                'revenue' => (int) $group->sum('line_total'),
            ])
            ->filter(fn ($row) => $row['listing'] !== null)
            ->sortByDesc('revenue')
            ->take(5)
            ->values();

        $soldListingIds = $bySeller->keys();
        $lowPerforming = $listings
            ->where('status', ListingStatus::Active)
            ->reject(fn (Listing $l) => $soldListingIds->contains($l->id))
            ->sortByDesc('view_count')
            ->take(5)
            ->values();

        $threshold = (int) config('marketplace.low_stock_threshold');
        $inventory = [
            'in_stock' => $listings->filter(fn ($l) => $l->availability_model->tracksStock() && $l->stock >= $threshold)->count(),
            'low_stock' => $listings->filter(fn ($l) => $l->availability_model->tracksStock() && $l->stock > 0 && $l->stock < $threshold)->count(),
            'out_of_stock' => $listings->filter(fn ($l) => $l->availability_model->tracksStock() && $l->stock <= 0)->count(),
        ];

        $listingIds = $listings->pluck('id');
        $reviews = Review::where('target_type', 'listing')->whereIn('target_id', $listingIds);

        return [
            'revenue' => $revenue,
            'ordersCount' => $ordersCount,
            'aov' => $ordersCount > 0 ? (int) round($revenue / $ordersCount) : 0,
            'totalProducts' => $listings->count(),
            'activeProducts' => $listings->where('status', ListingStatus::Active)->count(),
            'totalViews' => $totalViews,
            // A real ratio of this seller's own recorded views to completed
            // orders in the range — an aggregate proxy, not a per-session
            // funnel (this app has no session-level view tracking).
            'conversionRate' => $totalViews > 0 ? round($ordersCount / $totalViews * 100, 1) : null,
            'bestSelling' => $bestSelling,
            'lowPerforming' => $lowPerforming,
            'inventory' => $inventory,
            'reviewCount' => (clone $reviews)->count(),
            'averageRating' => (clone $reviews)->avg('rating') ? round((clone $reviews)->avg('rating'), 1) : null,
            'dailySeries' => $this->dailySeries($seller, $range),
        ];
    }

    /** Bucketed revenue for the simple bar chart — by day for short ranges, by month for long ones. */
    private function dailySeries(User $seller, DateRange $range): Collection
    {
        $byMonth = $range->from->diffInDays($range->to) > 62;

        $bucket = match (DB::getDriverName()) {
            'sqlite' => $byMonth ? "strftime('%Y-%m', orders.placed_at)" : "strftime('%Y-%m-%d', orders.placed_at)",
            default => $byMonth ? "to_char(orders.placed_at, 'YYYY-MM')" : "to_char(orders.placed_at, 'YYYY-MM-DD')",
        };

        $rows = OrderLine::query()
            ->where('order_lines.seller_user_id', $seller->id)
            ->join('orders', 'orders.id', '=', 'order_lines.order_id')
            ->whereBetween('orders.placed_at', [$range->from, $range->to])
            ->whereIn('orders.status', array_map(fn ($s) => $s->value, self::REVENUE_STATUSES))
            ->selectRaw("{$bucket} as bucket, sum(order_lines.line_total) as total")
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        return collect($rows);
    }
}
