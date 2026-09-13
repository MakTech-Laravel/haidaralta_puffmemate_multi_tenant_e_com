---
paths:
  - 'app/Models/{Category,Product,ProductVariant,BranchProduct,Inventory,InventoryTransaction,BranchProductPrice}.php'
  - 'app/Services/Catalog/**'
  - 'app/Services/Inventory/**'
  - app/Models/ProductImage.php
---

# Catalog

## Soft-delete vs branch enablement
Global catalog tables (categories, products, product_variants) are soft-deletable and only platform `manage-catalog` may delete/restore. Branches never destroy catalog rows — they toggle `branch_products.status` for their own branch. Platform product create leaves all branches disabled; branch create enables only the creating branch. Stock/price mutations go through InventoryService / BranchPricingService scoped by `branch_id`.

## Primary image vs product_images gallery
products.image is the primary listing image. product_images holds gallery photos (path + sort_order). Soft-deleting a product leaves gallery rows until cascade/hard cleanup; prefer deleting gallery files via ProductService when removing gallery ids.
