<?php

namespace App\Services;

use App\Models\Category;
use App\Models\DeliveryZone;
use Illuminate\Support\Collection;

/**
 * FR-090 / FR-094 — delivery cost and feasibility.
 *
 * The feasibility half matters more than the price half: a perishable good
 * must not be offered into a governorate the carrier cannot reach inside the
 * category's max_delivery_days. Better to hide the option than to ship
 * something that arrives spoiled and generates a dispute.
 */
class DeliveryQuoter
{
    /** @return Collection<int, array{zone: DeliveryZone, price: int, days: int}> */
    public function optionsFor(string $governorate, Collection $categories): Collection
    {
        $maxDays = $this->tightestDeliveryWindow($categories);

        return DeliveryZone::with('carrier')
            ->where('governorate', $governorate)
            ->whereHas('carrier', fn ($q) => $q->where('is_active', true))
            ->get()
            ->filter(fn (DeliveryZone $zone) => $maxDays === null || $zone->lead_time_days <= $maxDays)
            ->map(fn (DeliveryZone $zone) => [
                'zone' => $zone,
                'price' => (int) $zone->base_price,
                'days' => (int) $zone->lead_time_days,
            ])
            ->sortBy('price')
            ->values();
    }

    public function cheapest(string $governorate, Collection $categories): ?array
    {
        return $this->optionsFor($governorate, $categories)->first();
    }

    /** The strictest max_delivery_days across every category in the basket. */
    private function tightestDeliveryWindow(Collection $categories): ?int
    {
        return $categories
            ->map(fn (Category $c) => $c->effectiveRule()?->max_delivery_days)
            ->filter()
            ->min();
    }

    /** Handling flags the carrier needs, unioned across the basket. */
    public function handlingFlags(Collection $categories): array
    {
        return $categories
            ->flatMap(fn (Category $c) => $c->effectiveRule()?->handlingFlags() ?? [])
            ->unique()
            ->values()
            ->all();
    }
}
