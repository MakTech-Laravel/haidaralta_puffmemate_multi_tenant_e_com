---
paths:
  - 'resources/js/components/branch/product-inventory-panel.tsx'
  - 'resources/js/pages/{platform,branch}/**/products/show.tsx'
  - 'app/Support/ProductManageRedirect.php'
  - 'routes/{platform,branch}.php'
---

# Discounts

## Discounts live on product manage
There is no bulk Discounts workspace. Selling price and discount type/value/dates are edited on the product manage Inventory & pricing card (`ProductInventoryPanel`) for one branch + one SKU (including variants). Hydrate saved fixed/percent offers so Save is not destructive. Empty SKUs and prices with `discount_type` none/missing select None and hide value + dates.

## Old discount URLs redirect
GET/PUT `/discounts`, `/discounts/preview`, and legacy `/stock/discount` go through `App\Support\ProductManageRedirect` (same destinations as retired Manage stock / Update price). Both IDs → product manage show; platform product-only → Manage branches; otherwise products index. Branch portal product-only → that branch’s product show. Do not restore DiscountController or `applyDiscount` / `previewSelectedPairs`.

## Retired stock URLs resolve through ProductManageRedirect
Old stock/manage, stock/price, and /discounts URLs redirect here. Both IDs go to product manage show; platform product-only goes to Manage branches; otherwise products index. Branch portal product-only goes to that branch product show. Invalid filled ids 404.
