<?php

namespace App\Support;

final class CompactNumber
{
    public static function format(int $number): string
    {
        $number = max(0, $number);

        if ($number < 1000) {
            return (string) $number;
        }

        foreach ([
            1_000_000_000 => 'b',
            1_000_000 => 'm',
            1_000 => 'k',
        ] as $unit => $suffix) {
            if ($number >= $unit) {
                return self::formatWithUnit($number, $unit, $suffix);
            }
        }

        return (string) $number;
    }

    private static function formatWithUnit(int $number, int $unit, string $suffix): string
    {
        $rounded = round($number / $unit, 1);

        if ($rounded >= 1000) {
            return match ($suffix) {
                'k' => self::formatWithUnit($number, 1_000_000, 'm'),
                'm' => self::formatWithUnit($number, 1_000_000_000, 'b'),
                default => self::label($rounded, $suffix),
            };
        }

        return self::label($rounded, $suffix);
    }

    private static function label(float $rounded, string $suffix): string
    {
        $value = fmod($rounded, 1.0) === 0.0
            ? (string) (int) $rounded
            : number_format($rounded, 1, '.', '');

        return $value.$suffix;
    }
}
