<?php

namespace App\Enums;

/**
 * The fixed vocabulary of things a GreenScoreRule can check. Deliberately
 * closed rather than free-text: an admin can turn rules on/off and set
 * points, but cannot invent a new kind of "evidence" without a code change —
 * that is what keeps every point traceable to real data instead of a claim.
 */
enum GreenScoreCheckType: string
{
    case OriginGovernorate = 'origin_governorate';
    case GreenAttribute = 'green_attribute';
    case SellerBadge = 'seller_badge';
    case SmallProducer = 'small_producer';

    public function label(): string
    {
        return __('green_score.check_type.'.$this->value);
    }

    /** Whether this check type needs a `check_value` parameter (a code to look up). */
    public function needsValue(): bool
    {
        return in_array($this, [self::GreenAttribute, self::SellerBadge], true);
    }
}
