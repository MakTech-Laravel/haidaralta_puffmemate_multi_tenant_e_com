---
paths:
  - 'resources/js/pages/{platform,branch}/catalog/products/show.tsx'
---

# Platformbranch Catalog Products

## Product show vs Manage stock
Stock Manage never inlines Listing details. After a pair is picked, show View details to platform.products.show or branch.products.show. Branch show is listing only (BranchProductSummary) — no Branch network table. Product show headers use side-by-side `Button`s (outline + primary Edit), not the list-page `DataTableRowActions` ⋯ menu. Platform: All products, Manage (`stock/manage?product=&locked=product`), Manage branches, Stock transactions, Delete, Edit. Branch: All products, Manage (`locked=both`), Stock transactions, Edit.

## Show header buttons not list menu
Product show headers use side-by-side Button components in the same row as All products / Edit (outline + primary). Never copy DataTableRowActions from the list page onto show. Platform: All products, Manage (`stock/manage?product=&locked=product`), Manage branches, Stock transactions, Delete, Edit. Branch: All products, Manage, Stock transactions, Edit.
