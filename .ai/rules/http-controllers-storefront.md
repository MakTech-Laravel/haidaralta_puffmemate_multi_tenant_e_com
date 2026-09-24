---
paths:
  - 'config/catalog.php,app/Http/Controllers/Storefront/HomeController.php'
---

# Http Controllers Storefront

## Home product rails capped by tag
Homepage New Arrivals / Best Selling use StorefrontCatalog::productsWithTag with config('catalog.home_products_max') (default 8). Each rail is active products with that tag only.
