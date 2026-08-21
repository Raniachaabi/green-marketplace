<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\OrderLine;
use App\Models\User;
use App\Support\Money;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Phase 2 §22 — who is actually driving the marketplace right now, from
 * real orders and real follows only (no fabricated engagement score).
 */
class SellerActivityStats extends BaseWidget
{
    protected static ?int $sort = 2;

    private const REVENUE_STATUSES = [
        OrderStatus::Pending, OrderStatus::Confirmed, OrderStatus::Shipped, OrderStatus::Delivered,
    ];

    protected function getStats(): array
    {
        $newSellers = User::whereHas('roles', fn ($q) => $q->where('role', 'seller'))
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $mostFollowed = User::withCount('followers')
            ->orderByDesc('followers_count')
            ->first();

        $topRevenueRow = OrderLine::query()
            ->whereHas('order', fn ($q) => $q->where('placed_at', '>=', now()->startOfMonth())
                ->whereIn('status', array_map(fn ($s) => $s->value, self::REVENUE_STATUSES)))
            ->selectRaw('seller_user_id, sum(line_total) as aggregate')
            ->groupBy('seller_user_id')
            ->orderByDesc('aggregate')
            ->first();

        $topSeller = $topRevenueRow ? User::find($topRevenueRow->seller_user_id) : null;

        return [
            Stat::make('New sellers (30d)', $newSellers)
                ->description('Joined and listed in the last month'),

            Stat::make('Most-followed seller', $mostFollowed?->full_name ?? '—')
                ->description($mostFollowed ? $mostFollowed->followers_count.' followers' : 'No follows yet'),

            Stat::make('Top seller this month', $topSeller?->full_name ?? '—')
                ->description($topRevenueRow ? Money::format((int) $topRevenueRow->aggregate).' revenue' : 'No orders yet'),
        ];
    }
}
