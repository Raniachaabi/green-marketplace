<?php

namespace App\Support;

use NumberFormatter;

/**
 * TND is a three-decimal currency: 1 dinar = 1000 millimes.
 *
 * Every monetary value in this codebase is an integer number of millimes.
 * Never introduce a float for money — 0.1 + 0.2 problems in a marketplace
 * become reconciliation problems, and reconciliation problems become
 * seller disputes.
 */
final class Money
{
    public const SCALE = 1000;

    public static function fromDinars(float|string $dinars): int
    {
        return (int) round(((float) $dinars) * self::SCALE);
    }

    public static function toDinars(int $millimes): float
    {
        return $millimes / self::SCALE;
    }

    public static function format(int $millimes, ?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        $amount = number_format(self::toDinars($millimes), 3, ',', ' ');

        return match ($locale) {
            'ar' => $amount.' د.ت',
            default => $amount.' TND',
        };
    }

    /** Apply a percentage and round half-up to the nearest millime. */
    public static function percent(int $millimes, float $pct): int
    {
        return (int) round($millimes * $pct / 100);
    }
}
