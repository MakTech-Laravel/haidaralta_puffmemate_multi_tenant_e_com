---
paths:
  - 'resources/js/{pages,components}/storefront/packs-saves*/**'
---

# Pagescomponents Storefront

## Storefront Packs & Saves shows real branch packs
/packs-saves renders active, in-window branch packs (status true, quantity > 0, start/end dates, active branch, ?branch= filter) via StorefrontCatalog::packs / featuredPack / mapPackCard; never on-offer products. Sections are separate components in components/storefront/packs-saves (hero, featured-pack, packs-toolbar, packs-grid); the grid uses the shared PackCard. Search/sort reload only packs+filters. Buy Now (card and featured) is visual only until pack cart support exists. Pack card ids are branch_slug:slug because pack slugs are unique per branch only.
