<?php

namespace App\Services\Publishing;

use App\Enums\AvailabilityModel;
use App\Enums\ListingStatus;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Species;
use Illuminate\Support\Collection;

/**
 * The publishing gate.
 *
 * Everything the marketplace promises a buyer is enforced here, once, at the
 * moment a listing tries to go live. Not in a form request (bypassable by the
 * admin panel), not in a controller (bypassable by a console command) — here,
 * so every path into "active" goes through the same rules.
 *
 * Enforces FR-011, FR-014, FR-028, FR-031, FR-033, FR-036 and FR-037.
 */
class PublishingGate
{
    public function check(Listing $listing): GateResult
    {
        $category = $listing->category;

        if (! $category) {
            return GateResult::fromViolations([
                new Violation('no_category', __('publishing.no_category')),
            ]);
        }

        return GateResult::fromViolations(collect()
            ->merge($this->checkBasics($listing))
            ->merge($this->checkCredentials($listing, $category))
            ->merge($this->checkPriceCap($listing, $category))
            ->merge($this->checkRequiredFields($listing, $category))
            ->merge($this->checkCategoryRules($listing, $category))
            ->merge($this->checkSpecies($listing))
        );
    }

    /**
     * Publish if — and only if — every rule passes. Returns the gate result
     * either way so the caller can show the seller exactly what to fix.
     */
    public function publish(Listing $listing): GateResult
    {
        $result = $this->check($listing);

        if ($result->passes()) {
            $listing->forceFill([
                'status' => ListingStatus::Active,
                'status_reason' => null,
                'published_at' => $listing->published_at ?? now(),
                'suspended_at' => null,
            ])->save();

            AuditLog::record('listing.published', $listing, [
                'category' => $listing->category?->slug,
            ]);
        } else {
            // Analytics: this is the event that tells you where supply is
            // being lost. Emit it even though publication failed.
            AuditLog::record('listing.blocked_by_requirement', $listing, [
                'codes' => $result->codes(),
                'violations' => $result->toArray(),
            ]);
        }

        return $result;
    }

    // ------------------------------------------------------------ checks

    private function checkBasics(Listing $listing): Collection
    {
        $violations = collect();

        if (blank($listing->translate('title'))) {
            $violations->push(Violation::missingTitle());
        }

        // FR-035 — at least one image. A listing without a photo does not sell,
        // and an empty-looking catalogue kills the marketplace faster than a
        // missing feature.
        if ($listing->exists && $listing->media()->count() === 0) {
            $violations->push(Violation::missingMedia());
        }

        if ($listing->availability_model === AvailabilityModel::Seasonal
            && (! $listing->season_start || ! $listing->season_end)) {
            $violations->push(Violation::missingSeasonWindow());
        }

        return $violations;
    }

    /**
     * FR-011 / FR-028 / FR-029 — the seller must currently hold every
     * mandatory credential for this category, approved and unexpired.
     *
     * Note the difference between "never provided" and "lapsed": sellers get
     * different messages, because the second is a renewal and the first is an
     * application.
     */
    private function checkCredentials(Listing $listing, Category $category): Collection
    {
        $required = $category->mandatoryCredentialCodes();

        if ($required->isEmpty()) {
            return collect();
        }

        $seller = $listing->seller();

        if (! $seller) {
            return collect([new Violation('no_seller', __('publishing.no_seller'))]);
        }

        $usable = $seller->approvedCredentialCodes();

        // Everything they hold at all, regardless of state — used to tell
        // "expired" apart from "never submitted".
        $held = $seller->credentials()->pluck('credential_type_code')->unique();

        $requirements = $category->effectiveRequirements()->keyBy('credential_type_code');

        return $required
            ->reject(fn (string $code) => $usable->contains($code))
            ->map(function (string $code) use ($held, $requirements) {
                $name = $requirements->get($code)?->credentialType?->name() ?? $code;

                return $held->contains($code)
                    ? Violation::expiredCredential($code, $name)
                    : Violation::missingCredential($code, $name);
            })
            ->values();
    }

    /** FR-014 / LC-02 — state-fixed ceilings are enforced, not suggested. */
    private function checkPriceCap(Listing $listing, Category $category): Collection
    {
        $cap = $category->activePriceCap();

        if (! $cap) {
            return collect();
        }

        $underContract = (bool) $listing->attr('under_exploitation_contract', false);
        $ceiling = $cap->ceilingFor($underContract);

        if ((int) $listing->price > $ceiling) {
            return collect([Violation::priceAboveCap((int) $listing->price, $ceiling, $cap->unit)]);
        }

        return collect();
    }

    /** FR-031 — required category fields block publication when empty. */
    private function checkRequiredFields(Listing $listing, Category $category): Collection
    {
        $values = $listing->attribute_values ?? [];

        return $category->effectiveFields()
            ->where('required', true)
            ->filter(fn ($field) => blank($values[$field->key] ?? null))
            ->map(fn ($field) => Violation::missingField($field->key, $field->name()))
            ->values();
    }

    /**
     * FR-036 — a lot number on every edible listing.
     *
     * This is one string column and it is the difference between a targeted
     * recall and a catastrophe. The category rule decides where it applies,
     * so extending it to cosmetics later is a config change.
     */
    private function checkCategoryRules(Listing $listing, Category $category): Collection
    {
        $rule = $category->effectiveRule();

        if ($rule?->requires_lot_number && blank($listing->lot_number)) {
            return collect([Violation::missingLotNumber()]);
        }

        return collect();
    }

    /** FR-037 — an invasive species cannot be sold, whatever the paperwork. */
    private function checkSpecies(Listing $listing): Collection
    {
        if (! $listing->exists) {
            return collect();
        }

        return $listing->species()
            ->where('is_invasive_blocked', true)
            ->get()
            ->map(fn (Species $s) => Violation::invasiveSpecies($s->botanical_name))
            ->values();
    }
}
