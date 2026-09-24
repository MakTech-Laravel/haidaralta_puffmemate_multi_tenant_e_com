---
paths:
  - 'app/Support/Storefront/StorefrontShopQuery.php,resources/js/pages/storefront/shop.tsx,app/Models/PriceRange.php,config/scout.php'
---

# Storefront Models

## Shop Meilisearch filters and PriceRange
Shop uses StorefrontShopQuery (Meilisearch search + Eloquent filters). No branch select. Facets from root categories, ProductTag cases, PriceRange. Product Scout indexes selling_price and effective_price.
