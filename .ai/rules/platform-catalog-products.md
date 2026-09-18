---
paths:
  - 'resources/js/lib/stock-query.ts, resources/js/pages/**/products/index.tsx, resources/js/pages/platform/catalog/products/branches.tsx'
---

# Platform Catalog Products

## Branch product row actions are grouped
Row menus use labeled DataTableRowActions groups. Catalog is View / Manage branches / Edit. Stock is Manage stock, Stock transactions, and Price history via stockActionItems (manage URL optional; reports use the same product+branch query with locked omitted). Platform {branch} products is Catalog (View) / Stock. Branch-network / Manage branches is Stock only. Never use a flat items list on those rows. Do not add Add stock, Update price, or Add discount.
