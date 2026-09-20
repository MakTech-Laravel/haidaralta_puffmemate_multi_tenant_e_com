<?php

namespace App\Contracts;

/**
 * Declares the column allow-lists a model exposes to the data tables.
 *
 * Satisfied by the `HasDataTableConfig` trait. The interface exists so the
 * data tables can narrow a `class-string<Model>` to something that actually
 * has these static methods.
 */
interface DataTableConfigurable
{
    /**
     * Columns used for global text search.
     *
     * @return list<string>
     */
    public static function dataTableSearchableColumns(): array;

    /**
     * Columns allowed for the `sort=` parameter.
     *
     * @return list<string>
     */
    public static function dataTableSortableColumns(): array;

    /**
     * Keys allowed via `filter[key]=`.
     *
     * @return list<string>
     */
    public static function dataTableFilterableColumns(): array;
}
