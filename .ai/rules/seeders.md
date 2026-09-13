---
paths:
    - 'database/seeders/**'
---

# Seeders

## Dev seed accounts use phone login

UserSeeder phone constants (`PLATFORM_SUPER_ADMIN_PHONE`, `BRANCH_1_*_PHONE`, `DRIVER_PHONE`, `CUSTOMER_PHONE`, etc.) are the canonical E.164 login identifiers. Shared password for every seeded user is `password` (`UserSeeder::DEFAULT_PASSWORD`). Optional `@dev.com` email constants remain for display only. Branch 1 = downtown, branch 2 = uptown. Prefer `UserSeeder::*_PHONE` in tests for authentication; use email constants only when asserting display email.

## Catalog seeders use real content
Catalog seeders use hand-written realistic product data only — never fake()/Faker. Prefer firstOrCreate by slug/sku so re-seed is idempotent. Branch stock/price goes through InventoryService and BranchPricingService after enabling via BranchProductAvailabilityService.
