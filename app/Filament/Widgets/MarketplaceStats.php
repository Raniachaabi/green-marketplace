<?php

namespace App\Filament\Widgets;

use App\Enums\CredentialStatus;
use App\Enums\ListingStatus;
use App\Models\Credential;
use App\Models\Listing;
use App\Models\Order;
use App\Models\User;
use App\Support\Money;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * The metrics from PRD §2.3 that actually decide whether this is working.
 * Deliberately not vanity metrics — badge compliance and pending queue depth
 * are operational alarms, not dashboard decoration.
 */
class MarketplaceStats extends BaseWidget
{
    protected function getStats(): array
    {
        $lapsedButLive = Credential::where('status', CredentialStatus::Approved)
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<', now())
            ->count();

        return [
            Stat::make('Verified sellers', User::whereHas('credentials',
                fn ($q) => $q->where('status', CredentialStatus::Approved))->count())
                ->description('Target: 60 by month 3')
                ->color('success'),

            Stat::make('Live listings', Listing::where('status', ListingStatus::Active)->count())
                ->description('Target: 400 by month 3'),

            Stat::make('Orders this week', Order::where('placed_at', '>=', now()->subWeek())->count())
                ->description('Target: 80/week'),

            Stat::make('GMV this month', Money::format(
                (int) Order::where('placed_at', '>=', now()->startOfMonth())->sum('total')
            )),

            Stat::make('Verification queue', Credential::where('status', CredentialStatus::Pending)->count())
                ->description('Sellers waiting on you')
                ->color('warning'),

            Stat::make('Lapsed credentials still approved', $lapsedButLive)
                ->description($lapsedButLive > 0 ? 'Run credentials:check-expiry' : 'Clean')
                ->color($lapsedButLive > 0 ? 'danger' : 'success'),
        ];
    }
}
