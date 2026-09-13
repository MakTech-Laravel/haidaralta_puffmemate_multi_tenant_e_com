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
