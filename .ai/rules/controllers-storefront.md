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

## PDP GET counts unique IP product views
products.views increments via ProductViewService on products.show GET (skip prefetch / Inertia partial reload of storefront/product) and on cart.store when count_view is true. At most one count per product_id+IP per 24 hours; logs live in product_view_logs and are pruned after 7 days without decreasing products.views. Cart flashes view_counted for ProductCard. PDP Add to cart never sends count_view. ProductCard must not prefetch the PDP.
