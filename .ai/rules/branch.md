---
paths:
  - 'app/Models/{Category,Product,ProductVariant,BranchProduct,Inventory,InventoryTransaction,BranchProductPrice}.php'
  - 'app/Services/Catalog/**'
  - 'app/Services/Inventory/**'
---

# Catalog

## Soft-delete vs branch enablement
Global catalog tables (categories, products, product_variants) are soft-deletable and only platform `manage-catalog` may delete/restore. Branches never destroy catalog rows — they toggle `branch_products.status` for their own branch. Platform product create leaves all branches disabled; branch create enables only the creating branch. Stock/price mutations go through InventoryService / BranchPricingService scoped by `branch_id`.
