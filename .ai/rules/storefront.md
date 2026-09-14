---
paths:
    - 'resources/js/{layouts,components,pages}/storefront/**'
---

# Storefront

## Storefront UI is disposable

Storefront UI under resources/js/{layouts,components,pages}/storefront and lib/storefront-demo.ts is temporary placeholder until Figma. Prefer deleting/replacing that tree; keep public route names home/shop/about/contact when swapping UI.

## Live catalog replaces DemoCatalog for browse surfaces
Home, shop, category, and product pages read Eloquent via `App\Support\Storefront\StorefrontCatalog` (nav `show_in_nav`, featured homepage cards + slogan, tagged products, branch session price/stock). Cart/checkout may still use DemoCatalog until checkout is built. Selected branch lives in session key `storefront.branch_slug` (set from `?branch=`). Shared Inertia prop `storefront.navCategories` drives the header.

