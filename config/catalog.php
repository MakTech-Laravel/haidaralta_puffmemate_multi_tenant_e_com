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
    | Homepage product rails
    |--------------------------------------------------------------------------
    |
    | Maximum products per tagged homepage carousel (New Arrivals, Best
    | Selling, etc.). Each rail filters active products by that tag only.
    |
    */

    'home_products_max' => env('HOME_PRODUCTS_MAX', 8),

    /*
    |--------------------------------------------------------------------------
    | Shop price slider bounds (defaults)
    |--------------------------------------------------------------------------
    |
    | Used when creating the singleton shop_price_filter_settings row.
    | Platform users override both values on Shop price ranges.
    |
    */

    'shop_price_slider_min' => env('SHOP_PRICE_SLIDER_MIN', 0),

    'shop_price_slider_max' => env('SHOP_PRICE_SLIDER_MAX', 50000),

    /*
    |--------------------------------------------------------------------------
    | Similar products (PDP)
    |--------------------------------------------------------------------------
    |
    | Maximum products on the product detail "Similar Products" rail.
    | Ranked by category affinity, brand match, and merchandising tags
    | (best_selling, popular, on_offer, new_arrival) — not alphabetical.
    |
    */

    'similar_products_max' => env('SIMILAR_PRODUCTS_MAX', 4),

    /*
    |--------------------------------------------------------------------------
    | Header navigation categories
    |--------------------------------------------------------------------------
    |
    | Maximum number of categories that may appear in the storefront header.
    | Keep this low enough that the nav does not wrap or overflow on desktop.
    |
    */

    'nav_categories_max' => env('NAV_CATEGORIES_MAX', 4),

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

    /*
    |--------------------------------------------------------------------------
    | Product variants
    |--------------------------------------------------------------------------
    |
    | When false, variant UI and variant mutations abort 404. Tables and
    | routes stay in the codebase. Flip to true to restore variants.
    |
    */

    'show_product_variants' => env('SHOW_PRODUCT_VARIANTS', false),

    /*
    |--------------------------------------------------------------------------
    | Bulk stock import (web)
    |--------------------------------------------------------------------------
    |
    | When false, Bulk Import is hidden from platform and branch sidebars and
    | stock/import routes abort 404. Pages and controllers stay in the
    | codebase. Flip to true to restore web bulk import.
    |
    */

    'show_bulk_import' => env('SHOW_BULK_IMPORT', false),

    /*
    |--------------------------------------------------------------------------
    | Branch product pricing UI
    |--------------------------------------------------------------------------
    |
    | When false, branch-level price/discount UI and price-history surfaces
    | stay in the codebase but are not rendered; price upsert and price-history
    | routes abort 404. Catalog selling/regular prices on products drive
    | display instead. Flip to true to restore per-branch pricing.
    |
    */

    'show_branch_pricing' => env('SHOW_BRANCH_PRICING', false),

    /*
    |--------------------------------------------------------------------------
    | Branch catalog price / discount edits
    |--------------------------------------------------------------------------
    |
    | Only applies when show_branch_pricing is false (common product prices).
    | When true, branch portals keep Manage pricing and may write catalog
    | selling/regular/discount. When false, Manage pricing is hidden from
    | branch nav and those routes abort 404; only platform may change common
    | prices. Flip to false for platform-only catalog pricing.
    |
    */

    'branch_can_manage_catalog_pricing' => env('BRANCH_CAN_MANAGE_CATALOG_PRICING', false),

    /*
    |--------------------------------------------------------------------------
    | Product SKU prefix
    |--------------------------------------------------------------------------
    |
    | Auto-generated SKUs are PREFIX-SLUG in uppercase (e.g. PMM-AIR-PRO).
    | Staff can still edit the SKU before save.
    |
    */

    'sku_prefix' => env('CATALOG_SKU_PREFIX', 'PMM'),

    /*
    |--------------------------------------------------------------------------
    | Default branch shipping rule
    |--------------------------------------------------------------------------
    |
    | Fallback used by ShippingCalculator when a branch has no
    | branch_shipping_rules row configured yet.
    |
    */

    'default_shipping_base_fee' => env('DEFAULT_SHIPPING_BASE_FEE', 5),

    'default_shipping_free_distance_km' => env('DEFAULT_SHIPPING_FREE_DISTANCE_KM', 0),

    'default_shipping_cost_per_km' => env('DEFAULT_SHIPPING_COST_PER_KM', 1),

    'default_shipping_minimum_charge' => env('DEFAULT_SHIPPING_MINIMUM_CHARGE', 0),

];
