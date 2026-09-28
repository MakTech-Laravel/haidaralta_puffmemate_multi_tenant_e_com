---
paths:
  - app/Http/Controllers/Storefront/HomeController.php
  - app/Http/Controllers/Storefront/CategoryController.php
  - app/Http/Controllers/Storefront/ProductController.php
---

# Controllers Storefront

## Home featured categories are DB-backed
Homepage featured category cards use StorefrontCatalog::featuredCategories() (DB featured + slogan/image). Do not wire them through DemoCatalog. CategoryCard uses a shared aspect-4/3 for all sizes.

## Home product rails are DB-tagged and capped
Homepage bestSellingProducts / newArrivalProducts use StorefrontCatalog::productsWithTag(ProductTag::BestSelling|NewArrival, selectedBranch(), config('catalog.home_products_max')). Each rail is active products with that tag only, limited by home_products_max (default 8). Do not wire them through DemoCatalog.

## Category shop uses StorefrontShopQuery
/shop/{category} has no branch select. Search, sort, and listing go through StorefrontShopQuery (Scout/Meilisearch then Eloquent fallback), locked to that category plus one-level children. Sort options are name_asc, name_desc, newest, price_asc, price_desc.

## PDP GET counts product views
products.views increments on storefront products.show GET unless the Purpose header is prefetch. Do not increment from Add to cart. ProductCard must not prefetch the PDP so each card click is a real view.
