<?php

namespace App\DataTables\Filters;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\AllowedFilter;

final class RoleTableFilters
{
    public static function kind(string $guard): AllowedFilter
    {
        return AllowedFilter::callback('kind', function (Builder $query, mixed $value): void {
            if ($value === null || $value === '' || $value === 'all') {
                return;
            }

            $normalized = strtolower((string) $value);

            if ($normalized === 'system') {
                $query->where('name', RoleEnum::SUPER_ADMIN->value);

                return;
            }

            if ($normalized === 'custom') {
                $query->where('name', '!=', RoleEnum::SUPER_ADMIN->value);
            }
        });
    }

    public static function users(): AllowedFilter
    {
        return AllowedFilter::callback('users', function (Builder $query, mixed $value): void {
            if ($value === null || $value === '' || $value === 'all') {
                return;
            }

            $normalized = strtolower((string) $value);

            if ($normalized === 'with') {
                $query->has('users');

                return;
            }

            if ($normalized === 'without') {
                $query->doesntHave('users');
            }
        });
    }

    public static function permissions(string $guard): AllowedFilter
    {
        return AllowedFilter::callback('permissions', function (Builder $query, mixed $value) use ($guard): void {
            if ($value === null || $value === '' || $value === 'all') {
                return;
            }

            $normalized = strtolower((string) $value);
            $manyThreshold = max(2, (int) ceil(count(PermissionEnum::forGuard($guard)) * 0.75));

            if ($normalized === 'none') {
                $query
                    ->where('name', '!=', RoleEnum::SUPER_ADMIN->value)
                    ->doesntHave('permissions');

                return;
            }

            if ($normalized === 'some') {
                $query
                    ->where('name', '!=', RoleEnum::SUPER_ADMIN->value)
                    ->has('permissions')
                    ->has('permissions', '<', $manyThreshold);

                return;
            }

            if ($normalized === 'many') {
                $query->where(function (Builder $builder) use ($manyThreshold): void {
                    $builder->where('name', RoleEnum::SUPER_ADMIN->value)
                        ->orHas('permissions', '>=', $manyThreshold);
                });
            }
        });
    }
}
