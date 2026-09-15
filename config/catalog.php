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
    | When false, branch create/store abort 404 and the Add category
    | button is hidden. The list itself is gated separately by
    | branch_can_view_categories. Flip to true to restore branch
    | category creation (the list flag must also be true).
    |
    */

    'branch_can_create_categories' => env('BRANCH_CAN_CREATE_CATEGORIES', false),

    /*
    |--------------------------------------------------------------------------
    | Branch category list
    |--------------------------------------------------------------------------
    |
    | When false, branch portals cannot open category index/show/edit (or
    | merchandising) — those routes abort 404 and the sidebar drops
    | Categories so Products sits as a top-level item, not under Catalog.
    | Flip to true to restore the branch category list.
    |
    */

    'branch_can_view_categories' => env('BRANCH_CAN_VIEW_CATEGORIES', false),

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
