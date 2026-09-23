---
paths:
    - 'resources/js/pages/{platform,branch}/catalog/products/show.tsx'
---

# Platformbranch Catalog Products

## Product show vs Manage stock

Stock Manage never inlines Listing details. After a pair is picked, show View details to platform.products.show or branch.products.show. Branch show is listing only (BranchProductSummary) — no Branch network table. Product show headers use side-by-side `Button`s (outline + primary Edit), not the list-page `DataTableRowActions` ⋯ menu. Platform: All products, Manage stock (`stock/manage?product=`), Manage branches, Stock transactions, Price history, Delete, Edit. Branch: All products, Manage stock (`stock/manage?branch=&product=`), Stock transactions, Price history, Edit.

## Show header buttons not list menu

Product show headers use side-by-side Button components in the same row as All products / Edit (outline + primary). Never copy DataTableRowActions from the list page onto show. Platform: All products, Manage stock (`stock/manage?product=`), Manage branches, Stock transactions, Price history, Delete, Edit. Branch: All products, Manage stock, Stock transactions, Price history, Edit.

## Product show includes Price history

Product show headers keep side-by-side Buttons. Platform: All products, Manage stock, Manage branches, Stock transactions, Price history, Delete, Edit. Branch: All products, Manage stock, Stock transactions, Price history, Edit. Price history uses stockPageUrl on reports/price-history (product only on platform; product + branch on branch). List-row Stock groups also include Price history via stockActionItems.
