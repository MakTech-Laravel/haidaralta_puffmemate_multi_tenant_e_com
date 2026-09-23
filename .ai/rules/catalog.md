---
paths:
  - 'resources/js/components/catalog/**'
---

# Catalog

## Manage pricing is product-picker only
ManageCatalogPricingPage is product-only InfiniteScrollCombobox against stock product-options, then Price + Discount (no buying, stock, variants, or history). Platform nav when !showBranchPricing; branch nav also requires branch_can_manage_catalog_pricing. PUT platform.products.prices / branch.products.catalog-prices.
