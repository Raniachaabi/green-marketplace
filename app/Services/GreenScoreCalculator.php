<?php

namespace App\Services;

use App\Enums\GreenScoreCheckType;
use App\Models\GreenScoreRule;
use App\Models\Listing;
use App\Models\Organization;
use App\Support\GreenScoreResult;

/**
 * Scores a listing against the active GreenScoreRule set. Every rule checks
 * one real, already-verified piece of data (a green attribute the seller
 * attached, a badge earned through the credential pipeline, a known
 * governorate) — nothing here is inferred or guessed.
 *
 * Deliberately scoped to a single listing at a time (the product page), not
 * a catalogue grid: SellerBadge checks touch the credentials table, and
 * running that per card on a 24-listing results page is the N+1 this
 * service is built to avoid rather than cause.
 */
class GreenScoreCalculator
{
    public function scoreFor(Listing $listing): GreenScoreResult
    {
        $rules = GreenScoreRule::active()->orderBy('display_order')->get();
        $seller = $listing->seller();

        $breakdown = $rules
            ->filter(fn (GreenScoreRule $rule) => $this->earns($rule, $listing, $seller))
            ->map(fn (GreenScoreRule $rule) => ['rule' => $rule, 'points' => $rule->points])
            ->values();

        $total = min(100, (int) $breakdown->sum('points'));

        return new GreenScoreResult($total, $breakdown);
    }

    private function earns(GreenScoreRule $rule, Listing $listing, mixed $seller): bool
    {
        return match ($rule->check_type) {
            GreenScoreCheckType::OriginGovernorate => filled($listing->governorate),

            GreenScoreCheckType::GreenAttribute => $listing->relationLoaded('greenAttributes')
                ? $listing->greenAttributes->contains('code', $rule->check_value)
                : $listing->greenAttributes()->where('code', $rule->check_value)->exists(),

            GreenScoreCheckType::SellerBadge => $seller
                ? $seller->activeBadges()->contains('code', $rule->check_value)
                : false,

            GreenScoreCheckType::SmallProducer => $seller instanceof Organization
                ? in_array($seller->type, ['association', 'gda', 'smsa'], true)
                : $seller !== null,
        };
    }
}
