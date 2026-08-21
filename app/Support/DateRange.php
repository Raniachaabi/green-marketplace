<?php

namespace App\Support;

use Carbon\Carbon;

/** A small, explicit value object for the seller analytics time filter — never a magic array. */
final class DateRange
{
    public function __construct(
        public readonly Carbon $from,
        public readonly Carbon $to,
        public readonly string $key,
    ) {}

    public static function fromKey(string $key, ?string $customFrom = null, ?string $customTo = null): self
    {
        $to = now()->endOfDay();

        $from = match ($key) {
            'today' => now()->startOfDay(),
            '7d' => now()->subDays(6)->startOfDay(),
            '30d' => now()->subDays(29)->startOfDay(),
            '3m' => now()->subMonths(3)->startOfDay(),
            '12m' => now()->subMonths(12)->startOfDay(),
            'custom' => $customFrom ? Carbon::parse($customFrom)->startOfDay() : now()->subDays(29)->startOfDay(),
            default => now()->subDays(29)->startOfDay(),
        };

        if ($key === 'custom' && $customTo) {
            $to = Carbon::parse($customTo)->endOfDay();
        }

        return new self($from, $to, in_array($key, ['today', '7d', '30d', '3m', '12m', 'custom'], true) ? $key : '30d');
    }
}
