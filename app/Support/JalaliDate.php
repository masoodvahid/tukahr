<?php

namespace App\Support;

use Carbon\CarbonImmutable;

final class JalaliDate
{
    public static function toGregorian(int $jy, int $jm, int $jd): array
    {
        $jy += 1595;
        $days = -355668 + (365 * $jy) + intdiv($jy, 33) * 8 + intdiv(($jy % 33) + 3, 4) + $jd
            + (($jm < 7) ? ($jm - 1) * 31 : (($jm - 7) * 30) + 186);

        $gy = 400 * intdiv($days, 146097);
        $days %= 146097;

        if ($days > 36524) {
            $gy += 100 * intdiv(--$days, 36524);
            $days %= 36524;
            if ($days >= 365) {
                $days++;
            }
        }

        $gy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $gy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        $gd = $days + 1;
        $leap = ($gy % 4 === 0 && $gy % 100 !== 0) || ($gy % 400 === 0);
        $months = [0, 31, $leap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

        for ($gm = 1; $gm <= 12 && $gd > $months[$gm]; $gm++) {
            $gd -= $months[$gm];
        }

        return [$gy, $gm, $gd];
    }

    public static function fromGregorian(int $gy, int $gm, int $gd): array
    {
        $monthDays = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];

        if ($gy > 1600) {
            $jy = 979;
            $gy -= 1600;
        } else {
            $jy = 0;
            $gy -= 621;
        }

        $gy2 = $gm > 2 ? $gy + 1 : $gy;
        $days = (365 * $gy)
            + intdiv($gy2 + 3, 4)
            - intdiv($gy2 + 99, 100)
            + intdiv($gy2 + 399, 400)
            - 80
            + $gd
            + $monthDays[$gm - 1];

        $jy += 33 * intdiv($days, 12053);
        $days %= 12053;

        $jy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $jy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        if ($days < 186) {
            $jm = 1 + intdiv($days, 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + intdiv($days - 186, 30);
            $jd = 1 + (($days - 186) % 30);
        }

        return [$jy, $jm, $jd];
    }

    public static function today(string $tz = 'Asia/Tehran'): array
    {
        $today = CarbonImmutable::now($tz);

        return self::fromGregorian($today->year, $today->month, $today->day);
    }

    public static function currentYear(string $tz = 'Asia/Tehran'): int
    {
        return self::today($tz)[0];
    }

    public static function currentMonth(string $tz = 'Asia/Tehran'): int
    {
        return self::today($tz)[1];
    }

    public static function editDeadline(int $jy, int $jm, string $tz = 'Asia/Tehran'): CarbonImmutable
    {
        $ny = $jy;
        $nm = $jm + 1;

        if ($nm === 13) {
            $nm = 1;
            $ny++;
        }

        [$gy, $gm, $gd] = self::toGregorian($ny, $nm, 15);

        return CarbonImmutable::create($gy, $gm, $gd, 0, 0, 0, $tz)->utc();
    }
}
