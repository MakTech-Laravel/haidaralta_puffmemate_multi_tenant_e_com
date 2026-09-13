---
paths:
  - 'database/migrations/**'
---

# Migrations

## Post-handover migrations are modification-only
After handover / shared environments with real data: never edit, rename, or delete a migration that has already been deployed. Any schema change is a new modification migration (Schema::table or a new create file for a new table). Do not rewrite shipped create migrations, and do not add app tables into Laravel/package multi-table starters (users/sessions/password_reset_tokens, cache, jobs, passkeys, two_factor, permissions). Pre-handover fresh DBs may still edit the users create block only when columns belong on create; one new table = one new migration file. Prefer migrate --seed over migrate:fresh where data must be preserved.

## Pre-handover: edit create migrations, never data-move mods
Development / pre-handover: prefer editing the original create_* migration when a column should never have existed (e.g. drop products.category_id). One new table = one new create migration (e.g. category_product). Do not add Schema::table data-copy + drop-column migrations for greenfield schema changes. Post-handover shared DBs remain modification-only.

## Discount type belongs on create migration
Pre-handover: put new columns on the original create_* migration (e.g. branch_product_prices.discount_type). Do not ship a separate Schema::table + data backfill migration for greenfield schema.
