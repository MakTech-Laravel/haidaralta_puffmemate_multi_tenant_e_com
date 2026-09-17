---
paths:
  - resources/js/pages/platform/catalog/products/show.tsx
  - 'resources/js/pages/platform/catalog/products/**'
  - resources/js/pages/branch/catalog/products/show.tsx
---

# Products

## Product variants on show are in-memory DataTable
Product show variants use in-memory DataTable (toClientDataTablePayload + showPagination=false + DataTableRowActions). Keep create/edit dialogs. Do not add ProductVariantDataTable/Meilisearch unless a product regularly exceeds ~50 variants and needs server search.

## Catalog Manage branches overview
Platform catalog products expose Manage branches (permission manage-branch-inventories) to GET platform.products.branches — a product-centric list of every branch with stock/price snapshots. Row **Manage stock** (product known, no branch yet) goes to `stock/manage?product=&locked=product`. Row **Manage stock** with a known pair (branch network / Manage branches / branch catalog) goes to `stock/manage?branch=&product=&locked=both`. Wire from products index row actions and product show header; do not nest this under branch-first inventory only.

## Manage branches uses server DataTable
Manage branches list is a server DataTable (filter[search|missing_price], sort, per_page, page) via useDataTableQuery — not client-only filtering. Keep DemoStatCard summary from server summary prop (`branches`, `missing_price`).

## Catalog product show is a compact profile with the branch table in-page
Platform catalog product show matches category profile: one Listing details card with a 14–16rem ProductGallery `layout="tile"` rail (`items-start`) plus a 3-col fact grid. Tile shows a compact square and a horizontal thumb strip when gallery images exist. Image background, status, and timestamps are Facts in that grid — not a Media card, not a Catalog record card, not a footer bar. Specs are a wrapping tile grid (label over value, 2–4 cols, no bordered table, no max-h scroll). Branch network stays its own card with `ProductBranchInventoryDataTable::makeForProduct`. Variants render only when `catalog.show_product_variants`. Header actions are real `Button`s in a row (same outline/primary as All products / Edit) — never `DataTableRowActions`. Platform: All products, Manage stock, Manage branches, Stock transactions, Delete, Edit.

## Branch product show has no branch network
`GET branch.products.show` renders `branch/catalog/products/show`: listing details only (`BranchProductSummary`), no Branch network table, no Inventory & pricing. Header buttons sit side by side like All products / Edit: All products, Manage stock, Stock transactions, Edit. Never paste list-page `DataTableRowActions` onto show. Branch cannot destroy products.

## Keep product variants until owner confirmation
Do not remove `product_variants` / variant stock/price without explicit site-owner go-ahead. Owner checklist: (1) Do any products sell as pack and carton (or flavor SKUs) under one listing? → keep variants. (2) Is every listing exactly one barcode/SKU? → schedule variant removal later. (3) Confirm tag vocabulary and max nav count (default 6). Figma “Variant: Gold” is a marketing specs row, not necessarily a ProductVariant SKU.

## Hide variants when catalog flag is off
Hide Variants count column and variant snapshot cards when catalog.show_product_variants is false. Product branch summary is { branches, missing_price } — no enabled/low_stock.

## Product and branch rows carry stock actions
Product list and branch-network rows expose Stock transactions via `stockActionItems` in `@/lib/stock-query` (same query filters, `locked` omitted). Daily stock, selling price, and discounts stay under **Manage stock**. Catalog **Manage stock** with a known pair opens that page locked. Do not add Add stock, Update price, or Add discount row actions. Product/category list menus use `DataTableRowActions` `groups` with labels: products → Catalog / Stock / Danger zone; categories → Merchandising / Catalog / Danger zone; branch network / Manage branches → Catalog (Manage stock) / Stock. Category Catalog group includes View products → products index with `filter[category_id]`.

## Product show matches category profile
Platform product show is one Listing details card (not a 50/50 Media + details split). Compact tile rail + 3-col facts including image background. Specs fold into the same card; there is no Catalog record card.
