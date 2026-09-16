---
paths:
  - 'app/Models/{Category,Product,ProductVariant,Inventory,InventoryTransaction,BranchProductPrice}.php'
  - 'app/Services/Catalog/**'
  - 'app/Services/Inventory/**'
  - app/Models/ProductImage.php
  - 'app/Models/{Category,Product}.php'
  - 'app/Models/Driver*.php'
---

# Catalog

## Soft-delete vs branch-scoped edits
Global catalog tables (categories, products, product_variants) are soft-deletable and only platform `manage-catalog` may delete/restore. Branches may **update** the same global category/product rows under `manage-branch-products`, but never destroy or restore them. Branch **category create** is gated by `config('catalog.branch_can_create_categories')` (default false; abort 404 and hide Add category). Branch **category list/show/edit** is gated by `config('catalog.branch_can_view_categories')` (default false; abort 404 and collapse sidebar to Products). There is no per-branch enablement: an active product is listed at every branch, and only stock/price are branch-scoped, through InventoryService / BranchPricingService keyed by `branch_id`.

## Primary image vs product_images gallery
products.image is the primary listing image. product_images holds gallery photos (path + sort_order). Soft-deleting a product leaves gallery rows until cascade/hard cleanup; prefer deleting gallery files via ProductService when removing gallery ids.

## Products belong to many categories via category_product
Products↔categories are belongsToMany through category_product (not products.category_id). Forms/API use category_ids (required min 1). List filters keep filter[category_id] meaning “has this category”. Scout filterable attribute is category_ids (array). Display uses categoryNamesLabel() / joined category_name.

## Catalog color_code JSON background modes
categories.color_code and products.color_code are nullable JSON: transparent | solid(+color) | gradient(+from,to,angle). Normalize via CatalogBackgroundColor (PHP) and ColorPicker / colorPickerStyle (TS). No radial persistence until enum extended. No new color npm package unless approved.

## Catalog kill-switches live in config
Category kill-switches live in config/catalog.php and are shared to Inertia as catalog: branch_can_create_categories, branch_can_view_categories (both default false), show_parent_category. When view is false, every branch category route aborts 404 (keep the routes). Controllers read config(); React reads usePage().props.catalog. Do not add CatalogFeatures or duplicate TS constants. Flip the keys to restore hidden list/create/parent UI.

## Adjustment is counted on-hand plus reason
Adjustment quantity is the counted on-hand total and requires InventoryAdjustmentReason. New entry and Reduce are unsigned deltas (abs add/subtract). Compute after/variance only inside InventoryService::adjust() after lockForUpdate — controllers never pre-compute variance. Reject zero variance. Store signed variance as txn quantity for Adjustment; in/out store a positive units-moved count. Reason is null for in/out and future reserve/sale.
