---
paths:
  - 'app/Models/{Category,Product,ProductVariant,BranchProduct,Inventory,InventoryTransaction,BranchProductPrice}.php'
  - 'app/Services/Catalog/**'
  - 'app/Services/Inventory/**'
  - app/Models/ProductImage.php
  - 'app/Models/{Category,Product}.php'
  - 'app/Models/Driver*.php'
---

# Catalog

## Soft-delete vs branch enablement
Global catalog tables (categories, products, product_variants) are soft-deletable and only platform `manage-catalog` may delete/restore. Branches may **create and update** the same global category/product rows under `manage-branch-products`, but never destroy or restore them — they toggle `branch_products.status` for their own branch. Platform product create leaves all branches disabled; branch create enables only the creating branch. Stock/price mutations go through InventoryService / BranchPricingService scoped by `branch_id`.

## Primary image vs product_images gallery
products.image is the primary listing image. product_images holds gallery photos (path + sort_order). Soft-deleting a product leaves gallery rows until cascade/hard cleanup; prefer deleting gallery files via ProductService when removing gallery ids.

## Products belong to many categories via category_product
Products↔categories are belongsToMany through category_product (not products.category_id). Forms/API use category_ids (required min 1). List filters keep filter[category_id] meaning “has this category”. Scout filterable attribute is category_ids (array). Display uses categoryNamesLabel() / joined category_name.

## Catalog color_code JSON background modes
categories.color_code and products.color_code are nullable JSON: transparent | solid(+color) | gradient(+from,to,angle). Normalize via CatalogBackgroundColor (PHP) and ColorPicker / colorPickerStyle (TS). No radial persistence until enum extended. No new color npm package unless approved.
