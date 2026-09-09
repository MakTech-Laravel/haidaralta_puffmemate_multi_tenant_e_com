<?php

namespace App\Support;

use DateTimeInterface;
use Illuminate\Support\Carbon;

/**
 * Normalize model timestamps for Scout indexes when Eloquent date casting may be off.
 */
final class SearchableTimestamp
{
    public static function from(mixed $value): int
    {
        if ($value instanceof DateTimeInterface) {
            return $value->getTimestamp();
        }

        if ($value === null || $value === '') {
            return 0;
        }

        return Carbon::parse($value)->getTimestamp();
    }
}
