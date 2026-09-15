---
paths:
    - 'resources/js/{layouts,components,pages}/storefront/**'
    - 'resources/js/lib/storefront*.ts'
---

# Storefront

These rules apply only to storefront paths above. Do not apply this folder layout to platform, branch, admin, or other app areas.

## Storefront UI is disposable

Storefront UI under resources/js/{layouts,components,pages}/storefront and lib/storefront-demo.ts is temporary placeholder until Figma. Prefer deleting/replacing that tree; keep public route names home/shop/about/contact when swapping UI.

## Catalog sources by surface

Home, shop, and product detail use `DemoCatalog` so card ids (`"1"`, `"2"`, …) resolve. Category page still reads Eloquent via `App\Support\Storefront\StorefrontCatalog`. Nav categories and branch session (`storefront.branch_slug` from `?branch=`) stay on StorefrontCatalog. Cart/checkout may still use DemoCatalog until checkout is built. Shared Inertia prop `storefront.navCategories` drives the header.

