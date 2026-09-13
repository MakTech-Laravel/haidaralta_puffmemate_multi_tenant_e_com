<?php

namespace App\Support;

use App\Enums\CatalogBackgroundMode;

class CatalogBackgroundColor
{
    /**
     * @param  array<string, mixed>|null  $value
     * @return array<string, mixed>|null
     */
    public static function normalize(?array $value): ?array
    {
        if ($value === null || $value === []) {
            return null;
        }

        $mode = CatalogBackgroundMode::tryFrom((string) ($value['mode'] ?? ''));

        if ($mode === null) {
            return null;
        }

        return match ($mode) {
            CatalogBackgroundMode::Transparent => [
                'mode' => CatalogBackgroundMode::Transparent->value,
            ],
            CatalogBackgroundMode::Solid => [
                'mode' => CatalogBackgroundMode::Solid->value,
                'color' => self::normalizeHex($value['color'] ?? null) ?? '#E5E7EB',
            ],
            CatalogBackgroundMode::Gradient => [
                'mode' => CatalogBackgroundMode::Gradient->value,
                'from' => self::normalizeHex($value['from'] ?? null) ?? '#E5E7EB',
                'to' => self::normalizeHex($value['to'] ?? null) ?? '#FFFFFF',
                'angle' => self::normalizeAngle($value['angle'] ?? 135),
            ],
        };
    }

    /**
     * CSS background shorthand suitable for inline styles.
     *
     * @param  array<string, mixed>|null  $value
     */
    public static function cssBackground(?array $value): ?string
    {
        $normalized = self::normalize($value);

        if ($normalized === null) {
            return null;
        }

        $mode = CatalogBackgroundMode::from($normalized['mode']);

        return match ($mode) {
            CatalogBackgroundMode::Transparent => null,
            CatalogBackgroundMode::Solid => $normalized['color'],
            CatalogBackgroundMode::Gradient => sprintf(
                'linear-gradient(%ddeg, %s, %s)',
                $normalized['angle'],
                $normalized['from'],
                $normalized['to'],
            ),
        };
    }

    public static function normalizeHex(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $hex = strtoupper(trim($value));

        if (! preg_match('/^#[0-9A-F]{6}$/', $hex)) {
            return null;
        }

        return $hex;
    }

    public static function normalizeAngle(mixed $value): int
    {
        $angle = (int) $value;

        if ($angle < 0) {
            return 0;
        }

        if ($angle > 360) {
            return 360;
        }

        return $angle;
    }
}
