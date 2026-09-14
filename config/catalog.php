<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Featured categories (homepage)
    |--------------------------------------------------------------------------
    |
    | Maximum number of categories that may be marked featured at once for
    | storefront homepage "Shop by category" cards.
    |
    */

    'featured_categories_max' => env('FEATURED_CATEGORIES_MAX', 5),

    /*
    |--------------------------------------------------------------------------
    | Header navigation categories
    |--------------------------------------------------------------------------
    |
    | Maximum number of categories that may appear in the storefront header.
    | Keep this low enough that the nav does not wrap or overflow on desktop.
    |
    */

    'nav_categories_max' => env('NAV_CATEGORIES_MAX', 6),

];
