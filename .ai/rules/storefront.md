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

Home, shop, category, and product detail use `DemoCatalog` so card ids (`"1"`, `"2"`, …) resolve and prices show as plain `$…` (no “Start from”, no branch required). Header category tabs use static demo chrome `storefrontNavItems` in `storefront-chrome.ts` (not DB `navCategories`). Branch session (`storefront.branch_slug` from `?branch=`) stays on StorefrontCatalog. Cart/checkout may still use DemoCatalog until checkout is built. Shared Inertia prop `storefront.navCategories` may still be shared but does not drive the header.
