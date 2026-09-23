---
paths:
  - 'database/seeders/**'
  - database/seeders/WorkforceShiftSeeder.php
---

# Seeders

## Dev seed accounts use phone login

UserSeeder phone constants (`PLATFORM_SUPER_ADMIN_PHONE`, `BRANCH_1_*_PHONE`, `DRIVER_PHONE`, `CUSTOMER_PHONE`, etc.) are the canonical E.164 login identifiers. Shared password for every seeded user is `password` (`UserSeeder::DEFAULT_PASSWORD`). Optional email constants remain for display only. Branch 1 = `sydney-cbd`, branch 2 = `melbourne-collins`, branch 3 = `brisbane-queen` (`BranchSeeder::*` constants). Prefer `UserSeeder::*_PHONE` in tests for authentication; use email constants only when asserting display email.

## Catalog category homepage and nav caps

CatalogSeeder may feature at most `config('catalog.featured_categories_max')` (5) categories and put at most `nav_categories_max` (6) in header nav (`show_in_nav` + `nav_sort`). `slogan` is the homepage card subtitle. Featured and show_in_nav are independent flags.

## Catalog product storefront fields

Seed brand, tags (`new_arrival`, `best_selling`, `popular`, `on_offer`), and specifications label/value rows for key SKUs. Keep product_variants for pack/carton and flavor SKUs until the owner explicitly approves removal. CatalogSeeder also sets products.selling_price / regular_price (Sydney flagship ticket + ~15% compare-at) so common catalog pricing works when `show_branch_pricing` is false.

## Catalog seeder images use public web paths

Seed media lives under `public/images` (committed storefront + `public/images/catalog/...` assets). DB stores `/images/...` paths — never raw remote URLs and never copies into `storage/app/public`. Keep catalog/storefront images committed for staging/production. Runtime admin uploads still use the public disk; resolve all media through `App\Support\MediaUrl`.

## Seeded branches are AU retail locations

Branch 1 = sydney-cbd (PuffMeMate Sydney CBD), branch 2 = melbourne-collins, branch 3 = brisbane-queen. Use BranchSeeder::SYDNEY_CBD / MELBOURNE_COLLINS / BRISBANE_QUEEN constants — never downtown/uptown. User emails are realistic *@puffmemate.dev / *@sydney-cbd.dev style; login remains phone + UserSeeder::DEFAULT_PASSWORD (password).

## Seeded driver uses users.id as driver_id

Daniel Foster (UserSeeder::DRIVER) is user_type=driver with branch_id null, a driver_profiles row (user_id), and driver_branch_assignments keyed by driver_id = users.id. Seed Sydney CBD always. Also seed Melbourne Collins when `config('drivers.single_active_branch')` is false. Do not hardcode one or two active branches in the seeder.

## Seeder has no roster periods
WorkforceShiftSeeder uses personal date ranges only. Do not seed global roster periods or names like September 2026 / Spring 2026.
