---
paths:
    - 'resources/js/{layouts,components,pages}/storefront/**'
    - 'resources/js/lib/storefront*.ts'
---

# Storefront

These rules apply only to storefront paths above. Do not apply this folder layout to platform, branch, admin, or other app areas.

## Storefront UI is disposable

Storefront UI under resources/js/{layouts,components,pages}/storefront and lib/storefront-*.ts is temporary placeholder until Figma. Prefer deleting/replacing that tree; keep public route names home/shop/about/contact when swapping UI.

## Storefront file structure

Keep Inertia pages only under `resources/js/pages/storefront/*.tsx`. Never move a page into `components/` — controllers render `storefront/{name}` and must resolve that page path.

Put page-specific section UI in `resources/js/components/storefront/{page}/` (e.g. `cart/`, `wholesale/`, `privacy/`, `product/`, `home/`). Name files `{page}-*.tsx` or a clear section name; prefix exports with the page when needed (e.g. `HomeHeroSlider`).

Keep shared storefront UI at `resources/js/components/storefront/` root: chrome (`storefront-header`, `storefront-footer`, search, branch select) and multi-page pieces (`product-card`, `category-card`, `features-bar`).

Keep static demo copy and types in `resources/js/lib/storefront-*.ts`. Pages compose sections; do not dump long-form layout into the page file.
