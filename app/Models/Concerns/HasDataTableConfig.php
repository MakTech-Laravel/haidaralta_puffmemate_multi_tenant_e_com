<?php

namespace App\Models\Concerns;

trait HasDataTableConfig
{
    /**
     * Columns used for global text search (LIKE / Scout fallback).
     *
     * @return list<string>
     */
    public static function dataTableSearchableColumns(): array
    {
        return property_exists(static::class, 'dataTableSearchable')
            ? static::$dataTableSearchable
            : [];
    }

    /**
     * Columns allowed for Spatie sort= param.
     *
     * @return list<string>
     */
    public static function dataTableSortableColumns(): array
    {
        return property_exists(static::class, 'dataTableSortable')
            ? static::$dataTableSortable
            : [];
    }

    /**
     * Exact filter keys allowed via filter[key]=.
     *
     * @return list<string>
     */
    public static function dataTableFilterableColumns(): array
    {
        return property_exists(static::class, 'dataTableFilterable')
            ? static::$dataTableFilterable
            : [];
    }
}
