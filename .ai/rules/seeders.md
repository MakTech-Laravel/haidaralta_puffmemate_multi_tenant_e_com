---
paths:
    - 'database/seeders/**'
---

# Seeders

## Dev seed accounts use phone login

UserSeeder phone constants (`PLATFORM_SUPER_ADMIN_PHONE`, `BRANCH_1_*_PHONE`, `DRIVER_PHONE`, `CUSTOMER_PHONE`, etc.) are the canonical E.164 login identifiers. Shared password for every seeded user is `password` (`UserSeeder::DEFAULT_PASSWORD`). Optional email constants remain for display only. Branch 1 = `sydney-cbd`, branch 2 = `melbourne-collins`, branch 3 = `brisbane-queen` (`BranchSeeder::*` constants). Prefer `UserSeeder::*_PHONE` in tests for authentication; use email constants only when asserting display email.

## Catalog seeders use real content
Catalog seeders use hand-written realistic product data only — never fake()/Faker. Prefer firstOrCreate by slug/sku so re-seed is idempotent. Branch stock/price goes through InventoryService and BranchPricingService after enabling via BranchProductAvailabilityService.

## Catalog seeder images use public web paths
Seed media lives under `public/images` (committed storefront assets or downloads into `public/images/catalog/...`). DB stores `/images/...` paths — never raw remote URLs and never copies into `storage/app/public`. Runtime admin uploads still use the public disk; resolve all media through `App\Support\MediaUrl`.

## Seeded branches are AU retail locations
Branch 1 = sydney-cbd (PuffOnMate Sydney CBD), branch 2 = melbourne-collins, branch 3 = brisbane-queen. Use BranchSeeder::SYDNEY_CBD / MELBOURNE_COLLINS / BRISBANE_QUEEN constants — never downtown/uptown. User emails are realistic *@puffonmate.dev / *@sydney-cbd.dev style; login remains phone + UserSeeder::DEFAULT_PASSWORD (password).
