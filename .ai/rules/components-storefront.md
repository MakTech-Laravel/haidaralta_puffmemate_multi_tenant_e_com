---
paths:
  - resources/js/components/storefront/shop-filters-sidebar.tsx
  - resources/js/components/storefront/product-card.tsx
  - resources/js/components/storefront/pack-card.tsx
---

# Components Storefront

## Shop price slider syncs with inputs
Shop price range: min/max inputs live-sync the dual slider (no floating tooltips). Slider floor/ceiling come from `filterOptions.sliderMin` / `sliderMax`. Commit filter on blur/Enter; at floor/ceiling commit null bounds.

## Shop price slider has no tooltips
Shop dual-range price slider does not show floating value bubbles/tooltips; values are shown only via the synced min/max inputs.

## Product cards add to cart
Shared ProductCard keeps image/title/price as a PDP link and a separate Add to cart button (never nest the button in the Link). Cards add quantity 1 via addToCart(product.id) — card id is the product slug. Variants stay on the PDP.

## Product cards show compact views
ProductCard shows CompactNumber-formatted views from mapProductCard (1k, 1.2k, 1m, 1b). Do not prefetch the PDP link. Do not nest Add to cart inside the Link.

## PackCard is presentational only
Storefront PackCard takes a StorefrontPack (types/storefront.ts) plus optional href, onBuyNow, buying, disabled. It fetches no data and has no cart wiring; pages own that. Save badge shows only when savings is set, the 'N left' pill only when stockLeft is set. Image/text sit in the Link when href is given; the Buy now button stays outside the Link. Red is bg-primary fill only; price text stays foreground.
