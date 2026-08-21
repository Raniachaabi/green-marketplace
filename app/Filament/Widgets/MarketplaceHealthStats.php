<?php

namespace App\Filament\Widgets;

use App\Enums\AvailabilityModel;
use App\Enums\ListingStatus;
use App\Models\Dispute;
use App\Models\Incident;
use App\Models\Listing;
use App\Models\Review;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Phase 2 §22 — things an operator needs to notice today, not vanity
 * counters. Every figure is a live count against this app's own trust &
 * safety tables; nothing here is estimated.
 */
class MarketplaceHealthStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $pendingListings = Listing::where('status', ListingStatus::Pending)->count();

        $threshold = (int) config('marketplace.low_stock_threshold');
        $lowStock = Listing::where('status', ListingStatus::Active)
            ->whereIn('availability_model', [AvailabilityModel::InStock->value, AvailabilityModel::LimitedBatch->value])
            ->where('stock', '>', 0)
            ->where('stock', '<', $threshold)
            ->count();

        $openIncidents = Incident::where('status', 'open')->count();
        $openDisputes = Dispute::where('status', 'open')->count();
        $reportedReviews = Review::whereHas('reports')->count();

        return [
            Stat::make('Listings awaiting review', $pendingListings)
                ->description($pendingListings > 0 ? 'Sellers waiting on you' : 'Nothing pending')
                ->color($pendingListings > 0 ? 'warning' : 'success'),

            Stat::make('Low stock listings', $lowStock)
                ->description('Below the '.$threshold.'-unit threshold')
                ->color($lowStock > 0 ? 'warning' : 'success'),

            Stat::make('Open incidents', $openIncidents)
                ->description('Quality / safety reports')
                ->color($openIncidents > 0 ? 'danger' : 'success'),

            Stat::make('Open disputes', $openDisputes)
                ->description('Buyer/seller order disputes')
                ->color($openDisputes > 0 ? 'danger' : 'success'),

            Stat::make('Reported reviews', $reportedReviews)
                ->description('Flagged by a buyer or seller')
                ->color($reportedReviews > 0 ? 'warning' : 'success'),
        ];
    }
}
