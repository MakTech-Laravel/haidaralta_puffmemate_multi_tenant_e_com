<?php

namespace App\DataTables\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\AllowedFilter;

final class StatusBooleanFilter
{
    public static function make(string $name = 'status'): AllowedFilter
    {
        return AllowedFilter::callback($name, function (Builder $query, mixed $value) use ($name): void {
            if ($value === null || $value === '' || $value === 'all') {
                return;
            }

            if (is_bool($value)) {
                $query->where($name, $value);

                return;
            }

            $normalized = strtolower((string) $value);

            if (in_array($normalized, ['1', 'true', 'active', 'yes'], true)) {
                $query->where($name, true);

                return;
            }

            if (in_array($normalized, ['0', 'false', 'inactive', 'no'], true)) {
                $query->where($name, false);
            }
        });
    }
}
