---
paths:
  - 'database/seeders/**'
  - database/seeders/WorkforceShiftSeeder.php
  - database/seeders/CatalogSeeder.php
  - database/seeders/CategorySeeder.php
  - database/seeders/ProductSeeder.php
  - database/seeders/PriceRangeSeeder.php
---

# Seeders

## Dev seed accounts use phone login

UserSeeder phone constants (`PLATFORM_SUPER_ADMIN_PHONE`, `BRANCH_1_*_PHONE`, `DRIVER_PHONE`, `CUSTOMER_PHONE`, etc.) are the canonical E.164 login identifiers. Shared password for every seeded user is `password` (`UserSeeder::DEFAULT_PASSWORD`). Optional email constants remain for display only. Branch 1 = `sydney-cbd`, branch 2 = `melbourne-collins`, branch 3 = `brisbane-queen` (`BranchSeeder::*` constants). Prefer `UserSeeder::*_PHONE` in tests for authentication; use email constants only when asserting display email.

## Catalog category homepage and nav caps

CategorySeeder may feature at most `config('catalog.featured_categories_max')` (5) categories and put at most `nav_categories_max` (6) in header nav (`show_in_nav` + `nav_sort`). `slogan` is the homepage card subtitle. Featured and show_in_nav are independent flags.

## Catalog product storefront fields

Seed brand, tags (`new_arrival`, `best_selling`, `popular`, `on_offer`), pack_label / pieces_per_pack / pack_unit, and specifications label/value rows for key SKUs. Keep product_variants for pack/carton and flavor SKUs until the owner explicitly approves removal. ProductSeeder also sets products.selling_price / regular_price (Sydney flagship ticket + ~15% compare-at) so common catalog pricing works when `show_branch_pricing` is false.

## Catalog seeder images use public web paths

Seed media lives under `public/images` (committed storefront + `public/images/catalog/...` assets). DB stores `/images/...` paths — never raw remote URLs and never copies into `storage/app/public`. Keep catalog/storefront images committed for staging/production. Runtime admin uploads still use the public disk; resolve all media through `App\Support\MediaUrl`. Shared helpers live in `database/seeders/Concerns/SeedsCatalogMedia.php`.

## Catalog split: CategorySeeder + ProductSeeder

Catalog seeding is split: `CategorySeeder` then `ProductSeeder`. `CatalogSeeder` remains a thin wrapper that calls both (tests may still seed it). DatabaseSeeder calls the two seeders directly. ProductSeeder looks up categories by slug and depends on CategorySeeder.

## Shop price ranges seeder

`PriceRangeSeeder` seeds AUD shop sidebar bands (Under $20 … `$10,000 & above`) via `updateOrCreate` on slug, and creates `shop_price_filter_settings` (slider_min/max) when missing. Included in `DatabaseSeeder` after products. Re-run alone with `php artisan db:seed --class=PriceRangeSeeder`. “All Price” stays a client clear option — not a DB row.

## Seeded branches are AU retail locations

Flagship trio: Branch 1 = sydney-cbd, branch 2 = melbourne-collins, branch 3 = brisbane-queen (`BranchSeeder::SYDNEY_CBD` / `MELBOURNE_COLLINS` / `BRISBANE_QUEEN`). Also seed Perth Hay, Adelaide Rundle, Surfers Paradise, Canberra Civic, Newcastle Hunter, and Parramatta Church. User emails are realistic *@puffmemate.dev / *@{slug}.dev; login remains phone + UserSeeder::DEFAULT_PASSWORD (password). No Faker in any seeder.

## Seeded people are handwritten rosters

UserSeeder keeps the canonical phone/email constants for tests, then adds extra platform admins/managers, 3–5 staff per store, ~14 drivers, and ~20 customers — all fixed names and E.164 phones. TransferHistorySeeder writes archive transfer rows only (people already sit on destination branches).

## Seeded driver uses users.id as driver_id

Daniel Foster (UserSeeder::DRIVER) is user_type=driver with branch_id null, a driver_profiles row (user_id), and driver_branch_assignments keyed by driver_id = users.id. Seed Sydney CBD always. Also seed Melbourne Collins when `config('drivers.single_active_branch')` is false. Do not hardcode one or two active branches in the seeder.

## Seeder has no roster periods
WorkforceShiftSeeder uses personal date ranges only. Do not seed global roster periods or names like September 2026 / Spring 2026.

## Seeders are massive handwritten AU retail data
All seeders use handwritten real-world AU retail copy — never Faker or fake(). Core branches: 9 stores (3 flagships + Perth, Adelaide, Surfers, Canberra, Newcastle, Parramatta), plus 73 nationwide stores from NationwideBranchSeeder. Users expand beyond constants. Core catalog ~45 products + ~600 from ExtendedProductSeeder. TransferHistorySeeder archives only. ConversationVolume is high-volume phrase banks (not run by DatabaseSeeder).

## CatalogSeeder featured category set
Featured homepage set is cigarettes, vapes, tobacco, nicotine-pouches, accessories (slogans match marketing cards). Disposables/pod-systems are vapes children with images, not in nav. Packs & Saves / Wholesale stay fixed pages. CategorySeeder `category()` uses updateOrCreate so re-seed refreshes images/flags.

## Catalog seeders are split
Catalog seeding is CategorySeeder then ProductSeeder. CatalogSeeder is a thin wrapper for both. Shared image helpers live in Concerns/SeedsCatalogMedia.

## Nationwide seeders layer on top of the core seeders
Keep BranchSeeder (9 core stores, null radius = deliver anywhere) and ProductSeeder (~45 hand-written products) unchanged; tests seed them directly and assert on their fixed sets. Scale data lives in separate seeders called only by DatabaseSeeder: NationwideBranchSeeder (73 real AU stores with metro/regional/remote radii), ExtendedCategorySeeder (non-featured, non-nav children), ExtendedProductSeeder (~600 deterministic products from handwritten brand/flavour/size lists, no Faker), NationwideInventorySeeder (bulk-insert stock, stock_in ledger, and branch prices; skips existing rows). DatabaseSeeder no longer runs OrderConversationSeeder/ConversationVolumeSeeder; their files stay because conversation tests use them. Long seeders report live counts via Concerns/ReportsSeedProgress.

## Every seeder reports live progress
All seeders use Concerns/ReportsSeedProgress so db:seed shows `Label  n/total [bar] % elapsed` plus a one-line summary. Loops use withSeedProgress($label, $items, $callback, $summary); hand-written seeders built from inline helper calls queue work with queueSeedStep() and run it with runQueuedSeedSteps() so the total is known up front (queued steps must set their own Spatie team context). Summary closures receive the step total. The trait no-ops when no console command is attached (e.g. app()-resolved seeders).

## DriverStockAssignmentSeeder after inventory
DatabaseSeeder runs DriverStockAssignmentSeeder after BranchInventorySeeder + NationwideInventorySeeder. It calls DriverStockAssignmentService (Multiple mode) with a fixed starter-bag slug map for every branch that has active drivers. Re-runs skip branches that already have DriverStockAssignment or driver_inventories rows. Prefer the service over raw inserts so branch stock, driver_inventories, and both ledgers stay consistent.
