---
paths:
  - 'app/Http/Controllers/Storefront/**,app/Support/Storefront/StorefrontCatalog.php,resources/js/{pages,components}/storefront/**,resources/js/types/storefront.ts,resources/js/lib/storefront*.ts'
---

# Types Js Lib

## Catalog storefront is StorefrontCatalog-only
Storefront catalog pages (home, shop, category, product, packs-saves) use StorefrontCatalog only. Types in types/storefront.ts; features in lib/storefront-features.ts. Never depend on DemoCatalog or storefront-demo product arrays for catalog. Cart/checkout/customer/driver may still use DemoCatalog until migrated.
