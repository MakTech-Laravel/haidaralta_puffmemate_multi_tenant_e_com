---
paths:
  - resources/js/components/storefront/shop-filters-sidebar.tsx
  - resources/js/components/storefront/product-card.tsx
  - resources/js/components/storefront/pack-card.tsx
  - 'resources/js/components/storefront/**'
---

# Components Storefront

## Shop price slider syncs with inputs
Shop price range: min/max inputs live-sync the dual slider (no floating tooltips). Slider floor/ceiling come from `filterOptions.sliderMin` / `sliderMax`. Commit filter on blur/Enter; at floor/ceiling commit null bounds.

## Shop price slider has no tooltips
Shop dual-range price slider does not show floating value bubbles/tooltips; values are shown only via the synced min/max inputs.

## Product cards add to cart
Shared ProductCard keeps image/title/price as a PDP link and a separate Add to cart button (never nest the button in the Link). Cards add quantity 1 via addToCart(product.id) — card id is the product slug. Variants stay on the PDP.

## Product cards show compact views
ProductCard shows CompactNumber-formatted views from mapProductCard (1k, 1.2k, 1m, 1b) as a pill in the bottom-right of the image (Offer badge stays top-right). Card Add to cart sends countView and bumps the shown count locally when it is a plain number. Do not prefetch the PDP link. Do not nest Add to cart inside the Link.

## PackCard is presentational only
Storefront PackCard takes a StorefrontPack (types/storefront.ts) plus optional href, onBuyNow, buying, disabled. It fetches no data and has no cart wiring; pages own that. Save badge shows only when savings is set, the 'N left' pill only when stockLeft is set. Image/text sit in the Link when href is given; the Buy now button stays outside the Link. Red is bg-primary fill only; price text stays foreground.

## Storefront product and pack prices show AUD, never $
Product and pack cards and detail pages display prices as "AUD 0.00" ("Start from AUD 0.00" for multi-price products). Backend labels come from BranchProductPriceDisplay::formatAud (label, compare_at_formatted, StorefrontCatalog compareAtPrice); pack components render "{currency} {price}" from plain numbers. Cart, checkout, orders and portals still use $ until changed separately.

## PackCard sizes itself with container queries
PackCard sits in 2/3/4-column grids (packs grid, related packs), so it uses @container on the article and @xs: (card >= 320px) variants instead of screen breakpoints. Narrow cards use smaller badge/pill/text and stack the price above a full-width Buy Now button; @xs restores the side-by-side footer.
