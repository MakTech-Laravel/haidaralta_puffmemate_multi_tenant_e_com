---
paths:
  - 'app/DataTables/**'
  - 'resources/js/components/data-table/**'
  - 'resources/js/pages/**/index.tsx'
  - app/DataTables/DatabaseBackupZipDataTable.php
  - app/DataTables/BranchProductListingDataTable.php
  - app/DataTables/BranchCategoryDataTable.php
  - app/DataTables/ProductBranchInventoryDataTable.php
---

# Data Tables

## Spatie QueryBuilder DataTables
List indexes use Spatie QueryBuilder via App\DataTables\*DataTable extending AbstractDataTable. URL: `filter[key]`, `sort=-col`, `per_page`, `page`. Models may use HasDataTableConfig (`$dataTableSearchable` / Sortable / Filterable). SoftDeletes lists use `AllowedFilter::trashed()` (`with`|`only`). Frontend: `@/components/data-table` with TanStack v8 + Spatie query keys via `useDataTableQuery`. Do not add niche datatable packages.

## Query contract
Only `filter[]`, `sort`, `per_page`, `page` — no legacy `?search=` / `?status=` dual APIs. Row actions use `DataTableRowActions` (View / Edit / Delete / Restore); destructive labels use `text-destructive`, never wrap items in a `<div>` (breaks Radix). Image-first columns where media exists. Empty states + per-page footer on server lists.

## Scout vs SQL
Free-text search may use Scout/Meilisearch only on Searchable models when driver is meilisearch. Filters, sorts, and pagination stay SQL.

## Intentional exceptions (do not force Eloquent *DataTable)
- **Permissions groups** — static Spatie catalog; shared `PermissionGroupsTable` (grouped UX + client search). See `.ai/rules/permissions.md`.
- **Backup zip archives** — filesystem metadata via `DatabaseBackupZipDataTable` (`archives_*` query keys so history `filter[]`/`sort`/`per_page`/`page` stay free). Prop: `archives`.
- **In-page product variants** — small per-product set; `toClientDataTablePayload` + `DataTable` with `showPagination={false}`. No Meilisearch unless variant search becomes a product requirement.

## Backup zip DataTable uses archives_* query keys
Backup zip listing is filesystem metadata, not Eloquent. Use DatabaseBackupZipDataTable + archives_* query keys (archives_search/sort/per_page/page) so it coexists with operator history DataTable on the same page. Frontend prop is archives. No Meilisearch.

## Branch listing makeForBranch + filters
Use BranchProductListingDataTable::makeForBranch (not make) — child signature cannot override AbstractDataTable::make. Filters: enabled (branch_products.status) and low_stock (product-level inventory available <= minimum).

## Product → branches inventory DataTable
Platform catalog Manage branches uses ProductBranchInventoryDataTable::makeForProduct (Scout free-text on Branch; SQL filters enabled / low_stock / missing_price; paginate+sort). Pair with ProductBranchInventoryPayload::product + ::summary for header cards (summary is product-wide, not the current page). Page: platform/catalog/products/branches.

## Branch categories DataTable no trash
Branch category index uses BranchCategoryDataTable without AllowedFilter::trashed — soft-delete UI stays platform-only.
