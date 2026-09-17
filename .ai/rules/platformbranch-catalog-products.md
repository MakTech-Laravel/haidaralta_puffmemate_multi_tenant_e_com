---
paths:
  - 'resources/js/pages/{platform,branch}/catalog/products/show.tsx'
---

# Platformbranch Catalog Products

## Product show vs Manage stock
Stock Manage never inlines Listing details. After a pair is picked, show View details to platform.products.show or branch.products.show. Branch show is listing only (BranchProductSummary) with Catalog/Stock DataTableRowActions — no Branch network table. Platform show keeps Branch network and uses the same Catalog/Stock/Danger zone action groups as the products index, plus primary Edit.
