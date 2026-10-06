---
paths:
  - 'database/migrations/**'
  - database/migrations/2026_09_13_042225_create_products_table.php
---

# Migrations

## Post-handover migrations are modification-only

After handover / shared environments with real data: never edit, rename, or delete a migration that has already been deployed. Any schema change is a new modification migration (Schema::table or a new create file for a new table). Do not rewrite shipped create migrations, and do not add app tables into Laravel/package multi-table starters (users/sessions/password_reset_tokens, cache, jobs, passkeys, two_factor, permissions). Pre-handover fresh DBs may still edit the users create block only when columns belong on create; one new table = one new migration file. Prefer migrate --seed over migrate:fresh where data must be preserved.

## Pre-handover: edit create migrations, never data-move mods

Development / pre-handover: prefer editing the original create_* migration when a column should never have existed (e.g. drop products.category_id). One new table = one new create migration (e.g. category_product). Do not add Schema::table data-copy + drop-column migrations for greenfield schema changes. Post-handover shared DBs remain modification-only.

## Discount type belongs on create migration

Pre-handover: put new columns on the original create_* migration (e.g. branch_product_prices.discount_type). Do not ship a separate Schema::table + data backfill migration for greenfield schema.

## Category featured on create migration

Pre-handover: categories.featured boolean (default false) belongs on the original create_categories migration — never a separate Schema::table + backfill migration.

## Category slogan / nav columns on create migration

Pre-handover: categories.slogan, show_in_nav, nav_sort (and products.brand, tags, specifications, pack_label, pieces_per_pack, pack_unit) belong on the original create_* migrations — never Schema::table greenfield mods.

## Inventory transaction reason belongs on create

inventory_transactions.reason, reason_note, and unit_cost belong on the original create_inventory_transactions migration. buying_price and regular_price belong on create_branch_product_prices. Catalog-wide selling_price, regular_price, discount_type (default none), discount_price, discount_start_date, and discount_end_date belong on create_products. Do not add a Schema::table modification for greenfield columns on these tables. Price history is a new create_* table (`branch_product_price_histories`).

## Assignment dates belong on create_driver_branch_assignments

Development / pre-handover: `started_at` and `ended_at` belong on `create_driver_branch_assignments`. Do not ship a Schema::table modification for these greenfield columns. Unique remains `(driver_id, branch_id)` — never unique-on-active. Each new workforce table is its own create migration (`user_shift_days` is a new create file; do not add a period table). Data backfill lives in artisan `workforce:backfill-transfers-and-shifts`, not in migrations. Shift template names use partial unique indexes (`WHERE branch_id IS NULL` / `IS NOT NULL`) so sqlite tests and pgsql both work. `user_shift_days` also uses a partial unique index on `(user_id, branch_id, duty_on, shift_template_id) WHERE shift_template_id IS NOT NULL`.

## Catalog discount columns on create_products
Pre-handover: products.discount_type (default none), discount_price, discount_start_date, and discount_end_date belong on create_products alongside selling_price/regular_price. Do not add a Schema::table modification for these greenfield columns.

## Delivery radius on create_branches
Pre-handover: branches.delivery_radius_km nullable decimal belongs on create_branches beside lat/lng. Do not add a Schema::table modification for this greenfield column.

## Cart columns stay on create migrations
Pre-handover: carts (user_id, session_id, status) and cart_items (cart_id, product_id, variant_id, quantity, price_snapshot) belong on the original create_* files. Do not add Schema::table modification migrations such as add_storefront_*. Use migrate:fresh --seed when the local DB is still greenfield.

## PostgreSQL catalog indexes
PostgreSQL products indexes: partial indexes on active rows (name, created_at, selling_price), GIN jsonb_path_ops on tags, and pg_trgm GIN on name/brand/sku for LIKE fallback. SQLite tests get the same partial indexes with status = 1 and skip GIN/trgm. MySQL keeps composite btrees. Do not index effective price: the discount window uses the current date, which PostgreSQL rejects in an index expression.

## GIN tags index needs jsonb
Laravel $table->json() creates PostgreSQL json, and jsonb_path_ops rejects json. On pgsql, alter products.tags to jsonb (using tags::jsonb) before create index products_tags_gin using gin (tags jsonb_path_ops). pgvector/pg18 is unrelated.

## Pre-handover: drop image path columns on create migrations
Image path columns (users.avatar, branches.logo, categories/products/packs.image, driver_profiles.vehicle_image_path, home_banners.image_path, application_settings *_path, product_images/pack_images tables) were removed from original create_* migrations under pre-handover rules. Do not add Schema::table drop/backfill migrations for these. Data attachment uses Media Library + artisan media:migrate-legacy-paths / seeders. Prefer migrate:fresh --seed locally.
