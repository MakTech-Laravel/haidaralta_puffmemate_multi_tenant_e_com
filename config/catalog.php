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

    /*
    |--------------------------------------------------------------------------
    | Branch category create
    |--------------------------------------------------------------------------
    |
    | When false, branch portals can still view and edit global categories
    | but create/store abort 404 and the Add category button is hidden.
    | Flip to true to restore branch category creation.
    |
    */

    'branch_can_create_categories' => env('BRANCH_CAN_CREATE_CATEGORIES', false),

    /*
    |--------------------------------------------------------------------------
    | Parent / child category UI
    |--------------------------------------------------------------------------
    |
    | When false, parent picker, list column/filter, and subcategory
    | surfaces stay in the codebase but are not rendered. Flip to true
    | to restore parent-category management.
    |
    */

    'show_parent_category' => env('SHOW_PARENT_CATEGORY', false),

];
