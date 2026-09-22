<?php

namespace App\Support;

use DateTimeImmutable;
use InvalidArgumentException;

final class ShiftDateRuns
{
    /**
     * @param  list<string>  $dates
     * @return list<string>
     */
    public static function normalize(array $dates): array
    {
        $normalized = [];

        foreach ($dates as $date) {
            $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            $errors = DateTimeImmutable::getLastErrors();

            if (
                $parsed === false
                || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
                || $parsed->format('Y-m-d') !== $date
            ) {
                throw new InvalidArgumentException('Shift dates must use the Y-m-d format.');
            }

            $normalized[$date] = $date;
        }

        ksort($normalized);

        return array_values($normalized);
    }

    /**
     * @param  list<string>  $dates
     * @return list<array{start_date: string, end_date: string, dates: list<string>}>
     */
    public static function partition(array $dates): array
    {
        $dates = self::normalize($dates);

        if ($dates === []) {
            return [];
        }

        $runs = [];
        $currentDates = [];
        $previous = null;

        foreach ($dates as $date) {
            if (
                $previous !== null
                && (new DateTimeImmutable($previous))->modify('+1 day')->format('Y-m-d') !== $date
            ) {
                $runs[] = self::run($currentDates);
                $currentDates = [];
            }

            $currentDates[] = $date;
            $previous = $date;
        }

        $runs[] = self::run($currentDates);

        return $runs;
    }

    /**
     * @param  list<string>  $dates
     */
    public static function inclusiveSpan(array $dates): int
    {
        $dates = self::normalize($dates);

        if ($dates === []) {
            return 0;
        }

        $first = new DateTimeImmutable($dates[0]);
        $last = new DateTimeImmutable($dates[array_key_last($dates)]);

        return (int) $first->diff($last)->days + 1;
    }

    /**
     * @param  non-empty-list<string>  $dates
     * @return array{start_date: string, end_date: string, dates: list<string>}
     */
    private static function run(array $dates): array
    {
        return [
            'start_date' => $dates[0],
            'end_date' => $dates[array_key_last($dates)],
            'dates' => $dates,
        ];
    }
}
