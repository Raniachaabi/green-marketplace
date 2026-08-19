<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_it_converts_dinars_to_millimes(): void
    {
        $this->assertSame(170_000, Money::fromDinars(170));
        $this->assertSame(18_500, Money::fromDinars(18.5));
        $this->assertSame(1, Money::fromDinars(0.001));
    }

    /** The classic float trap: 0.1 + 0.2 must not become 0.30000000000000004. */
    public function test_it_rounds_rather_than_truncating(): void
    {
        $this->assertSame(300, Money::fromDinars(0.1) + Money::fromDinars(0.2));
        $this->assertSame(1_005, Money::fromDinars(1.0049));
    }

    public function test_percent_rounds_half_up(): void
    {
        $this->assertSame(5_100, Money::percent(170_000, 3));
    }
}
