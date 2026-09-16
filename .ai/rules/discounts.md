---
paths:
  - 'resources/js/components/discounts/**'
  - 'resources/js/pages/{platform,branch}/discounts/**'
  - 'app/Http/Controllers/{Platform,Branch}/DiscountController.php'
  - 'app/Http/Requests/Discount/**'
---

# Discounts

## Discounts is its own workspace
Discounts lives under `resources/js/pages/{platform,branch}/discounts` and `resources/js/components/discounts` — never under stock or catalog. The page is a full-width workspace (`flex w-full flex-col gap-4 p-2 md:p-4`) with InfiniteScrollMultiCombobox pickers. Discount type SelectTrigger is `w-full` with SelectValue placeholder "Select type". Save replaces type/value/dates per selected pair and keeps selling_price. Branch portal locks the current branch; row-action entry pre-selects without locking pickers.

## Discounts has its own controller and routes
Platform and branch DiscountController own GET/PUT `/discounts` and GET `/discounts/preview` (names `platform.discounts` / `branch.discounts`). Do not put discount actions on StockController or under `stock/` URLs. Old `/stock/discount` URLs permanently redirect. Writes accept `branch_ids`+`product_ids`, keep selling_price, replace the offer, 404 on branch portal if another branch id is posted. Picker option JSON stays on `stock/branch-options` and `stock/product-options`.
