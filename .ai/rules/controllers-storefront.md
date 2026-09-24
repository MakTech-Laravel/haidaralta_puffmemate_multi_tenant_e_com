---
paths:
  - app/Http/Controllers/Storefront/HomeController.php
---

# Controllers Storefront

## Home featured categories are DB-backed
Homepage featured category cards use StorefrontCatalog::featuredCategories() (DB featured + slogan/image). Do not wire them through DemoCatalog. CategoryCard uses a shared aspect-4/3 for all sizes.
