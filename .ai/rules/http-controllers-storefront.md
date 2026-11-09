---
paths:
  - 'config/catalog.php,app/Http/Controllers/Storefront/HomeController.php'
---

# Http Controllers Storefront

## Home product rails capped by tag
Homepage Best Selling uses StorefrontCatalog::bestSellingProducts (real sales on delivered, collected/verified orders, not tags). New Arrivals uses StorefrontCatalog::productsWithTag(ProductTag::NewArrival), newest first. Each rail is capped independently by config('catalog.home_products_max') (default 8).
