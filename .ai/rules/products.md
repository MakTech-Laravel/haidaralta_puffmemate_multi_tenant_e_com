---
paths:
  - resources/js/pages/platform/catalog/products/show.tsx
  - 'resources/js/pages/platform/catalog/products/**'
---

# Products

## Product variants on show are in-memory DataTable
Product show variants use in-memory DataTable (toClientDataTablePayload + showPagination=false + DataTableRowActions). Keep create/edit dialogs. Do not add ProductVariantDataTable/Meilisearch unless a product regularly exceeds ~50 variants and needs server search.

## Catalog Manage branches overview
Platform catalog products expose Manage branches (permission manage-branch-inventories) to GET platform.products.branches — a product-centric list of every branch with stock/price snapshots and Manage → platform.branches.products.show. Wire from products index row actions and product show header/alert; do not nest this under branch-first inventory only.

## Manage branches uses server DataTable
Manage branches list is a server DataTable (filter[search|missing_price], sort, per_page, page) via useDataTableQuery — not client-only filtering. Keep DemoStatCard summary from server summary prop (`branches`, `missing_price`).

## Catalog product show is a compact profile with the branch table in-page
Platform catalog product show pairs a compact ProductGallery `layout="tile"` (14–16rem rail, same as category profile — not a `min-h-96` hero) with listing details, scrolling specs (`max-h-64 overflow-y-auto`), and catalog record timestamps. No DemoStatCards. The Branch network card embeds the server-paginated branches table: `ProductBranchInventoryDataTable::makeForProduct` passed as `table`, driven by `useDataTableQuery` against `platform.products.show`. Variants block renders only when `catalog.show_product_variants`.

## Keep product variants until owner confirmation
Do not remove `product_variants` / variant stock/price without explicit site-owner go-ahead. Owner checklist: (1) Do any products sell as pack and carton (or flavor SKUs) under one listing? → keep variants. (2) Is every listing exactly one barcode/SKU? → schedule variant removal later. (3) Confirm tag vocabulary and max nav count (default 6). Figma “Variant: Gold” is a marketing specs row, not necessarily a ProductVariant SKU.

## Hide variants when catalog flag is off
Hide Variants count column and variant snapshot cards when catalog.show_product_variants is false. Product branch summary is { branches, missing_price } — no enabled/low_stock.

## Product and branch rows carry stock actions
Product list and branch-network rows expose Add stock, Stock transactions, Update price, Add discount via `stockActionItems` in `@/lib/stock-query`. From a products list the product is locked; from a product's branch list both are locked; Stock transactions passes the same selection as filters (no `locked`).
