<?php

namespace App\Support;

use Carbon\CarbonInterface;

final class DutyWindow
{
    /**
     * @param  list<int>  $daysOfWeek
     */
    public static function coversMoment(
        CarbonInterface $moment,
        string $startDate,
        string $endDate,
        string $startTime,
        string $endTime,
        array $daysOfWeek,
        bool $isOvernight = false,
    ): bool {
        $day = $moment->toDateString();

        if ($day < $startDate || $day > $endDate) {
            return false;
        }

        if (! in_array($moment->isoWeekday(), $daysOfWeek, true)) {
            return false;
        }

        return self::coversTime($moment->format('H:i:s'), $startTime, $endTime, $isOvernight);
    }

    public static function coversTime(string $time, string $startTime, string $endTime, bool $isOvernight = false): bool
    {
        $normalized = self::normalizeTime($time);
        $start = self::normalizeTime($startTime);
        $end = self::normalizeTime($endTime);

        if ($isOvernight || $end <= $start) {
            return $normalized >= $start || $normalized < $end;
        }

        return $start <= $normalized && $normalized < $end;
    }

    /**
     * @param  list<int>  $leftDays
     * @param  list<int>  $rightDays
     */
    public static function overlaps(
        string $leftStartDate,
        string $leftEndDate,
        string $leftStartTime,
        string $leftEndTime,
        array $leftDays,
        bool $leftOvernight,
        string $rightStartDate,
        string $rightEndDate,
        string $rightStartTime,
        string $rightEndTime,
        array $rightDays,
        bool $rightOvernight,
    ): bool {
        if ($leftStartDate > $rightEndDate || $rightStartDate > $leftEndDate) {
            return false;
        }

        if (array_intersect($leftDays, $rightDays) === []) {
            return false;
        }

        return self::timesOverlap(
            $leftStartTime,
            $leftEndTime,
            $leftOvernight,
            $rightStartTime,
            $rightEndTime,
            $rightOvernight,
        );
    }

    public static function timesOverlap(
        string $leftStartTime,
        string $leftEndTime,
        bool $leftOvernight,
        string $rightStartTime,
        string $rightEndTime,
        bool $rightOvernight,
    ): bool {
        foreach (self::segments($leftStartTime, $leftEndTime, $leftOvernight) as $left) {
            foreach (self::segments($rightStartTime, $rightEndTime, $rightOvernight) as $right) {
                if ($left[0] < $right[1] && $right[0] < $left[1]) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function isOvernight(string $startTime, string $endTime): bool
    {
        return self::normalizeTime($endTime) <= self::normalizeTime($startTime);
    }

    public static function normalizeTime(string $time): string
    {
        return strlen($time) === 5 ? "{$time}:00" : $time;
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private static function segments(string $startTime, string $endTime, bool $isOvernight): array
    {
        $start = self::normalizeTime($startTime);
        $end = self::normalizeTime($endTime);

        if ($isOvernight || $end <= $start) {
            return [
                [$start, '24:00:00'],
                ['00:00:00', $end],
            ];
        }

        return [[$start, $end]];
    }
}
