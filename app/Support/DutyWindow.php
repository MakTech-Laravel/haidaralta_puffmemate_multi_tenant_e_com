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
    ): bool {
        $day = $moment->toDateString();

        if ($day < $startDate || $day > $endDate) {
            return false;
        }

        if (! in_array($moment->isoWeekday(), $daysOfWeek, true)) {
            return false;
        }

        return self::coversTime($moment->format('H:i:s'), $startTime, $endTime);
    }

    public static function coversTime(string $time, string $startTime, string $endTime): bool
    {
        if (! self::isSameDayWindow($startTime, $endTime)) {
            return false;
        }

        $normalized = self::normalizeTime($time);
        $start = self::normalizeTime($startTime);
        $end = self::normalizeTime($endTime);

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
        string $rightStartDate,
        string $rightEndDate,
        string $rightStartTime,
        string $rightEndTime,
        array $rightDays,
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
            $rightStartTime,
            $rightEndTime,
        );
    }

    public static function timesOverlap(
        string $leftStartTime,
        string $leftEndTime,
        string $rightStartTime,
        string $rightEndTime,
    ): bool {
        if (! self::isSameDayWindow($leftStartTime, $leftEndTime) || ! self::isSameDayWindow($rightStartTime, $rightEndTime)) {
            return false;
        }

        $leftStart = self::normalizeTime($leftStartTime);
        $leftEnd = self::normalizeTime($leftEndTime);
        $rightStart = self::normalizeTime($rightStartTime);
        $rightEnd = self::normalizeTime($rightEndTime);

        return $leftStart < $rightEnd && $rightStart < $leftEnd;
    }

    public static function isSameDayWindow(string $startTime, string $endTime): bool
    {
        return self::normalizeTime($endTime) > self::normalizeTime($startTime);
    }

    public static function normalizeTime(string $time): string
    {
        return strlen($time) === 5 ? "{$time}:00" : $time;
    }
}
