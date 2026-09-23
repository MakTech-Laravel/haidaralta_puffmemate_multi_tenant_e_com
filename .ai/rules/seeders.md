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

Flagship trio: Branch 1 = sydney-cbd, branch 2 = melbourne-collins, branch 3 = brisbane-queen (`BranchSeeder::SYDNEY_CBD` / `MELBOURNE_COLLINS` / `BRISBANE_QUEEN`). Also seed Perth Hay, Adelaide Rundle, Surfers Paradise, Canberra Civic, Newcastle Hunter, and Parramatta Church. User emails are realistic *@puffmemate.dev / *@{slug}.dev; login remains phone + UserSeeder::DEFAULT_PASSWORD (password). No Faker in any seeder.

## Seeded people are handwritten rosters

UserSeeder keeps the canonical phone/email constants for tests, then adds extra platform admins/managers, 3–5 staff per store, ~14 drivers, and ~20 customers — all fixed names and E.164 phones. TransferHistorySeeder writes archive transfer rows only (people already sit on destination branches).

## Seeded driver uses users.id as driver_id

Daniel Foster (UserSeeder::DRIVER) is user_type=driver with branch_id null, a driver_profiles row (user_id), and driver_branch_assignments keyed by driver_id = users.id. Seed Sydney CBD always. Also seed Melbourne Collins when `config('drivers.single_active_branch')` is false. Do not hardcode one or two active branches in the seeder.

## Seeder has no roster periods
WorkforceShiftSeeder uses personal date ranges only. Do not seed global roster periods or names like September 2026 / Spring 2026.

## Seeders are massive handwritten AU retail data
All seeders use handwritten real-world AU retail copy — never Faker or fake(). Branches: 9 stores (3 flagships + Perth, Adelaide, Surfers, Canberra, Newcastle, Parramatta). Users expand beyond constants. Catalog ~48 products. TransferHistorySeeder archives only. ConversationVolume is high-volume phrase banks.
