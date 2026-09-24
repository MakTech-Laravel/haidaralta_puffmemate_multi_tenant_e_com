---
paths:
  - app/Http/Controllers/Storefront/HomeController.php
---

# Controllers Storefront

## Home featured categories are DB-backed
Homepage featured category cards use StorefrontCatalog::featuredCategories() (DB featured + slogan/image). Do not wire them through DemoCatalog. CategoryCard uses a shared aspect-4/3 for all sizes.

## Home product rails are DB-tagged and capped
Homepage bestSellingProducts / newArrivalProducts use StorefrontCatalog::productsWithTag(ProductTag::BestSelling|NewArrival, selectedBranch(), config('catalog.home_products_max')). Each rail is active products with that tag only, limited by home_products_max (default 8). Do not wire them through DemoCatalog.
