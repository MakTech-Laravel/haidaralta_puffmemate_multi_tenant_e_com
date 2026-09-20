<?php

namespace App\Contracts;

use Laravel\Scout\Builder;

/**
 * Marks a model as indexed by Scout.
 *
 * Satisfied by Scout's `Searchable` trait. A trait cannot be used in a type
 * position, so this interface is what lets callers narrow a
 * `class-string<Model>` down to one that can be searched.
 *
 * The signature is intentionally untyped to stay compatible with the trait.
 */
interface ScoutSearchable
{
    /**
     * @param  string  $query
     * @param  callable|null  $callback
     * @return Builder
     */
    public static function search($query = '', $callback = null);
}
