---
paths:
  - 'resources/js/{pages,components}/storefront/packs-saves*/**'
  - 'resources/js/{pages,components}/storefront/pack*/**'
---

# Pagescomponents Storefront

## Storefront Packs & Saves shows real branch packs
/packs-saves renders active, in-window branch packs (status true, quantity > 0, start/end dates, active branch, ?branch= filter) via StorefrontCatalog::packs / featuredPack / mapPackCard; never on-offer products. Sections are separate components in components/storefront/packs-saves (hero, featured-pack, packs-toolbar, packs-grid); the grid uses the shared PackCard. Search/sort reload only packs+filters. Buy Now (card, featured, detail) POSTs packs-saves.buy with quantity, stores session `storefront.checkout_pack`, and redirects to checkout — no cart. Coupons are not allowed on pack checkout. Pack card ids are branch_slug:slug because pack slugs are unique per branch only.

## Storefront pack detail page
Pack cards (grid, featured, related) link to packs-saves.show /packs-saves/{branchSlug}/{packSlug} via mapPackCard slug + branchSlug. PackController@show 404s unless StorefrontCatalog::findActivePack passes activePacksQuery (active, qty > 0, in window, active branch). Page sections live in components/storefront/pack-detail (summary, trust note, spec cards, related packs); the gallery reuses ProductGallery (thumbnail carousel, arrows when more than 5 images) with fallbackImage and mainClassName instead of a separate pack gallery; PackInsideList and PackBuyControls are shared with FeaturedPack. Spec cards are derived from pack data; no fake rating. Buy Now uses startPackCheckout (same session checkout as cards/featured).
