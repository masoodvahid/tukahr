<?php

namespace Tests\Unit;

use App\Support\JalaliDate;
use PHPUnit\Framework\TestCase;

class JalaliDateTest extends TestCase
{
    public function test_known_jalali_date_converts_to_gregorian(): void
    {
        $this->assertSame([2026, 10, 7], JalaliDate::toGregorian(1405, 7, 15));
    }

    public function test_known_gregorian_date_converts_to_jalali(): void
    {
        $this->assertSame([1405, 6, 19], JalaliDate::fromGregorian(2026, 9, 10));
        $this->assertSame([1405, 1, 1], JalaliDate::fromGregorian(2026, 3, 21));
    }

    public function test_deadline_is_start_of_day_15_next_jalali_month(): void
    {
        $deadline = JalaliDate::editDeadline(1405, 6);

        $this->assertSame('2026-10-06 20:30:00', $deadline->format('Y-m-d H:i:s'));
    }
}
