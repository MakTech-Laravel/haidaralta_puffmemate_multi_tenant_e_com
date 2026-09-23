---
paths:
  - 'app/DataTables/**'
  - 'resources/js/components/data-table/**'
  - 'resources/js/pages/**/index.tsx'
  - app/DataTables/DatabaseBackupZipDataTable.php
  - app/DataTables/BranchProductListingDataTable.php
  - app/DataTables/BranchCategoryDataTable.php
  - app/DataTables/ProductBranchInventoryDataTable.php
  - app/DataTables/BranchStaffDataTable.php
  - app/DataTables/BranchRoleDataTable.php
---

# Data Tables

## Spatie QueryBuilder DataTables

List indexes use Spatie QueryBuilder via App\DataTables\*DataTable extending AbstractDataTable. URL: `filter[key]`, `sort=-col`, `per_page`, `page`. Models may use HasDataTableConfig (`$dataTableSearchable` / Sortable / Filterable). SoftDeletes lists use `AllowedFilter::trashed()` (`with`|`only`). Frontend: `@/components/data-table` with TanStack v8 + Spatie query keys via `useDataTableQuery`. Do not add niche datatable packages.

## Query contract

Only `filter[]`, `sort`, `per_page`, `page` — no legacy `?search=` / `?status=` dual APIs. Row actions use `DataTableRowActions` (View / Edit / Delete / Restore); destructive labels use `text-destructive`, never wrap items in a `<div>` (breaks Radix). For multi-section menus pass `groups={[{ label, items }]}` (product lists: Catalog / Stock / Danger zone; categories: Merchandising / Catalog / Danger zone; platform branches index: Branch / People / Stock / Danger zone; branch product and branch-network rows: Catalog / Stock or Stock only). Empty groups are dropped. Image-first columns where media exists. Empty states + per-page footer on server lists.

## Scout vs SQL

Free-text search may use Scout/Meilisearch only on Searchable models when driver is meilisearch. Filters, sorts, and pagination stay SQL.

## Intentional exceptions (do not force Eloquent *DataTable)

- **Permissions groups** — static Spatie catalog; shared `PermissionGroupsTable` (grouped UX + client search). See `.ai/rules/permissions.md`.
- **Backup zip archives** — filesystem metadata via `DatabaseBackupZipDataTable` (`archives_*` query keys so history `filter[]`/`sort`/`per_page`/`page` stay free). Prop: `archives`.
- **In-page product variants** — small per-product set; `toClientDataTablePayload` + `DataTable` with `showPagination={false}`. No Meilisearch unless variant search becomes a product requirement.

## Backup zip DataTable uses archives_* query keys

Backup zip listing is filesystem metadata, not Eloquent. Use DatabaseBackupZipDataTable + archives_* query keys (archives_search/sort/per_page/page) so it coexists with operator history DataTable on the same page. Frontend prop is archives. No Meilisearch.

## Branch listing makeForBranch + filters

Use BranchProductListingDataTable::makeForBranch (not make) — child signature cannot override AbstractDataTable::make. Filters: category_id (scalar or array OR-match), status (catalog), missing_price (product for branch without active selling price). filterOptions.categories are the currently selected rows only; pickers load categories/options. Platform index passes includeImage true and BranchProductListingPayload::summary (products/missing_price, branch-wide). No enabled/low_stock filters.

## Product → branches inventory DataTable

Platform catalog Manage branches uses ProductBranchInventoryDataTable::makeForProduct (Scout free-text on Branch; SQL filter missing_price; paginate+sort). Pair with ProductBranchInventoryPayload::product + ::summary for header cards (summary is product-wide, not the current page: branches/missing_price). Page: platform/catalog/products/branches.

## Branch categories DataTable no trash

Branch category index uses BranchCategoryDataTable without AllowedFilter::trashed — soft-delete UI stays platform-only.

## Listings use quantity and discounted price

Branch/platform product listings use quantity as Stock. No Enabled column or toggle (route gone). No enabled/low_stock filters. Discounted prices show strikethrough selling_price plus effective_price when has_discount; otherwise strikethrough regular_price plus selling when has_compare_at. Do not show buying_price on listings. Summary cards are { products, missing_price } or { branches, missing_price }.

## Stock transaction filters do not dump catalogs

InventoryTransactionDataTable filterOptions carry types plus the currently selected branch/product only. Platform ledger columns are Product (name + SKU), then Branch, then type/reason/qty/buying. Product and branch pickers load pages from stock/*-options JSON. Branch portal omits the branch column, branch filter, and branch text search. The page is `*/reports/stock-transactions`.

## Price history filters do not dump catalogs

BranchProductPriceHistoryDataTable filterOptions carry sources plus the currently selected branch/product only. Platform columns are Product (name + SKU + variant), then Branch, then source/changes/when/by. Product and branch pickers load pages from stock/*-options JSON. Branch portal omits the branch column, branch filter, and branch text search. The page is `*/reports/price-history`.

## DataTable cells truncate with overflow tooltip

Non-action/image DataTable cells truncate with max-w-[14rem] and DataTableTruncated (overflow-only Tooltip from innerText). Composite cells (avatar, name+subtitle, badges, price stacks) set meta.truncate: false and wrap each text line in DataTableTruncated instead of the whole block.

## useDataTableQuery must not visit on mount

useDataTableQuery must not router.get on mount. React Strict Mode double-invokes effects in Vite/dev, so never use a one-shot skip ref. Visit only when search differs from the last committed value, and skip when the built query already matches the current URL. Never serialize UI sentinels into filter[] (trashed=active, status=all); Spatie trashed is with|only or omitted.

## User list avatars use users.avatar

Image-first user columns (platform admins, drivers, and other user lists) read users.avatar and expose avatar_url via MediaUrl. Composite name+phone cells set meta.truncate: false. Do not read driver_profiles for avatars.

## exportQuery unwraps Spatie QueryBuilder for Excel

DataTable exportQuery() applies the same Spatie filter[]/sort contract as the list, then returns QueryBuilder::getEloquentBuilder() because Spatie QueryBuilder v7 does not extend Eloquent Builder and Laravel Excel FromQuery rejects the wrapper. Branch portal scopedBranchId is never stripped, including scope=all. Do not invent a second query API for exports.

## Scoped branch staff DataTable has role and status filters

Scoped BranchStaffDataTable (forBranch) exposes filter[role] and filterOptions for is_active plus RoleEnum labels, and eager-loads roles. Set the Spatie team to the branch before role(). Do not dump branch pickers when scoped. Global acrossBranches keeps filter[branch_id] and filterOptions.selected_branch only (currently filtered row)—never filterOptions.branches.

## Branch roles DataTable uses makeForBranch
BranchRoleDataTable uses makeForBranch(Request, Branch), not make — child signatures cannot override AbstractDataTable::make(Request). RoleController index calls makeForBranch.
