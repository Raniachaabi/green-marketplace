<?php

namespace App\Support;

use App\Models\GreenScoreRule;
use Illuminate\Support\Collection;

/**
 * A listing's Green Score, broken down by the rules that actually earned it.
 * `$breakdown` holds only earned factors — this is what renders as the
 * explanation under the score, so the number is never a black box.
 */
final class GreenScoreResult
{
    /**
     * @param  Collection<int, array{rule: GreenScoreRule, points: int}>  $breakdown
     */
    public function __construct(
        public readonly int $total,
        public readonly Collection $breakdown,
    ) {}

    public function isEmpty(): bool
    {
        return $this->breakdown->isEmpty();
    }
}
