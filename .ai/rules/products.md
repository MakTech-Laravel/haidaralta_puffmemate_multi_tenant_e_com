---
paths:
  - resources/js/pages/platform/catalog/products/show.tsx
  - 'resources/js/pages/platform/catalog/products/**'
---

# Products

## Product variants on show are in-memory DataTable
Product show variants use in-memory DataTable (toClientDataTablePayload + showPagination=false + DataTableRowActions). Keep create/edit dialogs. Do not add ProductVariantDataTable/Meilisearch unless a product regularly exceeds ~50 variants and needs server search.

## Catalog Manage branches overview
Platform catalog products expose Manage branches (permission manage-branch-inventories) to GET platform.products.branches — a product-centric list of every branch with enable/stock/price snapshots and Manage → platform.branches.products.show. Wire from products index row actions and product show header/alert; do not nest this under branch-first inventory only.

## Manage branches uses server DataTable
Manage branches list is a server DataTable (filter[search|enabled|low_stock|missing_price], sort, per_page, page) via useDataTableQuery — not client-only filtering. Keep DemoStatCard summary from server summary prop.

## Catalog product show is a merchandising profile
Platform catalog product show is a PDP-style profile: large primary media stage (color_code tile, object-contain, lightbox), listing details (SKU/slug/brand/categories/tags/description/background), specs, catalog record timestamps, DemoStatCard snapshots (variants/media/branch enablement), and the existing in-memory variants table. Pass ProductBranchInventoryPayload::summary as branchSummary. Do not use a tiny Avatar for the primary image.

## Keep product variants until owner confirmation
Do not remove `product_variants` / variant stock/price without explicit site-owner go-ahead. Owner checklist: (1) Do any products sell as pack and carton (or flavor SKUs) under one listing? → keep variants. (2) Is every listing exactly one barcode/SKU? → schedule variant removal later. (3) Confirm tag vocabulary and max nav count (default 6). Figma “Variant: Gold” is a marketing specs row, not necessarily a ProductVariant SKU.
